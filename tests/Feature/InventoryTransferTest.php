<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductColor;
use App\Models\User;
use App\Models\UserProduct;
use App\Services\InventoryTransferService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventoryTransferTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => false,
            'cache.default' => 'array',
            'session.driver' => 'array',
        ]);
        Notification::fake();
        $this->withoutMiddleware(\App\Http\Middleware\HandleInertiaRequests::class);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('credit', 20, 4)->default(0);
            $table->decimal('debit', 20, 4)->default(0);
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->unsignedInteger('role_id');
            $table->unsignedInteger('country_id');
            $table->timestamps();
        });
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedInteger('country_id');
            $table->string('barcode')->nullable();
            $table->text('sizes')->nullable();
            $table->decimal('cost_price', 20, 4)->default(0);
            $table->decimal('retail_price', 20, 4)->default(0);
            $table->decimal('sale_price', 20, 4)->default(0);
            $table->decimal('price_before_discount', 20, 4)->nullable();
            $table->timestamps();
        });
        Schema::create('colors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->string('code')->nullable();
            $table->timestamps();
        });
        Schema::create('product_colors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('color_id');
            $table->unsignedInteger('country_id');
            $table->integer('stock')->default(0);
            $table->string('barcode');
            $table->text('sizes')->nullable();
            $table->timestamps();
        });
        Schema::create('user_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_color_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('merchant_id')->nullable();
            $table->unsignedInteger('country_id');
            $table->string('size');
            $table->string('barcode');
            $table->integer('stock');
            $table->decimal('wholesale_price', 20, 4)->nullable();
            $table->decimal('retail_price', 20, 4)->nullable();
            $table->decimal('price_before_discount', 20, 4)->nullable();
            $table->boolean('approved')->default(false);
            $table->timestamps();
        });
        Schema::create('user_product_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_product_id');
            $table->text('note');
            $table->boolean('approved')->default(false);
            $table->timestamps();
        });
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('label');
            $table->decimal('rate', 20, 6);
            $table->timestamps();
        });
        DB::table('currencies')->insert([
            ['name' => 'aed', 'label' => 'AED', 'rate' => 3.67, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'syp', 'label' => 'SYP', 'rate' => 13000, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_admin_transfer_stores_prices_in_usd_and_deducts_each_size(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 2, 'admin@example.test');
        $shop = $this->user(User::ROLE_SHOP, 2, 'shop@example.test');
        $productColor = $this->productColor(2, [
            ['size' => 'M', 'barcode' => 'PC-M', 'stock' => 5],
            ['size' => 'L', 'barcode' => 'PC-L', 'stock' => 3],
        ]);

        $response = $this->actingAs($admin)->postJson(route('userProducts.store'), [
            'destination_user_id' => $shop->id,
            'product_color_id' => $productColor->id,
            'currency_code' => 'AED',
            'retail_price' => 367,
            'wholesale_price' => 183.5,
            'items' => [
                ['size' => 'M', 'barcode' => 'PC-M', 'quantity' => 2],
                ['size' => 'L', 'barcode' => 'PC-L', 'quantity' => 1],
            ],
        ]);

        $response->assertOk()->assertJson([
            'success' => true,
            'destination_user_id' => $shop->id,
            'retail_price_usd' => 100,
            'wholesale_price_usd' => 50,
        ]);
        $this->assertDatabaseHas('user_products', [
            'user_id' => $shop->id,
            'barcode' => 'PC-M',
            'stock' => 2,
            'retail_price' => 100,
            'wholesale_price' => 50,
        ]);
        $this->assertSame(5, $productColor->fresh()->stock);
    }

    public function test_warehouse_transfer_updates_existing_destination_price_and_stock(): void
    {
        $warehouse = $this->user(User::ROLE_WAREHOUSE, 4, 'warehouse@example.test');
        $shop = $this->user(User::ROLE_SHOP, 4, 'syria-shop@example.test');
        $productColor = $this->productColor(4, [
            ['size' => 'M', 'barcode' => 'SY-M', 'stock' => 20],
        ]);
        $source = $this->stock($warehouse, $productColor, 'M', 'SY-M', 5, 0, 0);
        $destination = $this->stock($shop, $productColor, 'M', 'SY-M', 1, 0, 0);

        app(InventoryTransferService::class)->transfer($warehouse, [
            'destination_user_id' => $shop->id,
            'product_color_id' => $productColor->id,
            'currency_code' => 'SYP',
            'retail_price' => 1300000,
            'wholesale_price' => 650000,
            'price_before_discount' => null,
            'merchant_id' => null,
            'items' => [['size' => 'M', 'barcode' => 'SY-M', 'quantity' => 2]],
        ]);

        $this->assertSame(3, $source->fresh()->stock);
        $this->assertSame(3, $destination->fresh()->stock);
        $this->assertEquals(100, $destination->fresh()->retail_price);
        $this->assertEquals(50, $destination->fresh()->wholesale_price);
    }

    public function test_warehouse_destinations_exclude_itself_and_keep_other_local_warehouses(): void
    {
        $warehouse = $this->user(User::ROLE_WAREHOUSE, 4, 'source-warehouse@example.test');
        $otherWarehouse = $this->user(User::ROLE_WAREHOUSE, 4, 'destination-warehouse@example.test');
        $shop = $this->user(User::ROLE_SHOP, 4, 'local-shop@example.test');
        $this->user(User::ROLE_MERCHANT, 4, 'merchant@example.test');
        $this->user(User::ROLE_SHOP, 2, 'foreign-shop@example.test');

        $destinations = app(InventoryTransferService::class)->destinationsFor($warehouse);

        $this->assertEqualsCanonicalizing(
            [$otherWarehouse->id, $shop->id],
            $destinations->pluck('id')->all()
        );
        $this->assertFalse($destinations->contains('id', $warehouse->id));
    }

    public function test_product_color_transfer_availability_uses_warehouse_stock_not_central_stock(): void
    {
        $warehouse = $this->user(User::ROLE_WAREHOUSE, 4, 'availability-warehouse@example.test');
        $productColor = $this->productColor(4, [
            ['size' => 'M', 'barcode' => 'AVAILABLE-M', 'stock' => 10],
            ['size' => 'L', 'barcode' => 'AVAILABLE-L', 'stock' => 8],
        ]);
        $this->stock($warehouse, $productColor, 'M', 'AVAILABLE-M', 3, 20, 10);

        $availability = app(InventoryTransferService::class)
            ->availabilityFor($warehouse, collect([$productColor]));

        $this->assertSame([
            ['size' => 'M', 'barcode' => 'AVAILABLE-M', 'stock' => 3],
        ], $availability[$productColor->id]);
    }

    public function test_product_color_transfer_availability_keeps_central_stock_for_admin(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 4, 'availability-admin@example.test');
        $productColor = $this->productColor(4, [
            ['size' => 'M', 'barcode' => 'CENTRAL-M', 'stock' => 10],
            ['size' => 'L', 'barcode' => 'CENTRAL-L', 'stock' => 0],
        ]);

        $availability = app(InventoryTransferService::class)
            ->availabilityFor($admin, collect([$productColor]));

        $this->assertSame([
            ['size' => 'M', 'barcode' => 'CENTRAL-M', 'stock' => 10],
        ], $availability[$productColor->id]);
    }

    public function test_product_colors_json_exposes_virtual_transfer_stock_without_database_columns(): void
    {
        $warehouse = $this->user(User::ROLE_WAREHOUSE, 4, 'product-colors-page@example.test');
        $productColor = $this->productColor(4, [
            ['size' => 'M', 'barcode' => 'PAGE-M', 'stock' => 10],
        ]);
        $this->stock($warehouse, $productColor, 'M', 'PAGE-M', 3, 20, 10);

        $this->actingAs($warehouse)
            ->getJson(route('productColors.index'))
            ->assertOk()
            ->assertJsonPath('data.0.transfer_stock', 3)
            ->assertJsonPath('data.0.transfer_sizes.0.stock', 3)
            ->assertJsonPath('data.0.transfer_sizes.0.barcode', 'PAGE-M');

        $this->assertFalse(Schema::hasColumn('product_colors', 'transfer_sizes'));
        $this->assertFalse(Schema::hasColumn('product_colors', 'transfer_stock'));
    }

    public function test_warehouse_can_transfer_to_another_warehouse_in_the_same_country(): void
    {
        $warehouse = $this->user(User::ROLE_WAREHOUSE, 4, 'transfer-source@example.test');
        $destination = $this->user(User::ROLE_WAREHOUSE, 4, 'transfer-destination@example.test');
        $productColor = $this->productColor(4, [
            ['size' => 'M', 'barcode' => 'WAREHOUSE-M', 'stock' => 20],
        ]);
        $source = $this->stock($warehouse, $productColor, 'M', 'WAREHOUSE-M', 4, 20, 10);

        $this->actingAs($warehouse)->postJson(route('userProducts.store'), [
            'destination_user_id' => $destination->id,
            'product_color_id' => $productColor->id,
            'currency_code' => 'SYP',
            'retail_price' => 260000,
            'wholesale_price' => 130000,
            'items' => [
                ['size' => 'M', 'barcode' => 'WAREHOUSE-M', 'quantity' => 2],
            ],
        ])->assertOk()->assertJson([
            'success' => true,
            'destination_user_id' => $destination->id,
        ]);

        $this->assertSame(2, $source->fresh()->stock);
        $this->assertDatabaseHas('user_products', [
            'user_id' => $destination->id,
            'barcode' => 'WAREHOUSE-M',
            'stock' => 2,
        ]);
    }

    public function test_warehouse_cannot_transfer_to_itself_even_with_a_manual_request(): void
    {
        $warehouse = $this->user(User::ROLE_WAREHOUSE, 4, 'self-transfer@example.test');
        $productColor = $this->productColor(4, [
            ['size' => 'M', 'barcode' => 'SELF-M', 'stock' => 20],
        ]);
        $source = $this->stock($warehouse, $productColor, 'M', 'SELF-M', 4, 20, 10);

        $this->actingAs($warehouse)->postJson(route('userProducts.store'), [
            'destination_user_id' => $warehouse->id,
            'product_color_id' => $productColor->id,
            'currency_code' => 'SYP',
            'retail_price' => 260000,
            'wholesale_price' => 130000,
            'items' => [
                ['size' => 'M', 'barcode' => 'SELF-M', 'quantity' => 2],
            ],
        ])->assertStatus(422)
            ->assertJsonValidationErrors('destination_user_id')
            ->assertJsonFragment([
                'يجب أن تكون جهة الاستلام مختلفة عن المستودع المرسل.',
            ]);

        $this->assertSame(4, $source->fresh()->stock);
    }

    public function test_failed_multi_size_transfer_rolls_back_all_stock_and_prices(): void
    {
        $warehouse = $this->user(User::ROLE_WAREHOUSE, 2, 'rollback-warehouse@example.test');
        $shop = $this->user(User::ROLE_SHOP, 2, 'rollback-shop@example.test');
        $productColor = $this->productColor(2, [
            ['size' => 'M', 'barcode' => 'RB-M', 'stock' => 10],
            ['size' => 'L', 'barcode' => 'RB-L', 'stock' => 10],
        ]);
        $medium = $this->stock($warehouse, $productColor, 'M', 'RB-M', 5, 10, 5);
        $large = $this->stock($warehouse, $productColor, 'L', 'RB-L', 1, 10, 5);

        try {
            app(InventoryTransferService::class)->transfer($warehouse, [
                'destination_user_id' => $shop->id,
                'product_color_id' => $productColor->id,
                'currency_code' => 'USD',
                'retail_price' => 100,
                'wholesale_price' => 50,
                'price_before_discount' => null,
                'merchant_id' => null,
                'items' => [
                    ['size' => 'M', 'barcode' => 'RB-M', 'quantity' => 2],
                    ['size' => 'L', 'barcode' => 'RB-L', 'quantity' => 2],
                ],
            ]);
            $this->fail('Expected insufficient stock validation failure.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('items', $exception->errors());
        }

        $this->assertSame(5, $medium->fresh()->stock);
        $this->assertSame(1, $large->fresh()->stock);
        $this->assertDatabaseMissing('user_products', ['user_id' => $shop->id]);
    }

    public function test_invalid_or_empty_transfer_returns_validation_error_without_changes(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 2, 'invalid-admin@example.test');
        $shop = $this->user(User::ROLE_SHOP, 2, 'invalid-shop@example.test');
        $productColor = $this->productColor(2, [
            ['size' => 'M', 'barcode' => 'INVALID-M', 'stock' => 5],
        ]);

        $this->actingAs($admin)->postJson(route('userProducts.store'), [
            'destination_user_id' => $shop->id,
            'product_color_id' => $productColor->id,
            'currency_code' => 'AED',
            'retail_price' => 0,
            'wholesale_price' => 0,
            'items' => [],
        ])->assertStatus(422)->assertJsonValidationErrors([
            'retail_price', 'wholesale_price', 'items',
        ]);

        $this->assertSame(5, $productColor->fresh()->stock);
        $this->assertDatabaseCount('user_products', 0);
    }

    public function test_transfer_to_another_country_is_rejected_without_partial_changes(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 2, 'country-admin@example.test');
        $foreignShop = $this->user(User::ROLE_SHOP, 4, 'foreign-shop@example.test');
        $productColor = $this->productColor(2, [
            ['size' => 'M', 'barcode' => 'COUNTRY-M', 'stock' => 5],
        ]);

        $this->actingAs($admin)->postJson(route('userProducts.store'), [
            'destination_user_id' => $foreignShop->id,
            'product_color_id' => $productColor->id,
            'currency_code' => 'AED',
            'retail_price' => 367,
            'wholesale_price' => 183.5,
            'items' => [
                ['size' => 'M', 'barcode' => 'COUNTRY-M', 'quantity' => 1],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors('destination_user_id');

        $this->assertSame(5, $productColor->fresh()->stock);
        $this->assertDatabaseCount('user_products', 0);
    }

    private function user(int $role, int $country, string $email): User
    {
        return User::query()->create([
            'name' => $email,
            'email' => $email,
            'password' => 'password',
            'role_id' => $role,
            'country_id' => $country,
        ]);
    }

    private function productColor(int $country, array $sizes): ProductColor
    {
        $product = Product::query()->create([
            'name' => 'Test product',
            'name_en' => 'Test product',
            'barcode' => uniqid('P'),
            'cost_price' => 10,
            'retail_price' => 20,
            'country_id' => $country,
        ]);
        $colorId = DB::table('colors')->insertGetId([
            'name' => 'أسود',
            'name_en' => 'Black',
            'code' => '#000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ProductColor::query()->create([
            'product_id' => $product->id,
            'color_id' => $colorId,
            'country_id' => $country,
            'barcode' => uniqid('PC'),
            'stock' => array_sum(array_column($sizes, 'stock')),
            'sizes' => json_encode($sizes),
        ]);
    }

    private function stock(
        User $user,
        ProductColor $productColor,
        string $size,
        string $barcode,
        int $quantity,
        float $retail,
        float $wholesale
    ): UserProduct {
        return UserProduct::query()->create([
            'user_id' => $user->id,
            'product_color_id' => $productColor->id,
            'country_id' => $user->country_id,
            'size' => $size,
            'barcode' => $barcode,
            'stock' => $quantity,
            'retail_price' => $retail,
            'wholesale_price' => $wholesale,
        ]);
    }
}

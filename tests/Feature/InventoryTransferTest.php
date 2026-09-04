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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedInteger('country_id');
            $table->string('barcode')->nullable();
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

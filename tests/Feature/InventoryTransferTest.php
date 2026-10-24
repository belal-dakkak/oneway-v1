<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductColor;
use App\Models\User;
use App\Models\UserProduct;
use App\Notifications\ShopNotification;
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
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('credit', 20, 4)->default(0);
            $table->decimal('debit', 20, 4)->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'currency_code']);
        });
        Schema::create('wallet_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wallet_id');
            $table->unsignedBigInteger('user_id');
            $table->string('currency_code', 3);
            $table->string('direction', 8);
            $table->decimal('amount', 24, 4);
            $table->decimal('exchange_rate', 20, 6)->default(1);
            $table->decimal('base_amount', 24, 4);
            $table->decimal('balance_after', 24, 4);
            $table->string('payment_method', 32)->nullable();
            $table->string('source_type', 64)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('exchange_group')->nullable();
            $table->string('idempotency_key')->unique();
            $table->text('note')->nullable();
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
        Schema::create('operational_prices', function (Blueprint $table) {
            $table->id();
            $table->string('priceable_type', 120);
            $table->unsignedBigInteger('priceable_id');
            $table->string('currency_code', 3);
            $table->decimal('exchange_rate', 20, 6);
            $table->decimal('cost_price', 20, 4)->nullable();
            $table->decimal('wholesale_price', 20, 4)->nullable();
            $table->decimal('retail_price', 20, 4)->nullable();
            $table->decimal('sale_price', 20, 4)->nullable();
            $table->decimal('price_before_discount', 20, 4)->nullable();
            $table->timestamps();
            $table->unique(['priceable_type', 'priceable_id', 'currency_code']);
        });
        Schema::create('user_product_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_product_id');
            $table->text('note');
            $table->boolean('approved')->default(false);
            $table->timestamps();
        });
        Schema::create('merchant_debits', function (Blueprint $table) {
            $table->string('currency_code', 3)->nullable();
            $table->decimal('exchange_rate', 20, 6)->nullable();
            $table->id();
            $table->unsignedBigInteger('creditor_id');
            $table->unsignedBigInteger('debtor_id');
            $table->decimal('amount', 24, 4)->default(0);
            $table->timestamps();
        });
        Schema::create('debits', function (Blueprint $table) {
            $table->string('currency_code', 3)->nullable();
            $table->decimal('exchange_rate', 20, 6)->nullable();
            $table->id();
            $table->unsignedBigInteger('creditor_id');
            $table->unsignedBigInteger('debtor_id');
            $table->boolean('type')->default(0);
            $table->decimal('amount', 24, 4);
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('user_product_id')->nullable();
            $table->unsignedBigInteger('user_product_log_id')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
        });
        Schema::create('debit_logs', function (Blueprint $table) {
            $table->string('currency_code', 3)->nullable();
            $table->decimal('exchange_rate', 20, 6)->nullable();
            $table->id();
            $table->unsignedBigInteger('merchant_debit_id');
            $table->unsignedBigInteger('user_product_id')->nullable();
            $table->unsignedBigInteger('debit_payment_id')->nullable();
            $table->unsignedBigInteger('merchant_refund_id')->nullable();
            $table->unsignedBigInteger('product_color_id')->nullable();
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->unsignedBigInteger('merchant_id')->nullable();
            $table->date('request_date')->nullable();
            $table->integer('qty')->nullable();
            $table->string('type')->nullable();
            $table->text('note');
            $table->decimal('amount', 24, 4);
            $table->timestamps();
        });
        Schema::create('merchant_refunds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_product_id');
            $table->unsignedBigInteger('merchant_id');
            $table->unsignedBigInteger('merchant_debit_id');
            $table->integer('qty');
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
        Notification::assertSentTo($shop, ShopNotification::class, function ($notification, $channels) use ($shop) {
            $payload = $notification->toArray($shop);
            return in_array('database', $channels, true)
                && in_array('broadcast', $channels, true)
                && $payload['source'] === 'inventory'
                && !empty($payload['image_url'])
                && $payload['image_url'] === $payload['table']['product_color']['photo_url'];
        });
    }

    public function test_merchant_refund_reduces_stock_and_aed_debt_without_touching_cashboxes(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 2, 'refund-admin@example.test');
        $shop = $this->user(User::ROLE_SHOP, 2, 'refund-shop@example.test');
        $merchant = $this->user(User::ROLE_MERCHANT, 2, 'refund-merchant@example.test');
        $productColor = $this->productColor(2, [
            ['size' => 'M', 'barcode' => 'REFUND-M', 'stock' => 10],
        ]);
        $stock = $this->stock($shop, $productColor, 'M', 'REFUND-M', 10, 20, 10);
        $accountId = DB::table('merchant_debits')->insertGetId([
            'creditor_id' => $merchant->id, 'debtor_id' => $shop->id,
            'currency_code' => 'AED', 'exchange_rate' => 3.67, 'amount' => 367,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($admin)->postJson(route('merchantRefunds.store'), [
            'merchant_id' => $merchant->id,
            'merchant_debit_id' => $accountId,
            'user_products' => json_encode([['id' => $stock->id, 'qty' => 5]]),
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertSame(5, $stock->fresh()->stock);
        $this->assertEquals(183.5, DB::table('merchant_debits')->where('id', $accountId)->value('amount'));
        $this->assertDatabaseHas('merchant_refunds', ['user_product_id' => $stock->id, 'qty' => 5]);
        $this->assertDatabaseHas('debit_logs', ['merchant_debit_id' => $accountId, 'amount' => 183.5]);
        $this->assertSame(0, DB::table('wallet_movements')->count());
        $this->assertSame(0.0, (float) $shop->wallet->fresh()->credit - (float) $shop->wallet->fresh()->debit);
        $this->assertSame(0.0, (float) $merchant->wallet->fresh()->credit - (float) $merchant->wallet->fresh()->debit);
    }

    public function test_failed_multi_item_merchant_refund_rolls_back_every_item(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 2, 'rollback-refund-admin@example.test');
        $shop = $this->user(User::ROLE_SHOP, 2, 'rollback-refund-shop@example.test');
        $merchant = $this->user(User::ROLE_MERCHANT, 2, 'rollback-refund-merchant@example.test');
        $productColor = $this->productColor(2, [
            ['size' => 'M', 'barcode' => 'ROLLBACK-M', 'stock' => 10],
            ['size' => 'L', 'barcode' => 'ROLLBACK-L', 'stock' => 1],
        ]);
        $medium = $this->stock($shop, $productColor, 'M', 'ROLLBACK-M', 10, 20, 10);
        $large = $this->stock($shop, $productColor, 'L', 'ROLLBACK-L', 1, 20, 10);
        $accountId = DB::table('merchant_debits')->insertGetId([
            'creditor_id' => $merchant->id, 'debtor_id' => $shop->id,
            'currency_code' => 'AED', 'exchange_rate' => 3.67, 'amount' => 1000,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($admin)->postJson(route('merchantRefunds.store'), [
            'merchant_id' => $merchant->id,
            'merchant_debit_id' => $accountId,
            'user_products' => json_encode([
                ['id' => $medium->id, 'qty' => 5],
                ['id' => $large->id, 'qty' => 2],
            ]),
        ])->assertStatus(422);

        $this->assertSame(10, $medium->fresh()->stock);
        $this->assertSame(1, $large->fresh()->stock);
        $this->assertEquals(1000, DB::table('merchant_debits')->where('id', $accountId)->value('amount'));
        $this->assertDatabaseCount('merchant_refunds', 0);
        $this->assertDatabaseCount('wallet_movements', 0);
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
            'currency_code' => 'USD',
            'retail_price' => 100,
            'wholesale_price' => 50,
            'price_before_discount' => null,
            'merchant_id' => null,
            'items' => [['size' => 'M', 'barcode' => 'SY-M', 'quantity' => 2]],
        ]);

        $this->assertSame(3, $source->fresh()->stock);
        $this->assertSame(3, $destination->fresh()->stock);
        $this->assertEquals(100, $destination->fresh()->retail_price);
        $this->assertEquals(50, $destination->fresh()->wholesale_price);
    }

    /** @dataProvider merchantCountries */
    public function test_transfer_with_merchant_updates_receivable_without_touching_cashboxes(int $country, string $currency, float $rate): void
    {
        $admin = $this->user(User::ROLE_ADMIN, $country, 'merchant-transfer-admin@example.test');
        $shop = $this->user(User::ROLE_SHOP, $country, 'merchant-transfer-shop@example.test');
        $merchant = $this->user(User::ROLE_MERCHANT, $country, 'merchant-transfer-creditor@example.test');
        $productColor = $this->productColor($country, [
            ['size' => 'M', 'barcode' => 'MERCHANT-M', 'stock' => 0],
        ]);

        $this->actingAs($admin)->postJson(route('userProducts.store'), [
            'source_type' => InventoryTransferService::SOURCE_CATALOG,
            'destination_user_id' => $shop->id,
            'merchant_id' => $merchant->id,
            'product_color_id' => $productColor->id,
            'currency_code' => $currency,
            'retail_price' => 20 * $rate,
            'wholesale_price' => 10 * $rate,
            'items' => [
                ['size' => 'M', 'barcode' => 'MERCHANT-M', 'quantity' => 3],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('merchant_debits', [
            'creditor_id' => $merchant->id,
            'debtor_id' => $shop->id,
            'amount' => 30 * $rate,
            'currency_code' => $currency,
        ]);
        $this->assertDatabaseHas('debit_logs', ['amount' => 30 * $rate, 'currency_code' => $currency]);
        $this->assertDatabaseHas('debits', ['amount' => 30 * $rate, 'currency_code' => $currency]);
        $this->assertDatabaseHas('user_products', ['user_id' => $shop->id, 'wholesale_price' => 10, 'retail_price' => 20]);
        $this->assertSame(0, DB::table('wallet_movements')->count());
        $this->assertSame(0.0, (float) $shop->wallet->fresh()->credit - (float) $shop->wallet->fresh()->debit);
        $this->assertSame(0.0, (float) $merchant->wallet->fresh()->credit - (float) $merchant->wallet->fresh()->debit);
    }

    public static function merchantCountries(): array
    {
        return ['Syria base USD' => [4, 'USD', 1], 'UAE local AED' => [2, 'AED', 3.67]];
    }

    public function test_legacy_merchant_account_returns_validation_error_without_partial_transfer(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 2, 'legacy-transfer-admin@example.test');
        $shop = $this->user(User::ROLE_SHOP, 2, 'legacy-transfer-shop@example.test');
        $merchant = $this->user(User::ROLE_MERCHANT, 2, 'legacy-transfer-merchant@example.test');
        $productColor = $this->productColor(2, [
            ['size' => 'M', 'barcode' => 'LEGACY-M', 'stock' => 4],
        ]);
        $accountId = DB::table('merchant_debits')->insertGetId([
            'creditor_id' => $merchant->id,
            'debtor_id' => $shop->id,
            'amount' => 100,
            'currency_code' => 'USD',
            'exchange_rate' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)->postJson(route('userProducts.store'), [
            'source_type' => InventoryTransferService::SOURCE_CATALOG,
            'destination_user_id' => $shop->id,
            'merchant_id' => $merchant->id,
            'product_color_id' => $productColor->id,
            'currency_code' => 'AED',
            'retail_price' => 73.40,
            'wholesale_price' => 36.70,
            'items' => [
                ['size' => 'M', 'barcode' => 'LEGACY-M', 'quantity' => 2],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors('merchant_id');

        $this->assertDatabaseHas('merchant_debits', [
            'id' => $accountId,
            'amount' => 100,
            'currency_code' => 'USD',
        ]);
        $this->assertDatabaseCount('user_products', 0);
        $this->assertDatabaseCount('debits', 0);
        $this->assertDatabaseCount('debit_logs', 0);
    }

    public function test_transfer_destinations_follow_catalog_and_inventory_source_rules(): void
    {
        $warehouse = $this->user(User::ROLE_WAREHOUSE, 4, 'source-warehouse@example.test');
        $otherWarehouse = $this->user(User::ROLE_WAREHOUSE, 4, 'destination-warehouse@example.test');
        $shop = $this->user(User::ROLE_SHOP, 4, 'local-shop@example.test');
        $this->user(User::ROLE_MERCHANT, 4, 'merchant@example.test');
        $this->user(User::ROLE_SHOP, 2, 'foreign-shop@example.test');

        $service = app(InventoryTransferService::class);
        $inventoryDestinations = $service->destinationsFor($warehouse);
        $catalogDestinations = $service->destinationsFor(
            $warehouse,
            InventoryTransferService::SOURCE_CATALOG
        );

        $this->assertEqualsCanonicalizing(
            [$otherWarehouse->id, $shop->id],
            $inventoryDestinations->pluck('id')->all()
        );
        $this->assertFalse($inventoryDestinations->contains('id', $warehouse->id));
        $this->assertEqualsCanonicalizing(
            [$warehouse->id, $otherWarehouse->id, $shop->id],
            $catalogDestinations->pluck('id')->all()
        );
    }

    public function test_warehouse_catalog_transfer_accepts_any_quantity_without_source_stock(): void
    {
        $warehouse = $this->user(User::ROLE_WAREHOUSE, 4, 'catalog-warehouse@example.test');
        $shop = $this->user(User::ROLE_SHOP, 4, 'catalog-shop@example.test');
        $productColor = $this->productColor(4, [
            ['size' => 'M', 'barcode' => 'CATALOG-M', 'stock' => 0],
        ]);

        $this->actingAs($warehouse)->postJson(route('userProducts.store'), [
            'source_type' => InventoryTransferService::SOURCE_CATALOG,
            'destination_user_id' => $shop->id,
            'product_color_id' => $productColor->id,
            'currency_code' => 'USD',
            'retail_price' => 20,
            'wholesale_price' => 10,
            'items' => [
                ['size' => 'M', 'barcode' => 'CATALOG-M', 'quantity' => 1000],
            ],
        ])->assertOk()->assertJson([
            'success' => true,
            'source_type' => InventoryTransferService::SOURCE_CATALOG,
        ]);

        $this->assertSame(0, $productColor->fresh()->stock);
        $this->assertDatabaseHas('user_products', [
            'user_id' => $shop->id,
            'barcode' => 'CATALOG-M',
            'stock' => 1000,
        ]);
    }

    public function test_warehouse_catalog_transfer_can_add_stock_to_itself(): void
    {
        $warehouse = $this->user(User::ROLE_WAREHOUSE, 4, 'catalog-self@example.test');
        $productColor = $this->productColor(4, [
            ['size' => 'M', 'barcode' => 'CATALOG-SELF-M', 'stock' => 0],
        ]);
        $stock = $this->stock($warehouse, $productColor, 'M', 'CATALOG-SELF-M', 4, 20, 10);

        $this->actingAs($warehouse)->postJson(route('userProducts.store'), [
            'source_type' => InventoryTransferService::SOURCE_CATALOG,
            'destination_user_id' => $warehouse->id,
            'product_color_id' => $productColor->id,
            'currency_code' => 'USD',
            'retail_price' => 20,
            'wholesale_price' => 10,
            'items' => [
                ['size' => 'M', 'barcode' => 'CATALOG-SELF-M', 'quantity' => 100],
            ],
        ])->assertOk();

        $this->assertSame(104, $stock->fresh()->stock);
    }

    public function test_admin_catalog_transfer_does_not_decrease_central_stock(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 4, 'catalog-admin@example.test');
        $shop = $this->user(User::ROLE_SHOP, 4, 'catalog-admin-shop@example.test');
        $productColor = $this->productColor(4, [
            ['size' => 'M', 'barcode' => 'ADMIN-CATALOG-M', 'stock' => 2],
        ]);

        $this->actingAs($admin)->postJson(route('userProducts.store'), [
            'source_type' => InventoryTransferService::SOURCE_CATALOG,
            'destination_user_id' => $shop->id,
            'product_color_id' => $productColor->id,
            'currency_code' => 'USD',
            'retail_price' => 20,
            'wholesale_price' => 10,
            'items' => [
                ['size' => 'M', 'barcode' => 'ADMIN-CATALOG-M', 'quantity' => 50],
            ],
        ])->assertOk();

        $this->assertSame(2, $productColor->fresh()->stock);
    }

    public function test_catalog_transfer_rejects_a_size_or_barcode_outside_the_model(): void
    {
        $warehouse = $this->user(User::ROLE_WAREHOUSE, 4, 'invalid-catalog@example.test');
        $shop = $this->user(User::ROLE_SHOP, 4, 'invalid-catalog-shop@example.test');
        $productColor = $this->productColor(4, [
            ['size' => 'M', 'barcode' => 'VALID-CATALOG-M', 'stock' => 0],
        ]);

        $this->actingAs($warehouse)->postJson(route('userProducts.store'), [
            'source_type' => InventoryTransferService::SOURCE_CATALOG,
            'destination_user_id' => $shop->id,
            'product_color_id' => $productColor->id,
            'currency_code' => 'USD',
            'retail_price' => 20,
            'wholesale_price' => 10,
            'items' => [
                ['size' => 'XL', 'barcode' => 'FAKE-CATALOG-XL', 'quantity' => 50],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors('items');

        $this->assertDatabaseMissing('user_products', [
            'user_id' => $shop->id,
            'product_color_id' => $productColor->id,
        ]);
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
            'currency_code' => 'USD',
            'retail_price' => 20,
            'wholesale_price' => 10,
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
            'currency_code' => 'USD',
            'retail_price' => 20,
            'wholesale_price' => 10,
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

    public function test_syria_transfer_rejects_syp_as_an_input_currency(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 4, 'syria-currency-admin@example.test');
        $shop = $this->user(User::ROLE_SHOP, 4, 'syria-currency-shop@example.test');
        $productColor = $this->productColor(4, [
            ['size' => 'M', 'barcode' => 'SY-USD-M', 'stock' => 5],
        ]);

        $this->actingAs($admin)->postJson(route('userProducts.store'), [
            'source_type' => InventoryTransferService::SOURCE_CATALOG,
            'destination_user_id' => $shop->id,
            'product_color_id' => $productColor->id,
            'currency_code' => 'SYP',
            'retail_price' => 260000,
            'wholesale_price' => 130000,
            'items' => [
                ['size' => 'M', 'barcode' => 'SY-USD-M', 'quantity' => 1],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors('currency_code');

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
            'sizes' => [],
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

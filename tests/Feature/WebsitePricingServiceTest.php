<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductColor;
use App\Models\UserProduct;
use App\Services\CurrencyService;
use App\Services\WebsitePricingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WebsitePricingServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => false,
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('role_id');
            $table->unsignedInteger('country_id');
            $table->timestamps();
        });

        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('label');
            $table->decimal('rate', 20, 6);
            $table->timestamps();
        });
        Schema::create('country_commerce_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('country_id')->unique();
            $table->decimal('shipping_fee_usd', 20, 4)->default(0);
            $table->decimal('free_shipping_threshold_usd', 20, 4)->nullable();
            $table->decimal('cod_fee_percent', 8, 4)->default(0);
            $table->boolean('card_enabled')->default(false);
            $table->string('gateway_currency', 3)->default('USD');
            $table->string('gateway_mode')->default('sandbox');
            $table->unsignedBigInteger('website_cashbox_user_id')->nullable();
            $table->unsignedBigInteger('website_stock_user_id')->nullable();
            $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('barcode');
            $table->unsignedInteger('country_id');
            $table->decimal('cost_price', 20, 4)->default(0);
            $table->decimal('retail_price', 20, 4);
            $table->decimal('sale_price', 20, 4);
            $table->decimal('price_before_discount', 20, 4)->nullable();
            $table->timestamps();
        });
        Schema::create('product_colors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedInteger('country_id');
            $table->timestamps();
        });
        Schema::create('user_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_color_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedInteger('country_id');
            $table->string('size');
            $table->integer('stock');
            $table->timestamps();
        });

        DB::table('currencies')->insert([
            'name' => 'syp', 'label' => 'SYP', 'rate' => 13000,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('currencies')->insert([
            'name' => 'aed', 'label' => 'AED', 'rate' => 3.67,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('country_commerce_settings')->insert([
            'country_id' => 4,
            'shipping_fee_usd' => 1,
            'free_shipping_threshold_usd' => null,
            'cod_fee_percent' => 5,
            'website_stock_user_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('users')->insert([
            'id' => 1, 'role_id' => 3, 'country_id' => 4,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        app(CurrencyService::class)->clearRateCache();
    }

    public function test_syrian_retail_quote_is_syp_and_uses_server_prices_only_once(): void
    {
        $color = $this->product();

        $quote = app(WebsitePricingService::class)->quote([[
            'product_id' => $color->id,
            'size' => 'M',
            'qty' => 2,
            'price' => 1,
        ]], 4, false, 'cod');

        $this->assertSame('SYP', $quote['currency']);
        $this->assertSame(13000.0, $quote['rate']);
        $this->assertSame(158600.0, $quote['items'][0]['item_price']);
        $this->assertSame(317200.0, $quote['subtotal']);
        $this->assertSame(13000.0, $quote['shipping_fee']);
        $this->assertSame(15860.0, $quote['cod_fee']);
        $this->assertSame(346060.0, $quote['total']);
        $this->assertSame('USD', $quote['display']['currency']);
        $this->assertSame(26.62, $quote['display']['amount']);
        $this->assertSame(13000.0, $quote['display']['rate']);
    }

    public function test_syrian_wholesale_quote_stays_usd_and_displays_syp_estimate(): void
    {
        $color = $this->product();

        $quote = app(WebsitePricingService::class)->quote([[
            'product_id' => $color->id,
            'size' => 'M',
            'qty' => 2,
        ]], 4, true, 'cod');

        $this->assertSame('USD', $quote['currency']);
        $this->assertSame(8.0, $quote['items'][0]['item_price']);
        $this->assertSame(17.8, $quote['total']);
        $this->assertSame('SYP', $quote['display']['currency']);
        $this->assertSame(231400.0, $quote['display']['amount']);
    }

    public function test_quote_uses_only_the_configured_location_and_can_allocate_duplicate_stock_rows(): void
    {
        $color = $this->product();
        UserProduct::query()->where('product_color_id', $color->id)->update(['stock' => 1]);
        $secondSelected = UserProduct::query()->create([
            'product_color_id' => $color->id, 'user_id' => 1, 'country_id' => 4,
            'size' => 'M', 'stock' => 2,
        ]);
        DB::table('users')->insert([
            'id' => 2, 'role_id' => 3, 'country_id' => 4,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        UserProduct::query()->create([
            'product_color_id' => $color->id, 'user_id' => 2, 'country_id' => 4,
            'size' => 'M', 'stock' => 100,
        ]);

        $quote = app(WebsitePricingService::class)->quote([[
            'product_id' => $color->id, 'size' => 'M', 'qty' => 3,
        ]], 4, false, 'cod');

        $this->assertSame(3, array_sum(array_column($quote['items'], 'qty')));
        $this->assertCount(2, $quote['items']);
        $this->assertContains($secondSelected->id, array_column($quote['items'], 'stock_user_product_id'));
        $this->assertNotContains(
            UserProduct::query()->where('user_id', 2)->value('id'),
            array_column($quote['items'], 'stock_user_product_id')
        );
    }

    public function test_uae_card_quote_requires_readiness_and_keeps_the_aed_settlement_rate(): void
    {
        DB::table('users')->insert([
            'id' => 2, 'role_id' => 3, 'country_id' => 2,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('country_commerce_settings')->insert([
            'country_id' => 2,
            'shipping_fee_usd' => 0,
            'free_shipping_threshold_usd' => null,
            'cod_fee_percent' => 0,
            'card_enabled' => true,
            'gateway_currency' => 'AED',
            'gateway_mode' => 'sandbox',
            'website_cashbox_user_id' => 2,
            'website_stock_user_id' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        config([
            'services.tap.secret_key' => 'sk_test_example',
            'services.tap.callback_url' => 'https://test.oneway.fashion/payment/callback',
            'services.tap.webhook_url' => 'https://test.oneway.fashion/payment/webhook',
        ]);
        $product = Product::query()->create([
            'name' => 'UAE Dress', 'barcode' => 'AE-D-1', 'country_id' => 2,
            'user_id' => 2, 'cost_price' => 5, 'retail_price' => 12.20,
            'sale_price' => 8, 'price_before_discount' => 12.20,
        ]);
        $color = ProductColor::query()->create(['product_id' => $product->id, 'country_id' => 2]);
        UserProduct::query()->create([
            'product_color_id' => $color->id, 'user_id' => 2, 'country_id' => 2,
            'size' => 'M', 'stock' => 2,
        ]);

        $quote = app(WebsitePricingService::class)->quote([[
            'product_id' => $color->id, 'size' => 'M', 'qty' => 1,
        ]], 2, false, 'card');

        $this->assertSame('AED', $quote['currency']);
        $this->assertSame('AED', $quote['gateway']['currency']);
        $this->assertSame(3.67, $quote['gateway']['rate']);
        $this->assertSame($quote['total'], $quote['gateway']['amount']);
    }

    private function product(): ProductColor
    {
        $product = Product::query()->create([
            'name' => 'Dress',
            'barcode' => 'D-1',
            'country_id' => 4,
            'user_id' => 1,
            'cost_price' => 5,
            'retail_price' => 12.20,
            'sale_price' => 8,
            'price_before_discount' => 12.20,
        ]);
        $color = ProductColor::query()->create([
            'product_id' => $product->id,
            'country_id' => 4,
        ]);
        UserProduct::query()->create([
            'product_color_id' => $color->id,
            'user_id' => 1,
            'country_id' => 4,
            'size' => 'M',
            'stock' => 10,
        ]);

        return $color;
    }
}

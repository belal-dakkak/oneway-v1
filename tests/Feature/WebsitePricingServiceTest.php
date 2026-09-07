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
        DB::table('country_commerce_settings')->insert([
            'country_id' => 4,
            'shipping_fee_usd' => 1,
            'free_shipping_threshold_usd' => null,
            'cod_fee_percent' => 5,
            'created_at' => now(),
            'updated_at' => now(),
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

    private function product(): ProductColor
    {
        $product = Product::query()->create([
            'name' => 'Dress',
            'barcode' => 'D-1',
            'country_id' => 4,
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
            'country_id' => 4,
            'size' => 'M',
            'stock' => 10,
        ]);

        return $color;
    }
}

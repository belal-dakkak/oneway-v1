<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\CurrencyService;
use App\Services\OperationalPriceService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class OperationalProductPriceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'session.driver' => 'array',
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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('country_id');
            $table->decimal('cost_price', 20, 4)->nullable();
            $table->decimal('retail_price', 20, 4)->nullable();
            $table->decimal('sale_price', 20, 4)->nullable();
            $table->decimal('price_before_discount', 20, 4)->nullable();
            $table->timestamps();
        });
        Schema::create('operational_prices', function (Blueprint $table) {
            $table->id();
            $table->string('priceable_type');
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
        DB::table('currencies')->insert([
            'name' => 'aed', 'label' => 'AED', 'rate' => 3.675,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        Session::put('country', 'AE');
    }

    public function test_aed_prices_stay_exact_after_save_and_exchange_rate_change(): void
    {
        $prices = app(OperationalPriceService::class);

        foreach ([25, 45, 89, 25.50] as $amount) {
            $prepared = $prices->prepare([
                'cost_price' => $amount,
                'retail_price' => $amount,
                'sale_price' => $amount,
                'price_before_discount' => $amount,
            ], 'AED', OperationalPriceService::PRODUCT_FIELDS);
            $product = Product::query()->create(array_merge(['country_id' => 2], $prepared['base']));
            $prices->persist($product, $prepared);
            $this->assertSame((float) $amount, $product->fresh()->final_price_value);
        }

        DB::table('currencies')->where('name', 'aed')->update(['rate' => 4.25]);
        app(CurrencyService::class)->clearRateCache();

        $this->assertSame(89.0, Product::query()->find(3)->final_price_value);
        $this->assertSame(25.5, Product::query()->find(4)->final_price_value);
        $this->assertSame('89 AED', Product::query()->find(3)->final_price);
        $this->assertSame('25.50 AED', Product::query()->find(4)->final_price);
    }

    public function test_usd_prices_are_not_exchanged(): void
    {
        Session::put('country', 'SY');
        $prices = app(OperationalPriceService::class);
        $prepared = $prices->prepare([
            'cost_price' => 12.20,
            'retail_price' => 12.20,
            'sale_price' => 9.75,
            'price_before_discount' => 15.40,
        ], 'USD', OperationalPriceService::PRODUCT_FIELDS);
        $product = Product::query()->create(array_merge(['country_id' => 4], $prepared['base']));
        $prices->persist($product, $prepared);

        $this->assertSame(12.2, $product->fresh()->final_price_value);
        $this->assertSame(9.75, $product->fresh()->wholesale_price_value);
    }
}

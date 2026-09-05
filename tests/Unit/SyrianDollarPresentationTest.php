<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\UserProduct;
use App\Support\Country;
use Tests\TestCase;

class SyrianDollarPresentationTest extends TestCase
{
    public function test_syrian_product_keeps_its_raw_usd_price_without_exchange(): void
    {
        $product = new Product([
            'country_id' => Country::SYRIA,
            'retail_price' => 12.20,
            'sale_price' => 9.75,
            'price_before_discount' => 15.40,
        ]);

        $this->assertSame(12.2, $product->final_price_value);
        $this->assertSame('12.20 USD', $product->final_price);
        $this->assertSame(9.75, $product->wholesale_price_value);
        $this->assertSame('15.40 USD', $product->formatted_price_before_discount);
    }

    public function test_syrian_inventory_keeps_its_raw_usd_price_without_exchange(): void
    {
        $product = new UserProduct([
            'country_id' => Country::SYRIA,
            'retail_price' => 12.20,
            'price_before_discount' => 15.40,
        ]);

        $this->assertSame(12.2, $product->final_price_value);
        $this->assertSame('12.20 USD', $product->final_price);
        $this->assertSame('15.40 USD', $product->formatted_price_before_discount);
    }
}

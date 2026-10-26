<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StorefrontOfferEligibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_products_with_a_positive_configured_price_are_current_offers(): void
    {
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Offers', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $missing = $this->product($categoryId, 'NO-OFFER', null);
        $zero = $this->product($categoryId, 'ZERO-OFFER', 0);
        $positive = $this->product($categoryId, 'ACTIVE-OFFER', 69);

        $this->assertSame(
            [$positive->id],
            Product::query()->currentOffer()->pluck('id')->all()
        );
        $this->assertSame(0.0, (float) $missing->fresh()->price_before_discount);
        $this->assertSame(0.0, (float) $zero->fresh()->price_before_discount);
        $this->assertSame(69.0, (float) $positive->fresh()->price_before_discount);
    }

    private function product(int $categoryId, string $barcode, $priceBeforeDiscount): Product
    {
        return Product::query()->create([
            'name' => $barcode,
            'name_en' => $barcode,
            'details' => 'Details',
            'details_en' => 'Details',
            'barcode' => $barcode,
            'category_id' => $categoryId,
            'country_id' => 2,
            'sizes' => [],
            'cost_price' => 10,
            'retail_price' => 50,
            'sale_price' => 40,
            'price_before_discount' => $priceBeforeDiscount,
        ]);
    }
}

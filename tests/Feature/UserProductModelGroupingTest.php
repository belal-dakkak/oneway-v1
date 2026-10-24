<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Color;
use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\User;
use App\Models\UserProduct;
use App\Services\UserProductModelGroupingService;
use App\Services\CurrencyService;
use App\Support\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Tests\TestCase;

class UserProductModelGroupingTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_lists_paginate_complete_barcode_model_groups(): void
    {
        $admin = User::factory()->create([
            'role_id' => User::ROLE_ADMIN,
            'country_id' => Country::UAE,
        ]);
        $this->actingAs($admin);
        Currency::query()->updateOrCreate(
            ['name' => 'aed'],
            ['label' => 'AED', 'rate' => 3.675]
        );
        app(CurrencyService::class)->clearRateCache();

        $category = Category::query()->create(['name' => 'Grouped models']);
        $color = Color::query()->create([
            'name' => 'Black',
            'name_en' => 'Black',
            'code' => '#000000',
        ]);

        $oldActivity = now()->subDay();
        foreach (['18225B26', '18225K70', '18225K20', '18225C35'] as $barcode) {
            $this->inventoryRow($admin, $category, $color, $barcode, $oldActivity);
        }

        // Ten newer model groups fill page one. The four 18225 colors must all
        // move to page two instead of being split at the ten-row boundary.
        foreach (range(1, 10) as $index) {
            $prefix = str_pad((string) (30000 + $index), 5, '0', STR_PAD_LEFT);
            $this->inventoryRow($admin, $category, $color, $prefix . 'A01', now()->addMinutes($index));
        }

        foreach ([route('userProducts.index'), route('userProducts.all')] as $url) {
            $pageOne = $this->getJson($url)->assertOk()->json();
            $this->assertSame(11, $pageOne['total']);
            $this->assertCount(10, $pageOne['data']);
            $this->assertEmpty($this->barcodesWithPrefix($pageOne['data'], '18225'));

            $pageTwo = $this->getJson($url . '?page=2')->assertOk()->json();
            $this->assertSame(
                ['18225B26', '18225C35', '18225K20', '18225K70'],
                $this->barcodesWithPrefix($pageTwo['data'], '18225')
            );

            $filtered = $this->getJson($url . '?search=18225')->assertOk()->json();
            $this->assertSame(1, $filtered['total']);
            $this->assertSame(
                ['18225B26', '18225C35', '18225K20', '18225K70'],
                $this->barcodesWithPrefix($filtered['data'], '18225')
            );
            $this->assertStringContainsString('search=18225', $filtered['first_page_url']);
        }
    }

    public function test_short_or_missing_barcodes_use_independent_color_groups(): void
    {
        $items = collect([
            ['barcode' => 'A1', 'color_id' => 10, 'updated_at' => 10],
            ['barcode' => ' A1 ', 'color_id' => 11, 'updated_at' => 20],
            ['barcode' => null, 'color_id' => 12, 'updated_at' => 30],
        ]);

        $paginator = app(UserProductModelGroupingService::class)->paginate(
            $items,
            Request::create('/admin/userProducts', 'GET'),
            fn (array $item) => $item['barcode'],
            fn (array $item) => $item['color_id'],
            fn (array $item) => $item['updated_at'],
            fn (array $item) => $item['color_id']
        );

        $this->assertSame(3, $paginator->total());
        $this->assertCount(3, $paginator->items());
    }

    private function inventoryRow(
        User $owner,
        Category $category,
        Color $color,
        string $barcode,
        $updatedAt
    ): UserProduct {
        $product = Product::query()->create([
            'name' => $barcode,
            'name_en' => $barcode,
            'details' => 'Details',
            'details_en' => 'Details',
            'barcode' => 'PRODUCT-' . $barcode,
            'category_id' => $category->id,
            'country_id' => Country::UAE,
            'cost_price' => 10,
            'retail_price' => 20,
        ]);
        $productColor = ProductColor::query()->create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'image' => $barcode . '.jpg',
            'barcode' => $barcode,
            'sizes' => json_encode([['size' => 'M', 'stock' => 1, 'barcode' => $barcode . 'M']]),
            'stock' => 1,
            'country_id' => Country::UAE,
        ]);
        $inventory = UserProduct::query()->create([
            'product_color_id' => $productColor->id,
            'user_id' => $owner->id,
            'stock' => 1,
            'wholesale_price' => 10,
            'retail_price' => 20,
            'price_before_discount' => 25,
            'approved' => true,
            'size' => 'M',
            'barcode' => $barcode . 'M',
            'country_id' => Country::UAE,
        ]);

        $inventory->forceFill([
            'created_at' => $updatedAt,
            'updated_at' => $updatedAt,
        ])->saveQuietly();

        return $inventory;
    }

    private function barcodesWithPrefix(array $rows, string $prefix): array
    {
        return Collection::make($rows)
            ->map(function (array $row) {
                return $row['product']['barcode'] ?? $row['product_color']['barcode'] ?? null;
            })
            ->filter(function ($barcode) use ($prefix) {
                return is_string($barcode) && str_starts_with($barcode, $prefix);
            })
            ->values()
            ->all();
    }
}

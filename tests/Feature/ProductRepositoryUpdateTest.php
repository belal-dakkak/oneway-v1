<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductColor;
use App\Models\User;
use App\Repositories\ProductRepository;
use App\Services\CurrencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductRepositoryUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('currencies')->updateOrInsert(
            ['name' => 'aed'],
            ['label' => 'AED', 'rate' => 3.675, 'created_at' => now(), 'updated_at' => now()]
        );
        app(CurrencyService::class)->clearRateCache();
    }

    protected function tearDown(): void
    {
        app(CurrencyService::class)->clearRateCache();

        parent::tearDown();
    }

    public function test_updating_a_product_assigns_the_resolved_country_to_existing_and_new_colors(): void
    {
        $admin = User::factory()->create([
            'role_id' => User::ROLE_ADMIN,
            'country_id' => 2,
        ]);
        $this->actingAs($admin);

        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Category',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $firstColorId = DB::table('colors')->insertGetId([
            'name' => 'Black',
            'name_en' => 'Black',
            'code' => '#000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $secondColorId = DB::table('colors')->insertGetId([
            'name' => 'White',
            'name_en' => 'White',
            'code' => '#ffffff',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $product = Product::query()->create([
            'name' => 'Old name',
            'name_en' => 'Old name',
            'details' => 'Old details',
            'details_en' => 'Old details',
            'barcode' => 'MODEL-1',
            'category_id' => $categoryId,
            'country_id' => 2,
            'sizes' => [],
            'cost_price' => 10,
            'retail_price' => 20,
            'sale_price' => 15,
        ]);
        $existingColor = ProductColor::query()->create([
            'product_id' => $product->id,
            'image' => 'old.jpg',
            'color_id' => $firstColorId,
            'stock' => 1,
            'barcode' => 'MODEL-1-B',
            'sizes' => json_encode([['stock' => 1, 'size' => 'M', 'barcode' => 'MODEL-1-B62']]),
            'country_id' => 2,
        ]);

        $request = Request::create('/admin/products/'.$product->id, 'PUT', [
            'name' => 'Updated name',
            'name_en' => 'Updated name',
            'details' => 'Updated details',
            'details_en' => 'Updated details',
            'barcode' => 'MODEL-1',
            'country' => ['value' => 2],
            'price_currency_code' => 'AED',
            'cost_price' => '36.75',
            'retail_price' => '73.50',
            'sale_price' => '55.13',
            'price_before_discount' => '80.00',
            'selected_sizes' => [],
            'selected_category' => ['id' => $categoryId],
            'selected_products' => [
                [
                    'id' => $existingColor->id,
                    'image' => ['updated.jpg'],
                    'color' => ['id' => $firstColorId],
                    'barcode' => 'MODEL-1-B',
                    'sizes' => [['stock' => 2, 'size' => ['id' => 'M']]],
                ],
                [
                    'image' => ['new.jpg'],
                    'color' => ['id' => $secondColorId],
                    'barcode' => 'MODEL-1-W',
                    'sizes' => [['stock' => 3, 'size' => ['id' => 'L']]],
                ],
            ],
        ]);

        app(ProductRepository::class)->update($request, $product);

        $this->assertSame(2, (int) $product->fresh()->country_id);
        $this->assertDatabaseHas('product_colors', [
            'id' => $existingColor->id,
            'country_id' => 2,
            'stock' => 2,
        ]);
        $this->assertDatabaseHas('product_colors', [
            'product_id' => $product->id,
            'color_id' => $secondColorId,
            'country_id' => 2,
            'stock' => 3,
        ]);
    }
}

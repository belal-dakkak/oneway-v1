<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductColor;
use App\Models\User;
use App\Repositories\ProductColorRepository;
use App\Repositories\ProductRepository;
use App\Services\CurrencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
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
                    'is_cover' => true,
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
        $cover = ProductColor::query()
            ->where('product_id', $product->id)
            ->where('color_id', $secondColorId)
            ->firstOrFail();
        $this->assertSame($cover->id, (int) $product->fresh()->cover_product_color_id);
        $this->assertDatabaseHas('operational_prices', [
            'priceable_type' => $product->getMorphClass(),
            'priceable_id' => $product->id,
            'currency_code' => 'AED',
            'retail_price' => 73.50,
        ]);
        $this->assertSame(73.5, $product->fresh()->final_price_value);
    }

    public function test_aed_price_remains_exact_when_product_is_updated_more_than_once(): void
    {
        $admin = User::factory()->create([
            'role_id' => User::ROLE_ADMIN,
            'country_id' => 2,
        ]);
        $this->actingAs($admin);

        [$product, $categoryId] = $this->makeProduct();

        app(ProductRepository::class)->update(
            $this->makeUpdateRequest($categoryId, '90'),
            $product
        );

        $this->assertSame(90.0, $product->fresh()->final_price_value);
        $this->assertEqualsWithDelta(24.4898, (float) $product->fresh()->getRawOriginal('retail_price'), 0.00005);
        $this->assertDatabaseHas('operational_prices', [
            'priceable_type' => $product->getMorphClass(),
            'priceable_id' => $product->id,
            'currency_code' => 'AED',
            'retail_price' => 90,
        ]);

        app(ProductRepository::class)->update(
            $this->makeUpdateRequest($categoryId, '89.50'),
            $product->fresh()
        );

        $this->assertSame(89.5, $product->fresh()->final_price_value);
        $this->assertDatabaseHas('operational_prices', [
            'priceable_type' => $product->getMorphClass(),
            'priceable_id' => $product->id,
            'currency_code' => 'AED',
            'retail_price' => 89.50,
        ]);
    }

    public function test_creating_uae_and_shared_products_persists_the_exact_aed_price(): void
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
        $colorId = DB::table('colors')->insertGetId([
            'name' => 'Black',
            'name_en' => 'Black',
            'code' => '#000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([[2, 'UAE-90'], [3, 'GLOBAL-90']] as [$countryId, $barcode]) {
            $request = Request::create('/admin/productColors', 'POST', [
                'name' => $barcode,
                'name_en' => $barcode,
                'details' => 'Details',
                'details_en' => 'Details',
                'barcode' => $barcode,
                'country' => ['value' => $countryId],
                'price_currency_code' => 'AED',
                'cost_price' => '45',
                'retail_price' => '90',
                'sale_price' => '89.50',
                'price_before_discount' => '100',
                'selected_sizes' => [],
                'selected_category' => ['id' => $categoryId],
                'selected_products' => [[
                    'image' => ['product.jpg'],
                    'color' => ['id' => $colorId],
                    'barcode' => $barcode.'-B',
                    'is_cover' => true,
                    'sizes' => [['stock' => 1, 'size' => ['id' => 'M']]],
                ]],
            ]);

            $product = app(ProductColorRepository::class)->add($request);

            $this->assertInstanceOf(Product::class, $product);
            $this->assertSame($countryId, (int) $product->country_id);
            $this->assertNotNull($product->fresh()->cover_product_color_id);
            $this->assertDatabaseHas('operational_prices', [
                'priceable_type' => $product->getMorphClass(),
                'priceable_id' => $product->id,
                'currency_code' => 'AED',
                'retail_price' => 90,
                'sale_price' => 89.50,
            ]);
        }
    }

    public function test_update_fails_before_mutating_product_or_colors_when_price_storage_is_missing(): void
    {
        $admin = User::factory()->create([
            'role_id' => User::ROLE_ADMIN,
            'country_id' => 2,
        ]);
        $this->actingAs($admin);

        [$product, $categoryId] = $this->makeProduct();
        $colorId = DB::table('colors')->insertGetId([
            'name' => 'Black',
            'name_en' => 'Black',
            'code' => '#000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $productColor = ProductColor::query()->create([
            'product_id' => $product->id,
            'image' => 'old.jpg',
            'color_id' => $colorId,
            'stock' => 1,
            'barcode' => 'MODEL-ATOMIC-B',
            'sizes' => json_encode([['stock' => 1, 'size' => 'M', 'barcode' => 'MODEL-ATOMIC-B62']]),
            'country_id' => 2,
        ]);
        $request = $this->makeUpdateRequest($categoryId, '90', [[
            'id' => $productColor->id,
            'image' => ['updated.jpg'],
            'color' => ['id' => $colorId],
            'barcode' => 'MODEL-ATOMIC-B',
            'sizes' => [['stock' => 9, 'size' => ['id' => 'M']]],
        ]]);

        Schema::drop('operational_prices');

        try {
            app(ProductRepository::class)->update($request, $product);
            $this->fail('The update should fail when operational price storage is unavailable.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('operational_prices', $exception->getMessage());
        }

        $this->assertSame('Old name', $product->fresh()->name);
        $this->assertSame(20.0, (float) $product->fresh()->getRawOriginal('retail_price'));
        $this->assertSame(1, (int) $productColor->fresh()->stock);
        $this->assertSame('old.jpg', $productColor->fresh()->image);
    }

    public function test_backfill_freezes_the_displayed_aed_price_without_rounding_it_up(): void
    {
        [$product] = $this->makeProduct([
            'cost_price' => 24.22,
            'retail_price' => 24.22,
            'sale_price' => 24.22,
            'price_before_discount' => 24.22,
        ]);

        $this->artisan('catalog:backfill-operational-prices', ['--chunk' => 50])
            ->assertExitCode(0);

        $this->assertDatabaseHas('operational_prices', [
            'priceable_type' => $product->getMorphClass(),
            'priceable_id' => $product->id,
            'currency_code' => 'AED',
            'retail_price' => 89.01,
        ]);
    }

    public function test_update_accepts_ui_string_images_and_switches_the_existing_cover(): void
    {
        $admin = User::factory()->create([
            'role_id' => User::ROLE_ADMIN,
            'country_id' => 2,
        ]);
        $this->actingAs($admin);

        [$product, $categoryId] = $this->makeProduct();
        $blackId = DB::table('colors')->insertGetId([
            'name' => 'Black', 'name_en' => 'Black', 'code' => '#000000',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $whiteId = DB::table('colors')->insertGetId([
            'name' => 'White', 'name_en' => 'White', 'code' => '#ffffff',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $black = ProductColor::query()->create([
            'product_id' => $product->id, 'image' => 'black-old.jpg', 'color_id' => $blackId,
            'stock' => 1, 'barcode' => 'MODEL-ATOMIC-B', 'sizes' => json_encode([['stock' => 1, 'size' => 'M']]),
            'country_id' => 2,
        ]);
        $white = ProductColor::query()->create([
            'product_id' => $product->id, 'image' => 'white-old.jpg', 'color_id' => $whiteId,
            'stock' => 1, 'barcode' => 'MODEL-ATOMIC-W', 'sizes' => json_encode([['stock' => 1, 'size' => 'M']]),
            'country_id' => 2,
        ]);
        $product->update(['cover_product_color_id' => $black->id]);

        $request = $this->makeUpdateRequest($categoryId, '90', [
            [
                'id' => $black->id, 'image' => 'products/colors/black-new.jpg',
                'color' => ['id' => $blackId], 'barcode' => 'MODEL-ATOMIC-B',
                'sizes' => [['stock' => 1, 'size' => ['id' => 'M']]], 'is_cover' => false,
            ],
            [
                'id' => $white->id, 'image' => 'products/colors/white-new.jpg',
                'color' => ['id' => $whiteId], 'barcode' => 'MODEL-ATOMIC-W',
                'sizes' => [['stock' => 1, 'size' => ['id' => 'M']]], 'is_cover' => true,
            ],
        ]);

        app(ProductRepository::class)->update($request, $product);

        $this->assertSame($white->id, (int) $product->fresh()->cover_product_color_id);
        $this->assertSame('products/colors/black-new.jpg', $black->fresh()->image);
        $this->assertSame('products/colors/white-new.jpg', $white->fresh()->image);
    }

    private function makeProduct(array $prices = []): array
    {
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Category',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $product = Product::query()->create(array_merge([
            'name' => 'Old name',
            'name_en' => 'Old name',
            'details' => 'Old details',
            'details_en' => 'Old details',
            'barcode' => 'MODEL-ATOMIC',
            'category_id' => $categoryId,
            'country_id' => 2,
            'sizes' => [],
            'cost_price' => 10,
            'retail_price' => 20,
            'sale_price' => 15,
            'price_before_discount' => 25,
        ], $prices));

        return [$product, $categoryId];
    }

    private function makeUpdateRequest(int $categoryId, string $retailPrice, array $selectedProducts = []): Request
    {
        return Request::create('/admin/products/1', 'PUT', [
            'name' => 'Updated name',
            'name_en' => 'Updated name',
            'details' => 'Updated details',
            'details_en' => 'Updated details',
            'barcode' => 'MODEL-ATOMIC',
            'country' => ['value' => 2],
            'price_currency_code' => 'AED',
            'cost_price' => '45',
            'retail_price' => $retailPrice,
            'sale_price' => '40',
            'price_before_discount' => '100',
            'selected_sizes' => [],
            'selected_category' => ['id' => $categoryId],
            'selected_products' => $selectedProducts,
        ]);
    }
}

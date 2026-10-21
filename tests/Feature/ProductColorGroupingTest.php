<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\User;
use App\Repositories\ProductColorRepository;
use App\Repositories\ProductRepository;
use App\Support\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ProductColorGroupingTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_pages_keep_model_colors_together_and_sort_by_latest_color(): void
    {
        $admin = User::factory()->create([
            'role_id' => User::ROLE_ADMIN,
            'country_id' => Country::UAE,
        ]);
        $this->actingAs($admin);
        $category = Category::query()->create(['name' => 'Catalog']);
        $black = Color::query()->create(['name' => 'Black', 'name_en' => 'Black', 'code' => '#000000']);
        $white = Color::query()->create(['name' => 'White', 'name_en' => 'White', 'code' => '#ffffff']);

        $oldModel = $this->product($category, 'OLD-MODEL');
        $this->productColor($oldModel, $black, 'OLD-BLACK');

        foreach (range(1, 10) as $index) {
            $model = $this->product($category, 'NEW-'.$index);
            $this->productColor($model, $black, 'NEW-'.$index.'-BLACK');
        }

        // Adding a color to an old model must move the entire model group to the top.
        $this->productColor($oldModel, $white, 'OLD-WHITE');

        $paginator = app(ProductColorRepository::class)->getProducts(new Request());
        $rows = $paginator->items();

        $this->assertSame(11, $paginator->total(), 'Pagination counts models, not individual colors.');
        $this->assertSame($oldModel->id, $rows[0]->product_id);
        $this->assertSame($oldModel->id, $rows[1]->product_id);
        $this->assertCount(2, collect($rows)->where('product_id', $oldModel->id));
        $this->assertSame(
            $oldModel->id,
            app(ProductRepository::class)->getProducts(new Request())->items()[0]->id
        );

        // A later model edit must outrank an older color edit as well.
        $editedModel = Product::query()->where('barcode', 'NEW-1')->firstOrFail();
        $editedModel->forceFill(['updated_at' => now()->addDay()])->saveQuietly();

        $afterModelEdit = app(ProductColorRepository::class)->getProducts(new Request());
        $this->assertSame($editedModel->id, $afterModelEdit->items()[0]->product_id);
        $this->assertSame(
            $editedModel->id,
            app(ProductRepository::class)->getProducts(new Request())->items()[0]->id
        );
    }

    private function product(Category $category, string $barcode): Product
    {
        return Product::query()->create([
            'name' => $barcode, 'name_en' => $barcode, 'details' => 'Details', 'details_en' => 'Details',
            'barcode' => $barcode, 'category_id' => $category->id, 'country_id' => Country::UAE,
            'cost_price' => 10, 'retail_price' => 20,
        ]);
    }

    private function productColor(Product $product, Color $color, string $barcode): ProductColor
    {
        return ProductColor::query()->create([
            'product_id' => $product->id, 'color_id' => $color->id,
            'image' => $barcode.'.jpg', 'barcode' => $barcode,
            'sizes' => json_encode([['size' => 'M', 'stock' => 1, 'barcode' => $barcode.'-M']]),
            'stock' => 1, 'country_id' => Country::UAE,
        ]);
    }
}

<?php

namespace Tests\Unit;

use App\Models\Color;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\UserProduct;
use App\Services\OperationalPriceService;
use App\Services\StorefrontProductPresenter;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Tests\TestCase;

class StorefrontProductPresenterTest extends TestCase
{
    public function test_it_removes_large_model_fields_but_keeps_card_and_cart_data(): void
    {
        $prices = Mockery::mock(OperationalPriceService::class);
        $prices->shouldReceive('amount')->andReturnUsing(function ($model, $field, $currency, $base) {
            return (float) ($base ?? 0);
        });
        $prices->shouldReceive('normalizeInput')->andReturnUsing(fn ($value) => (float) $value);
        $prices->shouldReceive('format')->andReturnUsing(fn ($value) => (string) (float) $value);
        $this->app->instance(OperationalPriceService::class, $prices);

        $product = new Product();
        $product->forceFill([
            'id' => 91,
            'name' => 'Test product',
            'name_en' => 'Test product',
            'details' => str_repeat('large-description-', 100000),
            'image' => 'products/product.webp',
            'country_id' => 2,
            'retail_price' => 90,
            'sale_price' => 70,
            'price_before_discount' => 100,
            'rate' => 4.5,
            'reviews_count' => 8,
        ]);
        $product->syncOriginal();
        $product->setRelation('operationalPrices', new Collection());

        $colorName = new Color();
        $colorName->forceFill(['id' => 5, 'name' => 'أحمر', 'name_en' => 'Red', 'code' => '#ff0000']);

        $available = new UserProduct();
        $available->forceFill([
            'id' => 301,
            'product_color_id' => 201,
            'user_id' => 805,
            'country_id' => 2,
            'size' => 'M',
            'stock' => 3,
        ]);
        $empty = new UserProduct();
        $empty->forceFill([
            'id' => 302,
            'product_color_id' => 201,
            'user_id' => 805,
            'country_id' => 2,
            'size' => 'L',
            'stock' => 0,
        ]);

        $color = new ProductColor();
        $color->forceFill([
            'id' => 201,
            'product_id' => 91,
            'image' => 'products/red.webp',
            'barcode' => 'RED-91',
            'sizes' => str_repeat('unused-size-data', 100000),
        ]);
        $color->setRelation('color', $colorName);
        $color->setRelation('userProducts', new Collection([$available, $empty]));
        $product->setRelation('colors', new Collection([$color]));

        $payload = app(StorefrontProductPresenter::class)->product($product);
        $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $this->assertSame(91, $payload['id']);
        $this->assertSame(90.0, $payload['final_price_value']);
        $this->assertSame(['M'], $payload['sizes']);
        $this->assertSame(3, $payload['colors'][0]['stock']);
        $this->assertSame('RED-91', $payload['colors'][0]['barcode']);
        $this->assertSame('M', $payload['colors'][0]['user_products'][0]['size']);
        $this->assertCount(1, $payload['colors'][0]['user_products']);
        $this->assertStringNotContainsString('large-description', $json);
        $this->assertStringNotContainsString('unused-size-data', $json);
        $this->assertLessThan(10000, strlen($json));

        $homepageJson = json_encode(
            app(StorefrontProductPresenter::class)->collection(array_fill(0, 36, $product)),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
        );
        $this->assertLessThan(524288, strlen($homepageJson));
    }
}

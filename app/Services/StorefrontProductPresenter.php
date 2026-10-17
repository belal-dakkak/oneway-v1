<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductColor;

class StorefrontProductPresenter
{
    /**
     * Build the small product representation used by storefront cards.
     *
     * Product and ProductColor models both append several reporting and stock
     * fields when Laravel serializes them. Those fields are useful in the
     * administration screens, but make the initial storefront HTML very large
     * and repeat the same size data several times.
     *
     * @return array<int, array<string, mixed>>
     */
    public function collection(iterable $products): array
    {
        return collect($products)
            ->map(fn (Product $product): array => $this->product($product))
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    public function product(Product $product): array
    {
        $colors = $product->relationLoaded('colors')
            ? $product->getRelation('colors')
            : collect();

        $colors = $colors
            ->map(fn (ProductColor $color): ?array => $this->color($color))
            ->filter()
            ->values();

        $sizes = $colors
            ->flatMap(fn (array $color): array => array_column($color['user_products'], 'size'))
            ->filter(fn ($size): bool => $size !== null && $size !== '')
            ->unique()
            ->values()
            ->all();
        $firstColor = $colors->first();

        return [
            'id' => (int) $product->getKey(),
            'name' => (string) $product->name,
            'name_en' => (string) ($product->name_en ?: $product->name),
            'image' => $product->image,
            'photo_url' => $product->photo_url,
            'image_url' => $firstColor['photo_url'] ?? $product->photo_url,
            'retail_price' => $this->number($product->retail_price),
            'sale_price' => $this->number($product->sale_price),
            'price_before_discount' => $this->number($product->price_before_discount),
            'price_before_discount_value' => $this->number($product->price_before_discount_value),
            'final_price' => (string) $product->final_price,
            'final_price_value' => $this->number($product->final_price_value),
            'final_price_currency' => (string) $product->final_price_currency,
            'wholesale_price_value' => $this->number($product->wholesale_price_value),
            'formatted_wholesale_price' => (string) $product->formatted_wholesale_price,
            'formatted_price_before_discount' => (string) $product->formatted_price_before_discount,
            'prices_by_currency' => $product->prices_by_currency,
            'rate' => $this->number($product->rate),
            'reviews_count' => (int) ($product->reviews_count ?? 0),
            'sizes' => $sizes,
            'colors' => $colors->all(),
        ];
    }

    /** @return array<string, mixed>|null */
    private function color(ProductColor $color): ?array
    {
        $rows = $color->relationLoaded('userProducts')
            ? $color->getRelation('userProducts')
            : collect();

        $rows = $rows
            ->filter(fn ($row): bool => $row->size !== null && $row->size !== '' && (int) $row->stock > 0)
            ->map(function ($row): array {
                return [
                    'id' => (int) $row->id,
                    'product_color_id' => (int) $row->product_color_id,
                    'country_id' => (int) $row->country_id,
                    'size' => (string) $row->size,
                    'stock' => (int) $row->stock,
                ];
            })
            ->values();

        if ($rows->isEmpty()) {
            return null;
        }

        $colorModel = $color->relationLoaded('color') ? $color->getRelation('color') : null;
        $language = request()->header('Accept-Language', 'en');
        $colorName = $language === 'en'
            ? ($colorModel->name_en ?? $colorModel->name ?? '')
            : ($colorModel->name ?? $colorModel->name_en ?? '');

        return [
            'id' => (int) $color->getKey(),
            'barcode' => (string) $color->barcode,
            'photo_url' => $color->photo_url,
            'color_name' => (string) $colorName,
            'color_code' => (string) ($colorModel->code ?? ''),
            'stock' => (int) $rows->sum('stock'),
            'user_products' => $rows->all(),
        ];
    }

    private function number($value): float
    {
        return (float) ($value ?? 0);
    }
}

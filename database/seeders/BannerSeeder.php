<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Product;
use App\Models\ProductColor;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $product1 = ProductColor::query()->inRandomOrder()->first();
        $product2 = ProductColor::query()->inRandomOrder()->first();
        $product3 = ProductColor::query()->inRandomOrder()->first();
        Banner::query()->firstOrCreate([
            'type' => Banner::TYPE_PRODUCT,
            'image' => $product1->image,
            'related_id' => $product1->product_id
        ], [
            'type' => Banner::TYPE_PRODUCT,
            'image' => $product1->image,
            'related_id' => $product1->product_id
        ]);

        Banner::query()->firstOrCreate([
            'type' => Banner::TYPE_PRODUCT,
            'image' => $product2->image,
            'related_id' => $product2->product_id
        ], [
            'type' => Banner::TYPE_PRODUCT,
            'image' => $product2->image,
            'related_id' => $product2->product_id
        ]);

        Banner::query()->firstOrCreate([
            'type' => Banner::TYPE_PRODUCT,
            'image' => $product3->image,
            'related_id' => $product3->product_id
        ], [
            'type' => Banner::TYPE_PRODUCT,
            'image' => $product3->image,
            'related_id' => $product3->product_id
        ]);

    }
}

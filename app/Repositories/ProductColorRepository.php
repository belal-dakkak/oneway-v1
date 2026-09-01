<?php

namespace App\Repositories;

use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProductColorRepository
{
    private function clearHomeCache()
    {
        Cache::forget('home_new_products_1');
        Cache::forget('home_new_products_2');
        Cache::forget('home_offer_products_1');
        Cache::forget('home_offer_products_2');
        Cache::forget('home_random_products_1');
        Cache::forget('home_random_products_2');
    }

    public function add(Request $request)
    {
        try {
            if(count($request->get('selected_products')) == 0) return false;
            DB::beginTransaction();
            $product = new Product($request->all());
            if(auth()->user()->country_id == 2){
                $aedRate = Currency::where('name','aed')->first()->rate;
                $product->cost_price = $product->cost_price / $aedRate;
                $product->retail_price = $product->retail_price / $aedRate;
                $product->sale_price = $product->sale_price / $aedRate;
                if ($product->price_before_discount && $product->price_before_discount != 0 && $product->price_before_discount != '0') {
                    $product->price_before_discount = $product->price_before_discount / $aedRate;
                } else {
                    $product->price_before_discount = null;
                }

            }
            if ($request->hasFile('images'))
                $product->images = uploadMultiImages('images', 'products');

            if ($request->hasFile('photo'))
                $product->image = uploadImage('photo', 'products');
            if(!is_null($request->get('selected_sizes')))
                $product->sizes = array_column($request->get('selected_sizes'), 'id');
            else
                $product->sizes = [];
            $product->category_id = $request->get('selected_category')['id'];
            // $product->country_id = auth()->user()->country_id;
            $country_id = 1;
            if($request->get('country'))
                $country_id = array_key_exists('value',$request->get('country'))? $request->get('country')['value'] : $request->get('country')['id'];
            if(!in_array(auth()->user()->role_id,[User::ROLE_ADMIN,USER::ROLE_WAREHOUSE])  && $country_id != auth()->user()->country_id )
                $country_id = auth()->user()->country_id;

            $product->country_id = $country_id;
            $barcode = $request->get('barcode');
            if (!$request->get('barcode') || is_null($request->get('barcode')) || $request->get('barcode') == ''){
                generate:
                $barcode = generateRandomNumber(5);
                if (Product::query()->where('barcode', $barcode)->exists())
                    goto generate;
            }
            $product->barcode = $barcode;
            $product->save();

            foreach ($request->get('selected_products') as $subProduct){
                if (isset($subProduct['image']) && isset($subProduct['color']) && isset($subProduct['sizes'])){
                    $subProductBarcode = '';
                    if (!isset($subProduct['barcode']) || is_null($subProduct['barcode']) || $subProduct['barcode'] == ''){
                        barcode:
                        $rand = rand(1, 99);
                        $char = generateRandomString(1, true);
                        $subProductBarcode = $product->barcode.$char.$rand;
                        if (ProductColor::query()->where('barcode', $subProductBarcode)->exists())
                            goto barcode;
                    }else{
                        $subProductBarcode = $subProduct['barcode'];
                    }
                    $sizes = array();
                    $stock = 0;
                    foreach ($subProduct['sizes'] as $size) {
                        $subsubProductBarcode = $subProductBarcode.convertSizeToNumber($size['size']['id']);
                        array_push($sizes,['stock' => $size['stock'],'barcode' => $subsubProductBarcode,'size' => $size['size']['id']]);
                        $stock += $size['stock'];
                    }
                    ProductColor::query()->create([
                        'product_id' => $product->id,
                        'image' => $subProduct['image'][0],
                        'color_id' => $subProduct['color']['id'],
                        // 'stock' => $subProduct['stock']??0,
                        'stock' => $stock,
                        'barcode' => $subProductBarcode,
                        'sizes' => json_encode($sizes),
                        'country_id' => $country_id
                    ]);
                }
            }

        }catch (\Exception $exception){
            DB::rollBack();
            return $exception->getMessage();
        }
        DB::commit();
        $this->clearHomeCache();
        return $product;
    }

    public function update(Request $request, Product $product)
    {
        $product->update($request->all());
        // $product->update(['country_id' => auth()->user()->country_id]);

        if ($request->hasFile('images')){
            $product->images = uploadMultiImages('images', 'products', $product->images);
            $product->save();
        }
        $this->clearHomeCache();
    }

    public function getProducts(Request $request): LengthAwarePaginator
    {
        $country = auth()->user()->country_id;
        $products = ProductColor::query()->with(['color', 'product', 'product.category'])
        // ->where('country_id',$country)
        ->where('sizes','<>','')
        ->whereIn('country_id',[auth()->user()->country_id,3])
        ->whereNotNull('sizes');
        if ($category = $request->get('category_id'))
            $products->whereHas('product', function ($query) use ($category){
                $query->where('category_id', $category);
            });

        if ($color = $request->get('color_id'))
            $products->where('color_id', $color);

        if ($search = $request->get('search')){
            $products->Where('sizes', 'LIKE', "%$search%");
            // $products->whereHas('product', function ($query) use ($search){
            //     $query->where('name', 'LIKE', "%$search%")
            //         ->orWhere('barcode', 'LIKE', "%$search%");
            // })->orWhere('barcode', 'LIKE', "%$search%");
        }

        if ($request->has(['field', 'direction'])){
            $field = $request->get('field');
            $direction = $request->get('direction');

            $sortableArray = app(ProductColor::class)->getFillable();
            $sortableArray[] = 'updated_at';
            if(in_array($field, $sortableArray)){
                $products->orderBy($field, $direction);
            }else{
                $products->orderByDesc('updated_at');
            }
        }else{
            $products->orderByDesc('updated_at');
        }

        return $products->paginate(10);
    }

}

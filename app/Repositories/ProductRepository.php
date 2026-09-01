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

class ProductRepository
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

    public function update(Request $request, Product $product)
    {
        $data = $request->all(['name', 'name_en', 'details', 'details_en', 'cost_price', 'barcode', 'retail_price','sale_price', 'price_before_discount']);
        $priceBeforeDiscount = $data['price_before_discount'];
        if(auth()->user()->country_id == 2){
            $aedRate = Currency::where('name','aed')->first()->rate;
            $data['cost_price'] = $data['cost_price'] / $aedRate;
            $data['retail_price'] = $data['retail_price'] / $aedRate;
            $data['sale_price'] = $data['sale_price'] / $aedRate;
            if ($priceBeforeDiscount && $priceBeforeDiscount != 0 && $priceBeforeDiscount != '0' && $priceBeforeDiscount != 'NaN') {
                $data['price_before_discount'] = $priceBeforeDiscount / $aedRate;
            } else {
                $data['price_before_discount'] = null;
            }
        }
        $product->update($data);
        // $product->sizes = array_column($request->get('selected_sizes'), 'id');
        if(!is_null($request->get('selected_sizes')))
                $product->sizes = array_column($request->get('selected_sizes'), 'id');
            else
                $product->sizes = [];
        $product->category_id = $request->get('selected_category')['id'];
        $country_id = 1;
        if($request->get('country'))
            $country_id = array_key_exists('value',$request->get('country'))? $request->get('country')['value'] : $request->get('country')['id'];
        if(!in_array(auth()->user()->role_id,[User::ROLE_ADMIN,USER::ROLE_WAREHOUSE])  && $country_id != auth()->user()->country_id )
            $country_id = auth()->user()->country_id;

        $product->country_id = $country_id;
        if ($request->hasFile('images')){
            $product->images = uploadMultiImages('images', 'products', $product->images);
        }
        $product->save();
        foreach ($request->get('selected_products') as $subProduct){
            if (isset($subProduct['image']) && isset($subProduct['color']) && isset($subProduct['sizes'])){

                if (isset($subProduct['id'])){

                    $sizes = array();
                    $stock = 0;
                    foreach ($subProduct['sizes'] as $size) {

                        array_push($sizes,['stock' => $size['stock'],'size' => $size['size']['id']]);
                        $stock += $size['stock'];
                    }

                    $productColor = ProductColor::query()->where('id', $subProduct['id'])->first();
                    $subProductBarcode = $subProduct['barcode'];
                    if(is_null($subProduct['barcode']) || $subProduct['barcode'] == ''){
                        sbarcode:
                            $rand = rand(1, 99);
                            $char = generateRandomString(1, true);
                            $subProductBarcode = $product->barcode.$char.$rand;
                        if (ProductColor::query()->where('barcode', $subProductBarcode)->exists())
                            goto sbarcode;
                    }
                    foreach ($sizes as $key => $size) {
                        $subsubProductBarcode = $subProductBarcode.convertSizeToNumber($size['size']);
                        $sizes[$key]['barcode'] = $subsubProductBarcode;
                    }
                    $productColor->update([
                        'image' => is_array($subProduct['image']) ? $subProduct['image'][0] : $productColor->image,
                        'color_id' => $subProduct['color']['id'] ?? $productColor->color_id,
                        'stock' => $stock,
                        // 'stock' => $subProduct['stock'] ?? $productColor->stock,
                        'barcode' => $subProductBarcode,
                        'sizes' => json_encode($sizes),
                        'country_id' => $country_id
                    ]);
                }else{

                    barcode:
                    $rand = rand(1, 99);
                    $char = generateRandomString(1, true);
                    $subProductBarcode = $product->barcode.$char.$rand;
                    if (ProductColor::query()->where('barcode', $subProductBarcode)->where('country_id',auth()->user()->country_id)->exists())
                        goto barcode;

                    $sizes = array();

                    $stock = 0;

                    foreach ($subProduct['sizes'] as $size) {

                        // array_push($sizes,['stock' => $size['stock'],'size' => $size['size']['id']]);

                        $subsubProductBarcode = $subProductBarcode.convertSizeToNumber($size['size']['id']);
                        array_push($sizes,['stock' => $size['stock'],'barcode' => $subsubProductBarcode,'size' => $size['size']['id']]);

                        $stock += $size['stock'];
                    }


                    foreach ($sizes as $key => $size) {
                        if(!isset($size['barcode'])){
                            $subsubProductBarcode = $subProductBarcode.convertSizeToNumber($size['size']);
                            $size['barcode'] = $subsubProductBarcode;
                        }
                    }

                    // dd($sizes);

                    ProductColor::query()->create([
                        'product_id' => $product->id,
                        'image' => $subProduct['image'][0],
                        'color_id' => $subProduct['color']['id'],
                        'stock' => $stock,
                        // 'stock' => $subProduct['stock'],
                        'barcode' => $subProductBarcode,
                        'sizes' => json_encode($sizes),
                        'country_id' => $country_id
                    ]);
                }
            }
        }
        $this->clearHomeCache();
    }

    public function getProducts(Request $request): LengthAwarePaginator
    {
        $products = Product::query()->with(['category', 'colors']);
        // $products = Product::query()->with(['category', 'colors'])->where('country_id',auth()->user()->country_id);
        if ($category = $request->get('category_id'))
            $products->where('category_id', $category);
        if ($search = $request->get('search')){
            $products->where('name', 'LIKE', "%$search%")
                ->orWhere('barcode', 'LIKE', "%$search%");
        }
        if ($request->has(['field', 'direction'])){
            $field = $request->get('field');
            $direction = $request->get('direction');
            $sortableArray = app(Product::class)->getFillable();
            $sortableArray[] = 'id';
            if(in_array($field, $sortableArray)){
                $products->orderBy($field, $direction);
            }else{
                $products->orderByDesc('id');
            }
        }else{
            $products->orderByDesc('id');
        }
        return $products->paginate(15);
    }

    public function delete(Product $product)
    {
        try {
            DB::beginTransaction();
            deleteMedia($product, 'photo');
            foreach ($product->colors as $color){
                deleteMedia($color, 'image');
                $color->delete();
            }
            $product->delete();
        }catch (\Exception $exception){
            DB::rollBack();
            return $exception->getMessage();
        }
        DB::commit();
        $this->clearHomeCache();
        return false;
    }
}

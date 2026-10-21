<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\ProductColor;
use App\Models\User;
use App\Services\OperationalPriceService;
use App\Support\Country;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProductRepository
{
    private $prices;

    public function __construct(OperationalPriceService $prices)
    {
        $this->prices = $prices;
    }

    private function clearHomeCache()
    {
        foreach (Country::allowedIds() as $countryId) {
            foreach (['home_new_products_', 'home_offer_products_', 'home_random_products_'] as $prefix) {
                Cache::forget($prefix.$countryId);
                Cache::forget($prefix.$countryId.'_m');
            }
        }
    }

    public function update(Request $request, Product $product)
    {
        $countryId = $this->requestedCountryId($request);
        $currencyCode = strtoupper((string) $request->input(
            'price_currency_code',
            Country::defaultCurrency($countryId === Country::globalProductId()
                ? (int) auth()->user()->country_id
                : $countryId)
        ));
        $preparedPrices = $this->prices->prepare(
            $request->only(OperationalPriceService::PRODUCT_FIELDS),
            $currencyCode,
            OperationalPriceService::PRODUCT_FIELDS
        );
        DB::transaction(function () use ($request, $product, $preparedPrices, $countryId) {
            $data = array_merge(
                $request->only(['name', 'name_en', 'details', 'details_en', 'barcode']),
                $preparedPrices['base']
            );
            $product->update($data);
            // $product->sizes = array_column($request->get('selected_sizes'), 'id');
            if(!is_null($request->get('selected_sizes')))
                    $product->sizes = array_column($request->get('selected_sizes'), 'id');
                else
                    $product->sizes = [];
            $product->category_id = $request->get('selected_category')['id'];
            $product->country_id = $countryId;
            if ($request->hasFile('images')){
                $product->images = uploadMultiImages('images', 'products', $product->images);
            }
            $product->save();
            $this->prices->persist($product, $preparedPrices);
            $coverProductColorId = null;
            foreach ($request->get('selected_products', []) as $subProduct) {
                if (!isset($subProduct['image'], $subProduct['color'], $subProduct['sizes'])) {
                    continue;
                }

                if (isset($subProduct['id'])) {
                    $sizes = [];
                    $stock = 0;
                    foreach ($subProduct['sizes'] as $size) {
                        $sizes[] = ['stock' => $size['stock'], 'size' => $size['size']['id']];
                        $stock += $size['stock'];
                    }

                    $productColor = ProductColor::query()
                        ->where('product_id', $product->id)
                        ->findOrFail($subProduct['id']);
                    $subProductBarcode = $subProduct['barcode'];
                    if (is_null($subProductBarcode) || $subProductBarcode === '') {
                        sbarcode:
                        $rand = rand(1, 99);
                        $char = generateRandomString(1, true);
                        $subProductBarcode = $product->barcode.$char.$rand;
                        if (ProductColor::query()->where('barcode', $subProductBarcode)->exists()) {
                            goto sbarcode;
                        }
                    }
                    foreach ($sizes as $key => $size) {
                        $sizes[$key]['barcode'] = $subProductBarcode.convertSizeToNumber($size['size']);
                    }
                    $productColor->update([
                        'image' => is_array($subProduct['image']) ? $subProduct['image'][0] : $productColor->image,
                        'color_id' => $subProduct['color']['id'] ?? $productColor->color_id,
                        'stock' => $stock,
                        'barcode' => $subProductBarcode,
                        'sizes' => json_encode($sizes),
                        'country_id' => $countryId,
                    ]);
                    if (filter_var($subProduct['is_cover'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                        $coverProductColorId = $productColor->id;
                    }
                    continue;
                }

                barcode:
                $rand = rand(1, 99);
                $char = generateRandomString(1, true);
                $subProductBarcode = $product->barcode.$char.$rand;
                if (ProductColor::query()->where('barcode', $subProductBarcode)->where('country_id', auth()->user()->country_id)->exists()) {
                    goto barcode;
                }

                $sizes = [];
                $stock = 0;
                foreach ($subProduct['sizes'] as $size) {
                    $sizes[] = [
                        'stock' => $size['stock'],
                        'barcode' => $subProductBarcode.convertSizeToNumber($size['size']['id']),
                        'size' => $size['size']['id'],
                    ];
                    $stock += $size['stock'];
                }

                $productColor = ProductColor::query()->create([
                    'product_id' => $product->id,
                    'image' => $subProduct['image'][0],
                    'color_id' => $subProduct['color']['id'],
                    'stock' => $stock,
                    'barcode' => $subProductBarcode,
                    'sizes' => json_encode($sizes),
                    'country_id' => $countryId,
                ]);
                if (filter_var($subProduct['is_cover'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    $coverProductColorId = $productColor->id;
                }
            }

            if ($coverProductColorId) {
                $product->cover_product_color_id = $coverProductColorId;
                $product->save();
            }
        }, 3);
        $this->clearHomeCache();
    }

    private function requestedCountryId(Request $request): int
    {
        $countryId = (int) auth()->user()->country_id;
        if ($request->get('country')) {
            $country = $request->get('country');
            $countryId = (int) (array_key_exists('value', $country) ? $country['value'] : $country['id']);
        }
        if (!in_array(auth()->user()->role_id, [User::ROLE_ADMIN, User::ROLE_WAREHOUSE], true)
            && $countryId !== (int) auth()->user()->country_id) {
            return (int) auth()->user()->country_id;
        }
        return $countryId;
    }

    public function getProducts(Request $request): LengthAwarePaginator
    {
        $products = Product::query()
            ->with(['category', 'colors.color', 'coverColor'])
            ->withMax('colors', 'updated_at')
            ->withMax('colors', 'id')
            ->whereIn('country_id', [auth()->user()->country_id, Country::globalProductId()]);
        // $products = Product::query()->with(['category', 'colors'])->where('country_id',auth()->user()->country_id);
        if ($category = $request->get('category_id'))
            $products->where('category_id', $category);
        if ($search = $request->get('search')){
            $products->where(function ($query) use ($search) {
                $query->where('name', 'LIKE', "%$search%")
                    ->orWhere('name_en', 'LIKE', "%$search%")
                    ->orWhere('barcode', 'LIKE', "%$search%")
                    ->orWhereHas('colors', function ($colors) use ($search) {
                        $colors->where('barcode', 'LIKE', "%$search%");
                    });
            });
        }
        if ($request->has(['field', 'direction'])){
            $field = $request->get('field');
            $direction = $request->get('direction');
            $sortableArray = app(Product::class)->getFillable();
            $sortableArray[] = 'id';
            if(in_array($field, $sortableArray)){
                $products->orderBy($field, $direction);
            }else{
                $products->orderByRaw('CASE WHEN colors_max_updated_at IS NULL OR products.updated_at >= colors_max_updated_at THEN products.updated_at ELSE colors_max_updated_at END DESC')
                    ->orderByDesc('colors_max_id')
                    ->orderByDesc('products.id');
            }
        }else{
            $products->orderByRaw('CASE WHEN colors_max_updated_at IS NULL OR products.updated_at >= colors_max_updated_at THEN products.updated_at ELSE colors_max_updated_at END DESC')
                ->orderByDesc('colors_max_id')
                ->orderByDesc('products.id');
        }
        return $products->paginate(15)->withQueryString();
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

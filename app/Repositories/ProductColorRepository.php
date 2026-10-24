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

class ProductColorRepository
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

    public function add(Request $request)
    {
        try {
            if(count($request->get('selected_products')) == 0) return false;
            DB::beginTransaction();
            $country_id = $this->requestedCountryId($request);
            $currencyCode = strtoupper((string) $request->input(
                'price_currency_code',
                Country::defaultCurrency($country_id === Country::globalProductId()
                    ? (int) auth()->user()->country_id
                    : $country_id)
            ));
            $preparedPrices = $this->prices->prepare(
                $request->only(OperationalPriceService::PRODUCT_FIELDS),
                $currencyCode,
                OperationalPriceService::PRODUCT_FIELDS
            );
            $product = new Product(array_merge($request->all(), $preparedPrices['base']));
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
            $this->prices->persist($product, $preparedPrices);

            $coverProductColorId = null;
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
                    $productColor = ProductColor::query()->create([
                        'product_id' => $product->id,
                        'image' => $subProduct['image'][0],
                        'color_id' => $subProduct['color']['id'],
                        // 'stock' => $subProduct['stock']??0,
                        'stock' => $stock,
                        'barcode' => $subProductBarcode,
                        'sizes' => json_encode($sizes),
                        'country_id' => $country_id
                    ]);
                    if (filter_var($subProduct['is_cover'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                        $coverProductColorId = $productColor->id;
                    }
                }
            }

            if ($coverProductColorId) {
                $product->cover_product_color_id = $coverProductColorId;
                $product->save();
            }

        }catch (\Exception $exception){
            DB::rollBack();
            return $exception->getMessage();
        }
        DB::commit();
        $this->clearHomeCache();
        return $product;
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
        $countryIds = [auth()->user()->country_id, Country::globalProductId()];
        $category = $request->get('category_id');
        $color = $request->get('color_id');
        $search = trim((string) $request->get('search', ''));

        $eligibleColors = function ($query) use ($countryIds, $color) {
            $query->whereIn('country_id', $countryIds)
                ->whereNotNull('sizes')
                ->where('sizes', '<>', '');
            if ($color) {
                $query->where('color_id', $color);
            }
        };

        $products = Product::query()
            ->withMax(['colors' => $eligibleColors], 'updated_at')
            ->withMax(['colors' => $eligibleColors], 'id')
            ->with([
                'category',
                'colors' => function ($query) use ($eligibleColors) {
                    $eligibleColors($query);
                    $query->with(['color', 'product.category'])->orderBy('id');
                },
            ])
            ->whereIn('country_id', $countryIds)
            ->whereHas('colors', $eligibleColors);

        if ($category) {
            $products->where('category_id', $category);
        }

        if ($search !== '') {
            $products->where(function ($query) use ($search, $countryIds, $color) {
                $query->where('name', 'LIKE', "%$search%")
                    ->orWhere('name_en', 'LIKE', "%$search%")
                    ->orWhere('barcode', 'LIKE', "%$search%")
                    ->orWhereHas('colors', function ($colors) use ($search, $countryIds, $color) {
                        $colors->whereIn('country_id', $countryIds)
                            ->whereNotNull('sizes')
                            ->where('sizes', '<>', '')
                            ->when($color, function ($query) use ($color) {
                                $query->where('color_id', $color);
                            })
                            ->where(function ($matching) use ($search) {
                                $matching->where('barcode', 'LIKE', "%$search%")
                                    ->orWhere('sizes', 'LIKE', "%$search%");
                            });
                    });
            });
        }

        $field = (string) $request->get('field', '');
        $direction = strtolower((string) $request->get('direction')) === 'asc' ? 'asc' : 'desc';
        if (in_array($field, ['id', 'cost_price', 'updated_at'], true)) {
            $products->orderBy('products.'.$field, $direction);
        } else {
            $products->orderByRaw('CASE WHEN colors_max_updated_at IS NULL OR products.updated_at >= colors_max_updated_at THEN products.updated_at ELSE colors_max_updated_at END DESC')
                ->orderByDesc('colors_max_id')
                ->orderByDesc('products.id');
        }

        $paginator = $products->paginate(10)->withQueryString();
        $paginator->setCollection(
            $paginator->getCollection()->flatMap(function (Product $product) {
                return $product->colors;
            })->values()
        );

        return $paginator;
    }

}

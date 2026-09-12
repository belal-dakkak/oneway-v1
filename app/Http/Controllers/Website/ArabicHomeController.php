<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Slider;
use App\Models\MobileSlider;
use App\Models\User;
use App\Models\UserProduct;
use App\Models\ProductColor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Support\Country;
use App\Services\WebsiteInventoryService;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class ArabicHomeController extends Controller
{
    public function index(): Response
    {
        $countryId = Country::id();
        $country = $countryId;
        $language = 'ar';
        $wnumber = Country::code() === 'LB' ? '+96176658734' : (Country::code() === 'SY' ? '' : '+971545516995');
        $stockUserId = app(WebsiteInventoryService::class)->stockUserId($countryId);
        $featuredProducts = Product::query()
            ->withCount('reviews')
            ->whereIn('country_id', [$countryId, \App\Support\Country::globalProductId()])
            ->when(Session::get('is_merchant'), fn($q) => $q->where('shown_for_merchant', true))
            ->whereHas('colors.userProducts', function($query) use ($countryId, $stockUserId) {
                $query->where('country_id', $countryId)->where('user_id', $stockUserId ?: 0)->where('stock', '>', 0);
            })
            ->with(['colors' => function($query) use ($countryId, $stockUserId) {
                $query->whereHas('userProducts', function($q) use ($countryId, $stockUserId) {
                    $q->where('country_id', $countryId)->where('user_id', $stockUserId ?: 0)->where('stock', '>', 0);
                })->with(['userProducts' => function($q) use ($countryId, $stockUserId) {
                    $q->where('country_id', $countryId)->where('user_id', $stockUserId ?: 0);
                }]);
            }])
            ->limit(12)
            ->get();

        foreach ($featuredProducts as $product) {
            $product->final_price = $product->getFinalPriceAttribute();
            if (Session::get('is_merchant')) {
                $product->wholesale_price_value = $product->getWholesalePriceValueAttribute();
                $product->formatted_wholesale_price = $product->getFormattedWholesalePriceAttribute();
                $product->append(['wholesale_price_value', 'formatted_wholesale_price']);
            }
        }
        // Cache Settings for 1 hour
        $settings = Cache::remember("settings_{$country}_{$language}", 3600, function() use ($country, $language) {
            return Setting::where('country', $country)->where('language', $language)->pluck('value', 'name')->toArray();
        });

        $sliders = Cache::remember("home_sliders", 3600, fn() => Slider::all());
        $mobileSliders = Cache::remember("mobile_home_sliders", 3600, fn() => MobileSlider::all());
        $categories = Cache::remember("shop_categories", 3600, fn() => transformDataForVue(Category::all()));

        $title     = $settings['title'] ?? '';
        $phone     = $settings['phone'] ?? '';
        $tiktok    = $settings['tiktok'] ?? '';
        $facebook  = $settings['facebook'] ?? '';
        $instagram = $settings['instagram'] ?? '';
        $whatsapp  = $settings['whatsapp'] ?? '';
        $address   = $settings['address'] ?? '';
        $email     = $settings['email'] ?? '';
        return Inertia::render('WelcomeAr', [
            'products' => $featuredProducts,
            'categories' => $categories,
            'wnumber' => $wnumber,
            'sliders' => $sliders,
            'mobileSliders' => $mobileSliders,
            'title' => $title,
            'phone' => $phone,
            'tiktok' => $tiktok,
            'facebook' => $facebook,
            'instagram' => $instagram,
            'whatsapp' => $whatsapp,
            'address' => $address,
            'email' => $email
        ]);
    }

    public function shop(Request $request)
    {
        $is_uae = Session::get('country') == 'AE';
        $categories = Category::query()->get();
        $categories = transformDataForVue($categories);
        $category = null;

        $country_id = Country::id();
        $stockUserId = app(WebsiteInventoryService::class)->stockUserId($country_id);

        $userProducts = Product::query()
            ->with(['category', 'colors' => function($query) use ($country_id, $stockUserId) {
                $query->whereHas('userProducts', function ($q) use ($country_id, $stockUserId) {
                    $q->where('country_id', $country_id)->where('user_id', $stockUserId ?: 0)->where('stock', '>', 0);
                })->with(['color', 'userProducts' => function($q) use ($country_id, $stockUserId) {
                        $q->where('country_id', $country_id)->where('user_id', $stockUserId ?: 0);
                    }]);
            }])
            ->whereIn('country_id', [$country_id, \App\Support\Country::globalProductId()])
            ->when(Session::get('is_merchant'), fn($q) => $q->where('shown_for_merchant', true))
            ->whereHas('colors.userProducts', function ($query) use ($country_id, $stockUserId) {
                $query->where('country_id', $country_id)->where('user_id', $stockUserId ?: 0)->where('stock', '>', 0);
            });

        if ($search = $request->get('search'))
            $userProducts->where('name', 'LIKE', "%$search%");

        if ($categoryId = $request->get('category')){
            $category = transformItemForVue(Category::query()->find($categoryId), Category::class);
            $userProducts->where('category_id', $categoryId);
        }

        if ($request->has(['field', 'direction'])){
            $field = $request->get('field');
            $direction = $request->get('direction');

            $sortableArray = app(Product::class)->getFillable();
            $sortableArray[] = 'id';
            if(in_array($field, $sortableArray)){
                $userProducts->orderBy($field, $direction);
            }else{
                $userProducts->orderByDesc('id');
            }
        }else{
            $userProducts->orderByDesc('id');
        }

        $products = $userProducts->paginate(24);

        foreach($products as $product) {
            $product->final_price = $product->getFinalPriceAttribute();

            if (Session::get('is_merchant')) {
                $product->wholesale_price_value = $product->getWholesalePriceValueAttribute();
                $product->formatted_wholesale_price = $product->getFormattedWholesalePriceAttribute();
                $product->append(['wholesale_price_value', 'formatted_wholesale_price']);
            }

            // Extract sizes from preloaded userProducts to avoid N+1
            $sizes_arr = [];
            foreach ($product->colors as $color) {
                foreach ($color->userProducts as $up) {
                    if ($up->size && $up->stock > 0) {
                        $sizes_arr[] = $up->size;
                    }
                }
            }
            $product->sizes = array_unique($sizes_arr);
        }


        if ($request->wantsJson()){
            return $products;
        }

        return Inertia::render('ShopAr', [
            'categories' => $categories,
            'products' => $products,
            'category' => $category,
            'filters' => $request->all(['search', 'field', 'direction'])
        ]);
    }
}

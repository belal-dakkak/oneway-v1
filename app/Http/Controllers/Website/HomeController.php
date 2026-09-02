<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Slider;
use App\Models\MobileSlider;
use App\Models\User;
use App\Models\UserProduct;
use App\Models\ProductColor;
use App\Models\MerchantCode;
use App\Models\Currency;
use App\Support\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $countryCode = Country::code();
        $countryId = Country::id($countryCode);
        $country = $countryId;
        $language = 'ar';
        $isMerchant = Session::get('is_merchant');

        $baseQuery = function () use ($countryId) {
            return Product::query()
                ->withCount('reviews')
                ->with([
                    'colors.color',
                    'colors.userProducts' => function ($q) use ($countryId) {
                        $q->where('country_id', $countryId)
                            ->where('stock', '>', 0)
                            ->select(['id','product_color_id','size','stock','country_id']);
                    }
                ])
                ->join('product_colors', 'products.id', '=', 'product_colors.product_id')
                ->join('user_products', 'product_colors.id', '=', 'user_products.product_color_id')
                ->where('user_products.country_id', $countryId)
                ->whereIn('products.country_id', [$countryId, Country::globalProductId()])
                ->when(Session::get('is_merchant'), function ($q) {
                    return $q->where('shown_for_merchant', true);
                })
                ->where('user_products.stock', '>', 0)
                ->whereNotNull('user_products.size')
                ->select('products.*')
                ->distinct();
        };

        $merchantSuffix = $isMerchant ? '_m' : '';

        // New Products
        $newProducts = Cache::remember(
            "home_new_products_{$countryId}{$merchantSuffix}",
            600,
            function () use ($baseQuery) {
                return $baseQuery()
                    ->orderByDesc('products.id')
                    ->limit(12)
                    ->get();
            }
        );

        // Offer Products
        $offerProducts = Cache::remember(
            "home_offer_products_{$countryId}{$merchantSuffix}",
            600,
            function () use ($baseQuery) {
                return $baseQuery()
                    ->where(function($q) {
                        $q->whereNotNull('products.price_before_discount')
                          ->orWhere('products.price_before_discount','>', 0);
                    })
                    ->limit(12)
                    ->get();
            }
        );

        $excludedIds = $newProducts->pluck('id')->toArray();
        // Random Products
        $randomProducts = Cache::remember(
            "home_random_products_{$countryId}{$merchantSuffix}",
            600,
            function () use ($baseQuery, $excludedIds) {
                return $baseQuery()
                    ->whereNotIn('products.id', $excludedIds)
                    ->inRandomOrder()
                    ->limit(12)
                    ->get();
            }
        );

        // Post-processing
        $allCollections = [$newProducts, $offerProducts, $randomProducts];

        foreach ($allCollections as $collection) {
            foreach ($collection as $product) {

                $product->final_price = $product->getFinalPriceAttribute();

                $sizes = $product->colors
                    ->flatMap(function ($color) {
                        return $color->userProducts;
                    })
                    ->pluck('size')
                    ->unique()
                    ->values();

                $product->sizes = $sizes;

                if ($isMerchant) {
                    $product->wholesale_price_value = $product->getWholesalePriceValueAttribute();
                    $product->formatted_wholesale_price = $product->getFormattedWholesalePriceAttribute();
                    $product->append(['wholesale_price_value', 'formatted_wholesale_price']);
                }
            }
        }

        $wnumber = $countryCode === 'LB' ? '+96176658734'
            : ($countryCode === 'SY' ? '' : '+971545516995');

        // Categories Cache
        $categories = Cache::remember('shop_categories', 3600, function () {
            return transformDataForVue(Category::all());
        });

        // Settings Cache
        $settings = Cache::remember(
            "shop_settings_{$country}_{$language}",
            3600,
            function () use ($country, $language) {
                return Setting::where('country', $country)
                    ->where('language', $language)
                    ->pluck('value', 'name')
                    ->toArray();
            }
        );

        // Sliders Cache
        $sliders = Cache::remember(
            "home_sliders",
            3600,
            function () {
                return Slider::all();
            }
        );

        $mobileSliders = Cache::remember(
            "mobile_home_sliders",
            3600,
            function () {
                return MobileSlider::all();
            }
        );

        return Inertia::render('Welcome', [
            'newProducts' => $newProducts,
            'offerProducts' => $offerProducts,
            'randomProducts' => $randomProducts,
            'categories' => $categories,
            'wnumber' => $wnumber,
            'sliders' => $sliders,
            'mobileSliders' => $mobileSliders,
            'title' => $settings['title'] ?? '',
            'phone' => $settings['phone'] ?? '',
            'tiktok' => $settings['tiktok'] ?? '',
            'facebook' => $settings['facebook'] ?? '',
            'instagram' => $settings['instagram'] ?? '',
            'whatsapp' => $settings['whatsapp'] ?? '',
            'address' => $settings['address'] ?? '',
            'email' => $settings['email'] ?? ''
        ]);
    }

    public function shop(Request $request)
    {
        $countryCode = Country::code();
        $countryId = Country::id($countryCode);
        $isMerchant = Session::get('is_merchant');

        // Cache categories
        $categories = Cache::remember('shop_categories', 3600, function () {
            return transformDataForVue(Category::all());
        });

        $category = null;

        $query = Product::query()
            ->select([
                'products.id',
                'products.name',
                'products.category_id',
                'products.country_id',
                'products.retail_price',
                'products.sale_price',
                'products.price_before_discount'
            ])
            ->join('product_colors', 'product_colors.product_id', '=', 'products.id')
            ->join('user_products', 'user_products.product_color_id', '=', 'product_colors.id')
            ->whereIn('products.country_id', [$countryId, Country::globalProductId()])
            ->when(Session::get('is_merchant'), function ($q) {
                return $q->where('shown_for_merchant', true);
            })
            ->where('user_products.country_id', $countryId)
            ->where('user_products.stock', '>', 0)
            ->with([
                'category',
                'colors.color',
                'colors.userProducts' => function ($q) use ($countryId) {
                    $q->where('country_id', $countryId)
                        ->where('stock', '>', 0)
                        ->select(['id','product_color_id','size','stock','country_id']);
                }
            ])
            ->distinct();

        // Search
        if ($search = $request->get('search')) {
            $query->where('products.name', 'LIKE', "%{$search}%");
        }

        // Category Filter
        if ($categoryId = $request->get('category')) {
            $categoryModel = Category::find($categoryId);
            if ($categoryModel) {
                $category = transformItemForVue($categoryModel, Category::class);
            }

            $query->where('products.category_id', $categoryId);
        }

        // Sale Filter
        if ($request->boolean('sale')) {
            $query->where(function($q) {
                $q->whereNotNull('products.price_before_discount')
                  ->orWhere('products.price_before_discount', '>', 0);
            });
        }

        // Sorting
        if ($request->has(['field', 'direction'])) {

            $field = $request->get('field');
            $direction = $request->get('direction');

            $sortable = array_merge(
                (new Product)->getFillable(),
                ['id']
            );

            if (in_array($field, $sortable)) {
                $query->orderBy("products.$field", $direction);
            } else {
                $query->orderByDesc('products.id');
            }

        } else {
            $query->orderByDesc('products.id');
        }

        // Pagination
        $products = $query->paginate(24)->withQueryString();

        // Post-processing
        foreach ($products as $product) {

            $product->final_price = $product->getFinalPriceAttribute();

            if ($isMerchant) {
                $product->wholesale_price_value = $product->getWholesalePriceValueAttribute();
                $product->formatted_wholesale_price = $product->getFormattedWholesalePriceAttribute();
                $product->append(['wholesale_price_value', 'formatted_wholesale_price']);
            }

            $sizes = $product->colors
                ->flatMap(function ($color) {
                    return $color->userProducts;
                })
                ->pluck('size')
                ->unique()
                ->values();

            $product->sizes = $sizes;
        }

        if ($request->wantsJson()) {
            return $products;
        }

        // Cache settings
        $country = $countryId;
        $language = 'ar';

        $settings = Cache::remember(
            "shop_settings_{$country}_{$language}",
            3600,
            function () use ($country, $language) {
                return Setting::where('country', $country)
                    ->where('language', $language)
                    ->pluck('value', 'name')
                    ->toArray();
            }
        );

        return Inertia::render('Shop', [
            'categories' => $categories,
            'products' => $products,
            'category' => $category,
            'filters' => $request->only(['search','field','direction','category','sale']),
            'title' => $settings['title'] ?? '',
            'phone' => $settings['phone'] ?? '',
            'email' => $settings['email'] ?? '',
            'facebook' => $settings['facebook'] ?? '',
            'instagram' => $settings['instagram'] ?? '',
            'tiktok' => $settings['tiktok'] ?? '',
            'whatsapp' => $settings['whatsapp'] ?? '',
            'address' => $settings['address'] ?? ''
        ]);
    }


    public function product(Product $product): Response
    {
        if (Session::get('is_merchant') && !$product->shown_for_merchant) {
            abort(404);
        }

        $countryId = Country::id();

        $product->load([
            'category',
            'colors' => function ($q) use ($countryId) {
                $q->whereHas('userProducts', function ($uq) use ($countryId) {
                    $uq->where('country_id', $countryId)
                        ->where('stock', '>', 0)
                        ->whereNotNull('size');
                });
            },
            'colors.color',
            'colors.userProducts' => function($q) use ($countryId) {
                $q->where('country_id', $countryId)
                    ->where('stock', '>', 0)
                    ->whereNotNull('size');
            }
        ]);
        $product->final_price = $product->getFinalPriceAttribute();
        if (Session::get('is_merchant')) {
            $product->wholesale_price_value = $product->getWholesalePriceValueAttribute();
            $product->formatted_wholesale_price = $product->getFormattedWholesalePriceAttribute();
            $product->append(['wholesale_price_value', 'formatted_wholesale_price']);
        }

        $relatedProducts = Product::query()
            ->withCount('reviews')
            ->where('category_id', $product->category_id)
            ->where('products.id', '!=', $product->id)
            ->whereIn('products.country_id', [$countryId, Country::globalProductId()])
            ->when(Session::get('is_merchant'), function ($q) {
                return $q->where('shown_for_merchant', true);
            })
            ->where('products.sale_price', '>', 0)
            ->whereHas('colors.userProducts', function ($uq) use ($countryId) {
                $uq->where('country_id', $countryId)
                    ->where('stock', '>', 0)
                    ->whereNotNull('size');
            })
            ->limit(12)
            ->get();

        foreach($relatedProducts as $rp) {
            $rp->final_price = $rp->getFinalPriceAttribute();
            $rp->formatted_price_before_discount = $rp->getFormattedPriceBeforeDiscountAttribute();
            $rp->append(['final_price', 'formatted_price_before_discount']);

            if (Session::get('is_merchant')) {
                $rp->wholesale_price_value = $rp->getWholesalePriceValueAttribute();
                $rp->formatted_wholesale_price = $rp->getFormattedWholesalePriceAttribute();
                $rp->append(['wholesale_price_value', 'formatted_wholesale_price']);
            }
        }

        $categories = Category::all();
        $categories = transformDataForVue($categories);

        $country = Country::id();
        $language = 'ar';
        $settings = Setting::where('country', $country)->where('language', $language)->pluck('value', 'name')->toArray();

//        dd($product->colors);
        return Inertia::render('ProductDetail', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
            'categories' => $categories,
            'phone' => $settings['phone'] ?? '',
            'email' => $settings['email'] ?? '',
            'facebook' => $settings['facebook'] ?? '',
            'instagram' => $settings['instagram'] ?? '',
            'tiktok' => $settings['tiktok'] ?? '',
            'whatsapp' => $settings['whatsapp'] ?? '',
            'address' => $settings['address'] ?? '',
        ])->withViewData([
            'meta_title' => $product->name,
            'meta_description' => mb_substr(strip_tags($product->details), 0, 160),
            'meta_image' => $product->image_url,
        ]);
    }

    public function categories(): Response
    {
        $categories = Category::all();
        $categories = transformDataForVue($categories);

        $country = Country::id();
        $language = 'ar';
        $settings = Setting::where('country', $country)->where('language', $language)->pluck('value', 'name')->toArray();

        return Inertia::render('Categories', [
            'categories' => $categories,
            'phone' => $settings['phone'] ?? '',
            'email' => $settings['email'] ?? '',
            'facebook' => $settings['facebook'] ?? '',
            'instagram' => $settings['instagram'] ?? '',
            'tiktok' => $settings['tiktok'] ?? '',
            'whatsapp' => $settings['whatsapp'] ?? '',
            'address' => $settings['address'] ?? '',
        ]);
    }

    public function about(): Response
    {
        $categories = Category::limit(6)->get();
        $categories = transformDataForVue($categories);

        $country = Country::id();
        $language = 'ar';
        $settings = Setting::where('country', $country)->where('language', $language)->pluck('value', 'name')->toArray();

        return Inertia::render('About', [
            'categories' => $categories,
            'phone' => $settings['phone'] ?? '',
            'email' => $settings['email'] ?? '',
            'facebook' => $settings['facebook'] ?? '',
            'instagram' => $settings['instagram'] ?? '',
            'tiktok' => $settings['tiktok'] ?? '',
            'whatsapp' => $settings['whatsapp'] ?? '',
            'address' => $settings['address'] ?? '',
        ]);
    }

    public function contact(): Response
    {
        $categories = Category::limit(6)->get();
        $categories = transformDataForVue($categories);

        $country = Country::id();
        $language = 'ar';
        $settings = Setting::where('country', $country)->where('language', $language)->pluck('value', 'name')->toArray();

        return Inertia::render('Contact', [
            'categories' => $categories,
            'phone' => $settings['phone'] ?? '',
            'email' => $settings['email'] ?? '',
            'facebook' => $settings['facebook'] ?? '',
            'instagram' => $settings['instagram'] ?? '',
            'tiktok' => $settings['tiktok'] ?? '',
            'whatsapp' => $settings['whatsapp'] ?? '',
            'address' => $settings['address'] ?? '',
        ]);
    }
    public function verifyMerchantCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        $merchantCode = MerchantCode::where('code', $request->code)
            ->where('is_active', true)
            ->first();

        if ($merchantCode) {
            Session::put('is_merchant', true);
            Session::put('merchant_code', $request->code);
            return response()->json(['success' => true, 'message' => 'تم التفعيل بنجاح']);
        }

        return response()->json(['success' => false, 'message' => 'كود غير صحيح'], 422);
    }

    public function disableMerchantMode(Request $request)
    {
        Session::forget('is_merchant');
        Session::forget('merchant_code');
        return response()->json(['success' => true]);
    }

    public function setCountry(Request $request)
    {
        $request->validate([
            'country' => 'required|string|in:AE,LB,SY'
        ]);

        if ($request->country === 'SY' && (float) Currency::query()->where('name', 'syp')->value('rate') <= 0) {
            return redirect()->back()->with('error', 'يجب ضبط سعر صرف الليرة السورية قبل تفعيل متجر سوريا.');
        }

        Session::put('country', $request->country);

        return redirect()->back();
    }

    public function comingSoon(): Response
    {
        $categories = Category::limit(6)->get();
        $categories = transformDataForVue($categories);

        $country = Country::id();
        $language = 'ar';
        $settings = Setting::where('country', $country)->where('language', $language)->pluck('value', 'name')->toArray();

        return Inertia::render('ComingSoon', [
            'categories' => $categories,
            'phone' => $settings['phone'] ?? '',
            'email' => $settings['email'] ?? '',
            'facebook' => $settings['facebook'] ?? '',
            'instagram' => $settings['instagram'] ?? '',
            'tiktok' => $settings['tiktok'] ?? '',
            'whatsapp' => $settings['whatsapp'] ?? '',
            'address' => $settings['address'] ?? '',
        ]);
    }

    public function privacyPolicy(): Response
    {
        return $this->renderPolicyPage('Privacy');
    }

    public function termsAndConditions(): Response
    {
        return $this->renderPolicyPage('Terms');
    }

    public function shippingPolicy(): Response
    {
        return $this->renderPolicyPage('Shipping');
    }

    public function refundPolicy(): Response
    {
        return $this->renderPolicyPage('Refund');
    }

    private function renderPolicyPage(string $component): Response
    {
        $categories = Category::limit(6)->get();
        $categories = transformDataForVue($categories);

        $country = Country::id();
        $language = 'ar';
        $settings = Setting::where('country', $country)->where('language', $language)->pluck('value', 'name')->toArray();

        return Inertia::render($component, [
            'categories' => $categories,
            'phone' => $settings['phone'] ?? '',
            'email' => $settings['email'] ?? '',
            'facebook' => $settings['facebook'] ?? '',
            'instagram' => $settings['instagram'] ?? '',
            'tiktok' => $settings['tiktok'] ?? '',
            'whatsapp' => $settings['whatsapp'] ?? '',
            'address' => $settings['address'] ?? '',
        ]);
    }
}

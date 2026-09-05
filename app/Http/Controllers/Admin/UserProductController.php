<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserProductRequest;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\User;
use App\Models\UserProduct;
use App\Models\UserProductLog;
use App\Repositories\UserProductRepository;
use App\Services\CurrencyService;
use App\Services\InventoryTransferService;
use App\Support\Country;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class UserProductController extends Controller

{

    private $productRepository;
    private $inventoryTransfer;

    public function __construct(
        UserProductRepository $productRepository,
        InventoryTransferService $inventoryTransfer
    )
    {
        $this->productRepository = $productRepository;
        $this->inventoryTransfer = $inventoryTransfer;
    }

    public function all(Request $request)
    {
        $request->validate([
            'direction' => ['in:asc,desc', 'nullable'],
            'field' => ['in:stock,id,color_id', 'nullable']
        ]);

        $products = $this->productRepository->getUserProductsAll($request, true);

        $users = $this->inventoryTransfer->destinationsFor(auth()->user());
        $merchants = User::query()->where('role_id', User::ROLE_MERCHANT)->where('country_id',auth()->user()->country_id)->get();

        if ($request->wantsJson()){
            return $products;
        }

        return Inertia::render('Admin/UserProducts/All', [
            'products' => $products,
            'users' => $users,
            'merchants' => $merchants,
            'filters' => $request->all(['search', 'field', 'direction']),
            'currency' => $this->currencyData(),
        ]);
    }


    // public function all(Request $request)
    // {

    //     $request->validate([
    //         'direction' => ['in:asc,desc', 'nullable'],
    //         'field' => ['in:stock,id,color_id', 'nullable']
    //     ]);

    //     if (auth()->user()->role_id === User::ROLE_ADMIN)
    //         $allProducts = UserProduct::query()->where('country_id',auth()->user()->country_id)->whereNotNull('size')->sum('stock');
    //     else
    //         $allProducts = UserProduct::query()->where('country_id',auth()->user()->country_id)->where('user_id', auth()->id())->whereNotNull('size')->sum('stock');


    //     $products   = $this->productRepository->getUserProducts($request,true);
    //     $nproducts  = array();
    //     $color_products  = array();
    //     $sizes  = array();
    //     foreach ($products as $product) {
    //         if(!array_key_exists($product->product_color_id,$nproducts))
    //             $nproducts[$product->product_color_id] = array();
    //         array_push($nproducts[$product->product_color_id],$product);
    //     }


    //     foreach ($nproducts as $key => $color) {
    //         $product_color  = ProductColor::find($key);
    //         $sub_sizes = array();
    //         foreach ($color as $sub_product) {
    //             array_push($sub_sizes,$sub_product->size.'     '.$sub_product->stock);
    //         }
    //         array_push($color_products,['product' => $product_color, 'colors' => $color, 'available_sizes' => implode(' || ', $sub_sizes)]);
    //     }

    //     if (auth()->user()->role_id == User::ROLE_WAREHOUSE || auth()->user()->role_id == User::ROLE_ADMIN){
    //         $users = User::query()->whereIn('role_id', [User::ROLE_SHOP, User::ROLE_WAREHOUSE])->get();
    //     }else{
    //         $users = User::query()->where('role_id', User::ROLE_SHOP)->get();
    //     }
    //     $merchants = User::query()->where('role_id', User::ROLE_MERCHANT)->get();

    //     if ($request->wantsJson()){
    //         return paginate($color_products,10,null,['path' => 'userProducts']);
    //         // return ['data' => paginate($color_products,5,null,['path' => 'userProducts'])];
    //     }
    //     $color_products = paginate($color_products,10,null,['path' => 'userProducts']);


    //     if(auth()->user()->country_id == 2){
    //         $rate = Currency::where('name','aed')->first()->rate;
    //     }else{
    //         $rate = 1;
    //     }

    //     return Inertia::render('Admin/UserProducts/All', [
    //         'products' => $color_products,
    //         'users' => $users,
    //         'rate' => $rate,
    //         'merchants' => $merchants,
    //         'stock_products' => $allProducts,
    //         'filters' => $request->all(['search', 'field', 'direction'])
    //     ]);
    // }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            // Skip validation for JSON requests (AJAX calls)
        } else {
            $request->validate([
                'direction' => ['in:asc,desc', 'nullable'],
                'field' => ['in:stock,id,color_id', 'nullable']
            ]);
        }

        $products   = $this->productRepository->getUserProducts($request);
        $nproducts  = array();
        $color_products  = array();
        foreach ($products as $product) {
            $groupKey = implode(':', [
                $product->user_id,
                $product->product_color_id,
                $product->wholesale_price,
                $product->retail_price,
                $product->price_before_discount,
                $product->merchant_id,
            ]);
            if(!array_key_exists($groupKey,$nproducts))
                $nproducts[$groupKey] = array();
            array_push($nproducts[$groupKey],$product);
        }

        $allProductsCount = 0;
        $totalWholesalePrice = 0;
        $totalSalePrice = 0;
        $totalRetailPrice = 0;

        foreach ($nproducts as $color) {
            $product_color  = ProductColor::query()->with('product')->find($color[0]->product_color_id);
            $sub_sizes = array();
            $total_qty = 0;
            $group_wholesale_price = 0;
            $group_retail_price = 0;
            foreach ($color as $sub_product) {
                array_push($sub_sizes,$sub_product->size.'     '.$sub_product->stock);
                $total_qty = $total_qty + $sub_product->stock;
                $allProductsCount = $allProductsCount + $sub_product->stock;
                $group_wholesale_price = $sub_product->wholesale_price;
                $group_retail_price = $sub_product->retail_price;
            }
            $totalWholesalePrice += $group_wholesale_price * $total_qty;
            $totalRetailPrice += $group_retail_price * $total_qty;
            $totalSalePrice += ($product_color->product->sale_price ?? 0) * $total_qty;
            array_push($color_products,['product' => $product_color, 'colors' => $color, 'qty' => $total_qty, 'available_sizes' => implode(' || ', $sub_sizes)]);
        }

        $users = $this->inventoryTransfer->destinationsFor(auth()->user());
        $merchants = User::query()->where('role_id', User::ROLE_MERCHANT)->where('country_id', auth()->user()->country_id)->get();

        if ($request->wantsJson()){
            $rate = app(\App\Services\CurrencyService::class)->rate(\App\Support\Country::defaultCurrency(auth()->user()->country_id));
            $paginator = paginate($color_products,10,null,['path' => 'userProducts','stock_products' => $allProductsCount]);
            $paginator->appends([
                'totals' => [
                    'stock_products' => $allProductsCount,
                    'total_wholesale_price' => $totalWholesalePrice * $rate,
                    'total_sale_price' => $totalSalePrice * $rate,
                    'total_retail_price' => $totalRetailPrice * $rate,
                ]
            ]);
            return response()->json([
                'data' => $paginator->items(),
                'current_page' => $paginator->currentPage(),
                'first_page_url' => $paginator->url(1),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'last_page_url' => $paginator->url($paginator->lastPage()),
                'links' => $paginator->linkCollection()->toArray(),
                'next_page_url' => $paginator->nextPageUrl(),
                'path' => $paginator->path(),
                'per_page' => $paginator->perPage(),
                'prev_page_url' => $paginator->previousPageUrl(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
                'totals' => [
                    'stock_products' => $allProductsCount,
                    'total_wholesale_price' => $totalWholesalePrice * $rate,
                    'total_sale_price' => $totalSalePrice * $rate,
                    'total_retail_price' => $totalRetailPrice * $rate,
                ]
            ]);
        }
        $color_products = paginate($color_products,10,null,['path' => 'userProducts','stock_products' => $allProductsCount]);

        $rate = app(\App\Services\CurrencyService::class)->rate(\App\Support\Country::defaultCurrency(auth()->user()->country_id));

//        dd($color_products);

        return Inertia::render('Admin/UserProducts/Index', [
            'products' => $color_products,
            'users' => $users,
            'shops' => $users,
            'rate' => $rate,
            'merchants' => $merchants,
            'stock_products' => $allProductsCount,
            'total_wholesale_price' => $totalWholesalePrice * $rate,
            'total_sale_price' => $totalSalePrice * $rate,
            'total_retail_price' => $totalRetailPrice * $rate,
            'filters' => $request->all(['search', 'field', 'direction', 'shop']),
            'currency' => $this->currencyData(),
        ]);
    }

    public function create(): Response
    {
        $users = $this->inventoryTransfer->destinationsFor(auth()->user());
        if (auth()->user()->role_id === User::ROLE_ADMIN){
            $products = ProductColor::query()->where('country_id',auth()->user()->country_id)->get();
        } else{
            $products = UserProduct::query()
                ->select([
                    'user_products.stock',
                    'user_products.wholesale_price',
                    'user_products.retail_price',
                    'user_products.price_before_discount',
                    'user_products.size',
                    'user_products.barcode',
                    'user_products.id',
                    'user_products.product_color_id',
                    'products.name',
                    DB::raw("CONCAT(products.name,' (',colors.name, ' )', ' (',user_products.size, ' - ',user_products.barcode, ' )') as product_name")
                ])
                ->join('product_colors', 'product_color_id', '=', 'product_colors.id')
                ->join('products', 'product_colors.product_id', '=', 'products.id')
                ->join('colors', 'product_colors.color_id', '=', 'colors.id')
                ->where('user_products.user_id', auth()->id())
                ->where('product_colors.country_id',auth()->user()->country_id)
                ->where('user_products.stock', '>', 0)
                ->get();
        }
        $new_sizes = getNewSizesVariables();
        return Inertia::render('Admin/UserProducts/Create', [
            'users' => $users,
            'products' => $products,
            'sizes' => $new_sizes,
            'currency' => $this->currencyData(),
        ]);
    }

    public function store(UserProductRequest $request)
    {
        $result = $this->inventoryTransfer->transfer(auth()->user(), $request->validated());

        return response()->json(array_merge($result, [
            'success' => true,
            'msg' => 'تم إرسال البضاعة وحفظ الأسعار بنجاح.',
        ]));
    }

    public function update(Request $request, UserProduct $userProduct)
    {
        $userProduct->update([
            'price_before_discount' => $request->get('price_before_discount')
        ]);

        return response()->json([
            'success' => true,
            'msg' => 'تم تحديث السعر بنجاح'
        ]);
    }

    public function destroy(UserProduct $userProduct)
    {
        $userProduct->delete();
        return Redirect::route('userProducts.index');
    }

    public function show(UserProduct $userProduct)
    {
        $log = $userProduct->log;
        // $product = $userProduct->productColor;
        // $size = $userProduct->size;
        // if(!is_null($product->sizes) && is_null($size)){
        //     $sizes = json_decode($product->sizes,true);
        //     if(!is_null($sizes)){
        //         if(count($sizes) > 0){
        //             $available_sizes = array_column($sizes, 'size');
        //             $size_exist = in_array($size,$available_sizes);
        //             if($size_exist){
        //                 $index = array_search($size, $available_sizes);
        //                 $product->barcode = $sizes[$index]['barcode'];
        //                 $product->raw_sizes = $sizes[$index]['size'];
        //             }
        //         }
        //     }
        // }
        $productColor = transformItemForVue($userProduct->productColor, ProductColor::class);
        $product = transformItemForVue($userProduct->productColor->product, Product::class);
        $user = transformItemForVue($userProduct->user, User::class);
        $userProduct = transformItemForVue($userProduct, UserProduct::class);

        return Inertia::render('Admin/UserProducts/Show', [
            'log' => $log,
            'productColor' => $productColor,
            'product' => $product,
            'userProduct' => $userProduct,
            'user' => $user
        ]);
    }

    public function match(Request $request)
    {
        $validated = $request->validate([
            'user' => ['required', 'integer', 'exists:users,id'],
            'product' => ['required', 'integer', 'exists:product_colors,id'],
        ]);

        $countryId = (int) auth()->user()->country_id;
        $destinationExists = User::query()
            ->whereKey($validated['user'])
            ->where('country_id', $countryId)
            ->exists();

        if (!$destinationExists) {
            return response()->json([
                'message' => 'The selected shop is not available in this country.',
            ], 422);
        }

        $item = UserProduct::query()
            ->where('country_id', $countryId)
            ->where('user_id', $validated['user'])
            ->where('product_color_id', $validated['product'])
            ->first();

        if ($item)
            return $item;
        return false;
    }

    public function forMerchant(Request $request)
    {
        $for_group = $request->has('group');
        $userId = $request->get('user_id');
        $merchantId = $request->get('merchant_id');
        if($for_group){
            return UserProduct::query()
                ->where('user_id', $userId)
                ->where('merchant_id', $merchantId)
                ->where('barcode', 'LIKE','%'.$request->get('barcode').'%')
                ->get()->map(function ($product){
                    // $product->product_name = $product->productColor->name_ar. " (العدد $product->stock)" ;
                    $product->product_name = $product->productColor->product_name_without_barcode. ' - ' .$product->barcode. " (العدد $product->stock)" . " (الحجم $product->size)" ;
                    return $product;
                });
            }else{
            return UserProduct::query()
                ->where('user_id', $userId)
                ->where('merchant_id', $merchantId)
                ->get()->map(function ($product){
                    // $product->product_name = $product->productColor->name_ar. " (العدد $product->stock)" ;
                    $product->product_name = $product->productColor->product_name_without_barcode. ' - ' .$product->barcode. " (العدد $product->stock)" . " (الحجم $product->size)" ;
                    return $product;
                });
        }

    }

    public function showNotification(Request $request)
    {
        $table = $request->get('table');
        $notificationId = $request->get('notification');

        $user = auth()->user();
        $notification = $user->unreadNotifications()->whereId($notificationId);

        $notification->update(['read_at' => Carbon::now()]);

        $productColor = $table['product_color'];
        $product = $table['product_color']['product'];
        $log = UserProductLog::query()->where('user_product_id', $table['id'])->orderByDesc('id')->first();

        $data = [
            'userProduct' => $table,
            'productColor' => $productColor,
            'product' => $product,
            'log' => $log
        ];

        return Inertia::render('Admin/Notification/Show', $data);
    }

    public function approveNotification($id): RedirectResponse
    {
        $log = UserProductLog::query()->find($id);
        $log->update(['approved' => 1]);
        return Redirect::route('dashboard');
    }

    private function currencyData(): array
    {
        $countryId = (int) auth()->user()->country_id;
        $code = Country::defaultCurrency($countryId);
        $service = app(CurrencyService::class);

        return [
            'code' => $code,
            'rate' => $service->rate($code),
            'decimals' => $code === 'SYP' ? 0 : 2,
            'display' => $service->displayForCountry($countryId),
        ];
    }

}

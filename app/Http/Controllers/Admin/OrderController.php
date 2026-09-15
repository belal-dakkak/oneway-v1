<?php

namespace App\Http\Controllers\Admin;

use App\Classes\Socket;
use App\Http\Controllers\Controller;
use App\Http\Traits\ReceiptTrait;
use App\Models\City;
use App\Models\Order;
use App\Models\WebsiteOrder;
use App\Models\CountryCommerceSetting;
use App\Models\OrderItem;
use App\Models\ProductColor;
use App\Models\ProductSize;
use App\Models\Setting;
use App\Models\ShippingDetails;
use App\Models\User;
use App\Models\UserProduct;
use App\Repositories\OrderRepository;
use App\Services\ClientAccountService;
use App\Services\CashboxService;
use App\Services\CurrencyService;
use App\Services\InvoiceDataService;
use App\Services\SalesCurrencyPolicy;
use App\Services\Payment\WebsiteOrderStockService;
use App\Support\Country;
use App\Mail\OrderStatusChangeEmail;
use Carbon\Carbon;
use PDF;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Jenssegers\Date\Date;
use Psr\Log\LogLevel;

class OrderController extends Controller
{
    use ReceiptTrait;

    private $orderRepository;
    private $currencyService;
    private $invoiceDataService;
    private $salesCurrencyPolicy;
    private $clientAccounts;
    private $cashboxes;
    private $websiteStock;

    public function __construct(
        OrderRepository $orderRepository,
        CurrencyService $currencyService,
        InvoiceDataService $invoiceDataService,
        SalesCurrencyPolicy $salesCurrencyPolicy,
        ClientAccountService $clientAccounts,
        CashboxService $cashboxes,
        WebsiteOrderStockService $websiteStock
    )
    {
        $this->orderRepository = $orderRepository;
        $this->currencyService = $currencyService;
        $this->invoiceDataService = $invoiceDataService;
        $this->salesCurrencyPolicy = $salesCurrencyPolicy;
        $this->clientAccounts = $clientAccounts;
        $this->cashboxes = $cashboxes;
        $this->websiteStock = $websiteStock;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'direction' => ['in:asc,desc', 'nullable'],
            'field' => ['in:type,id,total_price,remain_price,paid_price', 'nullable']
        ]);

        $orders = $this->orderRepository->getOrders($request);

        if ($request->wantsJson()){
            return $orders;
        }

        $shops = User::query()->whereIn('role_id', [User::ROLE_SHOP, User::ROLE_WAREHOUSE])->where('country_id',auth()->user()->country_id)->get();
        $shops = transformDataForVue($shops);

        $buyers = User::query()
            ->where('role_id', User::ROLE_CLIENT)
            ->where('country_id',auth()->user()->country_id)
            ->whereHas('orders')
            ->get();

        $buyers = transformDataForVue($buyers);

        return Inertia::render('Admin/Orders/Index', [
            'orders'  => $orders['orders'],
            'total'   => $orders['total'],
            'count'   => $orders['count'],
            'total_price_without_tax'   => $orders['total_price_without_tax'],
            'total_tax_value'   => $orders['total_tax_value'],
            'totals_by_currency' => $orders['totals_by_currency'] ?? [],
            'shops'   => $shops,
            'buyers'  => $buyers,

            'filters' => $request->all(['search', 'field', 'direction', 'shop', 'buyer', 'date', 'start_date', 'end_date'])
        ]);
    }

    public function monthly_orders(Request $request)
    {

        $orders = $this->orderRepository->getMonthlyOrders($request);

        if ($request->wantsJson()){
            return $orders;
        }

        $shops = User::query()->whereIn('role_id', [User::ROLE_SHOP, User::ROLE_WAREHOUSE])->where('country_id',auth()->user()->country_id)->get();
        $shops = transformDataForVue($shops);

        // $newArr = [
        //     'orders'  => $orders,
        //     'total'   => array_sum(array_column($orders,'total_price')),
        //     'count'   => array_sum(array_column($orders,'count')),
        //     'total_price_without_tax'   => array_sum(array_column($orders,'price_without_tax')),
        //     'total_tax_value'   => array_sum(array_column($orders,'tax_value')),
        //     'totalRefunds'   => array_sum(array_column($orders,'total_refund')),
        //     'shops'   => $shops,
        //     'filters' => $request->all(['search', 'field', 'direction'])
        // ];

        $newArr = $orders;

        // dd($newArr);

        return Inertia::render('Admin/Orders/Monthly', $newArr);
    }

    public function createPDF(Request $request)
    {
        ini_set("pcre.backtrack_limit", "10000000");

        $request->validate([
            'direction' => ['in:asc,desc', 'nullable'],
            'field' => ['in:type,id,total_price,remain_price,paid_price', 'nullable']
        ]);

        $is_website_order = $request->get('order_type') === 'website';
        if ($is_website_order) {
            $all_orders = $this->orderRepository->getOrders($request, false, false, ['buyer', 'items'], false);
        } else {
            $all_orders = $this->orderRepository->getOrders_v2($request, false, false, ['seller', 'buyer', 'items.product.productColor', 'items.refunds'], false);
        }


		// dd($all_orders);

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $startDate = $startDate ? Carbon::parse($startDate): false;
        $endDate = $endDate ? Carbon::parse($endDate): false;

        $language  = 'en';
        $country = auth()->user()->country_id;
        $settings = Setting::where('country',$country)->where('language',$language)->pluck('value','name')->toArray();

        if ($sellerId = $request->get('shop')) {
            $seller = User::find($sellerId);
        } else {
            $seller = null;
        }

        //return view('includes.orders_template',array('seller'=>$seller,'orders'=>$orders,'all_orders'=>$all_orders,'settings'=>$settings,'startDate'=>$startDate,'endDate'=>$endDate));

        // // dd('ok',$orders,$all_orders,$all_orders['orders']->toArray());

        $pdf = PDF::loadView('includes.orders_template',array('seller'=>$seller,'all_orders'=>$all_orders,'settings'=>$settings,'startDate'=>$startDate,'endDate'=>$endDate,'is_website_order'=>$is_website_order));
        return $pdf->download('Orders '.config('app.name').' Date '.now()->format('Y_m_d').'.pdf');
    }

    public function createPDF2(Request $request)
    {


        $report = $this->orderRepository->getMonthlyOrders($request);

       // dd($newArr);

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $startDate = $startDate ? Carbon::parse($startDate): false;
        $endDate = $endDate ? Carbon::parse($endDate): false;

        $language  = 'en';
        $country = auth()->user()->country_id;
        $settings = Setting::where('country',$country)->where('language',$language)->pluck('value','name')->toArray();

        if ($sellerId = $request->get('shop')) {
            $seller = User::find($sellerId);
        } else {
            $seller = null;
        }

        //return view('includes.monthly_orders_template',array('seller'=>$seller,'orders'=>$orders,'all_orders'=>$newArr['orders'],'settings'=>$settings,'startDate'=>$startDate,'endDate'=>$endDate));

        // // dd('ok',$orders,$all_orders,$all_orders['orders']->toArray());

        $pdf = PDF::loadView('includes.monthly_orders_template', [
            'seller' => $seller,
            'orders' => $report['orders'],
            'all_orders' => $report,
            'settings' => $settings,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
        return $pdf->download('Orders '.config('app.name').' Date '.now()->format('Y_m_d').'.pdf');
    }

    public function edit(Order $order)
    {
        abort_unless((int) optional($order->seller)->country_id === (int) auth()->user()->country_id, 404);
        $items = [];

        $rate = (float) ($order->curr_rate ?: 1);

        foreach ($order->items as $item){
            $product               = [];
            $product['barcode']    = $item->product->productColor->barcode;
            $product['qty']        = $item->qty;
            $product['qty_limit']  = $item->product->stock;
            $enteredPrice = $order->order_type === 'complex_from_multi'
                ? $item->price_without_tax
                : $item->item_price;
            $product['price'] = currencyExchange($enteredPrice, $rate);
            $product['product_id'] = $item->user_product_id;
            $product['name']       = $item->product->productColor->product_name." - ".$item->product->size;
            $product['image']      = $item->product->productColor->photo_url;

            $items[] = $product;
        }

        $emptyProduct = [];
        $emptyProduct['barcode'] = '';
        $emptyProduct['qty'] = '';
        $emptyProduct['qty_limit'] = '';
        $emptyProduct['price'] = '';
        $emptyProduct['product_id'] = '';
        $emptyProduct['name'] = '';
        $emptyProduct['image'] = '';

        $items[] = $emptyProduct;

        $totalCount = $order->items()->sum('qty');
        if ($order->type == Order::TYPE_CASH)
            return Inertia::render('Admin/Orders/EditOrderSimpleForm', [
                'order' => $order,
                'items' => $items,
                'total_qty' => $totalCount,
                'tax_ratio'    => $order->tax_ratio,
                'enable_tax'   => $order->tax_ratio > 0 ? 'yes' : 'no',
            ]);
        else{
            $users = User::query()->where('role_id', User::ROLE_CLIENT)->where('country_id',auth()->user()->country_id)->get();
            $shippers = User::query()->where('role_id', User::ROLE_SHIPPER)->where('country_id',auth()->user()->country_id)->get();

            return Inertia::render('Admin/Orders/EditOrderComplexForm', [
                'order' => $order,
                'items' => $items,
                'total_qty' => $totalCount,
                'users' => $users,
                'shippers' => $shippers,
                'order_buyer' => $order->buyer,
                'order_shipper' => $order->shipper,
                'tax_ratio'    => $order->tax_ratio,
                'enable_tax'   => $order->tax_ratio > 0 ? 'yes' : 'no',
            ]);
        }
    }

    public function update(Order $order, Request $request)
    {
        abort_unless((int) optional($order->seller)->country_id === (int) auth()->user()->country_id, 404);
        $request->validate([
            'selected_products' => 'required|array|min:1',
            'selected_products.*.product_id' => 'required|integer|exists:user_products,id',
            'selected_products.*.qty' => 'required|numeric|min:1',
            'selected_products.*.price' => 'required|numeric|min:0',
        ]);

        $request->merge([
            'order_type' => $order->order_type ?: ($order->type === Order::TYPE_CASH ? 'simple' : 'complex'),
            'currency' => [
                'name' => strtoupper($order->curr_type ?: 'USD'),
                'label' => strtoupper($order->curr_type ?: 'USD'),
                'value' => strtolower($order->curr_type ?: 'USD'),
                'code' => strtoupper($order->curr_type ?: 'USD'),
                'rate' => (float) ($order->curr_rate ?: 1),
            ],
        ]);

        $result = $this->orderRepository->update($request, $order);

        if (!$result){
            $request->session()->flash('error', 'لقد حدث خطأ ما أثناء التعديل');
            return Redirect::back();
        }

        if ($request->get('type') == Order::TYPE_CASH)
            return Redirect::route('orders.simple');
        return Redirect::route('orders.complex');
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order, Request $request)
    {
        abort_unless((int) optional($order->seller)->country_id === (int) auth()->user()->country_id, 404);

        // return redirect('invoice/print-v2/'.$order->id);

    	[
            'order' => $order,
            'items' => $items
        ] = $this->getExportData($order->id);

        $rate = (float) ($order->curr_rate ?: 1);
        $Currency = strtoupper($order->curr_type ?: Country::defaultCurrency($order->seller->country_id ?? Country::UAE));

        $o = newStd();
        $o->barcode                     = $order->barcode;
        $o->id                          = $order->id;
        $o->total_price                 = $order->total_price . ' ' .$Currency;
        $o->total_price_before_discount = $order->total_price_before_discount . ' ' .$Currency;
        $o->discount                    = $order->discount;
        $o->paid_price                  = $order->paid_price . ' ' .$Currency;
        $o->remain_price                = $order->remain_price . ' ' .$Currency;
        $o->buyer_name                  = $order->buyer?$order->buyer->name:'';
        $o->shipper_name                = $order->shipper?$order->shipper->name:'';
        $o->seller_name                 = $order->seller?$order->seller->name:'';
		$o->payment_label				= $order->payment_label ?? '';

        $o->tax_receipt                 = $order->tax_ratio > 0 ? true : false;
        $o->total_price_before_vat      = $order->price_without_tax . ' ' .$Currency;

        $o->tax                         = $order->tax_value . ' ' .$Currency;
        $o->tax_rate                    = $order->tax_ratio.'%';

        $o->customer_trn                = $order->trn ?? '';
        $o->trn                         = @$order->seller->trn ?? '';

        Date::setLocale('ar');
        $o->order_date = Date::parse($order->created_at)->timezone('Asia/Beirut')->format('d-m-Y');
        $o->order_time = Date::parse($order->created_at)->timezone('Asia/Beirut')->format('h:i a');

        $o->total_count       = $order->items()->sum('qty');
        $o->total_model_count = $order->items()->count();

        $o->products = $items;

        foreach($o->products as $product) {
            $product->vat = $product->line_tax_value . ' ' . $Currency;
            $product->total_price_before_vat = $product->line_price_without_tax . ' ' .$Currency;
            $product->total_price = $product->total_price . ' ' .$Currency;
        }

/*         if ($order->type == Order::TYPE_APP){ */
/*             $o->total_count = $order->productItems()->sum('qty'); */
/*             $o->total_model_count = $order->productItems()->count(); */

/*             $o->products = $order->productItems->map(function ($item){ */
/*                 return newStd([ */
/*                     'qty'         => $item->qty, */
/*                     'item_price'  => round($item->item_price * $item->order->curr_rate), */
/*                     'total_price' => round($item->total_price * $item->order->curr_rate), */
/*                     'name'        => $item->product->simple_name */
/*                 ]); */
/*             }); */
/*         }else{ */
/*             $o->total_count       = $order->items()->sum('qty'); */
/*             $o->total_model_count = $order->items()->count(); */

/*             $o->products = $order->items->map(function ($item){ */
/*                 return newStd([ */
/*                     'qty'         => $item->qty, */
/*                     'item_price'  => round($item->item_price * $item->order->curr_rate), */
/*                     'total_price' => round($item->total_price * $item->order->curr_rate), */
/*                     'name'        => $item->product->productColor->product->name . $item->product->productColor->color_name */
/*                 ]); */
/*             }); */
/*         } */

        $data = $o;

        return response()->json($data);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Orders/Create');
    }

    public function simpleCreate()
    {
        $allProducts = UserProduct::query()->where('user_id', auth()->id())->sum('stock');
        $country = auth()->user()->country_id;
        try {
            $currency = $this->salesCurrencyPolicy->orderOption($country, 'simple');
        } catch (\InvalidArgumentException $exception) {
            return Redirect::route('dashboard')->with('error', 'يجب ضبط سعر صرف عملة المبيعات قبل إنشاء الطلبية.');
        }
        $currencies = [$currency];
        $rate = $currency['rate'];

        return Inertia::render('Admin/Orders/CreateOrderSimpleForm', [
            'currencies'   => $currencies,
            'all_products' => $allProducts,
            'rate'         => $rate,
            'order_type'   => 'simple',
            'tax_ratio'    => auth()->user()->tax_ratio,
            'enable_tax'   => auth()->user()->enable_tax
        ]);
    }

    public function complexCreate()
    {
        $users = User::query()->where('role_id', User::ROLE_CLIENT)->where('country_id',auth()->user()->country_id)->get();
        $shippers = User::query()->where('role_id', User::ROLE_SHIPPER)->where('country_id',auth()->user()->country_id)->get();
        $allProducts = UserProduct::query()->where('user_id', auth()->id())->sum('stock');
        $country = auth()->user()->country_id;
        try {
            $currency = $this->salesCurrencyPolicy->orderOption($country, 'complex');
        } catch (\InvalidArgumentException $exception) {
            return Redirect::route('dashboard')->with('error', 'يجب ضبط سعر صرف عملة المبيعات قبل إنشاء الطلبية.');
        }
        $currencies = [$currency];
        $rate = $currency['rate'];

        return Inertia::render('Admin/Orders/CreateOrderComplexForm', [
            'users'        => $users,
            'shippers'     => $shippers,
            'all_products' => $allProducts,
            'currencies'   => $currencies,
            'rate'         => $rate,
            'order_type'   => 'complex',
            'tax_ratio'    => auth()->user()->tax_ratio,
            'enable_tax'   => auth()->user()->enable_tax
        ]);
    }

    public function multiCreate(): Response
    {
        $users = User::query()->where('role_id', User::ROLE_CLIENT)->where('country_id',auth()->user()->country_id)->get();
        $shippers = User::query()->where('role_id', User::ROLE_SHIPPER)->where('country_id',auth()->user()->country_id)->get();
        $allProducts = UserProduct::query()->where('user_id', auth()->id())->sum('stock');
        $country = auth()->user()->country_id;
        $currency = $this->salesCurrencyPolicy->orderOption($country, 'complex_from_multi');
        $currencies = [$currency];
        $rate = $currency['rate'];

        return Inertia::render('Admin/Orders/CreateOrderComplexFormMulti', [
            'users'        => $users,
            'shippers'     => $shippers,
            'all_products' => $allProducts,
            'currencies'   => $currencies,
            'rate'         => $rate,
            'order_type'   => 'complex_form_multi',
            'tax_ratio'    => auth()->user()->tax_ratio,
            'enable_tax'   => auth()->user()->enable_tax
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'order_type' => 'required|in:simple,complex,complex_from_multi',
            'selected_products' => 'required|array|min:1',
            'selected_products.*.product_id' => 'required|integer|exists:user_products,id',
            'selected_products.*.qty' => 'required|numeric|min:1',
            'selected_products.*.price' => 'required|numeric|min:0',
            'currency.value' => 'nullable|string',
        ]);

        $requestedCurrency = $request->input('currency.code', $request->input('currency.value'));
        try {
            $currency = $this->salesCurrencyPolicy->orderOption(
                (int) auth()->user()->country_id,
                $request->order_type,
                $requestedCurrency
            );
        } catch (\InvalidArgumentException $exception) {
            return Redirect::back()->withErrors([
                'currency' => 'يجب ضبط سعر صرف عملة الطلب قبل حفظ الفاتورة.',
            ]);
        }
        $request->merge(['currency' => $currency]);

        //DB::beginTransaction();

        try {

            $result = $this->orderRepository->add($request);

            if (!$result)
                $request->session()->flash('error', 'لقد حدث خطأ ما أثناء الإضافة');

            if ($result && $request->get('action_status') > 1){

                if ($request->get('action_status') == 2 && $result->buyer){

                    $number = $result->buyer->phone;

                    Log::log(LogLevel::INFO, 'single_print');

                    //DB::commit();

                    return Inertia::render('WA2', [
                        'id' => $result->id,
                        'number' => $number,
                        'invoice_url' => $result->invoice_links['download'],
                    ]);
                }

                /*else if($request->get('action_status') == 3){
                    $request->request->add(['order' => $result->id]);

                }*/
                else if($request->get('action_status') == 4){

                    //DB::commit();

                    return Inertia::render('RedirectInvoice', [
                        'id' => $result->id,
                        'invoice_url' => $result->invoice_links['download'],
                    ]);
                }
            }

            if ($request->get('type') == Order::TYPE_CASH)
                //return redirect()->back();
                //DB::commit();
                return Redirect::route('orders.simple', [ 'order_id' => $result->id ]);

            //DB::commit();
            return Redirect::route('orders.complex', [ 'order_id' => $result->id ]);

        } catch (Exception $e) {
            //DB::rollBack();
            return redirect()->back()->with('error','لقد حدث خطأ ما برجاء المحاولة مره اخري');
        }


    }

    public function match(Request $request)
    {
        $userId = auth()->id();
        $productId = $request->get('product');

        $item = UserProduct::query()
            ->with(['productColor'])
            ->where('user_id', $userId)
            // ->whereHas('productColor', function ($query) use ($productId){
            //     $query->where('barcode', $productId);
            // })
            ->where('barcode', $productId)
            ->where('stock', '>', 0)
            ->first();

        if ($item)
            return $item;
        return false;
    }

    public function multiMatch(Request $request)
    {
        $userId = auth()->id();
        $productId = $request->get('product');
        if(strlen($productId) < 8) return false;
        $items = UserProduct::query()
            ->with(['productColor'])
            ->where('user_id', $userId)
            ->where('barcode','LIKE', '%'.$productId."%")
            ->where('stock', '>', 0)
            ->get();

        if ($items)
            return $items;
        return false;
    }

    public function addPayment(Request $request)
    {
        try {
            $request->validate([
                'order' => 'required|integer',
                'amount' => 'required|numeric|min:0.0001',
            ]);
            $order = Order::query()
                ->whereHas('seller', function ($query) {
                    $query->where('country_id', auth()->user()->country_id);
                })
                ->findOrFail($request->get('order'));
            $this->clientAccounts->payOrder($order, (float) $request->get('amount'));
        }catch (Exception $exception){
            return response()->json([
                'error' => $exception->getMessage()
            ], 422);
        }

        return response()->json([
            'success' => true
        ]);
    }

    public function websiteOrders(Request $request)
    {
        $request->merge(['order_type' => 'website']);

        $orders = $this->orderRepository->getOrders($request);

        // Mark all website-order notifications as read when the page is opened
        $this->markWebsiteOrderNotificationsRead();

        if ($request->wantsJson()) {
            return $orders;
        }

        $shops = User::query()->whereIn('role_id', [User::ROLE_SHOP, User::ROLE_WAREHOUSE])->where('country_id', auth()->user()->country_id)->get();
        $shops = transformDataForVue($shops);

        $buyers = User::query()
            ->where('role_id', User::ROLE_CLIENT)
            ->where('country_id', auth()->user()->country_id)
            ->whereHas('orders')
            ->get();
        $buyers = transformDataForVue($buyers);

        return Inertia::render('Admin/Orders/WebsiteOrders', [
            'orders'  => $orders['orders'],
            'total'   => $orders['total'],
            'count'   => $orders['count'],
            'total_price_without_tax'   => $orders['total_price_without_tax'],
            'total_tax_value'   => $orders['total_tax_value'],
            'totals_by_currency' => $orders['totals_by_currency'] ?? [],
            'shops'   => $shops,
            'buyers'  => $buyers,
            'filters' => $request->all(['search', 'field', 'direction', 'start_date', 'end_date', 'date', 'buyer'])
        ]);
    }

    /**
     * Mark all unread website-order notifications as read for the current user.
     * Website order notifications have 'email' and 'phone' fields in their data.table.
     */
    public function clearWebsiteOrderNotifications(Request $request): JsonResponse
    {
        $this->markWebsiteOrderNotificationsRead();
        return response()->json(['success' => true]);
    }

    private function markWebsiteOrderNotificationsRead(): void
    {
        $user = auth()->user();
        if (!$user) return;

        // Mark all website order notifications as read for ALL admin/shop/warehouse users
        User::whereIn('role_id', [User::ROLE_ADMIN, User::ROLE_WAREHOUSE, User::ROLE_SHOP])
            ->get()
            ->each(function ($adminUser) {
                $adminUser->unreadNotifications()
                    ->whereNotNull('data->table->email')
                    ->whereNotNull('data->table->phone')
                    ->update([
                        'read_at' => now(),
                    ]);
            });
    }

    public function invoice($id)
    {
        return $this->typedInvoice(InvoiceDataService::SOURCE_ORDER, (int) $id);
    }

    public function appInvoice($id)
    {
        $order = WebsiteOrder::query()->where('country_id', auth()->user()->country_id)->find($id);
        if (!$order) {
            $order = Order::query()
                ->whereHas('seller', function ($query) {
                    $query->where('country_id', auth()->user()->country_id);
                })
                ->find($id);
        }

        $shippingDetails = $order->shippingDetails ?? $order; // WebsiteOrder has shipping fields direct
        $items = $order instanceof WebsiteOrder ? $order->items() : $order->productItems();
        $buyer = transformItemForVue($order->buyer, User::class);
        $order = transformItemForVue($order, $order instanceof WebsiteOrder ? WebsiteOrder::class : Order::class);

        if ($order instanceof Order && $shippingDetails && isset($shippingDetails->city) && $shippingDetails->city)
            $city = transformItemForVue($shippingDetails->city, City::class);
        else
            $city = null;

        if ($order instanceof Order) {
            $shippingDetails = transformItemForVue($shippingDetails, ShippingDetails::class);
        } else {
            // For WebsiteOrder, shipping details are flat in the model
            $shippingDetails = $order;
        }

        $data = [];
        foreach ($items->get() as $item){
            $product = $item->product;
            $productSize = ProductSize::query()
                ->where('product_color_id', $product->id)
                ->where('product_id', $product->product_id)
                ->first();

            $data[] = (object)[
                'name' => $product->simple_name,
                'qty' => $item->qty,
                'item_price' => $item->item_price,
                'total_price' => $item->total_price,
                'barcode' => $productSize ? $productSize->barcode : '',
                'size' => $item->size,
            ];
        }

        return Inertia::render('Admin/Orders/View', [
            'order' => $order,
            'buyer' => $buyer,
            'items' => $data,
            'address' => $shippingDetails,
            'city' => $city
        ]);
    }

    public function invoiceShipper($id)
    {
        $order = $this->invoiceDataService->resolve(InvoiceDataService::SOURCE_ORDER, (int) $id);
        $this->authorizeInvoiceAccess($order);
        $data = $this->invoiceDataService->forOrder($order);

        return view('receipts.pdfReceiptShipper', $data);
    }

    public function changeStatus(Request $request): JsonResponse
    {
        Order::query()
            ->where('id', $request->get('order'))
            ->update(['status' => DB::raw("status + 1")]);

        return response()->json($request->get('order'));
    }

    public function changeWebsiteOrderStatus(Request $request, $id): JsonResponse
    {
        $request->validate(['status' => 'nullable|integer|in:0,1,2,3,4']);
        $oldStatus = '';
        $order = DB::transaction(function () use ($request, $id, &$oldStatus) {
            $order = WebsiteOrder::query()
                ->when((int) auth()->user()->country_id !== Country::ALL, function ($query) {
                    $query->where('country_id', auth()->user()->country_id);
                })
                ->lockForUpdate()
                ->findOrFail($id);
            $oldStatus = $order->status_label;

            if ($request->has('status')) {
                $order->status = $request->get('status');
            } elseif ($order->status < WebsiteOrder::STATUS_DELIVERED) {
                $order->status = $order->status + 1;
            }

            if ((int) $order->status === WebsiteOrder::STATUS_DELIVERED && $order->payment_type === 'cod') {
                $this->postWebsiteCod($order);
            }
            if ((int) $order->status === WebsiteOrder::STATUS_FAILED && !$order->payment_captured_at) {
                $this->websiteStock->releaseLocked($order);
            }
            $order->save();

            return $order;
        }, 3);

        $newStatus = $order->status_label;

        // Send email notification to client about status change
        try {
            Mail::to($order->email)->queue(new OrderStatusChangeEmail($order, $oldStatus, $newStatus));
            Log::info('Order status change email sent successfully', [
                'order_id' => $order->id,
                'email' => $order->email,
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send order status change email', [
                'order_id' => $order->id,
                'email' => $order->email,
                'error' => $e->getMessage()
            ]);
        }

        return response()->json($order);
    }

    public function markWebsiteOrderPaid(Request $request, $id): JsonResponse
    {
        $order = DB::transaction(function () use ($id) {
            $order = WebsiteOrder::query()
                ->when((int) auth()->user()->country_id !== Country::ALL, function ($query) {
                    $query->where('country_id', auth()->user()->country_id);
                })
                ->lockForUpdate()
                ->findOrFail($id);
            if ($order->payment_type !== 'cod') {
                abort(422, 'Card orders are marked paid only by the payment gateway.');
            }
            $this->postWebsiteCod($order);
            $order->save();

            return $order;
        }, 3);

        return response()->json([
            'id'           => $order->id,
            'paid_price'   => $order->paid_price,
            'remain_price' => $order->remain_price,
            'is_paid'      => $order->is_paid,
        ]);
    }

    private function postWebsiteCod(WebsiteOrder $order): void
    {
        if ($order->cashbox_posted_at) {
            return;
        }

        $commerce = CountryCommerceSetting::forCountry((int) $order->country_id);
        $cashboxUserId = (int) ($commerce->website_cashbox_user_id ?: 0);
        $operator = auth()->user();
        if (!$cashboxUserId && (int) $order->country_id !== Country::SYRIA && $operator) {
            // Preserve the existing Lebanon/UAE collection flow.
            $cashboxUserId = (int) $operator->id;
        }
        if (!$cashboxUserId
            && $operator
            && (int) $operator->country_id === (int) $order->country_id
            && in_array((int) $operator->role_id, [User::ROLE_SHOP, User::ROLE_WAREHOUSE], true)) {
            $cashboxUserId = (int) $operator->id;
        }
        if (!$cashboxUserId) {
            throw ValidationException::withMessages([
                'cashbox' => 'Configure the website receiving cashbox before collecting this COD order.',
            ]);
        }
        $this->cashboxes->credit(
            $cashboxUserId,
            (float) $order->total_price,
            strtoupper((string) ($order->curr_type ?: 'USD')),
            "website-order:{$order->id}:cod-collected",
            [
                'exchange_rate' => (float) ($order->curr_rate ?: 1),
                'payment_method' => 'cod',
                'source_type' => WebsiteOrder::class,
                'source_id' => $order->id,
                'note' => "COD collected for website order #{$order->barcode}",
            ]
        );
        $order->paid_price = $order->total_price;
        $order->remain_price = 0;
        $order->cashbox_posted_at = now();
    }

    //public function singlePrint(Request $request)
	//public function singlePrint($result)
	public function singlePrint(Request $request,$result)
    {

        $order = Order::query()
            ->with(['seller', 'buyer', 'shipper'])
            ->select(['barcode', 'seller_id', 'shipper_id', 'buyer_id', 'id', 'total_price', 'total_price_before_discount', 'discount', 'paid_price', 'remain_price'])
            ->find($result->id);

		if($order != null) {

			$o = newStd();
			$o->barcode                     = $order->barcode;
			$o->id                          = $order->id;
			$o->total_price                 = $order->total_price;
			$o->total_price_before_discount = $order->total_price_before_discount;
			$o->discount                    = $order->discount;
			$o->paid_price                  = $order->paid_price;
			$o->remain_price                = $order->remain_price;
			$o->buyer_name                  = $order->buyer?$order->buyer->name:'';
			$o->shipper_name                = $order->shipper?$order->shipper->name:'';
			$o->seller_name                 = $order->seller->name;

			Date::setLocale('ar');
			$o->order_date = Date::parse($order->created_at)->timezone('Asia/Beirut')->format('d-m-Y');
			$o->order_time = Date::parse($order->created_at)->timezone('Asia/Beirut')->format('h:i a');

			$o->shop_name = 'One Way';
			$o->shop_tel  = 'Tel: +971 545516995, +961 76658734';
			$o->facebook  = 'theoneway.fashion';
			$o->tiktok    = '@oneway503';
			$o->insta     = 'theoneway.fashion';

			if ($order->type == Order::TYPE_APP){
				$o->total_count = $order->productItems()->sum('qty');
				$o->total_model_count = $order->productItems()->count();

				$o->products = $order->productItems->map(function ($item){
					return newStd([
						'qty'         => $item->qty,
						'item_price'  => round($item->item_price * $item->order->curr_rate),
						'total_price' => round($item->total_price * $item->order->curr_rate),
						'name'        => $item->product->simple_name
					]);
				});
			}else{
				$o->total_count       = $order->items()->sum('qty');
				$o->total_model_count = $order->items()->count();

				$o-> products = $order->items->map(function ($item){
					return newStd([
						'qty'         => $item->qty,
						'item_price'  => ceil($item->item_price * $item->order->curr_rate),
						'total_price' => ceil($item->total_price * $item->order->curr_rate),
						'name'        => $item->product->productColor->product->name . $item->product->productColor->color_name
					]);
				});
			}

			$data = [
				'type'    => 'fatora',
				'content' => $o,
				'id'      => auth()->id()
			];
		}

		//dd($request->get('order'));
		//return response()->json($result);
        return response()->json($request->get('order'));
    }

    public function indexToNumberName($index){
        switch ($index){
            case 0:
                return 'slotOne';
            case 1:
                return 'slotTwo';
            case 2:
                return 'slotThree';
            case 3:
                return 'slotFour';
            case 4:
                return 'slotFive';
        }
    }


    //            $startTime = date("H:i", strtotime('-1 minutes', $startTime));
//            $stopTime = date("H:i", strtotime('+1 minutes', $stopTime));


    public function print()
    {
//        return view('receipts.pdfReceipt', $data);
    }

    public function debts(Request $request)
    {
        $request->validate([
            'direction' => ['in:asc,desc', 'nullable'],
            'field' => ['in:type,id,total_price,remain_price,paid_price', 'nullable']
        ]);

        $orders = $this->orderRepository->getOrders($request, true);

        if ($request->wantsJson()){
            return $orders;
        }

        $shops = User::query()->whereIn('role_id', [User::ROLE_SHOP, User::ROLE_WAREHOUSE])->where('country_id',auth()->user()->country_id)->get();
        $shops = transformDataForVue($shops);

        return Inertia::render('Admin/Orders/Debts', [
            'orders' => $orders['orders'],
            'total' => $orders['total'],
            'shops' => $shops,
            'filters' => $request->all(['search', 'buyerName', 'field', 'direction'])
        ]);
    }

    public function profits(Request $request)
    {
        $request->validate([
            'direction' => ['in:asc,desc', 'nullable'],
            'field' => ['in:type,id,total_price,remain_price,paid_price', 'nullable']
        ]);

        $orders = $this->orderRepository->getOrders($request, false, true,null,true,true);

        if ($request->wantsJson()){
            return $orders;
        }

        $shops = User::query()->whereIn('role_id', [User::ROLE_SHOP, User::ROLE_WAREHOUSE])->where('country_id',auth()->user()->country_id)->get();
        $shops = transformDataForVue($shops);

        $buyers = User::query()->where('country_id',auth()->user()->country_id)
            ->where('role_id', User::ROLE_CLIENT)
            ->whereHas('orders')
            ->get();
        $buyers = transformDataForVue($buyers);

        $rate = $this->currencyService->rate(Country::defaultCurrency(auth()->user()->country_id));

        return Inertia::render('Admin/Orders/Profits', [
            'orders'  => $orders['orders'],
            'profit'  => array_key_exists('profit',$orders) ? $orders['profit'] : 0,
            'profits_by_currency' => $orders['profits_by_currency'] ?? [],
            'shops'   => $shops,
            'buyers'  => $buyers,
            'rate'    => $rate,
            'filters' => $request->all(['search', 'field', 'direction'])
        ]);
    }

    public function forClient(Request $request)
    {
        $userId = $request->get('user_id');
        $shopId = $request->get('shop_id');

        $orders = Order::query()
            ->where('seller_id', $shopId)
            ->where('buyer_id', $userId)
            ->pluck('id')
            ->toArray();
        $for_group = $request->has('group');
        $barcode = $request->get('barcode');
        if($for_group){
            return OrderItem::query()
            ->with('product.productColor')
            ->whereIn('order_id', $orders)
            ->whereHas('product', function ($query) use ($barcode){
                $query->where('barcode', 'LIKE','%'.$barcode.'%');
            })->get()->map(function ($item){
                $item->product_name = $item->product->productColor->product_name. " (العدد $item->qty)" . " (الحجم ".$item->product->size.")" ;
                return $item;
            });
        }else{
            return OrderItem::query()
            ->with('product.productColor')
            ->whereIn('order_id', $orders)
            ->get()->map(function ($item){
                $item->product_name = $item->product->productColor->product_name. " (العدد $item->qty)" . " (الحجم ".$item->product->size.")" ;
                return $item;
            });
        }
    }

    public function getItems($id)
    {
        $order = WebsiteOrder::with('items.product')
            ->where('country_id', auth()->user()->country_id)
            ->find($id);
        if (!$order) {
            $order = Order::query()
                ->whereHas('seller', function ($query) {
                    $query->where('country_id', auth()->user()->country_id);
                })
                ->where('id', $id)
                ->with('items.product.productColor')
                ->first();
        }
        return response()->json([$order]);
    }

    public function appOrders(Request $request)
    {
        $request->validate([
            'direction' => ['in:asc,desc', 'nullable'],
            'field' => ['in:type,id,total_price,remain_price,paid_price', 'nullable']
        ]);

        $request->request->add(['type' => Order::TYPE_APP]);

        $orders = $this->orderRepository->getOrders($request, null, null, $with = ['buyer', 'productItems','seller', 'shipper', 'productItems.product']);

        if ($request->wantsJson()){
            return $orders;
        }

        $shops = User::query()->whereIn('role_id', [User::ROLE_SHOP, User::ROLE_WAREHOUSE])->where('country_id',auth()->user()->country_id)->get();
        $shops = transformDataForVue($shops);

        $buyers = User::query()->where('country_id',auth()->user()->country_id)
            ->where('role_id', User::ROLE_CLIENT)
            ->whereHas('orders')
            ->get();

        $buyers = transformDataForVue($buyers);

        return Inertia::render('Admin/Orders/App', [
            'orders' => $orders['orders'],
            'total' => $orders['total'],
            'shops' => $shops,
            'buyers' => $buyers,
            'filters' => $request->all(['search', 'field', 'direction'])
        ]);
    }

    function download_invoice($oid) {

        return $this->downloadInvoiceFor(InvoiceDataService::SOURCE_ORDER, (int) $oid);

        //dd($oid);

        $order = WebsiteOrder::find($oid) ?: Order::findOrFail($oid);

        if($order instanceof Order && $order->seller) {

            if($order->seller->role_id == 3) {
                $user_role = 'shop';
            } else {
                $user_role = 'stock';
            }

        } else {
            $user_role = 'shop';
        }

		$rate = $order instanceof WebsiteOrder ? 1.0 : (float) ($order->curr_rate ?: 1);
		$Currency = strtoupper($order->curr_type ?: Country::defaultCurrency($order->country_id ?? $order->seller->country_id ?? Country::UAE));


		//dd($rate);
		//dd($order->type == Order::TYPE_APP);


        if ($order instanceof WebsiteOrder || $order->type == Order::TYPE_APP){

            $items = $order instanceof WebsiteOrder ? $order->items() : $order->productItems();
            $items = $items->selectRaw('SUM(qty) as qty, MAX(item_price) as item_price, MAX(total_price) as total_price, MAX(price_without_tax) as price_without_tax, MAX(tax_value) as tax_value, product_color_id')->groupBy('product_color_id')->get();
            $products = array();
            foreach ($items as $item) {
                $product = ProductColor::find($item->product_color_id);
                if(!array_key_exists($product->product_id,$products))
                    $products[$product->product_id] = [
                        'name' => $product->product->name." - ".$product->product->barcode,
                        'qty' => $item->qty,
                        'item_price' => $item->item_price * $rate,
                        'total_price' => $item->total_price * $rate,
                        'price_without_tax' => $item->price_without_tax * $rate,
                        'tax_value' => $item->tax_value * $rate,
                    ];
                else
                    $products[$product->product_id]['qty'] += $item->qty;
            }
            $data = [];
            foreach ($products as $item){
                $item = (object) $item;
                $data[] = (object)[
                    'name' => $item->name,
                    'qty' => $item->qty,
                    'item_price' => $item->item_price,
                    'total_price' => $item->total_price,
                    'price_without_tax' => $item->price_without_tax,
                    'tax_value' => $item->tax_value,
                ];
            }
            $items = collect($data);
        }else{

            // $items    = $order->items();
            // $userProductsArr = $order->items()->pluck('user_product_id')->toArray();

            // $user_products = UserProduct::selectRaw('GROUP_CONCAT(id) as ids, product_color_id')->whereIn('id',$userProductsArr)->groupBy('product_color_id')->get();

            // $data = [];

            // $productss = array();

            // foreach ($user_products as $item){

            //     $order_item = Order::query()->find($oid)->items()->selectRaw('SUM(qty) as qty, MAX(item_price) as item_price, MAX(total_price) as total_price, MAX(price_without_tax) as price_without_tax, MAX(tax_value) as tax_value')->whereIn('user_product_id',explode(',',$item->ids))->first();

            //     $product = ProductColor::find($item->product_color_id);

            //     if(!array_key_exists($product->product_id,$productss))
            //         $productss[$product->product_id] = [
            //             'name' => $product->product->name." - ".$product->product->barcode,
            //             'qty' => $order_item->qty,
            //             'item_price' => $order_item->item_price,
            //             'total_price' => $order_item->total_price,
            //             'price_without_tax' => $order_item->price_without_tax,
            //             'tax_value' => $order_item->tax_value,
            //         ];
            //     else
            //         $productss[$product->product_id]['qty'] += $order_item->qty;
            // }

            $items    = $order->items();
            $userProductsArr = $order->items()->pluck('user_product_id')->toArray();

            $product_ids_arr = [];
            $user_products = UserProduct::selectRaw('GROUP_CONCAT(id) as ids, product_color_id')->whereIn('id',$userProductsArr)->groupBy('product_color_id')->get();

            $data = [];

            $productss = array();


            // foreach($items->get() as $index => $order_item) {

            //     if($order_item->user_product != null && $order_item->user_product->productColor != null && $order_item->user_product->productColor->product != null && ! in_array($order_item->user_product->productColor->product->id, array_column($productss, 'product_id'))) {

            //             $product = $order_item->user_product->productColor->product;

            //             $productss[$index] = [
            //                 'product_id' => $product->id,
            //                 'name' => $product->name." - ".$product->barcode,
            //                 'qty' => $order_item->qty,
            //                 'item_price' => $order_item->item_price,
            //                 'total_price' => $order_item->total_price,
            //                 'price_without_tax' => $order_item->price_without_tax,
            //                 'tax_value' => $order_item->tax_value,
            //             ];

            //     } elseif( $order_item->user_product != null && $order_item->user_product->productColor != null && $order_item->user_product->productColor->product != null && in_array($order_item->user_product->productColor->product->id, array_column($productss, 'product_id'))) {

            //         $product = $order_item->user_product->productColor->product;

            //         $i = array_search($order_item->user_product->productColor->product->id, array_column($productss, 'product_id'));
            //         //dd($productss[$i],$order_item->item_price);

            //         if($productss[$i]['item_price'] == $order_item->item_price) {

            //             // dd($i,$productss[$i], $order_item->item_price);

            //             $productss[$i]['qty'] += $order_item->qty;
            //             // $productss[$product->id]['item_price'] += $order_item->item_price;
            //             $productss[$i]['total_price'] += $order_item->total_price;
            //             $productss[$i]['price_without_tax'] += $order_item->price_without_tax;
            //             $productss[$i]['tax_value'] += $order_item->tax_value;

            //         }  else {

            //             $productss[$index] = [
            //                 'product_id' => $product->id,
            //                 'name' => $product->name." - ".$product->barcode,
            //                 'qty' => $order_item->qty,
            //                 'item_price' => $order_item->item_price,
            //                 'total_price' => $order_item->total_price,
            //                 'price_without_tax' => $order_item->price_without_tax,
            //                 'tax_value' => $order_item->tax_value,
            //             ];
            //         }

            //     }
            // }


            foreach ($items->get() as $order_item) {

				//dd(11,$order_item,$order_item->user_product);

                if (
                    $order_item->user_product != null &&
                    $order_item->user_product->productColor != null &&
                    $order_item->user_product->productColor->product != null
                ) {


                    $product = $order_item->user_product->productColor->product;

                    // Search for the product by product_id and price
                    $existingIndex = null;

                    foreach ($productss as $key => $productEntry) {
                        if ($productEntry['product_id'] === $product->id && $productEntry['item_price'] === $order_item->item_price) {
                            $existingIndex = $key;
                            break;
                        }
                    }

                    if ($existingIndex !== null) {
                        // Merge quantities and totals for the matching price
                        $productss[$existingIndex]['qty'] += $order_item->qty;
                        $productss[$existingIndex]['total_price'] += $order_item->total_price;
                        $productss[$existingIndex]['price_without_tax'] += $order_item->price_without_tax;
                        $productss[$existingIndex]['tax_value'] += $order_item->tax_value;
                    } else {
                        // Add as a new entry if no match is found by product_id and price
                        $productss[] = [
                            'product_id' => $product->id,
                            'name' => $product->name . " - " . $product->barcode,
                            'qty' => $order_item->qty,
                            'item_price' => $order_item->item_price,
                            'total_price' => $order_item->total_price,
                            'price_without_tax' => $order_item->price_without_tax,
                            'tax_value' => $order_item->tax_value,
                        ];
                    }
                }
            }


            // dd($productss);

            $data = [];


			//dd($productss);

            foreach ($productss as $item){
                $item = (object) $item;
                $data[] = (object)[
                    'name' => $item->name,
                    'qty' => $item->qty,
                    'item_price' => ($item->item_price * $rate),
                    'total_price' => ($item->total_price * $rate),
                    'price_without_tax' => ($item->price_without_tax * $rate),
                    'tax_value' => ($item->tax_value * $rate),
                ];
            }
            $items = collect($data);
        }

        // dd($items);

        $invoice_date = date('jS F Y', strtotime($order->invoice_date));
        $language  = 'en';
        $country = $order->country_id ?? $order->seller->country_id ?? auth()->user()->country_id;
        $settings = Setting::where('country',$country)->where('language',$language)->pluck('value','name')->toArray();

        // dd('ok');

		//dd($items);

        //return view('includes.invoice_template',array('order'=>$order,'settings'=>$settings, 'items' => $items,'Currency' => $Currency));

        $pdf = PDF::loadView('includes.invoice_template',array('user_role'=>$user_role,'order'=>$order,'settings'=>$settings, 'items' => $items,'Currency' => $Currency));

        // dd($pdf);

        return $pdf->download('Invoice_'.config('app.name').'_Order_No # '.$oid.' Date_'.$invoice_date.'.pdf');
    }


    function print_invoice_v2($oid) {

        return $this->printInvoiceFor(InvoiceDataService::SOURCE_ORDER, (int) $oid);


        $order = WebsiteOrder::find($oid) ?: Order::findOrFail($oid);

        if($order instanceof Order && $order->seller) {

            if($order->seller->role_id == 3) {
                $user_role = 'shop';
            } else {
                $user_role = 'stock';
            }

        } else {
            $user_role = 'shop';
        }

		$rate = $order instanceof WebsiteOrder ? 1.0 : (float) ($order->curr_rate ?: 1);
		$Currency = strtoupper($order->curr_type ?: Country::defaultCurrency($order->country_id ?? $order->seller->country_id ?? Country::UAE));


        if ($order instanceof WebsiteOrder || $order->type == Order::TYPE_APP){

            $items = $order instanceof WebsiteOrder ? $order->items() : $order->productItems();
            $items = $items->selectRaw('SUM(qty) as qty, MAX(item_price) as item_price, MAX(total_price) as total_price, MAX(price_without_tax) as price_without_tax, MAX(tax_value) as tax_value, product_color_id')->groupBy('product_color_id')->get();
            $products = array();
            foreach ($items as $item) {
                $product = ProductColor::find($item->product_color_id);
                if(!array_key_exists($product->product_id,$products))
                    $products[$product->product_id] = [
                        'name' => $product->product->name." - ".$product->product->barcode,
                        'qty' => $item->qty,
                        'item_price' => ceil($item->item_price * $rate),
                        'total_price' => ceil($item->total_price * $rate),
                        'price_without_tax' => ceil($item->price_without_tax * $rate),
                        'tax_value' => ceil($item->tax_value * $rate),
                    ];
                else
                    $products[$product->product_id]['qty'] += $item->qty;
            }
            $data = [];
            foreach ($products as $item){
                $item = (object) $item;
                $data[] = (object)[
                    'name' => $item->name,
                    'qty' => $item->qty,
                    'item_price' => $item->item_price,
                    'total_price' => $item->total_price,
                    'price_without_tax' => $item->price_without_tax,
                    'tax_value' => $item->tax_value,
                ];
            }
            $items = collect($data);
        }else{

            $items    = $order->items();

            $products = $order->items()->pluck('user_product_id')->toArray();

            $products = UserProduct::selectRaw('GROUP_CONCAT(id) as ids, product_color_id')->whereIn('id',$products)->groupBy('product_color_id')->get();

            $data = [];

            $productss = array();

            foreach ($products as $item){

                $order_item = Order::query()->find($oid)->items()->selectRaw('SUM(qty) as qty, MAX(item_price) as item_price, MAX(total_price) as total_price, MAX(price_without_tax) as price_without_tax, MAX(tax_value) as tax_value')->whereIn('user_product_id',explode(',',$item->ids))->first();

                $product = ProductColor::find($item->product_color_id);

                if(!array_key_exists($product->product_id,$productss))
                    $productss[$product->product_id] = [
                        'name' => $product->product->name." - ".$product->product->barcode,
                        'qty' => $order_item->qty,
                        'item_price' => $order_item->item_price,
                        'total_price' => $order_item->total_price,
                        'price_without_tax' => $order_item->price_without_tax,
                        'tax_value' => $order_item->tax_value,
                    ];
                else
                    $productss[$product->product_id]['qty'] += $order_item->qty;

            }
            $data = [];

            foreach ($productss as $item){
                $item = (object) $item;
                $data[] = (object)[
                    'name' => $item->name,
                    'qty' => $item->qty,
                    'item_price' => ($item->item_price * $rate),
                    'total_price' => ($item->total_price * $rate),
                    'price_without_tax' => ($item->price_without_tax * $rate),
                    'tax_value' => ($item->tax_value * $rate),
                ];
            }
            $items = collect($data);

        }

        $invoice_date = date('jS F Y', strtotime($order->invoice_date));
        $language  = 'en';
        $country = $order->country_id ?? $order->seller->country_id ?? auth()->user()->country_id;
        $settings = Setting::where('country',$country)->where('language',$language)->pluck('value','name')->toArray();

        return view('includes.printer',array('user_role'=>$user_role,'order'=>$order,'settings'=>$settings, 'items' => $items,'Currency' => $Currency));

    }

    public function typedInvoice(string $source, int $id)
    {
        $data = $this->invoicePayload($source, $id);

        return view('receipts.pdfReceipt', $data);
    }

    public function typedDownloadInvoice(string $source, int $id)
    {
        return $this->downloadInvoiceFor($source, $id);
    }

    public function typedPrintInvoice(string $source, int $id)
    {
        return $this->printInvoiceFor($source, $id);
    }

    private function downloadInvoiceFor(string $source, int $id)
    {
        $data = $this->invoicePayload($source, $id);
        $date = $data['order']->invoice_date ?: $data['order']->created_at;
        $fileDate = date('jS F Y', strtotime((string) $date));
        $pdf = PDF::loadView('includes.invoice_template', $data);

        return $pdf->download('Invoice_' . config('app.name') . '_Order_No # ' . $id . ' Date_' . $fileDate . '.pdf');
    }

    private function printInvoiceFor(string $source, int $id)
    {
        return view('includes.printer', $this->invoicePayload($source, $id));
    }

    private function invoicePayload(string $source, int $id): array
    {
        $order = $this->invoiceDataService->resolve($source, $id);
        $this->authorizeInvoiceAccess($order);
        $data = $this->invoiceDataService->forOrder($order);
        $country = $order->country_id ?? optional($order->seller)->country_id ?? Country::UAE;
        $data['settings'] = Setting::where('country', $country)
            ->where('language', 'en')
            ->pluck('value', 'name')
            ->toArray();
        $data['invoiceIdentity'] = $this->invoiceDataService->identityForOrder($order, $data['settings']);
        $data['Currency'] = $data['currency'];
        $data['user_role'] = $order instanceof Order && $order->seller && (int) $order->seller->role_id !== User::ROLE_SHOP
            ? 'stock'
            : 'shop';

        return $data;
    }

    private function authorizeInvoiceAccess($order): void
    {
        $user = auth()->user();
        if (!$user) {
            abort_unless(request()->hasValidSignature(), 403);
            return;
        }

        $countryId = $order instanceof WebsiteOrder
            ? (int) $order->country_id
            : (int) optional($order->seller)->country_id;
        $hasCountryAccess = (int) $user->country_id === Country::ALL
            || (int) $user->country_id === $countryId;
        abort_unless($hasCountryAccess, 403);

        if ($order instanceof Order
            && !in_array((int) $user->role_id, [User::ROLE_ADMIN, User::ROLE_WAREHOUSE], true)
            && (int) $order->seller_id !== (int) $user->id) {
            abort(403);
        }
    }
}

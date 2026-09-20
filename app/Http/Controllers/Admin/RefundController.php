<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RefundRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Refund;
use App\Models\User;
use App\Models\UserProduct;
use App\Repositories\RefundRepository;
use App\Services\CurrencyService;
use App\Support\Country;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class RefundController extends Controller
{

    private $refundRepository;

    public function __construct(RefundRepository $refundRepository)
    {
        $this->refundRepository = $refundRepository;
    }
	
	public function show($id) {
	
	}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'direction' => ['in:asc,desc', 'nullable'],
            'field' => ['in:stock,id,qty', 'nullable'],
            'date' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $refunds = $this->refundRepository->getRefunds($request);

        if ($request->wantsJson()){
            return [
                'rows' => $refunds['rows'],
                'refunds' => $refunds['rows'],
                'total'   => $refunds['total'],
                'totals_by_currency' => $refunds['totals_by_currency'] ?? [],
            ];
        }
        $shops = User::query()->whereIn('role_id', [User::ROLE_SHOP, User::ROLE_WAREHOUSE])->where('country_id',auth()->user()->country_id)->get();
        $shops = transformDataForVue($shops);

        $buyers = User::query()
            ->where('role_id', User::ROLE_CLIENT)
            ->whereHas('orders')
            ->where('country_id',auth()->user()->country_id)
            ->get();

        $buyers = transformDataForVue($buyers);

        $rate = app(CurrencyService::class)->rate(Country::defaultCurrency(auth()->user()->country_id));


        return Inertia::render('Admin/Refunds/Index', [
            'rate'    => $rate,
            'rows'    => $refunds['rows'],
            'refunds' => $refunds['rows'],
            'total'   => $refunds['total'],
            'totals_by_currency' => $refunds['totals_by_currency'] ?? [],
            'shops'   => $shops,
            'buyers'  => $buyers,
            'filters' => $request->all(['search', 'buyer', 'shop', 'field', 'direction', 'date', 'start_date', 'end_date'])
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Refunds/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'selected_products' => ['required', 'array', 'min:1'],
            'selected_products.*.product_id' => ['required', 'integer', 'exists:order_items,id'],
            'selected_products.*.qty' => ['required', 'integer', 'min:1'],
            'selected_products.*.price' => ['sometimes', 'required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($request) {
            $this->refundRepository->add($request);
        });
        $request->session()->flash('success', 'تم إنشاء المرتجع بنجاح');
        return Redirect::route('refunds.index');
    }

    public function match(Request $request)
    {
        $userId = auth()->id();
        $productId = $request->get('product');

        $item = OrderItem::query()
            ->whereHas('product', function ($query) use ($productId){
                //$query->whereRelation('productColor', 'barcode', $productId);
				$query->where('barcode', $productId);
            })->whereRelation('order', 'seller_id', $userId)
            ->whereRelation('order', 'buyer_id', null)
        ->orderBy('id','desc')->first();

        if ($item){
            $color = $item->product->productColor;
            $price = $this->refundRepository->maxUnitRefundPrice($item);

            return [
                'id' => $item->id,
                'stock' => $item->qty,
                'price' => $price,
                'max_refund_unit_price' => $price,
                'currency_code' => strtoupper($item->order->curr_type ?: 'USD'),
                'product_color' => [
                    'photo_url' => $color->photo_url,
                    'product_name' => $color->product_name,
                ],
            ];
        }
        return false;
    }

}

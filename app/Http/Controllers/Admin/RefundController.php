<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RefundRequest;
use App\Models\Currency;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Refund;
use App\Models\User;
use App\Models\UserProduct;
use App\Models\Wallet;
use App\Repositories\RefundRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'field' => ['in:stock,id,qty', 'nullable']
        ]);

        $refunds = $this->refundRepository->getRefunds($request);

        if ($request->wantsJson()){
            return [
                'refunds' => $refunds['refunds'],
                'total'   => $refunds['total'],
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

        if(auth()->user()->country_id == 2)
            $rate = Currency::where('name','aed')->first()->rate;
        else
            $rate = 1;


        return Inertia::render('Admin/Refunds/Index', [
            'rate'    => $rate,
            'refunds' => $refunds['refunds'],
            'total'   => $refunds['total'],
            'shops'   => $shops,
            'buyers'  => $buyers,
            'filters' => $request->all(['search', 'buyer', 'shop', 'field', 'direction'])
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Refunds/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->refundRepository->add($request);
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

        if(auth()->user()->country_id == 2){
            $rate = Currency::query()->where('name','aed')->first()->rate;
        }else{
            $rate = 1;
        }

        if ($item){
            $result = $item->product;
            $result->product_color = $item->product->productColor;
            $result->stock = $item->qty;
            $result->price = currencyExchange($item->item_price, $rate);
            $result->id = $item->id;
            return $result;
        }
        return false;
    }

}

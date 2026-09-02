<?php

namespace App\Http\Controllers\Admin;

use App\Classes\Socket;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\User;
use App\Models\Currency;
use App\Services\CurrencyService;
use App\Support\Country;
use App\Repositories\ProductColorRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProductColorController extends Controller

{

    private $productRepository;

    public function __construct(ProductColorRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'direction' => ['in:asc,desc', 'nullable'],
            'field' => ['in:stock,id,color_id', 'nullable']
        ]);

        $products = $this->productRepository->getProducts($request);
        $nproducts = array();
        foreach ($products as $product) {
            foreach ($product->list_sizes as $subproduct) {
                array_push($nproducts,$subproduct);
            }
        }
        $users = User::query()->whereIn('role_id', [User::ROLE_SHOP, User::ROLE_WAREHOUSE])
            ->where('country_id', auth()->user()->country_id)->get();
        $merchants = User::query()->where('role_id', User::ROLE_MERCHANT)
            ->where('country_id', auth()->user()->country_id)->get();
        $new_sizes = getNewSizesVariables();
        if ($request->wantsJson()){
            return $products;
            // return ['data'=>$nproducts];
        }

        $rate = app(CurrencyService::class)->rate(Country::defaultCurrency(auth()->user()->country_id));

        return Inertia::render('Admin/ProductColors/Index', [
            'products'  => $products,
            'nproducts' => ['data'=>$nproducts],
            'users'     => $users,
            'merchants' => $merchants,
            'filters'   => $request->all(['search', 'field', 'direction', 'subsearch']),
            'sizes'     => $new_sizes,
            'rate'      => $rate
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function print(Request $request): JsonResponse
    {
        $product = ProductColor::query()->findOrFail($request->get('id'));
        $barcode = $request->get('barcode');
        $size = convertNumberToSize(substr($barcode, -2));

        $productData = [
            'name' => $size . " - ". $product->simple_name,
            'barcode' => $product->barcode,
            'id' => time().rand(1, 1000),
            'qty' => $request->get('stock')??$product->stock??1
        ];
        /* $data = [ */
        /*     'type' => 'label', */
        /*     'content' => $productData, */
        /*     'id' => auth()->id() */
        /* ]; */

        return response()->json($productData);
    }

    public function singlePrint(Request $request, Socket $socket)
    {
        $product = ProductColor::query()->findOrFail($request->get('product'));
        $barcode = $request->get('barcode');
        $size = convertNumberToSize(substr($barcode, -2));

        $productData = [
            'name' => $size . " - ". $product->simple_name ,
            'barcode' => $request->get('barcode'),
            'id' => time().rand(1, 1000),
            'qty' => 1
        ];
        $data = [
            'type' => 'label',
            'content' => $productData,
            'id' => auth()->id()
        ];
        $socket->send(json_encode($data));

        return response()->json($size);
    }

    public function create(): Response
    {
        $categories = Category::query()->get();
        $colors = Color::query()->get();
        $sizes = getSizesVariables();
        $new_sizes = getNewSizesVariables();

        return Inertia::render('Admin/ProductColors/Create', [
            'categories' => $categories,
            'colors' => $colors,
            'sizes' => $new_sizes,
            'new_sizes' => $new_sizes,
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = $this->productRepository->add($request);
        if ($product instanceof Product)
            $request->session()->flash('success', 'تم إنشاء المنتج بنجاح');
        else
            if($product == "no")
                $request->session()->flash('error', "You can't create product without colors");
            else
                $request->session()->flash('error', $product);

        return Redirect::route('productColors.index');
    }

}

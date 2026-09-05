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
use App\Repositories\ProductRepository;
use App\Services\CurrencyService;
use App\Support\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller

{

    private $productRepository;
    private $currencyService;

    public function __construct(ProductRepository $productRepository, CurrencyService $currencyService)
    {
        $this->productRepository = $productRepository;
        $this->currencyService = $currencyService;
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

        if ($request->wantsJson()){
            return $products;
        }

        $countryId = (int) auth()->user()->country_id;
        $currencyCode = Country::defaultCurrency($countryId);
        $rate = $this->currencyService->rate($currencyCode);

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'filters'  => $request->all(['search', 'field', 'direction']),
            'rate'     => $rate,
            'currency' => [
                'code' => $currencyCode,
                'rate' => $rate,
                'decimals' => $currencyCode === 'SYP' ? 0 : 2,
                'display' => $this->currencyService->displayForCountry($countryId),
            ],
        ]);
    }

    public function create(): Response
    {
        $categories = Category::query()->get();
        $colors = Color::query()->get();
        $sizes = getSizesVariables();
        $new_sizes = getNewSizesVariables();
        $currencyCode = Country::defaultCurrency((int) auth()->user()->country_id);
        $rate = $this->currencyService->rate($currencyCode);

        return Inertia::render('Admin/Products/Create', [
            'categories' => $categories,
            'colors' => $colors,
            'sizes' => $new_sizes,
            'currency' => ['code' => $currencyCode, 'rate' => $rate, 'decimals' => $currencyCode === 'SYP' ? 0 : 2],
        ]);
    }

    public function edit($id): Response
    {
        $product = Product::query()->with(['category', 'colors'])->findOrFail($id);
        $categories = Category::query()->get();
        $colors = Color::query()->get();
        $sizes = getSizesVariables();
        $new_sizes = getNewSizesVariables();

        $selectedSizes = getSizes($product->sizes);
        $selectedColors = getColors($product->colors);

        $rate = $this->currencyService->rate(Country::defaultCurrency(auth()->user()->country_id));
        $currencyCode = Country::defaultCurrency((int) auth()->user()->country_id);

        return Inertia::render('Admin/Products/Edit', [
            'categories' => $categories,
            'colors' => $colors,
            'sizes' => $new_sizes,
            'product' => $product,
            'selected_sizes' => $selectedSizes,
            'selected_colors' => $selectedColors,
            'rate' => $rate,
            'currency' => ['code' => $currencyCode, 'rate' => $rate, 'decimals' => $currencyCode === 'SYP' ? 0 : 2],
        ]);
    }

    public function update(Product $product, ProductRequest $request)
    {
        $this->productRepository->update($request, $product);
        $request->session()->flash('success', 'تم تعديل الموديل بنجاح');
        return Redirect::route('products.index');
    }

    public function storeMedia(): JsonResponse
    {
        $image = uploadImage('file', 'products/colors');
        return response()->json([$image]);
    }

    public function destroy(Product $product)
    {
        $error = $this->productRepository->delete($product);
        if (!$error)
            return response()->json([
                'success' => true,
                'msg' => 'تم حذف الموديل بنجاح'
            ]);
        return response()->json([
            'success' => false,
            'error' => $error
        ]);
    }

    public function testPrint(Socket $socket)
    {
        $socket->send('hi');
    }

    public function toggleMerchantVisibility(Product $product): JsonResponse
    {
        $product->update([
            'shown_for_merchant' => !$product->shown_for_merchant
        ]);

        return response()->json([
            'success' => true,
            'shown_for_merchant' => $product->shown_for_merchant,
            'msg' => 'Visibility updated successfully'
        ]);
    }


}

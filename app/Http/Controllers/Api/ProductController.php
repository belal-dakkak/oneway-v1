<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Models\User;
use App\Repositories\MobileProductRepository;
use App\Support\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends ApiController
{
    protected $productRepository;

    public function __construct(MobileProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function index(Request $request): JsonResponse
    {
        $colors = array_values(explode(',', str_replace(['[', ']', '"', '"'], '', $request->get('color'))));
        $colors = array_filter($colors, 'strlen');
        if (count($colors) > 0)
            $request->request->add(['colors' => $colors]);

        $currency = $request->header('Accept-Currency', 'LBP');
        $request->request->add([
            'country_id' => Country::idForCurrency($currency, $request->header('Accept-Country')),
        ]);

        $products = $this->productRepository->getProducts($request, true);

        return $this->respondSuccess($products->all(), $this->createApiPaginator($products));
    }
}

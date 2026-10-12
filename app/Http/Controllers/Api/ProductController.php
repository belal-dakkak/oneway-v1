<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Models\User;
use App\Repositories\MobileProductRepository;
use App\Services\CurrencyService;
use App\Services\OperationalPriceService;
use App\Support\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends ApiController
{
    protected $productRepository;
    protected $currencyService;
    protected $operationalPrices;

    public function __construct(
        MobileProductRepository $productRepository,
        CurrencyService $currencyService,
        OperationalPriceService $operationalPrices
    )
    {
        $this->productRepository = $productRepository;
        $this->currencyService = $currencyService;
        $this->operationalPrices = $operationalPrices;
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
        $displayCurrency = $this->currencyService->displayForCountry($request->get('country_id'));
        foreach ($products as $product) {
            $product->setAttribute('base_currency', 'USD');
            $product->setAttribute('display_currency', $displayCurrency);
            if ($displayCurrency) {
                $product->setAttribute('display_prices', [
                    'retail_price' => $this->operationalPrices->amount(
                        $product,
                        'retail_price',
                        $displayCurrency['code'],
                        $product->getRawOriginal('retail_price')
                    ),
                    'sale_price' => $this->operationalPrices->amount(
                        $product,
                        'sale_price',
                        $displayCurrency['code'],
                        $product->getRawOriginal('sale_price')
                    ),
                    'price_before_discount' => $this->operationalPrices->nullableAmount(
                        $product,
                        'price_before_discount',
                        $displayCurrency['code'],
                        $product->getRawOriginal('price_before_discount')
                    ),
                ]);
            }
        }

        return $this->respondSuccess($products->all(), $this->createApiPaginator($products));
    }
}

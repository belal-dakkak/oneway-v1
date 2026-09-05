<?php

namespace App\Http\Controllers\Api\Order;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\CheckoutRequest;

use App\Models\Order;
use App\Models\WebsiteOrder;
use App\Repositories\OrderRepository;
use App\Services\CurrencyService;
use App\Support\Country;
use InvalidArgumentException;


class CartController extends ApiController
{
    private $orderRepository;
    private $currencyService;

    public function __construct(OrderRepository $orderRepository, CurrencyService $currencyService)
    {
        $this->orderRepository = $orderRepository;
        $this->currencyService = $currencyService;
    }

    public function store(CheckoutRequest $request)
    {
        /** @var  $items */
        $items = $request->get('items');
        if (!$items)
            return $this->respondError(__('api.please_add_items_first'));

        /** @var  $user */
        // This route is protected by Sanctum. Never accept a client-supplied
        // user_id here because that could cross both customer and branch data.
        $user = $request->user();
        if (!$user)
            return $this->respondError(__('api.user_not_found'));

        $countryId = (int) $user->country_id;
        $requestedCurrency = strtoupper((string) $request->input(
            'currency',
            $request->header('Accept-Currency', Country::defaultCurrency($countryId))
        ));
        try {
            $currency = $this->currencyService->validateForCountry($requestedCurrency, $countryId, true);
        } catch (InvalidArgumentException $exception) {
            return $this->respondError('The selected currency is not available for this country.');
        }

        $payment = $request->input('payment', ['name' => 'cod']);
        if (is_string($payment)) {
            $payment = ['name' => $payment];
        }
        if ($countryId === Country::SYRIA && ($payment['name'] ?? 'cod') !== 'cod') {
            return $this->respondError('Card payment is not available for Syria.');
        }

        $request->merge([
            'type' => Order::TYPE_APP,
            // The repository must price and reserve stock for the authenticated
            // customer's branch, never for a country supplied by the client.
            'country_id' => $countryId,
            'authenticated_user_id' => (int) $user->id,
            'currency' => $currency,
            'payment' => $payment,
        ]);

        $order = $this->orderRepository->addForOnline($request);

        if (!$order instanceof WebsiteOrder)
            return $this->respondError($order);

        return $this->respondSuccess(__('api.your_order_has_been_created_successfully'));
    }
}

<?php

namespace App\Http\Controllers\Api\Order;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\CheckoutRequest;

use App\Models\Order;
use App\Models\WebsiteOrder;
use App\Repositories\OrderRepository;
use App\Services\SalesCurrencyPolicy;
use App\Support\Country;
use InvalidArgumentException;


class CartController extends ApiController
{
    private $orderRepository;
    private $salesCurrencyPolicy;

    public function __construct(OrderRepository $orderRepository, SalesCurrencyPolicy $salesCurrencyPolicy)
    {
        $this->orderRepository = $orderRepository;
        $this->salesCurrencyPolicy = $salesCurrencyPolicy;
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
            // The mobile API currently represents the normal retail storefront.
            // The server policy therefore forces Syrian API sales to SYP.
            $currency = $this->salesCurrencyPolicy
                ->websiteOption($countryId, false, $requestedCurrency)['code'];
        } catch (InvalidArgumentException $exception) {
            return $this->respondError('The selected currency is not available for this country.');
        }

        $payment = $request->input('payment', ['name' => 'cod']);
        if (is_string($payment)) {
            $payment = ['name' => $payment];
        }
        if (($payment['name'] ?? 'cod') !== 'cod') {
            // This endpoint has no gateway redirect/callback contract. Refuse to
            // create an unpaid card order that the client cannot complete.
            return $this->respondError('Card payment must be completed through the website checkout.');
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

        if (($payment['name'] ?? 'cod') === 'cod' && $order->exists && $order->id) {
            $order->dispatchNotifications();
        }

        return $this->respondSuccess(__('api.your_order_has_been_created_successfully'));
    }
}

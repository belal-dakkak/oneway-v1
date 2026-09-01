<?php

namespace App\Http\Controllers\Api\Order;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\CheckoutRequest;

use App\Models\Order;
use App\Models\WebsiteOrder;
use App\Repositories\OrderRepository;


class CartController extends ApiController
{
    private $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function store(CheckoutRequest $request)
    {
        /** @var  $items */
        $items = $request->get('items');
        if (!$items)
            return $this->respondError(__('api.please_add_items_first'));

        /** @var  $user */
        $user = $this->getUser($request);
        if (!$user)
            return $this->respondError(__('api.user_not_found'));

        $request->request->add(['type' => Order::TYPE_APP]);

        $order = $this->orderRepository->addForOnline($request);

        if (!$order instanceof WebsiteOrder)
            return $this->respondError($order);

        return $this->respondSuccess(__('api.your_order_has_been_created_successfully'));
    }
}

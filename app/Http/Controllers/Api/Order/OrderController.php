<?php

namespace App\Http\Controllers\Api\Order;

use App\Http\Controllers\ApiController;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Repositories\OrderRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends ApiController
{
    private $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function index(Request $request): JsonResponse
    {
        $limit = $request->get('limit') ?: 100;
        if ($limit > 100) $limit = 100;

        $user = $this->getUser($request);
        if (!$user)
            return $this->respondError(__('api.user_not_found'));

        $request->request->add(['user_id' => $user->id]);

        $with = ['productItems.product.product', 'seller'];
        $orders = $this->orderRepository
            ->getOrders($request, false, false, $with)['orders'];

        return $this->respondSuccess($orders->all(), $this->createApiPaginator($orders));
    }

    public function order(Request $request, $id): JsonResponse
    {
        $order = Order::query()->find($id);
        if (!$order)
            return $this->respondError(__('api.order_not_found'));

        $user = $this->getUser($request);
        if ($user)
            $this->likedReviewByUser($order->items, $user);

        return $this->respondSuccess($order);
    }
}

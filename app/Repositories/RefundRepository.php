<?php

namespace App\Repositories;

use App\Models\OrderItem;
use App\Models\Refund;
use App\Models\User;
use App\Models\UserProduct;
use App\Models\Wallet;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class RefundRepository
{
    public function add(Request $request): Refund
    {

        $total  = 0;
        $selectedProducts = $request->get('selected_products');

		//dd($selectedProducts);

		$refund = null;

        // Track which orders were touched during this refund
        $touchedOrderIds = [];

        foreach ($selectedProducts as $product){

			//dd($product);

            $orderItem = OrderItem::query()->lockForUpdate()->find($product['product_id']);

			/*
			$orderItem = OrderItem::query()->whereHas('product', function ($query) use ($product){
				$query->where('barcode', $product['product_id']);
            })->orderBy('id','desc')->first();
			*/

			//dd($orderItem);

			if($orderItem) {
                $refundQty = (int) $product['qty'];
                if ($refundQty <= 0 || $refundQty > (int) $orderItem->qty) {
                    throw ValidationException::withMessages([
                        'selected_products' => 'كمية المرتجع أكبر من الكمية المتبقية في الفاتورة.',
                    ]);
                }

                $seller = optional($orderItem->order)->seller;
                if (! $seller || (int) $seller->country_id !== (int) auth()->user()->country_id) {
                    abort(403);
                }
                if (auth()->user()->role_id !== User::ROLE_ADMIN
                    && (int) $orderItem->order->seller_id !== (int) auth()->id()) {
                    abort(403);
                }

				$itemBarcode = $orderItem->product->productColor->barcode;
				$orderBarcode = $orderItem->order->barcode;

				/*
				$userProduct = UserProduct::query()
					->where('product_color_id', $orderItem->product->productColor->id)
					->where('user_id', auth()->id())->first();
				*/

				$userProduct = UserProduct::query()->find($orderItem->user_product_id);

				//dd($userProduct);

				$newStock = $userProduct->stock + $refundQty;
				$userProduct->update(['stock' => $newStock]);

                // $orderItem->update(['qty' => $orderItem->qty - $product['qty']]);
				
				$new_qty = $orderItem->qty - $refundQty;
				$rateAux = (float) $orderItem->order->curr_rate ?: 1;
				$productPrice = (float) $orderItem->item_price;

                $orderItem->update([
                    'qty' => $new_qty,
                    'total_price' => $productPrice * $new_qty,
                    'total_price_paid' => $productPrice * $new_qty * $rateAux,
                ]);

				$refund = new Refund([
					'order_item_id' => $product['product_id'],
					'qty' => $refundQty,
					'item_price' => $productPrice,
					'total_price' => $productPrice * $refundQty,
                    'total_price_paid' => $productPrice * $refundQty * $rateAux,
					'item_barcode' => $itemBarcode,
					'order_barcode' => $orderBarcode,
				]);
				$refund->save();
				$total += $productPrice * $refundQty;
				
				/////////////////////////////////////////////////////////////
                /////////////////////////////////////////////////////////////

                $order      = $orderItem->order;
                // Track this order as touched
                if ($order && !in_array($order->id, $touchedOrderIds)) {
                    $touchedOrderIds[] = $order->id;
                }

                /////////////////////////////////////////////////////////////
                /////////////////////////////////////////////////////////////


			}

        }

        // Check each touched order: if all items are fully refunded (qty = 0),
        // also refund the shipping fee and COD fee.
        foreach ($touchedOrderIds as $orderId) {
            $touchedOrder = \App\Models\Order::query()->find($orderId);
            if (!$touchedOrder) continue;

            $hasRemainingItems = $touchedOrder->items()->where('qty', '>', 0)->exists();

            if (!$hasRemainingItems) {
                // All items have been refunded — refund shipping and COD fees too.
                $feesToRefund = (float)($touchedOrder->shipping_fee ?? 0)
                              + (float)($touchedOrder->cod_fee ?? 0);

                if ($feesToRefund > 0) {
                    // Convert fees to base currency (same logic as item prices)
                    $rate = $touchedOrder->curr_rate ?: 1;
                    $feesToRefundBase = $feesToRefund / $rate;
                    $total += $feesToRefundBase;

                    // Zero out fees on the order to prevent double-refunding
                    $touchedOrder->update([
                        'shipping_fee' => 0,
                        'cod_fee'      => 0,
                    ]);
                }
            }
        }

        $wallet = Wallet::query()->where('user_id', auth()->id())->lockForUpdate()->first();
        if (! $wallet) {
            $wallet = Wallet::query()->create([
                'user_id' => auth()->id(),
                'credit' => 0,
                'debit' => 0,
            ]);
        }
        $wallet->decrement('credit', $total);

        return $refund;
    }

    public function getRefunds(Request $request)
    {
        $refunds = Refund::query()->with(['orderItem.order']);
        $country = auth()->user()->country_id;
        $refunds->whereHas('orderItem', function ($query) use ($country){
            $query->whereHas('product', function ($q) use ($country){
                $q->where('country_id',$country);
            });
        });
        if ($search = $request->get('search')) {
            $refunds->where(function ($query) use ($search) {
                $query->where('item_barcode', 'LIKE', "%$search%")
                    ->orWhere('order_barcode', 'LIKE', "%$search%");
            });
        }

        if (auth()->user()->role_id != User::ROLE_ADMIN)
            $refunds->whereHas('orderItem', function ($query){
                $query->whereHas('order', function ($query){
                    $query->where('seller_id', auth()->id());
                });
            });

        if ($shop = $request->get('shop')){
            $refunds->whereHas('orderItem', function ($query) use ($shop){
                $query->whereHas('order', function ($query) use ($shop){
                    $query->where('seller_id', $shop);
                });
            });
        }

        if ($buyer = $request->get('buyer')){
            $refunds->whereHas('orderItem', function ($query) use ($buyer){
                $query->whereHas('order', function ($query) use ($buyer){
                    $query->where('buyer_id', $buyer);
                });
            });
        }

        if ($date = $request->get('date'))
            $refunds->whereDate('refunds.created_at', Carbon::parse($date));

        if ($startDate = $request->get('start_date'))
            $refunds->whereDate('refunds.created_at', '>=', Carbon::parse($startDate));

        if ($endDate = $request->get('end_date'))
            $refunds->whereDate('refunds.created_at', '<=', Carbon::parse($endDate));


        if ($request->has(['field', 'direction'])){
            $field = $request->get('field');
            $direction = $request->get('direction');

            $sortableArray = app(Refund::class)->getFillable();
            $sortableArray[] = 'id';
            if(in_array($field, $sortableArray)){
                $refunds->orderBy($field, $direction);
            }else{
                $refunds->orderByDesc('id');
            }
        }else{
            $refunds->orderByDesc('id');
        }

        $totalsByCurrency = (clone $refunds)
            ->reorder()
            ->join('order_items as refund_order_items', 'refunds.order_item_id', '=', 'refund_order_items.id')
            ->join('orders as refund_orders', 'refund_order_items.order_id', '=', 'refund_orders.id')
            ->selectRaw("UPPER(COALESCE(refund_orders.curr_type, 'USD')) as currency_code")
            ->selectRaw('SUM(CASE WHEN refunds.total_price_paid IS NOT NULL AND refunds.total_price_paid <> 0 THEN refunds.total_price_paid ELSE refunds.total_price * COALESCE(refund_orders.curr_rate, 1) END) as total')
            ->groupBy('currency_code')
            ->pluck('total', 'currency_code')
            ->map(function ($total) {
                return (float) $total;
            })
            ->toArray();
        $totalAmount = count($totalsByCurrency) === 1 ? (float) reset($totalsByCurrency) : 0;

        return [
            'refunds' => $refunds->paginate(10),
            'total'   => $totalAmount,
            'totals_by_currency' => $totalsByCurrency,
        ];
    }

}

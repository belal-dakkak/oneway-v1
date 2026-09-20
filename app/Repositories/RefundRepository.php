<?php

namespace App\Repositories;

use App\Models\OrderItem;
use App\Models\Refund;
use App\Models\User;
use App\Models\UserProduct;
use App\Services\CashboxService;
use App\Support\Country;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class RefundRepository
{
    private $cashboxes;

    public function __construct(CashboxService $cashboxes)
    {
        $this->cashboxes = $cashboxes;
    }

    public function add(Request $request): Refund
    {

        $total  = 0;
        $selectedProducts = $request->get('selected_products');

		//dd($selectedProducts);

		$refund = null;

        // Track which orders were touched during this refund
        $touchedOrderIds = [];
        $lastRefundByOrder = [];

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
                if ($orderItem->order->buyer_id) {
                    throw ValidationException::withMessages([
                        'selected_products' => 'Use the customer return workflow for invoices linked to a customer account.',
                    ]);
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
                    'currency_code' => strtoupper((string) ($orderItem->order->curr_type ?: 'USD')),
                    'net_amount' => (float) ($orderItem->price_without_tax_paid ?: $orderItem->item_price_paid ?: ($productPrice * $rateAux)) * $refundQty,
                    'tax_amount' => (float) ($orderItem->tax_value_paid ?: 0) * $refundQty,
                    'cost_amount' => (float) ($orderItem->unit_cost ?: $orderItem->product->wholesale_price ?: 0) * $rateAux * $refundQty,
					'item_barcode' => $itemBarcode,
					'order_barcode' => $orderBarcode,
				]);
				$refund->save();
				$lastRefundByOrder[(int) $orderItem->order_id] = $refund;
				$total += $productPrice * $refundQty;

                $currency = strtoupper((string) ($orderItem->order->curr_type ?: 'USD'));
                $this->cashboxes->debit(
                    (int) $orderItem->order->seller_id,
                    (float) $refund->total_price_paid,
                    $currency,
                    "refund:{$refund->id}",
                    [
                        'exchange_rate' => $currency === 'USD' ? 1 : $rateAux,
                        'payment_method' => 'refund',
                        'source_type' => Refund::class,
                        'source_id' => $refund->id,
                        'note' => "Refund for order #{$orderBarcode}",
                    ]
                );
				
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

                    $feeCurrency = strtoupper((string) ($touchedOrder->curr_type ?: 'USD'));
                    $this->cashboxes->debit(
                        (int) $touchedOrder->seller_id,
                        $feesToRefund,
                        $feeCurrency,
                        "order:{$touchedOrder->id}:fees-refund",
                        [
                            'exchange_rate' => $feeCurrency === 'USD' ? 1 : (float) $rate,
                            'payment_method' => 'refund',
                            'source_type' => \App\Models\Order::class,
                            'source_id' => $touchedOrder->id,
                            'note' => "Shipping and COD refund for order #{$touchedOrder->barcode}",
                        ]
                    );

                    // Keep the original invoice immutable. The extra amount is
                    // stored on the final item-refund snapshot for this order;
                    // the idempotent cashbox key remains the posting safeguard.
                    $feeRefund = $lastRefundByOrder[(int) $touchedOrder->id] ?? null;
                    if ($feeRefund) {
                        $feeRefund->total_price = (float) $feeRefund->total_price + $feesToRefundBase;
                        $feeRefund->total_price_paid = (float) $feeRefund->total_price_paid + $feesToRefund;
                        $feeRefund->net_amount = (float) $feeRefund->net_amount + $feesToRefund;
                        $feeRefund->save();
                    }
                }
            }
        }

        return $refund;
    }

    public function getRefunds(Request $request)
    {
        $refunds = Refund::query()->with([
            'orderItem.order.buyer',
            'orderItem.order.seller',
            'orderItem.product.productColor',
        ]);
        $country = auth()->user()->country_id;
        $refunds->whereHas('orderItem.order.seller', function ($query) use ($country){
            $query->where('country_id', $country);
        });
        if ($search = $request->get('search')) {
            $refunds->where(function ($query) use ($search) {
                $query->where('refunds.item_barcode', 'LIKE', "%$search%")
                    ->orWhere('refunds.order_barcode', 'LIKE', "%$search%");
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

        $countryTimezone = Country::timezone((int) $country);
        $storageTimezone = (string) config('app.timezone', 'UTC');
        if ($date = $request->get('date')) {
            $local = Carbon::parse($date, $countryTimezone);
            $refunds->whereBetween('refunds.created_at', [
                $local->copy()->startOfDay()->setTimezone($storageTimezone),
                $local->copy()->endOfDay()->setTimezone($storageTimezone),
            ]);
        } else {
            if ($startDate = $request->get('start_date')) {
                $refunds->where('refunds.created_at', '>=', Carbon::parse($startDate, $countryTimezone)
                    ->startOfDay()->setTimezone($storageTimezone));
            }
            if ($endDate = $request->get('end_date')) {
                $refunds->where('refunds.created_at', '<=', Carbon::parse($endDate, $countryTimezone)
                    ->endOfDay()->setTimezone($storageTimezone));
            }
        }


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

        $currencyExpression = "UPPER(COALESCE(NULLIF(refunds.currency_code, ''), NULLIF(refund_orders.curr_type, ''), 'USD'))";
        $refundAmounts = (clone $refunds)
            ->reorder()
            ->join('order_items as refund_order_items', 'refunds.order_item_id', '=', 'refund_order_items.id')
            ->join('orders as refund_orders', 'refund_order_items.order_id', '=', 'refund_orders.id')
            ->selectRaw("{$currencyExpression} as report_currency")
            ->selectRaw('CASE WHEN refunds.total_price_paid IS NOT NULL AND refunds.total_price_paid <> 0 THEN refunds.total_price_paid ELSE refunds.total_price * COALESCE(refund_orders.curr_rate, 1) END as refund_amount');
        $totalsByCurrency = DB::query()
            ->fromSub($refundAmounts, 'refund_amounts')
            ->selectRaw('report_currency, SUM(refund_amount) as total')
            ->groupBy('report_currency')
            ->pluck('total', 'report_currency')
            ->map(function ($total) {
                return (float) $total;
            })
            ->toArray();
        $totalAmount = count($totalsByCurrency) === 1 ? (float) reset($totalsByCurrency) : 0;

        $rows = $refunds->paginate(10);
        return [
            'rows' => $rows,
            'refunds' => $rows,
            'total'   => $totalAmount,
            'totals_by_currency' => $totalsByCurrency,
        ];
    }

}

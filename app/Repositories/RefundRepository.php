<?php

namespace App\Repositories;

use App\Models\OrderItem;
use App\Models\Refund;
use App\Models\User;
use App\Models\UserProduct;
use App\Models\Wallet;
use App\Models\Currency;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RefundRepository
{

    public function add(Request $request): Refund
    {

        $total  = 0;
        $selectedProducts = $request->get('selected_products');

		//dd($selectedProducts);

		// new
		$userId = auth()->id();

		$refund = null;

        // Track which orders were touched during this refund
        $touchedOrderIds = [];

        foreach ($selectedProducts as $product){

			//dd($product);

            $orderItem = OrderItem::query()->find($product['product_id']);

			/*
			$orderItem = OrderItem::query()->whereHas('product', function ($query) use ($product){
				$query->where('barcode', $product['product_id']);
            })->orderBy('id','desc')->first();
			*/

			//dd($orderItem);

			if($orderItem) {

				$itemBarcode = $orderItem->product->productColor->barcode;
				$orderBarcode = $orderItem->order->barcode;

				$rate = $orderItem->order->curr_rate;

				/*
				$userProduct = UserProduct::query()
					->where('product_color_id', $orderItem->product->productColor->id)
					->where('user_id', auth()->id())->first();
				*/

				$userProduct = UserProduct::query()->find($orderItem->user_product_id);

				//dd($userProduct);

				$newStock = $userProduct->stock + $product['qty'];
				$userProduct->update(['stock' => $newStock]);

                // $orderItem->update(['qty' => $orderItem->qty - $product['qty']]);
				
				$new_qty = $orderItem->qty - $product['qty'];

                $orderItem->update([
                    'qty' => $new_qty
                ]);

				if(auth()->user()->country_id == User::COUNTRY_UAE)
					$rateAux = Currency::where('name','aed')->first()->rate;
				else
					$rateAux = 1;

				$productPrice = $product['price'] / $rateAux; // Price in $

				$refund = new Refund([
					'order_item_id' => $product['product_id'],
					'qty' => $product['qty'],
					'item_price' => $productPrice,
					'total_price' => $productPrice * $product['qty'],
                    'total_price_paid' => $productPrice * $product['qty'] * $rate,
					'item_barcode' => $itemBarcode,
					'order_barcode' => $orderBarcode,
				]);
				$refund->save();
				$total += ($product['price'] * $product['qty'])/$rate;
				
				/////////////////////////////////////////////////////////////
                /////////////////////////////////////////////////////////////

                $order      = $orderItem->order;
                $order_item = $orderItem;
                $item_price = $productPrice;

                // Track this order as touched
                if ($order && !in_array($order->id, $touchedOrderIds)) {
                    $touchedOrderIds[] = $order->id;
                }

                if($userProduct) {

                    if($userProduct->user) {

                        if($userProduct->user->enable_tax == 'yes') {

                            $tax_ratio             = $userProduct->user->tax_ratio;
                            $order_total_tax_ratio = $tax_ratio;

                            if($order->order_type == 'complex_from_multi') {

                                $price_without_tax = $item_price;
                                $tax_value         = ($price_without_tax * ($tax_ratio / 100));
                                $price_with_vat    = $price_without_tax + $tax_value;

                            } else {
                                $price_without_tax = $item_price / (1 + ($tax_ratio / 100) );
                                $tax_value         = $item_price - $price_without_tax;
                                $price_with_vat    = $item_price;
                            }

                            $item_price_paid        = $price_with_vat * $order->curr_rate;
                            $total_price_paid       = $new_qty * $price_with_vat * $order->curr_rate;
                            $tax_value_paid         = $tax_value * $order->curr_rate;
                            $price_without_tax_paid = $price_without_tax * $order->curr_rate;

                            $order_item->update([
                                'tax_ratio'              => $tax_ratio,
                                'tax_value'              => $new_qty > 0 ? $tax_value : 0,
                                'price_without_tax'      => $new_qty > 0 ? $price_without_tax : 0,
                                'total_price'            => $price_with_vat *  $new_qty,
                                'item_price'             => $price_with_vat,
                                'item_price_paid'        => ($item_price_paid),
                                'total_price_paid'       => ($total_price_paid),
                                'tax_value_paid'         => $new_qty > 0 ? ($tax_value_paid) : 0,
                                'price_without_tax_paid' => $new_qty > 0 ? ($price_without_tax_paid) : 0,
                            ]);
                        }
                    }
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

        $oldCredit = auth()->user()->wallet? auth()->user()->wallet->credit : 0;
        Wallet::query()->updateOrCreate(
            ['user_id' => auth()->id()],
            ['credit' => $oldCredit - $total, 'user_id' => auth()->id()]
        );

        return $refund;
    }

    public function getRefunds(Request $request)
    {
        $refunds = Refund::query()->with(['orderItem']);
        $country = auth()->user()->country_id;
        $refunds->whereHas('orderItem', function ($query) use ($country){
            $query->whereHas('product', function ($q) use ($country){
                $q->where('country_id',$country);
            });
        });
        if ($search = $request->get('search'))
            $refunds->where('item_barcode', 'LIKE', "%$search%")
                ->orWhere('order_barcode', 'LIKE', "%$search%");

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

        $totalAmount = $refunds->sum('refunds.total_price');

        return [
            'refunds' => $refunds->paginate(10),
            'total'   => ($totalAmount)
        ];
    }

}

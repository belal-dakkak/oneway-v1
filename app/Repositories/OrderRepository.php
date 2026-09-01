<?php



namespace App\Repositories;

use App\Http\Traits\ReceiptTrait;
use App\Jobs\NotificationOrderJob;
use App\Models\ClientDebit;
use App\Models\ClientDebitLog;
use App\Models\Currency;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\Refund;
use App\Models\User;
use App\Models\UserProduct;
use App\Models\WebsiteOrder;
use App\Models\WebsiteOrderItem;
use App\Models\Wallet;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Psr\Log\LogLevel;
use Illuminate\Support\Facades\Cache;

class OrderRepository
{
    private function clearHomeCache()
    {
        Cache::forget('home_new_products_1');
        Cache::forget('home_new_products_2');
        Cache::forget('home_offer_products_1');
        Cache::forget('home_offer_products_2');
        Cache::forget('home_random_products_1');
        Cache::forget('home_random_products_2');
    }
    use ReceiptTrait;


    public function add(Request $request)
    {
        try {
            DB::beginTransaction();
            $orderCash = $request->get('type') == Order::TYPE_CASH;
            $payment = $request->get('payment') ? $request->get('payment')['value']:0;

            generate:
            $barcode = generateRandomNumber(10);
            if (Order::query()->where('barcode', $barcode)->exists())
                goto generate;

            $totalPriceBeforeDisc = number_format((float)$request->get('total_price_before_discount'), 2, '.', '');

            $order_total_tax_ratio         = 0;

            if ($orderCash) {

                // new code
				$userId = $request->get('user') ? $request->get('user')['id'] : null;
                //$userId = auth()->id();

                $order = new Order([
					'order_type' => $request->get('order_type'),
                    'notes' => $request->get('notes'),
                    'trn' => $request->get('trn'),
                    'seller_id' => auth()->id(),
                    'type' => Order::TYPE_CASH,
					'buyer_id' => $userId,  // new code
                    'barcode' => $barcode,
                    'payment_type' => $payment,
                    'curr_type' => $request->get('currency')['value'],
                    'curr_rate' => $request->get('currency')['rate'],
                    'discount' => $request->get('discount', 0),
                    'total_price_before_discount' => $totalPriceBeforeDisc,
                ]);

            } else{


                $userId = $request->get('user') ? $request->get('user')['id'] : null;
                $order = new Order([
					'order_type' => $request->get('order_type'),
                    'notes' => $request->get('notes'),
                    'trn' => $request->get('trn'),
                    'seller_id' => auth()->id(),
                    'type' => $request->get('type') == Order::TYPE_FOR_CLIENT ? Order::TYPE_FOR_CLIENT : Order::TYPE_APP,
                    'buyer_id' => $userId,
                    'shipper_id' => $request->get('shipper') ? $request->get('shipper')['id'] : null,
                    'paid_price' => number_format((float)$request->get('paid_price'), 2, '.', ''),
                    'barcode' => $barcode,
                    'payment_type' => $payment,
                    'curr_type' => $request->get('currency')['value'],
                    'curr_rate' => $request->get('currency')['rate'],
                    'discount' => $request->get('discount', 0),
                    'total_price_before_discount' => $totalPriceBeforeDisc,
                ]);
            }

            $order->save();

            $totalPrice = 0;

            foreach ($request->get('selected_products') as $product){

                $itemTotalPrice = (float)number_format((float)$product['price'] * $product['qty'], 2, '.', '');

                $item_price = $product['price']/$request->get('currency')['rate'];

                $item_price_paid        = $item_price * $order->curr_rate;
                $total_price_paid       = $product['qty'] * $item_price * $order->curr_rate;
                $tax_value_paid         = 0;
                $price_without_tax_paid = $item_price * $order->curr_rate;

                $order_item = $order->items()->create([

                    'user_product_id' => $product['product_id'],
                    'qty' => $product['qty'],
                    'item_price' => $item_price,
                    'total_price' => $itemTotalPrice/$request->get('currency')['rate'],

                    'tax_ratio'              => 0,
                    'tax_value'              => 0,
                    'price_without_tax'      => $item_price,
                    'item_price_paid'        => ($item_price_paid),
                    'total_price_paid'       => ($total_price_paid),
                    'tax_value_paid'         => ($tax_value_paid),
                    'price_without_tax_paid' => ($price_without_tax_paid),
                ]);

                $totalPrice += $itemTotalPrice;

                $userProduct = UserProduct::query()->find($product['product_id']);

                $oldStock = $userProduct->stock;
                $newStock = $oldStock - $product['qty'];
                $userProduct->update(['stock' => $newStock]);

                if($userProduct) {

                    if($userProduct->user) {

                        if($userProduct->user->enable_tax == 'yes') {

                            $tax_ratio             = $userProduct->user->tax_ratio;
                            $order_total_tax_ratio = $tax_ratio;

                            if($request->order_type == 'complex_from_multi') {

                                $price_without_tax = $item_price;
                                $tax_value         = ($price_without_tax * ($tax_ratio / 100));
                                $price_with_vat    = $price_without_tax + $tax_value;

                            } else {
                                $price_without_tax = $item_price / (1 + ($tax_ratio / 100) );
                                $tax_value         = $item_price - $price_without_tax;
                                $price_with_vat       = $item_price;
                            }

                            $item_price_paid        = $price_with_vat * $order->curr_rate;
                            $total_price_paid       = $order_item->qty * $price_with_vat * $order->curr_rate;
                            $tax_value_paid         = $tax_value * $order->curr_rate;
                            $price_without_tax_paid = $price_without_tax * $order->curr_rate;

                            $order_item->update([
                                'tax_ratio'              => $tax_ratio,
                                'tax_value'              => $tax_value,
                                'price_without_tax'      => $price_without_tax,
                                'total_price'            => $price_with_vat *  $order_item->qty,
                                'item_price'             => $price_with_vat,
                                'item_price_paid'        => ($item_price_paid),
                                'total_price_paid'       => ($total_price_paid),
                                'tax_value_paid'         => ($tax_value_paid),
                                'price_without_tax_paid' => ($price_without_tax_paid),
                            ]);


                        }
                    }
                }
            }



            $shippingFee = $request->get('enable_shipping') ? (float)$request->get('shipping_fee', 0) : 0;
            $codFeePercentage = $request->get('enable_cod') ? (float)$request->get('cod_fee', 0) : 0;
            $codFee = $codFeePercentage > 0 ? ($totalPrice * ($codFeePercentage / 100)) : 0;
            
            $totalPriceAfterDiscount = number_format((float)$totalPrice - $request->get('discount', 0) + $shippingFee + $codFee, 2, '.', '');
            //        $receipt = $this->generatePDF($order->id);
            if($request->has('paid_price'))
                $paidPrice = number_format((float)$request->get('paid_price'), 2, '.', '');
            else
                $paidPrice = number_format((float)$totalPriceAfterDiscount, 2, '.', '');
            // return ['paid_price' => $paidPrice,
            //         'total_price' => $totalPriceAfterDiscount,
            //         'remain_price' => 0,
            //         'credit' => auth()->user()->wallet->credit + ($paidPrice/$request->get('currency')['rate']),
            //         'credit1' => auth()->user()->wallet->credit
            //     ];


            if ($orderCash)
                $order->update([
                    'paid_price' => $paidPrice,
                    'total_price' => $totalPriceAfterDiscount,
                    'remain_price' => 0,
                    'shipping_fee' => $shippingFee,
                    'cod_fee' => $codFee,
                ]);
            else{
                $remainPrice = $totalPriceAfterDiscount - $paidPrice;
                $order->update([
                    'paid_price' => $paidPrice,
                    'total_price' => $totalPriceAfterDiscount,
                    'remain_price' => $remainPrice,
                    'shipping_fee' => $shippingFee,
                    'cod_fee' => $codFee,
                ]);

            //    if ($remainPrice > 0){
            //        if (!$userId)
            //            throw new Exception('رجاءً اختر الزبون عند إنشاء طلبية بالدين');

            //        $clientDebit = ClientDebit::query()->firstOrCreate([
            //            'creditor_id' => auth()->id(),
            //            'debtor_id' => $userId
            //        ],[
            //            'creditor_id' => auth()->id(),
            //            'debtor_id' => $userId,
            //            'amount' => $remainPrice/$request->get('currency')['rate']
            //        ]);

            //        $am = number_format((float)$remainPrice/$request->get('currency')['rate'], 2, '.', '');

            //        $clientName = $clientDebit->debtor->name;
            //        $totalQty = $order->items()->sum('qty');
            //        $shopName = $clientDebit->creditor->name;

            //        $note = "فام الزبون $clientName بشراء $totalQty قطع بسعر $totalPriceAfterDiscount ".$request->get('currency')['name']." من المحل $shopName";
            //        $note .= " وقام بدفع $paidPrice ".$request->get('currency')['name']." والباقي $remainPrice ".$request->get('currency')['name'];


            //        if ($clientDebit->debtor->wallet->credit > 0) {
            //            if ($clientDebit->debtor->wallet->credit > $am) {
            //                $rm = $clientDebit->debtor->wallet->credit - $am;
            //                $clientDebit->debtor->wallet->update(['credit' => $rm, 'debit' => 0]);

            //            } else {
            //                $rm = $am - $clientDebit->debtor->wallet->credit;
            //                $clientDebit->debtor->wallet->update(['debit' => $rm, 'credit' => 0]);
            //            }
            //            $rmCurrency = number_format((float)$rm * $request->get('currency')['rate'], 2, '.', '');
            //            $note .= " وتم خصم مبلغ $rmCurrency من الرصيد";
            //            if (!$clientDebit->wasRecentlyCreated){
            //                $clientDebit->update(['amount' => $clientDebit->debtor->wallet->credit - $clientDebit->debtor->wallet->debit]);
            //            }
            //        }else {
            //            $rm = $am;
            //            if (!$clientDebit->wasRecentlyCreated){
            //                $clientDebit->update(['amount' => DB::raw("amount + ".$rm)]);
            //            }
            //            $clientDebit->debtor->wallet->update(['debit' => DB::raw("debit + ".$rm)]);
            //        }

            //        ClientDebitLog::query()->create([
            //            'client_debit_id' => $clientDebit->id,
            //            'order_id' => $order->id,
            //            'amount' => $rm,
            //            'note' => $note
            //        ]);
            //    }
            }

            if($request->order_type == 'complex_from_multi') {

                $order_total_price_without_tax = $order->total_price;
                $order_total_tax_value         = ($order_total_price_without_tax * $order_total_tax_ratio) / 100;
                $order_total_price             = $order_total_price_without_tax + $order_total_tax_value;

            } else {

                $order_total_price       = $order->total_price;
                $order_total_price_without_tax = $order_total_price / (1 + ($order_total_tax_ratio / 100) );
                $order_total_tax_value         = $order_total_price - $order_total_price_without_tax;

                // $order_total_tax_value        = ($order_total_price  * $order_total_tax_ratio) / 100;
                // $order_total_price_without_tax = $order_total_price - $order_total_tax_value;

            }

            $calc_remain_price = $order_total_price - $order->paid_price;
            $calc_total_price  = $order_total_price;
            $calc_paid_price   = $order->paid_price;

            $order->update([
                'tax_ratio'         => $order_total_tax_ratio,
                'tax_value'         => $order_total_tax_value,
                'price_without_tax' => $order_total_price_without_tax,
                'total_price'       => $order_total_price,
                'remain_price'       => $calc_remain_price,
            ]);


            // start new code

            if (! $orderCash && $calc_remain_price > 0) {

                if (!$userId)
                       throw new Exception('رجاءً اختر الزبون عند إنشاء طلبية بالدين');

                $clientDebit = ClientDebit::query()->firstOrCreate([
                    'creditor_id' => auth()->id(),
                    'debtor_id' => $userId
                ],[
                    'creditor_id' => auth()->id(),
                    'debtor_id' => $userId,
                    'amount' => $calc_remain_price/$request->get('currency')['rate']
                ]);

                $am = number_format((float)$calc_remain_price/$request->get('currency')['rate'], 2, '.', '');

                $clientName = $clientDebit->debtor->name;
                $totalQty = $order->items()->sum('qty');
                $shopName = $clientDebit->creditor->name;

                $note = "فام الزبون $clientName بشراء $totalQty قطع بسعر $calc_total_price ".$request->get('currency')['name']." من المحل $shopName";
                $note .= " وقام بدفع $calc_paid_price ".$request->get('currency')['name']." والباقي $calc_remain_price ".$request->get('currency')['name'];


                if ($clientDebit->debtor->wallet->credit > 0) {
                    if ($clientDebit->debtor->wallet->credit > $am) {
                        $rm = $clientDebit->debtor->wallet->credit - $am;
                        $clientDebit->debtor->wallet->update(['credit' => $rm, 'debit' => 0]);

                    } else {
                        $rm = $am - $clientDebit->debtor->wallet->credit;
                        $clientDebit->debtor->wallet->update(['debit' => $rm, 'credit' => 0]);
                    }
                    $rmCurrency = number_format((float)$rm * $request->get('currency')['rate'], 2, '.', '');
                    $note .= " وتم خصم مبلغ $rmCurrency من الرصيد";
                    if (!$clientDebit->wasRecentlyCreated){
                        $clientDebit->update(['amount' => $clientDebit->debtor->wallet->credit - $clientDebit->debtor->wallet->debit]);
                    }
                }else {
                    $rm = $am;
                    if (!$clientDebit->wasRecentlyCreated){
                        $clientDebit->update(['amount' => DB::raw("amount + ".$rm)]);
                    }
                    $clientDebit->debtor->wallet->update(['debit' => DB::raw("debit + ".$rm)]);
                }

                ClientDebitLog::query()->create([
                    'client_debit_id' => $clientDebit->id,
                    'order_id' => $order->id,
                    'amount' => $rm,
                    'note' => $note
                ]);

            }

            // end new code


            Wallet::query()->updateOrCreate([
                'user_id' => auth()->id()
            ],[
                'credit' => auth()->user()->wallet->credit + ($paidPrice/$request->get('currency')['rate']),
                'user_id' => auth()->id()
            ]);
        }catch (Exception $exception){
            DB::rollBack();
            Log::error($exception->getLine().'-'.$exception->getMessage());
            return false;
        }

        DB::commit();
        $this->clearHomeCache();
        return $order;

    }

    public function addForOnline(Request $request)
    {
        try {
            DB::beginTransaction();

            generate:
            $barcode = generateRandomNumber(10);
            if (WebsiteOrder::query()->where('barcode', $barcode)->exists())
                goto generate;


            $userId = auth()->id();
            $countryCode = Session::get('country');
            $countryId = $countryCode === 'LB' ? User::COUNTRY_LB : User::COUNTRY_UAE;

            $order = new WebsiteOrder([
                'notes' => $request->get('notes'),
                'barcode' => $barcode,
                'buyer_id' => $userId,
                'first_name' => $request->get('first_name'),
                'last_name' => $request->get('last_name'),
                'email' => $request->get('email'),
                'phone' => $request->get('phone'),
                'address' => $request->get('address'),
                'city' => $request->get('city'),
                'building_name' => $request->get('building_name'),
                'flat_number' => $request->get('flat_number'),
                'total_price_before_discount' => 0,
                'discount' => 0,
                'invoice' => $request->get('tap_id'),
                'payment_type' => $request->get('payment')['name'],
                // Use proper currency rate: 1 for LBP, 3.67 for AED
                'curr_rate' => Session::get('country') == 'LB' ? 1 : 3.67,
                'country_id' => $countryId,
                'status' => $request->get('payment')['name'] === 'card' ? WebsiteOrder::STATUS_UNPAID : WebsiteOrder::STATUS_PENDING,
            ]);

            $order->save();

            $totalPrice = 0;
            $totalPriceBeforeDiscount = 0;
            $isLebanon = Session::get('country') === 'LB';

            foreach ($request->get('items') as $product){
                $productObj = ProductColor::query()->where('id', $product['product_id'])->first();

                $itemTotalPrice = $isLebanon 
                    ? round($productObj->product->retail_price * $order->curr_rate * $product['qty'], 2)
                    : ceil($productObj->product->retail_price * $order->curr_rate * $product['qty']);

                $itemTotalPriceBeforeDiscount = $isLebanon
                    ? round($productObj->product->price_before_discount * $order->curr_rate * $product['qty'], 2)
                    : ceil($productObj->product->price_before_discount * $order->curr_rate * $product['qty']);

                $order->items()->create([
                    'product_color_id' => $productObj->id,
                    'qty' => $product['qty'],
                    'item_price' => $isLebanon
                        ? round($productObj->product->retail_price * $order->curr_rate, 2)
                        : ceil($productObj->product->retail_price * $order->curr_rate),
                    'total_price' => $isLebanon ? round($itemTotalPrice, 2) : ceil($itemTotalPrice),
                    'item_price_before_discount' => $isLebanon
                        ? round($productObj->product->price_before_discount * $order->curr_rate, 2)
                        : ceil($productObj->product->price_before_discount * $order->curr_rate),
                    'total_price_before_discount' => $isLebanon ? round($itemTotalPriceBeforeDiscount, 2) : ceil($itemTotalPriceBeforeDiscount),
                    'size' => $product['size']??null
                ]);

                $totalPrice += $itemTotalPrice;
                $totalPriceBeforeDiscount += $itemTotalPriceBeforeDiscount;
            }

            $discount = $isLebanon
                ? round($totalPriceBeforeDiscount - $totalPrice, 2)
                : ceil($totalPriceBeforeDiscount - $totalPrice);

            $paymentType = $request->get('payment')['name'];

            // Calculate shipping fee based on country
            if (Session::get('country') == 'LB') {
                // Lebanon: $5 for orders < $50, free for orders >= $50
                $shippingFee = ($totalPrice < 50) ? 5 : 0;
                $codFee = ($paymentType === 'cod') ? round($totalPrice * 0.10, 2) : 0;
            } else {
                // UAE: 20 AED for orders < 150 AED, free for orders >= 150 AED
                $shippingFee = ($totalPrice < 150) ? 20 : 0;
                $codFee = ($paymentType === 'cod') ? ceil($totalPrice * 0.10) : 0;
            }

            $finalTotal = $totalPrice + $shippingFee + $codFee;

            $order->update([
                'paid_price' => $finalTotal,
                'total_price' => $finalTotal,
                'remain_price' => 0,
                'discount' => $discount,
                'total_price_before_discount' => $totalPriceBeforeDiscount,
                'shipping_fee' => $shippingFee,
                'cod_fee' => $codFee,
            ]);



        }catch (Exception $exception){
            DB::rollBack();
            Log::error($exception->getMessage());
            return $exception->getMessage();
        }

        DB::commit();
        $this->clearHomeCache();
        return $order;

    }

    public function update(Request $request, Order $order)
    {
        try {
            DB::beginTransaction();

            $orderCash = $request->get('type') == Order::TYPE_CASH;

            $orderOldPrice = $orderCash ? $order->total_price : $order->paid_price;

            $order_total_tax_ratio         = 0;

            /* if ($orderCash) */
            /*     $order->update([ */
            /*         'discount' => $request->get('discount', 0), */
            /*         'total_price_before_discount' => $request->get('total_price_before_discount'), */
            /*         'paid_price' => $request->get('paid_price'), */
            /*     ]); */
            /* else{ */
            /*     $order->update([ */
            /*         'buyer_id' => $request->get('user') ? $request->get('user')['id'] : null, */
            /*         'shipper_id' => $request->get('shipper') ? $request->get('shipper')['id'] : null, */
            /*         'paid_price' => $request->get('paid_price'), */
            /*         'discount' => $request->get('discount', 0), */
            /*         'total_price_before_discount' => $request->get('total_price_before_discount'), */
            /*     ]); */
            /* } */


            if(auth()->user()->country_id == User::COUNTRY_UAE)
                $rate = Currency::where('name','aed')->first()->rate;
            else
                $rate = 1;

            $totalPrice = 0;
            Log::log(LogLevel::INFO, $request->get('selected_products'));

            foreach ($request->get('selected_products') as $product) {

                if (isset($product['product_id'])){

                    $userProduct = UserProduct::query()->find($product['product_id']);

                    $product['price'] /= $rate;

                    $item_price = $product['price'];

                    $itemTotalPrice = $product['price'] * $product['qty'];

                    $orderItem = OrderItem::query()
                        ->where('order_id', $order->id)
                        ->where('user_product_id', $product['product_id'])
                        ->first();

                    $oldStock = $userProduct->stock;

                    if (!$orderItem){

                        $item_price_paid        = $product['price'] * $order->curr_rate;
                        $total_price_paid       = $product['qty'] * $product['price'] * $order->curr_rate;
                        $tax_value_paid         = 0;
                        $price_without_tax_paid = $item_price_paid;

                        $orderItem = OrderItem::query()->create([
                            'user_product_id' => $product['product_id'],
                            'qty' => $product['qty'],
                            'item_price' => $product['price'],
                            'total_price' => $itemTotalPrice,
                            'order_id' => $order->id,

                            'tax_ratio'              => 0,
                            'tax_value'              => 0,
                            'price_without_tax'      => $product['price'],

                            'item_price_paid'        => ($item_price_paid),
                            'total_price_paid'       => ($total_price_paid),
                            'tax_value_paid'         => ($tax_value_paid),
                            'price_without_tax_paid' => ($price_without_tax_paid),

                        ]);

                        $newStock = $oldStock - $product['qty'];
                        $userProduct->update(['stock' => $newStock]);

                    } else {

                        $oldQty = $orderItem->qty;

                        $item_price_paid        = $product['price'] * $order->curr_rate;
                        $total_price_paid       = $product['qty'] * $product['price'] * $order->curr_rate;
                        $tax_value_paid         = 0;
                        $price_without_tax_paid = $item_price_paid;

                        $orderItem->update([
                            'qty' => $product['qty'],
                            'item_price' => $product['price'],
                            'total_price' => $itemTotalPrice,

                            'tax_ratio'              => 0,
                            'tax_value'              => 0,
                            'price_without_tax'      => $product['price'],

                            'item_price_paid'        => ($item_price_paid),
                            'total_price_paid'       => ($total_price_paid),
                            'tax_value_paid'         => ($tax_value_paid),
                            'price_without_tax_paid' => ($price_without_tax_paid),
                        ]);

                        Log::log(LogLevel::INFO, $oldStock);
                        Log::log(LogLevel::INFO, $oldQty);
                        Log::log(LogLevel::INFO, $product['qty']);

                        if ($product['qty'] > $oldQty){
                            $newStock = $oldStock - ($product['qty'] - $oldQty);
                            $userProduct->update(['stock' => $newStock]);
                        } elseif($oldQty > $product['qty']){
                            $newStock = $oldStock + ($oldQty - $product['qty']);
                            $userProduct->update(['stock' => $newStock]);
                        }
                    }

                    $totalPrice += $itemTotalPrice;

                    ///////////////////////////////// new code ///////////////////////////

                    if($userProduct) {

                        if($userProduct->user) {

                            if($userProduct->user->enable_tax == 'yes') {

                                $tax_ratio             = $userProduct->user->tax_ratio;
                                $order_total_tax_ratio = $tax_ratio;

                                if($request->order_type == 'complex_from_multi') {

                                    $price_without_tax = $item_price;
                                    $tax_value         = ($price_without_tax * ($tax_ratio / 100));
                                    $price_with_vat    = $price_without_tax + $tax_value;

                                } else {
                                    $price_without_tax = $item_price / (1 + ($tax_ratio / 100) );
                                    $tax_value         = $item_price - $price_without_tax;
                                    $price_with_vat       = $item_price;
                                }


                                $item_price_paid        = $price_without_tax * $order->curr_rate;
                                $total_price_paid       = $price_with_vat * $order->curr_rate;
                                $tax_value_paid         = $tax_value * $order->curr_rate;
                                $price_without_tax_paid = $price_without_tax * $order->curr_rate;

                                $orderItem->update([
                                    'tax_ratio'              => $tax_ratio,
                                    'tax_value'              => $tax_value,
                                    'price_without_tax'      => $price_without_tax,
                                    'total_price'            => $orderItem->qty * $price_with_vat,
                                    'item_price'             => $price_with_vat,
                                    'item_price_paid'        => ($item_price_paid),
                                    'total_price_paid'       => ($orderItem->qty * $total_price_paid),
                                    'tax_value_paid'         => ($tax_value_paid),
                                    'price_without_tax_paid' => ($price_without_tax_paid),
                                ]);

                            }
                        }
                    }

                    ///////////////////////////////// end new code ///////////////////////////

                }
            }

            $discount = $request->get('discount', 0) / $rate;

            $shippingFee = $request->get('enable_shipping') ? (float)$request->get('shipping_fee', 0) : 0;
            $codFeePercentage = $request->get('enable_cod') ? (float)$request->get('cod_fee', 0) : 0;
            $codFee = $codFeePercentage > 0 ? ($totalPrice * ($codFeePercentage / 100)) : 0;

            $totalPriceAfterDiscount = $totalPrice - $discount + $shippingFee + $codFee;

            // $paidPrice = $request->get('paid_price');
            $paidPrice   = !is_null($request->get('paid_price')) ? ($request->get('paid_price') / $rate) : ($totalPriceAfterDiscount);

            $remainPrice = currencyExchange($totalPriceAfterDiscount - $paidPrice, $rate);
            $paidPrice   = currencyExchange($paidPrice, $rate);

            if ($orderCash)
                $order->update([
                    'discount'                    => $request->get('discount', 0),
                    'total_price_before_discount' => $request->get('total_price_before_discount'),
                    'paid_price'                  => $paidPrice,
                    'total_price'                 => currencyExchange($totalPriceAfterDiscount, $rate),
                    'remain_price'                => $remainPrice,
                    'shipping_fee'                => $shippingFee,
                    'cod_fee'                     => $codFee,
                ]);
            else {
                $order->update([
                    'buyer_id'                    => $request->get('user') ? $request->get('user')['id'] : null,
                    'shipper_id'                  => $request->get('shipper') ? $request->get('shipper')['id'] : null,
                    'discount'                    => $request->get('discount', 0),
                    'total_price_before_discount' => $request->get('total_price_before_discount'),
                    'paid_price'                  => $paidPrice,
                    'total_price'                 => currencyExchange($totalPriceAfterDiscount, $rate),
                    'remain_price'                => $remainPrice,
                    'shipping_fee'                => $shippingFee,
                    'cod_fee'                     => $codFee,
                ]);

                // new code for if
                //if ($remainPrice > 0){

                    // old code
                    // $clientDebit = ClientDebit::query()
                    //     ->where('creditor_id', auth()->id())
                    //     ->where('debtor_id', $order->buyer_id)
                    //     ->first();

					 if ($remainPrice > 0){
						// new code
						$clientDebit = ClientDebit::query()->firstOrCreate([
								'creditor_id' => auth()->id(),
								'debtor_id' => $order->buyer_id
                        ],[
                            'creditor_id' => auth()->id(),
                            'debtor_id' => $order->buyer_id,
                            //'amount' => $remainPrice/$request->get('currency')['rate'],
                            'amount' => $remainPrice
                        ]);

					 } else {

					 	 $clientDebit = ClientDebit::query()
                          ->where('creditor_id', auth()->id())
                          ->where('debtor_id', $order->buyer_id)
                          ->first();
					 }

                    $clientName = $clientDebit != null ?  ($clientDebit->debtor != null ? $clientDebit->debtor->name : '') : '';
                    $totalQty = $order->items()->sum('qty');
                    $shopName = $clientDebit != null ?  ($clientDebit->creditor != null ? $clientDebit->creditor->name : '') : '';;
                    $note = "فام الزبون $clientName بشراء $totalQty قطع بسعر $totalPriceAfterDiscount من المحل $shopName";
                    $note .= " وقام بدفع $paidPrice والباقي $remainPrice";

                    $log = ClientDebitLog::query()
                        ->where('client_debit_id', ($clientDebit != null ? $clientDebit->id : 0))
                        ->where('order_id', $order->id)
                        ->first();
                    if($log){
                        if ($log->amount > $totalPriceAfterDiscount){
                            $newPrice = (int)ceil($log->amount - $totalPriceAfterDiscount/$order->curr_rate);

                            $log->update(['amount' => DB::raw("amount - $newPrice"), 'note' => $note]);

                            if($clientDebit) {
                                $clientDebit->update(['amount' => DB::raw("amount - $newPrice")]);
                                $clientDebit->debtor->wallet->update(['debit' => DB::raw("debit - $newPrice")]);
                            }

                        } elseif($log->amount < $totalPriceAfterDiscount){
                            $newPrice = (int)ceil($totalPriceAfterDiscount - $log->amount);

                            $log->update(['amount' => DB::raw("amount + $newPrice"), 'note' => $note]);

                            if($clientDebit) {
                                $clientDebit->update(['amount' => DB::raw("amount + $newPrice")]);
                                $clientDebit->debtor->wallet->update(['debit' => DB::raw("debit + $newPrice")]);
                            }
                        }
                    }


                    $debtor = $clientDebit != null ? $clientDebit->debtor()->get()->first() : null;
                    $debt   = $totalPriceAfterDiscount - currencyExchange($paidPrice, 1 / $rate);

                    if($clientDebit) {
                        $clientDebit->update([
                            'amount' => $debt
                        ]);
                    }


					if($debtor != null) {
						Wallet::query()->updateOrCreate([
							'user_id' => $debtor != null ? $debtor->id : 0
						],[
							'debit' => $debt,
							'user_id' => $debtor != null ? $debtor->id : 0
						]);
					}

                //} // end // new code for if
            }

            ///////////////////////////////////////////////////////////////

            if($request->order_type == 'complex_from_multi') {

                $order_total_price_without_tax = $order->total_price;
                $order_total_tax_value         = ($order_total_price_without_tax * $order_total_tax_ratio) / 100;
                $order_total_price             = $order_total_price_without_tax + $order_total_tax_value;

            } else {

                $order_total_price       = $order->total_price;
                $order_total_price_without_tax = $order_total_price / (1 + ($order_total_tax_ratio / 100) );
                $order_total_tax_value         = $order_total_price - $order_total_price_without_tax;

                // $order_total_tax_value        = ($order_total_price  * $order_total_tax_ratio) / 100;
                // $order_total_price_without_tax = $order_total_price - $order_total_tax_value;

            }

            $calc_remain_price = $order_total_price - $order->paid_price;
            $calc_total_price  = $order_total_price;
            $calc_paid_price   = $order->paid_price;

            $order->update([
                'tax_ratio'         => $order_total_tax_ratio,
                'tax_value'         => $order_total_tax_value,
                'price_without_tax' => $order_total_price_without_tax,
                'total_price'       => $order_total_price,
                'remain_price'       => $calc_remain_price,
            ]);



            ///////////////////////////////////////////////////////////////

            if ($paidPrice > $orderOldPrice)
                Wallet::query()->updateOrCreate([
                    'user_id' => auth()->id()
                ],[
                    'credit' => auth()->user()->wallet->credit + ($paidPrice - $orderOldPrice) / $rate,
                    'user_id' => auth()->id()
                ]);
            else if ($paidPrice < $orderOldPrice)
                Wallet::query()->updateOrCreate([
                    'user_id' => auth()->id()
                ],[
                    'credit' => auth()->user()->wallet->credit - ($orderOldPrice - $paidPrice)/ $rate,
                    'user_id' => auth()->id()
                ]);
            DB::commit();
            $this->clearHomeCache();
            return $order;

        }catch (Exception $exception){
            dd($exception->getMessage());
            DB::rollBack();
            Log::error($exception->getLine().'-'.$exception->getMessage());
            return false;
        }

        DB::commit();
        return $order;
    }

    private function returnRefoundOrdersArr($request, $debts = false, $profits = false, $with = null,$pagination = true,$withItems = true) {

        if (!$with)
            if($withItems)
                $with = ['buyer', 'productItems','seller', 'shipper', 'items.product.productColor'];
            else
                $with = ['buyer', 'productItems','seller', 'shipper'];

            // $with = ['buyer', 'productItems','seller', 'shipper', 'items.product.productColor'];

        $orders = Order::query()->with($with);
        $country = auth()->user()->country_id;
        $orders->whereHas('seller', function ($query) use ($country){
            $query->where('country_id',$country);
        });
        if (auth()->user()->role_id != User::ROLE_ADMIN)
            $orders->where('seller_id', auth()->id());

        if ($debts)
            $orders->where('remain_price', '>', 0);

        if ($type = $request->get('type'))
            $orders->where('type', $type);

        if ($search = $request->get('search'))
            $orders->where('barcode', 'LIKE', "%$search%");

        if ($buyerSearch = $request->query('buyerName'))
        {
            $orders->join('users', 'orders.buyer_id', '=', 'users.id')
            ->where('users.name', 'LIKE', "%{$buyerSearch}%");
        }

        if ($buyerId = $request->get('buyer_id'))
            $orders->where('buyer_id',"$buyerId");

        if ($buyerId = $request->get('buyer'))
            $orders->where('buyer_id',"$buyerId");

        if ($sellerId = $request->get('seller_id'))
            $orders->where('seller_id',"$sellerId");

        if ($sellerId = $request->get('shop'))
            $orders->where('seller_id',"$sellerId");

        if ($shipperId = $request->get('shipper_id'))
            $orders->where('shipper_id',"$shipperId");


        if ($request->has(['field', 'direction'])){
            $field = $request->get('field');
            $direction = $request->get('direction');

            $sortableArray = app(Order::class)->getFillable();
            $sortableArray[] = 'id';
            if(in_array($field, $sortableArray)){
                $orders->orderBy($field, $direction);
            }else{
                $orders->orderByDesc('orders.id');
            }
        }else{
            $orders->orderByDesc('orders.id');
        }

        $ids = $orders->pluck('orders.id');

        return $ids;
    }


    public function getOrders(Request $request, $debts = false, $profits = false, $with = null,$pagination = true,$withItems = true)
    {
        if ($request->get('order_type') === 'website') {
            return $this->getWebsiteOrders($request, $pagination);
        }
        //info($request->all());
        // dd($profits);

        if (!$with)
            if($withItems)
                $with = ['buyer', 'productItems','seller', 'shipper', 'items.product.productColor'];
            else
                $with = ['buyer', 'productItems','seller', 'shipper'];

            // $with = ['buyer', 'productItems','seller', 'shipper', 'items.product.productColor'];

        $orders = Order::query()->with($with);
        $country = auth()->user()->country_id;

        $orders->whereHas('seller', function ($query) use ($country){
            $query->where('country_id',$country);
        });
        if (auth()->user()->role_id != User::ROLE_ADMIN)
            $orders->where('seller_id', auth()->id());

        if ($debts)
            $orders->where('remain_price', '>', 0);

        if ($type = $request->get('type'))
            $orders->where('type', $type);

        if ($order_type = $request->get('order_type'))
            $orders->where('order_type', $order_type);

        if ($search = $request->get('search'))
            $orders->where('barcode', 'LIKE', "%$search%");

        if ($buyerSearch = $request->query('buyerName'))
        {
            $orders->join('users', 'orders.buyer_id', '=', 'users.id')
            ->where('users.name', 'LIKE', "%{$buyerSearch}%");
        }

        if ($buyerId = $request->get('buyer_id'))
            $orders->where('buyer_id',"$buyerId");

        if ($buyerId = $request->get('buyer'))
            $orders->where('buyer_id',"$buyerId");

        if ($sellerId = $request->get('seller_id'))
            $orders->where('seller_id',"$sellerId");

        if ($params = $request->get('params')) {

            $arr = json_decode($params,true);

            if(array_key_exists('shop',$arr)) {
                $orders->where('seller_id',array_key_exists('shop',$arr) ? $arr['shop'] : null);
            } elseif(array_key_exists('start_date',$arr)) {
                $orders->whereDate('orders.created_at', '>=', Carbon::parse($arr['start_date']));
            } elseif(array_key_exists('end_date',$arr)) {
                $orders->whereDate('orders.created_at', '<=', Carbon::parse($arr['end_date']));
            } elseif(array_key_exists('date',$arr)) {
                $orders->whereDate('orders.created_at', Carbon::parse($arr['date']));
            }
        }


        if ($sellerId = $request->get('shop'))
            $orders->where('seller_id',"$sellerId");

        if ($shipperId = $request->get('shipper_id'))
            $orders->where('shipper_id',"$shipperId");

        if ($date = $request->get('date'))
            $orders->whereDate('orders.created_at', Carbon::parse($date));

        if ($startDate = $request->get('start_date'))
            $orders->whereDate('orders.created_at', '>=', Carbon::parse($startDate));

        if ($endDate = $request->get('end_date'))
            $orders->whereDate('orders.created_at', '<=', Carbon::parse($endDate));

        if ($request->has(['field', 'direction'])){
            $field = $request->get('field');
            $direction = $request->get('direction');

            $sortableArray = app(Order::class)->getFillable();
            $sortableArray[] = 'id';
            if(in_array($field, $sortableArray)){
                $orders->orderBy($field, $direction);
            }else{
                $orders->orderByDesc('orders.id');
            }
        }else{
            $orders->orderByDesc('orders.id');
        }

        if(auth()->user()->country_id == User::COUNTRY_UAE)
            $rate = Currency::where('name','aed')->first()->rate;
        else
            $rate = 1;

        // Calculate total refunds
        $ids = $orders->pluck('orders.id');

        $ids2 = $this->returnRefoundOrdersArr($request, $debts = false, $profits = false, $with = null,$pagination = true,$withItems = true);

        $totalAmount  = $orders->sum('orders.total_price');

        ///////////////////////////////////////////////////////////////////////////////////////////////////

        $totalRefunds = Refund::query()
        ->when($request->date,function($q) use($request) {
            $q->whereDate('refunds.created_at', Carbon::parse($request->date));
        })->when($request->start_date,function($q) use($request) {
            $q->whereDate('refunds.created_at', '>=', Carbon::parse($request->start_date));
        })->when($request->end_date,function($q) use($request) {
            $q->whereDate('refunds.created_at', '<=', Carbon::parse($request->end_date));
        })
        ->join('order_items', 'order_item_id', '=', 'order_items.id')
        ->join('orders', 'order_items.order_id', '=', 'orders.id')
        ->selectRaw('SUM(refunds.total_price) as total')
        ->whereIn('order_items.order_id', $ids2)
        ->get()->first()->total;


        $profit = OrderItem::query()
            ->where('qty','>',0)
            ->join('user_products', 'user_product_id', '=', 'user_products.id')
            ->selectRaw('SUM(total_price) - SUM(user_products.wholesale_price * order_items.qty) as profit')
            ->whereIn('order_id', $ids)
            ->get()->first()->profit;


		$total_of_products_refunds = Refund::query()->when($request->date,function($q) use($request) {
            $q->whereDate('refunds.created_at', Carbon::parse($request->date));
        })->when($request->start_date,function($q) use($request) {
            $q->whereDate('refunds.created_at', '>=', Carbon::parse($request->start_date));
        })->when($request->end_date,function($q) use($request) {
            $q->whereDate('refunds.created_at', '<=', Carbon::parse($request->end_date));
        })
        ->join('order_items', 'order_item_id', '=', 'order_items.id')
		->join('user_products', 'order_items.user_product_id', '=', 'user_products.id')
        ->join('orders', 'order_items.order_id', '=', 'orders.id')
		->selectRaw('SUM(user_products.wholesale_price * refunds.qty) as total')
        ->whereIn('order_items.order_id', $ids2)
        ->get()->first()->total;


        $total_tax_value = OrderItem::query()->selectRaw('SUM(order_items.tax_value_paid * order_items.qty) as total_tax_value')
                ->whereIn('order_id', $ids)
                ->get()->first()->total_tax_value;


        $count = OrderItem::query()->selectRaw('SUM(order_items.qty) as total_count')
                ->whereIn('order_id', $ids)
                ->get()->first()->total_count;

        $calcCount = $count;

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////

        $total_refund = Refund::query()->whereDate('refunds.created_at', $date)
        ->join('order_items', 'order_item_id', '=', 'order_items.id')
        ->join('orders', 'order_items.order_id', '=', 'orders.id')
        ->selectRaw('SUM(refunds.total_price_paid) as total')
        ->when($request->shop,function($query) use($request) {
            $query->whereHas('orderItem', function($orderItem) use($request) {
                $orderItem->whereHas('order',function($order) use($request) {
                    $order->where('seller_id',$request->shop);
                });
            });
        })
        ->get()->first()->total;


        $total_price_without_tax_paid = OrderItem::query()
        ->whereIn('order_id', $ids)
        ->when($request->shop,function($query) use($request) {
            $query->whereHas('order',function($order) use($request){
                $order->where('seller_id',$request->shop);
            });
        })
        ->selectRaw('SUM(order_items.price_without_tax_paid * order_items.qty) as total_price_without_tax_paid')
        ->get()->first()->total_price_without_tax_paid;


        $total_tax_value = OrderItem::query()
        ->whereIn('order_id', $ids)
        ->when($request->shop,function($query) use($request) {
            $query->whereHas('order',function($order) use($request){
                $order->where('seller_id',$request->shop);
            });
        })
        ->selectRaw('SUM(order_items.tax_value_paid * order_items.qty) as total_tax_value')
        ->get()->first()->total_tax_value;

		$calc_val = 0;

		/*
        if($request->shop != null) {
            $new_user = User::findOrFail($request->shop);
			$calc_val =  (($new_user->tax_ratio / 100) * $total_refund);
            $total_tax_value = $total_tax_value - $calc_val;
        }
		*/

        //$total_price_without_tax_paid = $total_price_without_tax_paid - $total_refund;

		$total_price_without_tax_paid = $total_price_without_tax_paid;

        $total_price_with_tax_paid = $total_price_without_tax_paid + $total_tax_value + $calc_val;

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////

        if ($profits) {

			$totalRefunds = $totalRefunds - $total_of_products_refunds;

            $profit -= $totalRefunds;

            return [
                'orders' => $pagination ? $orders->paginate(10) : $orders->get(),
                'profit' => $profit,
                'count' => $calcCount,
                'total' => $total_price_with_tax_paid,
                'total_tax_value' => $total_tax_value,
                'total_price_without_tax' => $total_price_without_tax_paid,
                // 'total_tax_value' => $total_tax_value,
                // 'total_price_without_tax' => round($totalAmount - $total_tax_value,2),
            ];

        } else{

            $totalRefunds = doubleval(currencyExchange($totalRefunds, $rate));

            $totalAmount -= $totalRefunds;

            return [
                'orders' => $pagination ? $orders->paginate(10) : $orders->get(),
                'profit' => $profit,
                'count' => $calcCount,
                'total' => $total_price_with_tax_paid,
                'total_tax_value' => $total_tax_value,
                'total_price_without_tax' => $total_price_without_tax_paid,
                // 'total' => $totalAmount,
                // 'total_tax_value' => $total_tax_value,
                // 'total_price_without_tax' => $totalAmount - $total_tax_value,
            ];
        }

        // $countRefunds = Refund::query()->when($request->date,function($q) use($request) {
        //     $q->whereDate('refunds.created_at', Carbon::parse($request->date));
        // })->when($request->start_date,function($q) use($request) {
        //     $q->whereDate('refunds.created_at', '>=', Carbon::parse($request->start_date));
        // })->when($request->end_date,function($q) use($request) {
        //     $q->whereDate('refunds.created_at', '<=', Carbon::parse($request->end_date));
        // })
        // ->join('order_items', 'order_item_id', '=', 'order_items.id')
        // ->join('orders', 'order_items.order_id', '=', 'orders.id')
        // ->selectRaw('SUM(refunds.qty) as total')
        // ->whereIn('order_items.order_id', $ids2)
        // ->get()->first()->total;

    }


    public function getOrders_v2(Request $request, $debts = false, $profits = false, $with = null,$pagination = true,$withItems = true)
    {
        if (!$with)
            if($withItems)
                $with = ['buyer', 'productItems','seller', 'shipper', 'items.product.productColor'];
            else
                $with = ['buyer', 'productItems','seller', 'shipper'];

            // $with = ['buyer', 'productItems','seller', 'shipper', 'items.product.productColor'];

        $orders = Order::query()->with($with);
        $country = auth()->user()->country_id;
        $orders->whereHas('seller', function ($query) use ($country){
            $query->where('country_id',$country);
        });
        if (auth()->user()->role_id != User::ROLE_ADMIN)
            $orders->where('seller_id', auth()->id());

        if ($debts)
            $orders->where('remain_price', '>', 0);

        if ($type = $request->get('type'))
            $orders->where('type', $type);

        if ($search = $request->get('search'))
            $orders->where('barcode', 'LIKE', "%$search%");

        if ($buyerSearch = $request->query('buyerName'))
        {
            $orders->join('users', 'orders.buyer_id', '=', 'users.id')
            ->where('users.name', 'LIKE', "%{$buyerSearch}%");
        }

        if ($buyerId = $request->get('buyer_id'))
            $orders->where('buyer_id',"$buyerId");

        if ($buyerId = $request->get('buyer'))
            $orders->where('buyer_id',"$buyerId");

        if ($sellerId = $request->get('seller_id'))
            $orders->where('seller_id',"$sellerId");

        if ($sellerId = $request->get('shop'))
            $orders->where('seller_id',"$sellerId");

        if ($shipperId = $request->get('shipper_id'))
            $orders->where('shipper_id',"$shipperId");

        if ($date = $request->get('date'))
            $orders->whereDate('orders.created_at', Carbon::parse($date));

        if ($startDate = $request->get('start_date'))
            $orders->whereDate('orders.created_at', '>=', Carbon::parse($startDate));

        if ($endDate = $request->get('end_date'))
            $orders->whereDate('orders.created_at', '<=', Carbon::parse($endDate));

        if ($request->has(['field', 'direction'])){
            $field = $request->get('field');
            $direction = $request->get('direction');

            $sortableArray = app(Order::class)->getFillable();
            $sortableArray[] = 'id';
            if(in_array($field, $sortableArray)){
                $orders->orderBy($field, $direction);
            }else{
                $orders->orderByDesc('orders.id');
            }
        }else{
            $orders->orderByDesc('orders.id');
        }

        if(auth()->user()->country_id == User::COUNTRY_UAE)
            $rate = Currency::where('name','aed')->first()->rate;
        else
            $rate = 1;

        // Calculate total refunds
        $ids = $orders->pluck('orders.id');

        $ids2 = $this->returnRefoundOrdersArr($request, $debts = false, $profits = false, $with = null,$pagination = true,$withItems = true);

        $totalAmount  = $orders->sum('orders.total_price');

        /////////////////////////////////////////////////////////////////////////////////////////////////////////

        $totalRefunds = Refund::query()
        ->when($request->date,function($q) use($request) {
            $q->whereDate('refunds.created_at', Carbon::parse($request->date));
        })->when($request->start_date,function($q) use($request) {
            $q->whereDate('refunds.created_at', '>=', Carbon::parse($request->start_date));
        })->when($request->end_date,function($q) use($request) {
            $q->whereDate('refunds.created_at', '<=', Carbon::parse($request->end_date));
        })
        ->join('order_items', 'order_item_id', '=', 'order_items.id')
        ->join('orders', 'order_items.order_id', '=', 'orders.id')
        ->selectRaw('SUM(refunds.total_price) as total')
        ->whereIn('order_items.order_id', $ids2)
        ->get()->first()->total;


		$total_of_products_refunds = Refund::query()->when($request->date,function($q) use($request) {
            $q->whereDate('refunds.created_at', Carbon::parse($request->date));
        })->when($request->start_date,function($q) use($request) {
            $q->whereDate('refunds.created_at', '>=', Carbon::parse($request->start_date));
        })->when($request->end_date,function($q) use($request) {
            $q->whereDate('refunds.created_at', '<=', Carbon::parse($request->end_date));
        })
        ->join('order_items', 'order_item_id', '=', 'order_items.id')
		->join('user_products', 'order_items.user_product_id', '=', 'user_products.id')
        ->join('orders', 'order_items.order_id', '=', 'orders.id')
		->selectRaw('SUM(user_products.wholesale_price * refunds.qty) as total')
        ->whereIn('order_items.order_id', $ids2)
        ->get()->first()->total;

        $total_tax_value = OrderItem::query()->selectRaw('SUM(order_items.tax_value_paid * order_items.qty) as total_tax_value')
                ->whereIn('order_id', $ids)
                ->get()->first()->total_tax_value;


        $count = OrderItem::query()->selectRaw('SUM(order_items.qty) as total_count')
        ->whereIn('order_id', $ids)
        ->get()->first()->total_count;

        $calcCount = $count;

        $profit = OrderItem::query()
            ->where('qty','>',0)
            ->join('user_products', 'user_product_id', '=', 'user_products.id')
            ->selectRaw('SUM(total_price) - SUM(user_products.wholesale_price * order_items.qty) as profit')
            ->whereIn('order_id', $ids)
            ->get()->first()->profit;

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////

        $total_refund = Refund::query()->whereDate('refunds.created_at', $date)
        ->join('order_items', 'order_item_id', '=', 'order_items.id')
        ->join('orders', 'order_items.order_id', '=', 'orders.id')
        ->selectRaw('SUM(refunds.total_price_paid) as total')
        ->when($request->shop,function($query) use($request) {
            $query->whereHas('orderItem', function($orderItem) use($request) {
                $orderItem->whereHas('order',function($order) use($request) {
                    $order->where('seller_id',$request->shop);
                });
            });
        })
        ->get()->first()->total;


        $total_price_without_tax_paid = OrderItem::query()
        ->whereIn('order_id', $ids)
        ->when($request->shop,function($query) use($request) {
            $query->whereHas('order',function($order) use($request){
                $order->where('seller_id',$request->shop);
            });
        })
        ->selectRaw('SUM(order_items.price_without_tax_paid * order_items.qty) as total_price_without_tax_paid')
        ->get()->first()->total_price_without_tax_paid;


        $total_tax_value = OrderItem::query()
        ->whereIn('order_id', $ids)
        ->when($request->shop,function($query) use($request) {
            $query->whereHas('order',function($order) use($request){
                $order->where('seller_id',$request->shop);
            });
        })
        ->selectRaw('SUM(order_items.tax_value_paid) as total_tax_value')
        ->get()->first()->total_tax_value;

		/*
        if($request->shop != null) {
            $new_user = User::findOrFail($request->shop);
            $total_tax_value = $total_tax_value - (($new_user->tax_ratio / 100) * $total_refund);
        }
		*/

        // $total_price_without_tax_paid = $total_price_without_tax_paid - $total_refund;

		$total_price_without_tax_paid = $total_price_without_tax_paid ;

        $total_price_with_tax_paid = $total_price_without_tax_paid + $total_tax_value;

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////


        if ($profits){

			$totalRefunds = $totalRefunds - $total_of_products_refunds;

            $profit -= $totalRefunds;

            return [
                'orders' =>  $orders->get(),
                'profit' => $profit,
                'count' => $calcCount,
                'total' => $total_price_with_tax_paid,
                'total_tax_value' => $total_tax_value,
                'total_price_without_tax' => $total_price_without_tax_paid,
                // 'total_tax_value' => $total_tax_value,
                // 'total_price_without_tax' => $totalAmount - $total_tax_value,
                'totalRefunds' => $totalRefunds,
            ];

        } else {

            $totalRefunds = doubleval(currencyExchange($totalRefunds, $rate));

            $totalAmount -= $totalRefunds;

            return [
                'orders' => $orders->get(),
                'profit' => $profit,
                'count' => $calcCount,
                'total' => $total_price_with_tax_paid,
                'total_tax_value' => $total_tax_value,
                'total_price_without_tax' => $total_price_without_tax_paid,
                // 'total' => $totalAmount,
                // 'total_tax_value' => $total_tax_value,
                // 'total_price_without_tax' => $totalAmount - $total_tax_value,
                'totalRefunds' => $totalRefunds,
            ];
        }
    }

    // public function getOrders_v2(Request $request, $debts = false, $profits = false, $with = null,$pagination = true,$withItems = true)
    // {
    //     if (!$with)
    //         if($withItems)
    //             $with = ['buyer', 'productItems','seller', 'shipper', 'items.product.productColor'];
    //         else
    //             $with = ['buyer', 'productItems','seller', 'shipper'];

    //         // $with = ['buyer', 'productItems','seller', 'shipper', 'items.product.productColor'];

    //     $orders = Order::query()->with($with);
    //     $country = auth()->user()->country_id;
    //     $orders->whereHas('seller', function ($query) use ($country){
    //         $query->where('country_id',$country);
    //     });
    //     if (auth()->user()->role_id != User::ROLE_ADMIN)
    //         $orders->where('seller_id', auth()->id());

    //     if ($debts)
    //         $orders->where('remain_price', '>', 0);

    //     if ($type = $request->get('type'))
    //         $orders->where('type', $type);

    //     if ($search = $request->get('search'))
    //         $orders->where('barcode', 'LIKE', "%$search%");

    //     if ($buyerSearch = $request->query('buyerName'))
    //     {
    //         $orders->join('users', 'orders.buyer_id', '=', 'users.id')
    //         ->where('users.name', 'LIKE', "%{$buyerSearch}%");
    //     }

    //     if ($buyerId = $request->get('buyer_id'))
    //         $orders->where('buyer_id',"$buyerId");

    //     if ($buyerId = $request->get('buyer'))
    //         $orders->where('buyer_id',"$buyerId");

    //     if ($sellerId = $request->get('seller_id'))
    //         $orders->where('seller_id',"$sellerId");

    //     if ($sellerId = $request->get('shop'))
    //         $orders->where('seller_id',"$sellerId");

    //     if ($shipperId = $request->get('shipper_id'))
    //         $orders->where('shipper_id',"$shipperId");

    //     if ($date = $request->get('date'))
    //         $orders->whereDate('orders.created_at', Carbon::parse($date));

    //     if ($startDate = $request->get('start_date'))
    //         $orders->whereDate('orders.created_at', '>=', Carbon::parse($startDate));

    //     if ($endDate = $request->get('end_date'))
    //         $orders->whereDate('orders.created_at', '<=', Carbon::parse($endDate));

    //     if ($request->has(['field', 'direction'])){
    //         $field = $request->get('field');
    //         $direction = $request->get('direction');

    //         $sortableArray = app(Order::class)->getFillable();
    //         $sortableArray[] = 'id';
    //         if(in_array($field, $sortableArray)){
    //             $orders->orderBy($field, $direction);
    //         }else{
    //             $orders->orderByDesc('orders.id');
    //         }
    //     }else{
    //         $orders->orderByDesc('orders.id');
    //     }

    //     if(auth()->user()->country_id == User::COUNTRY_UAE)
    //         $rate = Currency::where('name','aed')->first()->rate;
    //     else
    //         $rate = 1;

    //     // Calculate total refunds
    //     $ids = $orders->pluck('orders.id');

    //     $ids2 = $this->returnRefoundOrdersArr($request, $debts = false, $profits = false, $with = null,$pagination = true,$withItems = true);

    //     $totalAmount  = $orders->sum('orders.total_price');

    //     /////////////////////////////////////////////////////////////////////////////////////////////////////////

    //     $totalRefunds = Refund::query()
    //     ->when($request->date,function($q) use($request) {
    //         $q->whereDate('refunds.created_at', Carbon::parse($request->date));
    //     })->when($request->start_date,function($q) use($request) {
    //         $q->whereDate('refunds.created_at', '>=', Carbon::parse($request->start_date));
    //     })->when($request->end_date,function($q) use($request) {
    //         $q->whereDate('refunds.created_at', '<=', Carbon::parse($request->end_date));
    //     })
    //     ->join('order_items', 'order_item_id', '=', 'order_items.id')
    //     ->join('orders', 'order_items.order_id', '=', 'orders.id')
    //     ->selectRaw('SUM(refunds.total_price) as total')
    //     ->whereIn('order_items.order_id', $ids2)
    //     ->get()->first()->total;


	// 	$total_of_products_refunds = Refund::query()->when($request->date,function($q) use($request) {
    //         $q->whereDate('refunds.created_at', Carbon::parse($request->date));
    //     })->when($request->start_date,function($q) use($request) {
    //         $q->whereDate('refunds.created_at', '>=', Carbon::parse($request->start_date));
    //     })->when($request->end_date,function($q) use($request) {
    //         $q->whereDate('refunds.created_at', '<=', Carbon::parse($request->end_date));
    //     })
    //     ->join('order_items', 'order_item_id', '=', 'order_items.id')
	// 	->join('user_products', 'order_items.user_product_id', '=', 'user_products.id')
    //     ->join('orders', 'order_items.order_id', '=', 'orders.id')
	// 	->selectRaw('SUM(user_products.wholesale_price * refunds.qty) as total')
    //     ->whereIn('order_items.order_id', $ids2)
    //     ->get()->first()->total;

    //     $total_tax_value = OrderItem::query()->selectRaw('SUM(order_items.tax_value_paid * order_items.qty) as total_tax_value')
    //             ->whereIn('order_id', $ids)
    //             ->get()->first()->total_tax_value;


    //     $count = OrderItem::query()->selectRaw('SUM(order_items.qty) as total_count')
    //     ->whereIn('order_id', $ids)
    //     ->get()->first()->total_count;


    //     $calcCount = $count;


    //     $profit = OrderItem::query()
    //         ->where('qty','>',0)
    //         ->join('user_products', 'user_product_id', '=', 'user_products.id')
    //         ->selectRaw('SUM(total_price) - SUM(user_products.wholesale_price * order_items.qty) as profit')
    //         ->whereIn('order_id', $ids)
    //         ->get()->first()->profit;


    //     ///////////////////////////////////////////////////////////////////////////////////////////////////////////

    //     $total_price_without_tax_paid = OrderItem::query()
    //     ->whereIn('order_id', $ids)
    //     ->when($request->shop,function($query) use($request) {
    //         $query->whereHas('order',function($order) use($request){
    //             $order->where('seller_id',$request->shop);
    //         });
    //     })
    //     ->selectRaw('SUM(order_items.price_without_tax_paid) as total_price_without_tax_paid')
    //     ->get()->first()->total_price_without_tax_paid;


    //     $total_tax_value = OrderItem::query()
    //     ->whereIn('order_id', $ids)
    //     ->when($request->shop,function($query) use($request) {
    //         $query->whereHas('order',function($order) use($request){
    //             $order->where('seller_id',$request->shop);
    //         });
    //     })
    //     ->selectRaw('SUM(order_items.tax_value_paid) as total_tax_value')
    //     ->get()->first()->total_tax_value;


    //     if($request->shop != null) {
    //         $new_user = User::findOrFail($request->shop);
    //         $total_tax_value = $total_tax_value - (($new_user->tax_ratio / 100) * $totalRefunds);
    //     }

    //     $total_price_without_tax_paid = $total_price_without_tax_paid - $totalRefunds;

    //     $total_price_with_tax_paid = $total_price_without_tax_paid + $total_tax_value;

    //     ///////////////////////////////////////////////////////////////////////////////////////////////////////////


    //     if ($profits){

	// 		$totalRefunds = $totalRefunds - $total_of_products_refunds;

    //         $profit -= $totalRefunds;

    //         return [
    //             'orders' =>  $orders->get(),
    //             'profit' => $profit,
    //             'count' => $calcCount,
    //             'total' => $total_price_with_tax_paid,
    //             'total_tax_value' => $total_tax_value,
    //             'total_price_without_tax' => $total_price_without_tax_paid,
    //             // 'total_tax_value' => $total_tax_value,
    //             // 'total_price_without_tax' => $totalAmount - $total_tax_value,
    //             'totalRefunds' => $totalRefunds,
    //         ];
    //     }else{

    //         $totalRefunds = doubleval(currencyExchange($totalRefunds, $rate));

    //         $totalAmount -= $totalRefunds;

    //         return [
    //             'orders' => $orders->get(),
    //             'profit' => $profit,
    //             'count' => $calcCount,
    //             'total' => $total_price_with_tax_paid,
    //             'total_tax_value' => $total_tax_value,
    //             'total_price_without_tax' => $total_price_without_tax_paid,
    //             // 'total' => $totalAmount,
    //             // 'total_tax_value' => $total_tax_value,
    //             // 'total_price_without_tax' => $totalAmount - $total_tax_value,
    //             'totalRefunds' => $totalRefunds,
    //         ];
    //     }
    // }


    public function getMonthlyOrders(Request $request, $debts = false, $profits = false, $with = null,$pagination = true,$withItems = true)
    {

        if(auth()->user()->country_id == User::COUNTRY_UAE)
            $rate = Currency::where('name','aed')->first()->rate;
        else
            $rate = 1;

        if($request->get('shop')) {
            $shops = User::query()->whereIn('role_id', [User::ROLE_SHOP, User::ROLE_WAREHOUSE])->where('id',$request->get('shop'))->get();
        } else {
            $shops = User::query()->whereIn('role_id', [User::ROLE_SHOP, User::ROLE_WAREHOUSE])->where('country_id',auth()->user()->country_id)->get();
        }

        if (!$with)
            if($withItems)
                $with = ['buyer', 'productItems','seller', 'shipper', 'items.product.productColor'];
            else
                $with = ['buyer', 'productItems','seller', 'shipper'];

            // $with = ['buyer', 'productItems','seller', 'shipper', 'items.product.productColor'];

        $country = auth()->user()->country_id;

        if($request->get('start_date') != null) {
            $start = Carbon::parse($request->get('start_date'));
        } elseif($request->get('date') != null) {
            $start = Carbon::parse($request->get('date'));
        } else {
            $start = Carbon::now()->startOfMonth();
        }

        if($request->get('end_date') != null) {
            $end = Carbon::parse($request->get('end_date'));
        } elseif($request->get('date') != null) {
            $end = Carbon::parse($request->get('date'));
        } else {
            $end = Carbon::now();
        }

        // Create a period from start date to end date
        $period = CarbonPeriod::create($start, $end);

        // Initialize an array to hold the dates
        $dates = [];

        // Iterate over the period and add each date to the array
        foreach ($period as $date) {
            $dates[] = $date->format('Y-m-d');
        }

       // dd($dates);

       usort($dates, function($a, $b) {
            return strtotime($b) - strtotime($a);
        });


        $dataArr = [];

        $new_total = 0;
        $new_count = 0;
        $new_total_price_without_tax = 0;
        $new_total_tax_value = 0;

        foreach($dates as $date) {

            foreach($shops as $shop) {

                $count = OrderItem::query()
                    ->whereHas('order',function($order) use($with,$country,$request,$shop) {
                        $order->with($with)->whereHas('seller', function ($query) use ($country,$shop) {
                            $query->where('seller_id',"$shop->id");
                        });
                    })
                    ->selectRaw('SUM(order_items.qty) as total_count')
                    ->whereDate('created_at', $date)
                    ->get()->first()->total_count;


                ////////////////////////////////////////////////////////////////////////////////////

                $total_refund = Refund::query()->whereDate('refunds.created_at', $date)
                    ->join('order_items', 'order_item_id', '=', 'order_items.id')
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->selectRaw('SUM(refunds.total_price_paid) as total')
                    ->whereHas('orderItem', function($orderItem) use($with,$country,$request,$shop) {
                        $orderItem->whereHas('order',function($order) use($with,$country,$request,$shop) {
                            $order->with($with)->whereHas('seller', function ($query) use ($country,$shop) {
                                $query->where('seller_id',"$shop->id");
                            });
                        });
                    })
                    ->get()->first()->total;

                /////////////////////////////////////////////////////////////////////////////////////

                $total_price_without_tax_paid = OrderItem::query()
                ->whereHas('order',function($order) use($with,$country,$request,$shop) {
                    $order->with($with)->whereHas('seller', function ($query) use ($country,$shop) {
                        $query->where('seller_id',"$shop->id");
                    });
                })
                ->selectRaw('SUM(order_items.price_without_tax_paid * order_items.qty) as total_price_without_tax_paid')
                ->whereDate('created_at', $date)
                ->get()->first()->total_price_without_tax_paid;

                // $total_price_without_tax_paid = $total_price_without_tax_paid - $total_refund;

				$total_price_without_tax_paid = $total_price_without_tax_paid;

                ///////////////////////////////////////////////////////////////////////////////////

                $total_tax_value = OrderItem::query()
                ->whereHas('order',function($order) use($with,$country,$request,$shop) {
                    $order->with($with)->whereHas('seller', function ($query) use ($country,$shop) {
                        $query->where('seller_id',"$shop->id");
                    });
                })
                ->selectRaw('SUM(order_items.tax_value_paid * order_items.qty) as total_tax_value')
                ->whereDate('created_at', $date)
                ->get()->first()->total_tax_value;

                ///////////////////////////////////////////////////////////////////////////////////

				/*
                if($request->shop != null) {
                    $total_tax_value = $total_tax_value - (($shop->tax_ratio / 100) * $total_refund);
                }
				*/

                ///////////////////////////////////////////////////////////////////////////////////

                $new_total                   = $new_total + $total_price_without_tax_paid + $total_tax_value;
                $new_count                   = $new_count + $count;
                $new_total_price_without_tax = $new_total_price_without_tax + $total_price_without_tax_paid;
                $new_total_tax_value         = $new_total_tax_value + $total_tax_value;

                // $total_price_with_tax_paid = Order::with($with)->whereHas('seller', function ($query) use ($country,$shop) {
                //     $query->where('seller_id',"$shop->id");
                // })
                // ->whereDate('created_at', $date)
                // ->sum('total_price');

                // $total_price_with_tax_paid = $total_price_with_tax_paid - $total_refund;

                // $total_tax_value = OrderItem::query()
                //     ->whereHas('order',function($order) use($with,$country,$request,$shop) {
                //         $order->with($with)->whereHas('seller', function ($query) use ($country,$shop) {
                //             $query->where('seller_id',"$shop->id");
                //         });
                //     })
                //     ->selectRaw('SUM(order_items.tax_value_paid * order_items.qty) as total_tax_value')
                //     ->whereDate('created_at', $date)
                //     ->get()->first()->total_tax_value;

                // $total_price_without_tax_paid = $total_price_with_tax_paid - $total_tax_value;



                // $total_price_without_tax_paid = Order::with($with)->whereHas('seller', function ($query) use ($country,$shop) {
                //         $query->where('seller_id',"$shop->id");
                //     })
                //     ->whereDate('created_at', $date)
                //     ->sum('price_without_tax');


                // $total_price_without_tax_paid = $total_price_without_tax_paid - $total_refund;


                // $total_price_without_tax_paid = OrderItem::query()
                //     ->whereHas('order',function($order) use($with,$country,$request,$shop) {
                //         $order->with($with)->whereHas('seller', function ($query) use ($country,$shop) {
                //             $query->where('seller_id',"$shop->id");
                //         });
                //     })
                //     ->selectRaw('SUM(order_items.price_without_tax_paid * order_items.qty) as total_price_without_tax_value')
                //     ->whereDate('created_at', $date)
                //     ->get()->first()->total_price_without_tax_value;



                // $total_price_with_tax_paid = $total_price_without_tax_paid + $total_tax_value;

                $dataArr[] = [
                    'shop_name' => $shop->name,
                    'date' => $date,
                    'count' => $count,
                    'price_without_tax' => $total_price_without_tax_paid,
                    'tax_value' => $total_tax_value,
                    'total_price' => $total_price_without_tax_paid + $total_tax_value,
                    // 'total_price' => $total_price_with_tax_paid,
                    'total_refund' => $total_refund
                ];

            }

        }

        $main_shops = User::query()->whereIn('role_id', [User::ROLE_SHOP, User::ROLE_WAREHOUSE])->where('country_id',auth()->user()->country_id)->get();
        $main_shops = transformDataForVue($main_shops);

        $totalRefunds = array_sum(array_column($dataArr,'total_refund'));

        $newArr = [
            'orders'                  => $dataArr,
            'total'                   => $new_total,
            'count'                   => $new_count,
            'total_price_without_tax' => $new_total_price_without_tax ,
            'total_tax_value'         => $new_total_tax_value,
            // 'total'                   => array_sum(array_column($dataArr,'total_price')) ,
            // 'count'                   => array_sum(array_column($dataArr,'count')),
            // 'total_price_without_tax' => array_sum(array_column($dataArr,'price_without_tax')),
            // 'total_tax_value'         => array_sum(array_column($dataArr,'tax_value')),
            'totalRefunds'            => $totalRefunds,
            'filters'                 => $request->all(['search', 'field', 'direction']),
            'shops'                   => $main_shops,
        ];

        // dd($newArr);

        return $newArr;

    }

    public function getProducts()
    {
        return UserProduct::query()
            ->select([
                'user_products.stock',
                'user_products.wholesale_price',
                'product_colors.barcode',
                'user_products.id',
                'products.name',
                DB::raw("CONCAT(products.name,' (',colors.name, ' )', ' (',product_colors.barcode, ' )') as product_name")
            ])
            ->join('product_colors', 'product_color_id', '=', 'product_colors.id')
            ->join('products', 'product_colors.product_id', '=', 'products.id')
            ->join('colors', 'product_colors.color_id', '=', 'colors.id')
            ->where('user_products.user_id', auth()->id())
            ->where('user_products.stock', '>', 0)
            ->get();

    }


    public function getOrdersForExport(Request $request, $debts = false, $profits = false, $with = null,$pagination = true)
    {
        if (!$with)
            $with = ['seller'];
            // $with = ['buyer', 'productItems','seller', 'shipper', 'items.product.productColor'];

        $orders = Order::query()->with($with);
        $country = auth()->user()->country_id;
        $orders->whereHas('seller', function ($query) use ($country){
            $query->where('country_id',$country);
        });
        if (auth()->user()->role_id != User::ROLE_ADMIN)
            $orders->where('seller_id', auth()->id());

        if ($debts)
            $orders->where('remain_price', '>', 0);

        if ($type = $request->get('type'))
            $orders->where('type', $type);

        if ($search = $request->get('search'))
            $orders->where('barcode', 'LIKE', "%$search%");

        if ($buyerId = $request->get('buyer_id'))
            $orders->where('buyer_id',"$buyerId");

        if ($buyerId = $request->get('buyer'))
            $orders->where('buyer_id',"$buyerId");

        if ($sellerId = $request->get('seller_id'))
            $orders->where('seller_id',"$sellerId");

        if ($sellerId = $request->get('shop'))
            $orders->where('seller_id',"$sellerId");

        if ($shipperId = $request->get('shipper_id'))
            $orders->where('shipper_id',"$shipperId");

        if ($date = $request->get('date'))
            $orders->whereDate('created_at', Carbon::parse($date));

        if ($startDate = $request->get('start_date'))
            $orders->whereDate('created_at', '>=', Carbon::parse($startDate));

        if ($endDate = $request->get('end_date'))
            $orders->whereDate('created_at', '<=', Carbon::parse($endDate));

        if ($request->has(['field', 'direction'])){
            $field = $request->get('field');
            $direction = $request->get('direction');

            $sortableArray = app(Order::class)->getFillable();
            $sortableArray[] = 'id';
            if(in_array($field, $sortableArray)){
                $orders->orderBy($field, $direction);
            }else{
                $orders->orderByDesc('id');
            }
        }else{
            $orders->orderByDesc('id');
        }
        $orders = $orders->selectRaw('Count(*) as count, seller_id, SUM(total_price) as total_price,SUM(paid_price) as paid_price,SUM(remain_price) as remain_price,SUM(price_without_tax) as total_price_without_tax,SUM(tax_value) as total_tax_value')->groupBy('seller_id')->get();
        return $orders;
    }

    public function getWebsiteOrders(Request $request, $pagination = true)
    {
        $with = ['buyer', 'items.product'];
        $query = WebsiteOrder::query()->with($with);
        $country = auth()->user()->country_id;

        $query->where('country_id', $country);

        if ($search = $request->get('search'))
            $query->where('barcode', 'LIKE', "%$search%");

        if ($buyerId = $request->get('buyer_id') ?: $request->get('buyer'))
            $query->where('buyer_id', $buyerId);

        if ($startDate = $request->get('start_date'))
            $query->whereDate('created_at', '>=', Carbon::parse($startDate));

        if ($endDate = $request->get('end_date'))
            $query->whereDate('created_at', '<=', Carbon::parse($endDate));

        if ($date = $request->get('date'))
            $query->whereDate('created_at', Carbon::parse($date));

        if ($request->has(['field', 'direction'])) {
            $field = $request->get('field');
            $direction = $request->get('direction');
            $query->orderBy($field, $direction);
        } else {
            $query->orderByDesc('id');
        }

        $totalAmount = $query->sum('total_price');
        $count = $query->count();

        if ($pagination) {
            $orders = $query->paginate(10);
        } else {
            $orders = $query->get();
        }

        return [
            'orders' => $orders,
            'total' => $totalAmount,
            'count' => $count,
            'total_price_without_tax' => 0,
            'total_tax_value' => 0,
        ];
    }
}

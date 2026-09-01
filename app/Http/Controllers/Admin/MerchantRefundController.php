<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClientDebitLog;
use App\Models\ClientRefund;
use App\Models\DebitLog;
use App\Models\MerchantRefund;
use App\Models\OrderItem;
use App\Models\Refund;
use App\Models\User;
use App\Models\UserProduct;
use App\Models\UserProductLog;
use App\Notifications\ShopNotification;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Jenssegers\Date\Date;

class MerchantRefundController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $userProduct = null;
        $userProductsArr = [];
        $note = null;

        $shopName = null;
        $merchantName = null;
        $merchantRefund = null;

        $total_stock = 0;
        $total_amount = 0;

        $logsArr = [];
        $logsArr2 = [];

        $products = json_decode($request->get('user_products'),true);

        $productColorsArr = [];

        $now = Carbon::now()->format('Y-m-d');
        $now2 = Carbon::now()->format('Y-m-d h:i A');

        foreach ($products as $key => $product) {

            try {
                DB::beginTransaction();
                // $qty = $request->get('qty');
                $qty = $product['qty'];

                $merchantRefund = MerchantRefund::query()->create([
                    'user_product_id' => $product['id'],
                    'merchant_id' => $request->get('merchant_id'),
                    'merchant_debit_id' => $request->get('merchant_debit_id'),
                    'qty' => $product['qty']
                ]);

                $total_stock = $total_stock + $qty;

                $userProduct = $merchantRefund->userProduct;
                $shop = $userProduct->user;
                $merchant = $merchantRefund->merchant;

                $shopName = $shop->name;
                $product = $userProduct->productColor->product_name;
                $merchantName = $merchant->name;



                $note = "لقد قام المحل $shopName بمرتجع للمنتج $product بعدد $qty للتاجر $merchantName بتاريخ $now2";

                // TODO: WE SHOULD REPLACE WHOLESALE PRICE WITH THE ACTUAL PRICE THAT THIS PRODUCT SOLD TO THIS MERCHANT (Refunds)
                // TODO: WHEN WE REFUND ITEM INTO REFUND THEN IT SHOULD BE REMOVED FROM TOTAL PRICE IN ORDERS
                // TODO: IN REFUNDS PAGE, SHOW TOTAL REFUNDS
                // TODO: ADD SAME FILTER IN ORDER TO THE REFUNDS
                $amount = $qty * $userProduct->wholesale_price;
                $total_amount = $total_amount + $amount;

                // DebitLog::query()->create([
                //     'merchant_debit_id' => $request->get('merchant_debit_id'),
                //     'merchant_refund_id' => $merchantRefund->id,
                //     'note' => $note,
                //     'amount' => $amount
                // ]);

                if(empty($productColorsArr)) {
                    $productColorsArr[$userProduct->productColor->id] = [
                        'product' => $product,
                        'qty' => $qty,
                        'amount' => $amount,
                        'userProduct' => $userProduct
                    ];
                } else {


                    if(in_array($userProduct->productColor->id,array_keys($productColorsArr)))  {
                        $productColorsArr[$userProduct->productColor->id] = [
                            'product' => $product,
                            'qty' => $productColorsArr[$userProduct->productColor->id]['qty'] + $qty,
                            'amount' => $productColorsArr[$userProduct->productColor->id]['amount'] + $amount,
                            'userProduct' => $userProduct
                        ];
                    } else {
                        $productColorsArr[$userProduct->productColor->id] = [
                            'product' => $product,
                            'qty' => $qty,
                            'amount' => $amount,
                            'userProduct' => $userProduct
                        ];
                    }
                }


                $userProduct->update([
                    'stock' => DB::raw("stock - $qty")
                ]);

                $shop->wallet->update([
                'debit' =>  DB::raw("debit - $amount")
                ]);

                $merchant->wallet->update([
                    'credit' =>  DB::raw("credit - $amount")
                ]);

                $merchantRefund->merchantDebit->update([
                    'amount' =>  DB::raw("amount - $amount")
                ]);

                ////////////////////////////////////////////

                $log = UserProductLog::query()->create([
                    'user_product_id' => $userProduct->id,
                    'note' => $note
                ]);

                $logsArr[] = [
                    'user_product_id'     => $userProduct->id,
                    'user_product_log_id' => $log->id,
                    'note'                => $note,
                    'stock'               => $qty,
                    'size'                => $userProduct->size,
                ];

                $logsArr2[][$userProduct->productColor->id] = [
                    'user_product_id'     => $userProduct->id,
                    'user_product_log_id' => $log->id,
                    'note'                => $note,
                    'stock'               => $qty,
                    'size'                => $userProduct->size,
                ];


            }catch (Exception $exception){
                DB::rollBack();
                return response()->json([
                    'error' => $exception->getMessage(),
                ]);
            }
            DB::commit();
        }


        if($userProduct != null && $note != null) {

            ///////////////////////////////////////////////

            if($merchantRefund && $shop) {

                if(! empty($productColorsArr)) {

                    foreach($productColorsArr as $product_color_id => $arr) {

                        $notificationLogsArr = [];

                        $product = $arr['product'];

                        $check_debit_log = DebitLog::where('product_color_id',$product_color_id)->where('shop_id',$shop->id)->where('merchant_id',$request->get('merchant_id'))->where('request_date',$now)->where('type','refound')->first();

                        $total_stock = $arr['qty'];
                        $total_amount = $arr['amount'];

                        if($check_debit_log == null) {

                            $debit_note = "لقد قام المحل $shopName بمرتجع للمنتج $product بعدد $total_stock للتاجر $merchantName بتاريخ $now2";

                            DebitLog::query()->create([
                                'merchant_debit_id' => $request->get('merchant_debit_id'),
                                'merchant_refund_id' => $merchantRefund->id,
                                'note' => $debit_note,
                                'amount' => $total_amount,

                                'user_product_id' => $userProduct->id,
                                'product_color_id' => $product_color_id,
                                'shop_id' => $shop->id,
                                'merchant_id' => $request->get('merchant_id'),
                                'request_date' => $now,
                                'qty' => $total_stock,
                                'type' => 'refound'
                            ]);

                        } else {

                            $total_stock = $total_stock + $check_debit_log->qty;
                            $total_amount = $total_amount + $check_debit_log->amount;

                            $debit_note = "لقد قام المحل $shopName بمرتجع للمنتج $product بعدد $total_stock للتاجر $merchantName بتاريخ $now2";

                            $check_debit_log->update([
                                'qty' => $total_stock,
                                'amount' => $total_amount,
                                'note' => $debit_note,
                            ]);
                        }

                        $note = "لقد قام المحل $shopName بمرتجع للمنتج $product بعدد $total_stock للتاجر $merchantName بتاريخ $now2";

                        $shopAccount = $userProduct->user;
                        $user = auth()->user();
                        $admin = User::query()->where('role_id', User::ROLE_ADMIN)->first();

                        foreach($logsArr2 as $index => $colorsArr) {
                            foreach($colorsArr as $ind => $val) {
                                if($product_color_id == $ind) {
                                    $notificationLogsArr[] = $logsArr2[$index][$ind];
                                }
                            }
                        }

                        $new_userProduct = $arr['userProduct'];

                        if($shopAccount && $shopAccount->id != $user->id) {
                            $shopAccount->notify(new ShopNotification($new_userProduct, $note, $user,$notificationLogsArr));
                        }

                        $admin->notify(new ShopNotification($new_userProduct, $note, $user,$notificationLogsArr));
                        $user->notify(new ShopNotification($new_userProduct, $note, $user,$notificationLogsArr));

                    }
                }

            }

            ///////////////////////////////////////////////////

        }

        return response()->json([
            'success' => true,
            'msg' => 'تم إنشاء المرتجعات بنجاح'
        ]);




        // if($userProduct != null && $note != null) {

        //     ///////////////////////////////////////////////

        //     $product = $userProduct->productColor->product_name;

        //     if($merchantRefund && $product) {

        //         $debit_note = "لقد قام المحل $shopName بمرتجع للمنتج $product بعدد $total_stock للتاجر $merchantName بتاريخ $now2";

        //         DebitLog::query()->create([
        //             'merchant_debit_id' => $request->get('merchant_debit_id'),
        //             'merchant_refund_id' => $merchantRefund->id,
        //             'note' => $debit_note,
        //             'amount' => $total_amount
        //         ]);
        //     }

        //     //////////////////////////////////////////

        //     $note = "لقد قام المحل $shopName بمرتجع للمنتج $product بعدد $total_stock للتاجر $merchantName بتاريخ $now2";

        //     $shopAccount = $userProduct->user;
        //     $user = auth()->user();
        //     $admin = User::query()->where('role_id', User::ROLE_ADMIN)->first();

        //     if($shopAccount && $shopAccount->id != $user->id) {
        //         $shopAccount->notify(new ShopNotification($userProduct, $note, $user,$logsArr));
        //     }

        //     $admin->notify(new ShopNotification($userProduct, $note, $user,$logsArr));
        //     $user->notify(new ShopNotification($userProduct, $note, $user,$logsArr));

        //     ///////////////////////////////////////////////////

        // }

        // return response()->json([
        //     'success' => true,
        //     'msg' => 'تم إنشاء المرتجعات بنجاح'
        // ]);




    }

    public function clientRefund(Request $request)
    {
        $products = json_decode($request->get('user_products'),true);

        foreach ($products as $key => $product) {
            try {

                DB::beginTransaction();
                // $qty = $request->get('qty');
                // $orderItemId = $request->get('order_item_id');
                $qty = $product['qty'];
                $orderItemId = $product['order_item_id'];

                $orderItem = OrderItem::query()->findOrFail($orderItemId);
                $itemBarcode = $orderItem->product->productColor->barcode;
                $orderBarcode = $orderItem->order->barcode;

                $refund = new Refund([
                    'order_item_id' => $orderItemId,
                    'qty' => $qty,
                    'item_price' => $orderItem->item_price,
                    'total_price' => $orderItem->item_price * $qty,
                    'item_barcode' => $itemBarcode,
                    'order_barcode' => $orderBarcode,
                ]);
                $refund->save();

				// new code
				$calc_new_total = round($orderItem->item_price * $qty * $orderItem->order->curr_rate);

				// new code
				$orderItem->order->update([
					'paid_price' => $orderItem->order->paid_price + $calc_new_total,
					'remain_price' => $orderItem->order->remain_price - $calc_new_total
				]);


                $orderItem->qty = $orderItem->qty - $qty;
                $orderItem->save();

                $clientRefund = ClientRefund::query()->create([
                    'refund_id' => $refund->id,
                    'client_id' => $request->get('client_id'),
                    'client_debit_id' => $request->get('client_debit_id')
                ]);

                $userProduct = $orderItem->product;
                $shop = $orderItem->order->seller;
                $client = $orderItem->order->buyer;

                $shopName = $shop->name;
                $clientName = $client->name;
                $product = $userProduct->productColor->product_name;

                $amount = $qty * $orderItem->item_price;

                $newStock = $userProduct->stock + $qty;
                $userProduct->update(['stock' => $newStock]);

                $wallet = $client->wallet;

                if ($wallet->debit >= $amount){
                    $wallet->update(['debit' => DB::raw("debit - $amount")]);
                }elseif($wallet->debit == 0){
                    $wallet->update(['credit' => DB::raw("$amount")]);
                } else {
                    $diff = $amount - $wallet->debit;
                    $wallet->update(['debit' => 0]);
                    $wallet->update(['credit' => DB::raw("credit + $diff")]);
                }

                if ($clientRefund->debit->amount > $amount) {

                    $clientRefund->debit->update([
                        'amount' =>  DB::raw("amount - $amount")
                    ]);

                } else {
                    $clientRefund->debit->update(['amount' => 0]);
                }


                $now = Carbon::now()->format('Y-m-d');

                $check_client_debit_log = ClientDebitLog::where('shop_id',$shop->id)->where('client_id',$request->get('client_id'))->where('product_color_id',$orderItem->product->productColor->id)->where('request_date',$now)->first();

                if($check_client_debit_log != null) {

                    $qty = $qty + $check_client_debit_log->qty;
                    $amount = $amount + $check_client_debit_log->amount;

                    $note = "لقد قام الزبون $clientName بمرتجع للمنتج $product بعدد $qty للمحل $shopName";

                    $check_client_debit_log->update([
                        'qty' => $qty,
                        'amount' => $amount,
                        'note' => $note
                    ]);

                } else {

                    $note = "لقد قام الزبون $clientName بمرتجع للمنتج $product بعدد $qty للمحل $shopName";

                    ClientDebitLog::query()->create([
                        'client_debit_id' => $request->get('client_debit_id'),
                        'client_refund_id' => $clientRefund->id,
                        'note' => $note,
                        'amount' => $amount,
                        'shop_id' => $shop->id,
                        'client_id' => $request->get('client_id'),
                        'product_color_id' => $orderItem->product->productColor->id,
                        'request_date' => $now,
                        'qty' => $qty
                    ]);
                }

            }catch (Exception $exception){
                DB::rollBack();
                return response()->json([
                    'error' => $exception->getMessage(),
                ]);
            }

            DB::commit();
        }
        return response()->json([
            'success' => true,
            'msg' => 'تم إنشاء المرتجعات بنجاح'
        ]);
    }


}

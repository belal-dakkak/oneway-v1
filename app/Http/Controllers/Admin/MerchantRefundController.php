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
use App\Services\ClientAccountService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Jenssegers\Date\Date;

class MerchantRefundController extends Controller
{
    private $clientAccounts;

    public function __construct(ClientAccountService $clientAccounts)
    {
        $this->clientAccounts = $clientAccounts;
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'user_products' => ['required', 'string'],
            'merchant_id' => ['required', 'integer', 'exists:users,id'],
            'merchant_debit_id' => ['required', 'integer', 'exists:merchant_debits,id'],
        ]);

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

        $products = json_decode($request->get('user_products'), true);
        if (! is_array($products) || empty($products)) {
            return response()->json(['error' => 'بيانات المنتجات المرتجعة غير صالحة.'], 422);
        }

        $productColorsArr = [];

        $now = Carbon::now()->format('Y-m-d');
        $now2 = Carbon::now()->format('Y-m-d h:i A');

        DB::beginTransaction();
        foreach ($products as $key => $product) {

            try {
                // $qty = $request->get('qty');
                $qty = (int) ($product['qty'] ?? 0);
                $userProductId = (int) ($product['id'] ?? 0);
                if ($qty <= 0 || $userProductId <= 0) {
                    throw new Exception('يجب تحديد منتج وكمية مرتجعة صحيحة.');
                }

                $userProduct = UserProduct::query()
                    ->with(['user', 'productColor.product', 'productColor.color'])
                    ->lockForUpdate()
                    ->findOrFail($userProductId);
                $shop = $userProduct->user;
                $merchant = User::query()->lockForUpdate()->findOrFail((int) $request->get('merchant_id'));
                $merchantAccount = \App\Models\MerchantDebit::query()
                    ->lockForUpdate()
                    ->findOrFail((int) $request->get('merchant_debit_id'));

                if ((int) $merchantAccount->creditor_id !== (int) $merchant->id
                    || (int) $merchantAccount->debtor_id !== (int) $shop->id
                    || (int) $shop->country_id !== (int) auth()->user()->country_id
                    || (int) $merchant->country_id !== (int) $shop->country_id) {
                    throw new Exception('حساب الدين أو المحل المحدد غير صالح لهذا المرتجع.');
                }
                if ($qty > (int) $userProduct->stock) {
                    throw new Exception('الكمية المرتجعة أكبر من المخزون المتاح.');
                }

                $merchantRefund = MerchantRefund::query()->create([
                    'user_product_id' => $userProductId,
                    'merchant_id' => $request->get('merchant_id'),
                    'merchant_debit_id' => $request->get('merchant_debit_id'),
                    'qty' => $product['qty']
                ]);

                $total_stock = $total_stock + $qty;

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


                $userProduct->decrement('stock', $qty);
                $userProduct->refresh()->load(['user', 'productColor.product', 'productColor.color']);

                $merchantCode = app(\App\Services\OperationalCurrency::class)->accountCode($merchantAccount);
                $accountAmount = app(\App\Services\OperationalCurrency::class)->fromBase($amount, $merchantCode);
                if ((float) $merchantAccount->amount < (float) $accountAmount) {
                    throw new Exception('قيمة المرتجع أكبر من رصيد دين التاجر.');
                }
                $merchantAccount->decrement('amount', $accountAmount);

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
                ], 422);
            }
        }
        $notificationsToSend = [];
        try {
        if($userProduct != null && $note != null) {

            ///////////////////////////////////////////////

            if($merchantRefund && $shop) {

                if(! empty($productColorsArr)) {

                    foreach($productColorsArr as $product_color_id => $arr) {

                        $notificationLogsArr = [];

                        $product = $arr['product'];

                        $check_debit_log = DebitLog::where('product_color_id',$product_color_id)->where('shop_id',$shop->id)->where('merchant_id',$request->get('merchant_id'))->where('request_date',$now)->where('type','refound')->lockForUpdate()->first();

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
                            $total_amount = app(\App\Services\OperationalCurrency::class)->fromBase($total_amount, $check_debit_log->currency_code ?: 'USD') + $check_debit_log->amount;

                            $debit_note = "لقد قام المحل $shopName بمرتجع للمنتج $product بعدد $total_stock للتاجر $merchantName بتاريخ $now2";

                            $check_debit_log->update([
                                'qty' => $total_stock,
                                'amount' => $total_amount,
                                'note' => $debit_note,
                            ]);
                        }

                        $note = "لقد قام المحل $shopName بمرتجع للمنتج $product بعدد $total_stock للتاجر $merchantName بتاريخ $now2";

                        $user = auth()->user();

                        foreach($logsArr2 as $index => $colorsArr) {
                            foreach($colorsArr as $ind => $val) {
                                if($product_color_id == $ind) {
                                    $notificationLogsArr[] = $logsArr2[$index][$ind];
                                }
                            }
                        }

                        $new_userProduct = $arr['userProduct'];
                        $recipients = User::query()
                            ->where('country_id', $user->country_id)
                            ->where('role_id', User::ROLE_ADMIN)
                            ->get()
                            ->push($user)
                            ->push($new_userProduct->user)
                            ->unique('id');
                        $notificationsToSend[] = [
                            'recipients' => $recipients,
                            'user_product' => $new_userProduct,
                            'note' => $note,
                            'actor' => $user,
                            'logs' => $notificationLogsArr,
                        ];

                    }
                }

            }

            ///////////////////////////////////////////////////

        }
        } catch (Exception $exception) {
            DB::rollBack();
            return response()->json(['error' => $exception->getMessage()], 422);
        }
        DB::commit();

        foreach ($notificationsToSend as $notificationData) {
            foreach ($notificationData['recipients'] as $recipient) {
                try {
                    $recipient->notify(new ShopNotification(
                        $notificationData['user_product'],
                        $notificationData['note'],
                        $notificationData['actor'],
                        $notificationData['logs']
                    ));
                } catch (\Throwable $exception) {
                    \Log::warning('Merchant refund notification failed.', [
                        'recipient_id' => $recipient->id,
                        'message' => $exception->getMessage(),
                    ]);
                }
            }
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
        $request->validate([
            'user_products' => ['required', 'string'],
            'client_id' => ['required', 'integer'],
        ]);

        $products = json_decode($request->get('user_products'), true);
        if (! is_array($products) || empty($products)) {
            return response()->json(['error' => 'بيانات المنتجات المرتجعة غير صالحة.'], 422);
        }

        DB::beginTransaction();

        foreach ($products as $key => $product) {
            try {

                DB::beginTransaction();
                // $qty = $request->get('qty');
                // $orderItemId = $request->get('order_item_id');
                $qty = $product['qty'];
                $orderItemId = $product['order_item_id'];

                $orderItem = OrderItem::query()->findOrFail($orderItemId);
                if ((int) $qty <= 0 || (int) $qty > (int) $orderItem->qty) {
                    throw new Exception('كمية المرتجع غير صالحة.');
                }
                if ((int) optional($orderItem->order->seller)->country_id !== (int) auth()->user()->country_id) {
                    abort(403);
                }
                $order = $orderItem->order;
                if (! $order->buyer_id) {
                    throw new Exception('لا يوجد زبون مرتبط بهذه الطلبية.');
                }
                $currency = strtoupper((string) ($order->curr_type ?: 'USD'));
                $rate = (float) ($order->curr_rate ?: 1);
                $decimals = $currency === 'SYP' ? 0 : 2;
                $calc_new_total = round((float) $orderItem->item_price * $qty * $rate, $decimals);
                $account = $this->clientAccounts->account(
                    (int) $order->seller_id,
                    (int) $order->buyer_id,
                    $currency
                );
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
				// The account and order are adjusted below in their original transaction currency.


                $remainingQty = max(0, (int) $orderItem->qty - (int) $qty);
                $orderItem->update([
                    'qty' => $remainingQty,
                    'total_price' => (float) $orderItem->item_price * $remainingQty,
                    'total_price_paid' => (float) $orderItem->item_price * $remainingQty * $rate,
                ]);

                $clientRefund = ClientRefund::query()->create([
                    'refund_id' => $refund->id,
                    'client_id' => $request->get('client_id'),
                    'client_debit_id' => $account->id
                ]);

                $this->clientAccounts->refundToAccount($order, $calc_new_total, $clientRefund->id);
                $newTotal = max(0, (float) $order->total_price - $calc_new_total);
                $newRemain = max(0, (float) $order->remain_price - $calc_new_total);
                $order->update([
                    'total_price' => $newTotal,
                    'paid_price' => max(0, $newTotal - $newRemain),
                    'remain_price' => $newRemain,
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

                if (false) {
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
                }

            }catch (Exception $exception){
                while (DB::transactionLevel() > 0) {
                    DB::rollBack();
                }
                return response()->json([
                    'error' => $exception->getMessage(),
                ]);
            }

            DB::commit();
        }
        DB::commit();
        return response()->json([
            'success' => true,
            'msg' => 'تم إنشاء المرتجعات بنجاح'
        ]);
    }


}

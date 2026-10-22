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

    public function clientRefund(Request $request): JsonResponse
    {
        $request->validate([
            'user_products' => ['required', 'string'],
            'client_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $products = json_decode((string) $request->get('user_products'), true);
        if (! is_array($products) || empty($products)) {
            return response()->json(['error' => 'Invalid refund product data.'], 422);
        }

        foreach ($products as $product) {
            if (! is_array($product)
                || (int) ($product['qty'] ?? 0) <= 0
                || (int) ($product['order_item_id'] ?? 0) <= 0) {
                return response()->json(['error' => 'Each refund row requires a valid item and quantity.'], 422);
            }
        }

        try {
            DB::transaction(function () use ($products, $request) {
                $clientId = (int) $request->get('client_id');
                $sellerId = null;
                $currencyCode = null;
                $account = null;

                foreach ($products as $product) {
                    $qty = (int) $product['qty'];
                    $orderItem = OrderItem::query()
                        ->lockForUpdate()
                        ->findOrFail((int) $product['order_item_id']);
                    $order = \App\Models\Order::query()
                        ->with(['seller', 'buyer'])
                        ->lockForUpdate()
                        ->findOrFail((int) $orderItem->order_id);

                    if (! $order->buyer_id || (int) $order->buyer_id !== $clientId) {
                        throw new Exception('The selected item does not belong to this customer account.');
                    }
                    if ((int) optional($order->seller)->country_id !== (int) auth()->user()->country_id) {
                        abort(403);
                    }

                    $currency = strtoupper((string) ($order->curr_type ?: 'USD'));
                    if (($sellerId !== null && $sellerId !== (int) $order->seller_id)
                        || ($currencyCode !== null && $currencyCode !== $currency)) {
                        throw new Exception('All returned items must belong to the same customer account and currency.');
                    }
                    $sellerId = (int) $order->seller_id;
                    $currencyCode = $currency;

                    if ($qty > (int) $orderItem->qty) {
                        throw new Exception('The refund quantity exceeds the remaining sold quantity.');
                    }

                    $userProduct = UserProduct::query()
                        ->with(['productColor.product', 'productColor.color'])
                        ->lockForUpdate()
                        ->findOrFail((int) $orderItem->user_product_id);
                    if ((int) $userProduct->user_id !== (int) $order->seller_id) {
                        throw new Exception('The sold inventory row does not belong to the order seller.');
                    }

                    $rate = max((float) ($order->curr_rate ?: 1), 0.000001);
                    $decimals = $currency === 'SYP' ? 0 : 2;
                    $baseUnit = (float) $orderItem->item_price;
                    $transactionUnit = (float) $orderItem->item_price_paid;
                    if ($transactionUnit == 0.0 && $baseUnit != 0.0) {
                        $transactionUnit = $baseUnit * $rate;
                    }
                    $refundAmount = round($transactionUnit * $qty, $decimals);
                    $baseTotal = $baseUnit * $qty;
                    $taxUnit = (float) $orderItem->tax_value_paid;
                    if ($taxUnit == 0.0 && (float) $orderItem->tax_value != 0.0) {
                        $taxUnit = (float) $orderItem->tax_value * $rate;
                    }
                    $netUnit = (float) $orderItem->price_without_tax_paid;
                    if ($netUnit == 0.0) {
                        $netUnit = max(0, $transactionUnit - $taxUnit);
                    }

                    $refund = Refund::query()->create([
                        'order_item_id' => $orderItem->id,
                        'qty' => $qty,
                        'item_price' => $baseUnit,
                        'total_price' => $baseTotal,
                        'total_price_paid' => $refundAmount,
                        'currency_code' => $currency,
                        'net_amount' => round($netUnit * $qty, $decimals),
                        'tax_amount' => round($taxUnit * $qty, $decimals),
                        'cost_amount' => round((float) $orderItem->unit_cost * $rate * $qty, $decimals),
                        'item_barcode' => (string) $userProduct->productColor->barcode,
                        'order_barcode' => (string) $order->barcode,
                    ]);

                    $account = $this->clientAccounts->account(
                        (int) $order->seller_id,
                        $clientId,
                        $currency
                    );
                    $clientRefund = ClientRefund::query()->create([
                        'refund_id' => $refund->id,
                        'client_id' => $clientId,
                        'client_debit_id' => $account->id,
                    ]);
                    $this->clientAccounts->refundToAccount($order, $refundAmount, $clientRefund->id);

                    $remainingQty = (int) $orderItem->qty - $qty;
                    $orderItem->update([
                        'qty' => $remainingQty,
                        'total_price' => $baseUnit * $remainingQty,
                        'total_price_paid' => round($transactionUnit * $remainingQty, $decimals),
                    ]);

                    $newTotal = max(0, round((float) $order->total_price - $refundAmount, $decimals));
                    $newRemain = max(0, round((float) $order->remain_price - $refundAmount, $decimals));
                    $order->update([
                        'total_price' => $newTotal,
                        'paid_price' => max(0, round($newTotal - $newRemain, $decimals)),
                        'remain_price' => $newRemain,
                        'tax_value' => max(0, round((float) $order->tax_value - ($taxUnit * $qty), $decimals)),
                        'price_without_tax' => max(0, round((float) $order->price_without_tax - ($netUnit * $qty), $decimals)),
                    ]);

                    $userProduct->increment('stock', $qty);
                }

                if ($account) {
                    $this->clientAccounts->reconcileSettledAccount(
                        $account,
                        'account_settled_after_customer_refund'
                    );
                }
            }, 3);
        } catch (\Throwable $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'msg' => 'Customer debt refund created successfully.',
        ]);
    }


}

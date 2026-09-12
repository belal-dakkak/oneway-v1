<?php

namespace App\Services\Payment;

use App\Models\WebsiteOrder;
use App\Models\CountryCommerceSetting;
use App\Services\CashboxService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class TapPaymentFinalizer
{
    private const TERMINAL_FAILURES = [
        'ABANDONED', 'CANCELLED', 'DECLINED', 'FAILED', 'RESTRICTED', 'VOID',
    ];

    private $stockService;
    private $cashboxes;

    public function __construct(WebsiteOrderStockService $stockService, CashboxService $cashboxes)
    {
        $this->stockService = $stockService;
        $this->cashboxes = $cashboxes;
    }

    public function finalize(array $charge): TapPaymentResult
    {
        $chargeId = (string) ($charge['id'] ?? '');
        $orderId = (int) ($charge['metadata']['order_id'] ?? 0);
        $status = strtoupper((string) ($charge['status'] ?? ''));

        if ($chargeId === '' || $orderId <= 0 || $status === '') {
            return new TapPaymentResult(TapPaymentResult::INVALID, null, 'Tap response is missing required payment data.');
        }

        $shouldNotify = false;
        $result = DB::transaction(function () use ($charge, $chargeId, $orderId, $status, &$shouldNotify) {
            $order = WebsiteOrder::query()->lockForUpdate()->find($orderId);
            if (!$order || !in_array($order->payment_type, ['card', 'pay_by_card'], true)) {
                return new TapPaymentResult(TapPaymentResult::INVALID, $order, 'The payment does not belong to a card order.');
            }

            $validationError = $this->validateCharge($order, $charge, $chargeId);
            if ($validationError) {
                Log::warning('Tap payment verification mismatch.', [
                    'order_id' => $order->id,
                    'charge_id' => $chargeId,
                    'reason' => $validationError,
                ]);
                return new TapPaymentResult(TapPaymentResult::INVALID, $order, $validationError);
            }

            if (!$order->invoice) {
                $order->invoice = $chargeId;
            }

            if ($status === 'CAPTURED') {
                $wasAwaitingPayment = in_array((int) $order->status, [
                    WebsiteOrder::STATUS_UNPAID,
                    WebsiteOrder::STATUS_FAILED,
                ], true);

                if (!$order->payment_captured_at) {
                    // New orders are reserved before redirecting to Tap. For an older unpaid
                    // order, reserve now; older already-accepted orders are left untouched.
                    if (!$order->stock_reserved_at && $wasAwaitingPayment) {
                        try {
                            $this->stockService->reserveLocked($order);
                        } catch (Throwable $exception) {
                            Log::critical('Captured Tap payment has insufficient stock.', [
                                'order_id' => $order->id,
                                'charge_id' => $chargeId,
                                'error' => $exception->getMessage(),
                            ]);
                        }
                    }

                    $order->forceFill([
                        'status' => WebsiteOrder::STATUS_PENDING,
                        'payment_captured_at' => now(),
                        'paid_price' => $order->total_price,
                        'remain_price' => 0,
                    ]);

                    if ($wasAwaitingPayment && !$order->notifications_sent_at) {
                        $shouldNotify = true;
                    }
                }

                $this->postCapturedCashbox($order, $chargeId);
                $order->save();

                return new TapPaymentResult(TapPaymentResult::CAPTURED, $order->fresh());
            }

            if (in_array($status, self::TERMINAL_FAILURES, true)) {
                if (!$order->payment_captured_at) {
                    $this->stockService->releaseLocked($order);
                    $order->forceFill(['status' => WebsiteOrder::STATUS_FAILED])->save();
                }

                return new TapPaymentResult(TapPaymentResult::FAILED, $order->fresh());
            }

            $order->save();
            return new TapPaymentResult(TapPaymentResult::PENDING, $order->fresh());
        }, 3);

        if ($shouldNotify && $result->order) {
            $result->order->dispatchNotifications();
        }

        return $result;
    }

    private function postCapturedCashbox(WebsiteOrder $order, string $chargeId): void
    {
        if ($order->cashbox_posted_at) {
            return;
        }

        $commerce = CountryCommerceSetting::forCountry((int) $order->country_id);
        if (!$commerce->website_cashbox_user_id) {
            return;
        }

        try {
            $currency = strtoupper((string) ($order->gateway_currency ?: $order->curr_type));
            $this->cashboxes->credit(
                (int) $commerce->website_cashbox_user_id,
                (float) ($order->gateway_amount ?: $order->total_price),
                $currency,
                "website-order:{$order->id}:card-captured",
                [
                    'exchange_rate' => $currency === 'USD' ? 1 : (float) ($order->gateway_rate ?: 1),
                    'payment_method' => 'card',
                    'source_type' => WebsiteOrder::class,
                    'source_id' => $order->id,
                    'note' => "Tap payment for website order #{$order->barcode}",
                ]
            );
            $order->cashbox_posted_at = now();
        } catch (Throwable $exception) {
            // A real captured payment must never be reverted merely because its
            // internal cashbox is temporarily unavailable. The next verified
            // callback/webhook will retry this idempotent posting.
            Log::critical('Captured Tap payment needs cashbox reconciliation.', [
                'order_id' => $order->id,
                'charge_id' => $chargeId,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function validateCharge(WebsiteOrder $order, array $charge, string $chargeId): ?string
    {
        if ($order->invoice && !hash_equals((string) $order->invoice, $chargeId)) {
            return 'Tap charge reference does not match the order reference.';
        }

        $currency = strtoupper((string) ($charge['currency'] ?? ''));
        $expectedCurrency = strtoupper((string) ($order->gateway_currency ?: $order->curr_type));
        if ($currency === '' || $currency !== $expectedCurrency) {
            return 'Tap charge currency does not match the order currency.';
        }

        if (!isset($charge['amount']) || !is_numeric($charge['amount'])) {
            return 'Tap charge amount is missing.';
        }

        $decimals = $currency === 'SYP' ? 0 : 2;
        $expected = round((float) ($order->gateway_amount ?: $order->total_price), $decimals);
        $actual = round((float) $charge['amount'], $decimals);
        $tolerance = $decimals === 0 ? 0.5 : 0.005;
        if (abs($expected - $actual) >= $tolerance) {
            return 'Tap charge amount does not match the order total.';
        }

        $referenceOrder = (string) ($charge['reference']['order'] ?? '');
        if ($referenceOrder !== '' && !hash_equals((string) $order->barcode, $referenceOrder)) {
            return 'Tap order reference does not match the order barcode.';
        }

        return null;
    }
}

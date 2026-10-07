<?php

namespace App\Services\Payment;

use App\Models\WebsiteOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TapPaymentFinalizer
{
    private const TERMINAL_FAILURES = [
        'ABANDONED', 'CANCELLED', 'DECLINED', 'FAILED', 'RESTRICTED', 'VOID',
    ];

    private $stockService;

    public function __construct(WebsiteOrderStockService $stockService)
    {
        $this->stockService = $stockService;
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
                    // The gateway result is kept for operational tracking. The
                    // separate manual sale is the only cashbox and stock event.

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

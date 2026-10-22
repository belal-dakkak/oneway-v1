<?php

namespace App\Services;

use App\Models\ClientDebit;
use App\Models\ClientDebitLog;
use App\Models\ClientDebitPayment;
use App\Models\Order;
use App\Models\OrderPayment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ClientAccountService
{
    private $currencies;
    private $cashboxes;
    private $reconciliations;

    public function __construct(
        CurrencyService $currencies,
        CashboxService $cashboxes,
        ClientDebtOrderReconciliationService $reconciliations
    ) {
        $this->currencies = $currencies;
        $this->cashboxes = $cashboxes;
        $this->reconciliations = $reconciliations;
    }

    public function syncOrderDebt(
        Order $order,
        float $oldRemain = 0,
        ?int $oldBuyerId = null,
        ?string $oldCurrency = null
    ): void {
        $newRemain = max(0, (float) $order->remain_price);
        $newBuyerId = $order->buyer_id ? (int) $order->buyer_id : null;
        $newCurrency = strtoupper((string) ($order->curr_type ?: 'USD'));
        $oldBuyerId = $oldBuyerId ?: $newBuyerId;
        $oldCurrency = strtoupper((string) ($oldCurrency ?: $newCurrency));

        if ($oldBuyerId === $newBuyerId && $oldCurrency === $newCurrency) {
            $this->adjustOrderDebt($order, $newBuyerId, $newCurrency, $newRemain - $oldRemain);
            return;
        }
        if ($oldBuyerId && $oldRemain > 0) {
            $this->adjustOrderDebt($order, $oldBuyerId, $oldCurrency, -$oldRemain);
        }
        if ($newBuyerId && $newRemain > 0) {
            $this->adjustOrderDebt($order, $newBuyerId, $newCurrency, $newRemain);
        }
    }

    public function payOrder(Order $order, float $amount): array
    {
        return DB::transaction(function () use ($order, $amount) {
            $expectedSellerId = (int) $order->seller_id;
            $expectedBuyerId = $order->buyer_id ? (int) $order->buyer_id : null;
            $expectedCurrency = strtoupper((string) ($order->curr_type ?: 'USD'));
            $account = $expectedBuyerId
                ? $this->findAccount($expectedSellerId, $expectedBuyerId, $expectedCurrency)
                : null;

            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            $currency = strtoupper((string) ($order->curr_type ?: 'USD'));
            if ((int) $order->seller_id !== $expectedSellerId
                || ($order->buyer_id ? (int) $order->buyer_id : null) !== $expectedBuyerId
                || $currency !== $expectedCurrency) {
                throw new InvalidArgumentException('Order account identity changed while the payment was being processed. Please retry.');
            }
            $amount = $this->validatePayment($amount, (float) $order->remain_price, $currency);
            $this->requireAccountCurrency((int) $order->seller_id, $currency);
            $rate = $this->paymentRate($currency);

            $payment = $order->payments()->create([
                'pay_amount' => $amount,
                'exchange_rate' => $rate,
                'base_amount' => $this->toBase($amount, $rate),
            ]);
            $newRemain = $this->money((float) $order->remain_price - $amount, $currency);
            $order->update([
                'paid_price' => $newRemain <= 0
                    ? $this->money($order->total_price, $currency)
                    : $this->money((float) $order->paid_price + $amount, $currency),
                'remain_price' => max(0, $newRemain),
            ]);

            $reconciliation = $this->emptyReconciliationResult($currency);
            if ($account) {
                $balanceBefore = $this->money($account->amount, $currency);
                $account->update(['amount' => $this->money($balanceBefore - $amount, $currency)]);
                $this->paymentLog($account, $amount, $rate, "Payment for invoice #{$order->barcode}");
                $account->refresh();
                $reconciliation = $this->reconciliations->settleLockedAccount(
                    $account,
                    'account_settled_after_order_payment',
                    $this->actorId(),
                    $balanceBefore
                );
            }

            $this->cashboxes->credit(
                (int) $order->seller_id,
                $amount,
                $currency,
                "order-payment:{$payment->id}",
                $this->cashboxContext($currency, $rate, OrderPayment::class, $payment->id, "Payment for order #{$order->barcode}")
            );

            $order->refresh();
            return array_merge($reconciliation, [
                'payment_amount' => $amount,
                'order' => [
                    'id' => (int) $order->id,
                    'paid_price' => (float) $order->paid_price,
                    'remain_price' => (float) $order->remain_price,
                    'is_paid' => $this->money($order->remain_price, $currency) <= 0,
                ],
            ]);
        });
    }

    public function payAccount(ClientDebit $account, float $amount): array
    {
        return DB::transaction(function () use ($account, $amount) {
            $account = ClientDebit::query()->lockForUpdate()->findOrFail($account->id);
            $currency = strtoupper((string) ($account->currency_code ?: 'USD'));
            $balanceBefore = $this->money($account->amount, $currency);
            $amount = $this->validatePayment($amount, max(0, $balanceBefore), $currency);
            $this->requireAccountCurrency((int) $account->creditor_id, $currency);
            $rate = $this->paymentRate($currency);
            $remaining = $amount;

            $orders = Order::query()
                ->where('seller_id', $account->creditor_id)
                ->where('buyer_id', $account->debtor_id)
                ->whereRaw('UPPER(curr_type) = ?', [$currency])
                ->where('remain_price', '>', 0)
                ->orderBy('created_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($orders as $order) {
                if ($this->money($remaining, $currency) <= 0) {
                    break;
                }
                $orderRemain = $this->money($order->remain_price, $currency);
                $allocated = $this->money(min($remaining, $orderRemain), $currency);
                if ($allocated <= 0) {
                    continue;
                }
                $order->payments()->create([
                    'pay_amount' => $allocated,
                    'exchange_rate' => $rate,
                    'base_amount' => $this->toBase($allocated, $rate),
                ]);
                $newRemain = $this->money($orderRemain - $allocated, $currency);
                $order->update([
                    'paid_price' => $newRemain <= 0
                        ? $this->money($order->total_price, $currency)
                        : $this->money((float) $order->paid_price + $allocated, $currency),
                    'remain_price' => max(0, $newRemain),
                ]);
                $remaining = $this->money($remaining - $allocated, $currency);
            }

            $account->update(['amount' => $this->money($balanceBefore - $amount, $currency)]);
            $payment = $this->paymentLog($account, $amount, $rate, 'Customer account payment');
            $account->refresh();
            $reconciliation = $this->reconciliations->settleLockedAccount(
                $account,
                'account_settled_after_account_payment',
                $this->actorId(),
                $balanceBefore
            );
            $this->cashboxes->credit(
                (int) $account->creditor_id,
                $amount,
                $currency,
                "client-account-payment:{$payment->id}",
                $this->cashboxContext($currency, $rate, ClientDebitPayment::class, $payment->id, 'Customer account payment')
            );

            return array_merge($reconciliation, [
                'payment_amount' => $amount,
                'unallocated_payment_amount' => max(0, $remaining),
            ]);
        });
    }

    public function refundToAccount(Order $order, float $amount, ?int $clientRefundId = null): ClientDebit
    {
        if (!$order->buyer_id || $amount <= 0) {
            throw new InvalidArgumentException('The refund order must have a customer.');
        }

        $currency = strtoupper((string) ($order->curr_type ?: 'USD'));
        $rate = (float) ($order->curr_rate ?: 1);
        $account = $this->account((int) $order->seller_id, (int) $order->buyer_id, $currency);
        $account->update(['amount' => $this->money((float) $account->amount - $amount, $currency)]);

        ClientDebitLog::query()->create([
            'client_debit_id' => $account->id,
            'client_refund_id' => $clientRefundId,
            'amount' => -$amount,
            'currency_code' => $currency,
            'exchange_rate' => $rate,
            'base_amount' => -$this->toBase($amount, $rate),
            'note' => "Refund from invoice #{$order->barcode}",
        ]);

        return $account;
    }

    public function reconcileSettledAccount(ClientDebit $account, string $reason = 'account_already_settled'): array
    {
        return DB::transaction(function () use ($account, $reason) {
            $account = ClientDebit::query()->lockForUpdate()->findOrFail($account->id);
            return $this->reconciliations->settleLockedAccount(
                $account,
                $reason,
                $this->actorId(),
                (float) $account->amount
            );
        });
    }

    public function withdrawCredit(ClientDebit $account, float $amount): void
    {
        DB::transaction(function () use ($account, $amount) {
            $account = ClientDebit::query()->lockForUpdate()->findOrFail($account->id);
            $available = max(0, -(float) $account->amount);
            $currency = strtoupper((string) ($account->currency_code ?: 'USD'));
            $amount = $this->validatePayment($amount, $available, $currency);
            $this->requireAccountCurrency((int) $account->creditor_id, $currency);
            $rate = $this->paymentRate($currency);
            $account->update(['amount' => $this->money((float) $account->amount + $amount, $currency)]);
            $log = ClientDebitLog::query()->create([
                'client_debit_id' => $account->id,
                'amount' => $amount,
                'currency_code' => $currency,
                'exchange_rate' => $rate,
                'base_amount' => $this->toBase($amount, $rate),
                'note' => 'Customer credit withdrawal',
            ]);
            $this->cashboxes->debit(
                (int) $account->creditor_id,
                $amount,
                $currency,
                "client-credit-withdrawal:{$log->id}",
                $this->cashboxContext($currency, $rate, ClientDebitLog::class, $log->id, 'Customer credit withdrawal')
            );
        });
    }

    public function account(int $creditorId, int $debtorId, string $currency): ClientDebit
    {
        $this->requireAccountCurrency($creditorId, $currency);
        $attributes = [
            'creditor_id' => $creditorId,
            'debtor_id' => $debtorId,
            'currency_code' => strtoupper($currency),
        ];
        $account = ClientDebit::query()->where($attributes)->lockForUpdate()->first();
        if (!$account) {
            $account = ClientDebit::query()->firstOrCreate($attributes, ['amount' => 0]);
            $account = ClientDebit::query()->whereKey($account->id)->lockForUpdate()->firstOrFail();
        }

        return $account;
    }

    private function adjustOrderDebt(Order $order, ?int $buyerId, string $currency, float $delta): void
    {
        if (!$buyerId || abs($delta) < 0.0001) {
            return;
        }

        $account = $delta > 0
            ? $this->account((int) $order->seller_id, $buyerId, $currency)
            : $this->findAccount((int) $order->seller_id, $buyerId, $currency);
        if (!$account) {
            return;
        }

        $account->update(['amount' => $this->money((float) $account->amount + $delta, $currency)]);
        $rate = (float) ($order->curr_rate ?: 1);
        ClientDebitLog::query()->create([
            'client_debit_id' => $account->id,
            'order_id' => $order->id,
            'amount' => $delta,
            'currency_code' => strtoupper($currency),
            'exchange_rate' => $rate,
            'base_amount' => $this->toBase($delta, $rate),
            'note' => "Invoice #{$order->barcode}: account changed by {$delta} {$currency}",
        ]);
    }

    private function findAccount(int $creditorId, int $debtorId, string $currency): ?ClientDebit
    {
        $this->requireAccountCurrency($creditorId, $currency);
        return ClientDebit::query()
            ->where('creditor_id', $creditorId)
            ->where('debtor_id', $debtorId)
            ->where('currency_code', strtoupper($currency))
            ->lockForUpdate()
            ->first();
    }

    private function paymentLog(ClientDebit $account, float $amount, float $rate, string $note): ClientDebitPayment
    {
        $payment = ClientDebitPayment::query()->create([
            'client_debit_id' => $account->id,
            'amount' => $amount,
            'exchange_rate' => $rate,
            'base_amount' => $this->toBase($amount, $rate),
        ]);
        ClientDebitLog::query()->create([
            'client_debit_id' => $account->id,
            'client_debit_payment_id' => $payment->id,
            'amount' => -$amount,
            'currency_code' => strtoupper((string) $account->currency_code),
            'exchange_rate' => $rate,
            'base_amount' => -$this->toBase($amount, $rate),
            'note' => $note,
        ]);

        return $payment;
    }

    private function cashboxContext(string $currency, float $rate, string $sourceType, ?int $sourceId, string $note): array
    {
        return [
            'exchange_rate' => $currency === 'USD' ? 1 : $rate,
            'payment_method' => 'cash',
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'note' => $note,
        ];
    }

    private function paymentRate(string $currency): float
    {
        return $currency === 'USD' ? 1.0 : $this->currencies->rate($currency);
    }

    private function requireAccountCurrency(int $owner, string $currency): void
    {
        if (app(OperationalCurrency::class)->code($owner) === 'AED' && strtoupper($currency) !== 'AED') {
            throw new InvalidArgumentException('UAE customer transactions require AED. Convert legacy accounts before posting payments.');
        }
    }

    private function validatePayment(float $amount, float $available, string $currency): float
    {
        $amount = $this->money($amount, $currency);
        $available = $this->money($available, $currency);
        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }
        if ($amount > $available) {
            throw new InvalidArgumentException('Payment amount exceeds the outstanding balance.');
        }
        return min($amount, $available);
    }

    private function money($amount, string $currency): float
    {
        return $this->currencies->round((float) $amount, $currency);
    }

    private function actorId(): ?int
    {
        $id = auth()->id();
        return $id ? (int) $id : null;
    }

    private function emptyReconciliationResult(string $currency): array
    {
        return [
            'account_id' => null,
            'account_balance' => null,
            'account_closed' => false,
            'settled_order_ids' => [],
            'settled_orders_count' => 0,
            'reconciliation_total' => 0.0,
            'currency_code' => strtoupper($currency),
        ];
    }

    private function toBase(float $amount, float $rate): float
    {
        return $this->currencies->toUsdAtRate($amount, $rate ?: 1);
    }
}

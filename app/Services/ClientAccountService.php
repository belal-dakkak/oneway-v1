<?php

namespace App\Services;

use App\Models\ClientDebit;
use App\Models\ClientDebitLog;
use App\Models\ClientDebitPayment;
use App\Models\Order;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ClientAccountService
{
    private $currencies;

    public function __construct(CurrencyService $currencies)
    {
        $this->currencies = $currencies;
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

    public function payOrder(Order $order, float $amount): void
    {
        DB::transaction(function () use ($order, $amount) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            $amount = $this->validatePayment($amount, (float) $order->remain_price);
            $currency = strtoupper((string) ($order->curr_type ?: 'USD'));
            $rate = $this->paymentRate($currency);

            $order->payments()->create([
                'pay_amount' => $amount,
                'exchange_rate' => $rate,
                'base_amount' => $this->toBase($amount, $rate),
            ]);
            $order->update([
                'paid_price' => (float) $order->paid_price + $amount,
                'remain_price' => (float) $order->remain_price - $amount,
            ]);

            if ($order->buyer_id) {
                $account = $this->findAccount((int) $order->seller_id, (int) $order->buyer_id, $currency);
                if ($account) {
                    $account->decrement('amount', $amount);
                    $this->paymentLog($account, $amount, $rate, "دفعة للفاتورة #{$order->barcode}");
                }
            }

            $this->changeWallet((int) $order->seller_id, $this->toBase($amount, $rate));
        });
    }

    public function payAccount(ClientDebit $account, float $amount): void
    {
        DB::transaction(function () use ($account, $amount) {
            $account = ClientDebit::query()->lockForUpdate()->findOrFail($account->id);
            $amount = $this->validatePayment($amount, max(0, (float) $account->amount));
            $currency = strtoupper((string) ($account->currency_code ?: 'USD'));
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
                if ($remaining <= 0) {
                    break;
                }
                $allocated = min($remaining, (float) $order->remain_price);
                $order->payments()->create([
                    'pay_amount' => $allocated,
                    'exchange_rate' => $rate,
                    'base_amount' => $this->toBase($allocated, $rate),
                ]);
                $order->update([
                    'paid_price' => (float) $order->paid_price + $allocated,
                    'remain_price' => (float) $order->remain_price - $allocated,
                ]);
                $remaining -= $allocated;
            }

            $account->decrement('amount', $amount);
            $this->paymentLog($account, $amount, $rate, 'تسديد على حساب الزبون');
            $this->changeWallet((int) $account->creditor_id, $this->toBase($amount, $rate));
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
        $account->decrement('amount', $amount);

        ClientDebitLog::query()->create([
            'client_debit_id' => $account->id,
            'client_refund_id' => $clientRefundId,
            'amount' => -$amount,
            'currency_code' => $currency,
            'exchange_rate' => $rate,
            'base_amount' => -$this->toBase($amount, $rate),
            'note' => "مرتجع من الفاتورة #{$order->barcode}",
        ]);

        $cashPart = max(0, $amount - max(0, (float) $order->remain_price));
        if ($cashPart > 0) {
            $this->changeWallet((int) $order->seller_id, -$this->toBase($cashPart, $rate));
        }

        return $account;
    }

    public function withdrawCredit(ClientDebit $account, float $amount): void
    {
        DB::transaction(function () use ($account, $amount) {
            $account = ClientDebit::query()->lockForUpdate()->findOrFail($account->id);
            $available = max(0, -(float) $account->amount);
            $amount = $this->validatePayment($amount, $available);
            $currency = strtoupper((string) ($account->currency_code ?: 'USD'));
            $rate = $this->paymentRate($currency);
            $account->increment('amount', $amount);
            ClientDebitLog::query()->create([
                'client_debit_id' => $account->id,
                'amount' => $amount,
                'currency_code' => $currency,
                'exchange_rate' => $rate,
                'base_amount' => $this->toBase($amount, $rate),
                'note' => 'سحب من رصيد الزبون',
            ]);
            $this->changeWallet((int) $account->creditor_id, -$this->toBase($amount, $rate));
        });
    }

    public function account(int $creditorId, int $debtorId, string $currency): ClientDebit
    {
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

        $account->increment('amount', $delta);
        $rate = (float) ($order->curr_rate ?: 1);
        ClientDebitLog::query()->create([
            'client_debit_id' => $account->id,
            'order_id' => $order->id,
            'amount' => $delta,
            'currency_code' => strtoupper($currency),
            'exchange_rate' => $rate,
            'base_amount' => $this->toBase($delta, $rate),
            'note' => "فاتورة #{$order->barcode}: تغير الرصيد بمقدار {$delta} {$currency}",
        ]);
    }

    private function findAccount(int $creditorId, int $debtorId, string $currency): ?ClientDebit
    {
        return ClientDebit::query()
            ->where('creditor_id', $creditorId)
            ->where('debtor_id', $debtorId)
            ->where('currency_code', strtoupper($currency))
            ->lockForUpdate()
            ->first();
    }

    private function paymentLog(ClientDebit $account, float $amount, float $rate, string $note): void
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
    }

    private function changeWallet(int $userId, float $baseDelta): void
    {
        $wallet = Wallet::query()->where('user_id', $userId)->lockForUpdate()->first();
        if (!$wallet) {
            $wallet = Wallet::query()->create(['user_id' => $userId, 'credit' => 0, 'debit' => 0]);
        }
        $wallet->increment('credit', $baseDelta);
    }

    private function paymentRate(string $currency): float
    {
        return $currency === 'USD' ? 1.0 : $this->currencies->rate($currency);
    }

    private function validatePayment(float $amount, float $available): float
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('يجب أن يكون المبلغ أكبر من صفر.');
        }
        if ($amount > $available + 0.0001) {
            throw new InvalidArgumentException('المبلغ أكبر من القيمة المستحقة.');
        }
        return min($amount, $available);
    }

    private function toBase(float $amount, float $rate): float
    {
        return $this->currencies->toUsdAtRate($amount, $rate ?: 1);
    }
}

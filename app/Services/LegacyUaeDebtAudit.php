<?php

namespace App\Services;

use App\Models\ClientDebit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LegacyUaeDebtAudit
{
    public function report(): array
    {
        return ClientDebit::query()->where('currency_code', 'USD')
            ->whereHas('creditor', fn ($q) => $q->where('country_id', User::COUNTRY_UAE))
            ->orderBy('id')->get()->map(fn ($account) => $this->inspect($account))->all();
    }

    public function inspect(ClientDebit $account, bool $lock = false): array
    {
        $read = static function ($query) use ($lock) {
            return ($lock ? $query->lockForUpdate() : $query)->orderBy('id')->get();
        };
        $accounts = $read(DB::table('client_debits')->where('creditor_id', $account->creditor_id)->where('debtor_id', $account->debtor_id));
        $orders = $read(DB::table('orders')->where('seller_id', $account->creditor_id)->where('buyer_id', $account->debtor_id));
        $logs = $read(DB::table('client_debit_logs')->where('client_debit_id', $account->id));
        $payments = $read(DB::table('client_debit_payments')->where('client_debit_id', $account->id));
        $refunds = $read(DB::table('client_refunds')->where('client_debit_id', $account->id));
        $orderPayments = $read(DB::table('order_payments')->whereIn('order_id', $orders->pluck('id')));
        $movements = $read(DB::table('wallet_movements')->where('user_id', $account->creditor_id)
            ->where(function ($q) use ($payments, $logs) {
                $q->where(function ($q) use ($payments) {
                    $q->where('source_type', \App\Models\ClientDebitPayment::class)->whereIn('source_id', $payments->pluck('id'));
                })->orWhere(function ($q) use ($logs) {
                    $q->where('source_type', \App\Models\ClientDebitLog::class)->whereIn('source_id', $logs->pluck('id'));
                });
            }));
        $reasons = [];
        $near = static fn ($a, $b) => abs((float) $a - (float) $b) < 0.005;
        $target = $accounts->where('currency_code', 'AED');
        $remaining = round((float) $orders->sum('remain_price'), 2);
        if ((float) $account->amount <= 0) $reasons[] = 'zero_or_credit_balance';
        if ((int) optional($account->creditor)->country_id !== User::COUNTRY_UAE || $account->currency_code !== 'USD') $reasons[] = 'not_uae_usd_account';
        if (!$account->created_at || $account->created_at->toDateString() >= '2026-09-06') $reasons[] = 'not_before_currency_migration';
        if ($orders->isEmpty() || $orders->contains(fn ($o) => strtoupper((string) $o->curr_type) !== 'AED')) $reasons[] = 'missing_or_mixed_currency_orders';
        if ($target->count() > 1 || $accounts->where('currency_code', 'USD')->count() !== 1) $reasons[] = 'duplicate_accounts';
        if (!$near($remaining, $account->amount + $target->sum('amount'))) $reasons[] = 'balance_does_not_match_aed_order_remainders';
        if ($orders->contains(fn ($o) => !$near($o->total_price, $o->paid_price + $o->remain_price))) $reasons[] = 'order_totals_do_not_reconcile';
        if ($logs->isEmpty() || !$near($logs->sum('amount'), $account->amount)) $reasons[] = 'incomplete_account_ledger';
        $byId = $orders->keyBy('id');
        foreach ($logs as $log) {
            if ($log->client_refund_id || (!$log->order_id && !$log->client_debit_payment_id)
                || ($log->order_id && !$byId->has($log->order_id))) $reasons[] = 'unmatched_ledger_entry';
            if (abs((float) $log->base_amount) > 0.0001 || !in_array($log->currency_code, ['USD', 'AED'], true)) $reasons[] = 'explicit_currency_accounting_needs_review';
        }
        foreach ($payments as $payment) {
            $linked = $logs->where('client_debit_payment_id', $payment->id);
            if ($linked->count() !== 1 || !$near(-$linked->sum('amount'), $payment->amount)
                || abs((float) $payment->base_amount) > 0.0001) $reasons[] = 'unmatched_or_converted_payment';
        }
        if ($logs->contains(fn ($log) => $log->client_debit_payment_id && !$payments->contains('id', $log->client_debit_payment_id))) $reasons[] = 'missing_payment_record';
        if ($orderPayments->sum('pay_amount') > $orders->sum('paid_price') + 0.005) $reasons[] = 'order_payment_totals_conflict';
        if ($refunds->isNotEmpty() || $movements->isNotEmpty()) $reasons[] = 'refund_or_cashbox_history_needs_review';
        $rates = $orders->pluck('curr_rate')->map(fn ($rate) => round((float) $rate, 6))->unique();
        if ($rates->count() !== 1 || (float) $rates->first() <= 1) $reasons[] = 'historical_rate_not_unambiguous';

        return [
            'account_id' => $account->id, 'creditor_id' => $account->creditor_id, 'debtor_id' => $account->debtor_id,
            'status' => $reasons ? 'review_required' : 'proven_aed_label',
            'reasons' => array_values(array_unique($reasons)),
            'source_balance' => (float) $account->amount, 'target_account_id' => optional($target->first())->id,
            'target_balance' => (float) $target->sum('amount'), 'aed_order_remaining' => $remaining,
            'historical_rate' => (float) $rates->first(),
            // Diagnostic evidence only: a match here must never trigger automatic conversion.
            'base_conversion_matches' => $rates->count() === 1 && $near(
                $remaining, $account->amount * (float) $rates->first() + $target->sum('amount')
            ),
            'orders' => $orders->map(fn ($o) => ['id' => $o->id, 'currency' => $o->curr_type, 'rate' => $o->curr_rate,
                'total' => $o->total_price, 'paid' => $o->paid_price, 'remaining' => $o->remain_price])->all(),
            'payment_count' => $payments->count(), 'order_payment_count' => $orderPayments->count(),
            'fingerprint' => hash('sha256', json_encode([$accounts, $orders, $logs, $payments, $refunds, $orderPayments, $movements])),
        ];
    }

    public function apply(array $reviewed): string
    {
        return DB::transaction(function () use ($reviewed) {
            $account = ClientDebit::query()->lockForUpdate()->findOrFail($reviewed['account_id']);
            $marker = 'AED currency reconciliation source #' . $account->id . ' ' . $reviewed['fingerprint'];
            if (DB::table('client_debit_logs')->where('note', $marker)->exists()) return 'already_applied';
            $current = $this->inspect($account, true);
            if ($current['status'] !== 'proven_aed_label' || !hash_equals($current['fingerprint'], $reviewed['fingerprint'])) {
                throw new RuntimeException('Account evidence changed or requires manual review: ' . $account->id);
            }
            $target = app(ClientAccountService::class)->account($account->creditor_id, $account->debtor_id, 'AED');
            $amount = (float) $account->amount;
            $rate = $current['historical_rate'];
            $target->increment('amount', $amount);
            foreach (DB::table('client_debit_logs')->where('client_debit_id', $account->id)->get() as $log) {
                DB::table('client_debit_logs')->where('id', $log->id)->update([
                    'client_debit_id' => $target->id, 'currency_code' => 'AED', 'exchange_rate' => $rate,
                    'base_amount' => round((float) $log->amount / $rate, 4),
                ]);
            }
            foreach (DB::table('client_debit_payments')->where('client_debit_id', $account->id)->get() as $payment) {
                DB::table('client_debit_payments')->where('id', $payment->id)->update([
                    'client_debit_id' => $target->id, 'exchange_rate' => $rate,
                    'base_amount' => round((float) $payment->amount / $rate, 4),
                ]);
            }
            $account->update(['amount' => 0]);
            DB::table('client_debit_logs')->insert([
                'client_debit_id' => $target->id, 'note' => $marker, 'amount' => 0, 'currency_code' => 'AED',
                'exchange_rate' => $rate, 'base_amount' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);
            return 'applied';
        }, 3);
    }
}

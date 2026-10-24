<?php

namespace App\Services;

use App\Models\ClientDebit;
use App\Models\ClientDebitLog;
use App\Models\ClientDebtReconciliation;
use App\Models\Order;

class ClientDebtOrderReconciliationService
{
    private $currencies;
    private $orderScope;

    public function __construct(CurrencyService $currencies, ClientDebtOrderScope $orderScope)
    {
        $this->currencies = $currencies;
        $this->orderScope = $orderScope;
    }

    /**
     * Reconcile outstanding orders after their authoritative customer account
     * has reached zero or become credit. The caller must hold an account lock.
     */
    public function settleLockedAccount(
        ClientDebit $account,
        string $reason,
        ?int $actorId = null,
        ?float $balanceBefore = null
    ): array {
        $currency = strtoupper((string) ($account->currency_code ?: 'USD'));
        $balanceAfter = $this->money($account->amount, $currency);
        if ($balanceAfter > 0) {
            return $this->emptyResult($account, $balanceAfter);
        }

        $matchingAccounts = $this->orderScope->matchingAccountCount($account, true);
        if ($matchingAccounts !== 1) {
            $result = $this->emptyResult($account, $balanceAfter);
            $result['account_closed'] = true;
            $result['reconciliation_conflicts'] = [[
                'reason' => 'ambiguous_customer_accounts',
                'matching_accounts_count' => $matchingAccounts,
                'creditor_id' => (int) $account->creditor_id,
                'debtor_id' => (int) $account->debtor_id,
                'currency_code' => $currency,
            ]];
            return $result;
        }

        $orders = $this->outstandingOrdersQuery($account, $currency)
            ->lockForUpdate()
            ->get();

        $settledIds = [];
        $total = 0.0;
        foreach ($orders as $order) {
            $oldPaid = $this->money($order->paid_price, $currency);
            $oldRemain = $this->money($order->remain_price, $currency);
            $key = $this->idempotencyKey($account, $order, $oldPaid, $oldRemain, $reason);

            $audit = ClientDebtReconciliation::query()->firstOrCreate(
                ['idempotency_key' => $key],
                [
                    'client_debit_id' => $account->id,
                    'order_id' => $order->id,
                    'actor_id' => $actorId,
                    'currency_code' => $currency,
                    'previous_paid_price' => $oldPaid,
                    'previous_remain_price' => $oldRemain,
                    'reconciled_amount' => $oldRemain,
                    'account_balance_before' => $this->money($balanceBefore ?? $balanceAfter, $currency),
                    'account_balance_after' => $balanceAfter,
                    'reason' => $reason,
                ]
            );

            $order->update([
                'paid_price' => $this->money($order->total_price, $currency),
                'remain_price' => 0,
            ]);

            if ($audit->wasRecentlyCreated) {
                ClientDebitLog::query()->create([
                    'client_debit_id' => $account->id,
                    'order_id' => $order->id,
                    'amount' => 0,
                    'currency_code' => $currency,
                    'exchange_rate' => (float) ($order->curr_rate ?: 1),
                    'base_amount' => 0,
                    'note' => "Non-cash order reconciliation #{$order->barcode}: {$this->decimal($oldRemain, $currency)} {$currency}",
                ]);
            }

            $settledIds[] = (int) $order->id;
            $total = $this->money($total + $oldRemain, $currency);
        }

        return [
            'account_id' => (int) $account->id,
            'account_balance' => $balanceAfter,
            'account_closed' => true,
            'settled_order_ids' => $settledIds,
            'settled_orders_count' => count($settledIds),
            'reconciliation_total' => $total,
            'currency_code' => $currency,
            'reconciliation_conflicts' => [],
        ];
    }

    public function outstandingOrdersQuery(ClientDebit $account, ?string $currency = null)
    {
        $currency = strtoupper((string) ($currency ?: $account->currency_code ?: 'USD'));

        return $this->orderScope->applyAccountIdentity(Order::query(), $account)
            ->where('remain_price', '>', 0)
            ->orderBy('created_at')
            ->orderBy('id');
    }

    public function money($amount, string $currency): float
    {
        return $this->currencies->round((float) $amount, $currency);
    }

    public function decimal($amount, string $currency): string
    {
        return number_format($this->money($amount, $currency), strtoupper($currency) === 'SYP' ? 0 : 2, '.', '');
    }

    public function idempotencyKey(
        ClientDebit $account,
        Order $order,
        float $oldPaid,
        float $oldRemain,
        string $reason
    ): string {
        $currency = strtoupper((string) ($account->currency_code ?: 'USD'));

        return hash('sha256', implode('|', [
            'client-debt-order-reconciliation-v1',
            $account->id,
            $order->id,
            $currency,
            $this->decimal($oldPaid, $currency),
            $this->decimal($oldRemain, $currency),
            $reason,
        ]));
    }

    private function emptyResult(ClientDebit $account, float $balance): array
    {
        return [
            'account_id' => (int) $account->id,
            'account_balance' => $balance,
            'account_closed' => false,
            'settled_order_ids' => [],
            'settled_orders_count' => 0,
            'reconciliation_total' => 0.0,
            'currency_code' => strtoupper((string) ($account->currency_code ?: 'USD')),
            'reconciliation_conflicts' => [],
        ];
    }
}

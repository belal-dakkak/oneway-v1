<?php

namespace App\Services;

use App\Models\ClientDebit;
use App\Models\ClientDebtReconciliation;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class SettledClientOrdersReconciliation
{
    private const REASON = 'historical_settled_account';

    private $reconciliations;

    public function __construct(ClientDebtOrderReconciliationService $reconciliations)
    {
        $this->reconciliations = $reconciliations;
    }

    public function report(): array
    {
        $this->requireAuditTable();
        $rows = [];

        ClientDebit::query()->orderBy('id')->chunkById(200, function ($accounts) use (&$rows) {
            foreach ($accounts as $account) {
                $row = $this->snapshot($account);
                if ($row !== null) {
                    $rows[] = $row;
                }
            }
        });

        $report = [
            'version' => 1,
            'generated_at' => now()->toIso8601String(),
            'reason' => self::REASON,
            'accounts_count' => count($rows),
            'orders_count' => array_sum(array_column($rows, 'orders_count')),
            'reconciliation_total_by_currency' => $this->totals($rows),
            'accounts' => $rows,
        ];
        $report['signature'] = $this->sign($report);

        return $report;
    }

    public function apply(array $report, ?int $actorId = null): array
    {
        $this->requireAuditTable();
        $this->validateReport($report);

        return DB::transaction(function () use ($report, $actorId) {
            $settled = 0;
            $alreadyApplied = 0;
            $totalByCurrency = [];

            foreach ($report['accounts'] as $expected) {
                $account = ClientDebit::query()->lockForUpdate()->find($expected['account_id']);
                if (!$account) {
                    throw new RuntimeException("Customer account {$expected['account_id']} no longer exists; no changes were applied.");
                }

                $current = $this->snapshot($account, true);
                if ($current === null) {
                    if ($this->wasApplied($expected)) {
                        $alreadyApplied += (int) $expected['orders_count'];
                        continue;
                    }
                    throw new RuntimeException("Customer account {$account->id} or its orders changed after preview; no changes were applied.");
                }
                if (!hash_equals((string) $expected['fingerprint'], (string) $current['fingerprint'])) {
                    throw new RuntimeException("Customer account {$account->id} or its orders changed after preview; no changes were applied.");
                }

                $result = $this->reconciliations->settleLockedAccount(
                    $account,
                    self::REASON,
                    $actorId,
                    (float) $account->amount
                );
                $settled += (int) $result['settled_orders_count'];
                $currency = $result['currency_code'];
                $totalByCurrency[$currency] = $this->reconciliations->money(
                    ($totalByCurrency[$currency] ?? 0) + $result['reconciliation_total'],
                    $currency
                );
            }

            return [
                'settled_orders_count' => $settled,
                'already_applied_orders_count' => $alreadyApplied,
                'reconciliation_total_by_currency' => $totalByCurrency,
            ];
        }, 3);
    }

    private function snapshot(ClientDebit $account, bool $lockOrders = false): ?array
    {
        $currency = strtoupper((string) ($account->currency_code ?: 'USD'));
        $balance = $this->reconciliations->money($account->amount, $currency);
        if ($balance > 0) {
            return null;
        }

        $query = $this->reconciliations->outstandingOrdersQuery($account, $currency);
        if ($lockOrders) {
            $query->lockForUpdate();
        }
        $orders = $query->get();
        if ($orders->isEmpty()) {
            return null;
        }

        $orderRows = $orders->map(function (Order $order) use ($account, $currency) {
            $paid = $this->reconciliations->money($order->paid_price, $currency);
            $remain = $this->reconciliations->money($order->remain_price, $currency);
            return [
                'id' => (int) $order->id,
                'barcode' => (string) $order->barcode,
                'total_price' => $this->reconciliations->decimal($order->total_price, $currency),
                'paid_price' => $this->reconciliations->decimal($paid, $currency),
                'remain_price' => $this->reconciliations->decimal($remain, $currency),
                'updated_at' => optional($order->updated_at)->format('Y-m-d H:i:s.u'),
                'idempotency_key' => $this->reconciliations->idempotencyKey(
                    $account,
                    $order,
                    $paid,
                    $remain,
                    self::REASON
                ),
            ];
        })->values()->all();

        $row = [
            'account_id' => (int) $account->id,
            'creditor_id' => (int) $account->creditor_id,
            'debtor_id' => (int) $account->debtor_id,
            'currency_code' => $currency,
            'account_balance' => $this->reconciliations->decimal($balance, $currency),
            'account_updated_at' => optional($account->updated_at)->format('Y-m-d H:i:s.u'),
            'orders_count' => count($orderRows),
            'reconciliation_total' => $this->reconciliations->decimal(
                array_sum(array_map(fn ($order) => (float) $order['remain_price'], $orderRows)),
                $currency
            ),
            'orders' => $orderRows,
        ];
        $row['fingerprint'] = hash('sha256', $this->canonical($row));

        return $row;
    }

    private function wasApplied(array $expected): bool
    {
        foreach ($expected['orders'] as $order) {
            $current = Order::query()->lockForUpdate()->find($order['id']);
            if (!$current || $this->reconciliations->money($current->remain_price, $expected['currency_code']) > 0) {
                return false;
            }
            if (!ClientDebtReconciliation::query()->where('idempotency_key', $order['idempotency_key'])->exists()) {
                return false;
            }
        }

        return true;
    }

    private function totals(array $rows): array
    {
        $totals = [];
        foreach ($rows as $row) {
            $currency = $row['currency_code'];
            $totals[$currency] = $this->reconciliations->money(
                ($totals[$currency] ?? 0) + (float) $row['reconciliation_total'],
                $currency
            );
        }
        ksort($totals);
        return $totals;
    }

    private function validateReport(array $report): void
    {
        if (($report['version'] ?? null) !== 1 || !isset($report['accounts'], $report['signature'])) {
            throw new RuntimeException('Invalid or unsupported reconciliation report.');
        }
        $signature = (string) $report['signature'];
        unset($report['signature']);
        if (!hash_equals($signature, $this->sign($report))) {
            throw new RuntimeException('The reconciliation report was modified or belongs to another application key.');
        }
    }

    private function sign(array $report): string
    {
        unset($report['signature']);
        return hash_hmac('sha256', $this->canonical($report), (string) config('app.key'));
    }

    private function canonical(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
    }

    private function requireAuditTable(): void
    {
        if (!Schema::hasTable('client_debt_reconciliations')) {
            throw new RuntimeException('Run migrations before reconciling settled customer orders.');
        }
    }
}

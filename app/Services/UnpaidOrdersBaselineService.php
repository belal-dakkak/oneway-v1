<?php

namespace App\Services;

use App\Models\Order;
use App\Models\UnpaidOrderArchive;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class UnpaidOrdersBaselineService
{
    private const REPORT_VERSION = 1;
    private const REASON = 'historical_unpaid_tab_reset';

    private $orderScope;
    private $currencies;

    public function __construct(ClientDebtOrderScope $orderScope, CurrencyService $currencies)
    {
        $this->orderScope = $orderScope;
        $this->currencies = $currencies;
    }

    public function report(): array
    {
        $this->requireSchema();

        $orders = $this->eligibleOrdersQuery()
            ->with('seller:id,country_id')
            ->orderBy('orders.id')
            ->get();

        $rows = $orders->map(function (Order $order) {
            return $this->snapshot($order);
        })->values()->all();

        $report = [
            'version' => self::REPORT_VERSION,
            'generated_at' => now()->toIso8601String(),
            'reason' => self::REASON,
            'orders_count' => count($rows),
            'remaining_total_by_currency' => $this->totals($rows),
            'orders' => $rows,
        ];
        $report['signature'] = $this->sign($report);

        return $report;
    }

    public function apply(array $report, ?int $actorId = null): array
    {
        $this->requireSchema();
        $this->validateReport($report);

        return DB::transaction(function () use ($report, $actorId) {
            $archived = 0;
            $alreadyApplied = 0;
            $archivedAt = now();

            foreach ($report['orders'] as $expected) {
                $orderId = (int) ($expected['id'] ?? 0);
                $key = (string) ($expected['idempotency_key'] ?? '');
                $order = Order::query()->lockForUpdate()->find($orderId);

                if ($order && $order->unpaid_tab_archived_at
                    && UnpaidOrderArchive::query()->where('idempotency_key', $key)->exists()) {
                    $alreadyApplied++;
                    continue;
                }

                if (!$order) {
                    throw new RuntimeException("Order {$orderId} no longer exists; no changes were applied.");
                }

                $order->loadMissing('seller:id,country_id');
                $current = $this->snapshot($order);
                if (!isset($expected['fingerprint'])
                    || !hash_equals((string) $expected['fingerprint'], (string) $current['fingerprint'])
                    || !$this->eligibleOrdersQuery()->whereKey($orderId)->exists()) {
                    throw new RuntimeException("Order {$orderId} changed after preview; no changes were applied.");
                }

                $order->forceFill(['unpaid_tab_archived_at' => $archivedAt])->save();
                UnpaidOrderArchive::query()->create([
                    'order_id' => $order->id,
                    'actor_id' => $actorId,
                    'order_barcode' => $order->barcode,
                    'currency_code' => $current['currency_code'],
                    'previous_paid_price' => (float) $current['paid_price'],
                    'previous_remain_price' => (float) $current['remain_price'],
                    'reason' => self::REASON,
                    'idempotency_key' => $key,
                    'archived_at' => $archivedAt,
                ]);
                $archived++;
            }

            return [
                'archived_orders_count' => $archived,
                'already_applied_orders_count' => $alreadyApplied,
            ];
        }, 3);
    }

    private function eligibleOrdersQuery()
    {
        return $this->orderScope->applyAuthoritativeOutstanding(Order::query(), 'orders');
    }

    private function snapshot(Order $order): array
    {
        $countryId = (int) optional($order->seller)->country_id;
        $storedCurrency = strtoupper((string) ($order->curr_type ?: 'USD'));
        $currency = $this->orderScope->effectiveOrderCurrency($countryId, $storedCurrency);

        $row = [
            'id' => (int) $order->id,
            'barcode' => (string) $order->barcode,
            'seller_id' => (int) $order->seller_id,
            'buyer_id' => $order->buyer_id ? (int) $order->buyer_id : null,
            'stored_currency' => $storedCurrency,
            'currency_code' => $currency,
            'total_price' => $this->decimal($order->total_price, $currency),
            'paid_price' => $this->decimal($order->paid_price, $currency),
            'remain_price' => $this->decimal($order->remain_price, $currency),
            'created_at' => optional($order->created_at)->format('Y-m-d H:i:s.u'),
            'updated_at' => optional($order->updated_at)->format('Y-m-d H:i:s.u'),
        ];
        $row['idempotency_key'] = hash('sha256', implode('|', [
            'unpaid-orders-baseline-v1',
            $row['id'],
            $row['paid_price'],
            $row['remain_price'],
            $row['updated_at'],
            self::REASON,
        ]));
        $row['fingerprint'] = hash('sha256', $this->canonical($row));

        return $row;
    }

    private function totals(array $orders): array
    {
        $totals = [];
        foreach ($orders as $order) {
            $currency = $order['currency_code'];
            $totals[$currency] = $this->currencies->round(
                ($totals[$currency] ?? 0) + (float) $order['remain_price'],
                $currency
            );
        }
        ksort($totals);
        return $totals;
    }

    private function decimal($amount, string $currency): string
    {
        $decimals = strtoupper($currency) === 'SYP' ? 0 : 2;
        return number_format($this->currencies->round((float) $amount, $currency), $decimals, '.', '');
    }

    private function validateReport(array $report): void
    {
        if (($report['version'] ?? null) !== self::REPORT_VERSION
            || ($report['reason'] ?? null) !== self::REASON
            || !isset($report['orders'], $report['signature'])
            || !is_array($report['orders'])) {
            throw new RuntimeException('Invalid or unsupported unpaid-orders baseline report.');
        }

        $signature = (string) $report['signature'];
        unset($report['signature']);
        if (!hash_equals($signature, $this->sign($report))) {
            throw new RuntimeException('The unpaid-orders baseline report was modified or belongs to another application key.');
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

    private function requireSchema(): void
    {
        if (!Schema::hasColumn('orders', 'unpaid_tab_archived_at')
            || !Schema::hasTable('unpaid_order_archives')) {
            throw new RuntimeException('Run migrations before creating an unpaid-orders baseline.');
        }
    }
}

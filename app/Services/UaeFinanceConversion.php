<?php

namespace App\Services;

use App\Support\Country;
use Brick\Math\BigDecimal as Decimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** A reviewed, signed set of field changes. Never invokes posting/model events. */
class UaeFinanceConversion
{
    public const KEY = 'uae-all-finance-aed-v1';
    public const RATE = '3.675';
    private const TABLES = ['users', 'orders', 'order_items', 'order_payments', 'website_orders',
        'website_order_items', 'refunds', 'client_debits', 'client_debit_logs', 'client_debit_payments',
        'client_refunds', 'merchant_debits', 'debits', 'debit_logs', 'debit_payments', 'merchant_refunds',
        'expenses', 'wallets', 'wallet_movements', 'user_products', 'country_commerce_settings'];
    private array $data = [];
    private array $changes = [];
    private array $conflicts = [];
    private array $warnings = [];
    private array $childIndex = [];
    private array $clientReconciliationFailures = [];
    private string $snapshotFingerprint = '';

    // Retain only fields used by the conversion. Every original field is still
    // fingerprinted while streaming, including base prices and inventory.
    private const SNAPSHOT_FIELDS = [
        'orders' => ['id', 'seller_id', 'buyer_id', 'curr_type', 'curr_rate', 'total_price_before_discount',
            'discount', 'total_price', 'paid_price', 'remain_price', 'tax_value', 'price_without_tax',
            'shipping_fee', 'cod_fee', 'display_currency', 'display_rate', 'type', 'payment_type',
            'order_type', 'created_at'],
        'website_orders' => ['id', 'country_id', 'curr_type', 'curr_rate', 'total_price_before_discount',
            'discount', 'total_price', 'paid_price', 'remain_price', 'tax_value', 'price_without_tax',
            'shipping_fee', 'cod_fee', 'display_currency', 'display_rate'],
        'order_items' => ['id', 'order_id', 'item_price_paid', 'total_price_paid', 'tax_value_paid', 'price_without_tax_paid'],
        'website_order_items' => ['id', 'website_order_id', 'item_price', 'item_price_before_discount', 'total_price', 'total_price_before_discount'],
        'order_payments' => ['id', 'order_id', 'pay_amount', 'exchange_rate', 'base_amount'],
        'refunds' => ['id', 'order_item_id', 'total_price', 'total_price_paid', 'net_amount', 'tax_amount', 'cost_amount', 'currency_code'],
        'user_products' => [], // Fingerprint only: base inventory is never converted.
    ];

    public function report(?array $legacy = null, bool $lock = false): array
    {
        $this->changes = $this->conflicts = $this->warnings = $this->childIndex = $this->clientReconciliationFailures = [];
        $this->data = []; // Release the preview snapshot before revalidation on this instance.
        $this->data = $this->snapshot($lock);
        $fingerprint = $this->snapshotFingerprint;
        $this->orders('orders', 'order_items', 'order_id');
        $this->orders('website_orders', 'website_order_items', 'website_order_id');
        $this->clients($legacy);
        $this->merchants();
        $this->expenses();
        $this->wallets();
        $this->checkCoverage();
        foreach ($this->data['country_commerce_settings'] as $row) {
            if ((int) $row['country_id'] === Country::UAE) $this->set('country_commerce_settings', $row, 'gateway_currency', 'AED', 'UAE settlement policy');
        }
        $report = ['version' => self::KEY, 'rate' => self::RATE, 'fingerprint' => $fingerprint,
            'legacy_source' => $legacy, 'conflicts' => $this->conflicts, 'warnings' => $this->warnings,
            'changes' => array_values($this->changes)];
        $report['signature'] = $this->signature($report);
        $this->data = $this->childIndex = $this->changes = $this->warnings = $this->clientReconciliationFailures = [];
        return $report;
    }

    public function apply(array $report): string
    {
        $signature = $report['signature'] ?? '';
        unset($report['signature']);
        if (!hash_equals($this->signature($report), $signature) || ($report['version'] ?? '') !== self::KEY) {
            throw new RuntimeException('Report signature is invalid. Generate it on this server; do not edit it.');
        }
        if (!empty($report['conflicts'])) throw new RuntimeException('Resolve report conflicts before applying.');
        $manifest = $this->auditManifest($report, $signature);
        return DB::transaction(function () use ($report, $manifest) {
            // One unique batch row serializes even simultaneous first invocations.
            DB::table('finance_conversion_batches')->insertOrIgnore(['conversion_key' => self::KEY,
                'fingerprint' => $report['fingerprint'], 'report' => $manifest,
                'created_at' => now(), 'updated_at' => now()]);
            $batch = DB::table('finance_conversion_batches')->where('conversion_key', self::KEY)->lockForUpdate()->first();
            if ($batch->applied_at) return 'already_applied';
            if (!hash_equals((string) $batch->fingerprint, (string) $report['fingerprint'])) {
                throw new RuntimeException('An unapplied conversion batch exists for a different financial snapshot. No changes applied.');
            }
            $fresh = $this->report($report['legacy_source'], true);
            if ($fresh['fingerprint'] !== $report['fingerprint'] || $fresh['conflicts']
                || ($fresh['warnings'] ?? []) !== ($report['warnings'] ?? [])
                || $fresh['changes'] !== $report['changes']) {
                throw new RuntimeException('Financial data changed after the preview. No changes applied; generate a new report.');
            }
            // Reparent child rows before deleting merged source accounts/wallets.
            $deletes = [];
            foreach ($report['changes'] as $change) {
                DB::table('finance_conversion_changes')->insert(['batch_id' => $batch->id,
                    'table_name' => $change['table'], 'record_id' => $change['id'], 'field_name' => $change['field'],
                    'before_value' => json_encode($change['before']), 'after_value' => json_encode($change['after'])]);
                if ($change['field'] === '__deleted__') { $deletes[] = $change; continue; }
                DB::table($change['table'])->where('id', $change['id'])->update([$change['field'] => $change['after']]);
            }
            foreach ($deletes as $change) DB::table($change['table'])->where('id', $change['id'])->delete();
            DB::table('finance_conversion_batches')->where('id', $batch->id)->update(['applied_at' => now(), 'updated_at' => now()]);
            return 'applied';
        }, 3);
    }

    /** Keep the database audit row well below MySQL packet limits; field history is journalled separately. */
    private function auditManifest(array $report, string $signature): string
    {
        $changesHash = hash_init('sha256');
        foreach ($report['changes'] ?? [] as $change) {
            hash_update($changesHash, json_encode($change,
                JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR) . "\n");
        }
        $warningsHash = hash_init('sha256');
        foreach ($report['warnings'] ?? [] as $warning) {
            hash_update($warningsHash, json_encode($warning,
                JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR) . "\n");
        }

        return json_encode([
            'version' => $report['version'] ?? null,
            'rate' => $report['rate'] ?? null,
            'fingerprint' => $report['fingerprint'] ?? null,
            'report_signature' => $signature,
            'legacy_source_sha256' => $report['legacy_source']['sha256'] ?? null,
            'change_count' => count($report['changes'] ?? []),
            'changes_sha256' => hash_final($changesHash),
            'conflict_count' => count($report['conflicts'] ?? []),
            'warning_count' => count($report['warnings'] ?? []),
            'warnings_sha256' => hash_final($warningsHash),
        ], JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
    }

    private function snapshot(bool $lock): array
    {
        $result = [];
        $hash = hash_init('sha256');
        foreach (self::TABLES as $table) {
            hash_update($hash, $table . "\n");
            $query = DB::table($table);
            if ($table === 'users') $query->select('id', 'country_id', 'role_id'); // No credentials in audit files.
            if ($lock) $query->lockForUpdate();
            $result[$table] = [];
            $fields = isset(self::SNAPSHOT_FIELDS[$table]) ? array_flip(self::SNAPSHOT_FIELDS[$table]) : null;
            // chunkById bounds both PDO's result buffer and temporary collections.
            // cursor() alone still buffers an entire result with MySQL PDO.
            $query->chunkById(250, function ($rows) use (&$result, $table, $fields, $hash) {
                foreach ($rows as $object) {
                    $row = (array) $object;
                    hash_update($hash, json_encode($row, JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR) . "\n");
                    if ($fields === []) continue;
                    $result[$table][$row['id']] = $fields === null ? $row : array_intersect_key($row, $fields);
                }
            });
        }
        $this->snapshotFingerprint = hash_final($hash);
        return $result;
    }

    private function orders(string $table, string $itemsTable, string $foreign): void
    {
        foreach ($this->data[$table] as $order) {
            $uae = $table === 'orders' ? $this->uae($order['seller_id']) : (int) $order['country_id'] === Country::UAE;
            if (!$uae) continue;
            $code = strtoupper($order['curr_type'] ?? '');
            if (!in_array($code, ['USD', 'AED'], true)) { $this->conflict($table, $order, 'unknown_currency'); continue; }
            if ($code === 'AED') continue;
            if ($table === 'orders' && !$this->equal($order['curr_rate'], 1)) { $this->conflict($table, $order, 'usd_order_has_non_usd_rate'); continue; }
            $payments = $table === 'orders' ? $this->children('order_payments', 'order_id', $order['id']) : [];
            $items = $this->children($itemsTable, $foreign, $order['id']);
            $legacyCashPaidMissing = $table === 'orders' && $order['paid_price'] === null
                && (int) ($order['type'] ?? 0) === \App\Models\Order::TYPE_CASH
                && (int) ($order['payment_type'] ?? -1) === \App\Models\Order::PAY_CASH
                && empty($order['buyer_id']) && $this->equal($order['remain_price'] ?? 0, 0)
                && Decimal::of((string) $order['total_price'])->isGreaterThan(0)
                && count($items) > 0 && count($payments) === 0;
            $paidForReconciliation = $legacyCashPaidMissing ? $order['total_price'] : ($order['paid_price'] ?? 0);
            if ($legacyCashPaidMissing) {
                $this->warning($table, $order, 'legacy_cash_paid_total_restored', [
                    'item_count' => count($items), 'payment_count' => 0,
                    'restored_paid_price' => (string) $order['total_price'],
                ]);
            }
            if (!$this->equal($order['total_price'], Decimal::of((string) $paidForReconciliation)->plus((string) ($order['remain_price'] ?? 0)))) {
                $paymentTotal = Decimal::zero();
                foreach ($payments as $payment) $paymentTotal = $paymentTotal->plus((string) ($payment['pay_amount'] ?? 0));
                $this->conflict($table, $order, 'order_payments_do_not_reconcile', [
                    'item_count' => count($items),
                    'payment_count' => count($payments), 'payment_total' => (string) $paymentTotal,
                    'payments' => array_map(fn ($payment) => $this->diagnosticRow($payment), $payments),
                ]); continue;
            }
            $this->money($table, $order, ['total_price_before_discount', 'discount', 'total_price', 'paid_price',
                'remain_price', 'tax_value', 'price_without_tax', 'shipping_fee', 'cod_fee']);
            $total = $this->value($table, $order, 'total_price');
            if ($legacyCashPaidMissing) {
                $this->set($table, $order, 'paid_price', $total, 'legacy cash sale with zero remaining restored as fully paid');
            }
            $this->set($table, $order, 'remain_price', $this->subtract($total, $this->value($table, $order, 'paid_price')), 'total minus paid, rounded in AED');
            if (isset($order['price_without_tax'], $order['tax_value'])) {
                if ($this->equal($order['total_price'], Decimal::of((string) $order['price_without_tax'])->plus((string) $order['tax_value']))) {
                    $this->set($table, $order, 'tax_value', $this->subtract($total, $this->value($table, $order, 'price_without_tax')), 'gross minus net, rounded in AED');
                } elseif (!$this->equal($order['tax_value'], 0) || !$this->equal($order['price_without_tax'], 0)) {
                    $this->conflict($table, $order, 'original_gross_net_tax_do_not_reconcile');
                }
                // Old ordinary invoices can have both tax metadata fields unset/zero.
                // Do not reinterpret the entire sale as VAT in that case.
            }
            $this->set($table, $order, 'curr_type', 'AED', 'original USD order');
            $this->set($table, $order, 'curr_rate', self::RATE, 'retained USD base price conversion');
            foreach (['display_currency', 'display_rate'] as $field) if (array_key_exists($field, $order)) $this->set($table, $order, $field, null, 'single UAE transaction currency');
            foreach ($items as $item) {
                $fields = $table === 'orders'
                    ? ['item_price_paid', 'total_price_paid', 'tax_value_paid', 'price_without_tax_paid']
                    : ['item_price', 'item_price_before_discount', 'total_price', 'total_price_before_discount'];
                $this->money($itemsTable, $item, $fields);
                if ($table === 'orders') foreach ($this->children('refunds', 'order_item_id', $item['id']) as $refund) {
                    if (!empty($refund['currency_code']) && strtoupper($refund['currency_code']) !== 'USD') {
                        $this->conflict('refunds', $refund, 'refund_currency_disagrees_with_order'); continue;
                    }
                    $this->money('refunds', $refund, ['total_price_paid', 'net_amount', 'tax_amount', 'cost_amount']);
                    if (isset($refund['total_price_paid'], $refund['net_amount'], $refund['tax_amount'])) {
                        if ($this->equal($refund['total_price_paid'], Decimal::of((string) $refund['net_amount'])->plus((string) $refund['tax_amount']))) {
                            $this->set('refunds', $refund, 'tax_amount', $this->subtract(
                                $this->value('refunds', $refund, 'total_price_paid'), $this->value('refunds', $refund, 'net_amount')), 'refund gross minus net in AED');
                        } elseif (!$this->equal($refund['net_amount'], 0) || !$this->equal($refund['tax_amount'], 0)) {
                            $this->conflict('refunds', $refund, 'original_refund_tax_does_not_reconcile');
                        }
                    }
                    $this->set('refunds', $refund, 'currency_code', 'AED', 'linked USD order');
                }
            }
            if ($table === 'orders') foreach ($this->children('order_payments', 'order_id', $order['id']) as $payment) {
                if (!$this->usdPayment($payment, 'pay_amount')) {
                    $this->conflict('order_payments', $payment, 'payment_currency_disagrees_with_order'); continue;
                }
                $this->money('order_payments', $payment, ['pay_amount']);
                $this->set('order_payments', $payment, 'base_amount', (string) $payment['pay_amount'], 'original USD payment');
                $this->set('order_payments', $payment, 'exchange_rate', self::RATE, 'USD payment converted to AED; base retained');
            }
            // Gateway currency/amount/charge identifiers describe an actual charge and remain original evidence.
        }
    }

    private function clients(?array $legacy): void
    {
        $old = collect($legacy['accounts'] ?? [])->keyBy('account_id');
        $groups = [];
        foreach ($this->data['client_debits'] as $account) {
            if (!$this->uae($account['creditor_id'])) continue;
            $snapshot = $old->get($account['id']);
            $code = strtoupper($account['currency_code'] ?? '');
            if ($snapshot && ($snapshot['shop_id'] != $account['creditor_id'] || $snapshot['customer_id'] != $account['debtor_id'])) {
                $this->conflict('client_debits', $account, 'source_identity_mismatch'); continue;
            }
            $convert = $code === 'USD' || ($snapshot && $snapshot['source_currency'] === 'USD');
            if ($convert) {
                $mixedActivity = null;
                if ($snapshot && !$this->equal($snapshot['source_amount'], $account['amount'])) {
                    $mixedActivity = $this->reconcileLaterAedActivity($account, $snapshot);
                }
                if (!$snapshot || (!$this->equal($snapshot['source_amount'], $account['amount']) && !$mixedActivity)) {
                    $this->conflict('client_debits', $account, 'original_debt_snapshot_missing_or_balance_changed', [
                        'original_source' => $snapshot,
                        'account_logs' => array_map(fn ($row) => $this->diagnosticRow($row),
                            $this->children('client_debit_logs', 'client_debit_id', $account['id'])),
                        'account_payments' => array_map(fn ($row) => $this->diagnosticRow($row),
                            $this->children('client_debit_payments', 'client_debit_id', $account['id'])),
                        'refund_reconciliation' => $this->clientReconciliationFailures[$account['id']] ?? null,
                    ]); continue;
                }
                foreach ($this->children('client_debit_logs', 'client_debit_id', $account['id']) as $log) {
                    if (strpos($log['note'] ?? '', UaeDebtBalanceConversion::MARKER) === 0) $this->conflict('client_debits', $account, 'balance_only_conversion_already_applied');
                    if (isset($mixedActivity['preserved_log_ids'][$log['id']])) continue;
                    // A newer explicit base amount is evidence of a mixed-currency ledger.
                    if (!$this->usdPayment($log, 'amount')) {
                        $this->conflict('client_debit_logs', $log, 'mixed_or_already_converted_ledger'); continue;
                    }
                    $this->money('client_debit_logs', $log, ['amount']);
                    $this->set('client_debit_logs', $log, 'base_amount', (string) $log['amount'], 'original USD amount');
                    $this->set('client_debit_logs', $log, 'currency_code', 'AED', 'old USD account provenance');
                    $this->set('client_debit_logs', $log, 'exchange_rate', self::RATE, 'fixed conversion rate');
                }
                foreach ($this->children('client_debit_payments', 'client_debit_id', $account['id']) as $payment) {
                    if (isset($mixedActivity['preserved_payment_ids'][$payment['id']])) continue;
                    if (!$this->usdPayment($payment, 'amount')) {
                        $this->conflict('client_debit_payments', $payment, 'mixed_or_already_converted_payment'); continue;
                    }
                    $this->money('client_debit_payments', $payment, ['amount']);
                    $this->set('client_debit_payments', $payment, 'base_amount', (string) $payment['amount'], 'original USD payment');
                    $this->set('client_debit_payments', $payment, 'exchange_rate', self::RATE, 'fixed conversion rate');
                }
                if ($mixedActivity) {
                    $this->set('client_debits', $account, 'amount', $mixedActivity['target_amount'],
                        'Original USD snapshot converted once; verified later AED activity retained');
                    $key = 'client_debits:' . $account['id'] . ':amount';
                    if (isset($this->changes[$key])) $this->changes[$key]['reconciliation'] = $mixedActivity;
                } else $this->money('client_debits', $account, ['amount']);
                $this->set('client_debits', $account, 'currency_code', 'AED', 'original export proves USD');
            } elseif ($code !== 'AED') $this->conflict('client_debits', $account, 'unknown_currency');
            $groups[$account['creditor_id'] . ':' . $account['debtor_id']][] = $account;
        }
        foreach ($groups as $accounts) {
            if (count($accounts) < 2) continue;
            $target = collect($accounts)->firstWhere('currency_code', 'AED') ?: $accounts[0];
            $sum = Decimal::zero();
            foreach ($accounts as $account) {
                $sum = $sum->plus($this->value('client_debits', $account, 'amount'));
                if ($account['id'] === $target['id']) continue;
                foreach (['client_debit_logs', 'client_debit_payments', 'client_refunds'] as $table) {
                    foreach ($this->children($table, 'client_debit_id', $account['id']) as $child) $this->set($table, $child, 'client_debit_id', $target['id'], 'merge matching AED account');
                }
                $this->remove('client_debits', $account);
            }
            $this->set('client_debits', $target, 'amount', (string) $sum->toScale(2, RoundingMode::HALF_UP), 'sum of matched account balances');
        }
    }

    /** Preserve verified AED activity posted after a label-only legacy USD snapshot. */
    private function reconcileLaterAedActivity(array $account, array $snapshot): ?array
    {
        if (strtoupper((string) $account['currency_code']) !== 'AED' || $snapshot['source_currency'] !== 'USD') {
            return $this->refundReconciliationFailure($account, 'account_or_snapshot_currency_is_not_expected');
        }

        $activityTotal = $refundTotal = Decimal::zero();
        $legacyOrderIds = $modern = [];
        $lastOldDate = '';
        foreach ($this->children('client_debit_logs', 'client_debit_id', $account['id']) as $log) {
            if (strpos($log['note'] ?? '', UaeDebtBalanceConversion::MARKER) === 0) {
                return $this->refundReconciliationFailure($account, 'balance_only_conversion_was_already_applied');
            }
            if (!$this->usdPayment($log, 'amount')) { $modern[] = $log; continue; }
            if (!empty($log['order_id'])) $legacyOrderIds[$log['order_id']] = true;
            $lastOldDate = max($lastOldDate, $log['created_at']);
        }
        if (!$modern) return $this->refundReconciliationFailure($account, 'no_later_aed_activity_logs');

        $preserved = $preservedPayments = $preservedOrders = $refundIds = $sourceRefundIds = [];
        foreach ($modern as $log) {
            $amount = Decimal::of((string) $log['amount']);
            if (strtoupper((string) $log['currency_code']) !== 'AED'
                || !$this->equal($log['exchange_rate'] ?? 0, self::RATE)
                || !$this->equal($amount->dividedBy(self::RATE, 4, RoundingMode::HALF_UP), $log['base_amount'] ?? 0)) {
                return $this->refundReconciliationFailure($account, 'later_log_amount_or_currency_is_not_verified_aed', [
                    'log' => $this->diagnosticRow($log)]);
            }
            if (empty($log['created_at']) || $log['created_at'] <= $lastOldDate) {
                return $this->refundReconciliationFailure($account, 'later_activity_log_date_is_invalid', [
                    'log' => $this->diagnosticRow($log)]);
            }

            $links = (int) !empty($log['order_id']) + (int) !empty($log['client_debit_payment_id'])
                + (int) !empty($log['client_refund_id']);
            if ($links !== 1) {
                $reason = $amount->isLessThan(0)
                    ? 'client_refund_link_or_identity_mismatch'
                    : 'later_activity_source_is_missing_or_ambiguous';
                return $this->refundReconciliationFailure($account, $reason, ['log' => $this->diagnosticRow($log)]);
            }

            if (!empty($log['order_id'])) {
                $order = $this->data['orders'][$log['order_id']] ?? null;
                if (!$order || (int) $order['seller_id'] !== (int) $account['creditor_id']
                    || (int) $order['buyer_id'] !== (int) $account['debtor_id']
                    || strtoupper((string) $order['curr_type']) !== 'AED') {
                    return $this->refundReconciliationFailure($account, 'later_order_log_identity_or_currency_mismatch', [
                        'log' => $this->diagnosticRow($log),
                        'order' => $order ? $this->diagnosticRow($order) : null]);
                }
                $preservedOrders[$order['id']] = true;
                $preserved[$log['id']] = true;
                $activityTotal = $activityTotal->plus($amount);
                continue;
            }

            if (!empty($log['client_debit_payment_id'])) {
                $payment = $this->data['client_debit_payments'][$log['client_debit_payment_id']] ?? null;
                if (!$payment || isset($preservedPayments[$payment['id']])
                    || (int) $payment['client_debit_id'] !== (int) $account['id']
                    || $this->usdPayment($payment, 'amount')
                    || !$this->equal($payment['exchange_rate'] ?? 0, self::RATE)
                    || !$this->equal(Decimal::of((string) $payment['amount'])->dividedBy(self::RATE, 4, RoundingMode::HALF_UP), $payment['base_amount'] ?? 0)
                    || !$this->equal($amount, Decimal::of((string) $payment['amount'])->negated())) {
                    return $this->refundReconciliationFailure($account, 'later_payment_link_amount_or_currency_mismatch', [
                        'log' => $this->diagnosticRow($log),
                        'payment' => $payment ? $this->diagnosticRow($payment) : null]);
                }
                $preservedPayments[$payment['id']] = true;
                $preserved[$log['id']] = true;
                $activityTotal = $activityTotal->plus($amount);
                continue;
            }

            $clientRefund = $this->data['client_refunds'][$log['client_refund_id'] ?? 0] ?? null;
            if (!$clientRefund || isset($refundIds[$clientRefund['id']])
                || (int) $clientRefund['client_debit_id'] !== (int) $account['id']
                || (int) $clientRefund['client_id'] !== (int) $account['debtor_id']) {
                return $this->refundReconciliationFailure($account, 'client_refund_link_or_identity_mismatch', [
                    'log' => $this->diagnosticRow($log), 'client_refund' => $clientRefund ? $this->diagnosticRow($clientRefund) : null]);
            }
            $refund = $this->data['refunds'][$clientRefund['refund_id']] ?? null;
            $item = $refund ? ($this->data['order_items'][$refund['order_item_id']] ?? null) : null;
            $order = $item ? ($this->data['orders'][$item['order_id']] ?? null) : null;
            if (!$refund || !$item || !$order) {
                return $this->refundReconciliationFailure($account, 'refund_order_chain_is_missing', [
                    'client_refund' => $this->diagnosticRow($clientRefund),
                    'refund' => $refund ? $this->diagnosticRow($refund) : null]);
            }
            if (isset($sourceRefundIds[$refund['id']])) {
                return $this->refundReconciliationFailure($account, 'same_refund_is_linked_more_than_once', ['refund_id' => $refund['id']]);
            }
            // Older refunds did not store their own currency. They inherit the
            // linked sale currency, consistently with invoice/refund coverage.
            $refundCurrency = $refund['currency_code'] ?: $order['curr_type'];
            if (strtoupper((string) $order['curr_type']) !== 'AED' || strtoupper((string) $refundCurrency) !== 'AED') {
                return $this->refundReconciliationFailure($account, 'refund_or_order_currency_is_not_aed', [
                    'refund' => $this->diagnosticRow($refund), 'order' => $this->diagnosticRow($order)]);
            }
            $refundAmount = Decimal::of((string) ($refund['total_price_paid'] ?? 0));
            if ($refundAmount->isZero()) {
                $refundAmount = Decimal::of((string) ($refund['total_price'] ?? 0))
                    ->multipliedBy((string) ($order['curr_rate'] ?: 1))
                    ->toScale(2, RoundingMode::HALF_UP);
            }
            if (!$this->equal($refundAmount, $amount->negated())) {
                return $this->refundReconciliationFailure($account, 'refund_amount_does_not_match_ledger', [
                    'log' => $this->diagnosticRow($log), 'refund' => $this->diagnosticRow($refund)]);
            }
            $refundTotal = $refundTotal->plus($amount);
            $activityTotal = $activityTotal->plus($amount);
            $preserved[$log['id']] = true;
            $refundIds[$clientRefund['id']] = true;
            $sourceRefundIds[$refund['id']] = true;
        }
        if (count($refundIds) !== count($this->children('client_refunds', 'client_debit_id', $account['id']))) {
            return $this->refundReconciliationFailure($account, 'not_all_account_refunds_were_verified', [
                'verified_refunds' => count($refundIds),
                'account_refunds' => count($this->children('client_refunds', 'client_debit_id', $account['id']))]);
        }
        $modernPaymentIds = [];
        foreach ($this->children('client_debit_payments', 'client_debit_id', $account['id']) as $payment) {
            if (!$this->usdPayment($payment, 'amount')) $modernPaymentIds[$payment['id']] = true;
        }
        if (array_keys($modernPaymentIds) !== array_keys($preservedPayments)) {
            return $this->refundReconciliationFailure($account, 'not_all_later_aed_payments_were_verified', [
                'verified_payment_ids' => array_keys($preservedPayments),
                'modern_payment_ids' => array_keys($modernPaymentIds)]);
        }

        $expectedCurrent = Decimal::of((string) $snapshot['source_amount'])->plus($activityTotal);
        if (!$this->equal($expectedCurrent, $account['amount'])) {
            return $this->refundReconciliationFailure($account, 'later_activity_does_not_explain_current_balance', [
                'snapshot_usd_numeric_balance' => $snapshot['source_amount'],
                'later_aed_activity_total' => (string) $activityTotal,
                'expected_current_balance' => (string) $expectedCurrent,
                'actual_current_balance' => $account['amount']]);
        }
        $converted = Decimal::of((string) $snapshot['source_amount'])->multipliedBy(self::RATE)
            ->toScale(2, RoundingMode::HALF_UP);
        return ['original_usd_balance' => (string) $snapshot['source_amount'],
            'converted_snapshot_aed' => (string) $converted,
            'preserved_activity_aed' => (string) $activityTotal,
            'preserved_refunds_aed' => (string) $refundTotal,
            'preserved_log_ids' => $preserved, 'preserved_payment_ids' => $preservedPayments,
            'preserved_order_ids' => array_keys($preservedOrders),
            'legacy_order_ids' => array_keys($legacyOrderIds),
            'client_refund_ids' => array_keys($refundIds),
            'target_amount' => (string) $converted->plus($activityTotal)->toScale(2, RoundingMode::HALF_UP)];
    }

    private function refundReconciliationFailure(array $account, string $reason, array $context = []): ?array
    {
        $this->clientReconciliationFailures[$account['id']] = ['reason' => $reason] + $context;
        return null;
    }

    private function merchants(): void
    {
        foreach (['merchant_debits', 'debits'] as $table) foreach ($this->data[$table] as $account) {
            if (!$this->uae($account['debtor_id']) && !$this->uae($account['creditor_id'])) continue;
            // Merchant inventory is settled in the destination shop's
            // operational currency, including genuine cross-country pairs.
            $settlement = $this->uae($account['debtor_id']) ? 'AED' : 'USD';
            $code = strtoupper($account['currency_code'] ?: 'USD'); // Legacy merchant amounts are base USD.
            if (!in_array($code, ['USD', 'AED'], true)) { $this->conflict($table, $account, 'unknown_currency'); continue; }
            if ($code !== 'USD' && $code !== $settlement) {
                $this->conflict($table, $account, 'merchant_settlement_currency_mismatch'); continue;
            }
            if ($settlement === 'AED' && $code === 'USD') $this->money($table, $account, ['amount']);
            $this->set($table, $account, 'currency_code', $settlement, 'destination shop operational currency');
            $this->set($table, $account, 'exchange_rate', $settlement === 'AED' ? self::RATE : '1', 'destination shop settlement rate');
            if ($table === 'merchant_debits') foreach (['debit_logs', 'debit_payments'] as $childTable) {
                foreach ($this->children($childTable, 'merchant_debit_id', $account['id']) as $child) {
                    $childCode = strtoupper((string) ($child['currency_code'] ?: 'USD'));
                    if (!in_array($childCode, ['USD', $settlement], true)) {
                        $this->conflict($childTable, $child, 'mixed_merchant_ledger'); continue;
                    }
                    if ($settlement === 'AED' && $childCode === 'USD') $this->money($childTable, $child, ['amount']);
                    $this->set($childTable, $child, 'currency_code', $settlement, 'destination shop operational currency');
                    $this->set($childTable, $child, 'exchange_rate', $settlement === 'AED' ? self::RATE : '1', 'destination shop settlement rate');
                }
            }
        }
        $groups = [];
        foreach ($this->data['merchant_debits'] as $row) {
            if ($this->uae($row['debtor_id']) && $this->uae($row['creditor_id'])) {
                $groups[$row['creditor_id'] . ':' . $row['debtor_id']][] = $row;
            }
        }
        foreach ($groups as $accounts) {
            if (count($accounts) < 2) continue;
            $target = collect($accounts)->firstWhere('currency_code', 'AED') ?: $accounts[0];
            $sum = Decimal::zero();
            foreach ($accounts as $account) {
                $sum = $sum->plus($this->value('merchant_debits', $account, 'amount'));
                if ($account['id'] === $target['id']) continue;
                foreach (['debit_logs', 'debit_payments', 'merchant_refunds'] as $table) {
                    foreach ($this->children($table, 'merchant_debit_id', $account['id']) as $child) {
                        $this->set($table, $child, 'merchant_debit_id', $target['id'], 'merge matching merchant AED account');
                    }
                }
                $this->remove('merchant_debits', $account);
            }
            $this->set('merchant_debits', $target, 'amount', (string) $sum->toScale(2, RoundingMode::HALF_UP), 'sum of merchant balances in AED');
        }
    }

    private function expenses(): void
    {
        foreach ($this->data['expenses'] as $expense) {
            if (!$this->uae($expense['issuer_id'])) continue;
            if (($expense['currency_code'] ?? 'AED') === 'USD') $this->money('expenses', $expense, ['amount']);
            elseif (!in_array($expense['currency_code'], [null, 'AED'], true)) { $this->conflict('expenses', $expense, 'unknown_expense_currency'); continue; }
            $this->set('expenses', $expense, 'currency_code', 'AED', 'expense input is local UAE amount');
            $this->set('expenses', $expense, 'exchange_rate', self::RATE, 'AED metadata; original local amount retained');
        }
    }

    private function wallets(): void
    {
        $groups = [];
        foreach ($this->data['wallets'] as $wallet) if ($this->uae($wallet['user_id'])) $groups[$wallet['user_id']][] = $wallet;
        foreach ($groups as $wallets) {
            // A legacy balance without matching history cannot be rebuilt from
            // those movements. Do not propose deleting/resetting that owner’s wallets.
            $openingRows = [];
            $invalid = false;
            foreach ($wallets as $wallet) {
                $walletCode = strtoupper((string) ($wallet['currency_code'] ?: 'USD'));
                if (!in_array($walletCode, ['USD', 'AED'], true)) {
                    $this->conflict('wallets', $wallet, 'unknown_currency');
                    $invalid = true;
                }
                $recordedCredit = $recordedDebit = Decimal::zero();
                $movements = $this->children('wallet_movements', 'wallet_id', $wallet['id']);
                foreach ($movements as $movement) {
                    if ($movement['direction'] === 'credit') $recordedCredit = $recordedCredit->plus((string) $movement['amount']);
                    elseif ($movement['direction'] === 'debit') $recordedDebit = $recordedDebit->plus((string) $movement['amount']);
                    else { $this->conflict('wallet_movements', $movement, 'unknown_direction'); $invalid = true; }
                    if (strtoupper((string) $movement['currency_code']) !== $walletCode
                        || (int) $movement['user_id'] !== (int) $wallet['user_id']) {
                        $this->conflict('wallet_movements', $movement, 'wallet_owner_or_currency_mismatch');
                        $invalid = true;
                    }
                }
                $openingCredit = Decimal::of((string) $wallet['credit'])->minus($recordedCredit);
                $openingDebit = Decimal::of((string) $wallet['debit'])->minus($recordedDebit);
                $openingRows[$wallet['id']] = ['wallet_id' => $wallet['id'],
                    'original_currency' => $walletCode,
                    'opening_credit' => (string) $openingCredit, 'opening_debit' => (string) $openingDebit,
                    'movement_count' => count($movements)];
            }
            if ($invalid) continue;
            $target = collect($wallets)->firstWhere('currency_code', 'AED') ?: $wallets[0];
            $credits = $debits = $openingBalance = Decimal::zero();
            $allMovements = [];
            foreach ($wallets as $wallet) {
                $code = strtoupper($wallet['currency_code'] ?: 'USD');
                if (!in_array($code, ['USD', 'AED'], true)) { $this->conflict('wallets', $wallet, 'unknown_currency'); continue; }
                $movements = $this->children('wallet_movements', 'wallet_id', $wallet['id']);
                $newMovementCredit = $newMovementDebit = Decimal::zero();
                foreach ($movements as $movement) {
                    if ($code === 'USD') {
                        $this->money('wallet_movements', $movement, ['amount']);
                        if ($movement['source_type'] === \App\Models\Expense::class) {
                            $expense = $this->data['expenses'][$movement['source_id']] ?? null;
                            if (!$expense || (int) $expense['issuer_id'] !== (int) $wallet['user_id']) {
                                $this->conflict('wallet_movements', $movement, 'missing_expense_source');
                            } else $this->set('wallet_movements', $movement, 'amount', $this->value('expenses', $expense, 'amount'), 'original local expense amount');
                        }
                        $this->set('wallet_movements', $movement, 'currency_code', 'AED', 'USD wallet history');
                        $this->set('wallet_movements', $movement, 'exchange_rate', self::RATE, 'fixed conversion rate');
                    }
                    $newAmount = $this->value('wallet_movements', $movement, 'amount');
                    if ($movement['direction'] === 'credit') $newMovementCredit = $newMovementCredit->plus($newAmount);
                    else $newMovementDebit = $newMovementDebit->plus($newAmount);
                    $this->set('wallet_movements', $movement, 'wallet_id', $target['id'], 'unified AED wallet');
                    $allMovements[] = $movement;
                }
                $newWalletCredit = $code === 'USD'
                    ? Decimal::of((string) $wallet['credit'])->multipliedBy(self::RATE)->toScale(2, RoundingMode::HALF_UP)
                    : Decimal::of((string) $wallet['credit']);
                $newWalletDebit = $code === 'USD'
                    ? Decimal::of((string) $wallet['debit'])->multipliedBy(self::RATE)->toScale(2, RoundingMode::HALF_UP)
                    : Decimal::of((string) $wallet['debit']);
                $credits = $credits->plus($newWalletCredit);
                $debits = $debits->plus($newWalletDebit);
                $openingBalance = $openingBalance
                    ->plus($newWalletCredit)->minus($newWalletDebit)
                    ->minus($newMovementCredit)->plus($newMovementDebit);
                if ($wallet['id'] !== $target['id']) $this->remove('wallets', $wallet);
            }
            usort($allMovements, fn ($a, $b) => [$a['created_at'], $a['id']] <=> [$b['created_at'], $b['id']]);
            $balance = $openingBalance;
            foreach ($allMovements as $movement) {
                $amount = $this->value('wallet_movements', $movement, 'amount');
                $balance = $movement['direction'] === 'credit' ? $balance->plus($amount) : $balance->minus($amount);
                $this->set('wallet_movements', $movement, 'balance_after', (string) $balance->toScale(2, RoundingMode::HALF_UP), 'reconciled chronological AED balance');
            }
            $this->set('wallets', $target, 'currency_code', 'AED', 'unified UAE cashbox');
            $this->set('wallets', $target, 'credit', (string) $credits->toScale(2, RoundingMode::HALF_UP), 'sum of converted credits');
            $this->set('wallets', $target, 'debit', (string) $debits->toScale(2, RoundingMode::HALF_UP), 'sum of converted debits');
            foreach (['credit', 'debit'] as $field) {
                $key = 'wallets:' . $target['id'] . ':' . $field;
                if (isset($this->changes[$key])) $this->changes[$key]['opening_balances'] = array_values($openingRows);
            }
        }
    }

    private function money(string $table, array $row, array $fields): void
    {
        foreach ($fields as $field) if (isset($row[$field])) {
            $exact = Decimal::of((string) $row[$field])->multipliedBy(self::RATE);
            $rounded = $exact->toScale(2, RoundingMode::HALF_UP);
            $this->set($table, $row, $field, (string) $rounded, 'USD * 3.675; half-up to AED cents; USD base fields retained');
            $key = $table . ':' . $row['id'] . ':' . $field;
            if (isset($this->changes[$key])) {
                $this->changes[$key]['unrounded_aed'] = (string) $exact;
                $this->changes[$key]['rounding_difference'] = (string) $rounded->minus($exact);
            }
        }
    }

    private function checkCoverage(): void
    {
        // A USD-labelled child of an AED transaction is not proof its amount is USD.
        foreach ($this->data['refunds'] as $refund) {
            $item = $this->data['order_items'][$refund['order_item_id']] ?? null;
            $order = $item ? ($this->data['orders'][$item['order_id']] ?? null) : null;
            if (!$order || !$this->uae($order['seller_id'])) continue;
            $code = $this->value('refunds', $refund, 'currency_code') ?: $this->value('orders', $order, 'curr_type');
            if (strtoupper((string) $code) !== 'AED') $this->conflict('refunds', $refund, 'refund_currency_needs_original_evidence');
        }
        foreach (['client_debit_logs', 'client_debit_payments'] as $table) foreach ($this->data[$table] as $row) {
            $account = $this->data['client_debits'][$row['client_debit_id']] ?? null;
            if (!$account || !$this->uae($account['creditor_id'])) continue;
            if ($table === 'client_debit_logs') {
                if ($this->value($table, $row, 'currency_code') !== 'AED') $this->conflict($table, $row, 'ledger_currency_needs_original_evidence');
                if (!empty($row['order_id'])) {
                    $order = $this->data['orders'][$row['order_id']] ?? null;
                    if ($order && ((int) $order['seller_id'] !== (int) $account['creditor_id'] || (int) $order['buyer_id'] !== (int) $account['debtor_id'])) {
                        $this->warning($table, $row, 'order_account_identity_mismatch_preserved');
                    }
                }
            }
        }
        foreach (['debit_logs', 'debit_payments', 'merchant_refunds'] as $table) foreach ($this->data[$table] as $row) {
            $account = $this->data['merchant_debits'][$row['merchant_debit_id']] ?? null;
            if (!$account || !$this->uae($account['debtor_id'])) continue;
            if ($table !== 'merchant_refunds' && $this->value($table, $row, 'currency_code') !== 'AED') {
                $this->conflict($table, $row, 'merchant_child_currency_needs_original_evidence');
            }
        }
        foreach ($this->data['wallet_movements'] as $row) {
            if (!$this->uae($row['user_id'])) continue;
            $tables = [\App\Models\Order::class => 'orders', \App\Models\WebsiteOrder::class => 'website_orders',
                \App\Models\OrderPayment::class => 'order_payments', \App\Models\ClientDebitPayment::class => 'client_debit_payments',
                \App\Models\ClientDebitLog::class => 'client_debit_logs', \App\Models\Refund::class => 'refunds',
                \App\Models\Expense::class => 'expenses', \App\Models\Debit::class => 'debits',
                \App\Models\DebitPayment::class => 'debit_payments', \App\Models\MerchantRefund::class => 'merchant_refunds',
                'client_refund' => 'client_refunds', \App\Models\User::class => 'users'];
            $table = $tables[$row['source_type']] ?? null;
            if ($table && !isset($this->data[$table][$row['source_id']])) $this->conflict('wallet_movements', $row, 'missing_financial_source');
            $source = $table ? ($this->data[$table][$row['source_id']] ?? null) : null;
            if ($table === 'orders' && $source && !$this->uae($source['seller_id'])) $this->conflict('wallet_movements', $row, 'cross_country_order_posting');
            if ($table === 'website_orders' && $source && (int) $source['country_id'] !== Country::UAE) $this->conflict('wallet_movements', $row, 'cross_country_website_posting');
            $verifiedCrossCountry = $this->verifiedCrossCountryTransfer($row);
            if ($table === 'users' && $source && !$this->uae($source['id']) && !$verifiedCrossCountry) {
                $this->conflict('wallet_movements', $row, 'cross_country_sales_closure_needs_matching_transfer');
            }
            if (!$table && !empty($row['source_type'])) $this->conflict('wallet_movements', $row, 'unrecognized_financial_source');
            $amountField = ['order_payments' => 'pay_amount', 'client_debit_payments' => 'amount',
                'debit_payments' => 'amount', 'debits' => 'amount', 'expenses' => 'amount'][$table] ?? null;
            if ($source && $amountField && !$this->equal($this->value('wallet_movements', $row, 'amount'), $this->value($table, $source, $amountField))) {
                $this->conflict('wallet_movements', $row, 'cash_movement_disagrees_with_source_amount');
            }
            $owners = null;
            if ($source && $table === 'order_payments') {
                $order = $this->data['orders'][$source['order_id']] ?? null;
                $owners = $order ? [$order['seller_id']] : [];
            } elseif ($source && $table === 'client_debit_payments') {
                $account = $this->data['client_debits'][$source['client_debit_id']] ?? null;
                $owners = $account ? [$account['creditor_id']] : [];
            } elseif ($source && $table === 'debit_payments') {
                $account = $this->data['merchant_debits'][$source['merchant_debit_id']] ?? null;
                $owners = $account ? [$account['creditor_id'], $account['debtor_id']] : [];
            } elseif ($source && $table === 'debits') $owners = [$source['creditor_id'], $source['debtor_id']];
            elseif ($source && $table === 'expenses') $owners = [$source['issuer_id']];
            if ($owners !== null && !in_array((int) $row['user_id'], array_map('intval', $owners), true)) {
                $this->conflict('wallet_movements', $row, 'cash_movement_owner_disagrees_with_source');
            }
            if (!empty($row['exchange_group'])) {
                foreach ($this->childrenByValue('wallet_movements', 'exchange_group', $row['exchange_group']) as $other) {
                    if (!$this->uae($other['user_id']) && !$verifiedCrossCountry) {
                        $this->conflict('wallet_movements', $row, 'cross_country_transfer_group_needs_review');
                    }
                }
            }
        }
    }

    private function verifiedCrossCountryTransfer(array $row): bool
    {
        if (empty($row['exchange_group']) || ($row['source_type'] ?? null) !== \App\Models\User::class
            || !$this->uae($row['user_id'])) return false;
        $peers = $this->childrenByValue('wallet_movements', 'exchange_group', $row['exchange_group']);
        if (count($peers) !== 2) return false;
        $other = collect($peers)->first(fn ($peer) => (int) $peer['id'] !== (int) $row['id']);
        if (!$other || $this->uae($other['user_id'])
            || ($other['source_type'] ?? null) !== \App\Models\User::class
            || (int) $row['source_id'] !== (int) $other['user_id']
            || (int) $other['source_id'] !== (int) $other['user_id']
            || strtoupper((string) $row['currency_code']) !== 'USD'
            || strtoupper((string) $other['currency_code']) !== 'USD'
            || $row['direction'] === $other['direction']
            || !in_array($row['direction'], ['credit', 'debit'], true)
            || !$this->equal($row['amount'], $other['amount'])
            || !$this->equal($row['base_amount'], $other['base_amount'])) return false;
        $peerWallet = $this->data['wallets'][$other['wallet_id']] ?? null;
        return $peerWallet && (int) $peerWallet['user_id'] === (int) $other['user_id'];
    }

    private function set(string $table, array $row, string $field, $value, string $evidence): void
    {
        $before = $row[$field] ?? null;
        $key = $table . ':' . $row['id'] . ':' . $field;
        if ($before === $value || ($before !== null && $value !== null && is_numeric($before) && is_numeric($value) && $this->equal($before, $value))) {
            unset($this->changes[$key]); return;
        }
        $this->changes[$key] = ['table' => $table, 'id' => $row['id'], 'field' => $field,
            'before' => $before, 'after' => $value, 'evidence' => $evidence];
    }

    private function remove(string $table, array $row): void
    {
        $this->changes[$table . ':' . $row['id'] . ':__deleted__'] = ['table' => $table, 'id' => $row['id'],
            'field' => '__deleted__', 'before' => $row, 'after' => null, 'evidence' => 'merged; all children reparented'];
    }

    private function value(string $table, array $row, string $field)
    {
        $key = $table . ':' . $row['id'] . ':' . $field;
        return isset($this->changes[$key]) ? $this->changes[$key]['after'] : ($row[$field] ?? 0);
    }

    private function children(string $table, string $field, int $id): array
    {
        return $this->childrenByValue($table, $field, (string) $id);
    }

    private function childrenByValue(string $table, string $field, string $id): array
    {
        $key = $table . ':' . $field;
        if (!isset($this->childIndex[$key])) {
            $this->childIndex[$key] = [];
            foreach ($this->data[$table] as $row) $this->childIndex[$key][(string) ($row[$field] ?? '')][] = $row;
        }
        return $this->childIndex[$key][$id] ?? [];
    }

    private function usdPayment(array $row, string $amount): bool
    {
        // MySQL DECIMAL values are strings: empty('0.0000') is false, unlike
        // SQLite's numeric zero. Compare numerically before diagnosing a mixed ledger.
        $rate = $row['exchange_rate'] ?? 0;
        $base = $row['base_amount'] ?? 0;
        return ($rate === '' || $this->equal($rate, 0) || $this->equal($rate, 1))
            && ($base === '' || $this->equal($base, 0) || $this->equal($base, $row[$amount]));
    }

    private function uae($userId): bool { return (int) ($this->data['users'][$userId]['country_id'] ?? 0) === Country::UAE; }
    private function equal($a, $b): bool { return Decimal::of((string) $a)->isEqualTo((string) $b); }
    private function subtract($a, $b): string { return (string) Decimal::of((string) $a)->minus((string) $b)->toScale(2, RoundingMode::HALF_UP); }
    private function conflict(string $table, array $row, string $reason, array $context = []): void
    {
        $this->conflicts[] = $this->issue($table, $row, $reason, $context);
    }

    private function warning(string $table, array $row, string $reason, array $context = []): void
    {
        $this->warnings[] = $this->issue($table, $row, $reason, $context);
    }

    private function issue(string $table, array $row, string $reason, array $context): array
    {
        $context['record'] = $this->diagnosticRow($row);
        $owners = $row;
        foreach (['client_debit_id' => 'client_debits', 'merchant_debit_id' => 'merchant_debits', 'order_id' => 'orders'] as $key => $relatedTable) {
            if (empty($row[$key])) continue;
            $related = $this->data[$relatedTable][$row[$key]] ?? null;
            $context[$relatedTable] = $related ? $this->diagnosticRow($related) : null;
            if ($related) $owners += $related;
        }
        foreach (['creditor_id', 'debtor_id', 'seller_id', 'buyer_id', 'user_id', 'issuer_id'] as $key) {
            if (!empty($owners[$key])) $context['owners'][$key] = $this->data['users'][$owners[$key]] ?? null;
        }
        if (($row['source_type'] ?? null) === \App\Models\User::class) {
            $context['source_user'] = $this->data['users'][$row['source_id']] ?? null;
        }
        if (!empty($row['exchange_group'])) {
            $context['transfer_movements'] = array_map(fn ($peer) => $this->diagnosticRow($peer),
                $this->childrenByValue('wallet_movements', 'exchange_group', $row['exchange_group']));
        }
        return ['table' => $table, 'id' => $row['id'], 'reason' => $reason, 'context' => $context];
    }

    private function diagnosticRow(array $row): array
    {
        // Financial evidence only: no names, phone numbers, free-text notes or credentials.
        return array_intersect_key($row, array_flip(['id', 'country_id', 'creditor_id', 'debtor_id', 'seller_id',
            'buyer_id', 'user_id', 'issuer_id', 'client_debit_id', 'merchant_debit_id', 'order_id',
            'client_debit_payment_id', 'client_refund_id', 'debit_payment_id', 'merchant_refund_id',
            'currency_code', 'curr_type', 'curr_rate', 'amount', 'pay_amount', 'base_amount', 'exchange_rate',
            'total_price', 'total_price_paid', 'net_amount', 'tax_amount', 'cost_amount',
            'paid_price', 'remain_price', 'price_without_tax', 'tax_value', 'order_item_id',
            'type', 'payment_type', 'order_type',
            'credit', 'debit', 'wallet_id', 'direction', 'balance_after', 'source_type', 'source_id',
            'exchange_group', 'created_at', 'updated_at']));
    }
    private function signature(array $report): string
    {
        $key = (string) config('app.key');
        if ($key === '') throw new RuntimeException('APP_KEY is required to sign conversion reports.');
        return hash_hmac('sha256', json_encode($report, JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR), $key);
    }
}

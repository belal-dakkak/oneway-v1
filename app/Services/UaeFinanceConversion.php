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
    private array $childIndex = [];
    private string $snapshotFingerprint = '';

    // Retain only fields used by the conversion. Every original field is still
    // fingerprinted while streaming, including base prices and inventory.
    private const SNAPSHOT_FIELDS = [
        'orders' => ['id', 'seller_id', 'buyer_id', 'curr_type', 'curr_rate', 'total_price_before_discount',
            'discount', 'total_price', 'paid_price', 'remain_price', 'tax_value', 'price_without_tax',
            'shipping_fee', 'cod_fee', 'display_currency', 'display_rate'],
        'website_orders' => ['id', 'country_id', 'curr_type', 'curr_rate', 'total_price_before_discount',
            'discount', 'total_price', 'paid_price', 'remain_price', 'tax_value', 'price_without_tax',
            'shipping_fee', 'cod_fee', 'display_currency', 'display_rate'],
        'order_items' => ['id', 'order_id', 'item_price_paid', 'total_price_paid', 'tax_value_paid', 'price_without_tax_paid'],
        'website_order_items' => ['id', 'website_order_id', 'item_price', 'item_price_before_discount', 'total_price', 'total_price_before_discount'],
        'order_payments' => ['id', 'order_id', 'pay_amount', 'exchange_rate', 'base_amount'],
        'refunds' => ['id', 'order_item_id', 'total_price_paid', 'net_amount', 'tax_amount', 'cost_amount', 'currency_code'],
        'user_products' => [], // Fingerprint only: base inventory is never converted.
    ];

    public function report(?array $legacy = null, bool $lock = false): array
    {
        $this->changes = $this->conflicts = $this->childIndex = [];
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
            'legacy_source' => $legacy, 'conflicts' => $this->conflicts, 'changes' => array_values($this->changes)];
        $report['signature'] = $this->signature($report);
        $this->data = $this->childIndex = $this->changes = [];
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
        return DB::transaction(function () use ($report) {
            // One unique batch row serializes even simultaneous first invocations.
            DB::table('finance_conversion_batches')->insertOrIgnore(['conversion_key' => self::KEY,
                'fingerprint' => $report['fingerprint'], 'report' => json_encode($report),
                'created_at' => now(), 'updated_at' => now()]);
            $batch = DB::table('finance_conversion_batches')->where('conversion_key', self::KEY)->lockForUpdate()->first();
            if ($batch->applied_at) return 'already_applied';
            $fresh = $this->report($report['legacy_source'], true);
            if ($fresh['fingerprint'] !== $report['fingerprint'] || $fresh['conflicts']
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
            if (!$this->equal($order['total_price'], Decimal::of((string) ($order['paid_price'] ?? 0))->plus((string) ($order['remain_price'] ?? 0)))) {
                $this->conflict($table, $order, 'order_payments_do_not_reconcile'); continue;
            }
            $this->money($table, $order, ['total_price_before_discount', 'discount', 'total_price', 'paid_price',
                'remain_price', 'tax_value', 'price_without_tax', 'shipping_fee', 'cod_fee']);
            $total = $this->value($table, $order, 'total_price');
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
            foreach ($this->children($itemsTable, $foreign, $order['id']) as $item) {
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
                if (!$snapshot || !$this->equal($snapshot['source_amount'], $account['amount'])) {
                    $this->conflict('client_debits', $account, 'original_debt_snapshot_missing_or_balance_changed', ['original_source' => $snapshot]); continue;
                }
                foreach ($this->children('client_debit_logs', 'client_debit_id', $account['id']) as $log) {
                    if (strpos($log['note'] ?? '', UaeDebtBalanceConversion::MARKER) === 0) $this->conflict('client_debits', $account, 'balance_only_conversion_already_applied');
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
                    if (!$this->usdPayment($payment, 'amount')) {
                        $this->conflict('client_debit_payments', $payment, 'mixed_or_already_converted_payment'); continue;
                    }
                    $this->money('client_debit_payments', $payment, ['amount']);
                    $this->set('client_debit_payments', $payment, 'base_amount', (string) $payment['amount'], 'original USD payment');
                    $this->set('client_debit_payments', $payment, 'exchange_rate', self::RATE, 'fixed conversion rate');
                }
                $this->money('client_debits', $account, ['amount']);
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

    private function merchants(): void
    {
        foreach (['merchant_debits', 'debits'] as $table) foreach ($this->data[$table] as $account) {
            if (!$this->uae($account['debtor_id']) && !$this->uae($account['creditor_id'])) continue;
            if (!$this->uae($account['debtor_id']) || !$this->uae($account['creditor_id'])) {
                $this->conflict($table, $account, 'cross_country_merchant_account'); continue;
            }
            $code = strtoupper($account['currency_code'] ?: 'USD'); // Legacy merchant amounts are base USD.
            if ($code === 'AED') continue;
            if ($code !== 'USD') { $this->conflict($table, $account, 'unknown_currency'); continue; }
            $this->money($table, $account, ['amount']);
            $this->set($table, $account, 'currency_code', 'AED', 'legacy merchant USD storage');
            $this->set($table, $account, 'exchange_rate', self::RATE, 'fixed conversion rate');
            if ($table === 'merchant_debits') foreach (['debit_logs', 'debit_payments'] as $childTable) {
                foreach ($this->children($childTable, 'merchant_debit_id', $account['id']) as $child) {
                    if (($child['currency_code'] ?? 'USD') === 'AED') { $this->conflict($childTable, $child, 'mixed_merchant_ledger'); continue; }
                    $this->money($childTable, $child, ['amount']);
                    $this->set($childTable, $child, 'currency_code', 'AED', 'legacy merchant USD storage');
                    $this->set($childTable, $child, 'exchange_rate', self::RATE, 'fixed conversion rate');
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
            $unreconciled = false;
            foreach ($wallets as $wallet) {
                $recordedCredit = $recordedDebit = Decimal::zero();
                $movements = $this->children('wallet_movements', 'wallet_id', $wallet['id']);
                foreach ($movements as $movement) {
                    if ($movement['direction'] === 'credit') $recordedCredit = $recordedCredit->plus((string) $movement['amount']);
                    if ($movement['direction'] === 'debit') $recordedDebit = $recordedDebit->plus((string) $movement['amount']);
                }
                if (!$this->equal($recordedCredit, $wallet['credit']) || !$this->equal($recordedDebit, $wallet['debit'])) {
                    $unreconciled = true;
                    $this->conflict('wallets', $wallet, 'cashbox_totals_do_not_match_recorded_movements', [
                        'recorded_credit' => (string) $recordedCredit, 'recorded_debit' => (string) $recordedDebit,
                        'unmatched_credit' => (string) Decimal::of((string) $wallet['credit'])->minus($recordedCredit),
                        'unmatched_debit' => (string) Decimal::of((string) $wallet['debit'])->minus($recordedDebit),
                        'movement_count' => count($movements),
                    ]);
                }
            }
            if ($unreconciled) continue;
            $target = collect($wallets)->firstWhere('currency_code', 'AED') ?: $wallets[0];
            $credits = $debits = Decimal::zero();
            $allMovements = [];
            foreach ($wallets as $wallet) {
                $code = strtoupper($wallet['currency_code'] ?: 'USD');
                if (!in_array($code, ['USD', 'AED'], true)) { $this->conflict('wallets', $wallet, 'unknown_currency'); continue; }
                $movements = $this->children('wallet_movements', 'wallet_id', $wallet['id']);
                $oldCredit = $oldDebit = $newCredit = $newDebit = Decimal::zero();
                foreach ($movements as $movement) {
                    if ($movement['currency_code'] !== $code || (int) $movement['user_id'] !== (int) $wallet['user_id']) {
                        $this->conflict('wallet_movements', $movement, 'wallet_owner_or_currency_mismatch'); continue;
                    }
                    $amount = (string) $movement['amount'];
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
                    if ($movement['direction'] === 'credit') { $oldCredit = $oldCredit->plus($amount); $newCredit = $newCredit->plus($newAmount); }
                    elseif ($movement['direction'] === 'debit') { $oldDebit = $oldDebit->plus($amount); $newDebit = $newDebit->plus($newAmount); }
                    else $this->conflict('wallet_movements', $movement, 'unknown_direction');
                    $this->set('wallet_movements', $movement, 'wallet_id', $target['id'], 'unified AED wallet');
                    $allMovements[] = $movement;
                }
                // Do not guess a missing opening cash balance.
                if (!$this->equal($oldCredit, $wallet['credit']) || !$this->equal($oldDebit, $wallet['debit'])) {
                    $this->conflict('wallets', $wallet, 'cashbox_totals_do_not_match_recorded_movements');
                }
                $credits = $credits->plus($newCredit);
                $debits = $debits->plus($newDebit);
                if ($wallet['id'] !== $target['id']) $this->remove('wallets', $wallet);
            }
            usort($allMovements, fn ($a, $b) => [$a['created_at'], $a['id']] <=> [$b['created_at'], $b['id']]);
            $balance = Decimal::zero();
            foreach ($allMovements as $movement) {
                $amount = $this->value('wallet_movements', $movement, 'amount');
                $balance = $movement['direction'] === 'credit' ? $balance->plus($amount) : $balance->minus($amount);
                $this->set('wallet_movements', $movement, 'balance_after', (string) $balance->toScale(2, RoundingMode::HALF_UP), 'reconciled chronological AED balance');
            }
            $this->set('wallets', $target, 'currency_code', 'AED', 'unified UAE cashbox');
            $this->set('wallets', $target, 'credit', (string) $credits->toScale(2, RoundingMode::HALF_UP), 'sum of converted credits');
            $this->set('wallets', $target, 'debit', (string) $debits->toScale(2, RoundingMode::HALF_UP), 'sum of converted debits');
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
                        $this->conflict($table, $row, 'order_account_identity_mismatch');
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
            if ($table === 'users' && $source && !$this->uae($source['id'])) $this->conflict('wallet_movements', $row, 'cross_country_sales_closure');
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
                    if (!$this->uae($other['user_id'])) $this->conflict('wallet_movements', $row, 'cross_country_transfer_group');
                }
            }
        }
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
        return (empty($row['exchange_rate']) || $this->equal($row['exchange_rate'], 1))
            && (empty($row['base_amount']) || $this->equal($row['base_amount'], $row[$amount]));
    }

    private function uae($userId): bool { return (int) ($this->data['users'][$userId]['country_id'] ?? 0) === Country::UAE; }
    private function equal($a, $b): bool { return Decimal::of((string) $a)->isEqualTo((string) $b); }
    private function subtract($a, $b): string { return (string) Decimal::of((string) $a)->minus((string) $b)->toScale(2, RoundingMode::HALF_UP); }
    private function conflict(string $table, array $row, string $reason, array $context = []): void
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
        $this->conflicts[] = ['table' => $table, 'id' => $row['id'], 'reason' => $reason, 'context' => $context];
    }

    private function diagnosticRow(array $row): array
    {
        // Financial evidence only: no names, phone numbers, free-text notes or credentials.
        return array_intersect_key($row, array_flip(['id', 'country_id', 'creditor_id', 'debtor_id', 'seller_id',
            'buyer_id', 'user_id', 'issuer_id', 'client_debit_id', 'merchant_debit_id', 'order_id',
            'client_debit_payment_id', 'client_refund_id', 'debit_payment_id', 'merchant_refund_id',
            'currency_code', 'curr_type', 'curr_rate', 'amount', 'pay_amount', 'base_amount', 'exchange_rate',
            'total_price', 'paid_price', 'remain_price', 'price_without_tax', 'tax_value',
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

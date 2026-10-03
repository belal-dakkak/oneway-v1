<?php

namespace App\Services;

use App\Support\Country;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;

class UaeDebtBalanceConversion
{
    public const RATE = '3.675';
    public const MARKER = 'uae-usd-balance-conversion:v1:';

    public function convert(array $snapshot, bool $apply = false): array
    {
        return DB::transaction(function () use ($snapshot, $apply) {
            $source = collect($snapshot['accounts']);
            $ids = $source->where('source_currency', 'USD')->pluck('account_id');
            // Every invocation locks in the same order. Read markers only AFTER these
            // locks, with a current read, so a concurrent invocation sees the commit.
            $accounts = DB::table('client_debits as d')
                ->leftJoin('users as shop', 'shop.id', '=', 'd.creditor_id')
                ->whereIn('d.id', $ids)->select('d.*', 'shop.country_id as shop_country_id')
                ->orderBy('d.id')->lockForUpdate()->get()->keyBy('id');
            $markers = DB::table('client_debit_logs')->whereIn('client_debit_id', $ids)
                ->where('note', 'like', self::MARKER . '%')
                ->orderBy('id')->lockForUpdate()->get()->groupBy('client_debit_id');

            $rows = [];
            foreach ($source as $item) {
                $row = $item + ['current_amount' => null, 'target_amount' => null,
                    'adjustment' => null, 'status' => 'excluded_aed', 'reason' => ''];
                if ($item['source_currency'] !== 'USD') {
                    $rows[] = $row;
                    continue;
                }
                $original = BigDecimal::of($item['source_amount']);
                $target = $original->multipliedBy(self::RATE)->toScale(2, RoundingMode::HALF_UP);
                $delta = $target->minus($original)->toScale(4);
                $row['target_amount'] = (string) $target;
                $row['adjustment'] = (string) $delta;
                $account = $accounts->get($item['account_id']);
                $row['current_amount'] = $account ? (string) BigDecimal::of((string) $account->amount)->toScale(4) : null;
                $reason = null;
                if (!$account) {
                    $reason = 'missing_account';
                } elseif ((int) $account->creditor_id !== $item['shop_id'] || (int) $account->debtor_id !== $item['customer_id']) {
                    $reason = 'account_identity_changed';
                } elseif ((int) $account->shop_country_id !== Country::UAE) {
                    $reason = 'not_uae_shop';
                } elseif ($account->currency_code !== 'AED') {
                    $reason = 'expected_aed_label';
                } elseif ($target->abs()->isGreaterThan('9999999999999999.9999') || $delta->abs()->isGreaterThan('9999999999999999.9999')) {
                    $reason = 'amount_exceeds_database_precision';
                }
                $existing = $markers->get($item['account_id'], collect());
                if (!$reason && $existing->isNotEmpty()) {
                    // Do not compare today's balance after a successful conversion:
                    // subsequent real sales/payments are allowed and must stay intact.
                    $expectedNote = $this->note($item, (string) $target, (string) $delta);
                    if ($existing->count() !== 1 || $existing->first()->note !== $expectedNote
                        || $existing->first()->currency_code !== 'AED'
                        || !BigDecimal::of((string) $existing->first()->amount)->isEqualTo($delta)) {
                        $reason = 'conversion_marker_conflict';
                    } else {
                        $row['status'] = 'already_applied';
                    }
                } elseif (!$reason) {
                    if (!$original->isEqualTo($row['current_amount'])) {
                        $reason = 'balance_changed_since_export';
                    } else {
                        $row['status'] = $original->isZero() ? 'zero_unchanged' : 'ready';
                    }
                }
                if ($reason) {
                    $row['status'] = 'conflict';
                    $row['reason'] = $reason;
                }
                $rows[] = $row;
            }

            $blocked = collect($rows)->contains('status', 'conflict');
            if ($apply && !$blocked) {
                foreach ($rows as &$row) {
                    if ($row['status'] !== 'ready') continue;
                    DB::table('client_debits')->where('id', $row['account_id'])->update([
                        'amount' => $row['target_amount'], 'updated_at' => now(),
                    ]);
                    DB::table('client_debit_logs')->insert([
                        'client_debit_id' => $row['account_id'],
                        'amount' => $row['adjustment'], 'currency_code' => 'AED', 'exchange_rate' => self::RATE,
                        'base_amount' => (string) BigDecimal::of($row['adjustment'])->dividedBy(self::RATE, 4, RoundingMode::HALF_UP),
                        'note' => $this->note($row, $row['target_amount'], $row['adjustment']),
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $row['status'] = 'converted';
                }
                unset($row);
            }
            return ['source_sha256' => $snapshot['sha256'], 'rate' => self::RATE,
                'applied' => $apply && !$blocked, 'blocked' => $blocked,
                'counts' => collect($rows)->countBy('status')->all(), 'rows' => $rows];
        }, 3);
    }

    private function note(array $row, string $target, string $delta): string
    {
        return self::MARKER . $row['account_id'] . '|'
            . 'تصحيح الرصيد المتبقي من الدولار إلى الدرهم: '
            . $row['source_amount'] . ' USD × ' . self::RATE . ' = ' . $target . ' AED; '
            . 'adjustment=' . $delta . ' AED; shop=' . $row['shop_id'] . '; customer=' . $row['customer_id'];
    }
}

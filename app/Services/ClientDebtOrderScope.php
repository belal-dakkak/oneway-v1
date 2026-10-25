<?php

namespace App\Services;

use App\Models\ClientDebit;
use App\Models\User;
use App\Support\Country;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Owns the identity rules shared by customer accounts and unpaid orders.
 * UAE sales are operationally AED; USD is accepted here only as a legacy
 * order label so old records can be reconciled with their AED account.
 */
class ClientDebtOrderScope
{
    public function applyAccountIdentity(Builder $query, ClientDebit $account, string $table = 'orders'): Builder
    {
        $table = $this->safeTable($table);
        $currency = $this->currency($account->currency_code);

        $query->where("{$table}.seller_id", $account->creditor_id)
            ->where("{$table}.buyer_id", $account->debtor_id);

        if ($this->isUaeAedAccount($account)) {
            return $query->whereIn(
                DB::raw("UPPER(COALESCE({$table}.curr_type, 'USD'))"),
                ['AED', 'USD']
            );
        }

        return $query->whereRaw("UPPER(COALESCE({$table}.curr_type, 'USD')) = ?", [$currency]);
    }

    /**
     * Keep only debts that are still open according to both the order and the
     * unique matching customer account. Missing/ambiguous accounts stay visible.
     */
    public function applyAuthoritativeOutstanding(Builder $query, string $table = 'orders'): Builder
    {
        $table = $this->safeTable($table);
        $remain = "ROUND(COALESCE({$table}.remain_price, 0), CASE WHEN UPPER(COALESCE({$table}.curr_type, 'USD')) = 'SYP' THEN 0 ELSE 2 END)";
        $match = $this->correlatedAccountMatchSql($table);
        $count = "(SELECT COUNT(*) FROM client_debits debt_accounts WHERE {$match})";
        $balance = "(SELECT MAX(ROUND(COALESCE(debt_accounts.amount, 0), CASE WHEN UPPER(COALESCE(debt_accounts.currency_code, 'USD')) = 'SYP' THEN 0 ELSE 2 END)) FROM client_debits debt_accounts WHERE {$match})";

        return $query
            ->whereNull("{$table}.unpaid_tab_archived_at")
            ->whereRaw("{$remain} > 0")
            ->whereRaw("({$count} <> 1 OR {$balance} > 0)");
    }

    public function matchingAccountCount(ClientDebit $account, bool $lock = false): int
    {
        $query = ClientDebit::query()
            ->where('creditor_id', $account->creditor_id)
            ->where('debtor_id', $account->debtor_id);

        if ($this->isUaeAedAccount($account)) {
            $query->whereRaw("UPPER(COALESCE(currency_code, 'USD')) = 'AED'");
        } else {
            $query->whereRaw("UPPER(COALESCE(currency_code, 'USD')) = ?", [$this->currency($account->currency_code)]);
        }

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->pluck('id')->count();
    }

    public function currencyMatches(int $sellerCountryId, ?string $orderCurrency, ?string $accountCurrency): bool
    {
        $orderCurrency = $this->currency($orderCurrency);
        $accountCurrency = $this->currency($accountCurrency);

        if ($sellerCountryId === Country::UAE && in_array($orderCurrency, ['AED', 'USD'], true)) {
            return $accountCurrency === 'AED';
        }

        return $orderCurrency === $accountCurrency;
    }

    public function effectiveOrderCurrency(int $sellerCountryId, ?string $orderCurrency): string
    {
        $currency = $this->currency($orderCurrency);
        return $sellerCountryId === Country::UAE && in_array($currency, ['AED', 'USD'], true)
            ? 'AED'
            : $currency;
    }

    public function isUaeAedAccount(ClientDebit $account): bool
    {
        if ($this->currency($account->currency_code) !== 'AED') {
            return false;
        }

        if ($account->relationLoaded('creditor')) {
            return (int) optional($account->creditor)->country_id === Country::UAE;
        }

        return (int) User::query()->whereKey($account->creditor_id)->value('country_id') === Country::UAE;
    }

    private function correlatedAccountMatchSql(string $table): string
    {
        $uaeSeller = "EXISTS (SELECT 1 FROM users debt_seller WHERE debt_seller.id = {$table}.seller_id AND debt_seller.country_id = " . Country::UAE . ')';
        $orderCurrency = "UPPER(COALESCE({$table}.curr_type, 'USD'))";
        $accountCurrency = "UPPER(COALESCE(debt_accounts.currency_code, 'USD'))";

        return "debt_accounts.creditor_id = {$table}.seller_id
            AND debt_accounts.debtor_id = {$table}.buyer_id
            AND (({$uaeSeller} AND {$orderCurrency} IN ('AED', 'USD') AND {$accountCurrency} = 'AED')
                OR (NOT {$uaeSeller} AND {$accountCurrency} = {$orderCurrency}))";
    }

    private function currency(?string $currency): string
    {
        return strtoupper(trim((string) ($currency ?: 'USD')));
    }

    private function safeTable(string $table): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $table)) {
            throw new \InvalidArgumentException('Invalid orders table alias.');
        }
        return $table;
    }
}

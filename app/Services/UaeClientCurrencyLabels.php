<?php

namespace App\Services;

use App\Support\Country;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UaeClientCurrencyLabels
{
    /**
     * Owner-requested label correction, not exchange or ledger reconciliation.
     * Existing amounts, rates, payments, orders and cashbox entries are untouched.
     */
    public function correct(bool $apply = false): array
    {
        return DB::transaction(function () use ($apply) {
            $accounts = DB::table('client_debits as d')
                ->join('users as shop', 'shop.id', '=', 'd.creditor_id')
                ->where('shop.country_id', Country::UAE)
                ->whereIn(DB::raw('UPPER(d.currency_code)'), ['USD', 'AED'])
                ->select('d.*')->orderBy('d.id')->lockForUpdate()->get();
            $sources = $accounts->filter(fn ($row) => strtoupper($row->currency_code) === 'USD');
            $conflicts = $accounts->groupBy(fn ($row) => $row->creditor_id . ':' . $row->debtor_id)
                ->filter(fn ($group) => $group->count() > 1
                    && $group->contains(fn ($row) => strtoupper($row->currency_code) === 'USD'))
                ->keys()->all();
            if ($conflicts) {
                throw new RuntimeException('Duplicate accounts need a separate decision; no labels changed. Shop:customer = ' . implode(', ', $conflicts));
            }
            $logs = DB::table('client_debit_logs')->whereIn('client_debit_id', $sources->pluck('id'))
                ->whereRaw('UPPER(currency_code) = ?', ['USD'])->orderBy('id')->lockForUpdate()->get(['id']);
            if ($apply) {
                DB::table('client_debit_logs')->whereIn('id', $logs->pluck('id'))->update(['currency_code' => 'AED']);
                DB::table('client_debits')->whereIn('id', $sources->pluck('id'))->update(['currency_code' => 'AED']);
            }
            return ['accounts' => $sources->count(), 'logs' => $logs->count(), 'applied' => $apply];
        }, 3);
    }
}

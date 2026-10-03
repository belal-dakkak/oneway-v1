<?php

namespace App\Models\Concerns;

use App\Models\MerchantDebit;
use App\Services\CurrencyService;
use App\Services\OperationalCurrency;

trait CreatesOperationalCurrency
{
    protected static function bootCreatesOperationalCurrency(): void
    {
        static::creating(function ($model) {
            $policy = app(OperationalCurrency::class);
            $code = $model->merchant_debit_id
                ? $policy->accountCode(MerchantDebit::findOrFail($model->merchant_debit_id))
                : $policy->pairCode((int) $model->creditor_id, (int) $model->debtor_id);
            if ($model->currency_code) {
                if ($code === 'AED' && $model->currency_code !== 'AED') {
                    throw new \InvalidArgumentException('New UAE merchant transactions must use AED.');
                }
                return; // Explicit amounts are already in transaction currency.
            }
            $model->currency_code = $code;
            $model->exchange_rate = app(CurrencyService::class)->rate($code);
            // Existing inventory writers supply base USD amounts.
            $model->amount = $policy->fromBase($model->amount ?? 0, $code);
        });
    }
}

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
                if (strtoupper((string) $model->currency_code) !== $code) {
                    throw new \InvalidArgumentException('Merchant transactions must use the destination shop currency.');
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

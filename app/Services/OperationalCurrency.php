<?php

namespace App\Services;

use App\Models\User;
use App\Support\Country;
use InvalidArgumentException;

class OperationalCurrency
{
    public function code(int $ownerId): string
    {
        return (int) User::query()->whereKey($ownerId)->value('country_id') === Country::UAE ? 'AED' : 'USD';
    }

    public function pairCode(int $creditor, int $debtor): string
    {
        return $this->code($debtor);
    }

    public function fromBase($amount, string $code): float
    {
        return app(CurrencyService::class)->fromUsd($amount, $code);
    }

    public function accountCode($account): string
    {
        $expected = $this->code((int) $account->debtor_id);
        $stored = $account->currency_code ?: 'USD';
        if ($stored !== $expected) {
            throw new InvalidArgumentException('Convert this legacy merchant account to the destination shop currency before posting new transactions.');
        }
        return $stored;
    }
}

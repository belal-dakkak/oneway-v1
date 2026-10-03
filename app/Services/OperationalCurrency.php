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
        $code = $this->code($debtor);
        if ($code !== $this->code($creditor)) throw new InvalidArgumentException('Cross-country merchant currency must be resolved explicitly.');
        return $code;
    }

    public function fromBase($amount, string $code): float
    {
        return app(CurrencyService::class)->fromUsd($amount, $code);
    }

    public function accountCode($account): string
    {
        $expected = $this->code((int) $account->debtor_id);
        if ($expected !== $this->code((int) $account->creditor_id)) {
            throw new InvalidArgumentException('Cross-country merchant transactions require an explicit settlement currency.');
        }
        $stored = $account->currency_code ?: 'USD';
        if ($expected === 'AED' && $stored !== 'AED') {
            throw new InvalidArgumentException('Convert this legacy UAE merchant account before posting new transactions.');
        }
        return $stored;
    }
}

<?php

namespace App\Services;

use App\Models\Currency;
use App\Support\Country;
use InvalidArgumentException;

class CurrencyService
{
    private static $rates = [];

    public function rate(string $code): float
    {
        $code = strtoupper($code);
        if ($code === 'USD') {
            return 1.0;
        }

        if (array_key_exists($code, self::$rates)) {
            return self::$rates[$code];
        }

        $currency = Currency::query()->where('name', strtolower($code))->first();
        if (!$currency || (float) $currency->rate <= 0) {
            throw new InvalidArgumentException("Currency {$code} does not have a valid exchange rate.");
        }

        return self::$rates[$code] = (float) $currency->rate;
    }

    public function validateForCountry(string $code, int $countryId, bool $storefront = false): string
    {
        $code = strtoupper($code);
        if (!in_array($code, Country::currencyCodes($countryId, $storefront), true)) {
            throw new InvalidArgumentException("Currency {$code} is not available for this country.");
        }

        $this->rate($code);
        return $code;
    }

    public function optionsForCountry(int $countryId, bool $storefront = false): array
    {
        $options = [];
        $codes = Country::currencyCodes($countryId, $storefront);
        $default = Country::defaultCurrency($countryId);
        usort($codes, function ($left, $right) use ($default) {
            return ($right === $default ? 1 : 0) <=> ($left === $default ? 1 : 0);
        });
        foreach ($codes as $code) {
            try {
                $options[] = [
                    'name' => $code,
                    'label' => $code,
                    'value' => strtolower($code),
                    'code' => $code,
                    'rate' => $this->rate($code),
                    'decimals' => $code === 'SYP' ? 0 : 2,
                ];
            } catch (InvalidArgumentException $exception) {
                // A currency with an invalid rate is intentionally unavailable.
            }
        }

        return $options;
    }

    public function fromUsd($amount, string $code): float
    {
        $value = (float) $amount * $this->rate($code);
        return $this->round($value, $code);
    }

    public function toUsd($amount, string $code): float
    {
        return round((float) $amount / $this->rate($code), 4);
    }

    public function round($amount, string $code): float
    {
        return round((float) $amount, strtoupper($code) === 'SYP' ? 0 : 2);
    }
}

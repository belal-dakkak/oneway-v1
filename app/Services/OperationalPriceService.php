<?php

namespace App\Services;

use App\Models\OperationalPrice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use WeakMap;

class OperationalPriceService
{
    public const PRODUCT_FIELDS = [
        'cost_price', 'retail_price', 'sale_price', 'price_before_discount',
    ];

    public const USER_PRODUCT_FIELDS = [
        'wholesale_price', 'retail_price', 'price_before_discount',
    ];

    private $currencies;
    private static $lookupCache;

    public function __construct(CurrencyService $currencies)
    {
        $this->currencies = $currencies;
    }

    public function prepare(array $values, string $currencyCode, array $fields): array
    {
        $currencyCode = strtoupper($currencyCode);
        $rate = $this->currencies->rate($currencyCode);
        $local = [];
        $base = [];

        foreach ($fields as $field) {
            $value = $values[$field] ?? null;
            if ($value === '' || $value === null || $value === 'NaN') {
                $local[$field] = null;
                $base[$field] = null;
                continue;
            }

            $local[$field] = $this->normalizeInput($value, $currencyCode);
            $base[$field] = $this->currencies->toUsdAtRate($local[$field], $rate);
        }

        return compact('currencyCode', 'rate', 'local', 'base');
    }

    public function persist(Model $model, array $prepared): void
    {
        if (!Schema::hasTable('operational_prices')) {
            return;
        }

        OperationalPrice::query()->updateOrCreate([
            'priceable_type' => $model->getMorphClass(),
            'priceable_id' => $model->getKey(),
            'currency_code' => $prepared['currencyCode'],
        ], array_merge($prepared['local'], [
            'exchange_rate' => $prepared['rate'],
        ]));

        if ($model->relationLoaded('operationalPrices')) {
            $model->unsetRelation('operationalPrices');
        }
        $this->forgetCachedPrice($model, $prepared['currencyCode']);
    }

    public function amount(Model $model, string $field, string $currencyCode, $baseValue = null): float
    {
        $currencyCode = strtoupper($currencyCode);
        $stored = $this->storedPrice($model, $currencyCode);
        if ($stored && $stored->{$field} !== null) {
            return $this->normalizeInput($stored->{$field}, $currencyCode);
        }

        if ($baseValue === null) {
            $baseValue = $model->getRawOriginal($field);
        }

        return $this->currencies->fromUsd($baseValue ?: 0, $currencyCode);
    }

    public function nullableAmount(Model $model, string $field, string $currencyCode, $baseValue = null): ?float
    {
        $currencyCode = strtoupper($currencyCode);
        $stored = $this->storedPrice($model, $currencyCode);
        if ($stored && $stored->{$field} !== null) {
            return $this->normalizeInput($stored->{$field}, $currencyCode);
        }
        if ($baseValue === null) {
            return null;
        }
        return $this->currencies->fromUsd($baseValue, $currencyCode);
    }

    public function values(Model $model, string $currencyCode, array $fields): array
    {
        $result = [];
        foreach ($fields as $field) {
            $raw = $model->getRawOriginal($field);
            $result[$field] = $raw === null
                ? null
                : $this->amount($model, $field, $currencyCode, $raw);
        }
        return $result;
    }

    public function normalizeInput($value, string $currencyCode): float
    {
        $decimals = strtoupper($currencyCode) === 'SYP' ? 0 : 2;
        return round((float) $value, $decimals);
    }

    public function format($value, string $currencyCode): string
    {
        $decimals = strtoupper($currencyCode) === 'SYP' ? 0 : 2;
        $amount = (float) $value;
        $minimumDecimals = $decimals > 0 && abs($amount - round($amount)) > 0.000001
            ? $decimals
            : 0;
        return number_format($amount, $minimumDecimals, '.', '');
    }

    private function storedPrice(Model $model, string $currencyCode): ?OperationalPrice
    {
        if (!Schema::hasTable('operational_prices')) {
            return null;
        }

        if (method_exists($model, 'operationalPrices') && $model->relationLoaded('operationalPrices')) {
            return $model->getRelation('operationalPrices')->firstWhere('currency_code', $currencyCode);
        }

        $cache = $this->lookupCache();
        $cached = $cache[$model] ?? [];
        if (array_key_exists($currencyCode, $cached)) {
            return $cached[$currencyCode];
        }

        $stored = OperationalPrice::query()
            ->where('priceable_type', $model->getMorphClass())
            ->where('priceable_id', $model->getKey())
            ->where('currency_code', $currencyCode)
            ->first();

        // Cache each currency separately because API responses can request a
        // display currency that differs from the model presentation currency.
        $cached[$currencyCode] = $stored;
        $cache[$model] = $cached;

        return $stored;
    }

    private function lookupCache(): WeakMap
    {
        if (!self::$lookupCache) {
            self::$lookupCache = new WeakMap();
        }
        return self::$lookupCache;
    }

    private function forgetCachedPrice(Model $model, string $currencyCode): void
    {
        $cache = $this->lookupCache();
        $cached = $cache[$model] ?? [];
        unset($cached[strtoupper($currencyCode)]);
        $cache[$model] = $cached;
    }
}

<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Session;

class Country
{
    public const LEBANON = 1;
    public const UAE = 2;
    public const ALL = 3;
    public const SYRIA = 4;

    public static function all(): array
    {
        return config('countries.countries', []);
    }

    public static function storefront(): array
    {
        return array_filter(self::all(), function ($country) {
            return !empty($country['storefront']);
        });
    }

    public static function code(?string $code = null): string
    {
        $code = strtoupper($code ?: (string) Session::get('country', 'AE'));
        return isset(self::storefront()[$code]) ? $code : 'AE';
    }

    public static function id(?string $code = null): int
    {
        return (int) self::all()[self::code($code)]['id'];
    }

    public static function codeFromId(?int $id): string
    {
        foreach (self::all() as $code => $country) {
            if ((int) ($country['id'] ?? 0) === (int) $id) {
                return $code;
            }
        }

        return 'AE';
    }

    public static function definition(?string $code = null): array
    {
        return self::all()[self::code($code)];
    }

    public static function definitionFromId(?int $id): array
    {
        return self::definition(self::codeFromId($id));
    }

    public static function allowedIds(): array
    {
        return array_values(array_map(function ($country) {
            return (int) $country['id'];
        }, self::storefront()));
    }

    public static function globalProductId(): int
    {
        return (int) config('countries.global_product_country_id', self::ALL);
    }

    public static function currencyCodes(int $countryId, bool $storefront = false): array
    {
        $country = self::definitionFromId($countryId);
        return $country[$storefront ? 'storefront_currencies' : 'admin_currencies'] ?? [];
    }

    public static function defaultCurrency(int $countryId): string
    {
        return self::definitionFromId($countryId)['default_currency'] ?? 'USD';
    }

    public static function baseCurrency(int $countryId): string
    {
        return self::definitionFromId($countryId)['base_currency'] ?? self::defaultCurrency($countryId);
    }

    public static function displayCurrency(int $countryId): ?string
    {
        $currency = self::definitionFromId($countryId)['display_currency'] ?? null;
        return $currency ? strtoupper((string) $currency) : null;
    }

    public static function timezone(int $countryId): string
    {
        return self::definitionFromId($countryId)['timezone'] ?? config('app.timezone');
    }

    public static function idForCurrency(string $currencyCode, ?string $countryCode = null): int
    {
        if ($countryCode && isset(self::storefront()[strtoupper($countryCode)])) {
            return self::id(strtoupper($countryCode));
        }

        switch (strtoupper($currencyCode)) {
            case 'SYP': return self::SYRIA;
            case 'LBP':
            case 'LP': return self::LEBANON;
            case 'AED':
            default: return self::UAE;
        }
    }

    public static function legacyUserConstants(): array
    {
        return [User::COUNTRY_LB, User::COUNTRY_UAE, User::COUNTRY_SYRIA];
    }
}

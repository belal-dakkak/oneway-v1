<?php

namespace App\Services;

use App\Support\Country;
use InvalidArgumentException;

class SalesCurrencyPolicy
{
    private $currencies;

    public function __construct(CurrencyService $currencies)
    {
        $this->currencies = $currencies;
    }

    public function code(int $countryId, string $context, ?string $requested = null): string
    {
        $definition = Country::definitionFromId($countryId);
        $configured = $definition['sales_currencies'][$context] ?? null;

        if ($configured) {
            $code = strtoupper($configured);
            // Sales currencies are deliberately separate from the operational
            // product/inventory currencies. Syria, for example, operates its
            // stock in USD while retail invoices are issued in SYP.
            $this->currencies->rate($code);

            return $code;
        }

        return $this->currencies->validateForCountry(
            strtoupper($requested ?: Country::defaultCurrency($countryId)),
            $countryId,
            strpos($context, 'website_') === 0
        );
    }

    public function option(int $countryId, string $context, ?string $requested = null): array
    {
        $code = $this->code($countryId, $context, $requested);

        return [
            'name' => $code,
            'label' => $code,
            'value' => strtolower($code),
            'code' => $code,
            'rate' => $this->currencies->rate($code),
            'decimals' => $code === 'SYP' ? 0 : 2,
            'locked' => (int) $countryId === Country::SYRIA,
        ];
    }

    public function orderOption(int $countryId, string $orderType, ?string $requested = null): array
    {
        if (!in_array($orderType, ['simple', 'complex', 'complex_from_multi'], true)) {
            throw new InvalidArgumentException('Unsupported order type.');
        }

        return $this->option($countryId, $orderType, $requested);
    }

    public function websiteOption(int $countryId, bool $wholesale, ?string $requested = null): array
    {
        return $this->option($countryId, $wholesale ? 'website_wholesale' : 'website_retail', $requested);
    }
}

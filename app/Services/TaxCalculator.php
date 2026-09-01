<?php

namespace App\Services;

use InvalidArgumentException;

class TaxCalculator
{
    public const INCLUSIVE_TYPES = ['simple', 'complex'];
    public const EXCLUSIVE_TYPES = ['complex_from_multi'];

    public function calculate(float $enteredPrice, float $taxRatio, string $orderType): array
    {
        if (!in_array($orderType, array_merge(self::INCLUSIVE_TYPES, self::EXCLUSIVE_TYPES), true)) {
            throw new InvalidArgumentException('Unsupported order type.');
        }

        $rate = max(0, $taxRatio) / 100;
        if (in_array($orderType, self::EXCLUSIVE_TYPES, true)) {
            $net = $enteredPrice;
            $tax = $net * $rate;
            $gross = $net + $tax;
        } else {
            $gross = $enteredPrice;
            $net = $rate > 0 ? $gross / (1 + $rate) : $gross;
            $tax = $gross - $net;
        }

        return [
            'price_without_tax' => round($net, 4),
            'tax_value' => round($tax, 4),
            'price_with_tax' => round($gross, 4),
            'mode' => in_array($orderType, self::EXCLUSIVE_TYPES, true) ? 'exclusive' : 'inclusive',
        ];
    }
}

<?php

namespace App\Services;

use App\Models\CountryCommerceSetting;
use App\Models\ProductColor;
use App\Models\UserProduct;
use App\Support\Country;
use InvalidArgumentException;

class WebsitePricingService
{
    private $currencies;
    private $policy;
    private $inventory;

    public function __construct(CurrencyService $currencies, SalesCurrencyPolicy $policy, WebsiteInventoryService $inventory)
    {
        $this->currencies = $currencies;
        $this->policy = $policy;
        $this->inventory = $inventory;
    }

    public function quote(array $items, int $countryId, bool $wholesale, string $paymentMethod = 'cod', bool $lock = false): array
    {
        if (!$items) {
            throw new InvalidArgumentException('Your cart is empty.');
        }
        if (!in_array($paymentMethod, ['cod', 'card'], true)) {
            throw new InvalidArgumentException('Unsupported payment method.');
        }

        $currency = $this->policy->websiteOption($countryId, $wholesale);
        $currencyCode = $currency['code'];
        $currencyRate = (float) $currency['rate'];
        $decimals = $currencyCode === 'SYP' ? 0 : 2;
        $commerce = CountryCommerceSetting::forCountry($countryId);
        $stockUserId = $this->inventory->requireStockUserId($countryId);
        $normalized = [];

        foreach ($items as $item) {
            $colorId = (int) ($item['product_id'] ?? $item['color']['id'] ?? 0);
            $size = (string) ($item['size'] ?? '');
            $qty = (int) ($item['qty'] ?? $item['quantity'] ?? 0);
            if ($colorId <= 0 || $size === '' || $qty <= 0) {
                throw new InvalidArgumentException('One or more cart items are invalid.');
            }
            $key = $colorId . '|' . $size;
            if (!isset($normalized[$key])) {
                $normalized[$key] = ['product_color_id' => $colorId, 'size' => $size, 'qty' => 0];
            }
            $normalized[$key]['qty'] += $qty;
        }

        ksort($normalized);
        $lines = [];
        $subtotal = 0.0;
        $beforeDiscount = 0.0;

        foreach ($normalized as $entry) {
            $productQuery = ProductColor::query()
                ->with('product')
                ->whereKey($entry['product_color_id'])
                ->whereHas('product', function ($query) use ($countryId) {
                    $query->whereIn('country_id', [$countryId, Country::globalProductId()]);
                });
            if ($lock) {
                $productQuery->lockForUpdate();
            }
            $color = $productQuery->first();
            if (!$color || !$color->product) {
                throw new InvalidArgumentException('A cart product is not available in this country.');
            }

            $stockQuery = UserProduct::query()
                ->where('product_color_id', $color->id)
                ->where('country_id', $countryId)
                ->where('size', $entry['size'])
                ->where('user_id', $stockUserId)
                ->orderBy('id');
            if ($lock) {
                $stockQuery->lockForUpdate();
            }
            $stocks = $stockQuery->get();
            if ($stocks->sum('stock') < $entry['qty']) {
                throw new InvalidArgumentException('The requested quantity is no longer available.');
            }

            $basePrice = (float) ($wholesale ? $color->product->sale_price : $color->product->retail_price);
            $baseOldPrice = (float) ($color->product->price_before_discount ?: $basePrice);
            $itemPrice = round($basePrice * $currencyRate, $decimals);
            $oldPrice = round($baseOldPrice * $currencyRate, $decimals);
            $lineTotal = round($itemPrice * $entry['qty'], $decimals);
            $lineBeforeDiscount = round($oldPrice * $entry['qty'], $decimals);

            $remaining = $entry['qty'];
            foreach ($stocks as $stock) {
                if ($remaining <= 0) {
                    break;
                }
                $allocated = min($remaining, (int) $stock->stock);
                if ($allocated <= 0) {
                    continue;
                }
                $lines[] = [
                    'product_color_id' => (int) $color->id,
                    'stock_user_product_id' => (int) $stock->id,
                    'size' => $entry['size'],
                    'qty' => $allocated,
                    'item_price' => $itemPrice,
                    'item_price_before_discount' => $oldPrice,
                    'total_price' => round($itemPrice * $allocated, $decimals),
                    'total_price_before_discount' => round($oldPrice * $allocated, $decimals),
                    'name' => (string) $color->product->name,
                ];
                $remaining -= $allocated;
            }
            $subtotal += $lineTotal;
            $beforeDiscount += $lineBeforeDiscount;
        }

        $subtotal = round($subtotal, $decimals);
        $beforeDiscount = round($beforeDiscount, $decimals);
        $discount = round(max(0, $beforeDiscount - $subtotal), $decimals);
        $subtotalUsd = $subtotal / $currencyRate;
        $shipping = $commerce->free_shipping_threshold_usd !== null
            && $subtotalUsd >= (float) $commerce->free_shipping_threshold_usd
            ? 0.0
            : round((float) $commerce->shipping_fee_usd * $currencyRate, $decimals);
        $cod = $paymentMethod === 'cod'
            ? round($subtotal * ((float) $commerce->cod_fee_percent / 100), $decimals)
            : 0.0;
        $total = round($subtotal + $shipping + $cod, $decimals);

        $displayCode = $currencyCode === 'SYP' ? 'USD' : Country::displayCurrency($countryId);
        $displayRate = $displayCode === 'USD' ? 1.0 : ($displayCode ? $this->currencies->rate($displayCode) : null);
        $displayTotal = null;
        if ($displayCode === 'USD') {
            $displayTotal = round($total / $currencyRate, 2);
        } elseif ($displayCode && $displayRate) {
            $displayTotal = $this->currencies->fromUsdAtRate($total / $currencyRate, $displayRate, $displayCode);
        }

        $gateway = null;
        if ($paymentMethod === 'card') {
            if (!$commerce->cardIsAvailable()) {
                throw new InvalidArgumentException('Card payment is not currently enabled for this country.');
            }

            $gatewayCode = strtoupper((string) ($commerce->gateway_currency ?: $currencyCode));
            if ($countryId === Country::SYRIA && $gatewayCode !== 'USD') {
                throw new InvalidArgumentException('Syrian card payments must settle in USD.');
            }
            $gatewayRate = $gatewayCode === 'USD' ? 1.0 : $this->currencies->rate($gatewayCode);
            $totalUsd = $total / $currencyRate;
            $gatewayAmount = $gatewayCode === 'USD'
                ? round($totalUsd, 2)
                : round($totalUsd * $gatewayRate, 2);
            $gateway = [
                'provider' => 'tap',
                'currency' => $gatewayCode,
                'amount' => $gatewayAmount,
                'rate' => $gatewayRate,
            ];
        }

        return [
            'currency' => $currencyCode,
            'rate' => $currencyRate,
            'pricing_mode' => $wholesale ? 'wholesale' : 'retail',
            'items' => $lines,
            'subtotal' => $subtotal,
            'total_price_before_discount' => $beforeDiscount,
            'discount' => $discount,
            'shipping_fee' => $shipping,
            'cod_fee' => $cod,
            'total' => $total,
            'display' => $displayCode ? [
                'currency' => $displayCode,
                'amount' => $displayTotal,
                // Freeze the SYP-per-USD exchange rate, not a precomputed
                // multiplier. This keeps the snapshot unambiguous whether the
                // official invoice is SYP or USD.
                'rate' => $currencyCode === 'SYP' ? $currencyRate : $displayRate,
                'approximate' => true,
            ] : null,
            'gateway' => $gateway,
        ];
    }
}

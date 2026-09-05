<?php

namespace App\Services;

use App\Models\Order;
use App\Models\WebsiteOrder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class InvoiceDataService
{
    public const SOURCE_ORDER = 'order';
    public const SOURCE_WEBSITE = 'website';

    public function resolve(string $source, int $id): Model
    {
        $source = strtolower($source);
        if ($source === self::SOURCE_ORDER) {
            return Order::query()->findOrFail($id);
        }
        if ($source === self::SOURCE_WEBSITE) {
            return WebsiteOrder::query()->findOrFail($id);
        }

        throw new InvalidArgumentException('Unsupported invoice source.');
    }

    public function forOrder(Model $order): array
    {
        if (!$order instanceof Order && !$order instanceof WebsiteOrder) {
            throw new InvalidArgumentException('Invoices can only be generated for an order or website order.');
        }

        if ($order instanceof Order && !$order->sent_at) {
            $order->forceFill(['sent_at' => Carbon::now()])->save();
        }

        $currency = strtoupper((string) ($order->curr_type ?: 'USD'));
        $decimals = $currency === 'SYP' ? 0 : 2;
        $rate = $order instanceof WebsiteOrder ? 1.0 : (float) ($order->curr_rate ?: 1);
        $displayCurrency = strtoupper((string) ($order->display_currency ?: ''));
        $displayRate = (float) ($order->display_rate ?: 0);
        $displayDecimals = $displayCurrency === 'SYP' ? 0 : 2;
        $displayTotal = $displayCurrency !== '' && $displayRate > 0
            ? round((float) $order->total_price * $displayRate, $displayDecimals)
            : null;

        if ($order instanceof WebsiteOrder) {
            $order->loadMissing('items.product.product');
            $items = $this->websiteItems($order, $decimals);
        } elseif ((int) $order->type === Order::TYPE_APP) {
            $order->loadMissing('productItems.product.product');
            $items = $this->appItems($order, $rate, $decimals);
        } else {
            $order->loadMissing('items.user_product.productColor.product');
            $items = $this->orderItems($order, $rate, $decimals);
        }

        return compact(
            'order',
            'items',
            'currency',
            'decimals',
            'displayCurrency',
            'displayRate',
            'displayDecimals',
            'displayTotal'
        );
    }

    private function websiteItems(WebsiteOrder $order, int $decimals): Collection
    {
        $lines = [];
        foreach ($order->items as $item) {
            $color = $item->product;
            $product = $color ? $color->product : null;
            $unitGross = $this->round($item->item_price, $decimals);
            $totalGross = $this->round($item->total_price, $decimals);
            $this->merge($lines, [
                'product_id' => $product ? $product->id : 'website-' . $item->id,
                'name' => $this->name($product, $item->product_color_id),
                'qty' => (int) $item->qty,
                'item_price' => $unitGross,
                'price_without_tax' => $unitGross,
                'tax_value' => 0.0,
                'line_price_without_tax' => $totalGross,
                'line_tax_value' => 0.0,
                'total_price' => $totalGross,
            ], $decimals);
        }

        return $this->objects($lines);
    }

    private function appItems(Order $order, float $rate, int $decimals): Collection
    {
        $lines = [];
        foreach ($order->productItems as $item) {
            $color = $item->product;
            $product = $color ? $color->product : null;
            $unitGross = $this->round((float) $item->item_price * $rate, $decimals);
            $totalGross = $this->round((float) $item->total_price * $rate, $decimals);
            $this->merge($lines, [
                'product_id' => $product ? $product->id : 'app-' . $item->id,
                'name' => $this->name($product, $item->product_color_id),
                'qty' => (int) $item->qty,
                'item_price' => $unitGross,
                'price_without_tax' => $unitGross,
                'tax_value' => 0.0,
                'line_price_without_tax' => $totalGross,
                'line_tax_value' => 0.0,
                'total_price' => $totalGross,
            ], $decimals);
        }

        return $this->objects($lines);
    }

    private function orderItems(Order $order, float $rate, int $decimals): Collection
    {
        $lines = [];
        foreach ($order->items as $item) {
            $color = $item->user_product ? $item->user_product->productColor : null;
            $product = $color ? $color->product : null;
            $qty = (int) $item->qty;
            $unitGross = $this->round((float) $item->item_price * $rate, $decimals);
            $unitNet = $this->round((float) ($item->price_without_tax ?? $item->item_price) * $rate, $decimals);
            $unitTax = $this->round((float) ($item->tax_value ?? 0) * $rate, $decimals);
            $lineGross = $this->round((float) $item->total_price * $rate, $decimals);

            $this->merge($lines, [
                'product_id' => $product ? $product->id : 'order-' . $item->id,
                'name' => $this->name($product, $item->user_product_id),
                'qty' => $qty,
                'item_price' => $unitGross,
                'price_without_tax' => $unitNet,
                'tax_value' => $unitTax,
                'line_price_without_tax' => $this->round($unitNet * $qty, $decimals),
                'line_tax_value' => $this->round($unitTax * $qty, $decimals),
                'total_price' => $lineGross,
            ], $decimals);
        }

        return $this->objects($lines);
    }

    private function merge(array &$lines, array $line, int $decimals): void
    {
        $key = implode('|', [
            $line['product_id'],
            number_format($line['item_price'], 6, '.', ''),
            number_format($line['price_without_tax'], 6, '.', ''),
            number_format($line['tax_value'], 6, '.', ''),
        ]);

        if (!isset($lines[$key])) {
            $lines[$key] = $line;
            return;
        }

        $lines[$key]['qty'] += $line['qty'];
        foreach (['line_price_without_tax', 'line_tax_value', 'total_price'] as $field) {
            $lines[$key][$field] = $this->round($lines[$key][$field] + $line[$field], $decimals);
        }
    }

    private function objects(array $lines): Collection
    {
        return collect(array_values($lines))->map(function (array $line) {
            unset($line['product_id']);
            return (object) $line;
        });
    }

    private function name($product, $fallbackId): string
    {
        return $product
            ? trim($product->name . ' - ' . $product->barcode)
            : 'Product #' . $fallbackId;
    }

    private function round($value, int $decimals): float
    {
        return round((float) $value, $decimals);
    }
}

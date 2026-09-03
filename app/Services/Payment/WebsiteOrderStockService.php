<?php

namespace App\Services\Payment;

use App\Models\UserProduct;
use App\Models\WebsiteOrder;
use Exception;
use Illuminate\Support\Facades\DB;

class WebsiteOrderStockService
{
    public function reserve(WebsiteOrder $order): bool
    {
        return DB::transaction(function () use ($order) {
            $lockedOrder = WebsiteOrder::query()->lockForUpdate()->findOrFail($order->id);

            return $this->reserveLocked($lockedOrder);
        });
    }

    public function release(WebsiteOrder $order): bool
    {
        return DB::transaction(function () use ($order) {
            $lockedOrder = WebsiteOrder::query()->lockForUpdate()->findOrFail($order->id);

            return $this->releaseLocked($lockedOrder);
        });
    }

    public function reserveLocked(WebsiteOrder $order): bool
    {
        if ($order->stock_reserved_at && !$order->stock_released_at) {
            return false;
        }

        $stocks = $this->lockedStocksFor($order);
        foreach ($stocks as $entry) {
            if ((int) $entry['stock']->stock < $entry['qty']) {
                throw new Exception('The requested product quantity is no longer available in this country.');
            }
        }

        foreach ($stocks as $entry) {
            $entry['stock']->decrement('stock', $entry['qty']);
        }

        $order->forceFill([
            'stock_reserved_at' => now(),
            'stock_released_at' => null,
        ])->save();

        return true;
    }

    public function releaseLocked(WebsiteOrder $order): bool
    {
        if (!$order->stock_reserved_at || $order->stock_released_at) {
            return false;
        }

        foreach ($this->lockedStocksFor($order) as $entry) {
            $entry['stock']->increment('stock', $entry['qty']);
        }

        $order->forceFill(['stock_released_at' => now()])->save();

        return true;
    }

    private function lockedStocksFor(WebsiteOrder $order): array
    {
        $order->loadMissing('items');
        $quantities = [];

        foreach ($order->items as $item) {
            $key = $item->product_color_id . '|' . (string) $item->size;
            if (!isset($quantities[$key])) {
                $quantities[$key] = [
                    'product_color_id' => (int) $item->product_color_id,
                    'size' => $item->size,
                    'qty' => 0,
                ];
            }
            $quantities[$key]['qty'] += (int) $item->qty;
        }

        ksort($quantities);
        $stocks = [];
        foreach ($quantities as $quantity) {
            $stock = UserProduct::query()
                ->where('product_color_id', $quantity['product_color_id'])
                ->where('country_id', $order->country_id)
                ->where('size', $quantity['size'])
                ->lockForUpdate()
                ->first();

            if (!$stock) {
                throw new Exception('The requested product stock record could not be found for this country.');
            }

            $stocks[] = ['stock' => $stock, 'qty' => $quantity['qty']];
        }

        return $stocks;
    }
}

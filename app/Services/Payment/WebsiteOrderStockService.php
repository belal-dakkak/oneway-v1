<?php

namespace App\Services\Payment;

use App\Models\UserProduct;
use App\Models\WebsiteOrder;
use App\Models\CountryCommerceSetting;
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
                    'stock_user_product_id' => $item->stock_user_product_id ? (int) $item->stock_user_product_id : null,
                    'size' => $item->size,
                    'qty' => 0,
                ];
            }
            $quantities[$key]['qty'] += (int) $item->qty;
        }

        ksort($quantities);
        $cashboxUserId = CountryCommerceSetting::forCountry((int) $order->country_id)
            ->website_cashbox_user_id;
        $stocks = [];
        foreach ($quantities as $quantity) {
            $stockQuery = UserProduct::query()
                ->when($quantity['stock_user_product_id'], function ($query) use ($quantity) {
                    $query->whereKey($quantity['stock_user_product_id']);
                }, function ($query) use ($quantity, $cashboxUserId) {
                    $query->where('product_color_id', $quantity['product_color_id'])
                        ->when($cashboxUserId, function ($stockQuery) use ($cashboxUserId) {
                            $stockQuery->where('user_id', $cashboxUserId);
                        });
                })
                ->where('product_color_id', $quantity['product_color_id'])
                ->where('country_id', $order->country_id)
                ->where('size', $quantity['size'])
                ->orderBy('id')
                ->lockForUpdate();
            $stock = $stockQuery->first();

            if (!$stock) {
                throw new Exception('The requested product stock record could not be found for this country.');
            }

            $stocks[] = ['stock' => $stock, 'qty' => $quantity['qty']];
        }

        return $stocks;
    }
}

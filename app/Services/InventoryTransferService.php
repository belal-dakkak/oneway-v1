<?php

namespace App\Services;

use App\Models\Debit;
use App\Models\DebitLog;
use App\Models\MerchantDebit;
use App\Models\ProductColor;
use App\Models\User;
use App\Models\UserProduct;
use App\Models\UserProductLog;
use App\Models\Wallet;
use App\Notifications\ShopNotification;
use App\Support\Country;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class InventoryTransferService
{
    public const SOURCE_CATALOG = 'catalog';
    public const SOURCE_INVENTORY = 'inventory';

    private $currency;

    public function __construct(CurrencyService $currency)
    {
        $this->currency = $currency;
    }

    public function destinationsFor(User $sender, string $sourceType = self::SOURCE_INVENTORY): Collection
    {
        return User::query()
            ->whereIn('role_id', [User::ROLE_SHOP, User::ROLE_WAREHOUSE])
            ->where('country_id', $sender->country_id)
            ->when(
                $sourceType === self::SOURCE_INVENTORY &&
                (int) $sender->role_id === User::ROLE_WAREHOUSE,
                function ($query) use ($sender) {
                $query->whereKeyNot($sender->id);
                }
            )
            ->orderBy('name')
            ->get();
    }

    public function transfer(User $sender, array $data): array
    {
        $sourceType = $data['source_type'] ?? self::SOURCE_INVENTORY;
        $rate = $this->currency->rate($data['currency_code']);
        $retailPrice = round($this->currency->toUsdAtRate($data['retail_price'], $rate), 2);
        $wholesalePrice = round($this->currency->toUsdAtRate($data['wholesale_price'], $rate), 2);
        $priceBeforeDiscount = isset($data['price_before_discount']) && $data['price_before_discount'] !== ''
            ? round($this->currency->toUsdAtRate($data['price_before_discount'], $rate), 2)
            : null;

        $result = DB::transaction(function () use (
            $sender,
            $data,
            $sourceType,
            $retailPrice,
            $wholesalePrice,
            $priceBeforeDiscount
        ) {
            $destination = User::query()->lockForUpdate()->findOrFail($data['destination_user_id']);
            $productColor = ProductColor::query()->with(['product', 'color'])
                ->lockForUpdate()
                ->findOrFail($data['product_color_id']);

            $this->assertCountryAccess($sender, $destination, $productColor, $sourceType);

            $merchant = null;
            if (!empty($data['merchant_id'])) {
                $merchant = User::query()->lockForUpdate()->findOrFail($data['merchant_id']);
                if ((int) $merchant->country_id !== (int) $sender->country_id ||
                    (int) $merchant->role_id !== User::ROLE_MERCHANT) {
                    throw ValidationException::withMessages([
                        'merchant_id' => 'The selected merchant is not available in this country.',
                    ]);
                }
            }

            $centralSizes = $this->decodeSizes($productColor->getRawOriginal('sizes'));
            $savedProducts = [];
            $logs = [];
            $totalQuantity = 0;
            $totalMerchantAmount = 0.0;

            foreach ($data['items'] as $item) {
                $quantity = (int) $item['quantity'];
                $size = (string) $item['size'];
                $barcode = (string) $item['barcode'];

                if ($sourceType === self::SOURCE_CATALOG) {
                    $this->assertCatalogItem($centralSizes, $size, $barcode);
                } elseif ((int) $sender->role_id === User::ROLE_WAREHOUSE) {
                    $this->deductWarehouseStock($sender, $productColor, $size, $barcode, $quantity);
                } else {
                    $centralSizes = $this->deductCentralStock($centralSizes, $size, $barcode, $quantity);
                }

                $destinationProduct = UserProduct::query()
                    ->where('user_id', $destination->id)
                    ->where('product_color_id', $productColor->id)
                    ->where('size', $size)
                    ->where('barcode', $barcode)
                    ->lockForUpdate()
                    ->first();

                if (!$destinationProduct) {
                    $destinationProduct = new UserProduct([
                        'user_id' => $destination->id,
                        'product_color_id' => $productColor->id,
                        'size' => $size,
                        'country_id' => $destination->country_id,
                        'barcode' => $barcode,
                        'stock' => 0,
                    ]);
                }

                $destinationProduct->fill([
                    'stock' => (int) $destinationProduct->stock + $quantity,
                    'retail_price' => $retailPrice,
                    'wholesale_price' => $wholesalePrice,
                    'price_before_discount' => $priceBeforeDiscount,
                    'merchant_id' => $merchant ? $merchant->id : null,
                ])->save();

                $message = $this->transferMessage(
                    $sender,
                    $destination,
                    $productColor,
                    $size,
                    $quantity,
                    $retailPrice,
                    (int) $destination->country_id
                );
                $userProductLog = UserProductLog::query()->create([
                    'user_product_id' => $destinationProduct->id,
                    'note' => $message,
                ]);

                $merchantAmount = round($wholesalePrice * $quantity, 2);
                if ($merchant) {
                    Debit::query()->create([
                        'creditor_id' => $merchant->id,
                        'debtor_id' => $destination->id,
                        'amount' => $merchantAmount,
                        'user_product_id' => $destinationProduct->id,
                        'user_product_log_id' => $userProductLog->id,
                    ]);
                }

                $savedProducts[] = $destinationProduct;
                $logs[] = [
                    'user_product_id' => $destinationProduct->id,
                    'user_product_log_id' => $userProductLog->id,
                    'note' => $message,
                    'stock' => $quantity,
                    'size' => $size,
                ];
                $totalQuantity += $quantity;
                $totalMerchantAmount += $merchantAmount;
            }

            if ($sourceType === self::SOURCE_INVENTORY &&
                (int) $sender->role_id === User::ROLE_ADMIN) {
                $productColor->sizes = json_encode($centralSizes);
                $productColor->stock = array_sum(array_map(function ($size) {
                    return (int) ($size['stock'] ?? 0);
                }, $centralSizes));
                $productColor->save();
            }

            // Keep the existing storefront behaviour, but persist the normalized USD value.
            $productColor->product->update(['retail_price' => $retailPrice]);

            if ($merchant) {
                $this->recordMerchantBalances(
                    $merchant,
                    $destination,
                    $productColor,
                    end($savedProducts),
                    $totalQuantity,
                    round($totalMerchantAmount, 2)
                );
            }

            return [
                'destination' => $destination,
                'last_product' => end($savedProducts),
                'logs' => $logs,
                'message' => end($logs)['note'],
                'saved_items' => collect($savedProducts)->map(function (UserProduct $product) {
                    return [
                        'id' => $product->id,
                        'size' => $product->size,
                        'barcode' => $product->barcode,
                        'stock' => (int) $product->stock,
                        'retail_price' => (float) $product->retail_price,
                        'wholesale_price' => (float) $product->wholesale_price,
                    ];
                })->all(),
            ];
        }, 3);

        $this->clearHomeCache();
        $this->notifyTransfer($sender, $result);

        return [
            'status' => 'success',
            'source_type' => $sourceType,
            'destination_user_id' => $result['destination']->id,
            'items' => $result['saved_items'],
            'retail_price_usd' => $retailPrice,
            'wholesale_price_usd' => $wholesalePrice,
        ];
    }

    private function assertCountryAccess(
        User $sender,
        User $destination,
        ProductColor $productColor,
        string $sourceType
    ): void
    {
        if (!in_array((int) $sender->role_id, [User::ROLE_ADMIN, User::ROLE_WAREHOUSE], true)) {
            throw ValidationException::withMessages([
                'destination_user_id' => 'Only an administrator or warehouse can transfer stock.',
            ]);
        }

        if ((int) $destination->country_id !== (int) $sender->country_id) {
            throw ValidationException::withMessages([
                'destination_user_id' => 'الوجهة المختارة غير متاحة في هذا البلد.',
            ]);
        }

        if ($sourceType === self::SOURCE_INVENTORY &&
            (int) $sender->role_id === User::ROLE_WAREHOUSE &&
            (int) $destination->id === (int) $sender->id) {
            throw ValidationException::withMessages([
                'destination_user_id' => 'يجب أن تكون جهة الاستلام مختلفة عن المستودع المرسل.',
            ]);
        }

        if (!in_array((int) $destination->role_id, [User::ROLE_SHOP, User::ROLE_WAREHOUSE], true)) {
            throw ValidationException::withMessages([
                'destination_user_id' => 'جهة الاستلام المختارة غير صالحة.',
            ]);
        }

        if (!in_array((int) $productColor->country_id, [
            (int) $sender->country_id,
            Country::globalProductId(),
        ], true)) {
            throw ValidationException::withMessages([
                'product_color_id' => 'The selected product is not available in this country.',
            ]);
        }
    }

    private function assertCatalogItem(array $sizes, string $size, string $barcode): void
    {
        foreach ($sizes as $catalogSize) {
            if ((string) ($catalogSize['size'] ?? '') === $size &&
                (string) ($catalogSize['barcode'] ?? '') === $barcode) {
                return;
            }
        }

        throw ValidationException::withMessages([
            'items' => "المقاس {$size} أو الباركود المحدد لا ينتمي إلى هذا الموديل.",
        ]);
    }

    private function deductWarehouseStock(
        User $sender,
        ProductColor $productColor,
        string $size,
        string $barcode,
        int $quantity
    ): void {
        $sources = UserProduct::query()
            ->where('user_id', $sender->id)
            ->where('product_color_id', $productColor->id)
            ->where('size', $size)
            ->where('barcode', $barcode)
            ->lockForUpdate()
            ->get();

        if ($sources->isEmpty() || (int) $sources->sum('stock') < $quantity) {
            throw ValidationException::withMessages([
                'items' => "الكمية المتاحة للمقاس {$size} غير كافية.",
            ]);
        }

        $remaining = $quantity;
        foreach ($sources as $source) {
            if ($remaining <= 0) {
                break;
            }

            $deduction = min((int) $source->stock, $remaining);
            $source->decrement('stock', $deduction);
            $remaining -= $deduction;
        }
    }

    private function deductCentralStock(array $sizes, string $size, string $barcode, int $quantity): array
    {
        foreach ($sizes as $index => $sourceSize) {
            if ((string) ($sourceSize['size'] ?? '') !== $size ||
                (string) ($sourceSize['barcode'] ?? '') !== $barcode) {
                continue;
            }

            if ((int) ($sourceSize['stock'] ?? 0) < $quantity) {
                throw ValidationException::withMessages([
                    'items' => "The available stock for size {$size} is not sufficient.",
                ]);
            }

            $sizes[$index]['stock'] = (int) $sourceSize['stock'] - $quantity;
            return $sizes;
        }

        throw ValidationException::withMessages([
            'items' => "Size {$size} does not belong to the selected product.",
        ]);
    }

    private function recordMerchantBalances(
        User $merchant,
        User $destination,
        ProductColor $productColor,
        UserProduct $lastProduct,
        int $quantity,
        float $amount
    ): void {
        $merchantDebit = MerchantDebit::query()->firstOrCreate([
            'creditor_id' => $merchant->id,
            'debtor_id' => $destination->id,
        ], ['amount' => 0]);
        $merchantDebit->increment('amount', $amount);

        Wallet::query()->firstOrCreate(
            ['user_id' => $destination->id],
            ['credit' => 0, 'debit' => 0]
        )->increment('debit', $amount);
        Wallet::query()->firstOrCreate(
            ['user_id' => $merchant->id],
            ['credit' => 0, 'debit' => 0]
        )->increment('credit', $amount);

        $today = now()->toDateString();
        $debitLog = DebitLog::query()
            ->where('product_color_id', $productColor->id)
            ->where('shop_id', $destination->id)
            ->where('merchant_id', $merchant->id)
            ->where('request_date', $today)
            ->where('type', 'store')
            ->lockForUpdate()
            ->first();

        $note = "إرسال {$quantity} من {$productColor->product_name} إلى {$destination->name}";
        if ($debitLog) {
            $debitLog->update([
                'qty' => (int) $debitLog->qty + $quantity,
                'amount' => (float) $debitLog->amount + $amount,
                'note' => $note,
            ]);
            return;
        }

        DebitLog::query()->create([
            'merchant_debit_id' => $merchantDebit->id,
            'user_product_id' => $lastProduct->id,
            'product_color_id' => $productColor->id,
            'merchant_id' => $merchant->id,
            'request_date' => $today,
            'shop_id' => $destination->id,
            'qty' => $quantity,
            'amount' => $amount,
            'note' => $note,
            'type' => 'store',
        ]);
    }

    private function transferMessage(
        User $sender,
        User $destination,
        ProductColor $productColor,
        string $size,
        int $quantity,
        float $retailPrice,
        int $countryId
    ): string {
        $currencyCode = Country::defaultCurrency($countryId);
        $displayPrice = $this->currency->fromUsd($retailPrice, $currencyCode);

        return "قام {$sender->name} بإرسال {$quantity} من المنتج {$productColor->product_name} "
            . "من المقاس {$size} إلى {$destination->name} بسعر مبيع {$displayPrice} {$currencyCode}";
    }

    private function notifyTransfer(User $sender, array $result): void
    {
        try {
            $recipients = User::query()
                ->where('country_id', $sender->country_id)
                ->where('role_id', User::ROLE_ADMIN)
                ->get()
                ->push($sender)
                ->push($result['destination'])
                ->unique('id');

            foreach ($recipients as $recipient) {
                $recipient->notify(new ShopNotification(
                    $result['last_product'],
                    $result['message'],
                    $sender,
                    $result['logs']
                ));
            }
        } catch (\Throwable $exception) {
            Log::warning('Inventory transfer notification failed.', [
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function decodeSizes($sizes): array
    {
        if (is_array($sizes)) {
            return $sizes;
        }

        $decoded = json_decode((string) $sizes, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function clearHomeCache(): void
    {
        foreach (Country::allowedIds() as $countryId) {
            foreach (['home_new_products_', 'home_offer_products_', 'home_random_products_'] as $prefix) {
                Cache::forget($prefix.$countryId);
                Cache::forget($prefix.$countryId.'_m');
            }
        }
    }
}

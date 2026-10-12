<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\UserProduct;
use App\Services\CurrencyService;
use App\Services\OperationalPriceService;
use App\Support\Country;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillOperationalPrices extends Command
{
    protected $signature = 'catalog:backfill-operational-prices {--chunk=500}';
    protected $description = 'Freeze legacy product prices in their operational currencies without changing base USD values';

    private $currencies;

    public function __construct(CurrencyService $currencies)
    {
        parent::__construct();
        $this->currencies = $currencies;
    }

    public function handle(): int
    {
        if (!Schema::hasTable('operational_prices')) {
            $this->error('Run migrations before backfilling operational prices.');
            return 1;
        }

        $chunk = max(50, (int) $this->option('chunk'));
        $products = 0;
        $inventory = 0;

        Product::query()->select(['id', 'country_id', 'cost_price', 'retail_price', 'sale_price', 'price_before_discount'])
            ->orderBy('id')->chunkById($chunk, function ($rows) use (&$products) {
                foreach ($rows as $product) {
                    $this->insertSnapshot($product, 'USD', OperationalPriceService::PRODUCT_FIELDS, false);
                    if (in_array((int) $product->country_id, [Country::UAE, Country::globalProductId()], true)) {
                        $this->insertSnapshot($product, 'AED', OperationalPriceService::PRODUCT_FIELDS, true);
                    }
                    $products++;
                }
            });

        UserProduct::query()->select(['id', 'country_id', 'wholesale_price', 'retail_price', 'price_before_discount'])
            ->orderBy('id')->chunkById($chunk, function ($rows) use (&$inventory) {
                foreach ($rows as $item) {
                    $this->insertSnapshot($item, 'USD', OperationalPriceService::USER_PRODUCT_FIELDS, false);
                    if ((int) $item->country_id === Country::UAE) {
                        $this->insertSnapshot($item, 'AED', OperationalPriceService::USER_PRODUCT_FIELDS, true);
                    }
                    $inventory++;
                }
            });

        $this->info("Backfilled {$products} products and {$inventory} inventory rows.");
        return 0;
    }

    private function insertSnapshot($model, string $currency, array $fields, bool $preserveLegacyAedDisplay): void
    {
        $rate = $this->currencies->rate($currency);
        $values = [];
        foreach ($fields as $field) {
            $raw = $model->getRawOriginal($field);
            if ($raw === null) {
                $values[$field] = null;
                continue;
            }
            $converted = $this->currencies->fromUsdAtRate($raw, $rate, $currency);
            $values[$field] = $preserveLegacyAedDisplay ? ceil($converted) : $converted;
        }

        DB::table('operational_prices')->insertOrIgnore(array_merge([
            'priceable_type' => $model->getMorphClass(),
            'priceable_id' => $model->getKey(),
            'currency_code' => $currency,
            'exchange_rate' => $rate,
            'created_at' => now(),
            'updated_at' => now(),
        ], $values));
    }
}

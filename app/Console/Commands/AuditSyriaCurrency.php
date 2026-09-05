<?php

namespace App\Console\Commands;

use App\Support\Country;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditSyriaCurrency extends Command
{
    protected $signature = 'currency:audit-syria {--json : Output the complete report as JSON}';

    protected $description = 'Read-only audit of Syrian prices and order currency metadata';

    public function handle(): int
    {
        $countryId = Country::SYRIA;
        $report = [
            'notice' => 'READ ONLY: amounts are reported exactly as stored; no conversion or update was performed.',
            'syp_rate' => $this->sypRate(),
            'products' => $this->products($countryId),
            'inventory' => $this->inventory($countryId),
            'admin_orders' => $this->adminOrders($countryId),
            'website_orders' => $this->websiteOrders($countryId),
        ];

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return self::SUCCESS;
        }

        $this->warn($report['notice']);
        $this->table(['Check', 'Value'], [
            ['SYP rate', $report['syp_rate']['rate'] ?? 'missing/invalid'],
            ['Syrian products', count($report['products'])],
            ['Syrian inventory rows', count($report['inventory'])],
            ['Syrian admin orders', count($report['admin_orders'])],
            ['Syrian website orders', count($report['website_orders'])],
            ['Historical SYP orders', collect($report['admin_orders'])->where('curr_type', 'SYP')->count()
                + collect($report['website_orders'])->where('curr_type', 'SYP')->count()],
        ]);

        $this->section('Products (raw stored USD fields)', $report['products']);
        $this->section('Inventory (raw stored USD fields)', $report['inventory']);
        $this->section('Admin orders (historical currency retained)', $report['admin_orders']);
        $this->section('Website orders (historical currency retained)', $report['website_orders']);

        return self::SUCCESS;
    }

    private function sypRate(): array
    {
        if (!Schema::hasTable('currencies')) {
            return ['rate' => null, 'valid' => false];
        }

        $row = DB::table('currencies')->where('name', 'syp')->first();
        $rate = $row ? (float) $row->rate : null;
        return ['rate' => $rate, 'valid' => $rate !== null && $rate > 0];
    }

    private function products(int $countryId): array
    {
        if (!Schema::hasTable('products')) {
            return [];
        }

        $fields = array_values(array_filter(
            ['id', 'barcode', 'cost_price', 'sale_price', 'retail_price', 'price_before_discount'],
            function ($field) {
                return Schema::hasColumn('products', $field);
            }
        ));

        return DB::table('products')->select($fields)->where('country_id', $countryId)->orderBy('id')->get()
            ->map(function ($row) {
                return $this->markInvalidAmounts((array) $row);
            })->all();
    }

    private function inventory(int $countryId): array
    {
        if (!Schema::hasTable('user_products')) {
            return [];
        }

        $fields = array_values(array_filter(
            ['id', 'user_id', 'product_color_id', 'stock', 'wholesale_price', 'retail_price', 'price_before_discount'],
            function ($field) {
                return Schema::hasColumn('user_products', $field);
            }
        ));

        return DB::table('user_products')->select($fields)->where('country_id', $countryId)->orderBy('id')->get()
            ->map(function ($row) {
                return $this->markInvalidAmounts((array) $row);
            })->all();
    }

    private function adminOrders(int $countryId): array
    {
        if (!Schema::hasTable('orders') || !Schema::hasTable('users')) {
            return [];
        }

        return DB::table('orders')
            ->join('users', 'users.id', '=', 'orders.seller_id')
            ->where('users.country_id', $countryId)
            ->orderBy('orders.id')
            ->get($this->orderSelects('orders'))
            ->map(function ($row) {
                return $this->markOrder((array) $row);
            })->all();
    }

    private function websiteOrders(int $countryId): array
    {
        if (!Schema::hasTable('website_orders')) {
            return [];
        }

        return DB::table('website_orders')->where('country_id', $countryId)->orderBy('id')
            ->get($this->orderSelects('website_orders'))
            ->map(function ($row) {
                return $this->markOrder((array) $row);
            })->all();
    }

    private function orderSelects(string $table): array
    {
        return collect(['id', 'barcode', 'total_price', 'curr_type', 'curr_rate', 'display_currency', 'display_rate'])
            ->filter(function ($field) use ($table) {
                return Schema::hasColumn($table, $field);
            })
            ->map(function ($field) use ($table) {
                return $table.'.'.$field;
            })
            ->all();
    }

    private function markInvalidAmounts(array $row): array
    {
        $amounts = collect($row)->only(['cost_price', 'sale_price', 'retail_price', 'wholesale_price', 'price_before_discount']);
        $row['invalid_amounts'] = $amounts->filter(function ($amount) {
            return $amount !== null && (float) $amount <= 0;
        })->keys()->values()->all();
        return $row;
    }

    private function markOrder(array $row): array
    {
        $row['historical_syp'] = strtoupper((string) ($row['curr_type'] ?? '')) === 'SYP';
        $row['invalid_rate'] = !isset($row['curr_rate']) || (float) $row['curr_rate'] <= 0;
        $row['missing_display_snapshot'] = strtoupper((string) ($row['curr_type'] ?? '')) === 'USD'
            && array_key_exists('display_currency', $row)
            && (!$row['display_currency'] || (float) ($row['display_rate'] ?? 0) <= 0);
        return $row;
    }

    private function section(string $title, array $rows): void
    {
        $this->newLine();
        $this->info($title);
        if (!$rows) {
            $this->line('None');
            return;
        }

        $this->table(array_keys($rows[0]), array_map(function ($row) {
            return array_map(function ($value) {
                return is_array($value) ? implode(',', $value) : $value;
            }, $row);
        }, $rows));
    }
}

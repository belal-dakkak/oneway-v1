<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExpandCurrencyAmountPrecision extends Migration
{
    public function up()
    {
        if (Schema::hasTable('currencies') && Schema::hasColumn('currencies', 'rate')) {
            Schema::table('currencies', function (Blueprint $table) {
                $table->decimal('rate', 20, 6)->change();
            });
        }

        $this->changeMoneyColumns('orders', [
            'total_price_before_discount', 'discount', 'total_price', 'paid_price',
            'remain_price', 'tax_value', 'price_without_tax', 'shipping_fee', 'cod_fee',
        ]);
        $this->changeMoneyColumns('order_items', [
            'item_price', 'total_price', 'tax_value', 'price_without_tax', 'item_price_paid',
            'total_price_paid', 'tax_value_paid', 'price_without_tax_paid',
        ]);
        $this->changeMoneyColumns('website_orders', [
            'total_price_before_discount', 'discount', 'total_price', 'paid_price',
            'remain_price', 'shipping_fee', 'cod_fee',
        ]);
        $this->changeMoneyColumns('website_order_items', [
            'item_price', 'total_price', 'item_price_before_discount', 'total_price_before_discount',
        ]);

        foreach (['orders', 'website_orders'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'curr_rate')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->decimal('curr_rate', 20, 6)->default(1)->change();
                });
            }
        }
    }

    private function changeMoneyColumns(string $tableName, array $columns): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        $existingColumns = array_values(array_filter($columns, function ($column) use ($tableName) {
            return Schema::hasColumn($tableName, $column);
        }));

        if ($existingColumns) {
            Schema::table($tableName, function (Blueprint $table) use ($existingColumns) {
                foreach ($existingColumns as $column) {
                    $table->decimal($column, 20, 4)->nullable()->change();
                }
            });
        }
    }

    public function down()
    {
        // Precision expansion is intentionally not reversed to avoid truncating financial data.
    }
}

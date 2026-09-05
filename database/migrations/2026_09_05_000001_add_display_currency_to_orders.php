<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDisplayCurrencyToOrders extends Migration
{
    public function up()
    {
        foreach (['orders', 'website_orders'] as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            if (!Schema::hasColumn($tableName, 'display_currency')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->string('display_currency', 8)->nullable()->after('curr_rate');
                });
            }
            if (!Schema::hasColumn($tableName, 'display_rate')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->decimal('display_rate', 20, 6)->nullable()->after('display_currency');
                });
            }
        }
    }

    public function down()
    {
        foreach (['orders', 'website_orders'] as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            $columns = array_values(array_filter(['display_currency', 'display_rate'], function ($column) use ($tableName) {
                return Schema::hasColumn($tableName, $column);
            }));
            if ($columns) {
                Schema::table($tableName, function (Blueprint $table) use ($columns) {
                    $table->dropColumn($columns);
                });
            }
        }
    }
}

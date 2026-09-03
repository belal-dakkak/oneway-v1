<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentTrackingToWebsiteOrders extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('website_orders')) {
            return;
        }

        Schema::table('website_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('website_orders', 'stock_reserved_at')) {
                $table->timestamp('stock_reserved_at')->nullable()->after('invoice');
            }
            if (!Schema::hasColumn('website_orders', 'stock_released_at')) {
                $table->timestamp('stock_released_at')->nullable()->after('stock_reserved_at');
            }
            if (!Schema::hasColumn('website_orders', 'payment_captured_at')) {
                $table->timestamp('payment_captured_at')->nullable()->after('stock_released_at');
            }
            if (!Schema::hasColumn('website_orders', 'notifications_sent_at')) {
                $table->timestamp('notifications_sent_at')->nullable()->after('payment_captured_at');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('website_orders')) {
            return;
        }

        $columns = array_values(array_filter([
            'stock_reserved_at',
            'stock_released_at',
            'payment_captured_at',
            'notifications_sent_at',
        ], function ($column) {
            return Schema::hasColumn('website_orders', $column);
        }));

        if ($columns) {
            Schema::table('website_orders', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPricingModeToWebsiteOrders extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('website_orders', 'pricing_mode')) {
            Schema::table('website_orders', function (Blueprint $table) {
                $table->string('pricing_mode', 16)->default('retail')->after('country_id')->index();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('website_orders', 'pricing_mode')) {
            Schema::table('website_orders', function (Blueprint $table) {
                $table->dropColumn('pricing_mode');
            });
        }
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomerTrnToWebsiteOrders extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('website_orders', 'trn')) {
            Schema::table('website_orders', function (Blueprint $table) {
                $table->string('trn')->nullable();
            });
        }
    }

    public function down()
    {
        // Keep historical invoice snapshots.
    }
}

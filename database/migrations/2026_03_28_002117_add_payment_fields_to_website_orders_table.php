<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentFieldsToWebsiteOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('website_orders', function (Blueprint $table) {
            $table->decimal('paid_price', 10)->nullable()->after('total_price');
            $table->decimal('remain_price', 10)->nullable()->after('paid_price');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('website_orders', function (Blueprint $table) {
            //
        });
    }
}

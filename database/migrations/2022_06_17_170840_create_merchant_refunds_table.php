<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMerchantRefundsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('merchant_refunds', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_product_id');

            $table->foreignId('merchant_id')
                ->nullable()
                ->constrained('users')
                ->references('id');

            $table->foreignId('merchant_debit_id')
                ->constrained('merchant_debits')
                ->references('id')
                ->onDelete('cascade');

            $table->integer('qty');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('merchant_refunds');
    }
}

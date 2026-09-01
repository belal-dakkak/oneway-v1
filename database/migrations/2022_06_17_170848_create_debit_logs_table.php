<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDebitLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('debit_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('merchant_debit_id')
                ->constrained('merchant_debits')
                ->references('id')
                ->onDelete('cascade');

            $table->foreignId('user_product_id')
                ->nullable();

            $table->foreignId('debit_payment_id')
                ->nullable();

            $table->foreignId('merchant_refund_id')
                ->nullable();

            $table->text('note');

            $table->decimal('amount');
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
        Schema::dropIfExists('debit_logs');
    }
}

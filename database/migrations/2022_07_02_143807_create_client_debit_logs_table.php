<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientDebitLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_debit_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_debit_id')
                ->constrained('client_debits')
                ->references('id')
                ->onDelete('cascade');

            $table->foreignId('order_id')
                ->nullable();

            $table->foreignId('client_debit_payment_id')
                ->nullable();

            $table->foreignId('client_refund_id')
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
        Schema::dropIfExists('client_debit_logs');
    }
}

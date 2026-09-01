<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientRefundsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_refunds', function (Blueprint $table) {
            $table->id();

            $table->foreignId('refund_id');

            $table->foreignId('client_id')
                ->nullable()
                ->constrained('users')
                ->references('id');

            $table->foreignId('client_debit_id')
                ->constrained('client_debits')
                ->references('id')
                ->onDelete('cascade');

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
        Schema::dropIfExists('client_refunds');
    }
}

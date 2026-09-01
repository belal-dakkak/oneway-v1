<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMerchantDebitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('merchant_debits', function (Blueprint $table) {
            $table->id();

            $table->foreignId('creditor_id')
                ->constrained('users')
                ->references('id')
                ->onDelete('cascade');

            $table->foreignId('debtor_id')
                ->constrained('users')
                ->references('id')
                ->onDelete('cascade');

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
        Schema::dropIfExists('merchant_debits');
    }
}

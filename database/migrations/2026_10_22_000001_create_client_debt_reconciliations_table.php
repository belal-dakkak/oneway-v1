<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientDebtReconciliationsTable extends Migration
{
    public function up()
    {
        Schema::create('client_debt_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_debit_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('currency_code', 3);
            $table->decimal('previous_paid_price', 20, 4);
            $table->decimal('previous_remain_price', 20, 4);
            $table->decimal('reconciled_amount', 20, 4);
            $table->decimal('account_balance_before', 20, 4);
            $table->decimal('account_balance_after', 20, 4);
            $table->string('reason', 80);
            $table->string('idempotency_key', 64)->unique();
            $table->timestamps();

            $table->foreign('client_debit_id')->references('id')->on('client_debits')->cascadeOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            $table->foreign('actor_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['client_debit_id', 'currency_code'], 'client_debt_reconciliations_account_currency_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('client_debt_reconciliations');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOperationalCurrencyAndConversionAudit extends Migration
{
    public function up()
    {
        foreach (['expenses', 'debits', 'merchant_debits', 'debit_logs', 'debit_payments'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                // NULL preserves the distinction between legacy and newly explicit currency.
                $table->string('currency_code', 3)->nullable();
                $table->decimal('exchange_rate', 20, 6)->nullable();
                $table->decimal('amount', 20, 4)->change();
            });
        }
        Schema::create('finance_conversion_batches', function (Blueprint $table) {
            $table->id();
            $table->string('conversion_key')->unique();
            $table->string('fingerprint', 64);
            $table->longText('report');
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
        });
        Schema::create('finance_conversion_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('finance_conversion_batches');
            $table->string('table_name', 64);
            $table->unsignedBigInteger('record_id');
            $table->string('field_name', 64);
            $table->longText('before_value')->nullable();
            $table->longText('after_value')->nullable();
            $table->unique(['table_name', 'record_id', 'field_name'], 'finance_conversion_field_once');
        });
    }

    public function down()
    {
        // Conversion history and financial currency metadata are intentionally retained.
    }
}

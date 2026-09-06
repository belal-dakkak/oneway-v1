<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMultiCurrencyClientAccounts extends Migration
{
    public function up()
    {
        Schema::table('client_debits', function (Blueprint $table) {
            if (!Schema::hasColumn('client_debits', 'currency_code')) {
                $table->string('currency_code', 3)->default('USD')->after('amount')->index();
                $table->index(
                    ['creditor_id', 'debtor_id', 'currency_code'],
                    'client_debits_account_currency_index'
                );
            }
            $table->decimal('amount', 20, 4)->default(0)->change();
        });

        Schema::table('client_debit_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('client_debit_payments', 'exchange_rate')) {
                $table->decimal('exchange_rate', 20, 6)->default(1)->after('amount');
            }
            if (!Schema::hasColumn('client_debit_payments', 'base_amount')) {
                $table->decimal('base_amount', 20, 4)->default(0)->after('exchange_rate');
            }
            $table->decimal('amount', 20, 4)->change();
        });

        Schema::table('client_debit_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('client_debit_logs', 'currency_code')) {
                $table->string('currency_code', 3)->default('USD')->after('amount')->index();
            }
            if (!Schema::hasColumn('client_debit_logs', 'exchange_rate')) {
                $table->decimal('exchange_rate', 20, 6)->default(1)->after('currency_code');
            }
            if (!Schema::hasColumn('client_debit_logs', 'base_amount')) {
                $table->decimal('base_amount', 20, 4)->default(0)->after('exchange_rate');
            }
            $table->decimal('amount', 20, 4)->change();
        });

        Schema::table('order_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('order_payments', 'exchange_rate')) {
                $table->decimal('exchange_rate', 20, 6)->default(1)->after('pay_amount');
            }
            if (!Schema::hasColumn('order_payments', 'base_amount')) {
                $table->decimal('base_amount', 20, 4)->default(0)->after('exchange_rate');
            }
            $table->decimal('pay_amount', 20, 4)->nullable()->change();
        });

        if (Schema::hasTable('refunds') && !Schema::hasColumn('refunds', 'total_price_paid')) {
            Schema::table('refunds', function (Blueprint $table) {
                $table->decimal('total_price_paid', 20, 4)->nullable()->after('total_price');
            });
        }
    }

    public function down()
    {
        // Financial precision and historical currency markers are intentionally retained.
    }
}

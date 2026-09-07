<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddMultiCurrencyCashboxesAndGatewayFields extends Migration
{
    public function up()
    {
        if (Schema::hasTable('wallets')) {
            Schema::table('wallets', function (Blueprint $table) {
                if (!Schema::hasColumn('wallets', 'currency_code')) {
                    $table->string('currency_code', 3)->default('USD')->after('user_id')->index();
                }
                $table->decimal('credit', 24, 4)->default(0)->change();
                $table->decimal('debit', 24, 4)->default(0)->change();
            });

            // Every historical wallet represented the old USD base balance.
            DB::table('wallets')->whereNull('currency_code')->update(['currency_code' => 'USD']);

            // The service serializes writes per user; this composite index also
            // keeps currency lookups fast without risking a failed deployment if
            // legacy data happens to contain duplicate wallet rows.
            if (!$this->indexExists('wallets', 'wallets_user_currency_index')) {
                Schema::table('wallets', function (Blueprint $table) {
                    $table->index(['user_id', 'currency_code'], 'wallets_user_currency_index');
                });
            }
        }

        if (!Schema::hasTable('wallet_movements')) {
            Schema::create('wallet_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('currency_code', 3)->index();
                $table->string('direction', 8);
                $table->decimal('amount', 24, 4);
                $table->decimal('exchange_rate', 20, 6)->default(1);
                $table->decimal('base_amount', 24, 4);
                $table->decimal('balance_after', 24, 4);
                $table->string('payment_method', 32)->nullable();
                $table->string('source_type', 64)->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->uuid('exchange_group')->nullable()->index();
                $table->string('idempotency_key', 191)->unique();
                $table->text('note')->nullable();
                $table->timestamps();

                $table->index(['source_type', 'source_id']);
                $table->index(['user_id', 'currency_code', 'created_at']);
            });
        }

        if (Schema::hasTable('country_commerce_settings')) {
            Schema::table('country_commerce_settings', function (Blueprint $table) {
                if (!Schema::hasColumn('country_commerce_settings', 'card_enabled')) {
                    $table->boolean('card_enabled')->default(false)->after('cod_fee_percent');
                }
                if (!Schema::hasColumn('country_commerce_settings', 'gateway_currency')) {
                    $table->string('gateway_currency', 3)->default('USD')->after('card_enabled');
                }
                if (!Schema::hasColumn('country_commerce_settings', 'gateway_mode')) {
                    $table->string('gateway_mode', 16)->default('sandbox')->after('gateway_currency');
                }
                if (!Schema::hasColumn('country_commerce_settings', 'website_cashbox_user_id')) {
                    $table->unsignedBigInteger('website_cashbox_user_id')->nullable()->after('gateway_mode')->index();
                }
            });
        }

        if (Schema::hasTable('website_orders')) {
            Schema::table('website_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('website_orders', 'gateway_provider')) {
                    $table->string('gateway_provider', 32)->nullable()->after('invoice');
                }
                if (!Schema::hasColumn('website_orders', 'gateway_currency')) {
                    $table->string('gateway_currency', 3)->nullable()->after('gateway_provider');
                }
                if (!Schema::hasColumn('website_orders', 'gateway_amount')) {
                    $table->decimal('gateway_amount', 24, 4)->nullable()->after('gateway_currency');
                }
                if (!Schema::hasColumn('website_orders', 'gateway_rate')) {
                    $table->decimal('gateway_rate', 20, 6)->nullable()->after('gateway_amount');
                }
                if (!Schema::hasColumn('website_orders', 'cashbox_posted_at')) {
                    $table->timestamp('cashbox_posted_at')->nullable()->after('notifications_sent_at');
                }
            });
        }

        if (Schema::hasTable('website_order_items')
            && !Schema::hasColumn('website_order_items', 'stock_user_product_id')) {
            Schema::table('website_order_items', function (Blueprint $table) {
                // Snapshot the exact stock record reserved for the line. This is
                // deliberately nullable so historical orders keep their fallback.
                $table->unsignedBigInteger('stock_user_product_id')->nullable()->after('product_color_id')->index();
            });
        }
    }

    public function down()
    {
        // Financial audit data and historical currency markers are intentionally retained.
    }

    private function indexExists(string $table, string $index): bool
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            foreach (DB::select("PRAGMA index_list('{$table}')") as $row) {
                if (($row->name ?? null) === $index) {
                    return true;
                }
            }
            return false;
        }

        if ($driver === 'mysql') {
            return count(DB::select(
                'SELECT INDEX_NAME FROM information_schema.statistics '
                . 'WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
                [$table, $index]
            )) > 0;
        }

        return false;
    }
}

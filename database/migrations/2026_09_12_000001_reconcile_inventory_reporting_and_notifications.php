<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReconcileInventoryReportingAndNotifications extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }

        if (Schema::hasTable('country_commerce_settings')
            && !Schema::hasColumn('country_commerce_settings', 'website_stock_user_id')) {
            Schema::table('country_commerce_settings', function (Blueprint $table) {
                $table->unsignedBigInteger('website_stock_user_id')->nullable()->after('website_cashbox_user_id')->index();
            });

            DB::table('country_commerce_settings')->orderBy('id')->each(function ($setting) {
                if (!$setting->website_cashbox_user_id) {
                    return;
                }

                $valid = DB::table('users')
                    ->where('id', $setting->website_cashbox_user_id)
                    ->where('country_id', $setting->country_id)
                    ->whereIn('role_id', [2, 3])
                    ->exists();
                if ($valid) {
                    DB::table('country_commerce_settings')->where('id', $setting->id)->update([
                        'website_stock_user_id' => $setting->website_cashbox_user_id,
                    ]);
                }
            });
        }

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (!Schema::hasColumn('products', 'sale_price')) {
                    $table->decimal('sale_price', 20, 4)->nullable()->after('retail_price');
                }
                if (!Schema::hasColumn('products', 'price_before_discount')) {
                    $table->decimal('price_before_discount', 20, 4)->nullable()->after('sale_price');
                }
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'order_type')) {
                    $table->string('order_type', 32)->nullable()->after('type');
                }
                if (!Schema::hasColumn('orders', 'tax_ratio')) {
                    $table->decimal('tax_ratio', 8, 4)->default(0);
                }
                if (!Schema::hasColumn('orders', 'tax_value')) {
                    $table->decimal('tax_value', 20, 4)->default(0);
                }
                if (!Schema::hasColumn('orders', 'price_without_tax')) {
                    $table->decimal('price_without_tax', 20, 4)->default(0);
                }
                if (!Schema::hasColumn('orders', 'trn')) {
                    $table->string('trn')->nullable();
                }
            });
        }

        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) {
                if (!Schema::hasColumn('order_items', 'sold_qty')) {
                    $table->unsignedInteger('sold_qty')->nullable()->after('qty');
                }
                if (!Schema::hasColumn('order_items', 'unit_cost')) {
                    $table->decimal('unit_cost', 20, 4)->nullable()->after('sold_qty');
                }
                if (!Schema::hasColumn('order_items', 'tax_ratio')) {
                    $table->decimal('tax_ratio', 8, 4)->default(0);
                }
                if (!Schema::hasColumn('order_items', 'tax_value')) {
                    $table->decimal('tax_value', 20, 4)->default(0);
                }
                if (!Schema::hasColumn('order_items', 'price_without_tax')) {
                    $table->decimal('price_without_tax', 20, 4)->default(0);
                }
                if (!Schema::hasColumn('order_items', 'item_price_paid')) {
                    $table->decimal('item_price_paid', 20, 4)->default(0);
                }
                if (!Schema::hasColumn('order_items', 'total_price_paid')) {
                    $table->decimal('total_price_paid', 20, 4)->default(0);
                }
                if (!Schema::hasColumn('order_items', 'tax_value_paid')) {
                    $table->decimal('tax_value_paid', 20, 4)->default(0);
                }
                if (!Schema::hasColumn('order_items', 'price_without_tax_paid')) {
                    $table->decimal('price_without_tax_paid', 20, 4)->default(0);
                }
            });

            DB::table('order_items')->orderBy('id')->chunkById(500, function ($items) {
                foreach ($items as $item) {
                    $refunded = Schema::hasTable('refunds')
                        ? (int) DB::table('refunds')->where('order_item_id', $item->id)->sum('qty')
                        : 0;
                    $cost = DB::table('user_products')->where('id', $item->user_product_id)->value('wholesale_price');
                    DB::table('order_items')->where('id', $item->id)->update([
                        'sold_qty' => (int) $item->qty + $refunded,
                        'unit_cost' => $cost === null ? 0 : $cost,
                    ]);
                }
            });
        }

        if (Schema::hasTable('refunds')) {
            Schema::table('refunds', function (Blueprint $table) {
                if (!Schema::hasColumn('refunds', 'currency_code')) {
                    $table->string('currency_code', 3)->nullable()->after('total_price_paid');
                }
                if (!Schema::hasColumn('refunds', 'net_amount')) {
                    $table->decimal('net_amount', 20, 4)->nullable()->after('currency_code');
                }
                if (!Schema::hasColumn('refunds', 'tax_amount')) {
                    $table->decimal('tax_amount', 20, 4)->nullable()->after('net_amount');
                }
                if (!Schema::hasColumn('refunds', 'cost_amount')) {
                    $table->decimal('cost_amount', 20, 4)->nullable()->after('tax_amount');
                }
            });

            DB::table('refunds')->orderBy('id')->chunkById(500, function ($refunds) {
                foreach ($refunds as $refund) {
                    $item = DB::table('order_items')->where('id', $refund->order_item_id)->first();
                    $order = $item ? DB::table('orders')->where('id', $item->order_id)->first() : null;
                    if (!$item || !$order) {
                        continue;
                    }
                    $rate = max((float) ($order->curr_rate ?: 1), 0.000001);
                    $total = (float) ($refund->total_price_paid ?: ((float) $refund->total_price * $rate));
                    $netUnit = (float) ($item->price_without_tax_paid ?: $item->item_price_paid ?: 0);
                    $taxUnit = (float) ($item->tax_value_paid ?: 0);
                    DB::table('refunds')->where('id', $refund->id)->update([
                        'currency_code' => strtoupper((string) ($order->curr_type ?: 'USD')),
                        'net_amount' => $netUnit > 0 ? $netUnit * (int) $refund->qty : max(0, $total - ($taxUnit * (int) $refund->qty)),
                        'tax_amount' => $taxUnit * (int) $refund->qty,
                        'cost_amount' => (float) ($item->unit_cost ?: 0) * $rate * (int) $refund->qty,
                    ]);
                }
            });
        }

        if (Schema::hasTable('website_orders')) {
            Schema::table('website_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('website_orders', 'confirmation_email_sent_at')) {
                    $table->timestamp('confirmation_email_sent_at')->nullable()->after('notifications_sent_at');
                }
                if (!Schema::hasColumn('website_orders', 'notification_last_error')) {
                    $table->text('notification_last_error')->nullable()->after('confirmation_email_sent_at');
                }
            });
        }

        $this->mergeDuplicateWalletsAndEnforceUniqueness();
    }

    private function mergeDuplicateWalletsAndEnforceUniqueness(): void
    {
        if (!Schema::hasTable('wallets') || !Schema::hasColumn('wallets', 'currency_code')) {
            return;
        }

        $groups = DB::table('wallets')
            ->select('user_id', 'currency_code', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('user_id', 'currency_code')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($groups as $group) {
            DB::transaction(function () use ($group) {
                $wallets = DB::table('wallets')
                    ->where('user_id', $group->user_id)
                    ->where('currency_code', $group->currency_code)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();
                $canonical = $wallets->first();
                $duplicateIds = $wallets->skip(1)->pluck('id')->all();
                if (!$canonical || !$duplicateIds) {
                    return;
                }

                if (Schema::hasTable('wallet_movements')) {
                    DB::table('wallet_movements')->whereIn('wallet_id', $duplicateIds)->update(['wallet_id' => $canonical->id]);
                }
                DB::table('wallets')->where('id', $canonical->id)->update([
                    'credit' => $wallets->sum('credit'),
                    'debit' => $wallets->sum('debit'),
                ]);
                DB::table('wallets')->whereIn('id', $duplicateIds)->delete();
            });
        }

        if (!$this->indexExists('wallets', 'wallets_user_currency_unique')) {
            Schema::table('wallets', function (Blueprint $table) {
                $table->unique(['user_id', 'currency_code'], 'wallets_user_currency_unique');
            });
        }
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
                'SELECT INDEX_NAME FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
                [$table, $index]
            )) > 0;
        }
        return false;
    }

    public function down()
    {
        // Financial snapshots and audit safeguards intentionally remain in place.
    }
}

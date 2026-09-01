<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddSyriaCountrySupport extends Migration
{
    public function up()
    {
        if (Schema::hasTable('currencies')) {
            $currency = DB::table('currencies')->where('name', 'syp')->first();
            if ($currency) {
                DB::table('currencies')->where('name', 'syp')->update(['label' => 'SYP', 'updated_at' => now()]);
            } else {
                DB::table('currencies')->insert([
                    'name' => 'syp', 'label' => 'SYP', 'rate' => 0,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        if (!Schema::hasTable('country_commerce_settings')) {
            Schema::create('country_commerce_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedTinyInteger('country_id')->unique();
                $table->decimal('shipping_fee_usd', 20, 4)->default(0);
                $table->decimal('free_shipping_threshold_usd', 20, 4)->nullable();
                $table->decimal('cod_fee_percent', 5, 2)->default(0);
                $table->timestamps();
            });
        }

        $aedRate = max((float) DB::table('currencies')->where('name', 'aed')->value('rate'), 1);
        $commerceDefaults = [
            1 => ['shipping_fee_usd' => 5, 'free_shipping_threshold_usd' => 50, 'cod_fee_percent' => 10],
            2 => ['shipping_fee_usd' => 20 / $aedRate, 'free_shipping_threshold_usd' => 150 / $aedRate, 'cod_fee_percent' => 10],
            4 => ['shipping_fee_usd' => 0, 'free_shipping_threshold_usd' => 0, 'cod_fee_percent' => 0],
        ];
        foreach ($commerceDefaults as $countryId => $defaults) {
            if (!DB::table('country_commerce_settings')->where('country_id', $countryId)->exists()) {
                DB::table('country_commerce_settings')->insert(array_merge($defaults, [
                    'country_id' => $countryId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        if (Schema::hasTable('website_orders') && !Schema::hasColumn('website_orders', 'curr_type')) {
            Schema::table('website_orders', function (Blueprint $table) {
                $table->string('curr_type', 8)->nullable()->after('payment_type');
            });
            DB::table('website_orders')->where('country_id', 2)->update(['curr_type' => 'AED']);
            DB::table('website_orders')->where('country_id', '<>', 2)->update(['curr_type' => 'USD']);
        }
    }

    public function down()
    {
        if (Schema::hasTable('website_orders') && Schema::hasColumn('website_orders', 'curr_type')) {
            Schema::table('website_orders', function (Blueprint $table) {
                $table->dropColumn('curr_type');
            });
        }
        Schema::dropIfExists('country_commerce_settings');
        if (Schema::hasTable('currencies')) {
            DB::table('currencies')->where('name', 'syp')->delete();
        }
    }
}

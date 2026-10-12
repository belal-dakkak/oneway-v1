<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOperationalPricesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('operational_prices')) {
            Schema::create('operational_prices', function (Blueprint $table) {
                $table->id();
                $table->string('priceable_type', 120);
                $table->unsignedBigInteger('priceable_id');
                $table->string('currency_code', 3);
                $table->decimal('exchange_rate', 20, 6);
                $table->decimal('cost_price', 20, 4)->nullable();
                $table->decimal('wholesale_price', 20, 4)->nullable();
                $table->decimal('retail_price', 20, 4)->nullable();
                $table->decimal('sale_price', 20, 4)->nullable();
                $table->decimal('price_before_discount', 20, 4)->nullable();
                $table->timestamps();
                $table->unique(['priceable_type', 'priceable_id', 'currency_code'], 'operational_prices_owner_currency_unique');
                $table->index(['priceable_type', 'priceable_id'], 'operational_prices_owner_index');
            });
        }

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            $productColumns = array_values(array_filter(
                ['cost_price', 'retail_price', 'sale_price', 'price_before_discount'],
                function ($column) { return Schema::hasColumn('products', $column); }
            ));
            Schema::table('products', function (Blueprint $table) use ($productColumns) {
                foreach ($productColumns as $column) {
                    $definition = $table->decimal($column, 20, 4);
                    if ($column !== 'cost_price') {
                        $definition->nullable();
                    }
                    $definition->change();
                }
            });
            $inventoryColumns = array_values(array_filter(
                ['wholesale_price', 'retail_price', 'price_before_discount'],
                function ($column) { return Schema::hasColumn('user_products', $column); }
            ));
            Schema::table('user_products', function (Blueprint $table) use ($inventoryColumns) {
                foreach ($inventoryColumns as $column) {
                    $table->decimal($column, 20, 4)->nullable()->change();
                }
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('operational_prices');
    }
}

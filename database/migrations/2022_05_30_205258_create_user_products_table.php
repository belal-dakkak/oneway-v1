<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_color_id');
            $table->foreignId('user_id');
            $table->unsignedInteger('stock');
            $table->decimal('wholesale_price', 10)->nullable();
            $table->decimal('retail_price', 10)->nullable();
            $table->unique(['product_color_id', 'user_id']);
            $table->boolean('approved')->default(false);
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
        Schema::dropIfExists('user_products');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebsiteOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('website_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_order_id')->constrained('website_orders')->cascadeOnDelete();
            $table->foreignId('product_color_id')->constrained('product_colors')->cascadeOnDelete();
            $table->string('size')->nullable();
            $table->unsignedInteger('qty')->default(1);
            
            $table->decimal('item_price', 10)->nullable();
            $table->decimal('item_price_before_discount', 10)->nullable();
            $table->decimal('total_price', 10)->nullable();
            $table->decimal('total_price_before_discount', 10)->nullable();

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
        Schema::dropIfExists('website_order_items');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebsiteOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('website_orders', function (Blueprint $table) {
            $table->id();
            $table->string('barcode')->unique();
            $table->foreignId('buyer_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Shipping Details (Flat format for easier access)
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('building_name')->nullable();
            $table->string('flat_number')->nullable();

            $table->decimal('total_price_before_discount', 10)->nullable();
            $table->decimal('discount', 10)->nullable();
            $table->decimal('total_price', 10)->nullable();
            $table->decimal('shipping_fee', 10)->nullable();
            $table->decimal('cod_fee', 10)->nullable();

            $table->unsignedTinyInteger('status')->default(1); // 1: Pending
            $table->string('payment_type')->nullable();
            $table->string('invoice')->nullable(); // For Tap / Stripe IDs
            $table->text('notes')->nullable();
            $table->unsignedTinyInteger('country_id')->nullable();

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
        Schema::dropIfExists('website_orders');
    }
}

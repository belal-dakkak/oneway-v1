<?php

use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')
                ->constrained('users')
                ->references('id')
                ->onDelete('cascade');

            $table->foreignId('buyer_id')
                ->nullable()
                ->constrained('users')
                ->references('id')
                ->nullOnDelete();

            $table->foreignId('shipper_id')
                ->nullable()
                ->constrained('users')
                ->references('id')
                ->nullOnDelete();

            $table->unsignedTinyInteger('type')->default(Order::TYPE_CASH);
            $table->string('barcode')->unique();

            $table->decimal('total_price_before_discount', 10)->nullable();
            $table->decimal('discount', 10)->nullable();

            $table->decimal('total_price', 10)->nullable();

            $table->decimal('paid_price', 10)->nullable();
            $table->decimal('remain_price', 10)->nullable();

            $table->string('invoice')->nullable();

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
        Schema::dropIfExists('orders');
    }
}

<?php

use App\Models\Debit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDebitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('debits', function (Blueprint $table) {
            $table->id();

            $table->foreignId('creditor_id')
                ->constrained('users')
                ->references('id')
                ->onDelete('cascade');

            $table->foreignId('debtor_id')
                ->constrained('users')
                ->references('id')
                ->onDelete('cascade');

            $table->boolean('type')->default(Debit::TYPE_MERCHANT);

            $table->decimal('amount');

            $table->foreignId('order_id')
                ->nullable()
                ->constrained('orders')
                ->references('id')
                ->nullOnDelete();

            $table->foreignId('user_product_id')
                ->nullable()
                ->constrained('user_products')
                ->references('id')
                ->nullOnDelete();

            $table->foreignId('user_product_log_id')
                ->nullable()
                ->constrained('user_product_logs')
                ->references('id')
                ->nullOnDelete();

            $table->dateTime('paid_at')->nullable();

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
        Schema::dropIfExists('debits');
    }
}

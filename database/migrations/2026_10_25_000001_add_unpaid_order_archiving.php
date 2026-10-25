<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUnpaidOrderArchiving extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('unpaid_tab_archived_at')->nullable()->index();
        });

        Schema::create('unpaid_order_archives', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->nullable()->unique();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('order_barcode')->nullable();
            $table->string('currency_code', 3);
            $table->decimal('previous_paid_price', 20, 4);
            $table->decimal('previous_remain_price', 20, 4);
            $table->string('reason', 80);
            $table->string('idempotency_key', 64)->unique();
            $table->timestamp('archived_at');
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            $table->foreign('actor_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('unpaid_order_archives');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['unpaid_tab_archived_at']);
            $table->dropColumn('unpaid_tab_archived_at');
        });
    }
}

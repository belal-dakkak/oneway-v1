<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExpensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('issuer_id')
                ->constrained('users')
                ->references('id')
                ->onDelete('cascade');

            $table->foreignId('consumer_id')
                ->nullable()
                ->constrained('users')
                ->references('id')
                ->nullOnDelete();

            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->decimal('amount', 10);

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
        Schema::dropIfExists('expenses');
    }
}

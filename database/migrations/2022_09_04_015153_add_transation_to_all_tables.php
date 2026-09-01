<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTransationToAllTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('name_en')->nullable();
        });

        Schema::table('colors', function (Blueprint $table) {
            $table->string('name_en')->nullable();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('name_en')->nullable();
            $table->text('details_en')->nullable();
            $table->decimal('retail_price', 10)->nullable();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}

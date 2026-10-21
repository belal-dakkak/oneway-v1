<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProductCoversAndCountrySliders extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('cover_product_color_id')->nullable()->after('image');
            $table->foreign('cover_product_color_id')
                ->references('id')
                ->on('product_colors')
                ->nullOnDelete();
        });

        Schema::table('sliders', function (Blueprint $table) {
            $table->unsignedTinyInteger('country_id')->nullable()->after('type')->index();
        });

        Schema::table('mobile_sliders', function (Blueprint $table) {
            $table->unsignedTinyInteger('country_id')->nullable()->after('type')->index();
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['cover_product_color_id']);
            $table->dropColumn('cover_product_color_id');
        });

        Schema::table('sliders', function (Blueprint $table) {
            $table->dropColumn('country_id');
        });

        Schema::table('mobile_sliders', function (Blueprint $table) {
            $table->dropColumn('country_id');
        });
    }
}

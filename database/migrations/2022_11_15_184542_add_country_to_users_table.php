<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCountryToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('country_id')->default(User::COUNTRY_LB);
        });

        Schema::table('user_products', function (Blueprint $table) {
            $table->unsignedTinyInteger('country_id')->default(User::COUNTRY_LB);

            $table->dropUnique('user_products_product_color_id_user_id_unique');
            $table->foreignId('product_color_id')->nullable()->change();
            $table->foreignId('product_size_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('country_id');
        });
    }
}

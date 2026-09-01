<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTokenToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {

            $table->boolean('status')->default(0);
            $table->string('post_code')->nullable();

            $table->string('token')->nullable();
            $table->string('code')->nullable();
            $table->text('firebase_token')->nullable();

            $table->timestamp('mobile_verified_at')->nullable();
            $table->string('reset_token')->nullable();
            $table->enum('reset_verified',['yes','no'])->default('no');
            $table->enum('app_notification_status',['yes','no'])->default('yes');

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
            //
        });
    }
}

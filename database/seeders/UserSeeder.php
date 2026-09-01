<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::query()->firstOrCreate([
            'email' => 'admin@belalshop.com',
        ],[
            'name' => 'بلال',
            'email' => 'admin@belalshop.com',
            'password' => 'password',
            'role_id' => User::ROLE_ADMIN,
            'email_verified_at' => Carbon::now(),
            'created_at' => Carbon::now()
        ]);

        User::query()->firstOrCreate([
            'email' => 'ms@belalshop.com',
        ],[
            'name' => 'المستودع',
            'email' => 'ms@belalshop.com',
            'password' => 'password',
            'role_id' => User::ROLE_WAREHOUSE,
            'email_verified_at' => Carbon::now(),
            'created_at' => Carbon::now()
        ]);

        User::query()->firstOrCreate([
            'email' => 'm1@belalshop.com',
        ],[
            'name' => 'محل الأوزاعي',
            'email' => 'm1@belalshop.com',
            'password' => 'password',
            'role_id' => User::ROLE_SHOP,
            'email_verified_at' => Carbon::now(),
            'created_at' => Carbon::now()
        ]);

        User::query()->firstOrCreate([
            'email' => 'm2@belalshop.com',
        ],[
            'name' => 'محل بيروت',
            'email' => 'm2@belalshop.com',
            'password' => 'password',
            'role_id' => User::ROLE_SHOP,
            'email_verified_at' => Carbon::now(),
            'created_at' => Carbon::now()
        ]);

    }
}

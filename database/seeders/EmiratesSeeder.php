<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class EmiratesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        City::query()->create([
            'name' => 'Ras Al Khaimah',
            'name_ar' => 'رأس الخيمة',
        ]);

        City::query()->create([
            'name' => 'Sharjah',
            'name_ar' => 'الشارقة',
        ]);

        City::query()->create([
            'name' => 'Abu Dhabi',
            'name_ar' => 'أبو ظبي',
        ]);

        City::query()->create([
            'name' => 'Umm Al Quwain',
            'name_ar' => 'أم القيوين',
        ]);

        City::query()->create([
            'name' => 'Ajman',
            'name_ar' => 'عجمان',
        ]);

        City::query()->create([
            'name' => 'Dubai',
            'name_ar' => 'دبي',
        ]);

        City::query()->create([
            'name' => 'Fujairah',
            'name_ar' => 'الفجيرة',
        ]);
    }
}

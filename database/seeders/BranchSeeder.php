<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Branch::query()->firstOrCreate([
            'name_en' => 'Ozaai Branch',
            'name_ar' => 'فرع الأوزاعي',
            'address_en' => 'Ozaai, main street, Borj cross',
            'address_ar' => 'الأوزاعي، الشارع الرئيسي مفرق البرج',
            'latitude' => 33.877085,
            'longitude' => 35.505762
        ],[
            'name_en' => 'Ozaai Branch',
            'name_ar' => 'فرع الأوزاعي',
            'address_en' => 'Ozaai, main street, Borj cross',
            'address_ar' => 'الأوزاعي، الشارع الرئيسي مفرق البرج',
            'latitude' => 33.877085,
            'longitude' => 35.505762
        ]);

        Branch::query()->firstOrCreate([
            'name_en' => 'Sharjah Branch',
            'name_ar' => 'فرع الشارقة',
            'address_en' => 'Sharjah, main street, Mahabba Mall',
            'address_ar' => 'الشارقة، الشارع الرئيسي مول المحبة',
            'latitude' => 25.313020,
            'longitude' => 55.555068
        ],[
            'name_en' => 'Sharjah Branch',
            'name_ar' => 'فرع الشارقة',
            'address_en' => 'Sharjah, main street, Mahabba Mall',
            'address_ar' => 'الشارقة، الشارع الرئيسي مول المحبة',
            'latitude' => 25.313020,
            'longitude' => 55.555068
        ]);
    }
}

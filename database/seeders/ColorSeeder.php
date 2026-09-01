<?php

namespace Database\Seeders;

use App\Models\Color;
use Illuminate\Database\Seeder;

class ColorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Color::query()->firstOrCreate([
            'name' => 'أحمر'
        ], [
            'name' => 'أحمر',
            'code' => '#eb4034'
        ]);

        Color::query()->firstOrCreate([
            'name' => 'أزرق'
        ], [
            'name' => 'أزرق',
            'code' => '#0000ff'
        ]);

        Color::query()->firstOrCreate([
            'name' => 'موتارد'
        ], [
            'name' => 'موتارد',
            'code' => '#FFDB58'
        ]);

        Color::query()->firstOrCreate([
            'name' => 'بوردو'
        ], [
            'name' => 'بوردو',
            'code' => '#800020'
        ]);

        Color::query()->firstOrCreate([
            'name' => 'اكوا'
        ], [
            'name' => 'اكوا',
            'code' => '#00FFFF'
        ]);

        Color::query()->firstOrCreate([
            'name' => 'بريك'
        ], [
            'name' => 'بريك',
            'code' => '#43B5C3'
        ]);

        Color::query()->firstOrCreate([
            'name' => 'هافان'
        ], [
            'name' => 'هافان',
            'code' => '#3b2b2c'
        ]);

        Color::query()->firstOrCreate([
            'name' => 'أسود'
        ], [
            'name' => 'أسود',
            'code' => '#000000'
        ]);

        Color::query()->firstOrCreate([
            'name' => 'أزرق نيلي'
        ], [
            'name' => 'أزرق نيلي',
            'code' => '#4B0082'
        ]);

        Color::query()->firstOrCreate([
            'name' => 'سماوي'
        ], [
            'name' => 'سماوي',
            'code' => '#00FFFF'
        ]);

        Color::query()->firstOrCreate([
            'name' => 'أخضر'
        ], [
            'name' => 'أخضر',
            'code' => '#00ff00'
        ]);

        Color::query()->firstOrCreate([
            'name' => 'موف فاتح'
        ], [
            'name' => 'موف فاتح',
            'code' => '#d111d1'
        ]);

        Color::query()->firstOrCreate([
            'name' => 'موف غامق'
        ], [
            'name' => 'موف غامق',
            'code' => '#800080'
        ]);

        Color::query()->firstOrCreate([
            'name' => 'بيج'
        ], [
            'name' => 'بيج',
            'code' => '#F5F5DC'
        ]);

        Color::query()->firstOrCreate([
            'name' => 'عسلي'
        ], [
            'name' => 'عسلي',
            'code' => '#9c6646'
        ]);

        Color::query()->firstOrCreate([
            'name' => 'رمادي فاتح'
        ], [
            'name' => 'رمادي فاتح',
            'code' => '#B8B8B8'
        ]);

        Color::query()->firstOrCreate([
            'name' => 'رصاصي رمادي غامق'
        ], [
            'name' => 'رصاصي رمادي غامق',
            'code' => '#686868'
        ]);

        Color::query()->firstOrCreate([
            'name' => 'كحلي'
        ], [
            'name' => 'كحلي',
            'code' => '#000080'
        ]);

        Color::query()->firstOrCreate([
            'name' => 'أبيض'
        ], [
            'name' => 'أبيض',
            'code' => '#ffffff'
        ]);

        Color::query()->firstOrCreate([
            'name' => 'اوفوايت'
        ], [
            'name' => 'اوفوايت',
            'code' => '#F8F0E3'
        ]);

        Color::query()->firstOrCreate([
            'name' => 'أورانج'
        ], [
            'name' => 'أورانج',
            'code' => '#FA7000'
        ]);

        Color::query()->firstOrCreate([
            'name' => 'أصفر'
        ], [
            'name' => 'أصفر',
            'code' => '#FFF70A'
        ]);

        Color::query()->firstOrCreate([
            'name' => 'زهر'
        ], [
            'name' => 'زهر',
            'code' => '#F481B6'
        ]);

        Color::query()->firstOrCreate([
            'name' => 'فوشي'
        ], [
            'name' => 'فوشي',
            'code' => '#FF00FF'
        ]);

        Color::query()->firstOrCreate([
            'name' => 'بترولي'
        ], [
            'name' => 'بترولي',
            'code' => '#215d7d'
        ]);
    }
}

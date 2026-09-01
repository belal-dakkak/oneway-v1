<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Category::query()->firstOrCreate([
            'name' => 'طفم نسواني'
        ], [
            'name' => 'طفم نسواني'
        ]);

        Category::query()->firstOrCreate([
            'name' => 'فسطان'
        ], [
            'name' => 'فسطان'
        ]);

        Category::query()->firstOrCreate([
            'name' => 'بيجاما شرعي'
        ], [
            'name' => 'بيجاما شرعي'
        ]);


        Category::query()->firstOrCreate([
            'name' => 'بيجاما قصير'
        ], [
            'name' => 'بيجاما قصير'
        ]);

        Category::query()->firstOrCreate([
            'name' => 'بنطلون جينز'
        ], [
            'name' => 'بنطلون جينز'
        ]);

        Category::query()->firstOrCreate([
            'name' => 'بنطلون قماش'
        ], [
            'name' => 'بنطلون قماش'
        ]);

        Category::query()->firstOrCreate([
            'name' => 'بلوزة نص كم قصيرة'
        ], [
            'name' => 'بلوزة نص كم قصيرة'
        ]);

        Category::query()->firstOrCreate([
            'name' => 'بلوزة شرعية'
        ], [
            'name' => 'بلوزة شرعية'
        ]);

        Category::query()->firstOrCreate([
            'name' => 'قميص طويل'
        ], [
            'name' => 'قميص طويل'
        ]);

        Category::query()->firstOrCreate([
            'name' => 'قميص قصير'
        ], [
            'name' => 'قميص قصير'
        ]);

        Category::query()->firstOrCreate([
            'name' => 'بادي قصير'
        ], [
            'name' => 'بادي قصير'
        ]);

        Category::query()->firstOrCreate([
            'name' => 'بادي طويل'
        ], [
            'name' => 'بادي طويل'
        ]);

        Category::query()->firstOrCreate([
            'name' => 'شورت'
        ], [
            'name' => 'شورت'
        ]);
    }
}

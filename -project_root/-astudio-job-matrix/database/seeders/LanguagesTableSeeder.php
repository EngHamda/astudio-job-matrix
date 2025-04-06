<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LanguagesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = [
            'English',
            'Spanish',
            'French',
            'German',
            'Mandarin',
            'Arabic',
            'Portuguese',
            'Russian',
            'Japanese',
            'Hindi'
        ];

        foreach ($languages as $language) {
            DB::table('languages')->insertOrIgnore([
                'name' => $language,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}

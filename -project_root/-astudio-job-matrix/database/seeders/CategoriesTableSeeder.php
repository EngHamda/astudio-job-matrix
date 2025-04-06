<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Software Engineering',
            'Data Science',
            'Marketing',
            'Sales',
            'Customer Support',
            'Human Resources',
            'Finance',
            'Design',
            'Product Management',
            'Business Development'
        ];

        foreach ($categories as $category) {
            DB::table('categories')->insertOrIgnore([
                'name' => $category,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            //This sort is VIP to avoid throw exception
            LanguagesTableSeeder::class,
            LocationsTableSeeder::class,
            CategoriesTableSeeder::class,
            AttributesTableSeeder::class,
            CoreJobsTableSeeder::class,
            JobAttributeValuesTableSeeder::class
        ]);
    }
}

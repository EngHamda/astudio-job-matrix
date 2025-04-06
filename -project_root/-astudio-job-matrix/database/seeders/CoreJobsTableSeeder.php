<?php

namespace Database\Seeders;

use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Database\Seeders\Traits\JobAttributeValueTrait;


class CoreJobsTableSeeder extends Seeder
{
    use JobAttributeValueTrait;

    /**
     * Run the database seeds.
     * @throws Exception
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Fetch existing category, language, and location IDs
        $categoryIds = DB::table('categories')->pluck('id')->toArray();
        $languageIds = DB::table('languages')->pluck('id')->toArray();
        $locationIds = DB::table('locations')->pluck('id')->toArray();
        $attributeIds = DB::table('attributes')->pluck('id')->toArray();

        // If any of these arrays are empty, seed them first
        if (empty($categoryIds)) {
            $this->call(CategoriesTableSeeder::class);
            $categoryIds = DB::table('categories')->pluck('id')->toArray();
        }

        if (empty($languageIds)) {
            $this->call(LanguagesTableSeeder::class);
            $languageIds = DB::table('languages')->pluck('id')->toArray();
        }

        if (empty($locationIds)) {
            $this->call(LocationsTableSeeder::class);
            $locationIds = DB::table('locations')->pluck('id')->toArray();
        }

        if (empty($attributeIds)) {
            $this->call(AttributesTableSeeder::class);
            $attributeIds = DB::table('attributes')->pluck('id')->toArray();
        }

        // Throw an exception if still empty after seeding
        if (empty($categoryIds) || empty($languageIds) || empty($locationIds) || empty($attributeIds)) {
            throw new Exception("Unable to seed core jobs: required tables are empty");
        }

        // Create 50 sample jobs
        for ($i = 0; $i < 50; $i++) {
            $salaryMin = $faker->numberBetween(50000, 120000);
            $salaryMax = $salaryMin + $faker->numberBetween(10000, 50000);

            $jobId = DB::table('core_jobs')->insertGetId([
                'title' => $faker->jobTitle,
                'description' => $faker->paragraphs(3, true),
                'company_name' => $faker->company,
                'salary_min' => $salaryMin,
                'salary_max' => $salaryMax,
                'is_remote' => $faker->boolean(40), // 40% chance of being remote
                'job_type' => $faker->randomElement(['full-time', 'part-time', 'contract', 'freelance']),
                'status' => $faker->randomElement(['draft', 'published', 'archived']),
                'published_at' => $faker->dateTimeBetween('-1 month'),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Add random categories (ensure at least 1)
            $jobCategories = $faker->randomElements($categoryIds, max(1, $faker->numberBetween(1, 3)));
            foreach ($jobCategories as $categoryId) {
                DB::table('job_category')->insert([
                    'job_id' => $jobId,
                    'category_id' => $categoryId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            // Add random languages (ensure at least 1)
            $jobLanguages = $faker->randomElements($languageIds, max(1, $faker->numberBetween(1, 3)));
            foreach ($jobLanguages as $languageId) {
                DB::table('job_language')->insert([
                    'job_id' => $jobId,
                    'language_id' => $languageId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            // Add random locations (ensure at least 1)
            $jobLocations = $faker->randomElements($locationIds, max(1, $faker->numberBetween(1, 3)));
            foreach ($jobLocations as $locationId) {
                DB::table('job_location')->insert([
                    'job_id' => $jobId,
                    'location_id' => $locationId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            // Add some random job attributes (ensure at least 1)
            $jobAttributes = $faker->randomElements($attributeIds, max(1, $faker->numberBetween(1, 3)));
            $this->generateJobAttributeValues($jobId, $jobAttributes);
        }//endFor

    }
}

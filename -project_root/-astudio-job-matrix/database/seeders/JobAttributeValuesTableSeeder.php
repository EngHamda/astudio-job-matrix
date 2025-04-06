<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Database\Seeders\Traits\JobAttributeValueTrait;


class JobAttributeValuesTableSeeder extends Seeder
{
    use JobAttributeValueTrait;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Fetch existing job and attribute IDs
        $jobIds = DB::table('core_jobs')->pluck('id')->toArray();
        $attributeIds = DB::table('attributes')->pluck('id')->toArray();

        // Create attribute values for jobs
        foreach ($jobIds as $jobId) {
            // Randomly select some attributes for each job
            $selectedAttributes = $faker->randomElements($attributeIds, $faker->numberBetween(1, 3));

            // Use the trait method to generate and insert attribute values
            $this->generateJobAttributeValues($jobId, $selectedAttributes);
        }
    }
}

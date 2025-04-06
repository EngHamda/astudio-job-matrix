<?php

namespace Database\Seeders\Traits;

use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

trait JobAttributeValueTrait
{
    /**
     * Generate and insert job attribute values
     *
     * This section adds random dynamic job attribute values to a CoreJob.
     *
     * Steps:
     * 1. Retrieve all attribute IDs from the "attributes" table.
     * 2. Randomly select between 1 and 3 attribute IDs for the current job.
     * 3. For each selected attribute:
     *    - Retrieve the full attribute record to determine its type.
     *    - Generate a fake value based on the attribute type:
     *         - 'text'   → generate a random sentence.
     *         - 'number' → generate a random number (between 1 and 20).
     *         - 'boolean'→ generate a random boolean value.
     *         - 'date'   → generate a random date.
     *         - 'select' → pick a random option from the attribute's predefined options (decoded from JSON).
     * 4. Insert a new record into the "job_attribute_values" table with:
     *      - The current job's ID.
     *      - The attribute ID.
     *      - The generated value.
     *      - Timestamps for created_at and updated_at.
     *
     * This process ensures that each CoreJob gets a realistic set of dynamic attribute values
     * for testing and development purposes.
     *
     * @param int $jobId The ID of the job to add attribute values for
     * @param array $attributeIds The attribute IDs to generate values for
     * @return void
     */
    protected function generateJobAttributeValues(int $jobId, array $attributeIds): void
    {
        $faker = Faker::create();

        foreach ($attributeIds as $attributeId) {
            // Check if this job-attribute combination already exists
            $existingEntry = DB::table('job_attribute_values')
                ->where('job_id', $jobId)
                ->where('attribute_id', $attributeId)
                ->first();

            // Skip if the entry already exists
            if ($existingEntry) {
                continue;
            }

            $attribute = DB::table('attributes')->find($attributeId);

            $value = match ($attribute->type) {
                'text' => $faker->sentence,
                'number' => $faker->numberBetween(1, 20),
                'boolean' => $faker->boolean ? 'true' : 'false',
                'date' => $faker->date,
                'select' => $faker->randomElement(json_decode($attribute->options) ?? []),
                default => null
            };

            DB::table('job_attribute_values')->insert([
                'job_id' => $jobId,
                'attribute_id' => $attributeId,
                'value' => $value,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}

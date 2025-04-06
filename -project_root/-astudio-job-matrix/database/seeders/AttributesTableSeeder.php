<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttributesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $attributes = [
            [
                'name' => 'Experience  Level',
                'type' => 'select',
                'options' => json_encode(['Entry Level', 'Mid Level', 'Senior Level', 'Executive']),
                'is_required' => true
            ],
            [
                'name' => 'Work Authorization',
                'type' => 'select',
                'options' => json_encode(['Citizen', 'Permanent Resident', 'Work Visa', 'Other']),
                'is_required' => true
            ],
            [
                'name' => 'Education Level',
                'type' => 'select',
                'options' => json_encode(['High School', 'Bachelor\'s', 'Master\'s', 'PhD', 'Other']),
                'is_required' => false
            ],
            [
                'name' => 'Relocation Possible',
                'type' => 'boolean',
                'options' => null,
                'is_required' => false
            ],
            [
                'name' => 'Years of Experience',
                'type' => 'number',
                'options' => null,
                'is_required' => false
            ]
        ];

        foreach ($attributes as $attribute) {
            DB::table('attributes')->insertOrIgnore([
                'name' => $attribute['name'],
                'type' => $attribute['type'],
                'options' => $attribute['options'],
                'is_required' => $attribute['is_required'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}

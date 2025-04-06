<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            ['city' => 'San Francisco', 'state' => 'CA', 'country' => 'United States'],
            ['city' => 'New York', 'state' => 'NY', 'country' => 'United States'],
            ['city' => 'London', 'state' => null, 'country' => 'United Kingdom'],
            ['city' => 'Berlin', 'state' => null, 'country' => 'Germany'],
            ['city' => 'Toronto', 'state' => 'ON', 'country' => 'Canada'],
            ['city' => 'Sydney', 'state' => 'NSW', 'country' => 'Australia'],
            ['city' => 'Singapore', 'state' => null, 'country' => 'Singapore'],
            ['city' => 'Paris', 'state' => null, 'country' => 'France'],
            ['city' => 'Tokyo', 'state' => null, 'country' => 'Japan'],
            ['city' => 'Tel Aviv', 'state' => null, 'country' => 'Israel']
        ];

        foreach ($locations as $location) {
            DB::table('locations')->insertOrIgnore([
                'city' => $location['city'],
                'state' => $location['state'],
                'country' => $location['country'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}

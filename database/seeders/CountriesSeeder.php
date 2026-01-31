<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountriesSeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['code' => 'UAE', 'name' => 'United Arab Emirates'],
            ['code' => 'US',  'name' => 'United States'],
            ['code' => 'UK',  'name' => 'United Kingdom'],
            ['code' => 'CA',  'name' => 'Canada'],
            ['code' => 'AU',  'name' => 'Australia'],
        ];

        foreach ($countries as $c) {
            Country::updateOrCreate(
                ['code' => $c['code']],
                ['name' => $c['name'], 'is_active' => true]
            );
        }
    }
}


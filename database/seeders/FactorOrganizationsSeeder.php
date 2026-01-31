<?php

namespace Database\Seeders;

use App\Models\FactorOrganization;
use Illuminate\Database\Seeder;

class FactorOrganizationsSeeder extends Seeder
{
    public function run(): void
    {
        $orgs = [
            ['code' => 'IPCC',  'name' => 'IPCC (Intergovernmental Panel on Climate Change)', 'url' => 'https://www.ipcc.ch/'],
            ['code' => 'DEFRA', 'name' => 'DEFRA (UK Government GHG Conversion Factors)', 'url' => 'https://www.gov.uk/government/collections/government-conversion-factors-for-company-reporting'],
            ['code' => 'EPA',   'name' => 'US EPA (Environmental Protection Agency)', 'url' => 'https://www.epa.gov/'],
            ['code' => 'COUNTRY', 'name' => 'Country-specific (select country below)', 'url' => null],
        ];

        foreach ($orgs as $org) {
            FactorOrganization::updateOrCreate(
                ['code' => $org['code']],
                ['name' => $org['name'], 'url' => $org['url'] ?? '']
            );
        }
    }
}


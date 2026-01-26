<?php

namespace Database\Seeders;

use App\Models\EmissionSource;
use Illuminate\Database\Seeder;

class EmissionSourcesSeeder extends Seeder
{
    public function run(): void
    {
        /**
         * Starter library of common emission sources.
         * Keep names stable because data-entry dropdowns use the source name as the value.
         */
        $sources = [
            // -----------------------------
            // Scope 1: Stationary combustion
            // -----------------------------
            ['name' => 'Natural Gas Combustion', 'scope' => 1],
            ['name' => 'Diesel (Stationary Combustion)', 'scope' => 1],
            ['name' => 'Gasoline (Stationary Combustion)', 'scope' => 1],
            ['name' => 'LPG / Propane Combustion', 'scope' => 1],
            ['name' => 'Fuel Oil (Heating Oil) Combustion', 'scope' => 1],
            ['name' => 'Coal Combustion', 'scope' => 1],
            ['name' => 'Biomass Combustion', 'scope' => 1],

            // -----------------------------
            // Scope 1: Mobile combustion
            // -----------------------------
            ['name' => 'Company Fleet - Gasoline', 'scope' => 1],
            ['name' => 'Company Fleet - Diesel', 'scope' => 1],
            ['name' => 'Company Fleet - CNG', 'scope' => 1],

            // -----------------------------
            // Scope 1: Fugitive emissions
            // -----------------------------
            ['name' => 'Refrigerant Leakage (HFCs)', 'scope' => 1],
            ['name' => 'Fire Suppression (HFCs)', 'scope' => 1],

            // -----------------------------
            // Scope 2: Purchased energy
            // -----------------------------
            ['name' => 'Purchased Electricity (Location-based)', 'scope' => 2],
            ['name' => 'Purchased Electricity (Market-based)', 'scope' => 2],
            ['name' => 'Purchased Steam', 'scope' => 2],
            ['name' => 'District Heating', 'scope' => 2],
            ['name' => 'District Cooling', 'scope' => 2],

            // -----------------------------
            // Scope 3: GHG Protocol categories (15)
            // -----------------------------
            ['name' => 'Scope 3 - 1. Purchased Goods & Services', 'scope' => 3],
            ['name' => 'Scope 3 - 2. Capital Goods', 'scope' => 3],
            ['name' => 'Scope 3 - 3. Fuel & Energy Related Activities', 'scope' => 3],
            ['name' => 'Scope 3 - 4. Upstream Transportation & Distribution', 'scope' => 3],
            ['name' => 'Scope 3 - 5. Waste Generated in Operations', 'scope' => 3],
            ['name' => 'Scope 3 - 6. Business Travel', 'scope' => 3],
            ['name' => 'Scope 3 - 7. Employee Commuting', 'scope' => 3],
            ['name' => 'Scope 3 - 8. Upstream Leased Assets', 'scope' => 3],
            ['name' => 'Scope 3 - 9. Downstream Transportation & Distribution', 'scope' => 3],
            ['name' => 'Scope 3 - 10. Processing of Sold Products', 'scope' => 3],
            ['name' => 'Scope 3 - 11. Use of Sold Products', 'scope' => 3],
            ['name' => 'Scope 3 - 12. End-of-Life Treatment of Sold Products', 'scope' => 3],
            ['name' => 'Scope 3 - 13. Downstream Leased Assets', 'scope' => 3],
            ['name' => 'Scope 3 - 14. Franchises', 'scope' => 3],
            ['name' => 'Scope 3 - 15. Investments', 'scope' => 3],
        ];

        foreach ($sources as $source) {
            EmissionSource::updateOrCreate(
                ['name' => $source['name']],
                [
                    'scope' => $source['scope'],
                    'description' => $source['description'] ?? null,
                ]
            );
        }
    }
}


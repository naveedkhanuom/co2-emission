<?php

namespace Database\Seeders;

use App\Models\EmissionFactor;
use App\Models\EmissionSource;
use Illuminate\Database\Seeder;

class EmissionFactorsSeeder extends Seeder
{
    public function run(): void
    {
        /**
         * NOTE:
         * These are starter/default factors ONLY.
         * Replace with your official factors for your geography and reporting standard.
         *
         * factor_value unit is tCO2e per unit entered in `unit`.
         */
        $defaults = [
            // -----------------------------
            // Scope 1: Stationary combustion (starter factors)
            // -----------------------------
            ['source' => 'Natural Gas Combustion', 'unit' => 'm³', 'factor_value' => 0.001900, 'region' => 'default'],
            ['source' => 'Diesel (Stationary Combustion)', 'unit' => 'liters', 'factor_value' => 0.002680, 'region' => 'default'],
            ['source' => 'Gasoline (Stationary Combustion)', 'unit' => 'liters', 'factor_value' => 0.002310, 'region' => 'default'],
            ['source' => 'LPG / Propane Combustion', 'unit' => 'liters', 'factor_value' => 0.001510, 'region' => 'default'],
            ['source' => 'Fuel Oil (Heating Oil) Combustion', 'unit' => 'liters', 'factor_value' => 0.003170, 'region' => 'default'],
            ['source' => 'Coal Combustion', 'unit' => 'kg', 'factor_value' => 0.002420, 'region' => 'default'],
            ['source' => 'Biomass Combustion', 'unit' => 'kg', 'factor_value' => 0.000000, 'region' => 'default'],

            // -----------------------------
            // Scope 1: Mobile combustion (starter factors)
            // -----------------------------
            ['source' => 'Company Fleet - Gasoline', 'unit' => 'liters', 'factor_value' => 0.002310, 'region' => 'default'],
            ['source' => 'Company Fleet - Diesel', 'unit' => 'liters', 'factor_value' => 0.002680, 'region' => 'default'],
            ['source' => 'Company Fleet - CNG', 'unit' => 'm³', 'factor_value' => 0.002000, 'region' => 'default'],

            // -----------------------------
            // Scope 1: Fugitive (placeholders - depends heavily on refrigerant type/GWP)
            // -----------------------------
            ['source' => 'Refrigerant Leakage (HFCs)', 'unit' => 'kg', 'factor_value' => 1.000000, 'region' => 'placeholder'],
            ['source' => 'Fire Suppression (HFCs)', 'unit' => 'kg', 'factor_value' => 1.000000, 'region' => 'placeholder'],

            // -----------------------------
            // Scope 2: Purchased energy (starter placeholders)
            // -----------------------------
            ['source' => 'Purchased Electricity (Location-based)', 'unit' => 'kWh', 'factor_value' => 0.000500, 'region' => 'default'],
            ['source' => 'Purchased Electricity (Market-based)', 'unit' => 'kWh', 'factor_value' => 0.000400, 'region' => 'default'],
            ['source' => 'Purchased Steam', 'unit' => 'MJ', 'factor_value' => 0.000070, 'region' => 'default'],
            ['source' => 'District Heating', 'unit' => 'kWh', 'factor_value' => 0.000250, 'region' => 'default'],
            ['source' => 'District Cooling', 'unit' => 'kWh', 'factor_value' => 0.000180, 'region' => 'default'],

            // -----------------------------
            // Scope 3: Category placeholders (set to 0; use spend-based/EIO or supplier-specific factors)
            // -----------------------------
            ['source' => 'Scope 3 - 1. Purchased Goods & Services', 'unit' => 'unit', 'factor_value' => 0.000000, 'region' => 'placeholder'],
            ['source' => 'Scope 3 - 2. Capital Goods', 'unit' => 'unit', 'factor_value' => 0.000000, 'region' => 'placeholder'],
            ['source' => 'Scope 3 - 3. Fuel & Energy Related Activities', 'unit' => 'unit', 'factor_value' => 0.000000, 'region' => 'placeholder'],
            ['source' => 'Scope 3 - 4. Upstream Transportation & Distribution', 'unit' => 'ton-km', 'factor_value' => 0.000060, 'region' => 'placeholder'],
            ['source' => 'Scope 3 - 5. Waste Generated in Operations', 'unit' => 'kg', 'factor_value' => 0.000500, 'region' => 'placeholder'],
            ['source' => 'Scope 3 - 6. Business Travel', 'unit' => 'km', 'factor_value' => 0.000180, 'region' => 'placeholder'],
            ['source' => 'Scope 3 - 7. Employee Commuting', 'unit' => 'km', 'factor_value' => 0.000120, 'region' => 'placeholder'],
            ['source' => 'Scope 3 - 8. Upstream Leased Assets', 'unit' => 'm²', 'factor_value' => 0.000000, 'region' => 'placeholder'],
            ['source' => 'Scope 3 - 9. Downstream Transportation & Distribution', 'unit' => 'ton-km', 'factor_value' => 0.000060, 'region' => 'placeholder'],
            ['source' => 'Scope 3 - 10. Processing of Sold Products', 'unit' => 'unit', 'factor_value' => 0.000000, 'region' => 'placeholder'],
            ['source' => 'Scope 3 - 11. Use of Sold Products', 'unit' => 'unit', 'factor_value' => 0.000000, 'region' => 'placeholder'],
            ['source' => 'Scope 3 - 12. End-of-Life Treatment of Sold Products', 'unit' => 'kg', 'factor_value' => 0.000000, 'region' => 'placeholder'],
            ['source' => 'Scope 3 - 13. Downstream Leased Assets', 'unit' => 'm²', 'factor_value' => 0.000000, 'region' => 'placeholder'],
            ['source' => 'Scope 3 - 14. Franchises', 'unit' => 'unit', 'factor_value' => 0.000000, 'region' => 'placeholder'],
            ['source' => 'Scope 3 - 15. Investments', 'unit' => 'unit', 'factor_value' => 0.000000, 'region' => 'placeholder'],
        ];

        foreach ($defaults as $row) {
            $source = EmissionSource::where('name', $row['source'])->first();
            if (!$source) {
                continue;
            }

            EmissionFactor::updateOrCreate(
                [
                    'emission_source_id' => $source->id,
                    'unit' => $row['unit'],
                    'region' => $row['region'] ?? null,
                ],
                [
                    'factor_value' => $row['factor_value'],
                ]
            );
        }
    }
}


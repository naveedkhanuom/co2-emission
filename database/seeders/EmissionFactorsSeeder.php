<?php

namespace Database\Seeders;

use App\Models\EmissionFactor;
use App\Models\EmissionSource;
use App\Models\FactorOrganization;
use Illuminate\Database\Seeder;

class EmissionFactorsSeeder extends Seeder
{
    public function run(): void
    {
        /**
         * Complete A–Z emission factors (tCO2e per unit).
         * Default organization: IPCC. Replace with official factors for your geography/reporting standard.
         */
        $defaultOrgId = FactorOrganization::where('code', 'IPCC')->value('id');
        if (!$defaultOrgId) {
            return;
        }

        $factors = [
            // =============================
            // SCOPE 1 — Stationary combustion (tCO2e per unit)
            // =============================
            ['source' => 'Biomass Combustion', 'unit' => 'kg', 'factor_value' => 0.000000, 'region' => 'default'],
            ['source' => 'Coal Combustion', 'unit' => 'kg', 'factor_value' => 0.002420, 'region' => 'default'],
            ['source' => 'Diesel (Stationary Combustion)', 'unit' => 'liters', 'factor_value' => 0.002680, 'region' => 'default'],
            ['source' => 'Fuel Oil (Heating Oil) Combustion', 'unit' => 'liters', 'factor_value' => 0.003170, 'region' => 'default'],
            ['source' => 'Gasoline (Stationary Combustion)', 'unit' => 'liters', 'factor_value' => 0.002310, 'region' => 'default'],
            ['source' => 'Kerosene Combustion', 'unit' => 'liters', 'factor_value' => 0.002520, 'region' => 'default'],
            ['source' => 'LPG / Propane Combustion', 'unit' => 'liters', 'factor_value' => 0.001510, 'region' => 'default'],
            ['source' => 'Natural Gas Combustion', 'unit' => 'm³', 'factor_value' => 0.001900, 'region' => 'default'],
            ['source' => 'Wood / Wood Pellets Combustion', 'unit' => 'kg', 'factor_value' => 0.000000, 'region' => 'default'],

            // =============================
            // SCOPE 1 — Mobile combustion
            // =============================
            ['source' => 'Aviation (Company-owned) - Jet Fuel', 'unit' => 'liters', 'factor_value' => 0.002520, 'region' => 'default'],
            ['source' => 'Company Fleet - Biodiesel', 'unit' => 'liters', 'factor_value' => 0.000000, 'region' => 'default'],
            ['source' => 'Company Fleet - CNG', 'unit' => 'm³', 'factor_value' => 0.002000, 'region' => 'default'],
            ['source' => 'Company Fleet - Diesel', 'unit' => 'liters', 'factor_value' => 0.002680, 'region' => 'default'],
            ['source' => 'Company Fleet - Gasoline', 'unit' => 'liters', 'factor_value' => 0.002310, 'region' => 'default'],
            ['source' => 'Company Fleet - LPG', 'unit' => 'liters', 'factor_value' => 0.001510, 'region' => 'default'],
            ['source' => 'Marine / Marine Vessels - Diesel', 'unit' => 'liters', 'factor_value' => 0.002680, 'region' => 'default'],
            ['source' => 'Marine / Marine Vessels - Heavy Fuel Oil', 'unit' => 'liters', 'factor_value' => 0.003210, 'region' => 'default'],
            ['source' => 'Off-road Machinery - Diesel', 'unit' => 'liters', 'factor_value' => 0.002680, 'region' => 'default'],
            ['source' => 'Rail (Company-owned) - Diesel', 'unit' => 'liters', 'factor_value' => 0.002680, 'region' => 'default'],

            // =============================
            // SCOPE 1 — Fugitive (GWP-based; placeholders where needed)
            // =============================
            ['source' => 'Fire Suppression (Halon)', 'unit' => 'kg', 'factor_value' => 9.000000, 'region' => 'placeholder'],
            ['source' => 'Fire Suppression (HFCs)', 'unit' => 'kg', 'factor_value' => 1.430000, 'region' => 'placeholder'],
            ['source' => 'Methane Leakage (Gas Systems)', 'unit' => 'kg', 'factor_value' => 0.028000, 'region' => 'default'],
            ['source' => 'N2O from Industrial Processes', 'unit' => 'kg', 'factor_value' => 0.298000, 'region' => 'default'],
            ['source' => 'PFCs (Aluminium / Semiconductors)', 'unit' => 'kg', 'factor_value' => 8.000000, 'region' => 'placeholder'],
            ['source' => 'Refrigerant Leakage (CFCs)', 'unit' => 'kg', 'factor_value' => 4.750000, 'region' => 'placeholder'],
            ['source' => 'Refrigerant Leakage (HCFCs)', 'unit' => 'kg', 'factor_value' => 2.000000, 'region' => 'placeholder'],
            ['source' => 'Refrigerant Leakage (HFCs)', 'unit' => 'kg', 'factor_value' => 2.088000, 'region' => 'placeholder'],
            ['source' => 'Refrigerant Leakage (HFOs / Natural)', 'unit' => 'kg', 'factor_value' => 0.001000, 'region' => 'placeholder'],
            ['source' => 'SF6 (Electrical Equipment)', 'unit' => 'kg', 'factor_value' => 23.500000, 'region' => 'default'],

            // =============================
            // SCOPE 2 — Purchased energy
            // =============================
            ['source' => 'CHP - Electricity', 'unit' => 'kWh', 'factor_value' => 0.000400, 'region' => 'default'],
            ['source' => 'CHP - Heat', 'unit' => 'MJ', 'factor_value' => 0.000060, 'region' => 'default'],
            ['source' => 'District Cooling', 'unit' => 'kWh', 'factor_value' => 0.000180, 'region' => 'default'],
            ['source' => 'District Heating', 'unit' => 'kWh', 'factor_value' => 0.000250, 'region' => 'default'],
            ['source' => 'Purchased Electricity (Location-based)', 'unit' => 'kWh', 'factor_value' => 0.000500, 'region' => 'default'],
            ['source' => 'Purchased Electricity (Market-based)', 'unit' => 'kWh', 'factor_value' => 0.000400, 'region' => 'default'],
            ['source' => 'Purchased Hot Water', 'unit' => 'm³', 'factor_value' => 0.000080, 'region' => 'default'],
            ['source' => 'Purchased Steam', 'unit' => 'MJ', 'factor_value' => 0.000070, 'region' => 'default'],

            // =============================
            // SCOPE 3 — All 15 categories (activity or spend-based placeholders)
            // =============================
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

        foreach ($factors as $row) {
            $source = EmissionSource::where('name', $row['source'])->first();
            if (!$source) {
                continue;
            }

            EmissionFactor::updateOrCreate(
                [
                    'emission_source_id' => $source->id,
                    'organization_id' => $defaultOrgId,
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

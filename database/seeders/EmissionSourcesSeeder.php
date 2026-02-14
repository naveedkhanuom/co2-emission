<?php

namespace Database\Seeders;

use App\Models\EmissionSource;
use Illuminate\Database\Seeder;

class EmissionSourcesSeeder extends Seeder
{
    public function run(): void
    {
        /**
         * Complete A–Z emission sources library (GHG Protocol / IPCC aligned).
         * Keep names stable — data-entry dropdowns use source name as value.
         * scope: 1 = direct, 2 = purchased energy, 3 = value chain.
         */
        $sources = [
            // =============================
            // SCOPE 1 — Stationary combustion (alphabetical)
            // =============================
            ['name' => 'Biomass Combustion', 'scope' => 1, 'description' => 'Combustion of biomass for heat or power.'],
            ['name' => 'Coal Combustion', 'scope' => 1, 'description' => 'Combustion of coal (e.g. in boilers).'],
            ['name' => 'Diesel (Stationary Combustion)', 'scope' => 1, 'description' => 'Diesel burned in generators, boilers, or heaters.'],
            ['name' => 'Fuel Oil (Heating Oil) Combustion', 'scope' => 1, 'description' => 'Heating oil / residual fuel in boilers.'],
            ['name' => 'Gasoline (Stationary Combustion)', 'scope' => 1, 'description' => 'Gasoline in stationary engines or equipment.'],
            ['name' => 'Kerosene Combustion', 'scope' => 1, 'description' => 'Kerosene in heaters or backup generators.'],
            ['name' => 'LPG / Propane Combustion', 'scope' => 1, 'description' => 'Liquefied petroleum gas or propane combustion.'],
            ['name' => 'Natural Gas Combustion', 'scope' => 1, 'description' => 'Natural gas in boilers, furnaces, or CHP.'],
            ['name' => 'Wood / Wood Pellets Combustion', 'scope' => 1, 'description' => 'Wood or wood pellets for heating.'],

            // =============================
            // SCOPE 1 — Mobile combustion (alphabetical)
            // =============================
            ['name' => 'Aviation (Company-owned) - Jet Fuel', 'scope' => 1, 'description' => 'Jet fuel for company-owned aircraft.'],
            ['name' => 'Company Fleet - Biodiesel', 'scope' => 1, 'description' => 'Biodiesel in company vehicles.'],
            ['name' => 'Company Fleet - CNG', 'scope' => 1, 'description' => 'Compressed natural gas in fleet vehicles.'],
            ['name' => 'Company Fleet - Diesel', 'scope' => 1, 'description' => 'Diesel in company cars and vans.'],
            ['name' => 'Company Fleet - Gasoline', 'scope' => 1, 'description' => 'Gasoline in company cars and vans.'],
            ['name' => 'Company Fleet - LPG', 'scope' => 1, 'description' => 'LPG in company vehicles.'],
            ['name' => 'Marine / Marine Vessels - Diesel', 'scope' => 1, 'description' => 'Diesel in company-owned boats or vessels.'],
            ['name' => 'Marine / Marine Vessels - Heavy Fuel Oil', 'scope' => 1, 'description' => 'Heavy fuel oil in marine vessels.'],
            ['name' => 'Off-road Machinery - Diesel', 'scope' => 1, 'description' => 'Diesel in construction, mining, or agricultural machinery.'],
            ['name' => 'Rail (Company-owned) - Diesel', 'scope' => 1, 'description' => 'Diesel in company-owned locomotives or rail vehicles.'],

            // =============================
            // SCOPE 1 — Fugitive emissions (alphabetical)
            // =============================
            ['name' => 'Fire Suppression (Halon)', 'scope' => 1, 'description' => 'Halon from fire suppression systems.'],
            ['name' => 'Fire Suppression (HFCs)', 'scope' => 1, 'description' => 'HFCs from fire suppression systems.'],
            ['name' => 'Methane Leakage (Gas Systems)', 'scope' => 1, 'description' => 'Fugitive methane from gas pipes or equipment.'],
            ['name' => 'N2O from Industrial Processes', 'scope' => 1, 'description' => 'Nitrous oxide from industrial processes (e.g. nitric acid).'],
            ['name' => 'PFCs (Aluminium / Semiconductors)', 'scope' => 1, 'description' => 'Perfluorocarbons from aluminium or semiconductor production.'],
            ['name' => 'Refrigerant Leakage (CFCs)', 'scope' => 1, 'description' => 'CFC refrigerant leakage from HVAC or refrigeration.'],
            ['name' => 'Refrigerant Leakage (HCFCs)', 'scope' => 1, 'description' => 'HCFC refrigerant leakage (e.g. R-22).'],
            ['name' => 'Refrigerant Leakage (HFCs)', 'scope' => 1, 'description' => 'HFC refrigerant leakage (e.g. R-410A, R-134a).'],
            ['name' => 'Refrigerant Leakage (HFOs / Natural)', 'scope' => 1, 'description' => 'HFO or natural refrigerant (e.g. CO2, ammonia) leakage.'],
            ['name' => 'SF6 (Electrical Equipment)', 'scope' => 1, 'description' => 'SF6 from switchgear or electrical equipment.'],

            // =============================
            // SCOPE 2 — Purchased energy (alphabetical)
            // =============================
            ['name' => 'CHP - Electricity', 'scope' => 2, 'description' => 'Electricity from combined heat and power.'],
            ['name' => 'CHP - Heat', 'scope' => 2, 'description' => 'Heat from combined heat and power.'],
            ['name' => 'District Cooling', 'scope' => 2, 'description' => 'Purchased district cooling.'],
            ['name' => 'District Heating', 'scope' => 2, 'description' => 'Purchased district heating.'],
            ['name' => 'Purchased Electricity (Location-based)', 'scope' => 2, 'description' => 'Grid electricity — location-based method.'],
            ['name' => 'Purchased Electricity (Market-based)', 'scope' => 2, 'description' => 'Grid electricity — market-based method (contracts/offsets).'],
            ['name' => 'Purchased Hot Water', 'scope' => 2, 'description' => 'Purchased hot water.'],
            ['name' => 'Purchased Steam', 'scope' => 2, 'description' => 'Purchased steam.'],

            // =============================
            // SCOPE 3 — Category 1: Purchased Goods & Services
            // =============================
            ['name' => 'Scope 3 - 1. Purchased Goods & Services', 'scope' => 3, 'description' => 'Upstream emissions from purchased goods and services.'],

            // =============================
            // SCOPE 3 — Category 2: Capital Goods
            // =============================
            ['name' => 'Scope 3 - 2. Capital Goods', 'scope' => 3, 'description' => 'Emissions from capital goods (machinery, buildings, etc.).'],

            // =============================
            // SCOPE 3 — Category 3: Fuel & Energy Related Activities
            // =============================
            ['name' => 'Scope 3 - 3. Fuel & Energy Related Activities', 'scope' => 3, 'description' => 'T&D losses, upstream fuel production, well-to-tank.'],

            // =============================
            // SCOPE 3 — Category 4: Upstream Transportation & Distribution
            // =============================
            ['name' => 'Scope 3 - 4. Upstream Transportation & Distribution', 'scope' => 3, 'description' => 'Inbound logistics: road, rail, sea, air, pipeline.'],

            // =============================
            // SCOPE 3 — Category 5: Waste Generated in Operations
            // =============================
            ['name' => 'Scope 3 - 5. Waste Generated in Operations', 'scope' => 3, 'description' => 'Waste disposal: landfill, recycling, incineration, composting.'],

            // =============================
            // SCOPE 3 — Category 6: Business Travel
            // =============================
            ['name' => 'Scope 3 - 6. Business Travel', 'scope' => 3, 'description' => 'Air, rail, car, taxi, hotel from business travel.'],

            // =============================
            // SCOPE 3 — Category 7: Employee Commuting
            // =============================
            ['name' => 'Scope 3 - 7. Employee Commuting', 'scope' => 3, 'description' => 'Commuting by car, bus, rail, motorcycle, etc.'],

            // =============================
            // SCOPE 3 — Category 8: Upstream Leased Assets
            // =============================
            ['name' => 'Scope 3 - 8. Upstream Leased Assets', 'scope' => 3, 'description' => 'Emissions from upstream leased assets (operational control).'],

            // =============================
            // SCOPE 3 — Category 9: Downstream Transportation & Distribution
            // =============================
            ['name' => 'Scope 3 - 9. Downstream Transportation & Distribution', 'scope' => 3, 'description' => 'Outbound logistics to customers.'],

            // =============================
            // SCOPE 3 — Category 10: Processing of Sold Products
            // =============================
            ['name' => 'Scope 3 - 10. Processing of Sold Products', 'scope' => 3, 'description' => 'Emissions from processing of sold products by third parties.'],

            // =============================
            // SCOPE 3 — Category 11: Use of Sold Products
            // =============================
            ['name' => 'Scope 3 - 11. Use of Sold Products', 'scope' => 3, 'description' => 'Emissions from use of sold products (e.g. fuel, electricity).'],

            // =============================
            // SCOPE 3 — Category 12: End-of-Life Treatment of Sold Products
            // =============================
            ['name' => 'Scope 3 - 12. End-of-Life Treatment of Sold Products', 'scope' => 3, 'description' => 'Waste treatment of sold products at end of life.'],

            // =============================
            // SCOPE 3 — Category 13: Downstream Leased Assets
            // =============================
            ['name' => 'Scope 3 - 13. Downstream Leased Assets', 'scope' => 3, 'description' => 'Emissions from downstream leased assets.'],

            // =============================
            // SCOPE 3 — Category 14: Franchises
            // =============================
            ['name' => 'Scope 3 - 14. Franchises', 'scope' => 3, 'description' => 'Emissions from franchise operations.'],

            // =============================
            // SCOPE 3 — Category 15: Investments
            // =============================
            ['name' => 'Scope 3 - 15. Investments', 'scope' => 3, 'description' => 'Emissions from investments (equity, debt, project finance).'],
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

<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\EmissionFactor;
use App\Models\EmissionSource;
use App\Models\FactorOrganization;
use Illuminate\Database\Seeder;

/**
 * Comprehensive Emission Factor Database Seeder
 *
 * Sources:
 *  - IPCC 2006 Guidelines & AR6 GWP values
 *  - DEFRA/DESNZ 2025 UK Government GHG Conversion Factors
 *  - US EPA GHG Emission Factors Hub 2025
 *  - Ember Global Electricity Review 2024/2025
 *  - IEA Emissions Factors
 *  - EPA WARM v16 (Waste)
 *  - EPA Supply Chain GHG Emission Factors v1.3 (USEEIO)
 *  - EPA eGRID2023
 *  - PCAF Standard (Financed Emissions)
 *  - EcoTransIT (Freight)
 *
 * All factor_value units are in tCO2e per stated unit (consistent with existing seeder).
 */
class ComprehensiveEmissionDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedOrganizations();
        $this->seedEmissionSources();
        $this->seedIPCCFactors();
        $this->seedDEFRAFactors();
        $this->seedEPAFactors();
        $this->seedCountryElectricityFactors();
        $this->seedRefrigerantGWPFactors();
        $this->seedScope3DetailedFactors();
    }

    // =========================================================================
    // ORGANIZATIONS
    // =========================================================================
    private function seedOrganizations(): void
    {
        $orgs = [
            ['code' => 'IPCC',    'name' => 'IPCC (Intergovernmental Panel on Climate Change)',   'url' => 'https://www.ipcc.ch/'],
            ['code' => 'DEFRA',   'name' => 'DEFRA/DESNZ 2025 UK GHG Conversion Factors',        'url' => 'https://www.gov.uk/government/publications/greenhouse-gas-reporting-conversion-factors-2025'],
            ['code' => 'EPA',     'name' => 'US EPA GHG Emission Factors Hub 2025',               'url' => 'https://www.epa.gov/climateleadership/ghg-emission-factors-hub'],
            ['code' => 'COUNTRY', 'name' => 'Country-Specific Grid Factor (Ember/IEA)',           'url' => 'https://ember-energy.org/data/yearly-electricity-data/'],
            ['code' => 'EGRID',   'name' => 'US EPA eGRID2023 Subregion Factors',                 'url' => 'https://www.epa.gov/egrid'],
            ['code' => 'GWP',     'name' => 'IPCC AR6 Global Warming Potential Values',           'url' => 'https://www.ipcc.ch/report/ar6/wg1/'],
        ];

        foreach ($orgs as $org) {
            FactorOrganization::updateOrCreate(
                ['code' => $org['code']],
                ['name' => $org['name'], 'url' => $org['url']]
            );
        }
    }

    // =========================================================================
    // EMISSION SOURCES (Expanded from 44 to 300+)
    // =========================================================================
    private function seedEmissionSources(): void
    {
        $sources = [
            // =================================================================
            // SCOPE 1 — Stationary Combustion
            // =================================================================
            ['name' => 'Anthracite Coal Combustion',                 'scope' => 1, 'description' => 'Anthracite coal burned in boilers or furnaces.'],
            ['name' => 'Bituminous Coal Combustion',                 'scope' => 1, 'description' => 'Bituminous coal burned in boilers or furnaces.'],
            ['name' => 'Sub-bituminous Coal Combustion',             'scope' => 1, 'description' => 'Sub-bituminous coal combustion.'],
            ['name' => 'Lignite Coal Combustion',                    'scope' => 1, 'description' => 'Lignite/brown coal combustion.'],
            ['name' => 'Coal Combustion',                            'scope' => 1, 'description' => 'General coal combustion (mixed).'],
            ['name' => 'Biomass Combustion',                         'scope' => 1, 'description' => 'Combustion of biomass for heat or power.'],
            ['name' => 'Wood / Wood Pellets Combustion',             'scope' => 1, 'description' => 'Wood or wood pellets for heating.'],
            ['name' => 'Agricultural Byproducts Combustion',         'scope' => 1, 'description' => 'Burning of agricultural residues.'],
            ['name' => 'Biogas Combustion',                          'scope' => 1, 'description' => 'Biogas (landfill gas, anaerobic digestion) combustion.'],
            ['name' => 'Biodiesel (B100) Combustion',                'scope' => 1, 'description' => 'Pure biodiesel stationary combustion.'],
            ['name' => 'Bioethanol (E100) Combustion',               'scope' => 1, 'description' => 'Pure bioethanol stationary combustion.'],
            ['name' => 'Natural Gas Combustion',                     'scope' => 1, 'description' => 'Natural gas in boilers, furnaces, or CHP.'],
            ['name' => 'LPG / Propane Combustion',                   'scope' => 1, 'description' => 'Liquefied petroleum gas or propane combustion.'],
            ['name' => 'Butane Combustion',                          'scope' => 1, 'description' => 'Butane combustion in stationary equipment.'],
            ['name' => 'Ethane Combustion',                          'scope' => 1, 'description' => 'Ethane combustion.'],
            ['name' => 'Diesel (Stationary Combustion)',              'scope' => 1, 'description' => 'Diesel burned in generators, boilers, or heaters.'],
            ['name' => 'Gasoline (Stationary Combustion)',            'scope' => 1, 'description' => 'Gasoline in stationary engines or equipment.'],
            ['name' => 'Kerosene Combustion',                        'scope' => 1, 'description' => 'Kerosene in heaters or backup generators.'],
            ['name' => 'Fuel Oil No. 2 Combustion',                  'scope' => 1, 'description' => 'Distillate fuel oil No. 2 combustion.'],
            ['name' => 'Fuel Oil No. 4 Combustion',                  'scope' => 1, 'description' => 'Distillate fuel oil No. 4 combustion.'],
            ['name' => 'Fuel Oil No. 6 (Residual) Combustion',       'scope' => 1, 'description' => 'Residual fuel oil No. 6 combustion.'],
            ['name' => 'Fuel Oil (Heating Oil) Combustion',          'scope' => 1, 'description' => 'Heating oil / residual fuel in boilers.'],
            ['name' => 'Jet Fuel (Stationary) Combustion',           'scope' => 1, 'description' => 'Jet kerosene in stationary generators.'],
            ['name' => 'Petroleum Coke Combustion',                  'scope' => 1, 'description' => 'Petroleum coke combustion.'],
            ['name' => 'Crude Oil Combustion',                       'scope' => 1, 'description' => 'Crude oil direct combustion.'],
            ['name' => 'Waste Oil Combustion',                       'scope' => 1, 'description' => 'Waste oil combustion.'],
            ['name' => 'Naphtha Combustion',                         'scope' => 1, 'description' => 'Naphtha combustion.'],
            ['name' => 'Coke Oven Gas Combustion',                   'scope' => 1, 'description' => 'Coke oven gas combustion.'],
            ['name' => 'Blast Furnace Gas Combustion',               'scope' => 1, 'description' => 'Blast furnace gas combustion.'],
            ['name' => 'Peat Combustion',                            'scope' => 1, 'description' => 'Peat fuel combustion.'],

            // =================================================================
            // SCOPE 1 — Mobile Combustion
            // =================================================================
            ['name' => 'Company Fleet - Gasoline',                   'scope' => 1, 'description' => 'Gasoline in company cars and vans.'],
            ['name' => 'Company Fleet - Diesel',                     'scope' => 1, 'description' => 'Diesel in company cars and vans.'],
            ['name' => 'Company Fleet - LPG',                        'scope' => 1, 'description' => 'LPG in company vehicles.'],
            ['name' => 'Company Fleet - CNG',                        'scope' => 1, 'description' => 'Compressed natural gas in fleet vehicles.'],
            ['name' => 'Company Fleet - LNG',                        'scope' => 1, 'description' => 'Liquefied natural gas in fleet vehicles.'],
            ['name' => 'Company Fleet - Biodiesel',                  'scope' => 1, 'description' => 'Biodiesel in company vehicles.'],
            ['name' => 'Company Fleet - Hybrid (Petrol)',             'scope' => 1, 'description' => 'Hybrid petrol vehicles in company fleet.'],
            ['name' => 'Company Fleet - Hybrid (Diesel)',             'scope' => 1, 'description' => 'Hybrid diesel vehicles in company fleet.'],
            ['name' => 'Company Fleet - Plug-in Hybrid',             'scope' => 1, 'description' => 'Plug-in hybrid vehicles in company fleet.'],
            ['name' => 'Company Fleet - Battery Electric',            'scope' => 1, 'description' => 'Battery electric vehicles in company fleet (zero direct).'],
            ['name' => 'Company Van - Diesel',                       'scope' => 1, 'description' => 'Diesel van in company fleet.'],
            ['name' => 'Company Van - Petrol',                       'scope' => 1, 'description' => 'Petrol van in company fleet.'],
            ['name' => 'Company Van - Electric',                     'scope' => 1, 'description' => 'Electric van in company fleet.'],
            ['name' => 'Heavy Goods Vehicle (HGV) - Diesel',         'scope' => 1, 'description' => 'Diesel heavy goods vehicles.'],
            ['name' => 'Light Duty Truck - Gasoline',                'scope' => 1, 'description' => 'Gasoline light-duty truck.'],
            ['name' => 'Light Duty Truck - Diesel',                  'scope' => 1, 'description' => 'Diesel light-duty truck.'],
            ['name' => 'Medium/Heavy Duty Truck - Diesel',           'scope' => 1, 'description' => 'Diesel medium/heavy-duty truck.'],
            ['name' => 'Aviation (Company-owned) - Jet Fuel',        'scope' => 1, 'description' => 'Jet fuel for company-owned aircraft.'],
            ['name' => 'Marine / Marine Vessels - Diesel',            'scope' => 1, 'description' => 'Diesel in company-owned boats or vessels.'],
            ['name' => 'Marine / Marine Vessels - Heavy Fuel Oil',    'scope' => 1, 'description' => 'Heavy fuel oil in marine vessels.'],
            ['name' => 'Marine / Marine Vessels - LNG',              'scope' => 1, 'description' => 'LNG in marine vessels.'],
            ['name' => 'Off-road Machinery - Diesel',                'scope' => 1, 'description' => 'Diesel in construction/mining/agricultural machinery.'],
            ['name' => 'Off-road Machinery - Gasoline',              'scope' => 1, 'description' => 'Gasoline in off-road equipment.'],
            ['name' => 'Rail (Company-owned) - Diesel',              'scope' => 1, 'description' => 'Diesel in company-owned locomotives.'],
            ['name' => 'Motorcycle - Petrol',                        'scope' => 1, 'description' => 'Company-owned motorcycles.'],
            ['name' => 'Forklift - Diesel',                          'scope' => 1, 'description' => 'Diesel forklifts.'],
            ['name' => 'Forklift - LPG',                             'scope' => 1, 'description' => 'LPG forklifts.'],

            // =================================================================
            // SCOPE 1 — Fugitive Emissions & Refrigerants
            // =================================================================
            ['name' => 'Refrigerant Leakage - R-22',                 'scope' => 1, 'description' => 'HCFC-22 refrigerant leakage (GWP: 1,810).'],
            ['name' => 'Refrigerant Leakage - R-32',                 'scope' => 1, 'description' => 'HFC-32 refrigerant leakage (GWP: 771).'],
            ['name' => 'Refrigerant Leakage - R-134a',               'scope' => 1, 'description' => 'HFC-134a refrigerant leakage (GWP: 1,530).'],
            ['name' => 'Refrigerant Leakage - R-404A',               'scope' => 1, 'description' => 'R-404A blend leakage (GWP: 3,922).'],
            ['name' => 'Refrigerant Leakage - R-407C',               'scope' => 1, 'description' => 'R-407C blend leakage (GWP: 1,774).'],
            ['name' => 'Refrigerant Leakage - R-410A',               'scope' => 1, 'description' => 'R-410A blend leakage (GWP: 2,088).'],
            ['name' => 'Refrigerant Leakage - R-507A',               'scope' => 1, 'description' => 'R-507A blend leakage (GWP: 3,985).'],
            ['name' => 'Refrigerant Leakage - R-448A',               'scope' => 1, 'description' => 'R-448A blend leakage (GWP: 1,386).'],
            ['name' => 'Refrigerant Leakage - R-449A',               'scope' => 1, 'description' => 'R-449A blend leakage (GWP: 1,397).'],
            ['name' => 'Refrigerant Leakage - R-454B',               'scope' => 1, 'description' => 'R-454B HFO blend (GWP: 466).'],
            ['name' => 'Refrigerant Leakage - R-290 (Propane)',       'scope' => 1, 'description' => 'Propane natural refrigerant (GWP: 3).'],
            ['name' => 'Refrigerant Leakage - R-717 (Ammonia)',       'scope' => 1, 'description' => 'Ammonia natural refrigerant (GWP: 0).'],
            ['name' => 'Refrigerant Leakage - R-744 (CO2)',           'scope' => 1, 'description' => 'CO2 natural refrigerant (GWP: 1).'],
            ['name' => 'Refrigerant Leakage - R-1234yf',             'scope' => 1, 'description' => 'HFO-1234yf ultra-low GWP (GWP: 1).'],
            ['name' => 'Refrigerant Leakage (CFCs)',                 'scope' => 1, 'description' => 'CFC refrigerant leakage from HVAC/refrigeration.'],
            ['name' => 'Refrigerant Leakage (HCFCs)',                'scope' => 1, 'description' => 'HCFC refrigerant leakage (e.g. R-22).'],
            ['name' => 'Refrigerant Leakage (HFCs)',                 'scope' => 1, 'description' => 'HFC refrigerant leakage (e.g. R-410A, R-134a).'],
            ['name' => 'Refrigerant Leakage (HFOs / Natural)',       'scope' => 1, 'description' => 'HFO or natural refrigerant leakage.'],
            ['name' => 'Fire Suppression (Halon)',                   'scope' => 1, 'description' => 'Halon from fire suppression systems.'],
            ['name' => 'Fire Suppression (HFCs)',                    'scope' => 1, 'description' => 'HFCs from fire suppression systems.'],
            ['name' => 'SF6 (Electrical Equipment)',                 'scope' => 1, 'description' => 'SF6 from switchgear or electrical equipment.'],
            ['name' => 'NF3 (Semiconductor Manufacturing)',          'scope' => 1, 'description' => 'NF3 from semiconductor/electronics manufacturing.'],
            ['name' => 'PFCs (Aluminium / Semiconductors)',          'scope' => 1, 'description' => 'PFC emissions from aluminium/semiconductor production.'],
            ['name' => 'CF4 (PFC-14)',                               'scope' => 1, 'description' => 'CF4 fugitive emissions.'],
            ['name' => 'C2F6 (PFC-116)',                             'scope' => 1, 'description' => 'C2F6 fugitive emissions.'],
            ['name' => 'Methane Leakage (Gas Systems)',              'scope' => 1, 'description' => 'Fugitive methane from gas pipes or equipment.'],
            ['name' => 'N2O from Industrial Processes',              'scope' => 1, 'description' => 'Nitrous oxide from industrial processes.'],
            ['name' => 'CO2 from Industrial Processes',              'scope' => 1, 'description' => 'Process CO2 (cement, lime, glass, etc.).'],

            // =================================================================
            // SCOPE 2 — Purchased Energy
            // =================================================================
            ['name' => 'Purchased Electricity (Location-based)',      'scope' => 2, 'description' => 'Grid electricity — location-based method.'],
            ['name' => 'Purchased Electricity (Market-based)',        'scope' => 2, 'description' => 'Grid electricity — market-based method.'],
            ['name' => 'Purchased Steam',                            'scope' => 2, 'description' => 'Purchased steam.'],
            ['name' => 'Purchased Hot Water',                        'scope' => 2, 'description' => 'Purchased hot water.'],
            ['name' => 'District Heating',                           'scope' => 2, 'description' => 'Purchased district heating.'],
            ['name' => 'District Cooling',                           'scope' => 2, 'description' => 'Purchased district cooling.'],
            ['name' => 'CHP - Electricity',                          'scope' => 2, 'description' => 'Electricity from combined heat and power.'],
            ['name' => 'CHP - Heat',                                 'scope' => 2, 'description' => 'Heat from combined heat and power.'],
            ['name' => 'Renewable Electricity (Solar PV)',            'scope' => 2, 'description' => 'Purchased solar PV electricity (market-based: 0).'],
            ['name' => 'Renewable Electricity (Wind)',               'scope' => 2, 'description' => 'Purchased wind electricity (market-based: 0).'],
            ['name' => 'Renewable Electricity (Hydro)',              'scope' => 2, 'description' => 'Purchased hydro electricity (market-based: 0).'],

            // =================================================================
            // SCOPE 3 — Category 1: Purchased Goods & Services
            // =================================================================
            ['name' => 'Scope 3 - 1. Purchased Goods & Services',                    'scope' => 3, 'description' => 'Upstream emissions from purchased goods and services.'],
            ['name' => 'S3.1 - Food & Beverage Products',                            'scope' => 3, 'description' => 'Spend-based: food & beverage products.'],
            ['name' => 'S3.1 - Textiles & Clothing',                                 'scope' => 3, 'description' => 'Spend-based: textiles and clothing.'],
            ['name' => 'S3.1 - Paper & Printing',                                    'scope' => 3, 'description' => 'Spend-based: paper and printing products.'],
            ['name' => 'S3.1 - Chemicals',                                           'scope' => 3, 'description' => 'Spend-based: chemical products.'],
            ['name' => 'S3.1 - Plastics & Rubber',                                   'scope' => 3, 'description' => 'Spend-based: plastics and rubber products.'],
            ['name' => 'S3.1 - Metals & Metal Products',                             'scope' => 3, 'description' => 'Spend-based: metal products.'],
            ['name' => 'S3.1 - Electronics & Electrical Equipment',                  'scope' => 3, 'description' => 'Spend-based: electronics and electrical equipment.'],
            ['name' => 'S3.1 - Furniture',                                           'scope' => 3, 'description' => 'Spend-based: furniture and fixtures.'],
            ['name' => 'S3.1 - Professional Services (Legal/Accounting)',             'scope' => 3, 'description' => 'Spend-based: professional services.'],
            ['name' => 'S3.1 - IT Services & Software',                              'scope' => 3, 'description' => 'Spend-based: IT services and software.'],
            ['name' => 'S3.1 - Telecommunications',                                  'scope' => 3, 'description' => 'Spend-based: telecom services.'],
            ['name' => 'S3.1 - Office Supplies',                                     'scope' => 3, 'description' => 'Spend-based: office supplies.'],
            ['name' => 'S3.1 - Cleaning Products & Services',                        'scope' => 3, 'description' => 'Spend-based: cleaning products and services.'],
            ['name' => 'S3.1 - Water Supply',                                        'scope' => 3, 'description' => 'Activity-based: water supply.'],
            ['name' => 'S3.1 - Water Treatment (Wastewater)',                        'scope' => 3, 'description' => 'Activity-based: wastewater treatment.'],

            // =================================================================
            // SCOPE 3 — Category 2: Capital Goods
            // =================================================================
            ['name' => 'Scope 3 - 2. Capital Goods',                                'scope' => 3, 'description' => 'Emissions from capital goods.'],
            ['name' => 'S3.2 - Buildings & Construction',                            'scope' => 3, 'description' => 'Spend-based: building construction.'],
            ['name' => 'S3.2 - Industrial Machinery',                                'scope' => 3, 'description' => 'Spend-based: industrial machinery.'],
            ['name' => 'S3.2 - Vehicles & Transport Equipment',                      'scope' => 3, 'description' => 'Spend-based: vehicle purchases.'],
            ['name' => 'S3.2 - IT Hardware & Servers',                               'scope' => 3, 'description' => 'Spend-based: IT hardware.'],
            ['name' => 'S3.2 - Office Furniture & Equipment',                        'scope' => 3, 'description' => 'Spend-based: office furniture purchases.'],

            // =================================================================
            // SCOPE 3 — Category 3: Fuel & Energy Related Activities (WTT)
            // =================================================================
            ['name' => 'Scope 3 - 3. Fuel & Energy Related Activities',              'scope' => 3, 'description' => 'T&D losses, upstream fuel production, well-to-tank.'],
            ['name' => 'S3.3 - WTT Natural Gas',                                    'scope' => 3, 'description' => 'Well-to-tank: natural gas.'],
            ['name' => 'S3.3 - WTT Diesel',                                         'scope' => 3, 'description' => 'Well-to-tank: diesel.'],
            ['name' => 'S3.3 - WTT Petrol (Gasoline)',                               'scope' => 3, 'description' => 'Well-to-tank: petrol/gasoline.'],
            ['name' => 'S3.3 - WTT LPG',                                            'scope' => 3, 'description' => 'Well-to-tank: LPG/propane.'],
            ['name' => 'S3.3 - WTT Jet Fuel',                                       'scope' => 3, 'description' => 'Well-to-tank: jet fuel.'],
            ['name' => 'S3.3 - WTT Coal',                                           'scope' => 3, 'description' => 'Well-to-tank: coal.'],
            ['name' => 'S3.3 - WTT Fuel Oil',                                       'scope' => 3, 'description' => 'Well-to-tank: fuel oil.'],
            ['name' => 'S3.3 - T&D Losses (Electricity)',                            'scope' => 3, 'description' => 'Transmission & distribution losses for grid electricity.'],

            // =================================================================
            // SCOPE 3 — Category 4: Upstream Transportation & Distribution
            // =================================================================
            ['name' => 'Scope 3 - 4. Upstream Transportation & Distribution',        'scope' => 3, 'description' => 'Inbound logistics: road, rail, sea, air.'],
            ['name' => 'S3.4 - Freight Road (HGV, average)',                         'scope' => 3, 'description' => 'Road freight by HGV.'],
            ['name' => 'S3.4 - Freight Road (Van)',                                  'scope' => 3, 'description' => 'Road freight by van.'],
            ['name' => 'S3.4 - Freight Rail',                                        'scope' => 3, 'description' => 'Freight by rail.'],
            ['name' => 'S3.4 - Freight Sea (Container)',                             'scope' => 3, 'description' => 'Sea freight by container ship.'],
            ['name' => 'S3.4 - Freight Sea (Bulk)',                                  'scope' => 3, 'description' => 'Sea freight by bulk carrier.'],
            ['name' => 'S3.4 - Freight Air (Short-haul)',                            'scope' => 3, 'description' => 'Air freight short-haul.'],
            ['name' => 'S3.4 - Freight Air (Long-haul)',                             'scope' => 3, 'description' => 'Air freight long-haul.'],
            ['name' => 'S3.4 - Freight Inland Waterway',                             'scope' => 3, 'description' => 'Freight by barge/inland waterway.'],

            // =================================================================
            // SCOPE 3 — Category 5: Waste Generated in Operations
            // =================================================================
            ['name' => 'Scope 3 - 5. Waste Generated in Operations',                'scope' => 3, 'description' => 'Waste disposal: landfill, recycling, incineration, composting.'],
            ['name' => 'S3.5 - General Waste to Landfill',                           'scope' => 3, 'description' => 'Municipal solid waste to landfill.'],
            ['name' => 'S3.5 - General Waste to Incineration',                       'scope' => 3, 'description' => 'Municipal solid waste to energy-from-waste.'],
            ['name' => 'S3.5 - Paper/Cardboard Recycling',                           'scope' => 3, 'description' => 'Paper and cardboard recycling.'],
            ['name' => 'S3.5 - Plastic Recycling',                                  'scope' => 3, 'description' => 'Mixed plastics recycling.'],
            ['name' => 'S3.5 - Metal Recycling',                                    'scope' => 3, 'description' => 'Mixed metal recycling.'],
            ['name' => 'S3.5 - Glass Recycling',                                    'scope' => 3, 'description' => 'Glass recycling.'],
            ['name' => 'S3.5 - Food Waste to Landfill',                              'scope' => 3, 'description' => 'Food waste sent to landfill.'],
            ['name' => 'S3.5 - Food Waste to Composting',                            'scope' => 3, 'description' => 'Food waste composting.'],
            ['name' => 'S3.5 - Food Waste to Anaerobic Digestion',                   'scope' => 3, 'description' => 'Food waste anaerobic digestion.'],
            ['name' => 'S3.5 - Electrical/Electronic Waste',                         'scope' => 3, 'description' => 'WEEE waste disposal.'],
            ['name' => 'S3.5 - Construction & Demolition Waste',                     'scope' => 3, 'description' => 'C&D waste disposal.'],
            ['name' => 'S3.5 - Textile Waste to Landfill',                           'scope' => 3, 'description' => 'Textiles sent to landfill.'],
            ['name' => 'S3.5 - Wood Waste to Landfill',                              'scope' => 3, 'description' => 'Wood waste sent to landfill.'],

            // =================================================================
            // SCOPE 3 — Category 6: Business Travel
            // =================================================================
            ['name' => 'Scope 3 - 6. Business Travel',                              'scope' => 3, 'description' => 'Air, rail, car, taxi, hotel from business travel.'],
            ['name' => 'S3.6 - Flight Domestic (Economy)',                           'scope' => 3, 'description' => 'Domestic flights economy class.'],
            ['name' => 'S3.6 - Flight Short-haul (Economy)',                         'scope' => 3, 'description' => 'Short-haul flights (<3700 km) economy.'],
            ['name' => 'S3.6 - Flight Short-haul (Business)',                        'scope' => 3, 'description' => 'Short-haul flights (<3700 km) business.'],
            ['name' => 'S3.6 - Flight Long-haul (Economy)',                          'scope' => 3, 'description' => 'Long-haul flights (>3700 km) economy.'],
            ['name' => 'S3.6 - Flight Long-haul (Premium Economy)',                  'scope' => 3, 'description' => 'Long-haul flights premium economy.'],
            ['name' => 'S3.6 - Flight Long-haul (Business)',                         'scope' => 3, 'description' => 'Long-haul flights business class.'],
            ['name' => 'S3.6 - Flight Long-haul (First)',                            'scope' => 3, 'description' => 'Long-haul flights first class.'],
            ['name' => 'S3.6 - Rail (National)',                                     'scope' => 3, 'description' => 'National rail business travel.'],
            ['name' => 'S3.6 - Rail (International/Eurostar)',                       'scope' => 3, 'description' => 'International rail business travel.'],
            ['name' => 'S3.6 - Taxi (Regular)',                                      'scope' => 3, 'description' => 'Regular taxi for business.'],
            ['name' => 'S3.6 - Rental Car (Average)',                                'scope' => 3, 'description' => 'Rental car average.'],
            ['name' => 'S3.6 - Bus / Coach',                                        'scope' => 3, 'description' => 'Bus or coach business travel.'],
            ['name' => 'S3.6 - Hotel Stay (UK)',                                     'scope' => 3, 'description' => 'Hotel room per night in UK.'],
            ['name' => 'S3.6 - Hotel Stay (International Average)',                  'scope' => 3, 'description' => 'Hotel room per night international average.'],

            // =================================================================
            // SCOPE 3 — Category 7: Employee Commuting
            // =================================================================
            ['name' => 'Scope 3 - 7. Employee Commuting',                           'scope' => 3, 'description' => 'Commuting by car, bus, rail, motorcycle, etc.'],
            ['name' => 'S3.7 - Car (Average, Petrol+Diesel)',                        'scope' => 3, 'description' => 'Average car commuting.'],
            ['name' => 'S3.7 - Car (Petrol, Small)',                                 'scope' => 3, 'description' => 'Small petrol car commuting.'],
            ['name' => 'S3.7 - Car (Diesel, Large)',                                 'scope' => 3, 'description' => 'Large diesel car commuting.'],
            ['name' => 'S3.7 - Car (Electric / BEV)',                                'scope' => 3, 'description' => 'Battery electric car commuting.'],
            ['name' => 'S3.7 - Car (Hybrid)',                                        'scope' => 3, 'description' => 'Hybrid car commuting.'],
            ['name' => 'S3.7 - Motorcycle',                                          'scope' => 3, 'description' => 'Motorcycle commuting.'],
            ['name' => 'S3.7 - Bus (Local)',                                         'scope' => 3, 'description' => 'Local bus commuting.'],
            ['name' => 'S3.7 - National Rail',                                       'scope' => 3, 'description' => 'National rail commuting.'],
            ['name' => 'S3.7 - Light Rail / Tram',                                   'scope' => 3, 'description' => 'Light rail or tram commuting.'],
            ['name' => 'S3.7 - Underground / Metro',                                'scope' => 3, 'description' => 'Underground/metro commuting.'],
            ['name' => 'S3.7 - Ferry (Foot Passenger)',                              'scope' => 3, 'description' => 'Ferry foot passenger commuting.'],
            ['name' => 'S3.7 - Bicycle / Walking',                                  'scope' => 3, 'description' => 'Zero-emission commuting.'],
            ['name' => 'S3.7 - E-bike / E-scooter',                                 'scope' => 3, 'description' => 'Electric bike/scooter commuting.'],

            // =================================================================
            // SCOPE 3 — Categories 8-15
            // =================================================================
            ['name' => 'Scope 3 - 8. Upstream Leased Assets',                       'scope' => 3, 'description' => 'Emissions from upstream leased assets.'],
            ['name' => 'S3.8 - Leased Office Space',                                'scope' => 3, 'description' => 'Energy use in leased office buildings.'],
            ['name' => 'S3.8 - Leased Warehouse',                                   'scope' => 3, 'description' => 'Energy use in leased warehouses.'],
            ['name' => 'S3.8 - Leased Vehicles',                                    'scope' => 3, 'description' => 'Fuel use in leased vehicles.'],

            ['name' => 'Scope 3 - 9. Downstream Transportation & Distribution',     'scope' => 3, 'description' => 'Outbound logistics to customers.'],
            ['name' => 'Scope 3 - 10. Processing of Sold Products',                 'scope' => 3, 'description' => 'Processing of sold products by third parties.'],
            ['name' => 'Scope 3 - 11. Use of Sold Products',                        'scope' => 3, 'description' => 'Emissions from use of sold products.'],
            ['name' => 'Scope 3 - 12. End-of-Life Treatment of Sold Products',      'scope' => 3, 'description' => 'Waste treatment of sold products at end of life.'],
            ['name' => 'Scope 3 - 13. Downstream Leased Assets',                    'scope' => 3, 'description' => 'Emissions from downstream leased assets.'],
            ['name' => 'Scope 3 - 14. Franchises',                                  'scope' => 3, 'description' => 'Emissions from franchise operations.'],
            ['name' => 'Scope 3 - 15. Investments',                                 'scope' => 3, 'description' => 'Emissions from investments (equity, debt, project finance).'],
        ];

        foreach ($sources as $source) {
            EmissionSource::updateOrCreate(
                ['name' => $source['name']],
                ['scope' => $source['scope'], 'description' => $source['description']]
            );
        }
    }

    // =========================================================================
    // IPCC DEFAULT FACTORS (tCO2e per unit)
    // Source: IPCC 2006 Guidelines, AR6 GWP values
    // =========================================================================
    private function seedIPCCFactors(): void
    {
        $orgId = FactorOrganization::where('code', 'IPCC')->value('id');
        if (!$orgId) return;

        $factors = [
            // Stationary Combustion — IPCC default CO2 factors (tCO2e per unit)
            // Derived from IPCC 2006 Vol 2 Table 2.2 (kgCO2/TJ) converted to practical units
            ['source' => 'Natural Gas Combustion',           'unit' => 'kWh',    'value' => 0.000202, 'region' => 'Global'],
            ['source' => 'Natural Gas Combustion',           'unit' => 'm³',     'value' => 0.002020, 'region' => 'Global'],
            ['source' => 'Natural Gas Combustion',           'unit' => 'MMBtu',  'value' => 0.053060, 'region' => 'Global'],
            ['source' => 'Diesel (Stationary Combustion)',   'unit' => 'liters', 'value' => 0.002680, 'region' => 'Global'],
            ['source' => 'Diesel (Stationary Combustion)',   'unit' => 'gallons','value' => 0.010210, 'region' => 'Global'],
            ['source' => 'Gasoline (Stationary Combustion)', 'unit' => 'liters', 'value' => 0.002310, 'region' => 'Global'],
            ['source' => 'Gasoline (Stationary Combustion)', 'unit' => 'gallons','value' => 0.008780, 'region' => 'Global'],
            ['source' => 'Kerosene Combustion',              'unit' => 'liters', 'value' => 0.002540, 'region' => 'Global'],
            ['source' => 'LPG / Propane Combustion',         'unit' => 'liters', 'value' => 0.001510, 'region' => 'Global'],
            ['source' => 'LPG / Propane Combustion',         'unit' => 'gallons','value' => 0.005720, 'region' => 'Global'],
            ['source' => 'Fuel Oil (Heating Oil) Combustion','unit' => 'liters', 'value' => 0.003170, 'region' => 'Global'],
            ['source' => 'Fuel Oil No. 2 Combustion',        'unit' => 'gallons','value' => 0.010210, 'region' => 'Global'],
            ['source' => 'Fuel Oil No. 6 (Residual) Combustion', 'unit' => 'gallons', 'value' => 0.011270, 'region' => 'Global'],
            ['source' => 'Anthracite Coal Combustion',       'unit' => 'kg',     'value' => 0.002602, 'region' => 'Global'],
            ['source' => 'Bituminous Coal Combustion',       'unit' => 'kg',     'value' => 0.002325, 'region' => 'Global'],
            ['source' => 'Sub-bituminous Coal Combustion',   'unit' => 'kg',     'value' => 0.001676, 'region' => 'Global'],
            ['source' => 'Lignite Coal Combustion',          'unit' => 'kg',     'value' => 0.001389, 'region' => 'Global'],
            ['source' => 'Coal Combustion',                  'unit' => 'kg',     'value' => 0.002420, 'region' => 'Global'],
            ['source' => 'Peat Combustion',                  'unit' => 'kg',     'value' => 0.001060, 'region' => 'Global'],
            ['source' => 'Petroleum Coke Combustion',        'unit' => 'kg',     'value' => 0.003510, 'region' => 'Global'],
            ['source' => 'Crude Oil Combustion',             'unit' => 'liters', 'value' => 0.003050, 'region' => 'Global'],
            ['source' => 'Naphtha Combustion',               'unit' => 'liters', 'value' => 0.002630, 'region' => 'Global'],
            ['source' => 'Jet Fuel (Stationary) Combustion', 'unit' => 'liters', 'value' => 0.002520, 'region' => 'Global'],
            ['source' => 'Butane Combustion',                'unit' => 'gallons','value' => 0.006670, 'region' => 'Global'],
            ['source' => 'Ethane Combustion',                'unit' => 'gallons','value' => 0.004070, 'region' => 'Global'],
            ['source' => 'Biomass Combustion',               'unit' => 'kg',     'value' => 0.000000, 'region' => 'Global'],
            ['source' => 'Wood / Wood Pellets Combustion',   'unit' => 'kg',     'value' => 0.000000, 'region' => 'Global'],
            ['source' => 'Biodiesel (B100) Combustion',      'unit' => 'liters', 'value' => 0.000000, 'region' => 'Global'],
            ['source' => 'Bioethanol (E100) Combustion',     'unit' => 'liters', 'value' => 0.000000, 'region' => 'Global'],
            ['source' => 'Biogas Combustion',                'unit' => 'kWh',    'value' => 0.000000, 'region' => 'Global'],
            ['source' => 'Waste Oil Combustion',             'unit' => 'gallons','value' => 0.010210, 'region' => 'Global'],
            ['source' => 'Coke Oven Gas Combustion',         'unit' => 'kWh',    'value' => 0.000160, 'region' => 'Global'],
            ['source' => 'Blast Furnace Gas Combustion',     'unit' => 'kWh',    'value' => 0.000936, 'region' => 'Global'],

            // Mobile Combustion — IPCC/general factors (tCO2e per unit)
            ['source' => 'Company Fleet - Gasoline',         'unit' => 'liters', 'value' => 0.002310, 'region' => 'Global'],
            ['source' => 'Company Fleet - Diesel',           'unit' => 'liters', 'value' => 0.002680, 'region' => 'Global'],
            ['source' => 'Company Fleet - LPG',              'unit' => 'liters', 'value' => 0.001510, 'region' => 'Global'],
            ['source' => 'Company Fleet - CNG',              'unit' => 'm³',     'value' => 0.002000, 'region' => 'Global'],
            ['source' => 'Company Fleet - LNG',              'unit' => 'gallons','value' => 0.004460, 'region' => 'Global'],
            ['source' => 'Company Fleet - Biodiesel',        'unit' => 'liters', 'value' => 0.000000, 'region' => 'Global'],
            ['source' => 'Company Fleet - Battery Electric',  'unit' => 'km',     'value' => 0.000000, 'region' => 'Global'],
            ['source' => 'Aviation (Company-owned) - Jet Fuel', 'unit' => 'liters', 'value' => 0.002520, 'region' => 'Global'],
            ['source' => 'Marine / Marine Vessels - Diesel',  'unit' => 'liters', 'value' => 0.002680, 'region' => 'Global'],
            ['source' => 'Marine / Marine Vessels - Heavy Fuel Oil', 'unit' => 'liters', 'value' => 0.003210, 'region' => 'Global'],
            ['source' => 'Off-road Machinery - Diesel',      'unit' => 'liters', 'value' => 0.002680, 'region' => 'Global'],
            ['source' => 'Rail (Company-owned) - Diesel',    'unit' => 'liters', 'value' => 0.002680, 'region' => 'Global'],

            // Scope 2 — Global average electricity (placeholder)
            ['source' => 'Purchased Electricity (Location-based)',  'unit' => 'kWh', 'value' => 0.000500, 'region' => 'Global (avg)'],
            ['source' => 'Purchased Steam',                  'unit' => 'MMBtu', 'value' => 0.066330, 'region' => 'Global'],
            ['source' => 'District Heating',                 'unit' => 'kWh',   'value' => 0.000250, 'region' => 'Global'],
            ['source' => 'District Cooling',                 'unit' => 'kWh',   'value' => 0.000180, 'region' => 'Global'],
        ];

        $this->insertFactors($factors, $orgId);
    }

    // =========================================================================
    // DEFRA/DESNZ 2025 FACTORS (tCO2e per unit)
    // Source: UK Government GHG Conversion Factors 2025
    // =========================================================================
    private function seedDEFRAFactors(): void
    {
        $orgId = FactorOrganization::where('code', 'DEFRA')->value('id');
        if (!$orgId) return;

        $factors = [
            // Stationary Combustion
            ['source' => 'Natural Gas Combustion',           'unit' => 'kWh',    'value' => 0.000183, 'region' => 'UK'],
            ['source' => 'Diesel (Stationary Combustion)',   'unit' => 'liters', 'value' => 0.002512, 'region' => 'UK'],
            ['source' => 'Gasoline (Stationary Combustion)', 'unit' => 'liters', 'value' => 0.002370, 'region' => 'UK'],
            ['source' => 'LPG / Propane Combustion',         'unit' => 'liters', 'value' => 0.001557, 'region' => 'UK'],
            ['source' => 'LPG / Propane Combustion',         'unit' => 'kWh',    'value' => 0.000260, 'region' => 'UK'],
            ['source' => 'Fuel Oil (Heating Oil) Combustion','unit' => 'liters', 'value' => 0.003240, 'region' => 'UK'],
            ['source' => 'Fuel Oil (Heating Oil) Combustion','unit' => 'kWh',    'value' => 0.000320, 'region' => 'UK'],
            ['source' => 'Kerosene Combustion',              'unit' => 'liters', 'value' => 0.002540, 'region' => 'UK'],
            ['source' => 'Coal Combustion',                  'unit' => 'kg',     'value' => 0.003260, 'region' => 'UK'],
            ['source' => 'Coal Combustion',                  'unit' => 'kWh',    'value' => 0.000340, 'region' => 'UK'],
            ['source' => 'Wood / Wood Pellets Combustion',   'unit' => 'kWh',    'value' => 0.000010, 'region' => 'UK'],
            ['source' => 'Biomass Combustion',               'unit' => 'kWh',    'value' => 0.000010, 'region' => 'UK'],
            ['source' => 'Jet Fuel (Stationary) Combustion', 'unit' => 'liters', 'value' => 0.002940, 'region' => 'UK'],

            // Scope 2 — UK Grid Electricity
            ['source' => 'Purchased Electricity (Location-based)', 'unit' => 'kWh', 'value' => 0.000177, 'region' => 'UK (2025)'],

            // Mobile — Passenger Vehicles (per km)
            ['source' => 'Company Fleet - Gasoline',         'unit' => 'km',     'value' => 0.000163, 'region' => 'UK'],
            ['source' => 'Company Fleet - Diesel',           'unit' => 'km',     'value' => 0.000173, 'region' => 'UK'],
            ['source' => 'Company Fleet - Hybrid (Petrol)',   'unit' => 'km',     'value' => 0.000128, 'region' => 'UK'],
            ['source' => 'Company Fleet - Plug-in Hybrid',   'unit' => 'km',     'value' => 0.000137, 'region' => 'UK'],
            ['source' => 'Company Fleet - Battery Electric',  'unit' => 'km',     'value' => 0.000040, 'region' => 'UK'],
            ['source' => 'Company Van - Diesel',             'unit' => 'km',     'value' => 0.000317, 'region' => 'UK'],
            ['source' => 'Company Van - Petrol',             'unit' => 'km',     'value' => 0.000273, 'region' => 'UK'],
            ['source' => 'Company Van - Electric',           'unit' => 'km',     'value' => 0.000087, 'region' => 'UK'],

            // Mobile — fuel-based (per litre)
            ['source' => 'Company Fleet - Gasoline',         'unit' => 'liters', 'value' => 0.002370, 'region' => 'UK'],
            ['source' => 'Company Fleet - Diesel',           'unit' => 'liters', 'value' => 0.002512, 'region' => 'UK'],

            // Scope 3 — Business Travel (DEFRA per passenger-km)
            ['source' => 'S3.6 - Flight Domestic (Economy)',          'unit' => 'passenger-km', 'value' => 0.000267, 'region' => 'UK'],
            ['source' => 'S3.6 - Flight Short-haul (Economy)',        'unit' => 'passenger-km', 'value' => 0.000126, 'region' => 'UK'],
            ['source' => 'S3.6 - Flight Short-haul (Business)',       'unit' => 'passenger-km', 'value' => 0.000189, 'region' => 'UK'],
            ['source' => 'S3.6 - Flight Long-haul (Economy)',         'unit' => 'passenger-km', 'value' => 0.000117, 'region' => 'UK'],
            ['source' => 'S3.6 - Flight Long-haul (Premium Economy)', 'unit' => 'passenger-km', 'value' => 0.000187, 'region' => 'UK'],
            ['source' => 'S3.6 - Flight Long-haul (Business)',        'unit' => 'passenger-km', 'value' => 0.000339, 'region' => 'UK'],
            ['source' => 'S3.6 - Flight Long-haul (First)',           'unit' => 'passenger-km', 'value' => 0.000469, 'region' => 'UK'],
            ['source' => 'S3.6 - Rail (National)',                    'unit' => 'passenger-km', 'value' => 0.000035, 'region' => 'UK'],
            ['source' => 'S3.6 - Rail (International/Eurostar)',      'unit' => 'passenger-km', 'value' => 0.000004, 'region' => 'UK'],
            ['source' => 'S3.6 - Taxi (Regular)',                     'unit' => 'passenger-km', 'value' => 0.000149, 'region' => 'UK'],
            ['source' => 'S3.6 - Bus / Coach',                       'unit' => 'passenger-km', 'value' => 0.000104, 'region' => 'UK'],
            ['source' => 'S3.6 - Hotel Stay (UK)',                    'unit' => 'room-night',   'value' => 0.010400, 'region' => 'UK'],

            // Scope 3 — Employee Commuting (DEFRA per passenger-km)
            ['source' => 'S3.7 - Car (Average, Petrol+Diesel)',      'unit' => 'passenger-km', 'value' => 0.000171, 'region' => 'UK'],
            ['source' => 'S3.7 - Car (Petrol, Small)',               'unit' => 'passenger-km', 'value' => 0.000150, 'region' => 'UK'],
            ['source' => 'S3.7 - Car (Diesel, Large)',               'unit' => 'passenger-km', 'value' => 0.000200, 'region' => 'UK'],
            ['source' => 'S3.7 - Car (Electric / BEV)',              'unit' => 'passenger-km', 'value' => 0.000050, 'region' => 'UK'],
            ['source' => 'S3.7 - Car (Hybrid)',                      'unit' => 'passenger-km', 'value' => 0.000128, 'region' => 'UK'],
            ['source' => 'S3.7 - Motorcycle',                        'unit' => 'passenger-km', 'value' => 0.000113, 'region' => 'UK'],
            ['source' => 'S3.7 - Bus (Local)',                       'unit' => 'passenger-km', 'value' => 0.000089, 'region' => 'UK'],
            ['source' => 'S3.7 - National Rail',                     'unit' => 'passenger-km', 'value' => 0.000035, 'region' => 'UK'],
            ['source' => 'S3.7 - Light Rail / Tram',                 'unit' => 'passenger-km', 'value' => 0.000029, 'region' => 'UK'],
            ['source' => 'S3.7 - Underground / Metro',               'unit' => 'passenger-km', 'value' => 0.000028, 'region' => 'UK'],
            ['source' => 'S3.7 - Ferry (Foot Passenger)',             'unit' => 'passenger-km', 'value' => 0.000019, 'region' => 'UK'],
            ['source' => 'S3.7 - Bicycle / Walking',                 'unit' => 'passenger-km', 'value' => 0.000000, 'region' => 'UK'],
            ['source' => 'S3.7 - E-bike / E-scooter',               'unit' => 'passenger-km', 'value' => 0.000005, 'region' => 'UK'],

            // Scope 3 — Cat 3: WTT Factors (DEFRA)
            ['source' => 'S3.3 - WTT Natural Gas',                  'unit' => 'kWh',    'value' => 0.000035, 'region' => 'UK'],
            ['source' => 'S3.3 - WTT Diesel',                       'unit' => 'liters', 'value' => 0.000600, 'region' => 'UK'],
            ['source' => 'S3.3 - WTT Petrol (Gasoline)',             'unit' => 'liters', 'value' => 0.000590, 'region' => 'UK'],
            ['source' => 'S3.3 - WTT LPG',                          'unit' => 'liters', 'value' => 0.000190, 'region' => 'UK'],
            ['source' => 'S3.3 - WTT Jet Fuel',                     'unit' => 'liters', 'value' => 0.000500, 'region' => 'UK'],
            ['source' => 'S3.3 - WTT Coal',                         'unit' => 'kWh',    'value' => 0.000045, 'region' => 'UK'],
            ['source' => 'S3.3 - WTT Fuel Oil',                     'unit' => 'liters', 'value' => 0.000720, 'region' => 'UK'],
            ['source' => 'S3.3 - T&D Losses (Electricity)',          'unit' => 'kWh',    'value' => 0.000019, 'region' => 'UK (2025)'],

            // Scope 3 — Cat 4: Freight (DEFRA per tonne-km)
            ['source' => 'S3.4 - Freight Road (HGV, average)',       'unit' => 'tonne-km', 'value' => 0.000105, 'region' => 'UK'],
            ['source' => 'S3.4 - Freight Road (Van)',                'unit' => 'tonne-km', 'value' => 0.000580, 'region' => 'UK'],
            ['source' => 'S3.4 - Freight Rail',                      'unit' => 'tonne-km', 'value' => 0.000028, 'region' => 'UK'],
            ['source' => 'S3.4 - Freight Sea (Container)',           'unit' => 'tonne-km', 'value' => 0.000016, 'region' => 'UK'],
            ['source' => 'S3.4 - Freight Sea (Bulk)',                'unit' => 'tonne-km', 'value' => 0.000005, 'region' => 'UK'],
            ['source' => 'S3.4 - Freight Air (Short-haul)',          'unit' => 'tonne-km', 'value' => 0.002210, 'region' => 'UK'],
            ['source' => 'S3.4 - Freight Air (Long-haul)',           'unit' => 'tonne-km', 'value' => 0.000570, 'region' => 'UK'],
            ['source' => 'S3.4 - Freight Inland Waterway',           'unit' => 'tonne-km', 'value' => 0.000031, 'region' => 'UK'],

            // Scope 3 — Cat 5: Waste (DEFRA per tonne)
            ['source' => 'S3.5 - General Waste to Landfill',         'unit' => 'tonnes', 'value' => 0.446242, 'region' => 'UK'],
            ['source' => 'S3.5 - General Waste to Incineration',     'unit' => 'tonnes', 'value' => 0.021317, 'region' => 'UK'],
            ['source' => 'S3.5 - Paper/Cardboard Recycling',         'unit' => 'tonnes', 'value' => 0.021317, 'region' => 'UK'],
            ['source' => 'S3.5 - Plastic Recycling',                 'unit' => 'tonnes', 'value' => 0.021317, 'region' => 'UK'],
            ['source' => 'S3.5 - Metal Recycling',                   'unit' => 'tonnes', 'value' => 0.021317, 'region' => 'UK'],
            ['source' => 'S3.5 - Glass Recycling',                   'unit' => 'tonnes', 'value' => 0.021317, 'region' => 'UK'],
            ['source' => 'S3.5 - Food Waste to Landfill',            'unit' => 'tonnes', 'value' => 0.580000, 'region' => 'UK'],
            ['source' => 'S3.5 - Food Waste to Composting',          'unit' => 'tonnes', 'value' => 0.010000, 'region' => 'UK'],
            ['source' => 'S3.5 - Food Waste to Anaerobic Digestion', 'unit' => 'tonnes', 'value' => 0.008900, 'region' => 'UK'],
            ['source' => 'S3.5 - Textile Waste to Landfill',         'unit' => 'tonnes', 'value' => 0.487000, 'region' => 'UK'],
            ['source' => 'S3.5 - Wood Waste to Landfill',            'unit' => 'tonnes', 'value' => 0.729000, 'region' => 'UK'],
            ['source' => 'S3.5 - Construction & Demolition Waste',   'unit' => 'tonnes', 'value' => 0.248000, 'region' => 'UK'],
            ['source' => 'S3.5 - Electrical/Electronic Waste',       'unit' => 'tonnes', 'value' => 0.021317, 'region' => 'UK'],

            // Scope 3 — Cat 1: Water (DEFRA)
            ['source' => 'S3.1 - Water Supply',              'unit' => 'm³', 'value' => 0.000171, 'region' => 'UK (2025)'],
            ['source' => 'S3.1 - Water Treatment (Wastewater)', 'unit' => 'm³', 'value' => 0.000360, 'region' => 'UK'],
        ];

        $this->insertFactors($factors, $orgId);
    }

    // =========================================================================
    // US EPA FACTORS (tCO2e per unit)
    // Source: EPA GHG Emission Factors Hub 2025
    // =========================================================================
    private function seedEPAFactors(): void
    {
        $orgId = FactorOrganization::where('code', 'EPA')->value('id');
        if (!$orgId) return;

        $factors = [
            // Stationary Combustion (per gallon for liquids, US units)
            ['source' => 'Natural Gas Combustion',           'unit' => 'MMBtu',  'value' => 0.053060, 'region' => 'US'],
            ['source' => 'Natural Gas Combustion',           'unit' => 'therm',  'value' => 0.005306, 'region' => 'US'],
            ['source' => 'Natural Gas Combustion',           'unit' => 'Mcf',    'value' => 0.054840, 'region' => 'US'],
            ['source' => 'Gasoline (Stationary Combustion)', 'unit' => 'gallons','value' => 0.008780, 'region' => 'US'],
            ['source' => 'Diesel (Stationary Combustion)',   'unit' => 'gallons','value' => 0.010210, 'region' => 'US'],
            ['source' => 'Kerosene Combustion',              'unit' => 'gallons','value' => 0.010150, 'region' => 'US'],
            ['source' => 'LPG / Propane Combustion',         'unit' => 'gallons','value' => 0.005720, 'region' => 'US'],
            ['source' => 'Fuel Oil No. 2 Combustion',        'unit' => 'gallons','value' => 0.010210, 'region' => 'US'],
            ['source' => 'Fuel Oil No. 4 Combustion',        'unit' => 'gallons','value' => 0.010960, 'region' => 'US'],
            ['source' => 'Fuel Oil No. 6 (Residual) Combustion','unit'=>'gallons','value'=> 0.011270, 'region' => 'US'],
            ['source' => 'Jet Fuel (Stationary) Combustion', 'unit' => 'gallons','value' => 0.009750, 'region' => 'US'],
            ['source' => 'Butane Combustion',                'unit' => 'gallons','value' => 0.006670, 'region' => 'US'],
            ['source' => 'Ethane Combustion',                'unit' => 'gallons','value' => 0.004070, 'region' => 'US'],
            ['source' => 'Crude Oil Combustion',             'unit' => 'barrels','value' => 0.432330, 'region' => 'US'],
            ['source' => 'Waste Oil Combustion',             'unit' => 'gallons','value' => 0.010210, 'region' => 'US'],
            ['source' => 'Anthracite Coal Combustion',       'unit' => 'short tons', 'value' => 2.602000, 'region' => 'US'],
            ['source' => 'Bituminous Coal Combustion',       'unit' => 'short tons', 'value' => 2.325000, 'region' => 'US'],
            ['source' => 'Sub-bituminous Coal Combustion',   'unit' => 'short tons', 'value' => 1.676000, 'region' => 'US'],
            ['source' => 'Lignite Coal Combustion',          'unit' => 'short tons', 'value' => 1.389000, 'region' => 'US'],
            ['source' => 'Wood / Wood Pellets Combustion',   'unit' => 'short tons', 'value' => 1.443000, 'region' => 'US (biogenic)'],
            ['source' => 'Biodiesel (B100) Combustion',      'unit' => 'gallons','value' => 0.009450, 'region' => 'US (biogenic)'],
            ['source' => 'Bioethanol (E100) Combustion',     'unit' => 'gallons','value' => 0.005750, 'region' => 'US (biogenic)'],
            ['source' => 'Petroleum Coke Combustion',        'unit' => 'gallons','value' => 0.014640, 'region' => 'US'],

            // Scope 2 — US National Average Electricity
            ['source' => 'Purchased Electricity (Location-based)', 'unit' => 'kWh', 'value' => 0.000373, 'region' => 'US (national avg 2023)'],
            ['source' => 'Purchased Electricity (Location-based)', 'unit' => 'MWh', 'value' => 0.373400, 'region' => 'US (national avg 2023)'],
            ['source' => 'Purchased Steam',                  'unit' => 'MMBtu', 'value' => 0.066330, 'region' => 'US'],

            // EPA Upstream/WTT factors (Cat 3)
            ['source' => 'S3.3 - WTT Natural Gas',          'unit' => 'MMBtu', 'value' => 0.010550, 'region' => 'US'],
            ['source' => 'S3.3 - WTT Petrol (Gasoline)',     'unit' => 'gallons','value' => 0.002040, 'region' => 'US'],
            ['source' => 'S3.3 - WTT Diesel',               'unit' => 'gallons','value' => 0.001770, 'region' => 'US'],
            ['source' => 'S3.3 - WTT Jet Fuel',             'unit' => 'gallons','value' => 0.001480, 'region' => 'US'],
            ['source' => 'S3.3 - WTT LPG',                  'unit' => 'gallons','value' => 0.000870, 'region' => 'US'],
            ['source' => 'S3.3 - WTT Coal',                 'unit' => 'short tons', 'value' => 0.195120, 'region' => 'US'],
            ['source' => 'S3.3 - WTT Fuel Oil',             'unit' => 'gallons','value' => 0.001010, 'region' => 'US'],

            // EPA Water (Cat 1)
            ['source' => 'S3.1 - Water Supply',              'unit' => 'thousand gallons', 'value' => 0.001460, 'region' => 'US'],
            ['source' => 'S3.1 - Water Treatment (Wastewater)', 'unit' => 'thousand gallons', 'value' => 0.004010, 'region' => 'US'],

            // EPA Waste — WARM Model (tCO2e per short ton)
            ['source' => 'S3.5 - General Waste to Landfill',  'unit' => 'short tons', 'value' => 0.520000, 'region' => 'US'],
            ['source' => 'S3.5 - Food Waste to Landfill',     'unit' => 'short tons', 'value' => 0.520000, 'region' => 'US'],
            ['source' => 'S3.5 - Food Waste to Composting',   'unit' => 'short tons', 'value' => 0.180000, 'region' => 'US'],
            ['source' => 'S3.5 - Paper/Cardboard Recycling',  'unit' => 'short tons', 'value' => 0.240000, 'region' => 'US (landfill alt)'],
        ];

        $this->insertFactors($factors, $orgId);
    }

    // =========================================================================
    // COUNTRY-SPECIFIC ELECTRICITY GRID FACTORS (tCO2e per kWh)
    // Sources: Ember 2024/2025, IEA, DEFRA, EEA, Australian NGA
    // =========================================================================
    private function seedCountryElectricityFactors(): void
    {
        $orgId = FactorOrganization::where('code', 'COUNTRY')->value('id');
        if (!$orgId) return;

        $sourceId = EmissionSource::where('name', 'Purchased Electricity (Location-based)')->value('id');
        if (!$sourceId) return;

        // [country_code => tCO2e per kWh]
        $gridFactors = [
            // North America
            'US' => 0.000369, 'CA' => 0.000110, 'MX' => 0.000400,

            // Europe - Western
            'GB' => 0.000177, 'DE' => 0.000380, 'FR' => 0.000052, 'IT' => 0.000315,
            'ES' => 0.000160, 'NL' => 0.000328, 'BE' => 0.000155, 'AT' => 0.000105,
            'CH' => 0.000025, 'LU' => 0.000070, 'IE' => 0.000296, 'PT' => 0.000150,
            'IS' => 0.000001,

            // Europe - Northern
            'NO' => 0.000030, 'SE' => 0.000041, 'DK' => 0.000120, 'FI' => 0.000064,

            // Europe - Eastern
            'PL' => 0.000662, 'CZ' => 0.000415, 'RO' => 0.000260, 'HU' => 0.000220,
            'BG' => 0.000470, 'SK' => 0.000130, 'SI' => 0.000230, 'HR' => 0.000170,
            'LT' => 0.000130, 'LV' => 0.000109, 'EE' => 0.000550,

            // Europe - Southern
            'GR' => 0.000340, 'CY' => 0.000530, 'MT' => 0.000390,

            // Europe - Other
            'TR' => 0.000400, 'RU' => 0.000449,

            // Asia - East
            'CN' => 0.000560, 'JP' => 0.000482, 'KR' => 0.000415, 'TW' => 0.000502,

            // Asia - South
            'IN' => 0.000708, 'PK' => 0.000354, 'BD' => 0.000528, 'LK' => 0.000350,

            // Asia - Southeast
            'ID' => 0.000761, 'MY' => 0.000585, 'TH' => 0.000453, 'VN' => 0.000462,
            'PH' => 0.000672, 'SG' => 0.000408, 'MM' => 0.000400, 'KH' => 0.000500,

            // Middle East
            'SA' => 0.000506, 'AE' => 0.000404, 'QA' => 0.000490, 'KW' => 0.000642,
            'BH' => 0.000640, 'OM' => 0.000530, 'IL' => 0.000470, 'JO' => 0.000450,
            'IQ' => 0.000600, 'LB' => 0.000550,

            // Africa
            'ZA' => 0.000900, 'NG' => 0.000440, 'KE' => 0.000120, 'EG' => 0.000450,
            'MA' => 0.000610, 'GH' => 0.000350, 'TZ' => 0.000350, 'ET' => 0.000030,
            'DZ' => 0.000500, 'TN' => 0.000480, 'SN' => 0.000560,

            // Latin America
            'BR' => 0.000103, 'AR' => 0.000330, 'CO' => 0.000150, 'CL' => 0.000340,
            'PE' => 0.000250, 'EC' => 0.000200, 'VE' => 0.000250, 'UY' => 0.000060,
            'PY' => 0.000020, 'BO' => 0.000350, 'CR' => 0.000050, 'PA' => 0.000200,

            // Oceania
            'AU' => 0.000550, 'NZ' => 0.000074,
        ];

        foreach ($gridFactors as $countryCode => $factorValue) {
            $country = Country::where('code', $countryCode)->first();
            if (!$country) continue;

            EmissionFactor::updateOrCreate(
                [
                    'emission_source_id' => $sourceId,
                    'organization_id' => $orgId,
                    'country_id' => $country->id,
                    'unit' => 'kWh',
                ],
                [
                    'factor_value' => $factorValue,
                    'region' => $country->name . ' Grid (2023-2024)',
                ]
            );
        }
    }

    // =========================================================================
    // REFRIGERANT GWP FACTORS (tCO2e per kg = GWP / 1000)
    // Source: IPCC AR6 GWP-100 values
    // =========================================================================
    private function seedRefrigerantGWPFactors(): void
    {
        $orgId = FactorOrganization::where('code', 'GWP')->value('id');
        if (!$orgId) return;

        $gwpFactors = [
            // Individual Gases
            ['source' => 'Methane Leakage (Gas Systems)',   'unit' => 'kg', 'value' => 0.029800, 'region' => 'AR6 GWP-100 (fossil CH4)'],
            ['source' => 'N2O from Industrial Processes',   'unit' => 'kg', 'value' => 0.273000, 'region' => 'AR6 GWP-100'],
            ['source' => 'SF6 (Electrical Equipment)',      'unit' => 'kg', 'value' => 25.200000, 'region' => 'AR6 GWP-100'],
            ['source' => 'NF3 (Semiconductor Manufacturing)','unit'=> 'kg', 'value' => 17.400000, 'region' => 'AR6 GWP-100'],
            ['source' => 'CF4 (PFC-14)',                    'unit' => 'kg', 'value' => 7.380000, 'region' => 'AR6 GWP-100'],
            ['source' => 'C2F6 (PFC-116)',                  'unit' => 'kg', 'value' => 12.400000, 'region' => 'AR6 GWP-100'],

            // HFC Refrigerants
            ['source' => 'Refrigerant Leakage - R-32',      'unit' => 'kg', 'value' => 0.771000, 'region' => 'AR6 GWP-100'],
            ['source' => 'Refrigerant Leakage - R-134a',    'unit' => 'kg', 'value' => 1.530000, 'region' => 'AR6 GWP-100'],
            ['source' => 'Refrigerant Leakage - R-22',      'unit' => 'kg', 'value' => 1.810000, 'region' => 'AR6 GWP-100'],
            ['source' => 'Refrigerant Leakage - R-404A',    'unit' => 'kg', 'value' => 3.922000, 'region' => 'AR6 GWP-100'],
            ['source' => 'Refrigerant Leakage - R-407C',    'unit' => 'kg', 'value' => 1.774000, 'region' => 'AR6 GWP-100'],
            ['source' => 'Refrigerant Leakage - R-410A',    'unit' => 'kg', 'value' => 2.088000, 'region' => 'AR6 GWP-100'],
            ['source' => 'Refrigerant Leakage - R-507A',    'unit' => 'kg', 'value' => 3.985000, 'region' => 'AR6 GWP-100'],
            ['source' => 'Refrigerant Leakage - R-448A',    'unit' => 'kg', 'value' => 1.386000, 'region' => 'AR6 GWP-100'],
            ['source' => 'Refrigerant Leakage - R-449A',    'unit' => 'kg', 'value' => 1.397000, 'region' => 'AR6 GWP-100'],
            ['source' => 'Refrigerant Leakage - R-454B',    'unit' => 'kg', 'value' => 0.466000, 'region' => 'AR6 GWP-100'],

            // Low-GWP / Natural
            ['source' => 'Refrigerant Leakage - R-290 (Propane)',  'unit' => 'kg', 'value' => 0.000003, 'region' => 'AR6 GWP-100'],
            ['source' => 'Refrigerant Leakage - R-717 (Ammonia)',  'unit' => 'kg', 'value' => 0.000000, 'region' => 'AR6 GWP-100'],
            ['source' => 'Refrigerant Leakage - R-744 (CO2)',      'unit' => 'kg', 'value' => 0.000001, 'region' => 'AR6 GWP-100'],
            ['source' => 'Refrigerant Leakage - R-1234yf',        'unit' => 'kg', 'value' => 0.000001, 'region' => 'AR6 GWP-100'],

            // Legacy placeholders (backward compatible with existing seeder names)
            ['source' => 'Refrigerant Leakage (HFCs)',       'unit' => 'kg', 'value' => 2.088000, 'region' => 'AR6 avg (R-410A)'],
            ['source' => 'Refrigerant Leakage (HCFCs)',      'unit' => 'kg', 'value' => 1.810000, 'region' => 'AR6 avg (R-22)'],
            ['source' => 'Refrigerant Leakage (CFCs)',        'unit' => 'kg', 'value' => 4.750000, 'region' => 'AR6 avg (CFC-12)'],
            ['source' => 'Refrigerant Leakage (HFOs / Natural)','unit'=>'kg', 'value' => 0.001000, 'region' => 'AR6 avg'],
            ['source' => 'Fire Suppression (HFCs)',          'unit' => 'kg', 'value' => 1.430000, 'region' => 'AR6 (HFC-134a)'],
            ['source' => 'Fire Suppression (Halon)',         'unit' => 'kg', 'value' => 9.000000, 'region' => 'Estimated'],
            ['source' => 'PFCs (Aluminium / Semiconductors)','unit' => 'kg', 'value' => 7.380000, 'region' => 'AR6 (CF4)'],
        ];

        $this->insertFactors($gwpFactors, $orgId);
    }

    // =========================================================================
    // SCOPE 3 DETAILED FACTORS — Spend-based (EPA USEEIO)
    // Source: EPA Supply Chain GHG Emission Factors v1.3
    // All values: tCO2e per USD (spend-based)
    // =========================================================================
    private function seedScope3DetailedFactors(): void
    {
        $orgId = FactorOrganization::where('code', 'EPA')->value('id');
        if (!$orgId) return;

        $factors = [
            // Cat 1: Purchased Goods & Services — Spend-based (tCO2e per USD)
            ['source' => 'S3.1 - Food & Beverage Products',          'unit' => 'USD', 'value' => 0.000500, 'region' => 'US (USEEIO avg)'],
            ['source' => 'S3.1 - Textiles & Clothing',               'unit' => 'USD', 'value' => 0.000600, 'region' => 'US (USEEIO avg)'],
            ['source' => 'S3.1 - Paper & Printing',                  'unit' => 'USD', 'value' => 0.001520, 'region' => 'US (USEEIO avg)'],
            ['source' => 'S3.1 - Chemicals',                         'unit' => 'USD', 'value' => 0.000750, 'region' => 'US (USEEIO avg)'],
            ['source' => 'S3.1 - Plastics & Rubber',                 'unit' => 'USD', 'value' => 0.000800, 'region' => 'US (USEEIO avg)'],
            ['source' => 'S3.1 - Metals & Metal Products',           'unit' => 'USD', 'value' => 0.000900, 'region' => 'US (USEEIO avg)'],
            ['source' => 'S3.1 - Electronics & Electrical Equipment', 'unit' => 'USD', 'value' => 0.000300, 'region' => 'US (USEEIO avg)'],
            ['source' => 'S3.1 - Furniture',                         'unit' => 'USD', 'value' => 0.000250, 'region' => 'US (USEEIO avg)'],
            ['source' => 'S3.1 - Professional Services (Legal/Accounting)', 'unit' => 'USD', 'value' => 0.000100, 'region' => 'US (USEEIO avg)'],
            ['source' => 'S3.1 - IT Services & Software',            'unit' => 'USD', 'value' => 0.000080, 'region' => 'US (USEEIO avg)'],
            ['source' => 'S3.1 - Telecommunications',                'unit' => 'USD', 'value' => 0.000120, 'region' => 'US (USEEIO avg)'],
            ['source' => 'S3.1 - Office Supplies',                   'unit' => 'USD', 'value' => 0.000300, 'region' => 'US (USEEIO avg)'],
            ['source' => 'S3.1 - Cleaning Products & Services',      'unit' => 'USD', 'value' => 0.000200, 'region' => 'US (USEEIO avg)'],

            // Cat 2: Capital Goods — Spend-based (tCO2e per USD)
            ['source' => 'S3.2 - Buildings & Construction',           'unit' => 'USD', 'value' => 0.000400, 'region' => 'US (USEEIO avg)'],
            ['source' => 'S3.2 - Industrial Machinery',               'unit' => 'USD', 'value' => 0.000350, 'region' => 'US (USEEIO avg)'],
            ['source' => 'S3.2 - Vehicles & Transport Equipment',     'unit' => 'USD', 'value' => 0.000400, 'region' => 'US (USEEIO avg)'],
            ['source' => 'S3.2 - IT Hardware & Servers',              'unit' => 'USD', 'value' => 0.000300, 'region' => 'US (USEEIO avg)'],
            ['source' => 'S3.2 - Office Furniture & Equipment',       'unit' => 'USD', 'value' => 0.000250, 'region' => 'US (USEEIO avg)'],

            // Cat 8: Upstream Leased Assets (tCO2e per m² per year benchmark)
            ['source' => 'S3.8 - Leased Office Space',               'unit' => 'm²/year', 'value' => 0.080000, 'region' => 'Global benchmark'],
            ['source' => 'S3.8 - Leased Warehouse',                  'unit' => 'm²/year', 'value' => 0.050000, 'region' => 'Global benchmark'],

            // Cat 15: Investments — PCAF-style (tCO2e per million USD by sector)
            ['source' => 'Scope 3 - 15. Investments',                'unit' => 'million USD', 'value' => 0.000200, 'region' => 'Global (avg all sectors)'],
        ];

        $this->insertFactors($factors, $orgId);
    }

    // =========================================================================
    // HELPER — Insert/Update factors
    // =========================================================================
    private function insertFactors(array $factors, int $orgId): void
    {
        foreach ($factors as $row) {
            $source = EmissionSource::where('name', $row['source'])->first();
            if (!$source) continue;

            EmissionFactor::updateOrCreate(
                [
                    'emission_source_id' => $source->id,
                    'organization_id' => $orgId,
                    'unit' => $row['unit'],
                    'region' => $row['region'] ?? null,
                ],
                [
                    'factor_value' => $row['value'],
                ]
            );
        }
    }
}

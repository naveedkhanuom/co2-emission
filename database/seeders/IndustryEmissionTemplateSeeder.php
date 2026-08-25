<?php

namespace Database\Seeders;

use App\Models\IndustryEmissionTemplate;
use Illuminate\Database\Seeder;

class IndustryEmissionTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            // Manufacturing Industry
            ['industry_type' => 'manufacturing', 'name' => 'Natural Gas Combustion', 'scope' => 1, 'emission_source' => 'Natural Gas', 'unit' => 'm³', 'default_factor' => 0.00196, 'priority' => 1],
            ['industry_type' => 'manufacturing', 'name' => 'Diesel Fuel', 'scope' => 1, 'emission_source' => 'Diesel', 'unit' => 'L', 'default_factor' => 0.00268, 'priority' => 2],
            ['industry_type' => 'manufacturing', 'name' => 'Electricity Consumption', 'scope' => 2, 'emission_source' => 'Electricity', 'unit' => 'kWh', 'default_factor' => 0.000527, 'priority' => 1],
            ['industry_type' => 'manufacturing', 'name' => 'Steam Production', 'scope' => 2, 'emission_source' => 'Steam', 'unit' => 'MJ', 'default_factor' => 0.00005, 'priority' => 2],
            ['industry_type' => 'manufacturing', 'name' => 'Waste Disposal', 'scope' => 3, 'emission_source' => 'Waste', 'unit' => 'kg', 'default_factor' => 0.0005, 'priority' => 1],
            ['industry_type' => 'manufacturing', 'name' => 'Transportation', 'scope' => 3, 'emission_source' => 'Transportation', 'unit' => 'km', 'default_factor' => 0.00015, 'priority' => 2],

            // Energy Industry
            ['industry_type' => 'energy', 'name' => 'Natural Gas Combustion', 'scope' => 1, 'emission_source' => 'Natural Gas', 'unit' => 'm³', 'default_factor' => 0.00196, 'priority' => 1],
            ['industry_type' => 'energy', 'name' => 'Coal Combustion', 'scope' => 1, 'emission_source' => 'Coal', 'unit' => 'kg', 'default_factor' => 0.0025, 'priority' => 2],
            ['industry_type' => 'energy', 'name' => 'Electricity Grid', 'scope' => 2, 'emission_source' => 'Electricity', 'unit' => 'kWh', 'default_factor' => 0.000527, 'priority' => 1],
            ['industry_type' => 'energy', 'name' => 'Fugitive Emissions', 'scope' => 1, 'emission_source' => 'Fugitive', 'unit' => 'kg', 'default_factor' => 0.0279, 'priority' => 3, 'description' => 'Vented and leaked methane. Uses the AR6 100-year GWP for CH4 (27.9).'],

            // Transportation Industry
            ['industry_type' => 'transportation', 'name' => 'Diesel Fuel', 'scope' => 1, 'emission_source' => 'Diesel', 'unit' => 'L', 'default_factor' => 0.00268, 'priority' => 1],
            ['industry_type' => 'transportation', 'name' => 'Gasoline', 'scope' => 1, 'emission_source' => 'Gasoline', 'unit' => 'L', 'default_factor' => 0.00231, 'priority' => 2],
            ['industry_type' => 'transportation', 'name' => 'Electricity for EVs', 'scope' => 2, 'emission_source' => 'Electricity', 'unit' => 'kWh', 'default_factor' => 0.000527, 'priority' => 1],
            ['industry_type' => 'transportation', 'name' => 'Business Travel', 'scope' => 3, 'emission_source' => 'Business Travel', 'unit' => 'km', 'default_factor' => 0.000255, 'priority' => 1],

            // Agriculture Industry
            ['industry_type' => 'agriculture', 'name' => 'Diesel Fuel', 'scope' => 1, 'emission_source' => 'Diesel', 'unit' => 'L', 'default_factor' => 0.00268, 'priority' => 1],
            ['industry_type' => 'agriculture', 'name' => 'Fertilizer Application', 'scope' => 1, 'emission_source' => 'Fertilizer', 'unit' => 'kg', 'default_factor' => 0.002, 'priority' => 2],
            ['industry_type' => 'agriculture', 'name' => 'Livestock Emissions', 'scope' => 1, 'emission_source' => 'Livestock', 'unit' => 'head', 'default_factor' => 2.5, 'priority' => 3],
            ['industry_type' => 'agriculture', 'name' => 'Electricity', 'scope' => 2, 'emission_source' => 'Electricity', 'unit' => 'kWh', 'default_factor' => 0.000527, 'priority' => 1],

            // Construction Industry
            ['industry_type' => 'construction', 'name' => 'Diesel Fuel', 'scope' => 1, 'emission_source' => 'Diesel', 'unit' => 'L', 'default_factor' => 0.00268, 'priority' => 1],
            ['industry_type' => 'construction', 'name' => 'Natural Gas', 'scope' => 1, 'emission_source' => 'Natural Gas', 'unit' => 'm³', 'default_factor' => 0.00196, 'priority' => 2],
            ['industry_type' => 'construction', 'name' => 'Electricity', 'scope' => 2, 'emission_source' => 'Electricity', 'unit' => 'kWh', 'default_factor' => 0.000527, 'priority' => 1],
            ['industry_type' => 'construction', 'name' => 'Material Transportation', 'scope' => 3, 'emission_source' => 'Transportation', 'unit' => 'km', 'default_factor' => 0.00015, 'priority' => 1],
            ['industry_type' => 'construction', 'name' => 'Waste Disposal', 'scope' => 3, 'emission_source' => 'Waste', 'unit' => 'kg', 'default_factor' => 0.0005, 'priority' => 2],

            // Retail Industry
            ['industry_type' => 'retail', 'name' => 'Natural Gas', 'scope' => 1, 'emission_source' => 'Natural Gas', 'unit' => 'm³', 'default_factor' => 0.00196, 'priority' => 1],
            ['industry_type' => 'retail', 'name' => 'Electricity', 'scope' => 2, 'emission_source' => 'Electricity', 'unit' => 'kWh', 'default_factor' => 0.000527, 'priority' => 1],
            ['industry_type' => 'retail', 'name' => 'Refrigerants', 'scope' => 1, 'emission_source' => 'Refrigerants', 'unit' => 'kg', 'default_factor' => 2.088, 'priority' => 2, 'description' => 'Leakage from display cabinets and HVAC. Defaults to R-410A; pick the actual gas from the factor library.'],
            ['industry_type' => 'retail', 'name' => 'Employee Commute', 'scope' => 3, 'emission_source' => 'Employee Commute', 'unit' => 'km', 'default_factor' => 0.00012, 'priority' => 1],
            ['industry_type' => 'retail', 'name' => 'Product Transportation', 'scope' => 3, 'emission_source' => 'Transportation', 'unit' => 'km', 'default_factor' => 0.00015, 'priority' => 2],

            // Technology Industry
            ['industry_type' => 'technology', 'name' => 'Natural Gas', 'scope' => 1, 'emission_source' => 'Natural Gas', 'unit' => 'm³', 'default_factor' => 0.00196, 'priority' => 1],
            ['industry_type' => 'technology', 'name' => 'Electricity', 'scope' => 2, 'emission_source' => 'Electricity', 'unit' => 'kWh', 'default_factor' => 0.000527, 'priority' => 1],
            ['industry_type' => 'technology', 'name' => 'Business Travel', 'scope' => 3, 'emission_source' => 'Business Travel', 'unit' => 'km', 'default_factor' => 0.000255, 'priority' => 1],
            ['industry_type' => 'technology', 'name' => 'Employee Commute', 'scope' => 3, 'emission_source' => 'Employee Commute', 'unit' => 'km', 'default_factor' => 0.00012, 'priority' => 2],
            ['industry_type' => 'technology', 'name' => 'Cloud Services', 'scope' => 3, 'emission_source' => 'Cloud Services', 'unit' => 'kWh', 'default_factor' => 0.000527, 'priority' => 3],

            // Food & Beverage Industry
            ['industry_type' => 'food_beverage', 'name' => 'Natural Gas', 'scope' => 1, 'emission_source' => 'Natural Gas', 'unit' => 'm³', 'default_factor' => 0.00196, 'priority' => 1],
            ['industry_type' => 'food_beverage', 'name' => 'Diesel Fuel', 'scope' => 1, 'emission_source' => 'Diesel', 'unit' => 'L', 'default_factor' => 0.00268, 'priority' => 2],
            ['industry_type' => 'food_beverage', 'name' => 'Electricity', 'scope' => 2, 'emission_source' => 'Electricity', 'unit' => 'kWh', 'default_factor' => 0.000527, 'priority' => 1],
            ['industry_type' => 'food_beverage', 'name' => 'Refrigerants', 'scope' => 1, 'emission_source' => 'Refrigerants', 'unit' => 'kg', 'default_factor' => 2.088, 'priority' => 3, 'description' => 'Cold-chain and process refrigeration leakage. Defaults to R-410A; pick the actual gas from the factor library.'],
            ['industry_type' => 'food_beverage', 'name' => 'Waste Disposal', 'scope' => 3, 'emission_source' => 'Waste', 'unit' => 'kg', 'default_factor' => 0.0005, 'priority' => 1],
            ['industry_type' => 'food_beverage', 'name' => 'Transportation', 'scope' => 3, 'emission_source' => 'Transportation', 'unit' => 'km', 'default_factor' => 0.00015, 'priority' => 2],

            // General templates for all industries
            ['industry_type' => 'other', 'name' => 'Natural Gas', 'scope' => 1, 'emission_source' => 'Natural Gas', 'unit' => 'm³', 'default_factor' => 0.00196, 'priority' => 1],
            ['industry_type' => 'other', 'name' => 'Diesel Fuel', 'scope' => 1, 'emission_source' => 'Diesel', 'unit' => 'L', 'default_factor' => 0.00268, 'priority' => 2],
            ['industry_type' => 'other', 'name' => 'Gasoline', 'scope' => 1, 'emission_source' => 'Gasoline', 'unit' => 'L', 'default_factor' => 0.00231, 'priority' => 3],
            ['industry_type' => 'other', 'name' => 'Electricity', 'scope' => 2, 'emission_source' => 'Electricity', 'unit' => 'kWh', 'default_factor' => 0.000527, 'priority' => 1],
            ['industry_type' => 'other', 'name' => 'Business Travel', 'scope' => 3, 'emission_source' => 'Business Travel', 'unit' => 'km', 'default_factor' => 0.000255, 'priority' => 1],
            ['industry_type' => 'other', 'name' => 'Employee Commute', 'scope' => 3, 'emission_source' => 'Employee Commute', 'unit' => 'km', 'default_factor' => 0.00012, 'priority' => 2],
            ['industry_type' => 'other', 'name' => 'Waste Disposal', 'scope' => 3, 'emission_source' => 'Waste', 'unit' => 'kg', 'default_factor' => 0.0005, 'priority' => 3],

            // ---------------------------------------------------------------
            // Healthcare — the distinctive source is anaesthetic/medical gases,
            // which most hospitals never think to measure despite very high GWPs.
            // ---------------------------------------------------------------
            ['industry_type' => 'healthcare', 'name' => 'Anaesthetic Gases', 'scope' => 1, 'emission_source' => 'Anaesthetic Gases', 'unit' => 'kg', 'default_factor' => 2.54, 'priority' => 1, 'description' => 'Desflurane, sevoflurane, nitrous oxide vented from theatres. Very high GWP and almost always missed.'],
            ['industry_type' => 'healthcare', 'name' => 'Medical Refrigerants', 'scope' => 1, 'emission_source' => 'Refrigerants', 'unit' => 'kg', 'default_factor' => 2.088, 'priority' => 2, 'description' => 'Leakage from cold-chain storage, vaccine fridges, chillers and HVAC.'],
            ['industry_type' => 'healthcare', 'name' => 'Backup Generators (Diesel)', 'scope' => 1, 'emission_source' => 'Diesel', 'unit' => 'L', 'default_factor' => 0.00268, 'priority' => 3, 'description' => 'Standby generators — mandatory in hospitals, run during tests and outages.'],
            ['industry_type' => 'healthcare', 'name' => 'Ambulance Fleet', 'scope' => 1, 'emission_source' => 'Fleet - Diesel', 'unit' => 'L', 'default_factor' => 0.00268, 'priority' => 4],
            ['industry_type' => 'healthcare', 'name' => 'Electricity Consumption', 'scope' => 2, 'emission_source' => 'Electricity', 'unit' => 'kWh', 'default_factor' => 0.000527, 'priority' => 1, 'description' => 'Continuous load — imaging, theatres, HVAC and sterilisation run 24/7.'],
            ['industry_type' => 'healthcare', 'name' => 'Medical Consumables & Pharmaceuticals', 'scope' => 3, 'emission_source' => 'Purchased Goods & Services', 'unit' => 'AED', 'default_factor' => 0.00035, 'priority' => 1, 'description' => 'Usually the largest single share of a hospital footprint. Spend-based is an acceptable start.'],
            ['industry_type' => 'healthcare', 'name' => 'Clinical Waste Treatment', 'scope' => 3, 'emission_source' => 'Waste', 'unit' => 'kg', 'default_factor' => 0.0012, 'priority' => 2, 'description' => 'Incinerated clinical waste carries a much higher factor than general waste.'],
            ['industry_type' => 'healthcare', 'name' => 'Patient & Visitor Travel', 'scope' => 3, 'emission_source' => 'Employee Commute', 'unit' => 'km', 'default_factor' => 0.00012, 'priority' => 3],

            // ---------------------------------------------------------------
            // Hospitality — guest-night driven; laundry and food are the hotspots.
            // ---------------------------------------------------------------
            ['industry_type' => 'hospitality', 'name' => 'Natural Gas (Kitchens & Hot Water)', 'scope' => 1, 'emission_source' => 'Natural Gas', 'unit' => 'm³', 'default_factor' => 0.00196, 'priority' => 1],
            ['industry_type' => 'hospitality', 'name' => 'Refrigerants (HVAC & Cold Rooms)', 'scope' => 1, 'emission_source' => 'Refrigerants', 'unit' => 'kg', 'default_factor' => 2.088, 'priority' => 2, 'description' => 'Large chiller plant plus kitchen cold rooms — leakage is material in hot climates.'],
            ['industry_type' => 'hospitality', 'name' => 'Guest Shuttle Fleet', 'scope' => 1, 'emission_source' => 'Fleet - Gasoline', 'unit' => 'L', 'default_factor' => 0.00231, 'priority' => 3],
            ['industry_type' => 'hospitality', 'name' => 'Electricity Consumption', 'scope' => 2, 'emission_source' => 'Electricity', 'unit' => 'kWh', 'default_factor' => 0.000527, 'priority' => 1, 'description' => 'Air-conditioning dominates in Gulf climates.'],
            ['industry_type' => 'hospitality', 'name' => 'District Cooling', 'scope' => 2, 'emission_source' => 'District Cooling', 'unit' => 'kWh', 'default_factor' => 0.000385, 'priority' => 2, 'description' => 'Common in UAE developments — billed in refrigeration tonne-hours or kWh.'],
            ['industry_type' => 'hospitality', 'name' => 'Food & Beverage Procurement', 'scope' => 3, 'emission_source' => 'Purchased Goods & Services', 'unit' => 'AED', 'default_factor' => 0.0008, 'priority' => 1, 'description' => 'Typically the largest upstream category for hotels and restaurants.'],
            ['industry_type' => 'hospitality', 'name' => 'Outsourced Laundry', 'scope' => 3, 'emission_source' => 'Purchased Goods & Services', 'unit' => 'kg', 'default_factor' => 0.0009, 'priority' => 2],
            ['industry_type' => 'hospitality', 'name' => 'Food Waste', 'scope' => 3, 'emission_source' => 'Waste', 'unit' => 'kg', 'default_factor' => 0.0006, 'priority' => 3],

            // ---------------------------------------------------------------
            // Mining — fugitive methane and off-road diesel dominate.
            // ---------------------------------------------------------------
            ['industry_type' => 'mining', 'name' => 'Off-road Mining Equipment (Diesel)', 'scope' => 1, 'emission_source' => 'Off-road Machinery - Diesel', 'unit' => 'L', 'default_factor' => 0.00268, 'priority' => 1, 'description' => 'Haul trucks, excavators, drills — usually the single largest Scope 1 source.'],
            ['industry_type' => 'mining', 'name' => 'Fugitive Methane from Extraction', 'scope' => 1, 'emission_source' => 'Methane Leakage (Gas Systems)', 'unit' => 'kg', 'default_factor' => 0.0279, 'priority' => 2, 'description' => 'Seam gas released during extraction. Material for coal; requires measurement, not estimation.'],
            ['industry_type' => 'mining', 'name' => 'Explosives Use', 'scope' => 1, 'emission_source' => 'N2O from Industrial Processes', 'unit' => 'kg', 'default_factor' => 0.00017, 'priority' => 3, 'description' => 'CO2 and N2O released from blasting agents (ANFO).'],
            ['industry_type' => 'mining', 'name' => 'Electricity (Processing Plant)', 'scope' => 2, 'emission_source' => 'Electricity', 'unit' => 'kWh', 'default_factor' => 0.000527, 'priority' => 1, 'description' => 'Crushing, milling and separation are highly power-intensive.'],
            ['industry_type' => 'mining', 'name' => 'Ore & Product Haulage', 'scope' => 3, 'emission_source' => 'Downstream Transportation & Distribution', 'unit' => 'tonne-km', 'default_factor' => 0.00011, 'priority' => 1],
            ['industry_type' => 'mining', 'name' => 'Processing of Sold Products', 'scope' => 3, 'emission_source' => 'Processing of Sold Products', 'unit' => 'tonne', 'default_factor' => 0.5, 'priority' => 2, 'description' => 'Smelting or refining performed by the buyer of your ore.'],

            // ---------------------------------------------------------------
            // Chemicals — process emissions are separate from combustion and are
            // routinely under-reported.
            // ---------------------------------------------------------------
            ['industry_type' => 'chemical', 'name' => 'Process Emissions (Non-combustion)', 'scope' => 1, 'emission_source' => 'N2O from Industrial Processes', 'unit' => 'tonne', 'default_factor' => 1.0, 'priority' => 1, 'description' => 'CO2/N2O released by the chemical reaction itself, not by burning fuel. Must be counted separately from combustion.'],
            ['industry_type' => 'chemical', 'name' => 'Natural Gas as Feedstock & Fuel', 'scope' => 1, 'emission_source' => 'Natural Gas', 'unit' => 'm³', 'default_factor' => 0.00196, 'priority' => 2, 'description' => 'Split feedstock use from combustion use — they are reported differently.'],
            ['industry_type' => 'chemical', 'name' => 'Fugitive Emissions (Flanges, Valves, Flares)', 'scope' => 1, 'emission_source' => 'Fugitive', 'unit' => 'kg', 'default_factor' => 0.0279, 'priority' => 3],
            ['industry_type' => 'chemical', 'name' => 'Electricity Consumption', 'scope' => 2, 'emission_source' => 'Electricity', 'unit' => 'kWh', 'default_factor' => 0.000527, 'priority' => 1],
            ['industry_type' => 'chemical', 'name' => 'Purchased Steam', 'scope' => 2, 'emission_source' => 'Purchased Steam', 'unit' => 'MJ', 'default_factor' => 0.00007, 'priority' => 2],
            ['industry_type' => 'chemical', 'name' => 'Raw Material Feedstocks', 'scope' => 3, 'emission_source' => 'Purchased Goods & Services', 'unit' => 'tonne', 'default_factor' => 1.8, 'priority' => 1],
            ['industry_type' => 'chemical', 'name' => 'Use of Sold Products', 'scope' => 3, 'emission_source' => 'Use of Sold Products', 'unit' => 'tonne', 'default_factor' => 2.0, 'priority' => 2, 'description' => 'Required where a sold chemical releases GHGs in use (e.g. refrigerant gases, solvents).'],

            // ---------------------------------------------------------------
            // Textiles — the footprint sits almost entirely in purchased fabric
            // and in outsourced wet processing.
            // ---------------------------------------------------------------
            ['industry_type' => 'textile', 'name' => 'Natural Gas (Boilers & Dyeing)', 'scope' => 1, 'emission_source' => 'Natural Gas', 'unit' => 'm³', 'default_factor' => 0.00196, 'priority' => 1, 'description' => 'Steam for dyeing and finishing is the main on-site fuel use.'],
            ['industry_type' => 'textile', 'name' => 'Diesel Generators', 'scope' => 1, 'emission_source' => 'Diesel', 'unit' => 'L', 'default_factor' => 0.00268, 'priority' => 2],
            ['industry_type' => 'textile', 'name' => 'Electricity Consumption', 'scope' => 2, 'emission_source' => 'Electricity', 'unit' => 'kWh', 'default_factor' => 0.000527, 'priority' => 1],
            ['industry_type' => 'textile', 'name' => 'Purchased Fabric & Yarn', 'scope' => 3, 'emission_source' => 'Purchased Goods & Services', 'unit' => 'kg', 'default_factor' => 0.0155, 'priority' => 1, 'description' => 'Usually 60-80% of a textile footprint. Cotton, polyester and viscose differ sharply.'],
            ['industry_type' => 'textile', 'name' => 'Outsourced Dyeing & Finishing', 'scope' => 3, 'emission_source' => 'Purchased Goods & Services', 'unit' => 'kg', 'default_factor' => 0.0045, 'priority' => 2, 'description' => 'Wet processing done by a subcontractor stays in your Scope 3.'],
            ['industry_type' => 'textile', 'name' => 'Wastewater Treatment', 'scope' => 3, 'emission_source' => 'Waste', 'unit' => 'm³', 'default_factor' => 0.00027, 'priority' => 3],
            ['industry_type' => 'textile', 'name' => 'End-of-Life of Sold Garments', 'scope' => 3, 'emission_source' => 'End-of-Life Treatment of Sold Products', 'unit' => 'kg', 'default_factor' => 0.0005, 'priority' => 4],

            // ---------------------------------------------------------------
            // Education — campus energy plus commuting; commuting is often the
            // single biggest line and is always forgotten.
            // ---------------------------------------------------------------
            ['industry_type' => 'education', 'name' => 'Heating Fuel / Natural Gas', 'scope' => 1, 'emission_source' => 'Natural Gas', 'unit' => 'm³', 'default_factor' => 0.00196, 'priority' => 1],
            ['industry_type' => 'education', 'name' => 'School Bus Fleet', 'scope' => 1, 'emission_source' => 'Fleet - Diesel', 'unit' => 'L', 'default_factor' => 0.00268, 'priority' => 2],
            ['industry_type' => 'education', 'name' => 'Electricity Consumption', 'scope' => 2, 'emission_source' => 'Electricity', 'unit' => 'kWh', 'default_factor' => 0.000527, 'priority' => 1],
            ['industry_type' => 'education', 'name' => 'Student & Staff Commuting', 'scope' => 3, 'emission_source' => 'Employee Commute', 'unit' => 'km', 'default_factor' => 0.00012, 'priority' => 1, 'description' => 'Frequently the largest category for a campus, and the most commonly omitted.'],
            ['industry_type' => 'education', 'name' => 'Purchased Goods & Catering', 'scope' => 3, 'emission_source' => 'Purchased Goods & Services', 'unit' => 'AED', 'default_factor' => 0.00035, 'priority' => 2],
            ['industry_type' => 'education', 'name' => 'Academic Travel', 'scope' => 3, 'emission_source' => 'Business Travel', 'unit' => 'km', 'default_factor' => 0.000255, 'priority' => 3, 'description' => 'Conference and field-trip flights; material for research institutions.'],
            ['industry_type' => 'education', 'name' => 'Waste Disposal', 'scope' => 3, 'emission_source' => 'Waste', 'unit' => 'kg', 'default_factor' => 0.0005, 'priority' => 4],

            // ---------------------------------------------------------------
            // Finance — operational emissions are trivial; financed emissions
            // (category 15) are typically >95% of the footprint.
            // ---------------------------------------------------------------
            ['industry_type' => 'finance', 'name' => 'Backup Generators', 'scope' => 1, 'emission_source' => 'Diesel', 'unit' => 'L', 'default_factor' => 0.00268, 'priority' => 1],
            ['industry_type' => 'finance', 'name' => 'Refrigerants (Office HVAC)', 'scope' => 1, 'emission_source' => 'Refrigerants', 'unit' => 'kg', 'default_factor' => 2.088, 'priority' => 2],
            ['industry_type' => 'finance', 'name' => 'Electricity (Offices & Data Centres)', 'scope' => 2, 'emission_source' => 'Electricity', 'unit' => 'kWh', 'default_factor' => 0.000527, 'priority' => 1],
            ['industry_type' => 'finance', 'name' => 'Financed Emissions (Investments)', 'scope' => 3, 'emission_source' => 'Investments', 'unit' => 'AED', 'default_factor' => 0.00015, 'priority' => 1, 'description' => 'Loans, equity and bonds. Normally over 95% of a financial institution footprint — follow PCAF methodology.'],
            ['industry_type' => 'finance', 'name' => 'Business Travel', 'scope' => 3, 'emission_source' => 'Business Travel', 'unit' => 'km', 'default_factor' => 0.000255, 'priority' => 2],
            ['industry_type' => 'finance', 'name' => 'Employee Commuting', 'scope' => 3, 'emission_source' => 'Employee Commute', 'unit' => 'km', 'default_factor' => 0.00012, 'priority' => 3],
            ['industry_type' => 'finance', 'name' => 'Purchased IT Services', 'scope' => 3, 'emission_source' => 'Purchased Goods & Services', 'unit' => 'AED', 'default_factor' => 0.00025, 'priority' => 4],

            // ---------------------------------------------------------------
            // Sub-industry templates. These are ADDITIVE to the generic industry
            // rows above — getByIndustry() returns both when a sub-industry is
            // set. They exist because `industry_type` alone cannot separate a
            // car-rental firm from a shipping line, or a cement plant from a
            // general contractor.
            // ---------------------------------------------------------------

            // Car rental — the boundary question that decides everything is who
            // owns the emissions from customer driving. The fleet is owned, but
            // it is operated by the customer: GHG Protocol treats this as
            // downstream leased assets (category 13), NOT Scope 1.
            ['industry_type' => 'transportation', 'sub_industry' => 'car_rental', 'name' => 'Customer-Driven Rental Fleet', 'scope' => 3, 'emission_source' => 'Downstream Leased Assets', 'unit' => 'L', 'default_factor' => 0.00231, 'priority' => 1, 'description' => 'Fuel burned by customers in vehicles you own but they operate. Category 13 (downstream leased assets) — a common and costly misclassification as Scope 1.'],
            ['industry_type' => 'transportation', 'sub_industry' => 'car_rental', 'name' => 'Fleet Movements by Staff', 'scope' => 1, 'emission_source' => 'Fleet - Gasoline', 'unit' => 'L', 'default_factor' => 0.00231, 'priority' => 2, 'description' => 'Repositioning, valeting and delivery driven by your own staff IS Scope 1.'],
            ['industry_type' => 'transportation', 'sub_industry' => 'car_rental', 'name' => 'Vehicle Manufacturing (Fleet Purchases)', 'scope' => 3, 'emission_source' => 'Capital Goods', 'unit' => 'vehicle', 'default_factor' => 6.5, 'priority' => 3, 'description' => 'Embodied emissions of each vehicle added to the fleet. Material given short replacement cycles.'],
            ['industry_type' => 'transportation', 'sub_industry' => 'car_rental', 'name' => 'Branch Electricity', 'scope' => 2, 'emission_source' => 'Electricity', 'unit' => 'kWh', 'default_factor' => 0.000527, 'priority' => 4],
            ['industry_type' => 'transportation', 'sub_industry' => 'car_rental', 'name' => 'Vehicle Servicing & Parts', 'scope' => 3, 'emission_source' => 'Purchased Goods & Services', 'unit' => 'AED', 'default_factor' => 0.00025, 'priority' => 5],

            // Shipping / freight — bunker fuel dominates and is measured in tonnes.
            ['industry_type' => 'transportation', 'sub_industry' => 'shipping', 'name' => 'Marine Bunker Fuel (HFO)', 'scope' => 1, 'emission_source' => 'Marine Vessels - Heavy Fuel Oil', 'unit' => 'tonne', 'default_factor' => 3.114, 'priority' => 1],
            ['industry_type' => 'transportation', 'sub_industry' => 'shipping', 'name' => 'Marine Gas Oil', 'scope' => 1, 'emission_source' => 'Marine Vessels - Diesel', 'unit' => 'tonne', 'default_factor' => 3.206, 'priority' => 2],
            ['industry_type' => 'transportation', 'sub_industry' => 'shipping', 'name' => 'Chartered Vessels', 'scope' => 3, 'emission_source' => 'Upstream Transportation & Distribution', 'unit' => 'tonne-km', 'default_factor' => 0.00001, 'priority' => 3, 'description' => 'Vessels you charter but do not operate fall in Scope 3, not Scope 1.'],
            ['industry_type' => 'transportation', 'sub_industry' => 'shipping', 'name' => 'Port & Terminal Electricity', 'scope' => 2, 'emission_source' => 'Electricity', 'unit' => 'kWh', 'default_factor' => 0.000527, 'priority' => 4],

            // Cement — clinker calcination is roughly 60% of the footprint and is
            // NOT combustion. Companies routinely count only the kiln fuel.
            ['industry_type' => 'construction', 'sub_industry' => 'cement', 'name' => 'Clinker Calcination (Process CO2)', 'scope' => 1, 'emission_source' => 'N2O from Industrial Processes', 'unit' => 'tonne', 'default_factor' => 0.525, 'priority' => 1, 'description' => 'CO2 driven off limestone in the kiln. Around 60% of cement emissions and entirely separate from fuel combustion — the most-missed source in the industry.'],
            ['industry_type' => 'construction', 'sub_industry' => 'cement', 'name' => 'Kiln Fuel (Coal / Petcoke)', 'scope' => 1, 'emission_source' => 'Coal', 'unit' => 'tonne', 'default_factor' => 2.5, 'priority' => 2],
            ['industry_type' => 'construction', 'sub_industry' => 'cement', 'name' => 'Alternative Fuels (Waste-derived)', 'scope' => 1, 'emission_source' => 'Biomass', 'unit' => 'tonne', 'default_factor' => 1.2, 'priority' => 3, 'description' => 'Report the biogenic fraction separately from the fossil fraction.'],
            ['industry_type' => 'construction', 'sub_industry' => 'cement', 'name' => 'Quarry & Plant Electricity', 'scope' => 2, 'emission_source' => 'Electricity', 'unit' => 'kWh', 'default_factor' => 0.000527, 'priority' => 4, 'description' => 'Grinding is the dominant electrical load.'],
            ['industry_type' => 'construction', 'sub_industry' => 'cement', 'name' => 'Raw Material Transport', 'scope' => 3, 'emission_source' => 'Upstream Transportation & Distribution', 'unit' => 'tonne-km', 'default_factor' => 0.00011, 'priority' => 5],

            // General contracting — the materials are bought by, or through,
            // subcontractors, which is why contractors under-report category 1.
            ['industry_type' => 'construction', 'sub_industry' => 'contracting', 'name' => 'Purchased Concrete & Steel', 'scope' => 3, 'emission_source' => 'Purchased Goods & Services', 'unit' => 'tonne', 'default_factor' => 0.12, 'priority' => 1, 'description' => 'Embodied carbon of materials — normally the largest category by far. Counts even when a subcontractor buys it on your behalf.'],
            ['industry_type' => 'construction', 'sub_industry' => 'contracting', 'name' => 'Site Plant & Machinery (Diesel)', 'scope' => 1, 'emission_source' => 'Off-road Machinery - Diesel', 'unit' => 'L', 'default_factor' => 0.00268, 'priority' => 2],
            ['industry_type' => 'construction', 'sub_industry' => 'contracting', 'name' => 'Temporary Site Power (Generators)', 'scope' => 1, 'emission_source' => 'Diesel', 'unit' => 'L', 'default_factor' => 0.00268, 'priority' => 3],
            ['industry_type' => 'construction', 'sub_industry' => 'contracting', 'name' => 'Subcontracted Works', 'scope' => 3, 'emission_source' => 'Purchased Goods & Services', 'unit' => 'AED', 'default_factor' => 0.0003, 'priority' => 4, 'description' => 'Emissions of subcontractors working on your project remain in your Scope 3.'],
            ['industry_type' => 'construction', 'sub_industry' => 'contracting', 'name' => 'Construction & Demolition Waste', 'scope' => 3, 'emission_source' => 'Waste', 'unit' => 'tonne', 'default_factor' => 0.021, 'priority' => 5],

            // Data centres — PUE-driven power, plus backup diesel and refrigerants.
            ['industry_type' => 'technology', 'sub_industry' => 'data_centre', 'name' => 'IT Load Electricity', 'scope' => 2, 'emission_source' => 'Electricity', 'unit' => 'kWh', 'default_factor' => 0.000527, 'priority' => 1, 'description' => 'Report total facility power, not IT load alone — apply your measured PUE.'],
            ['industry_type' => 'technology', 'sub_industry' => 'data_centre', 'name' => 'Backup Generator Testing', 'scope' => 1, 'emission_source' => 'Diesel', 'unit' => 'L', 'default_factor' => 0.00268, 'priority' => 2],
            ['industry_type' => 'technology', 'sub_industry' => 'data_centre', 'name' => 'Cooling Refrigerant Leakage', 'scope' => 1, 'emission_source' => 'Refrigerants', 'unit' => 'kg', 'default_factor' => 2.088, 'priority' => 3],
            ['industry_type' => 'technology', 'sub_industry' => 'data_centre', 'name' => 'Server Hardware Purchases', 'scope' => 3, 'emission_source' => 'Capital Goods', 'unit' => 'AED', 'default_factor' => 0.0004, 'priority' => 4],
        ];

        foreach ($templates as $template) {
            // Keyed on name (not emission_source) because one industry can have
            // several distinct templates sharing a source — a hotel's food
            // procurement and its outsourced laundry are both "Purchased Goods
            // & Services" but are different lines on the checklist.
            IndustryEmissionTemplate::updateOrCreate(
                [
                    'industry_type' => $template['industry_type'],
                    'sub_industry' => $template['sub_industry'] ?? null,
                    'name' => $template['name'],
                    'scope' => $template['scope'],
                ],
                array_merge($template, [
                    'is_active' => true,
                    'source_reference' => $template['source_reference'] ?? 'GHG Protocol / Default Factors',
                ])
            );
        }

        $this->command->info('Seeded '.count($templates).' industry emission templates.');
    }
}

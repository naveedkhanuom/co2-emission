<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\IndustryEmissionTemplate;

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
            ['industry_type' => 'energy', 'name' => 'Fugitive Emissions', 'scope' => 1, 'emission_source' => 'Fugitive', 'unit' => 'kg', 'default_factor' => 0.001, 'priority' => 3],

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
            ['industry_type' => 'retail', 'name' => 'Refrigerants', 'scope' => 1, 'emission_source' => 'Refrigerants', 'unit' => 'kg', 'default_factor' => 0.001, 'priority' => 2],
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
            ['industry_type' => 'food_beverage', 'name' => 'Refrigerants', 'scope' => 1, 'emission_source' => 'Refrigerants', 'unit' => 'kg', 'default_factor' => 0.001, 'priority' => 3],
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
        ];

        foreach ($templates as $template) {
            IndustryEmissionTemplate::updateOrCreate(
                [
                    'industry_type' => $template['industry_type'],
                    'emission_source' => $template['emission_source'],
                    'scope' => $template['scope'],
                ],
                array_merge($template, [
                    'is_active' => true,
                    'source_reference' => 'GHG Protocol / Default Factors',
                ])
            );
        }

        $this->command->info('Industry emission templates seeded successfully!');
    }
}

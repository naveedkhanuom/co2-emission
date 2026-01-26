<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EioFactor;

class EioFactorsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Common EIO factors (example values - should be replaced with actual data from EPA, DEFRA, etc.)
        $factors = [
            // Manufacturing sectors
            ['sector_code' => 'MANUF', 'sector_name' => 'Manufacturing', 'country' => 'USA', 'currency' => 'USD', 'emission_factor' => 0.45, 'data_source' => 'EPA', 'year' => 2023],
            ['sector_code' => 'MANUF', 'sector_name' => 'Manufacturing', 'country' => 'GBR', 'currency' => 'GBP', 'emission_factor' => 0.35, 'data_source' => 'DEFRA', 'year' => 2023],
            
            // Services sectors
            ['sector_code' => 'SERV', 'sector_name' => 'Professional Services', 'country' => 'USA', 'currency' => 'USD', 'emission_factor' => 0.25, 'data_source' => 'EPA', 'year' => 2023],
            ['sector_code' => 'SERV', 'sector_name' => 'Professional Services', 'country' => 'GBR', 'currency' => 'GBP', 'emission_factor' => 0.20, 'data_source' => 'DEFRA', 'year' => 2023],
            
            // Office supplies
            ['sector_code' => 'OFFICE', 'sector_name' => 'Office Supplies', 'country' => 'USA', 'currency' => 'USD', 'emission_factor' => 0.40, 'data_source' => 'EPA', 'year' => 2023],
            
            // IT Equipment
            ['sector_code' => 'IT', 'sector_name' => 'IT Equipment', 'country' => 'USA', 'currency' => 'USD', 'emission_factor' => 0.55, 'data_source' => 'EPA', 'year' => 2023],
            
            // Transportation
            ['sector_code' => 'TRANS', 'sector_name' => 'Transportation Services', 'country' => 'USA', 'currency' => 'USD', 'emission_factor' => 0.60, 'data_source' => 'EPA', 'year' => 2023],
            
            // Food & Beverage
            ['sector_code' => 'FOOD', 'sector_name' => 'Food & Beverage', 'country' => 'USA', 'currency' => 'USD', 'emission_factor' => 0.50, 'data_source' => 'EPA', 'year' => 2023],
            
            // Construction
            ['sector_code' => 'CONST', 'sector_name' => 'Construction', 'country' => 'USA', 'currency' => 'USD', 'emission_factor' => 0.70, 'data_source' => 'EPA', 'year' => 2023],
            
            // Energy
            ['sector_code' => 'ENERGY', 'sector_name' => 'Energy Services', 'country' => 'USA', 'currency' => 'USD', 'emission_factor' => 0.80, 'data_source' => 'EPA', 'year' => 2023],
            
            // General/Default
            ['sector_code' => 'DEFAULT', 'sector_name' => 'Default/General', 'country' => 'USA', 'currency' => 'USD', 'emission_factor' => 0.40, 'data_source' => 'EPA', 'year' => 2023],
        ];

        foreach ($factors as $factor) {
            EioFactor::updateOrCreate(
                [
                    'sector_code' => $factor['sector_code'],
                    'country' => $factor['country'],
                    'year' => $factor['year'],
                ],
                array_merge($factor, [
                    'factor_unit' => 'kg_CO2e_per_USD',
                    'is_active' => true,
                ])
            );
        }

        $this->command->info('EIO factors seeded successfully!');
        $this->command->warn('Note: These are example factors. Replace with actual data from EPA, DEFRA, or other authoritative sources.');
    }
}

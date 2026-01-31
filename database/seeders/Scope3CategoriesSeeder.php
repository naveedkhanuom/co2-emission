<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Scope3Category;

class Scope3CategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            // Upstream Categories
            [
                'code' => '3.1',
                'name' => 'Purchased Goods and Services',
                'description' => 'Emissions from the production of goods and services purchased by the company. This includes raw materials, office supplies, IT equipment, and professional services.',
                'category_type' => 'upstream',
                'sort_order' => 1,
            ],
            [
                'code' => '3.2',
                'name' => 'Capital Goods',
                'description' => 'Emissions from the production of capital goods (long-term assets) purchased by the company. This includes buildings, machinery, vehicles, and IT infrastructure.',
                'category_type' => 'upstream',
                'sort_order' => 2,
            ],
            [
                'code' => '3.3',
                'name' => 'Fuel- and Energy-Related Activities',
                'description' => 'Emissions from the extraction, production, and transportation of fuels and energy purchased by the company, not included in Scope 1 or 2. Includes transmission and distribution losses.',
                'category_type' => 'upstream',
                'sort_order' => 3,
            ],
            [
                'code' => '3.4',
                'name' => 'Upstream Transportation and Distribution',
                'description' => 'Emissions from transportation and distribution of products purchased by the company (incoming logistics). Includes shipping, freight, and third-party logistics.',
                'category_type' => 'upstream',
                'sort_order' => 4,
            ],
            [
                'code' => '3.5',
                'name' => 'Waste Generated in Operations',
                'description' => 'Emissions from disposal and treatment of waste generated in operations. Includes solid waste, wastewater, hazardous waste, recycling, and incineration.',
                'category_type' => 'upstream',
                'sort_order' => 5,
            ],
            [
                'code' => '3.6',
                'name' => 'Business Travel',
                'description' => 'Emissions from transportation of employees for business purposes. Includes air travel, car rentals, train travel, and hotel stays.',
                'category_type' => 'upstream',
                'sort_order' => 6,
            ],
            [
                'code' => '3.7',
                'name' => 'Employee Commuting',
                'description' => 'Emissions from transportation of employees between their homes and workplaces. Includes personal vehicles, public transportation, and remote work considerations.',
                'category_type' => 'upstream',
                'sort_order' => 7,
            ],
            [
                'code' => '3.8',
                'name' => 'Upstream Leased Assets',
                'description' => 'Emissions from operation of assets leased by the company (not included in Scope 1 or 2). Includes leased vehicles, buildings, and equipment.',
                'category_type' => 'upstream',
                'sort_order' => 8,
            ],
            
            // Downstream Categories
            [
                'code' => '3.9',
                'name' => 'Downstream Transportation and Distribution',
                'description' => 'Emissions from transportation and distribution of products sold by the company (outgoing logistics). Includes shipping to customers, retail distribution, and e-commerce fulfillment.',
                'category_type' => 'downstream',
                'sort_order' => 9,
            ],
            [
                'code' => '3.10',
                'name' => 'Processing of Sold Products',
                'description' => 'Emissions from processing of intermediate products sold to other companies. Includes chemical processing and manufacturing components that are further processed.',
                'category_type' => 'downstream',
                'sort_order' => 10,
            ],
            [
                'code' => '3.11',
                'name' => 'Use of Sold Products',
                'description' => 'Emissions from use of products sold by the company. Includes fuel consumption of vehicles, electricity consumption of electronics, and energy use during product operation.',
                'category_type' => 'downstream',
                'sort_order' => 11,
            ],
            [
                'code' => '3.12',
                'name' => 'End-of-Life Treatment of Sold Products',
                'description' => 'Emissions from disposal and treatment of products sold by the company at end of life. Includes landfill disposal, incineration, recycling, and product take-back programs.',
                'category_type' => 'downstream',
                'sort_order' => 12,
            ],
            [
                'code' => '3.13',
                'name' => 'Downstream Leased Assets',
                'description' => 'Emissions from operation of assets owned by the company but leased to others. Includes leased vehicles, buildings, and equipment (finance leases).',
                'category_type' => 'downstream',
                'sort_order' => 13,
            ],
            [
                'code' => '3.14',
                'name' => 'Franchises',
                'description' => 'Emissions from operation of franchises. Includes fast-food franchises, retail franchises, and service franchises.',
                'category_type' => 'downstream',
                'sort_order' => 14,
            ],
            [
                'code' => '3.15',
                'name' => 'Investments',
                'description' => 'Emissions associated with investments (for financial institutions). Includes equity investments, debt investments, project finance, and managed investments.',
                'category_type' => 'downstream',
                'sort_order' => 15,
            ],
        ];

        foreach ($categories as $category) {
            Scope3Category::updateOrCreate(
                ['code' => $category['code']],
                array_merge($category, [
                    'is_active' => true,
                ])
            );
        }

        $this->command->info('Scope 3 categories seeded successfully!');
    }
}

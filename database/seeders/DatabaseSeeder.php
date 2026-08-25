<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            DefaultUserSeeder::class,
            CountriesSeeder::class,
            Scope3CategoriesSeeder::class,
            EmissionSourcesSeeder::class,
            FactorOrganizationsSeeder::class,
            EmissionFactorsSeeder::class,
            EioFactorsSeeder::class,
            // Sector emission-source templates. The Boundary Advisor uses these
            // as its candidate catalogue and as the no-API-key fallback, so a
            // fresh install needs them.
            IndustryEmissionTemplateSeeder::class,
            ComprehensiveEmissionDataSeeder::class,
        ]);
    }
}

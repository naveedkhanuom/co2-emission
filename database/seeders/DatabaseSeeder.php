<?php

namespace Database\Seeders;

use App\Models\User;
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
        ]);
    }
}
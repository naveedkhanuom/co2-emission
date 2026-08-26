<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeds the CENTRAL database — the tenant registry and your own business.
 *
 * Client data does not live here. Everything that used to be in this list now
 * runs per tenant, inside that tenant's database: see TenantDatabaseSeeder,
 * which the TenantCreated pipeline calls automatically, and which you can
 * re-run by hand with:
 *
 *     php artisan tenants:seed
 *     php artisan tenants:seed --tenants=acme
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Nothing central to seed yet. Plans and platform staff accounts land
        // here when billing arrives.
    }
}

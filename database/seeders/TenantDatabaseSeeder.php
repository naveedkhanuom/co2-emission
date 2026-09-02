<?php

namespace Database\Seeders;

use App\Support\Auditing;
use Illuminate\Database\Seeder;

/**
 * Everything a newly provisioned client account needs before anyone signs in.
 *
 * Runs inside the tenant's own database, via the SeedDatabase job in the
 * TenantCreated pipeline (see App\Providers\TenancyServiceProvider), so no
 * tenant can exist in an unseeded state.
 *
 * Deliberately excluded: DefaultUserSeeder. It creates four hardcoded accounts
 * with a shared, publicly known password — fine as a local dev fixture, a
 * serious breach on a real client's database. The account owner is created by
 * the tenant:provision command with real credentials instead.
 */
class TenantDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seeding writes ~485 reference rows, and with auditing live each one
        // produced an audit_logs entry with user_id = NULL — so a brand-new
        // client opened their change history and found 485 entries before they
        // had done anything. The audit trail is what an ISO 14064-3 assurer
        // reads; its first page should not be provisioning noise.
        //
        // Only auditing is suppressed. Model::withoutEvents() would also silence
        // HasCompanyScope's creating hook, which stamps company_id.
        Auditing::without(function () {
            $this->call([
                // Access control. Every tenant gets the same role and permission
                // structure; who holds which role is then their own business.
                PermissionSeeder::class,
                RoleSeeder::class,

                // Reference data shared by every company inside this tenant. A
                // group defines its factor set once, so its subsidiaries report
                // consistently against the same numbers.
                CountriesSeeder::class,
                Scope3CategoriesSeeder::class,
                EmissionSourcesSeeder::class,
                FactorOrganizationsSeeder::class,
                EmissionFactorsSeeder::class,
                EioFactorsSeeder::class,

                // Sector emission-source templates. The Boundary Advisor uses
                // these as its candidate catalogue and as the no-API-key
                // fallback, so a fresh tenant needs them.
                IndustryEmissionTemplateSeeder::class,

                // IPCC 2006/AR6, DEFRA/DESNZ 2025, US EPA 2025, Ember, IEA.
                // Reference data, not sample data.
                ComprehensiveEmissionDataSeeder::class,

                // Last, because it needs the roles above to exist. Skips itself
                // when disabled or unconfigured — read the warning in
                // config/tenant_defaults.php before enabling it in production.
                DeveloperAccountSeeder::class,
            ]);
        });
    }
}

<?php

namespace Tests;

use App\Jobs\CreateTenantStorage;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\URL;

/**
 * Base class for tests that exercise the application itself.
 *
 * The application is served only on a tenant subdomain — routes/web.php is
 * loaded by routes/tenant.php behind subdomain identification — so a test
 * that hits an app route has to be inside a tenant, on that tenant's host.
 * Extending TestCase directly gets you the central domain, where none of
 * those routes exist.
 *
 * The test tenant is created once and then reused across runs, which mirrors
 * how this suite already worked: a persistent database plus transactions,
 * rather than migrating from scratch every time. Provisioning it costs a few
 * seconds on the very first run and nothing afterwards. To rebuild it, delete
 * the tenant row and its database:
 *
 *     php artisan tinker --execute='App\Models\Tenant::find("phpunit")?->delete();'
 */
abstract class TenantTestCase extends TestCase
{
    use DatabaseTransactions;

    /**
     * Not "test" — that one is in ProvisionTenant's reserved list, and this
     * tenant is created through the same rules everything else obeys.
     */
    protected const TEST_TENANT_ID = 'phpunit';

    /**
     * Tenancy is initialised BEFORE the traits are set up, because
     * DatabaseTransactions opens its transaction against whatever the default
     * connection is at that moment. Initialise afterwards and it would open —
     * and roll back — the CENTRAL connection, leaving everything the test
     * wrote to the tenant database permanently behind.
     */
    protected function setUpTraits(): array
    {
        $this->initializeTestTenant();

        return parent::setUpTraits();
    }

    protected function initializeTestTenant(): void
    {
        $tenant = Tenant::find(self::TEST_TENANT_ID) ?? $this->createTestTenant();

        // Idempotent, and deliberately not left to provisioning alone: a
        // test tenant created before CreateTenantStorage existed, or one
        // whose directory was cleaned up by hand, would otherwise fail on
        // the first upload with a confusing missing-path error.
        (new CreateTenantStorage($tenant))->handle();

        tenancy()->initialize($tenant);

        // Relative test requests — $this->get('/home') — resolve against the
        // root URL. Point it at the tenant's host so they reach the tenant
        // routes rather than 404ing on the central domain.
        URL::forceRootUrl($this->tenantUrl());
    }

    protected function createTestTenant(): Tenant
    {
        $tenant = Tenant::create([
            'id' => self::TEST_TENANT_ID,
            'name' => 'PHPUnit Test Account',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $tenant->domains()->create(['domain' => self::TEST_TENANT_ID]);

        return $tenant;
    }

    protected function tenantUrl(): string
    {
        $central = config('tenancy.central_domains')[0] ?? 'localhost';

        return 'http://'.self::TEST_TENANT_ID.'.'.$central;
    }
}

<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedOnDomainException;
use Tests\TestCase;

/**
 * Subdomain routing, and the one behaviour that makes the transitional
 * middleware safe rather than a hole.
 *
 * InitializeTenancyIfSubdomain falls through to central mode only when the
 * host is not a subdomain at all. A subdomain that does not match a tenant
 * must FAIL — if it fell through, nosuchclient.example.com would quietly
 * serve the central database to anyone who guessed a hostname.
 *
 * Not using DatabaseTransactions: provisioning issues CREATE DATABASE, an
 * implicit commit in MySQL. tearDown deletes the tenant instead.
 */
class TenantSubdomainRoutingTest extends TestCase
{
    protected ?string $provisionedTenantId = null;

    protected function tearDown(): void
    {
        // A test request leaves tenancy initialised in this process — nothing
        // ends it on the way out of the response. Without this, the next test
        // in the same process would run against the previous tenant's
        // database, and deleting the tenant below would run there too.
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        if ($this->provisionedTenantId !== null) {
            Tenant::find($this->provisionedTenantId)?->delete();
            $this->provisionedTenantId = null;
        }

        parent::tearDown();
    }

    protected function centralDomain(): string
    {
        return config('tenancy.central_domains')[0];
    }

    public function test_a_tenant_subdomain_resolves_to_its_own_database(): void
    {
        $this->provisionedTenantId = 'phpunitroute';

        $this->artisan('tenant:provision', [
            'subdomain' => 'phpunitroute',
            '--name' => 'PHPUnit Routing Account',
            '--owner-email' => 'owner@phpunit.test',
        ])->assertSuccessful();

        $this->get('http://phpunitroute.'.$this->centralDomain().'/tenant-health')
            ->assertOk()
            ->assertJson([
                'tenant' => 'phpunitroute',
                'name' => 'PHPUnit Routing Account',
                'status' => Tenant::STATUS_ACTIVE,
                'database' => 'tenant_phpunitroute',
            ]);
    }

    /**
     * The security-critical case. An unrecognised subdomain must raise rather
     * than silently continue on the central connection.
     */
    public function test_an_unknown_subdomain_is_rejected_rather_than_served_from_central(): void
    {
        $this->withoutExceptionHandling();

        $this->expectException(TenantCouldNotBeIdentifiedOnDomainException::class);

        $this->get('http://nosuchclient.'.$this->centralDomain().'/tenant-health');
    }

    /**
     * Transitional: the central domain still serves the application, because
     * the existing inventory has not been split into tenants yet. When that
     * migration lands this expectation flips to a redirect or a 404.
     */
    public function test_the_central_domain_still_serves_the_application(): void
    {
        $this->get('http://'.$this->centralDomain().'/login')->assertOk();
    }

    public function test_tenant_only_routes_are_unreachable_from_the_central_domain(): void
    {
        $this->get('http://'.$this->centralDomain().'/tenant-health')->assertNotFound();
    }

    /**
     * Ordering proof. SetCompanyConnection reads the companies table, so if it
     * ran before tenancy it would read the CENTRAL companies table. The health
     * route reporting the tenant's database means the swap happened first.
     */
    public function test_tenancy_resolves_before_the_company_middleware(): void
    {
        $this->provisionedTenantId = 'phpunitorder';

        // Captured BEFORE the request: a test request leaves tenancy
        // initialised, so reading this afterwards would report the tenant's
        // database and the comparison would be against itself.
        $centralDatabase = DB::connection(config('tenancy.database.central_connection'))->getDatabaseName();

        $this->artisan('tenant:provision', [
            'subdomain' => 'phpunitorder',
            '--owner-email' => 'owner@phpunit.test',
        ])->assertSuccessful();

        $response = $this->get('http://phpunitorder.'.$this->centralDomain().'/tenant-health');

        $response->assertOk();

        $this->assertSame(
            'tenant_phpunitorder',
            $response->json('database'),
            'The connection must already be the tenant database by the time the route runs.'
        );
        $this->assertNotSame(
            $centralDatabase,
            $response->json('database'),
            'It must not still be on the central database.'
        );
    }
}

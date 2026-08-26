<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Tests\TestCase;

/**
 * A tenant that is not active must not be served.
 *
 * Tenants resolve by subdomain, and the resolver matches on the domain alone
 * — it has no opinion about status. Before EnsureTenantIsActive, suspending
 * an account changed nothing a user could notice: they could still sign in
 * and work normally, and only the nightly scheduled run skipped them. A
 * Suspend button in the back-office would have been a lie.
 *
 * One tenant is provisioned for the whole class and reused, its status
 * rewritten per test. Provisioning takes seconds — a fresh one per test made
 * this file alone take a minute and a half — and none of these tests care
 * about anything inside the database, only about the status column.
 *
 * setUp resets the status rather than tearDown, so a test that dies part-way
 * through cannot leave the fixture poisoned for the next one.
 */
class SuspendedTenantAccessTest extends TestCase
{
    protected const TENANT_ID = 'phpunitstatus';

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::find(self::TENANT_ID) ?? $this->provision();
        $this->tenant->update(['status' => Tenant::STATUS_ACTIVE]);
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    protected function provision(): Tenant
    {
        $this->artisan('tenant:provision', [
            'subdomain' => self::TENANT_ID,
            '--name' => 'Status Fixture Account',
            '--owner-email' => 'owner@gmail.com',
        ])->assertSuccessful();

        return Tenant::find(self::TENANT_ID);
    }

    protected function centralDomain(): string
    {
        return config('tenancy.central_domains')[0];
    }

    protected function visit(string $path = '/login')
    {
        return $this->get('http://'.self::TENANT_ID.'.'.$this->centralDomain().$path);
    }

    protected function withStatus(string $status): void
    {
        $this->tenant->update(['status' => $status]);
    }

    public function test_an_active_tenant_is_served(): void
    {
        $this->visit()->assertOk();
    }

    public function test_a_suspended_tenant_is_refused(): void
    {
        $this->withStatus(Tenant::STATUS_SUSPENDED);

        $this->visit()
            ->assertForbidden()
            ->assertSee('Workspace suspended');
    }

    public function test_a_past_due_tenant_is_refused_and_told_their_data_is_safe(): void
    {
        $this->withStatus(Tenant::STATUS_PAST_DUE);

        $this->visit()
            ->assertForbidden()
            ->assertSee('Payment needed')
            ->assertSee('Your data is untouched', false);
    }

    public function test_a_tenant_still_provisioning_reports_that_it_is_not_ready(): void
    {
        $this->withStatus(Tenant::STATUS_PROVISIONING);

        $this->visit()
            ->assertStatus(503)
            ->assertSee('Almost ready');
    }

    /**
     * An archived account should look like nothing rather than like something
     * withheld — its address gives away no more than an unused one would.
     */
    public function test_an_archived_tenant_looks_like_it_does_not_exist(): void
    {
        $this->withStatus(Tenant::STATUS_ARCHIVED);

        $this->visit()->assertNotFound();
    }

    /**
     * The refusal has to cover the application, not just the login screen.
     * /home sits behind auth, which Laravel prioritises in the middleware
     * stack — until EnsureTenantIsActive was given a priority of its own, auth
     * ran first and answered with a redirect to login instead of a refusal.
     */
    public function test_a_suspended_tenant_cannot_reach_the_application(): void
    {
        $this->withStatus(Tenant::STATUS_SUSPENDED);

        foreach (['/home', '/emission-records', '/tenant-health'] as $path) {
            $this->assertSame(
                403,
                $this->visit($path)->getStatusCode(),
                "{$path} must be refused for a suspended tenant."
            );
        }
    }
}

<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Tests\TestCase;

/**
 * Commands that operate on client data must not run against the central
 * database, and must not run for accounts that are not active.
 *
 * This covers a regression that would otherwise have been silent. Client data
 * moved into per-tenant databases, but the scheduler still invoked these
 * commands bare. Because the central database still carries the old tables
 * from before the split, they would not have errored — they would have found
 * stale rows and acted on them, emailing people about companies that no longer
 * exist anywhere live.
 */
class TenantCommandGuardTest extends TestCase
{
    protected ?string $activeTenantId = null;

    protected ?string $suspendedTenantId = null;

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        if ($this->activeTenantId !== null) {
            Tenant::find($this->activeTenantId)?->delete();
            $this->activeTenantId = null;
        }

        if ($this->suspendedTenantId !== null) {
            // Created without events, so it has no database to drop; deleting
            // it with events would fire DeleteDatabase against nothing.
            Tenant::withoutEvents(function () {
                Tenant::find($this->suspendedTenantId)?->delete();
            });
            $this->suspendedTenantId = null;
        }

        parent::tearDown();
    }

    /**
     * @dataProvider tenantScopedCommands
     */
    public function test_a_client_data_command_refuses_to_run_without_a_tenant(string $command): void
    {
        $this->artisan($command)->assertFailed();
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function tenantScopedCommands(): array
    {
        return [
            'anomaly scan' => ['anomalies:scan'],
            'scheduled reports' => ['reports:run-scheduled'],
            'gwp backfill' => ['emissions:backfill-gwp'],
            'factor reconciliation' => ['factors:reconcile'],
            'factor alignment' => ['factors:align'],
        ];
    }

    public function test_the_refusal_says_how_to_run_it_properly(): void
    {
        $this->artisan('anomalies:scan')
            ->expectsOutputToContain('tenants:each')
            ->assertFailed();
    }

    public function test_a_client_data_command_runs_when_a_tenant_is_bound(): void
    {
        $this->activeTenantId = 'phpunitcmd';

        $this->artisan('tenant:provision', [
            'subdomain' => 'phpunitcmd',
            '--owner-email' => 'owner@phpunit.test',
        ])->assertSuccessful();

        $this->artisan('tenants:each', ['commandname' => 'emissions:backfill-gwp'])
            ->assertSuccessful();
    }

    /**
     * A suspended account must not have work run for it — that is most of what
     * suspending an account means. The suspended tenant here has no database
     * at all, so if tenants:each included it the run would fail rather than
     * quietly succeed.
     */
    public function test_it_runs_only_for_active_tenants(): void
    {
        $this->activeTenantId = 'phpunitactive';
        $this->suspendedTenantId = 'phpunitsuspended';

        $this->artisan('tenant:provision', [
            'subdomain' => 'phpunitactive',
            '--owner-email' => 'owner@phpunit.test',
        ])->assertSuccessful();

        Tenant::withoutEvents(function () {
            Tenant::create([
                'id' => 'phpunitsuspended',
                'name' => 'Suspended Account',
                'status' => Tenant::STATUS_SUSPENDED,
            ]);
        });

        $this->artisan('tenants:each', ['commandname' => 'emissions:backfill-gwp'])
            ->assertSuccessful();
    }

    public function test_it_reports_when_there_are_no_active_tenants(): void
    {
        $this->suspendedTenantId = 'phpunitnone';

        Tenant::withoutEvents(function () {
            Tenant::create([
                'id' => 'phpunitnone',
                'name' => 'Suspended Account',
                'status' => Tenant::STATUS_SUSPENDED,
            ]);
        });

        $this->artisan('tenants:each', [
            'commandname' => 'emissions:backfill-gwp',
            '--tenant' => ['phpunitnone'],
        ])
            ->expectsOutputToContain('No active tenants')
            ->assertSuccessful();
    }
}

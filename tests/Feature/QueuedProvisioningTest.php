<?php

namespace Tests\Feature;

use App\Jobs\ProvisionTenantWorkspace;
use App\Models\PlatformUser;
use App\Models\Tenant;
use App\Providers\TenancyServiceProvider;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Provisioning a client from the back-office runs off the request.
 *
 * `tenant:provision` creates a database, runs 88 migrations, seeds roles,
 * permissions and several thousand factor rows, then creates the first company
 * and its owner — 10-11 seconds idle, and the seeding grows with every factor
 * library imported. `Platform\TenantController::store()` called it with
 * `Artisan::call()` inside the HTTP request, so behind a 30-second PHP-FPM
 * timeout on a loaded server a browser-initiated onboarding cut out PART WAY
 * THROUGH, leaving a database that existed, a tenant row that might not, and an
 * operator with no idea which.
 *
 * Central-domain tests, so this extends TestCase rather than TenantTestCase:
 * the back-office is not served on a tenant subdomain.
 */
class QueuedProvisioningTest extends TestCase
{
    /** Set by any test that really provisions, so tearDown can clean up. */
    private ?string $provisionedTenantId = null;

    protected function tearDown(): void
    {
        if ($this->provisionedTenantId !== null) {
            Tenant::find($this->provisionedTenantId)?->delete();
            $this->provisionedTenantId = null;
        }

        parent::tearDown();
    }

    private function actAsStaff(): PlatformUser
    {
        $staff = PlatformUser::firstOrCreate(
            ['email' => 'queued-provisioning@example.test'],
            ['name' => 'Platform Staff', 'password' => Hash::make('password'), 'is_active' => true],
        );

        $this->actingAs($staff, 'platform');

        return $staff;
    }

    private function centralUrl(string $path = '/'): string
    {
        return 'http://'.(config('tenancy.central_domains')[0] ?? 'localhost').$path;
    }

    private function provision(array $overrides = [])
    {
        return $this->post($this->centralUrl('/admin/tenants'), array_merge([
            'subdomain' => 'queuedtest',
            'name' => 'Queued Test Ltd',
            'owner_email' => 'owner@queuedtest.test',
        ], $overrides));
    }

    public function test_the_request_dispatches_rather_than_provisioning_inline(): void
    {
        Queue::fake();
        $this->actAsStaff();

        $this->provision()->assertRedirect();

        Queue::assertPushed(
            ProvisionTenantWorkspace::class,
            fn (ProvisionTenantWorkspace $job) => $job->subdomain === 'queuedtest'
                && $job->options['--owner-email'] === 'owner@queuedtest.test'
        );

        // Nothing was created during the request itself — that is the whole point.
        $this->assertNull(Tenant::find('queuedtest'));
    }

    public function test_the_owner_password_is_generated_before_dispatch_so_it_can_be_shown(): void
    {
        Queue::fake();
        $this->actAsStaff();

        $response = $this->provision();

        $credentials = session('credentials');

        $this->assertNotEmpty($credentials['password']);
        $this->assertSame('owner@queuedtest.test', $credentials['email']);

        // ...and the same password is what the job will actually set, or the
        // operator would be handed a login that does not work.
        Queue::assertPushed(
            ProvisionTenantWorkspace::class,
            fn ($job) => $job->options['--owner-password'] === $credentials['password']
        );

        $response->assertRedirect();
    }

    public function test_the_screen_says_the_workspace_is_not_ready_yet(): void
    {
        Queue::fake();
        $this->actAsStaff();

        $this->provision();

        $this->assertTrue(session('credentials')['pending']);

        $this->get($this->centralUrl('/admin'))
            ->assertOk()
            ->assertSee('is being set up', false)
            ->assertDontSee('is ready', false);
    }

    /**
     * Everything after dispatch happens out of sight, so a subdomain that was
     * never going to work has to be refused while there is still a request to
     * answer into.
     */
    public function test_a_reserved_subdomain_is_refused_without_dispatching(): void
    {
        Queue::fake();
        $this->actAsStaff();

        $this->provision(['subdomain' => 'admin'])
            ->assertRedirect()
            ->assertSessionHas('error');

        Queue::assertNotPushed(ProvisionTenantWorkspace::class);
    }

    public function test_a_malformed_subdomain_is_refused_without_dispatching(): void
    {
        Queue::fake();
        $this->actAsStaff();

        $this->provision(['subdomain' => '-nope-'])->assertSessionHas('error');

        Queue::assertNotPushed(ProvisionTenantWorkspace::class);
    }

    public function test_a_subdomain_already_taken_is_refused_without_dispatching(): void
    {
        Queue::fake();
        $this->actAsStaff();

        $this->provision(['subdomain' => TenantTestCaseIdentity::EXISTING])
            ->assertSessionHas('error');

        Queue::assertNotPushed(ProvisionTenantWorkspace::class);
    }

    public function test_the_job_provisions_a_working_account(): void
    {
        // Not faked: this runs the real thing end to end, which is what proves
        // moving it to a job did not break provisioning.
        $this->provisionedTenantId = 'queuedjob';

        (new ProvisionTenantWorkspace('queuedjob', [
            '--name' => 'Queued Job Ltd',
            '--owner-email' => 'owner@queuedjob.test',
            '--owner-password' => 'a-known-password',
        ]))->handle();

        $tenant = Tenant::find('queuedjob');

        $this->assertNotNull($tenant);
        $this->assertSame(Tenant::STATUS_ACTIVE, $tenant->status);

        // The database, the company and the owner all exist — the steps that
        // would have been skipped had the TenantCreated pipeline been queued
        // instead of the whole operation.
        $tenant->run(function () {
            $this->assertSame(1, \App\Models\Company::count());

            $owner = \App\Models\User::withoutGlobalScope('company')
                ->where('email', 'owner@queuedjob.test')
                ->first();

            $this->assertNotNull($owner);
            $this->assertTrue($owner->is_account_owner);
            $this->assertTrue(Hash::check('a-known-password', $owner->password));
        });
    }

    public function test_the_job_is_not_retried(): void
    {
        // A second attempt would find the subdomain taken — by whatever the
        // first attempt left behind — and fail for a reason unrelated to why
        // it failed the first time.
        $this->assertSame(1, (new ProvisionTenantWorkspace('x', []))->tries);
    }

    /**
     * The trap this whole change exists to avoid.
     *
     * Queueing the TenantCreated pipeline looks like the way to make
     * provisioning asynchronous, and the comment in TenancyServiceProvider used
     * to say exactly that. It does not work: ProvisionTenant calls
     * `$tenant->run(...)` on the line after `Tenant::create()`, and that needs
     * the database the pipeline creates. Queue the pipeline and it writes into
     * a database that does not exist yet.
     */
    public function test_the_tenant_created_pipeline_stays_synchronous(): void
    {
        $listeners = (new TenancyServiceProvider(app()))->events()[\Stancl\Tenancy\Events\TenantCreated::class];

        $pipeline = $listeners[0];

        $queued = (function () {
            return $this->shouldBeQueued ?? false;
        })->call($pipeline);

        $this->assertFalse(
            $queued,
            'The TenantCreated pipeline was queued. ProvisionTenant writes into the tenant database on '
            .'the line after Tenant::create(), so this makes provisioning fail rather than deferring it.'
        );
    }
}

/**
 * The id TenantTestCase provisions for the suite. Named here rather than
 * hardcoded so the "already taken" test reads as intent.
 */
final class TenantTestCaseIdentity
{
    public const EXISTING = 'phpunit';
}

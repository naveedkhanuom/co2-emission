<?php

namespace Tests\Feature;

use App\Models\PlatformUser;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Onboarding has to hand back credentials somebody can actually use.
 *
 * The back-office invokes tenant:provision in-process, and the command
 * generates the owner password itself and prints it to its own output buffer.
 * The controller discards that buffer on success — so provisioning from the
 * screen produced an account whose password nobody had, and a status message
 * cheerfully claiming it had been logged, which it had not.
 *
 * No DatabaseTransactions: provisioning issues CREATE DATABASE, an implicit
 * commit in MySQL. tearDown deletes the tenant instead.
 */
class TenantCredentialsTest extends TestCase
{
    protected ?string $provisionedTenantId = null;

    protected function tearDown(): void
    {
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

    protected function staff(): PlatformUser
    {
        return PlatformUser::create([
            'name' => 'Platform Ops',
            'email' => 'ops-'.uniqid().'@gmail.com',
            'password' => Hash::make('correct-horse-battery'),
            'is_active' => true,
        ]);
    }

    public function test_onboarding_from_the_back_office_returns_usable_credentials(): void
    {
        $this->provisionedTenantId = 'phpunitcreds';

        $response = $this->actingAs($this->staff(), 'platform')
            ->post('http://'.$this->centralDomain().'/admin/tenants', [
                'subdomain' => 'phpunitcreds',
                'name' => 'Credentials Test Account',
                'owner_email' => 'owner@gmail.com',
            ]);

        $response->assertRedirect(route('platform.tenants.index'));

        $credentials = session('credentials');

        $this->assertIsArray($credentials, 'The screen must hand back the credentials.');
        $this->assertSame('owner@gmail.com', $credentials['email']);
        $this->assertNotEmpty($credentials['password']);

        // The password shown has to be the one that actually works. It used
        // to be generated inside the command and thrown away.
        Tenant::find('phpunitcreds')->run(function () use ($credentials) {
            $owner = User::where('email', 'owner@gmail.com')->first();

            $this->assertNotNull($owner);
            $this->assertTrue(
                Hash::check($credentials['password'], $owner->password),
                'The password shown on screen must be the account owner\'s real password.'
            );
        });
    }

    public function test_the_owner_password_can_be_reset_when_it_is_lost(): void
    {
        $tenant = Tenant::find('phpunitstatus');

        if (! $tenant) {
            $this->markTestSkipped('No fixture tenant available.');
        }

        $tenant->update(['status' => Tenant::STATUS_ACTIVE]);

        $before = $tenant->run(fn () => User::where('is_account_owner', true)->orderBy('id')->first()?->password);

        $this->artisan('tenant:reset-password', ['tenant' => 'phpunitstatus'])
            ->assertSuccessful();

        $after = $tenant->run(fn () => User::where('is_account_owner', true)->orderBy('id')->first()?->password);

        $this->assertNotNull($before);
        $this->assertNotSame($before, $after, 'The password must actually change.');
    }

    public function test_resetting_a_password_for_an_unknown_tenant_fails(): void
    {
        $this->artisan('tenant:reset-password', ['tenant' => 'nosuchclient'])
            ->assertFailed();
    }

    public function test_resetting_a_password_for_an_unknown_user_fails(): void
    {
        $tenant = Tenant::find('phpunitstatus');

        if (! $tenant) {
            $this->markTestSkipped('No fixture tenant available.');
        }

        $tenant->update(['status' => Tenant::STATUS_ACTIVE]);

        $this->artisan('tenant:reset-password', [
            'tenant' => 'phpunitstatus',
            '--email' => 'nobody@gmail.com',
        ])->assertFailed();
    }
}

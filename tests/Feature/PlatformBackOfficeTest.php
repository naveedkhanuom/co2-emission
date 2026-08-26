<?php

namespace Tests\Feature;

use App\Models\PlatformUser;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The back-office: staff sign-in on the central domain, and the client list.
 *
 * The boundary being tested is that platform staff and tenant users are two
 * separate populations. A platform account can reach every client, so it must
 * not live in any client's users table, and a session on one guard must not
 * be a session on the other.
 *
 * Extends TestCase, not TenantTestCase: all of this runs on the central
 * domain, where tenancy never initialises.
 */
class PlatformBackOfficeTest extends TestCase
{
    use DatabaseTransactions;

    protected function centralDomain(): string
    {
        return config('tenancy.central_domains')[0];
    }

    protected function admin(string $path = ''): string
    {
        return 'http://'.$this->centralDomain().'/admin'.$path;
    }

    protected function staff(bool $active = true): PlatformUser
    {
        return PlatformUser::create([
            'name' => 'Platform Ops',
            'email' => 'ops-'.uniqid().'@gmail.com',
            'password' => Hash::make('correct-horse-battery'),
            'is_active' => $active,
        ]);
    }

    public function test_the_back_office_requires_signing_in(): void
    {
        $this->get($this->admin())->assertRedirect(route('platform.login'));
    }

    public function test_staff_can_sign_in_and_see_the_client_list(): void
    {
        $user = $this->staff();

        $this->post($this->admin('/login'), [
            'email' => $user->email,
            'password' => 'correct-horse-battery',
        ])->assertRedirect(route('platform.tenants.index'));

        $this->assertAuthenticatedAs($user, 'platform');

        $this->get($this->admin())->assertOk()->assertSee('Clients');
    }

    public function test_wrong_credentials_are_refused(): void
    {
        $user = $this->staff();

        $this->post($this->admin('/login'), [
            'email' => $user->email,
            'password' => 'not-the-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('platform');
    }

    /**
     * Deactivating an account has to end access, not merely stop new
     * sign-ups — so the check happens after the credentials pass.
     */
    public function test_a_deactivated_staff_account_cannot_sign_in_even_with_the_right_password(): void
    {
        $user = $this->staff(active: false);

        $this->post($this->admin('/login'), [
            'email' => $user->email,
            'password' => 'correct-horse-battery',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('platform');
    }

    /**
     * The two guards are separate populations. A signed-in staff member is
     * not signed in to any workspace, and a client's user is not staff.
     */
    public function test_the_platform_guard_is_separate_from_the_tenant_guard(): void
    {
        $user = $this->staff();

        $this->actingAs($user, 'platform');

        $this->assertAuthenticatedAs($user, 'platform');
        $this->assertGuest('web');
    }

    public function test_the_back_office_is_unreachable_on_a_tenant_subdomain(): void
    {
        $user = $this->staff();

        // Even signed in as staff, /admin does not exist on a client's
        // address — the routes carry a central domain constraint.
        $this->actingAs($user, 'platform')
            ->get('http://phpunit.'.$this->centralDomain().'/admin')
            ->assertNotFound();
    }

    public function test_staff_can_suspend_and_reactivate_a_client(): void
    {
        $user = $this->staff();
        $tenant = Tenant::find('phpunitstatus') ?? $this->markTestSkipped('No fixture tenant available.');

        $tenant->update(['status' => Tenant::STATUS_ACTIVE]);

        $this->actingAs($user, 'platform')
            ->post($this->admin('/tenants/'.$tenant->id.'/suspend'))
            ->assertRedirect();

        $this->assertSame(Tenant::STATUS_SUSPENDED, $tenant->fresh()->status);

        $this->actingAs($user, 'platform')
            ->post($this->admin('/tenants/'.$tenant->id.'/activate'))
            ->assertRedirect();

        $this->assertSame(Tenant::STATUS_ACTIVE, $tenant->fresh()->status);
    }

    /**
     * Reviving an archived account needs more thought than a button press,
     * so the transition is refused rather than silently allowed.
     */
    public function test_an_archived_client_cannot_be_reactivated_from_the_list(): void
    {
        $user = $this->staff();
        $tenant = Tenant::find('phpunitstatus') ?? $this->markTestSkipped('No fixture tenant available.');

        $tenant->update(['status' => Tenant::STATUS_ARCHIVED]);

        $this->actingAs($user, 'platform')
            ->post($this->admin('/tenants/'.$tenant->id.'/activate'))
            ->assertSessionHas('error');

        $this->assertSame(Tenant::STATUS_ARCHIVED, $tenant->fresh()->status);

        $tenant->update(['status' => Tenant::STATUS_ACTIVE]);
    }

    public function test_provisioning_rejects_a_reserved_subdomain_without_creating_anything(): void
    {
        $user = $this->staff();

        $this->actingAs($user, 'platform')
            ->post($this->admin('/tenants'), [
                'subdomain' => 'admin',
                'owner_email' => 'owner@gmail.com',
            ])
            ->assertSessionHas('error');

        $this->assertNull(Tenant::find('admin'));
    }

    public function test_signing_out_ends_the_staff_session(): void
    {
        $user = $this->staff();

        $this->actingAs($user, 'platform')
            ->post($this->admin('/logout'))
            ->assertRedirect(route('platform.login'));

        $this->assertGuest('platform');
    }
}

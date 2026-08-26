<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * GHG-07 — who may read the change history.
 *
 * The audit trail records who altered which emission figure and when. It was
 * readable by every authenticated user in the company; it is now gated behind
 * `list-audit-logs`, with Super Admin and Admin bypassing via Gate::before.
 */
class AuditLogAccessTest extends TestCase
{
    use DatabaseTransactions;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Audit Access Co '.uniqid(),
            'industry_type' => 'manufacturing',
            'is_active' => true,
        ]);

        Permission::firstOrCreate(['name' => 'list-audit-logs', 'guard_name' => 'web']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function makeUser(): User
    {
        return User::create([
            'name' => 'Audit Test User',
            'email' => 'audit-test-'.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
        ]);
    }

    public function test_a_user_without_the_permission_is_refused(): void
    {
        $this->actingAs($this->makeUser())
            ->get(route('audit-logs.index'))
            ->assertForbidden();
    }

    public function test_a_user_with_the_permission_may_read_it(): void
    {
        $user = $this->makeUser();
        $user->givePermissionTo('list-audit-logs');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($user)
            ->get(route('audit-logs.index'))
            ->assertOk();
    }

    /**
     * Admins must not be locked out of their own trail — Gate::before covers
     * them without needing the permission granted explicitly.
     */
    public function test_an_admin_keeps_access_without_the_permission(): void
    {
        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = $this->makeUser();
        $user->assignRole('Admin');

        $this->actingAs($user)
            ->get(route('audit-logs.index'))
            ->assertOk();
    }

    public function test_a_guest_is_sent_to_login(): void
    {
        $this->get(route('audit-logs.index'))->assertRedirect(route('login'));
    }
}

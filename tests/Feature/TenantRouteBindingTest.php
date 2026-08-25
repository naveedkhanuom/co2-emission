<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\EmissionRecord;
use App\Models\Facilities;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Implicit route-model binding on company-scoped models.
 *
 * SetCompanyConnection is appended to the `web` middleware group, so it runs
 * AFTER SubstituteBindings. During binding there is no company context bound,
 * and HasCompanyScope falls through to `whereRaw('1 = 0')` for anyone who is
 * not a super-admin — so an ordinary user gets a 404 on their OWN records.
 *
 * AppServiceProvider registers explicit bindings for the affected models to
 * resolve the company the same way the middleware does. These tests prove both
 * halves: owners can reach their rows, and other tenants still cannot.
 */
class TenantRouteBindingTest extends TestCase
{
    use DatabaseTransactions;

    private function makeCompany(string $name): Company
    {
        return Company::create([
            'name' => $name,
            'industry_type' => 'manufacturing',
            'is_active' => true,
        ]);
    }

    /**
     * The routes under test sit behind spatie permission middleware, so the
     * user needs the permissions as well as the tenancy — otherwise a 403 would
     * mask whether binding resolved at all.
     */
    private function makeUser(Company $company, bool $superAdmin = false): User
    {
        $user = User::create([
            'name' => 'Tenant Test User',
            'email' => 'tenant-test-'.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
            'is_super_admin' => $superAdmin,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user->givePermissionTo([
            'list-emission-records',
            'edit-emission-record',
            'delete-emission-record',
            'list-facilities',
            'edit-facility',
            'delete-facility',
        ]);

        return $user;
    }

    private function makeRecord(Company $company): EmissionRecord
    {
        return EmissionRecord::create([
            'company_id' => $company->id,
            'entry_date' => now(),
            'scope' => 1,
            'facility' => 'Main Site',
            'emission_source' => 'Diesel',
            'activity_data' => 100,
            'emission_factor' => 0.00268,
            'co2e_value' => 0.268,
        ]);
    }

    private function makeFacility(Company $company): Facilities
    {
        return Facilities::create([
            'company_id' => $company->id,
            'name' => 'Plant A',
        ]);
    }

    /**
     * The core regression: an ordinary user viewing their own emission record.
     * Before the fix this returned 404 — the row exists and belongs to them.
     */
    public function test_owner_can_view_their_own_emission_record(): void
    {
        $company = $this->makeCompany('Owner Co');
        $user = $this->makeUser($company);
        $record = $this->makeRecord($company);

        $this->actingAs($user)
            ->get('/emission-records/'.$record->id)
            ->assertOk();
    }

    /** Deleting your own record must not 404 either. */
    public function test_owner_can_delete_their_own_emission_record(): void
    {
        $company = $this->makeCompany('Owner Co');
        $user = $this->makeUser($company);
        $record = $this->makeRecord($company);

        $this->actingAs($user)
            ->delete('/emission-records/'.$record->id)
            ->assertSuccessful();
    }

    /**
     * Facilities have the same binding on update/destroy. This controller
     * redirects rather than returning JSON, so assert the effect, not the
     * status code.
     */
    public function test_owner_can_delete_their_own_facility(): void
    {
        $company = $this->makeCompany('Owner Co');
        $user = $this->makeUser($company);
        $facility = $this->makeFacility($company);

        $this->actingAs($user)->delete('/facilities/'.$facility->id);

        $this->assertNull(
            Facilities::withoutGlobalScope('company')->find($facility->id),
            'The owner should be able to delete their own facility.'
        );
    }

    /**
     * The tenant boundary must survive the fix. Resolving without the global
     * scope is only safe because the company check is explicit in the binding.
     */
    public function test_user_cannot_view_another_companys_emission_record(): void
    {
        $mine = $this->makeCompany('My Co');
        $theirs = $this->makeCompany('Their Co');

        $user = $this->makeUser($mine);
        $foreign = $this->makeRecord($theirs);

        $this->actingAs($user)
            ->get('/emission-records/'.$foreign->id)
            ->assertNotFound();
    }

    /** Cross-tenant deletion is refused and the row survives. */
    public function test_user_cannot_delete_another_companys_emission_record(): void
    {
        $mine = $this->makeCompany('My Co');
        $theirs = $this->makeCompany('Their Co');

        $user = $this->makeUser($mine);
        $foreign = $this->makeRecord($theirs);

        $this->actingAs($user)
            ->delete('/emission-records/'.$foreign->id)
            ->assertNotFound();

        $this->assertNotNull(
            EmissionRecord::withoutGlobalScope('company')->find($foreign->id),
            'A cross-tenant delete must not remove the row.'
        );
    }

    /** Cross-tenant facility access is refused too. */
    public function test_user_cannot_delete_another_companys_facility(): void
    {
        $mine = $this->makeCompany('My Co');
        $theirs = $this->makeCompany('Their Co');

        $user = $this->makeUser($mine);
        $foreign = $this->makeFacility($theirs);

        $this->actingAs($user)
            ->delete('/facilities/'.$foreign->id)
            ->assertNotFound();
    }

    /**
     * A super-admin who has picked a company in the switcher (which writes
     * `current_company_id` to the session) must be able to work in it. The
     * binding has to honour the session company, not just the user's own
     * `company_id` — that is how cross-company access actually works here.
     */
    public function test_super_admin_can_work_in_the_company_they_selected(): void
    {
        $home = $this->makeCompany('Home Co');
        $other = $this->makeCompany('Other Co');

        $superAdmin = $this->makeUser($home, superAdmin: true);

        // Gate::before keys off the ROLE, not the is_super_admin column.
        if (Role::where('name', 'Super Admin')->exists()) {
            $superAdmin->assignRole('Super Admin');
        }

        $record = $this->makeRecord($other);

        $this->actingAs($superAdmin)
            ->withSession(['current_company_id' => $other->id])
            ->get('/emission-records/'.$record->id)
            ->assertOk();
    }

    /**
     * The session company wins over the user's own company, so switching
     * companies actually switches what the binding resolves.
     */
    public function test_session_company_takes_precedence_over_user_company(): void
    {
        $mine = $this->makeCompany('My Co');
        $other = $this->makeCompany('Other Co');

        $user = $this->makeUser($mine);
        $ownRecord = $this->makeRecord($mine);

        // While switched to another company, our own record is out of context.
        $this->actingAs($user)
            ->withSession(['current_company_id' => $other->id])
            ->get('/emission-records/'.$ownRecord->id)
            ->assertNotFound();
    }
}

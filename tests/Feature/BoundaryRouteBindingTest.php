<?php

namespace Tests\Feature;

use App\Models\BoundaryAssessment;
use App\Models\BoundaryItem;
use App\Models\Company;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;
use Tests\TenantTestCase;

/**
 * Regression guard for a tenancy trap.
 *
 * SetCompanyConnection is appended to the `web` middleware group, so it runs
 * AFTER SubstituteBindings. During implicit route-model binding there is no
 * company context bound yet, and HasCompanyScope falls through to its
 * deny-everything branch for anyone who is not an account owner — so a route type-hinting a
 * company-scoped model 404s on rows the user genuinely owns.
 *
 * BoundaryController therefore resolves its models explicitly. These tests fail
 * if anyone reintroduces implicit binding, and also prove the tenant boundary
 * is still enforced by the explicit path.
 *
 * Wrapped in a transaction: this suite runs against the configured database.
 */
class BoundaryRouteBindingTest extends TenantTestCase
{
    private function makeCompany(string $name): Company
    {
        return Company::create([
            'name' => $name,
            'industry_type' => 'transportation',
            'is_active' => true,
        ]);
    }

    private function makeUser(Company $company, array $permissions = ['list-boundary', 'create-boundary', 'edit-boundary']): User
    {
        $user = User::create([
            'name' => 'Boundary Test User',
            'email' => 'boundary-test-'.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if ($permissions) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }

    private function makeAssessment(Company $company): BoundaryAssessment
    {
        return BoundaryAssessment::create([
            'company_id' => $company->id,
            'reporting_year' => (int) now()->year,
            'status' => 'draft',
            'version' => 99,
            'questions' => [[
                'key' => 'premises',
                'question' => 'Do you own or rent?',
                'options' => [['value' => 'own', 'label' => 'We own them', 'implies' => []]],
            ]],
        ]);
    }

    private function makeItem(BoundaryAssessment $assessment): BoundaryItem
    {
        return BoundaryItem::create([
            'company_id' => $assessment->company_id,
            'boundary_assessment_id' => $assessment->id,
            'scope' => 1,
            'suggested_name' => 'Standby generator diesel',
            'materiality' => 'high',
            'relevance' => 'relevant',
            'decision' => 'pending',
            'source' => 'template',
        ]);
    }

    /**
     * The bug that shipped: a user who is not an account owner got "No query results for
     * model [BoundaryAssessment]" on their own draft. Hitting the route with an
     * invalid payload must now produce a 422 (validation ran, so the model
     * resolved) rather than a 404.
     */
    public function test_owner_can_resolve_their_own_assessment(): void
    {
        $company = $this->makeCompany('Binding Co');
        $user = $this->makeUser($company);
        $assessment = $this->makeAssessment($company);

        $response = $this->actingAs($user)
            ->postJson(route('boundary.generate', $assessment->id), ['answers' => []]);

        $response->assertStatus(422);
        $this->assertArrayHasKey('answers', $response->json('errors'));
    }

    /** Checklist items resolve for their owner too. */
    public function test_owner_can_decide_their_own_item(): void
    {
        $company = $this->makeCompany('Binding Co');
        $user = $this->makeUser($company);
        $item = $this->makeItem($this->makeAssessment($company));

        $this->actingAs($user)
            ->patchJson(route('boundary.items.decide', $item->id), ['decision' => 'included'])
            ->assertOk()
            ->assertJsonPath('item.decision', 'included');

        $this->assertSame('included', $item->fresh()->decision);
    }

    /** An exclusion with no reason is refused — that reason is the audit record. */
    public function test_exclusion_requires_a_reason(): void
    {
        $company = $this->makeCompany('Binding Co');
        $user = $this->makeUser($company);
        $item = $this->makeItem($this->makeAssessment($company));

        $this->actingAs($user)
            ->patchJson(route('boundary.items.decide', $item->id), ['decision' => 'excluded'])
            ->assertStatus(422);

        $this->assertSame('pending', $item->fresh()->decision);
    }

    /**
     * The tenant boundary must still hold. Dropping the global scope in the
     * controller is only safe because the company check is explicit — this
     * proves it is.
     */
    public function test_user_cannot_touch_another_companys_assessment(): void
    {
        $mine = $this->makeCompany('My Co');
        $theirs = $this->makeCompany('Their Co');

        $user = $this->makeUser($mine);
        $foreign = $this->makeAssessment($theirs);

        $this->actingAs($user)
            ->postJson(route('boundary.generate', $foreign->id), ['answers' => ['premises' => 'own']])
            ->assertStatus(404);
    }

    /** Cross-tenant item access is refused and leaves the row untouched. */
    public function test_user_cannot_decide_another_companys_item(): void
    {
        $mine = $this->makeCompany('My Co');
        $theirs = $this->makeCompany('Their Co');

        $user = $this->makeUser($mine);
        $foreignItem = $this->makeItem($this->makeAssessment($theirs));

        $this->actingAs($user)
            ->patchJson(route('boundary.items.decide', $foreignItem->id), ['decision' => 'included'])
            ->assertStatus(404);

        $this->assertSame('pending', $foreignItem->fresh()->decision);
    }

    /** Guests are redirected to login, not shown the workspace. */
    public function test_guest_cannot_reach_the_boundary_workspace(): void
    {
        $this->get(route('boundary.index'))->assertRedirect(route('login'));
    }

    /**
     * Permissions must actually be enforced, not merely seeded. Being signed in
     * is not authorisation.
     */
    public function test_user_without_permission_cannot_open_the_workspace(): void
    {
        $company = $this->makeCompany('Binding Co');
        $user = $this->makeUser($company, permissions: []);

        $this->actingAs($user)->get(route('boundary.index'))->assertForbidden();
    }

    /** Read access does not imply the right to scope or change a boundary. */
    public function test_read_only_user_cannot_generate_or_decide(): void
    {
        $company = $this->makeCompany('Binding Co');
        $user = $this->makeUser($company, permissions: ['list-boundary']);
        $item = $this->makeItem($this->makeAssessment($company));

        $this->actingAs($user)->get(route('boundary.index'))->assertOk();

        $this->actingAs($user)
            ->postJson(route('boundary.generate', $item->boundary_assessment_id), ['answers' => ['premises' => 'own']])
            ->assertForbidden();

        $this->actingAs($user)
            ->patchJson(route('boundary.items.decide', $item->id), ['decision' => 'included'])
            ->assertForbidden();

        $this->assertSame('pending', $item->fresh()->decision);
    }
}

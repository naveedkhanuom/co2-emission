<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;
use Tests\TenantTestCase;

/**
 * TEN-19 — "Ask Your Data" had no permission gate.
 *
 * The routes sit behind `auth`, so this was never open to the world. What it
 * was, was a read-anything channel for any signed-in user: AskYourDataService
 * answers from the same EmissionAnalyticsService snapshot the dashboard renders,
 * so a user deliberately kept off the dashboard could still ask the assistant
 * for those totals and get them back in prose.
 *
 * Gated on list-dashboard — the same permission AnalyticsController requires —
 * rather than a new "ask" permission, so the two surfaces cannot drift apart and
 * nothing new has to be seeded into every tenant.
 */
class AskAssistantAuthorisationTest extends TenantTestCase
{
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Ask Gate Co '.uniqid(),
            'industry_type' => 'manufacturing',
            'is_active' => true,
        ]);

        app()->instance('current_company_id', $this->company->id);
        app()->instance('current_company', $this->company);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function userWith(array $permissions): User
    {
        $user = User::create([
            'name' => 'Ask Gate User',
            'email' => 'ask-gate-'.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }

    public function test_a_user_without_dashboard_access_cannot_open_the_assistant(): void
    {
        $this->actingAs($this->userWith(['list-emission-records']))
            ->get(route('assistant.index'))
            ->assertForbidden();
    }

    /**
     * The POST is the one that actually returns figures, so it is asserted
     * separately — a gate on the page alone would leave the data path open.
     */
    public function test_a_user_without_dashboard_access_cannot_query_it(): void
    {
        $this->actingAs($this->userWith(['list-emission-records']))
            ->postJson(route('assistant.ask'), ['question' => 'What are our total emissions?'])
            ->assertForbidden();
    }

    public function test_a_user_with_dashboard_access_can_still_open_it(): void
    {
        $this->actingAs($this->userWith(['list-dashboard']))
            ->get(route('assistant.index'))
            ->assertOk();
    }
}

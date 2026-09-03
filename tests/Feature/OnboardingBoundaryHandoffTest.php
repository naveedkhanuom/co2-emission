<?php

namespace Tests\Feature;

use App\Http\Controllers\OnboardingController;
use App\Models\BoundaryAssessment;
use App\Models\Company;
use App\Models\ReportingPeriod;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;
use Tests\TenantTestCase;

/**
 * The setup wizard hands off to the Boundary Advisor.
 *
 * Before this, the four things a new client needs to do in order — profile,
 * boundary, data, lock — were four features that did not know about each other.
 * The wizard finished by sending them to an empty dashboard, and the Boundary
 * Advisor, which under the GHG Protocol decides what they are even supposed to
 * collect, sat in the sidebar waiting to be discovered.
 *
 * Three separate defects are pinned here, because each one fails on its own:
 *
 *  1. the wizard redirected to `home` and nothing led to the boundary;
 *  2. it never collected `business_description`, the one field the Advisor
 *     requires — so its first screen was a form the client had effectively
 *     already filled in once;
 *  3. it wrote the `base_year` SETTING but no `reporting_periods` row, so
 *     ReportingPeriod::baseYearFor() answered null however carefully the
 *     wizard was completed.
 */
class OnboardingBoundaryHandoffTest extends TenantTestCase
{
    private function makeCompany(): Company
    {
        return Company::create([
            'name' => 'Handoff Test Co '.uniqid(),
            'industry_type' => 'construction',
            'is_active' => true,
        ]);
    }

    private function actAsMemberOf(Company $company, array $permissions = []): User
    {
        $user = User::create([
            'name' => 'Setup User',
            'email' => 'setup-'.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        $this->actingAs($user);
        app()->instance('current_company_id', $company->id);
        app()->instance('current_company', $company);

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Handoff Test Co',
            'industry_type' => 'construction',
            'business_description' => 'We build residential towers in Abu Dhabi and subcontract the concrete work.',
            'country' => 'United Arab Emirates',
            'employee_count' => 120,
            'base_year' => 2026,
            'gwp_version' => 'ar6',
            'consolidation_approach' => 'operational_control',
            'sites' => [['name' => 'Head Office', 'location' => 'Abu Dhabi']],
            'activities' => ['electricity', 'onsite_fuel'],
        ], $overrides);
    }

    public function test_finishing_setup_sends_the_client_to_the_boundary_not_the_dashboard(): void
    {
        $company = $this->makeCompany();
        $this->actAsMemberOf($company);

        $response = $this->postJson(route('onboarding.save'), $this->payload());

        $response->assertOk();
        $response->assertJson(['success' => true]);

        // The defect: this used to be route('home').
        $this->assertSame(
            route('boundary.index'),
            $response->json('redirect'),
            'Setup finished by dropping the client on the dashboard instead of the boundary.'
        );
    }

    public function test_the_business_description_is_carried_over_so_the_advisor_does_not_ask_again(): void
    {
        $company = $this->makeCompany();
        $this->actAsMemberOf($company);

        $this->postJson(route('onboarding.save'), $this->payload())->assertOk();

        $this->assertSame(
            'We build residential towers in Abu Dhabi and subcontract the concrete work.',
            $company->fresh()->business_description,
            'The wizard discarded the description the Boundary Advisor needs.'
        );
    }

    public function test_a_description_that_is_too_short_for_the_advisor_is_rejected_here(): void
    {
        $company = $this->makeCompany();
        $this->actAsMemberOf($company);

        // BoundaryController::start() enforces min:10. Accepting less here
        // would just move the rejection one screen later.
        $this->postJson(route('onboarding.save'), $this->payload([
            'business_description' => 'builder',
        ]))->assertStatus(422)->assertJsonValidationErrors('business_description');
    }

    public function test_an_omitted_description_does_not_wipe_one_the_company_already_had(): void
    {
        $company = $this->makeCompany();
        $company->update(['business_description' => 'An existing description worth keeping.']);
        $this->actAsMemberOf($company);

        $this->postJson(route('onboarding.save'), $this->payload([
            'business_description' => null,
        ]))->assertOk();

        $this->assertSame(
            'An existing description worth keeping.',
            $company->fresh()->business_description
        );
    }

    public function test_the_chosen_base_year_becomes_a_real_reporting_period(): void
    {
        $company = $this->makeCompany();
        $this->actAsMemberOf($company);

        $this->assertNull(
            ReportingPeriod::baseYearFor($company->id),
            'Precondition: no base year before setup runs.'
        );

        $this->postJson(route('onboarding.save'), $this->payload(['base_year' => 2025]))->assertOk();

        // The setting was always written. The ROW is what baseYearFor() reads,
        // and what the year-over-year and target screens depend on.
        $this->assertSame('2025', (string) $company->fresh()->getSetting('base_year'));
        $this->assertSame(
            2025,
            ReportingPeriod::baseYearFor($company->id),
            'The base year existed only as a setting, so the rest of the app could not see it.'
        );
    }

    public function test_only_one_year_is_ever_the_base_year(): void
    {
        $company = $this->makeCompany();
        $this->actAsMemberOf($company);

        ReportingPeriod::create([
            'company_id' => $company->id,
            'year' => 2023,
            'status' => 'open',
            'is_base_year' => true,
        ]);

        $this->postJson(route('onboarding.save'), $this->payload(['base_year' => 2026]))->assertOk();

        $baseYears = ReportingPeriod::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->where('is_base_year', true)
            ->pluck('year')
            ->all();

        $this->assertSame([2026], $baseYears);
    }

    public function test_re_running_setup_does_not_reopen_a_locked_year(): void
    {
        $company = $this->makeCompany();
        $this->actAsMemberOf($company);

        ReportingPeriod::create([
            'company_id' => $company->id,
            'year' => 2024,
            'status' => 'locked',
            'locked_at' => now(),
            'is_base_year' => false,
        ]);

        $this->postJson(route('onboarding.save'), $this->payload(['base_year' => 2024]))->assertOk();

        $period = ReportingPeriod::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->where('year', 2024)
            ->first();

        $this->assertTrue($period->is_base_year, 'It should still become the base year.');
        $this->assertSame(
            'locked',
            $period->status,
            'A signed-off inventory year was unfrozen by re-running the setup wizard.'
        );
    }

    public function test_the_boundary_screen_greets_a_client_arriving_from_setup(): void
    {
        $company = $this->makeCompany();
        $this->actAsMemberOf($company, ['list-boundary', 'create-boundary']);

        $this->postJson(route('onboarding.save'), $this->payload())->assertOk();

        $this->assertSame(
            OnboardingController::STAGE_BOUNDARY,
            $company->fresh()->getSetting('onboarding_stage')
        );

        $response = $this->get(route('boundary.index'));

        $response->assertOk();
        $response->assertViewHas('fromOnboarding', true);
        $response->assertSee('Setup saved.', false);

        // Carried over, so step 1 is a confirmation rather than a form.
        $response->assertSee('subcontract the concrete work', false);
    }

    public function test_the_boundary_year_defaults_to_the_year_setup_nominated(): void
    {
        $company = $this->makeCompany();
        $this->actAsMemberOf($company, ['list-boundary']);

        $this->postJson(route('onboarding.save'), $this->payload([
            'base_year' => (int) now()->year - 1,
        ]))->assertOk();

        $this->get(route('boundary.index'))
            ->assertOk()
            ->assertViewHas('defaultReportingYear', (int) now()->year - 1);
    }

    public function test_a_base_year_outside_the_selectable_range_falls_back_to_this_year(): void
    {
        $company = $this->makeCompany();
        $this->actAsMemberOf($company, ['list-boundary']);

        // Selecting a year the dropdown does not offer would match no option,
        // and the browser would silently pick the first — a year ahead.
        $this->postJson(route('onboarding.save'), $this->payload(['base_year' => 2005]))->assertOk();

        $this->get(route('boundary.index'))
            ->assertOk()
            ->assertViewHas('defaultReportingYear', (int) now()->year);
    }

    public function test_activating_the_boundary_ends_the_setup_chain(): void
    {
        $company = $this->makeCompany();
        $this->actAsMemberOf($company, ['list-boundary', 'create-boundary', 'edit-boundary']);

        $this->postJson(route('onboarding.save'), $this->payload())->assertOk();

        $assessment = BoundaryAssessment::create([
            'company_id' => $company->id,
            'reporting_year' => (int) now()->year,
            'status' => 'draft',
            'version' => 1,
        ]);

        $this->post(route('boundary.activate', $assessment->id));

        $this->assertSame(
            'complete',
            $company->fresh()->getSetting('onboarding_stage'),
            'The "finish setting up" greeting would have followed the client around forever.'
        );

        $this->get(route('boundary.index'))
            ->assertOk()
            ->assertViewHas('fromOnboarding', false);
    }
}

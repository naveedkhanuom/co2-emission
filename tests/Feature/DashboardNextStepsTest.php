<?php

namespace Tests\Feature;

use App\Models\BoundaryAssessment;
use App\Models\BoundaryItem;
use App\Models\Company;
use App\Models\EmissionRecord;
use App\Models\User;
use App\Services\DataHealthService;
use Spatie\Permission\PermissionRegistrar;
use Tests\TenantTestCase;

/**
 * The dashboard tells a client what to do next.
 *
 * The setup wizard hands off to the Boundary Advisor, and the Advisor's own
 * coverage service turns the boundary into a work queue — but that queue only
 * ever rendered on /data-health, which is a good page nobody visits. A client
 * who finished setup landed on the dashboard, saw empty charts, and had nothing
 * telling them what came next. They do not churn loudly; they go quiet.
 *
 * The assessment now lives in DataHealthService so both screens read one
 * implementation and cannot reach different conclusions about what matters.
 */
class DashboardNextStepsTest extends TenantTestCase
{
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Next Steps Co '.uniqid(),
            'industry_type' => 'manufacturing',
            'scopes_enabled' => [1, 2],
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Dashboard User',
            'email' => 'steps-'.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
        ]);

        // Past the first-run wizard. HomeController sends a company with no
        // sites and no records to /onboarding, which is the right behaviour and
        // not the state this panel is for — it speaks to a client who has
        // finished setup and is wondering what comes next.
        $this->company->setSetting('onboarding_completed', true, 'boolean');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->givePermissionTo(['list-dashboard', 'list-review-data']);

        $this->actingAs($user);
        app()->instance('current_company_id', $this->company->id);
        app()->instance('current_company', $this->company);
    }

    private function activeBoundary(int $included = 1, int $covered = 0): BoundaryAssessment
    {
        $assessment = BoundaryAssessment::create([
            'company_id' => $this->company->id,
            'reporting_year' => (int) now()->year,
            'status' => 'active',
            'version' => 1,
        ]);

        for ($i = 0; $i < $included; $i++) {
            BoundaryItem::create([
                'company_id' => $this->company->id,
                'boundary_assessment_id' => $assessment->id,
                'scope' => 1,
                'suggested_name' => 'Diesel generators '.$i,
                'decision' => 'included',
                'materiality' => 'high',
                'emission_source_id' => null,
            ]);
        }

        return $assessment;
    }

    private function record(array $overrides = []): EmissionRecord
    {
        return EmissionRecord::create(array_merge([
            'company_id' => $this->company->id,
            'entry_date' => now()->startOfYear()->addMonth()->toDateString(),
            'scope' => 1,
            'facility' => 'Main Plant',
            'emission_source' => 'Diesel',
            'activity_data' => 100,
            'activity_unit' => 'liters',
            'co2e_value' => 268,
            'status' => 'active',
            // The column defaults to 'estimated', which trips the "your data is
            // estimated" step. These stand for metered figures unless a test
            // says otherwise.
            'data_quality' => 'primary',
        ], $overrides));
    }

    // ---------------------------------------------------------------------

    public function test_the_dashboard_shows_what_to_do_next(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('What to do next', false);
        $response->assertViewHas('nextSteps');
    }

    public function test_a_company_with_no_boundary_is_told_to_scope_one_first(): void
    {
        $response = $this->get(route('home'));

        // Scoping the boundary comes before entering anything — without it the
        // client is guessing at what to measure.
        $steps = $response->viewData('nextSteps');

        $this->assertSame('Work out what you actually need to measure', $steps[0]['title']);
        $this->assertSame('boundary.index', $steps[0]['route']);
        $this->assertSame('high', $steps[0]['sev']);

        $response->assertSee('Scope my boundary', false);
    }

    public function test_boundary_gaps_become_a_step_that_names_them(): void
    {
        $this->activeBoundary(included: 2);

        $steps = $this->get(route('home'))->viewData('nextSteps');

        $this->assertStringContainsString('items in your boundary have', $steps[0]['title']);
        $this->assertStringContainsString('Diesel generators', $steps[0]['sub']);
    }

    public function test_only_the_top_three_appear_with_the_rest_counted(): void
    {
        // No boundary, no Scope 1 data, no Scope 2 data, plus a pending review —
        // comfortably more steps than the panel shows.
        $this->record(['status' => 'draft', 'scope' => 3]);

        $response = $this->get(route('home'));

        // A prompt, not the report: the full list stays on Data Health.
        $this->assertLessThanOrEqual(3, count($response->viewData('nextSteps')));
        $this->assertGreaterThan(0, $response->viewData('remainingSteps'));
    }

    public function test_a_complete_inventory_is_told_so_rather_than_shown_an_empty_panel(): void
    {
        $assessment = $this->activeBoundary(included: 0);
        $this->assertNotNull($assessment);

        // Data in both enabled scopes, nothing pending, every month covered.
        for ($month = 1; $month <= (int) now()->month; $month++) {
            $this->record(['entry_date' => now()->startOfYear()->addMonths($month - 1)->toDateString()]);
            $this->record([
                'entry_date' => now()->startOfYear()->addMonths($month - 1)->toDateString(),
                'scope' => 2,
                'emission_source' => 'Grid electricity',
            ]);
        }

        $steps = $this->get(route('home'))->viewData('nextSteps');

        $this->assertSame('done', $steps[0]['sev']);
        $this->assertStringContainsString('looks complete', $steps[0]['title']);
    }

    public function test_the_panel_links_to_the_full_picture(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee(route('data_health.index'), false);
        $response->assertSee('Data health', false);
        $response->assertViewHas('healthScore');
    }

    // ---------------------------------------------------------------------
    // The two screens must not disagree
    // ---------------------------------------------------------------------

    public function test_the_dashboard_and_data_health_agree_on_what_matters(): void
    {
        $this->activeBoundary(included: 2);
        $this->record(['status' => 'draft']);

        $dashboard = $this->get(route('home'))->viewData('nextSteps');
        $dataHealth = $this->get(route('data_health.index'))->viewData('steps');

        // The dashboard shows a prefix of the same list, in the same order —
        // one implementation, so they cannot come to different conclusions.
        $this->assertSame(
            array_column(array_slice($dataHealth, 0, count($dashboard)), 'title'),
            array_column($dashboard, 'title'),
        );
    }

    public function test_the_service_answers_for_a_company_with_nothing_at_all(): void
    {
        // Reached on a brand-new tenant before any data exists; it must not
        // divide by zero or assume a boundary.
        $assessment = app(DataHealthService::class)->assess($this->company);

        $this->assertIsArray($assessment['steps']);
        $this->assertNotEmpty($assessment['steps']);
        $this->assertIsInt($assessment['healthScore']);
        $this->assertFalse($assessment['boundary']['has_boundary']);
    }

    public function test_the_service_answers_when_there_is_no_company_selected(): void
    {
        // An account owner who has not picked a company yet. current_company()
        // returns null and the dashboard still has to render.
        $assessment = app(DataHealthService::class)->assess(null);

        $this->assertFalse($assessment['boundary']['has_boundary']);
        $this->assertNotEmpty($assessment['steps']);
    }

    public function test_every_step_points_at_a_route_that_exists(): void
    {
        // Each step is rendered as route($step['route']); an unregistered name
        // would be a 500 on the dashboard rather than a broken link.
        $this->record(['status' => 'draft']);

        foreach (app(DataHealthService::class)->assess($this->company)['steps'] as $step) {
            $this->assertNotNull(
                app('router')->getRoutes()->getByName($step['route']),
                "Step \"{$step['title']}\" points at unknown route {$step['route']}."
            );
        }
    }
}

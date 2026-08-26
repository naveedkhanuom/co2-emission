<?php

namespace Tests\Feature;

use App\Models\BoundaryAssessment;
use App\Models\BoundaryItem;
use App\Models\Company;
use App\Models\EmissionRecord;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;
use Tests\TenantTestCase;

/**
 * Boundary coverage on the Data Health page.
 *
 * Every other dimension on that page measures data the company happened to
 * enter. This one measures it against what the company itself decided it needs
 * to measure — so it is the number that turns the boundary from a one-off
 * wizard into a recurring work queue.
 */
class DataHealthBoundaryTest extends TenantTestCase
{
    private function makeCompany(): Company
    {
        return Company::create([
            'name' => 'Health Test Co',
            'industry_type' => 'manufacturing',
            'is_active' => true,
        ]);
    }

    private function makeUser(Company $company): User
    {
        $user = User::create([
            'name' => 'Health Test User',
            'email' => 'health-test-'.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->givePermissionTo('list-dashboard');

        return $user;
    }

    private function makeActiveBoundary(Company $company, int $year): BoundaryAssessment
    {
        return BoundaryAssessment::create([
            'company_id' => $company->id,
            'reporting_year' => $year,
            'status' => 'active',
            'version' => 97,
        ]);
    }

    private function makeItem(BoundaryAssessment $assessment, string $name, string $materiality = 'high'): BoundaryItem
    {
        return BoundaryItem::create([
            'company_id' => $assessment->company_id,
            'boundary_assessment_id' => $assessment->id,
            'scope' => 1,
            'suggested_name' => $name,
            'materiality' => $materiality,
            'relevance' => 'relevant',
            'decision' => 'included',
            'source' => 'template',
        ]);
    }

    /** The reporting year Data Health uses — the base year setting, else now. */
    private function reportingYear(Company $company): int
    {
        return (int) ($company->getSetting('base_year', now()->year) ?? now()->year);
    }

    /**
     * A company that has never run the advisor must not be penalised — the
     * dimension is "not applicable", not zero. Scoring it zero would drop every
     * existing tenant's health score overnight for a feature they have not been
     * asked to use.
     */
    public function test_company_without_a_boundary_is_prompted_not_penalised(): void
    {
        $company = $this->makeCompany();
        $user = $this->makeUser($company);

        $response = $this->actingAs($user)->get(route('data_health.index'));

        $response->assertOk();
        $response->assertSee('Boundary not scoped yet');
        $response->assertSee('Work out what you actually need to measure');

        $boundaryDimension = collect($response->viewData('dimensions'))->firstWhere('key', 'boundary');
        $this->assertNull($boundaryDimension['score'], 'An unscoped boundary must not score zero.');
    }

    /** With a boundary and matching data, coverage reports 100%. */
    public function test_fully_covered_boundary_scores_100(): void
    {
        $company = $this->makeCompany();
        $user = $this->makeUser($company);
        $year = $this->reportingYear($company);

        $assessment = $this->makeActiveBoundary($company, $year);
        $this->makeItem($assessment, 'Diesel');

        EmissionRecord::create([
            'company_id' => $company->id,
            'entry_date' => now()->setYear($year)->startOfYear()->addMonth(),
            'scope' => 1,
            'facility' => 'Main Site',
            'emission_source' => 'Diesel',
            'activity_data' => 100,
            'emission_factor' => 0.00268,
            'co2e_value' => 0.268,
        ]);

        $response = $this->actingAs($user)->get(route('data_health.index'));

        $response->assertOk();

        $dimension = collect($response->viewData('dimensions'))->firstWhere('key', 'boundary');
        $this->assertSame(100, $dimension['score']);
    }

    /** Gaps become a named, prioritised next step rather than a bare number. */
    public function test_gaps_surface_as_a_next_step_naming_the_biggest(): void
    {
        $company = $this->makeCompany();
        $user = $this->makeUser($company);
        $year = $this->reportingYear($company);

        $assessment = $this->makeActiveBoundary($company, $year);
        $this->makeItem($assessment, 'Refrigerant Leakage', 'high');
        $this->makeItem($assessment, 'Office Paper', 'low');

        $response = $this->actingAs($user)->get(route('data_health.index'));

        $response->assertOk();
        $response->assertSee('Refrigerant Leakage');

        $dimension = collect($response->viewData('dimensions'))->firstWhere('key', 'boundary');
        $this->assertSame(0, $dimension['score']);

        $steps = collect($response->viewData('steps'));
        $boundaryStep = $steps->first(fn ($s) => str_contains($s['title'], 'boundary'));

        $this->assertNotNull($boundaryStep, 'Boundary gaps must produce a next step.');
        $this->assertSame('boundary.index', $boundaryStep['route']);
        $this->assertStringContainsString(
            'Refrigerant Leakage',
            $boundaryStep['sub'],
            'The highest-impact gap should be named first.'
        );
    }

    /** Another company's boundary must never appear on your Data Health page. */
    public function test_boundary_is_company_scoped(): void
    {
        $mine = $this->makeCompany();
        $theirs = $this->makeCompany();
        $user = $this->makeUser($mine);

        $assessment = $this->makeActiveBoundary($theirs, $this->reportingYear($theirs));
        $this->makeItem($assessment, 'Their Secret Source');

        $response = $this->actingAs($user)->get(route('data_health.index'));

        $response->assertOk();
        $response->assertDontSee('Their Secret Source');
    }
}

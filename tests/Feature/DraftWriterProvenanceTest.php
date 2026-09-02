<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\EmissionRecord;
use App\Models\ReportingPeriod;
use App\Models\Scope3Category;
use App\Models\Supplier;
use App\Models\SupplierSurvey;
use App\Models\User;
use App\Services\SupplierSurveyEmissionConverter;
use App\Support\Gwp;
use Tests\TenantTestCase;

/**
 * TEN-15 / TEN-16 — the draft-creating writers had neither provenance nor a
 * period-lock check.
 *
 * Three paths create EmissionRecords outside the manual-entry controller: the
 * utility-bill OCR upload, the supplier-survey converter, and the AI document
 * extraction. All three wrote rows that stated no GWP basis and carried no
 * emission_factor_id — the same defect as the closed GHG-02, in doors that were
 * missed — and none of them consulted the reporting-period lock.
 *
 * Both parts matter for the same reason. A figure with no GWP basis cannot be
 * disclosed under CSRD/ESRS E1 or CDP, and a draft filed against a locked year
 * can never be approved (ReviewDataController blocks it), so it sits in the
 * queue forever looking like outstanding work.
 *
 * This covers the supplier-survey converter, which is the sharpest case: it is
 * reachable from the PUBLIC supplier portal, so an unauthenticated third party
 * triggers the write. The OCR and AI paths share the fix and are exercised
 * through their own controllers elsewhere.
 */
class DraftWriterProvenanceTest extends TenantTestCase
{
    private Company $company;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Draft Writer Co '.uniqid(),
            'industry_type' => 'manufacturing',
            'is_active' => true,
        ]);

        $this->user = User::create([
            'name' => 'Draft Writer User',
            'email' => 'draft-writer-'.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
        ]);

        app()->instance('current_company_id', $this->company->id);
        app()->instance('current_company', $this->company);
    }

    private function makeSurvey(string $completedAt): SupplierSurvey
    {
        $supplier = Supplier::create([
            'company_id' => $this->company->id,
            'name' => 'Acme Supplies '.uniqid(),
            'email' => 'supplier-'.uniqid().'@example.test',
        ]);

        $categoryId = Scope3Category::query()->value('id');
        $this->assertNotNull($categoryId, 'Scope 3 categories are not seeded in this tenant.');

        return SupplierSurvey::create([
            'company_id' => $this->company->id,
            'supplier_id' => $supplier->id,
            'survey_type' => 'emissions',
            'title' => 'Annual supplier survey',
            'questions' => [[
                'question' => 'Diesel consumed delivering to us',
                'maps_to_emissions' => true,
                'scope3_category_id' => $categoryId,
                'emission_factor' => 0.00268,
                'activity_unit' => 'litres',
            ]],
            'responses' => [1000],
            'status' => 'completed',
            'completed_at' => $completedAt,
            'created_by' => $this->user->id,
        ]);
    }

    private function convert(SupplierSurvey $survey): int
    {
        return app(SupplierSurveyEmissionConverter::class)->convert($survey);
    }

    private function recordFor(SupplierSurvey $survey): ?EmissionRecord
    {
        return EmissionRecord::where('company_id', $this->company->id)
            ->where('supplier_id', $survey->supplier_id)
            ->latest('id')
            ->first();
    }

    public function test_a_converted_survey_record_states_its_gwp_basis(): void
    {
        $survey = $this->makeSurvey('2025-06-01');

        $this->assertSame(1, $this->convert($survey));

        $record = $this->recordFor($survey);
        $this->assertNotNull($record);
        $this->assertSame(
            Gwp::factorBasis(),
            $record->gwp_version,
            'A supplier-reported figure was stored without the GWP set it was computed under.'
        );
    }

    /**
     * The unit was already known at the point of conversion and was being
     * written only into the notes prose. Without the column,
     * EmissionFigureVerifier cannot re-derive activity x factor.
     */
    public function test_a_converted_survey_record_keeps_its_activity_unit(): void
    {
        $survey = $this->makeSurvey('2025-06-01');
        $this->convert($survey);

        $this->assertSame('litres', $this->recordFor($survey)?->activity_unit);
    }

    public function test_a_survey_in_a_locked_period_creates_no_records(): void
    {
        ReportingPeriod::create([
            'company_id' => $this->company->id,
            'year' => 2024,
            'status' => 'locked',
            'locked_at' => now(),
        ]);

        $survey = $this->makeSurvey('2024-06-01');
        $before = EmissionRecord::where('company_id', $this->company->id)->count();

        $this->assertSame(0, $this->convert($survey));
        $this->assertSame(
            $before,
            EmissionRecord::where('company_id', $this->company->id)->count(),
            'A supplier survey wrote emission records into a locked reporting period.'
        );
    }

    /**
     * Conversion is idempotent on emissions_generated_at, so refusing a locked
     * period must NOT stamp it — otherwise reopening the year later would leave
     * the supplier's answers permanently stranded.
     */
    public function test_a_locked_period_leaves_the_survey_convertible_later(): void
    {
        $period = ReportingPeriod::create([
            'company_id' => $this->company->id,
            'year' => 2024,
            'status' => 'locked',
            'locked_at' => now(),
        ]);

        $survey = $this->makeSurvey('2024-06-01');
        $this->convert($survey);

        $this->assertNull(
            $survey->fresh()->emissions_generated_at,
            'The survey was marked converted despite producing nothing, stranding its answers.'
        );

        // Reopen the year; the same survey must now convert.
        $period->update(['status' => 'open', 'locked_at' => null]);

        $this->assertSame(1, $this->convert($survey->fresh()));
    }

    public function test_an_open_period_still_converts_normally(): void
    {
        $survey = $this->makeSurvey('2025-06-01');

        $this->assertSame(1, $this->convert($survey));
        $this->assertEqualsWithDelta(2.68, (float) $this->recordFor($survey)?->co2e_value, 0.0001);
    }
}

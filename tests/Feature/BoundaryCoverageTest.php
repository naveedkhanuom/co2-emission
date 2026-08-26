<?php

namespace Tests\Feature;

use App\Models\BoundaryAssessment;
use App\Models\BoundaryItem;
use App\Models\Company;
use App\Models\EmissionRecord;
use App\Models\Scope3Category;
use App\Services\Boundary\BoundaryCoverageService;
use Tests\TenantTestCase;

/**
 * Boundary coverage — "14 of 19 things you said you'd measure have data".
 *
 * These run against the real schema on purpose. The first version of this
 * service matched on `emission_records.emission_source_id`, a column that does
 * not exist: that table identifies its source by NAME. A test that stubbed the
 * database would not have caught it, so these use real rows.
 */
class BoundaryCoverageTest extends TenantTestCase
{
    private BoundaryCoverageService $coverage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->coverage = app(BoundaryCoverageService::class);
    }

    private function makeCompany(): Company
    {
        return Company::create([
            'name' => 'Coverage Test Co',
            'industry_type' => 'transportation',
            'is_active' => true,
        ]);
    }

    private function makeActiveAssessment(Company $company): BoundaryAssessment
    {
        return BoundaryAssessment::create([
            'company_id' => $company->id,
            'reporting_year' => (int) now()->year,
            'status' => 'active',
            'version' => 98,
        ]);
    }

    private function makeItem(BoundaryAssessment $assessment, array $attributes = []): BoundaryItem
    {
        return BoundaryItem::create(array_merge([
            'company_id' => $assessment->company_id,
            'boundary_assessment_id' => $assessment->id,
            'scope' => 1,
            'suggested_name' => 'Diesel',
            'materiality' => 'high',
            'relevance' => 'relevant',
            'decision' => 'included',
            'source' => 'template',
        ], $attributes));
    }

    private function makeRecord(Company $company, array $attributes = []): EmissionRecord
    {
        return EmissionRecord::create(array_merge([
            'company_id' => $company->id,
            'entry_date' => now()->startOfYear()->addMonth(),
            'scope' => 1,
            'facility' => 'Main Site',
            'emission_source' => 'Diesel',
            'activity_data' => 100,
            'emission_factor' => 0.00268,
            'co2e_value' => 0.268,
        ], $attributes));
    }

    /** No boundary yet means no coverage claim, not a crash. */
    public function test_company_without_a_boundary_reports_nothing(): void
    {
        $result = $this->coverage->forCompany($this->makeCompany());

        $this->assertFalse($result['has_boundary']);
        $this->assertSame(0, $result['total']);
    }

    /** An included item with a matching record counts as covered. */
    public function test_matching_record_covers_the_item(): void
    {
        $company = $this->makeCompany();
        $assessment = $this->makeActiveAssessment($company);
        $this->makeItem($assessment, ['suggested_name' => 'Diesel']);
        $this->makeRecord($company, ['emission_source' => 'Diesel']);

        $result = $this->coverage->forCompany($company, (int) now()->year);

        $this->assertTrue($result['has_boundary']);
        $this->assertSame(1, $result['total']);
        $this->assertSame(1, $result['covered']);
        $this->assertSame(100.0, $result['percent']);
    }

    /** An included item with no data is a gap the user still has to close. */
    public function test_item_without_data_is_a_gap(): void
    {
        $company = $this->makeCompany();
        $assessment = $this->makeActiveAssessment($company);
        $this->makeItem($assessment, ['suggested_name' => 'Refrigerant Leakage']);

        $result = $this->coverage->forCompany($company, (int) now()->year);

        $this->assertSame(0, $result['covered']);
        $this->assertCount(1, $result['gaps']);
        $this->assertSame('Refrigerant Leakage', $result['gaps']->first()->suggested_name);
    }

    /** Scope 3 matches on category, which is exact — not on fuzzy text. */
    public function test_scope3_item_is_covered_by_category_match(): void
    {
        $category = Scope3Category::orderBy('sort_order')->firstOrFail();

        $company = $this->makeCompany();
        $assessment = $this->makeActiveAssessment($company);
        $this->makeItem($assessment, [
            'scope' => 3,
            'scope3_category_id' => $category->id,
            'suggested_name' => 'Something worded completely differently',
        ]);
        $this->makeRecord($company, [
            'scope' => 3,
            'scope3_category_id' => $category->id,
            'emission_source' => 'Scope 3 - 1. Purchased Goods & Services',
        ]);

        $result = $this->coverage->forCompany($company, (int) now()->year);

        $this->assertSame(1, $result['covered']);
    }

    /** Records from another year do not close this year's gap. */
    public function test_record_from_another_year_does_not_cover(): void
    {
        $company = $this->makeCompany();
        $assessment = $this->makeActiveAssessment($company);
        $this->makeItem($assessment, ['suggested_name' => 'Diesel']);
        $this->makeRecord($company, [
            'emission_source' => 'Diesel',
            'entry_date' => now()->subYears(2),
        ]);

        $result = $this->coverage->forCompany($company, (int) now()->year);

        $this->assertSame(0, $result['covered']);
    }

    /** Only accepted items are counted — excluded ones are not gaps. */
    public function test_excluded_items_are_not_counted(): void
    {
        $company = $this->makeCompany();
        $assessment = $this->makeActiveAssessment($company);
        $this->makeItem($assessment, ['decision' => 'included', 'suggested_name' => 'Diesel']);
        $this->makeItem($assessment, ['decision' => 'excluded', 'suggested_name' => 'Livestock']);
        $this->makeItem($assessment, ['decision' => 'pending', 'suggested_name' => 'Coal']);

        $result = $this->coverage->forCompany($company, (int) now()->year);

        $this->assertSame(1, $result['total'], 'Only included items belong to the boundary.');
    }

    /** Another company's records can never close your gaps. */
    public function test_another_companys_records_do_not_cover(): void
    {
        $mine = $this->makeCompany();
        $theirs = $this->makeCompany();

        $assessment = $this->makeActiveAssessment($mine);
        $this->makeItem($assessment, ['suggested_name' => 'Diesel']);
        $this->makeRecord($theirs, ['emission_source' => 'Diesel']);

        $result = $this->coverage->forCompany($mine, (int) now()->year);

        $this->assertSame(0, $result['covered']);
    }

    /**
     * Real entered names are messy — "Scope 3 - 7. Employee Commuting",
     * "Purchased Electricity (Location-Based)". Normalisation has to see past
     * the prefix and punctuation.
     */
    public function test_messy_source_names_still_match(): void
    {
        $company = $this->makeCompany();
        $assessment = $this->makeActiveAssessment($company);
        $this->makeItem($assessment, ['scope' => 2, 'suggested_name' => 'Electricity']);
        $this->makeRecord($company, [
            'scope' => 2,
            'emission_source' => 'Purchased Electricity (Location-Based)',
        ]);

        $result = $this->coverage->forCompany($company, (int) now()->year);

        $this->assertSame(1, $result['covered']);
    }

    /** Coverage must not claim a gap is closed by an unrelated source. */
    public function test_unrelated_source_does_not_cover(): void
    {
        $company = $this->makeCompany();
        $assessment = $this->makeActiveAssessment($company);
        $this->makeItem($assessment, ['suggested_name' => 'Refrigerant Leakage']);
        $this->makeRecord($company, ['emission_source' => 'Purchased Electricity']);

        $result = $this->coverage->forCompany($company, (int) now()->year);

        $this->assertSame(0, $result['covered'], 'Coverage must err towards under-counting.');
    }

    /** The highest-impact gap surfaces first — that is the "do this next" line. */
    public function test_top_gaps_are_ordered_by_impact(): void
    {
        $company = $this->makeCompany();
        $assessment = $this->makeActiveAssessment($company);
        $this->makeItem($assessment, ['suggested_name' => 'Minor Thing', 'materiality' => 'low']);
        $this->makeItem($assessment, ['suggested_name' => 'Major Thing', 'materiality' => 'high']);

        $gaps = $this->coverage->topGaps($company, (int) now()->year);

        $this->assertSame('Major Thing', $gaps->first()->suggested_name);
    }
}

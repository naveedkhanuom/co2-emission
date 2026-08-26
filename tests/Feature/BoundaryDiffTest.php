<?php

namespace Tests\Feature;

use App\Models\BoundaryAssessment;
use App\Models\BoundaryItem;
use App\Models\Company;
use App\Models\Scope3Category;
use App\Services\Boundary\BoundaryDiffService;
use Tests\TenantTestCase;

/**
 * Year-on-year boundary comparison.
 *
 * A boundary that changes between years is a GHG Protocol trigger for
 * recalculating the base year. Without this, a source quietly entering or
 * leaving the inventory shows up only as an unexplained jump in the totals —
 * which is precisely the finding an assurer raises.
 */
class BoundaryDiffTest extends TenantTestCase
{
    private BoundaryDiffService $diff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->diff = app(BoundaryDiffService::class);
    }

    private function makeCompany(): Company
    {
        return Company::create([
            'name' => 'Diff Test Co',
            'industry_type' => 'manufacturing',
            'is_active' => true,
        ]);
    }

    private function makeAssessment(Company $company, int $year, int $version, string $status): BoundaryAssessment
    {
        return BoundaryAssessment::create([
            'company_id' => $company->id,
            'reporting_year' => $year,
            'status' => $status,
            'version' => $version,
        ]);
    }

    private function makeItem(BoundaryAssessment $a, string $name, array $attributes = []): BoundaryItem
    {
        return BoundaryItem::create(array_merge([
            'company_id' => $a->company_id,
            'boundary_assessment_id' => $a->id,
            'scope' => 1,
            'suggested_name' => $name,
            'materiality' => 'medium',
            'relevance' => 'relevant',
            'decision' => 'included',
            'source' => 'template',
        ], $attributes));
    }

    /** A company's first-ever boundary has nothing to compare against. */
    public function test_first_assessment_has_no_diff(): void
    {
        $company = $this->makeCompany();
        $current = $this->makeAssessment($company, 2026, 1, 'active');
        $this->makeItem($current, 'Diesel');

        $this->assertNull($this->diff->forCompany($company));
    }

    /** Sources present in both years are not reported as changes. */
    public function test_unchanged_boundary_reports_no_changes(): void
    {
        $company = $this->makeCompany();

        $previous = $this->makeAssessment($company, 2025, 1, 'superseded');
        $this->makeItem($previous, 'Diesel');

        $current = $this->makeAssessment($company, 2026, 1, 'active');
        $this->makeItem($current, 'Diesel');

        $diff = $this->diff->forCompany($company);

        $this->assertFalse($diff['has_changes']);
        $this->assertFalse($diff['requires_recalculation']);
        $this->assertCount(0, $diff['added']);
        $this->assertCount(0, $diff['removed']);
    }

    /**
     * Items are regenerated each year, so matching has to survive rewording.
     * The same Scope 3 category is the same source even under a different name.
     */
    public function test_same_scope3_category_matches_despite_renaming(): void
    {
        $company = $this->makeCompany();
        $category = Scope3Category::where('sort_order', 1)->firstOrFail();

        $previous = $this->makeAssessment($company, 2025, 1, 'superseded');
        $this->makeItem($previous, 'Purchased goods', ['scope' => 3, 'scope3_category_id' => $category->id]);

        $current = $this->makeAssessment($company, 2026, 1, 'active');
        $this->makeItem($current, 'Raw materials and components', ['scope' => 3, 'scope3_category_id' => $category->id]);

        $diff = $this->diff->forCompany($company);

        $this->assertFalse($diff['has_changes'], 'A renamed source in the same category is not a boundary change.');
    }

    /** A new high-impact source is a recalculation candidate. */
    public function test_added_high_impact_source_flags_recalculation(): void
    {
        $company = $this->makeCompany();

        $previous = $this->makeAssessment($company, 2025, 1, 'superseded');
        $this->makeItem($previous, 'Diesel');

        $current = $this->makeAssessment($company, 2026, 1, 'active');
        $this->makeItem($current, 'Diesel');
        $this->makeItem($current, 'Process emissions', ['materiality' => 'high']);

        $diff = $this->diff->forCompany($company);

        $this->assertTrue($diff['has_changes']);
        $this->assertTrue($diff['requires_recalculation']);
        $this->assertCount(1, $diff['added']);
        $this->assertSame('Process emissions', $diff['added']->first()->suggested_name);
        $this->assertStringContainsString('Process emissions', implode(' ', $diff['reasons']));
    }

    /** A low-impact addition is a change, but not a recalculation trigger. */
    public function test_low_impact_addition_does_not_flag_recalculation(): void
    {
        $company = $this->makeCompany();

        $previous = $this->makeAssessment($company, 2025, 1, 'superseded');
        $this->makeItem($previous, 'Diesel');

        $current = $this->makeAssessment($company, 2026, 1, 'active');
        $this->makeItem($current, 'Diesel');
        $this->makeItem($current, 'Office paper', ['materiality' => 'low']);

        $diff = $this->diff->forCompany($company);

        $this->assertTrue($diff['has_changes'], 'The addition is still reported.');
        $this->assertFalse($diff['requires_recalculation'], 'A low-impact source should not trigger recalculation.');
    }

    /** A source dropping out of the boundary is reported and flagged. */
    public function test_removed_high_impact_source_is_reported(): void
    {
        $company = $this->makeCompany();

        $previous = $this->makeAssessment($company, 2025, 1, 'superseded');
        $this->makeItem($previous, 'Diesel');
        $this->makeItem($previous, 'Coal', ['materiality' => 'high']);

        $current = $this->makeAssessment($company, 2026, 1, 'active');
        $this->makeItem($current, 'Diesel');

        $diff = $this->diff->forCompany($company);

        $this->assertCount(1, $diff['removed']);
        $this->assertSame('Coal', $diff['removed']->first()->suggested_name);
        $this->assertTrue($diff['requires_recalculation']);
        $this->assertStringContainsString('left the boundary', implode(' ', $diff['reasons']));
    }

    /** A Scope 3 category entering the inventory is a recalculation candidate. */
    public function test_new_scope3_category_flags_recalculation(): void
    {
        $company = $this->makeCompany();
        $category = Scope3Category::where('sort_order', 11)->firstOrFail();

        $previous = $this->makeAssessment($company, 2025, 1, 'superseded');
        $this->makeItem($previous, 'Diesel');

        $current = $this->makeAssessment($company, 2026, 1, 'active');
        $this->makeItem($current, 'Diesel');
        $this->makeItem($current, 'Use of sold products', [
            'scope' => 3,
            'scope3_category_id' => $category->id,
            'materiality' => 'low',
        ]);

        $diff = $this->diff->forCompany($company);

        $this->assertTrue($diff['requires_recalculation']);
        $this->assertStringContainsString('Scope 3', implode(' ', $diff['reasons']));
    }

    /** Reassessed materiality is reported without being an add or a remove. */
    public function test_materiality_change_is_reported_separately(): void
    {
        $company = $this->makeCompany();

        $previous = $this->makeAssessment($company, 2025, 1, 'superseded');
        $this->makeItem($previous, 'Diesel', ['materiality' => 'low']);

        $current = $this->makeAssessment($company, 2026, 1, 'active');
        $this->makeItem($current, 'Diesel', ['materiality' => 'high']);

        $diff = $this->diff->forCompany($company);

        $this->assertCount(0, $diff['added']);
        $this->assertCount(0, $diff['removed']);
        $this->assertCount(1, $diff['changed']);
        $this->assertSame('low', $diff['changed']->first()['from']);
        $this->assertSame('high', $diff['changed']->first()['to']);
    }

    /** Excluded items are not part of the boundary, so they are not diffed. */
    public function test_excluded_items_are_not_compared(): void
    {
        $company = $this->makeCompany();

        $previous = $this->makeAssessment($company, 2025, 1, 'superseded');
        $this->makeItem($previous, 'Diesel');
        $this->makeItem($previous, 'Livestock', ['decision' => 'excluded', 'exclusion_reason' => 'No livestock.']);

        $current = $this->makeAssessment($company, 2026, 1, 'active');
        $this->makeItem($current, 'Diesel');

        $diff = $this->diff->forCompany($company);

        $this->assertFalse($diff['has_changes'], 'An item excluded in both years is not a boundary change.');
    }

    /** A draft is work in progress and must never be treated as the prior year. */
    public function test_draft_is_never_used_as_the_previous_boundary(): void
    {
        $company = $this->makeCompany();

        $draft = $this->makeAssessment($company, 2026, 2, 'draft');
        $this->makeItem($draft, 'Something speculative', ['materiality' => 'high']);

        $previous = $this->makeAssessment($company, 2025, 1, 'superseded');
        $this->makeItem($previous, 'Diesel');

        $current = $this->makeAssessment($company, 2026, 1, 'active');
        $this->makeItem($current, 'Diesel');

        $diff = $this->diff->forCompany($company);

        $this->assertSame(2025, $diff['previous']['year']);
        $this->assertFalse($diff['has_changes']);
    }

    /** Another company's history can never leak into this company's diff. */
    public function test_diff_is_company_scoped(): void
    {
        $mine = $this->makeCompany();
        $theirs = $this->makeCompany();

        $theirPrevious = $this->makeAssessment($theirs, 2025, 1, 'superseded');
        $this->makeItem($theirPrevious, 'Their Coal', ['materiality' => 'high']);

        $current = $this->makeAssessment($mine, 2026, 1, 'active');
        $this->makeItem($current, 'Diesel');

        $this->assertNull(
            $this->diff->forCompany($mine),
            'With no prior boundary of its own, the company must have no diff.'
        );
    }
}

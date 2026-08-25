<?php

namespace Tests\Feature;

use App\Models\BoundaryAssessment;
use App\Models\BoundaryItem;
use App\Models\Company;
use App\Models\Scope3Category;
use App\Models\User;
use App\Services\Boundary\BoundaryStatementService;
use App\Services\DisclosureReportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * The Boundary Statement — the artefact an assurer reads.
 *
 * Two obligations drive these tests: the Corporate Value Chain Standard
 * requires a decision on all fifteen Scope 3 categories, and every exclusion
 * must carry a written justification. Both must hold in the rendered document,
 * not just in the database.
 */
class BoundaryStatementTest extends TestCase
{
    use DatabaseTransactions;

    private function makeCompany(): Company
    {
        return Company::create([
            'name' => 'Statement Test Co',
            'industry_type' => 'construction',
            'business_description' => 'We build residential towers in Abu Dhabi.',
            'is_active' => true,
        ]);
    }

    private function makeUser(Company $company): User
    {
        $user = User::create([
            'name' => 'Statement Test User',
            'email' => 'statement-test-'.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->givePermissionTo(['list-boundary', 'list-reports']);

        return $user;
    }

    private function makeAssessment(Company $company, string $status = 'active'): BoundaryAssessment
    {
        return BoundaryAssessment::create([
            'company_id' => $company->id,
            'reporting_year' => (int) now()->year,
            'status' => $status,
            'version' => 96,
            'generator' => 'ai',
            'model' => 'claude-sonnet-4-6',
            'prompt_version' => 'boundary-advisor-v1',
            'confidence' => 0.82,
            'summary' => 'Boundary for a general contractor.',
        ]);
    }

    private function makeItem(BoundaryAssessment $a, array $attributes): BoundaryItem
    {
        return BoundaryItem::create(array_merge([
            'company_id' => $a->company_id,
            'boundary_assessment_id' => $a->id,
            'scope' => 1,
            'suggested_name' => 'Site plant diesel',
            'materiality' => 'high',
            'relevance' => 'relevant',
            'decision' => 'included',
            'source' => 'ai',
        ], $attributes));
    }

    private function statementFor(Company $company, BoundaryAssessment $assessment): array
    {
        return app(BoundaryStatementService::class)->build($company, $assessment);
    }

    /**
     * All fifteen categories must appear in the screening table. A category the
     * company never touched is reported as "not screened" — omitting it would
     * read as a decision that was never made.
     */
    public function test_all_fifteen_scope3_categories_appear_in_the_screening(): void
    {
        $company = $this->makeCompany();
        $assessment = $this->makeAssessment($company);
        $this->makeItem($assessment, ['suggested_name' => 'Site plant diesel']);

        $screening = $this->statementFor($company, $assessment)['scope3_screening'];

        $this->assertCount(Scope3Category::count(), $screening);
        $this->assertSame(range(1, 15), collect($screening)->pluck('number')->all());

        foreach ($screening as $row) {
            $this->assertNotEmpty($row['reason'], "Category {$row['number']} must carry a reason.");
        }
    }

    /** An excluded category carries the user's own written justification. */
    public function test_exclusion_justification_is_carried_into_the_screening(): void
    {
        $company = $this->makeCompany();
        $assessment = $this->makeAssessment($company);
        $category = Scope3Category::where('sort_order', 14)->firstOrFail();

        $this->makeItem($assessment, [
            'scope' => 3,
            'scope3_category_id' => $category->id,
            'suggested_name' => 'Franchises',
            'decision' => 'excluded',
            'relevance' => 'not_relevant',
            'exclusion_reason' => 'We do not operate or grant any franchises.',
        ]);

        $row = collect($this->statementFor($company, $assessment)['scope3_screening'])
            ->firstWhere('number', 14);

        $this->assertSame('Not relevant — excluded', $row['status']);
        $this->assertSame('We do not operate or grant any franchises.', $row['reason']);
    }

    /** The narrative states the consolidation approach and the counts. */
    public function test_narrative_describes_the_boundary(): void
    {
        $company = $this->makeCompany();
        $assessment = $this->makeAssessment($company);
        $this->makeItem($assessment, ['suggested_name' => 'Site plant diesel']);
        $this->makeItem($assessment, ['scope' => 2, 'suggested_name' => 'Site electricity']);

        $statement = $this->statementFor($company, $assessment);

        $this->assertStringContainsString('Statement Test Co', $statement['narrative']);
        $this->assertStringContainsString((string) now()->year, $statement['narrative']);
        $this->assertStringContainsString('operational control', $statement['narrative']);
        $this->assertStringContainsString('2 emission sources', $statement['narrative']);
    }

    /** Provenance is on the record — an assurer asks how the boundary was derived. */
    public function test_statement_records_ai_provenance(): void
    {
        $company = $this->makeCompany();
        $assessment = $this->makeAssessment($company);

        $meta = $this->statementFor($company, $assessment)['meta'];

        $this->assertSame('AI-assisted, human-approved', $meta['method']);
        $this->assertSame('claude-sonnet-4-6', $meta['model']);
        $this->assertSame('boundary-advisor-v1', $meta['prompt_version']);
    }

    /** The PDF downloads and is a real PDF. */
    public function test_statement_pdf_downloads(): void
    {
        $company = $this->makeCompany();
        $user = $this->makeUser($company);
        $assessment = $this->makeAssessment($company);
        $this->makeItem($assessment, ['suggested_name' => 'Site plant diesel']);

        $response = $this->actingAs($user)->get(route('boundary.statement', $assessment));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    /** The spreadsheet export returns the screening table. */
    public function test_screening_spreadsheet_downloads(): void
    {
        $company = $this->makeCompany();
        $user = $this->makeUser($company);
        $assessment = $this->makeAssessment($company);

        $this->actingAs($user)
            ->get(route('boundary.statement', ['assessment' => $assessment, 'format' => 'csv']))
            ->assertOk();
    }

    /** Another company's statement is not downloadable. */
    public function test_statement_is_company_scoped(): void
    {
        $mine = $this->makeCompany();
        $theirs = $this->makeCompany();

        $user = $this->makeUser($mine);
        $foreign = $this->makeAssessment($theirs);

        $this->actingAs($user)
            ->get(route('boundary.statement', $foreign))
            ->assertNotFound();
    }

    /**
     * The disclosure report picks the boundary up automatically, so ESRS E1
     * gains the boundary rows without anyone re-entering them.
     */
    public function test_disclosure_gains_boundary_datapoints(): void
    {
        $company = $this->makeCompany();
        $assessment = $this->makeAssessment($company);
        $category = Scope3Category::where('sort_order', 1)->firstOrFail();

        $this->makeItem($assessment, ['suggested_name' => 'Site plant diesel']);
        $this->makeItem($assessment, [
            'scope' => 3,
            'scope3_category_id' => $category->id,
            'suggested_name' => 'Purchased concrete',
        ]);

        $service = app(DisclosureReportService::class);
        $data = $service->build($company->id, (int) now()->year);
        $datapoints = collect($service->datapoints('esrs_e1', $data));

        $this->assertNotNull($data['boundary']);
        $this->assertSame(2, $data['boundary']['included_sources']);
        $this->assertSame(1, $data['boundary']['scope3_relevant']);

        $labels = $datapoints->pluck('label');
        $this->assertTrue($labels->contains('Scope 3 categories screened as relevant'));
        $this->assertTrue($labels->contains('Operational boundary determined by'));
    }

    /**
     * A company with no boundary must not gain unevidenced boundary rows — an
     * unsupported claim on a filing is worse than no claim.
     */
    public function test_disclosure_omits_boundary_rows_without_an_assessment(): void
    {
        $company = $this->makeCompany();

        $service = app(DisclosureReportService::class);
        $data = $service->build($company->id, (int) now()->year);
        $datapoints = collect($service->datapoints('esrs_e1', $data));

        $this->assertNull($data['boundary']);
        $this->assertFalse($datapoints->pluck('label')->contains('Scope 3 categories screened as relevant'));
    }

    /** A draft boundary is not disclosed — only an activated one counts. */
    public function test_draft_boundary_is_not_disclosed(): void
    {
        $company = $this->makeCompany();
        $assessment = $this->makeAssessment($company, status: 'draft');
        $this->makeItem($assessment, ['suggested_name' => 'Site plant diesel']);

        $data = app(DisclosureReportService::class)->build($company->id, (int) now()->year);

        $this->assertNull($data['boundary'], 'Only an active boundary belongs on a disclosure.');
    }
}

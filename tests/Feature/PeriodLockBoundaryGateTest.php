<?php

namespace Tests\Feature;

use App\Models\BoundaryAssessment;
use App\Models\BoundaryItem;
use App\Models\Company;
use App\Models\ReportingPeriod;
use App\Models\Scope3Category;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;
use Tests\TenantTestCase;

/**
 * Locking a reporting period declares an inventory final, and that claim
 * depends on a decided boundary: you cannot say a footprint is complete
 * without having said what it was supposed to contain. The Scope 3 Standard
 * goes further and requires all 15 categories to be SCREENED for relevance —
 * deciding one is irrelevant is a complete answer, leaving it untouched is not.
 *
 * Before this, lock() checked nothing but the request. A client could freeze a
 * year having never opened the Boundary Advisor, and the resulting inventory
 * was indistinguishable from one that had been properly scoped.
 *
 * It is deliberately NOT a refusal. Clients are already live with years of
 * data and no boundary assessment, and taking a governance action away from a
 * paying account is worse than letting it proceed on the record. The written
 * acknowledgement is itself the assurance artefact — the same bargain
 * BoundaryItem strikes, where an exclusion is acceptable precisely because a
 * reason was recorded against it.
 *
 * So there are three behaviours to pin, and all three fail without the gate:
 * refuse-when-silent, allow-when-acknowledged, and stay-clean-when-complete.
 */
class PeriodLockBoundaryGateTest extends TenantTestCase
{
    private Company $company;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Lock Gate Co '.uniqid(),
            'industry_type' => 'manufacturing',
            'is_active' => true,
        ]);

        $this->user = User::create([
            'name' => 'Reviewer',
            'email' => 'reviewer-'.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->user->givePermissionTo(['list-review-data', 'edit-review-data']);

        $this->actingAs($this->user);
        app()->instance('current_company_id', $this->company->id);
        app()->instance('current_company', $this->company);
    }

    /**
     * An active boundary with every Scope 3 category screened — the state in
     * which locking should be the one-click action it has always been.
     */
    private function completeBoundary(): BoundaryAssessment
    {
        $assessment = BoundaryAssessment::create([
            'company_id' => $this->company->id,
            'reporting_year' => (int) now()->year,
            'status' => 'active',
            'version' => 1,
        ]);

        foreach (Scope3Category::all() as $category) {
            BoundaryItem::create([
                'company_id' => $this->company->id,
                'boundary_assessment_id' => $assessment->id,
                'scope' => 3,
                'scope3_category_id' => $category->id,
                'suggested_name' => 'Screened: '.$category->id,
                'decision' => 'excluded',
                'exclusion_reason' => 'Not material to this business.',
            ]);
        }

        return $assessment;
    }

    private function period(int $year): ?ReportingPeriod
    {
        return ReportingPeriod::withoutGlobalScope('company')
            ->where('company_id', $this->company->id)
            ->where('year', $year)
            ->first();
    }

    public function test_a_year_cannot_be_silently_locked_with_no_boundary_at_all(): void
    {
        $response = $this->post(route('reporting_periods.lock', 2026));

        $response->assertSessionHas('error');
        $this->assertStringContainsString(
            'no inventory boundary has been scoped',
            session('error')
        );

        $this->assertNull(
            $this->period(2026),
            'The year was frozen despite the company never having scoped a boundary.'
        );
    }

    public function test_a_year_cannot_be_silently_locked_with_scope_3_left_unscreened(): void
    {
        // Active boundary, but only one of the 15 categories screened.
        $assessment = BoundaryAssessment::create([
            'company_id' => $this->company->id,
            'reporting_year' => (int) now()->year,
            'status' => 'active',
            'version' => 1,
        ]);

        BoundaryItem::create([
            'company_id' => $this->company->id,
            'boundary_assessment_id' => $assessment->id,
            'scope' => 3,
            'scope3_category_id' => Scope3Category::first()->id,
            'suggested_name' => 'Purchased goods',
            'decision' => 'included',
        ]);

        $this->post(route('reporting_periods.lock', 2026))->assertSessionHas('error');

        $this->assertStringContainsString('Scope 3 categories have not been screened', session('error'));
        $this->assertNull($this->period(2026));
    }

    public function test_a_complete_boundary_locks_in_one_click_and_records_no_caveat(): void
    {
        $this->completeBoundary();

        $this->post(route('reporting_periods.lock', 2026))->assertSessionHas('success');

        $period = $this->period(2026);

        $this->assertSame('locked', $period->status);
        $this->assertSame($this->user->id, $period->locked_by);

        // Null here is the good state: the lock needed no excuse.
        $this->assertNull($period->boundary_ack_gap);
        $this->assertNull($period->boundary_ack_reason);
    }

    public function test_an_incomplete_boundary_can_still_be_locked_with_a_written_reason(): void
    {
        $this->post(route('reporting_periods.lock', 2026), [
            'boundary_ack_reason' => 'Scope 3 screening was completed in the 2025 consultant report filed outside this system.',
        ])->assertSessionHas('success');

        $period = $this->period(2026);

        $this->assertSame('locked', $period->status);
        $this->assertStringContainsString('2025 consultant report', $period->boundary_ack_reason);

        // The machine's finding is kept verbatim and separately from the
        // human's answer to it, so a later reader sees what was actually
        // missing rather than what the person locking believed was missing.
        $this->assertStringContainsString('no inventory boundary has been scoped', $period->boundary_ack_gap);
    }

    public function test_a_token_acknowledgement_is_not_accepted(): void
    {
        $this->post(route('reporting_periods.lock', 2026), [
            'boundary_ack_reason' => 'ok',
        ])->assertSessionHasErrors('boundary_ack_reason');

        $this->assertNull($this->period(2026));
    }

    public function test_an_acknowledgement_is_not_recorded_against_a_year_that_did_not_need_one(): void
    {
        $this->completeBoundary();

        // Someone types a reason anyway — pasted, or left over from a previous
        // attempt. Storing it would put a permanent caveat on a clean year.
        $this->post(route('reporting_periods.lock', 2026), [
            'boundary_ack_reason' => 'This text should not end up attached to a properly scoped year.',
        ])->assertSessionHas('success');

        $period = $this->period(2026);

        $this->assertSame('locked', $period->status);
        $this->assertNull($period->boundary_ack_reason);
        $this->assertNull($period->boundary_ack_gap);
    }

    public function test_the_periods_screen_warns_before_the_client_tries_to_file(): void
    {
        ReportingPeriod::create([
            'company_id' => $this->company->id,
            'year' => 2026,
            'status' => 'open',
        ]);

        $response = $this->get(route('reporting_periods.index'));

        $response->assertOk();
        $response->assertViewHas('boundaryGap');
        $response->assertSee('Your inventory boundary is not complete', false);
        $response->assertSee(route('boundary.index'), false);
    }

    public function test_the_warning_disappears_once_the_boundary_is_complete(): void
    {
        $this->completeBoundary();

        ReportingPeriod::create([
            'company_id' => $this->company->id,
            'year' => 2026,
            'status' => 'open',
        ]);

        $response = $this->get(route('reporting_periods.index'));

        $response->assertOk();
        $response->assertViewHas('boundaryGap', null);
        $response->assertDontSee('Your inventory boundary is not complete', false);
    }

    public function test_a_year_locked_without_a_boundary_carries_a_visible_caveat(): void
    {
        $this->post(route('reporting_periods.lock', 2026), [
            'boundary_ack_reason' => 'Screening held in the parent company inventory for this cycle.',
        ])->assertSessionHas('success');

        $this->get(route('reporting_periods.index'))
            ->assertOk()
            ->assertSee('Boundary incomplete at lock', false);
    }

    public function test_unlocking_is_unaffected_by_the_gate(): void
    {
        ReportingPeriod::create([
            'company_id' => $this->company->id,
            'year' => 2026,
            'status' => 'locked',
            'locked_at' => now(),
        ]);

        $this->post(route('reporting_periods.unlock', 2026))->assertSessionHas('success');

        $this->assertSame('open', $this->period(2026)->status);
    }
}

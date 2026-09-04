<?php

namespace Tests\Feature;

use App\Console\Commands\SeedDemoData;
use App\Models\Company;
use App\Models\EmissionRecord;
use App\Models\Facilities;
use App\Models\ReportingPeriod;
use App\Models\Target;
use App\Models\User;
use App\Services\Boundary\BoundaryCoverageService;
use Illuminate\Support\Facades\Hash;
use Tests\TenantTestCase;

/**
 * `demo:seed` writes a worked example inventory into one company.
 *
 * What is worth testing here is not that rows appear — it is the two claims the
 * command makes that would quietly stop being true:
 *
 *   1. The records go through the real save path, so they carry a locked
 *      factor, stated provenance and a figure that reconciles with its own
 *      activity data. Demo data that skipped enrichment would look right on a
 *      dashboard and be wrong everywhere an assurer looks — which is worse than
 *      no demo data, because it would also mask a regression in the enrichment
 *      pipeline itself.
 *
 *   2. It owns only what it wrote. A demo company is exactly the company
 *      someone has also been typing into by hand, and a re-run that took their
 *      entries with it would be a data-loss bug in a convenience command.
 */
class DemoDataSeedTest extends TenantTestCase
{
    private Company $company;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Demo Seed Test Co '.uniqid(),
            'is_active' => true,
        ]);

        $this->owner = User::create([
            'name' => 'Demo Owner',
            'email' => 'demo-owner-'.uniqid().'@example.test',
            'password' => Hash::make('secret-secret'),
            'company_id' => $this->company->id,
        ]);

        app()->instance('current_company_id', $this->company->id);
        app()->instance('current_company', $this->company);
    }

    private function loadDemo(int $years = 2): void
    {
        $this->artisan('demo:seed', [
            'tenant' => self::TEST_TENANT_ID,
            '--company' => $this->company->id,
            '--years' => $years,
        ])->assertExitCode(0);
    }

    /** @return \Illuminate\Database\Eloquent\Builder<EmissionRecord> */
    private function records()
    {
        return EmissionRecord::withoutGlobalScope('company')
            ->where('company_id', $this->company->id);
    }

    public function test_it_writes_an_inventory_across_all_three_scopes(): void
    {
        $this->loadDemo();

        $this->assertGreaterThan(0, $this->records()->count());

        foreach ([1, 2, 3] as $scope) {
            $this->assertGreaterThan(
                0,
                $this->records()->where('scope', $scope)->count(),
                "Scope {$scope} has no demo records."
            );
        }
    }

    public function test_every_activity_based_figure_reconciles_with_its_own_factor(): void
    {
        $this->loadDemo();

        $records = $this->records()->where('calculation_method', 'activity-based')->get();

        $this->assertNotEmpty($records);

        foreach ($records as $record) {
            $this->assertEqualsWithDelta(
                (float) $record->activity_data * (float) $record->emission_factor,
                (float) $record->co2e_value,
                0.01,
                "Record #{$record->id} does not equal its own activity data times its factor."
            );
        }
    }

    public function test_records_carry_the_provenance_the_real_entry_path_would_give_them(): void
    {
        $this->loadDemo();

        $activityBased = $this->records()->where('calculation_method', 'activity-based')->get();

        $this->assertNotEmpty($activityBased);

        foreach ($activityBased as $record) {
            $this->assertNotNull($record->emission_factor_id, "Record #{$record->id} locked no factor.");
            $this->assertNotNull($record->factor_dataset, "Record #{$record->id} states no provenance.");
            $this->assertNotNull($record->gwp_version, "Record #{$record->id} states no GWP basis.");
            $this->assertNotNull($record->facility_id, "Record #{$record->id} is not linked to a facility.");
        }
    }

    /**
     * The dimension check in EmissionFigureVerifier holds back any record whose
     * activity unit disagrees with the unit its factor is published per. Nothing
     * the command writes should trip it: if a stream is ever paired with the
     * wrong unit, this is where it shows up rather than in a demo where every
     * row has silently landed in the review queue.
     */
    public function test_no_stream_is_priced_in_the_wrong_unit(): void
    {
        $this->loadDemo();

        $held = $this->records()->where('notes', 'like', '%Held for review%')->count();

        $this->assertSame(0, $held, 'A demo stream was paired with a factor published per a different unit.');
    }

    public function test_closed_years_are_locked_and_the_current_year_is_not(): void
    {
        $this->loadDemo(3);

        $periods = ReportingPeriod::withoutGlobalScope('company')
            ->where('company_id', $this->company->id)
            ->orderBy('year')
            ->get();

        $this->assertCount(3, $periods);
        $this->assertTrue($periods->first()->is_base_year, 'The earliest year is not flagged as the base year.');

        $currentYear = (int) now()->year;

        foreach ($periods as $period) {
            $period->year < $currentYear
                ? $this->assertTrue($period->isLocked(), "{$period->year} should be locked.")
                : $this->assertFalse($period->isLocked(), "{$period->year} is the open year and should not be locked.");
        }
    }

    public function test_it_also_creates_the_structure_the_records_hang_off(): void
    {
        $this->loadDemo();

        $facilities = Facilities::withoutGlobalScope('company')
            ->where('company_id', $this->company->id)
            ->get();

        $this->assertCount(3, $facilities);
        $this->assertTrue($facilities->contains('mrv_enabled', true), 'No facility is enabled for MRV reporting.');

        $this->assertSame(2, Target::withoutGlobalScope('company')
            ->where('company_id', $this->company->id)->count());
    }

    /**
     * The boundary is the only completeness figure that means anything to an
     * auditor, and it is the first thing the dashboard asks a new company for.
     * A demo without one leaves Data Health with nothing to say.
     */
    public function test_it_screens_a_boundary_the_coverage_metric_can_measure_against(): void
    {
        $this->loadDemo();

        $assessment = $this->company->fresh()->activeBoundaryAssessment();

        $this->assertNotNull($assessment, 'No active boundary assessment was created.');

        // The GHG Protocol Scope 3 Standard requires all fifteen categories to
        // be screened, relevant or not. A demo that screens seven of them is
        // demonstrating an incomplete assessment.
        $this->assertSame(0, $assessment->unscreenedScope3Count());

        $excluded = $assessment->items()->where('decision', 'excluded')->get();

        $this->assertNotEmpty($excluded, 'Nothing was excluded, so the exclusion rationale is never exercised.');

        foreach ($excluded as $item) {
            $this->assertNotEmpty(
                $item->exclusion_reason,
                "Boundary item \"{$item->suggested_name}\" is excluded with no written reason."
            );
        }
    }

    /**
     * Partly covered on purpose. A boundary the records satisfy completely
     * would leave the gap list — the thing that turns the boundary from a
     * document into a work queue — empty in every demo.
     */
    public function test_boundary_coverage_is_neither_empty_nor_complete(): void
    {
        $this->loadDemo();

        $coverage = app(BoundaryCoverageService::class)
            ->forCompany($this->company->fresh(), (int) now()->year);

        $this->assertTrue($coverage['has_boundary']);
        $this->assertGreaterThan(0, $coverage['covered'], 'No boundary item is met by the demo records.');
        $this->assertGreaterThan(0, $coverage['gaps']->count(), 'The boundary has no gaps left to work on.');
        $this->assertLessThan($coverage['total'], $coverage['covered']);
    }

    /**
     * Same command, same inventory. The figures come from a hash of the stream
     * and the date rather than from mt_rand(), so a second run has to land on
     * the same totals — otherwise no report generated from the demo can be
     * compared with the one generated before it.
     */
    public function test_a_second_run_reproduces_the_same_inventory_exactly(): void
    {
        $this->loadDemo();

        $first = [
            'count' => $this->records()->count(),
            'total' => round((float) $this->records()->sum('co2e_value'), 4),
        ];

        $this->loadDemo();

        $this->assertSame($first['count'], $this->records()->count());
        $this->assertSame($first['total'], round((float) $this->records()->sum('co2e_value'), 4));
    }

    public function test_a_re_run_leaves_records_it_did_not_write_alone(): void
    {
        $this->loadDemo();

        $typedByHand = EmissionRecord::create([
            'company_id' => $this->company->id,
            'entry_date' => now()->startOfYear()->toDateString(),
            'facility' => 'Head Office — Dubai',
            'scope' => 1,
            'emission_source' => 'Diesel (Stationary)',
            'activity_data' => 100,
            'activity_unit' => 'liters',
            'co2e_value' => 0.268,
            'confidence_level' => 'high',
            'status' => 'active',
            'notes' => 'Entered by a person, not by the seeder.',
        ]);

        $this->loadDemo();

        $this->assertDatabaseHas('emission_records', ['id' => $typedByHand->id]);
    }

    public function test_it_refuses_an_account_that_does_not_exist(): void
    {
        $this->artisan('demo:seed', ['tenant' => 'no-such-account'])
            ->assertExitCode(1);
    }

    /**
     * The rows this command writes are indistinguishable from real ones once
     * saved — same service, same provenance, same locked factor. That is what
     * makes it useful for a demo and what makes it unacceptable in a client's
     * live account by accident.
     */
    public function test_it_refuses_to_run_in_production_unless_forced(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('demo:seed', [
            'tenant' => self::TEST_TENANT_ID,
            '--company' => $this->company->id,
        ])->assertExitCode(1);

        $this->assertSame(0, $this->records()->count());
    }

    public function test_everything_it_writes_carries_its_tag(): void
    {
        $this->loadDemo();

        $untagged = $this->records()
            ->where(function ($query) {
                $query->whereNull('notes')
                    ->orWhere('notes', 'not like', '%'.SeedDemoData::TAG.'%');
            })
            ->count();

        $this->assertSame(0, $untagged, 'A demo record was written without the tag that a re-run cleans up by.');
    }
}

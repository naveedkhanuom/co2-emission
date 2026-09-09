<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\EmissionRecord;
use App\Models\Facilities;
use App\Models\MrvEmissionSource;
use App\Models\MrvFacilityReport;
use App\Models\MrvSourceStream;
use App\Models\ReportingPeriod;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;
use Tests\TenantTestCase;

/**
 * Locking a reporting year has to reach the regulated submission too.
 *
 * Locking is this platform's governance action: it declares an inventory
 * final, and seven write paths consult it — manual entry, the Excel import,
 * OCR, AI extraction, the review queue, the supplier-survey converter. The MRV
 * layer consulted none of them, so a signed-off year's EAD submission stayed
 * editable indefinitely. Every test here was checked against the pre-fix
 * controller, where the write went through and the assertion below failed.
 *
 * WHERE THE LINE IS
 *
 * Figures are frozen; the monitoring plan is not. The plan sheets describe HOW
 * the facility monitors rather than what it emitted, and answering a
 * regulator's questions about a submitted plan is the thing the workbook exists
 * for — freezing them would make that impossible without unlocking the whole
 * inventory. The second-to-last test pins that exception so it stays
 * deliberate rather than becoming an oversight someone later "fixes".
 */
class MrvPeriodLockTest extends TenantTestCase
{
    private Company $company;

    private Facilities $facility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Ruwais Refinery Co '.uniqid(),
            'industry_type' => 'manufacturing',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'MRV Officer',
            'email' => 'mrv-lock-'.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->givePermissionTo(['list-reports', 'create-report', 'edit-report']);

        $this->actingAs($user);
        app()->instance('current_company_id', $this->company->id);
        app()->instance('current_company', $this->company);

        $this->facility = Facilities::create([
            'company_id' => $this->company->id,
            'name' => 'Ruwais Train 3',
            'mrv_enabled' => true,
        ]);
    }

    private function lockYear(int $year = 2026): void
    {
        ReportingPeriod::create([
            'company_id' => $this->company->id,
            'year' => $year,
            'status' => 'locked',
            'locked_at' => now(),
        ]);
    }

    private function stream(): MrvSourceStream
    {
        return MrvSourceStream::create([
            'company_id' => $this->company->id,
            'facility_id' => $this->facility->id,
            'reporting_year' => 2026,
            'stream_code' => 'F01',
            'classification' => 'fuel_combusted',
            'estimated_co2e' => 900,
        ]);
    }

    public function test_a_source_stream_cannot_be_added_to_a_locked_year(): void
    {
        $this->lockYear();

        $this->post(route('mrv.saveStream'), [
            'facility_id' => $this->facility->id,
            'year' => 2026,
            'stream_code' => 'F01',
            'classification' => 'fuel_combusted',
            'estimated_co2e' => 1200,
        ])->assertRedirect();

        $this->assertSame(0, MrvSourceStream::where('facility_id', $this->facility->id)->count());
        $this->assertStringContainsString('locked', session('error'));
    }

    public function test_an_existing_streams_figure_cannot_be_rewritten_in_a_locked_year(): void
    {
        $stream = $this->stream();
        $this->lockYear();

        $this->post(route('mrv.saveStream'), [
            'facility_id' => $this->facility->id,
            'year' => 2026,
            'stream_code' => 'F01',
            'classification' => 'fuel_combusted',
            'estimated_co2e' => 5,
        ])->assertRedirect();

        $this->assertEquals(900, $stream->fresh()->estimated_co2e);
    }

    public function test_an_emission_source_cannot_be_saved_into_a_locked_year(): void
    {
        $this->lockYear();

        $this->post(route('mrv.saveSource'), [
            'facility_id' => $this->facility->id,
            'year' => 2026,
            'source_code' => 'S01',
            'name' => 'Crude heater',
            'methodology' => 'calculation',
            'total_co2e' => 4000,
        ])->assertRedirect();

        $this->assertSame(0, MrvEmissionSource::where('facility_id', $this->facility->id)->count());
    }

    public function test_a_stream_cannot_be_deleted_out_of_a_locked_year(): void
    {
        $stream = $this->stream();
        $this->lockYear();

        $this->delete(route('mrv.deleteStream', $stream->id))->assertRedirect();

        $this->assertNotNull($stream->fresh(), 'The stream was deleted from a signed-off inventory.');
    }

    public function test_prefill_cannot_rebuild_a_locked_years_figures(): void
    {
        EmissionRecord::create([
            'company_id' => $this->company->id,
            'entry_date' => '2026-05-02',
            'scope' => 1,
            'facility' => $this->facility->name,
            'facility_id' => $this->facility->id,
            'emission_source' => 'Natural Gas',
            'activity_data' => 100,
            'activity_unit' => 'MWh',
            'co2e_value' => 20,
            'status' => 'active',
        ]);

        $this->lockYear();

        $this->post(route('mrv.prefill'), [
            'facility_id' => $this->facility->id,
            'year' => 2026,
        ])->assertRedirect();

        $this->assertSame(0, MrvSourceStream::where('facility_id', $this->facility->id)->count());
        $this->assertSame(0, MrvEmissionSource::where('facility_id', $this->facility->id)->count());
    }

    /**
     * The deliberate exception. EAD asks questions about a submitted plan, and
     * the answers are documentation rather than figures — a locked year must
     * not make them unanswerable.
     */
    public function test_the_monitoring_plan_stays_editable_after_the_year_is_locked(): void
    {
        $this->lockYear();

        $this->post(route('mrv.saveReport'), [
            'facility_id' => $this->facility->id,
            'year' => 2026,
            'section' => 'verification',
            'verification_text' => 'Reviewed against purchase records by the HSE manager.',
        ])->assertRedirect();

        $report = MrvFacilityReport::where('facility_id', $this->facility->id)
            ->where('reporting_year', 2026)
            ->first();

        $this->assertNotNull($report, 'The monitoring plan could not be saved for a locked year.');
        $this->assertStringContainsString('purchase records', $report->verification_text);
    }

    public function test_an_open_year_is_untouched_by_any_of_this(): void
    {
        $this->post(route('mrv.saveStream'), [
            'facility_id' => $this->facility->id,
            'year' => 2026,
            'stream_code' => 'F01',
            'classification' => 'fuel_combusted',
            'estimated_co2e' => 1200,
        ])->assertRedirect();

        $this->assertSame(1, MrvSourceStream::where('facility_id', $this->facility->id)->count());
    }
}

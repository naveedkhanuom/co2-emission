<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Facilities;
use App\Models\MrvEmissionSource;
use App\Models\MrvFacilityReport;
use App\Models\MrvMeasuringInstrument;
use App\Models\User;
use App\Services\MRV\EadWorkbookFiller;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Spatie\Permission\PermissionRegistrar;
use Tests\TenantTestCase;

/**
 * Sheets 3e1 and 3e2 — the measurement-based approach.
 *
 * A large combustion plant, a cement kiln or a steel works does not calculate
 * its CO2 from fuel invoices; it MEASURES the flue gas continuously and
 * integrates concentration against stack flow over the year. None of that fits
 * the calculation-based columns, and until now the MRV layer had nothing for it
 * at all — which excluded exactly the heavy-industry operators the EAD regime
 * is aimed at.
 *
 * THE CONSTRAINT THAT SHAPES 3e1
 *
 * Its emissions column is `='2c2_Facility Description'!G43` downward, so row 9
 * always reports the FIRST emission source's total whatever id sits beside it.
 * The sheet therefore cannot be a filtered list of measured sources: it has to
 * mirror 2c2's ordering, with the measurement-specific columns filled only
 * where measurement applies. test_the_measured_sheet_stays_aligned_with_2c2 is
 * the assertion that holds that together.
 */
class MrvMeasurementTest extends TenantTestCase
{
    private Company $company;

    private Facilities $facility;

    private string $fixture;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Emirates Steel '.uniqid(),
            'industry_type' => 'manufacturing',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'MRV Officer',
            'email' => 'cems-'.uniqid().'@example.test',
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
            'name' => 'EAF Line 1',
            'mrv_enabled' => true,
        ]);

        $this->fixture = $this->makeFixture();
        config(['mrv.ead_template' => $this->fixture]);
    }

    protected function tearDown(): void
    {
        if (isset($this->fixture) && is_file($this->fixture)) {
            @unlink($this->fixture);
        }

        parent::tearDown();
    }

    private function makeFixture(): string
    {
        $book = new Spreadsheet;

        $ids = $book->getActiveSheet();
        $ids->setTitle('2c1_ Identifiers');
        $ids->setCellValue('D5', 'Entity/Company name ');
        $ids->setCellValue('D7', 'Facility Name (as stated in the Environmental Permit)');
        $ids->setCellValue('C13', 'Description of the facility and its activities (including site diagrams if applicable) (*):');

        $facility = $book->createSheet();
        $facility->setTitle('2c2_Facility Description');
        $facility->setCellValue('C42', 'Emission source ID');
        $facility->setCellValue('C74', 'Source Stream ID');

        $tiers = $book->createSheet();
        $tiers->setTitle('3d1_Source Streams (Calculated)');
        $tiers->setCellValue('B9', 'Source stream ID');
        $tiers->setCellValue('C40', 'Tier level used ');

        $calc = $book->createSheet();
        $calc->setTitle('3d2_ Calculation Approaches');
        $calc->setCellValue('B59', 'Source Stream ID');
        $calc->setCellValue('F24', 'Source (e.g., maintenance records, fuel logs)');
        $calc->setCellValue('C91', 'Associated source stream (ID)');

        $measured = $book->createSheet();
        $measured->setTitle('3e1_Emission Sources (Measured)');
        $measured->setCellValue('B8', 'Emission source ID');
        $measured->setCellValue('C39', 'Tier level used ');
        // What EAD ships: a worked first row, and the source-id scaffold.
        $measured->setCellValue('B9', 'S01');
        $measured->setCellValue('D9', 'Major');
        $measured->setCellValue('E9', 'Illustrative');
        $measured->setCellValue('B10', 'S02');
        $measured->setCellValue('C40', 3);
        $measured->setCellValue('I40', 'Illustrative');
        // The lookup that makes the sheet positional.
        $measured->setCellValue('C9', "='2c2_Facility Description'!G43");

        $measurement = $book->createSheet();
        $measurement->setTitle('3e2_MeasurementBasedApproaches');
        $measurement->setCellValue('B22', 'Measurement point ID');
        $measurement->setCellValue('B23', 'MI1');
        $measurement->setCellValue('C23', 'S03');
        $measurement->setCellValue('C24', 'S04');

        $fallback = $book->createSheet();
        $fallback->setTitle('3f_Fallback Approach');
        $fallback->setCellValue('B8', 'Please provide a concise description of the monitoring approach, including formulae, used to determine your annual CO2 or CO2(e) emissions in the text box below.');

        $book->createSheet()->setTitle('3g_Methane');
        $book->createSheet()->setTitle('4h_Verification and Data Gaps');
        $book->createSheet()->setTitle('4I - Management & QA');
        $book->createSheet()->setTitle('4J - Mitigation Measures');

        $path = tempnam(sys_get_temp_dir(), 'cems').'.xlsx';
        (new XlsxWriter($book))->save($path);

        return $path;
    }

    private function source(string $code, string $methodology, array $extra = []): MrvEmissionSource
    {
        return MrvEmissionSource::create(array_merge([
            'company_id' => $this->company->id,
            'facility_id' => $this->facility->id,
            'reporting_year' => 2026,
            'source_code' => $code,
            'name' => "Source {$code}",
            'methodology' => $methodology,
        ], $extra));
    }

    private function report(): ?MrvFacilityReport
    {
        return MrvFacilityReport::where('facility_id', $this->facility->id)
            ->where('reporting_year', 2026)->first();
    }

    private function sheet(string $name)
    {
        $book = app(EadWorkbookFiller::class)->fill($this->facility, 2026, $this->report());
        $path = tempnam(sys_get_temp_dir(), 'out').'.xlsx';
        (new XlsxWriter($book))->save($path);

        return IOFactory::createReader('Xlsx')->load($path)->getSheetByName($name);
    }

    // ---------------------------------------------------------------------
    // 3e1
    // ---------------------------------------------------------------------

    public function test_a_measured_sources_accuracy_reaches_3e1(): void
    {
        $this->source('S01', 'measurement', [
            'materiality' => 'major',
            'tier_level' => 3,
            'uncertainty_pct' => 3.6,
            'emission_stream_type' => 'CO2 emission sources',
            'accuracy_source' => 'Lab. Analysis',
        ]);

        $ws = $this->sheet('3e1_Emission Sources (Measured)');

        // (a) id and category.
        $this->assertSame('S01', $ws->getCell('B9')->getValue());
        $this->assertSame('Major', $ws->getCell('D9')->getValue());

        // (b) the same source, 31 rows below.
        $this->assertSame(3, $ws->getCell('C40')->getValue());
        $this->assertSame('Major', $ws->getCell('D40')->getValue());
        $this->assertEquals(3.6, $ws->getCell('E40')->getValue());
        $this->assertSame('CO2 emission sources', $ws->getCell('F40')->getValue());
        $this->assertSame('Lab. Analysis', $ws->getCell('G40')->getValue());

        // The markers that would otherwise label real data as an example.
        $this->assertNull($ws->getCell('E9')->getValue());
        $this->assertNull($ws->getCell('I40')->getValue());
    }

    public function test_the_measured_sheet_stays_aligned_with_2c2(): void
    {
        // Two calculated sources, then a measured one. 3e1's emissions column
        // is a lookup into 2c2 by ROW, so the third source's tier must land on
        // the third row — not the first.
        $this->source('S01', 'calculation');
        $this->source('S02', 'calculation');
        $this->source('S03', 'measurement', ['tier_level' => 4, 'materiality' => 'major']);

        $ws = $this->sheet('3e1_Emission Sources (Measured)');

        $this->assertSame('S01', $ws->getCell('B9')->getValue());
        $this->assertSame('S02', $ws->getCell('B10')->getValue());
        $this->assertSame('S03', $ws->getCell('B11')->getValue());

        // Tier on the third row of table (b) — 9 + 2 + 31.
        $this->assertSame(4, $ws->getCell('C42')->getValue());

        // And nowhere else: a calculated source has no measurement tier.
        $this->assertNull($ws->getCell('C40')->getValue());
        $this->assertNull($ws->getCell('C41')->getValue());
    }

    public function test_a_calculated_source_gets_no_measurement_columns(): void
    {
        // Tier values entered and then the methodology switched. The columns
        // keep the data — losing it silently would be worse — but 3e1 is the
        // MEASURED sheet and must not claim a measurement tier for it.
        $this->source('S01', 'calculation', [
            'materiality' => 'major',
            'tier_level' => 2,
            'accuracy_source' => 'Lab. Analysis',
        ]);

        $ws = $this->sheet('3e1_Emission Sources (Measured)');

        $this->assertSame('S01', $ws->getCell('B9')->getValue());
        $this->assertNull($ws->getCell('D9')->getValue());
        $this->assertNull($ws->getCell('C40')->getValue());
        $this->assertNull($ws->getCell('G40')->getValue());
    }

    public function test_the_2c2_lookup_survives(): void
    {
        $this->source('S01', 'measurement', ['tier_level' => 3]);

        $ws = $this->sheet('3e1_Emission Sources (Measured)');

        $this->assertSame(
            "='2c2_Facility Description'!G43",
            $ws->getCell('C9')->getValue(),
            'The lookup that gives 3e1 its emissions figures was blanked.'
        );
    }

    // ---------------------------------------------------------------------
    // 3e2
    // ---------------------------------------------------------------------

    public function test_the_measurement_narratives_reach_3e2(): void
    {
        $this->post(route('mrv.saveReport'), [
            'facility_id' => $this->facility->id,
            'year' => 2026,
            'section' => 'measurement',
            'measurement_approach' => 'NDIR CO2 analyser on Stack 2, dry basis, with ultrasonic flow.',
            'measurement_derivation' => "Concentration averaged hourly.\nFlow integrated continuously.\nGaps substituted with the prior month's hourly mean.",
            'measurement_comments' => 'Biomass share determined by monthly assay.',
        ])->assertRedirect();

        $ws = $this->sheet('3e2_MeasurementBasedApproaches');

        $this->assertStringContainsString('NDIR', $ws->getCell('B7')->getValue());

        // B9:K15 is seven rows; the derivation is laid out a line per row
        // rather than clipped into one.
        $this->assertSame('Concentration averaged hourly.', $ws->getCell('B9')->getValue());
        $this->assertSame('Flow integrated continuously.', $ws->getCell('B10')->getValue());
        $this->assertStringContainsString('substituted', $ws->getCell('B11')->getValue());

        $this->assertStringContainsString('monthly assay', $ws->getCell('B41')->getValue());
    }

    public function test_measurement_points_reach_3e2_over_the_worked_example(): void
    {
        MrvMeasuringInstrument::create([
            'company_id' => $this->company->id,
            'facility_id' => $this->facility->id,
            'reporting_year' => 2026,
            'instrument_code' => 'MP1',
            'emission_source_code' => 'S01',
            'type' => 'NDIR + ultrasonic flow',
            'procedures' => 'Hourly aggregation, QAL3 drift check',
            'relevant_procedures' => 'EN 14181',
            'relevant_source' => 'CEMS-OPS-04',
        ]);

        $ws = $this->sheet('3e2_MeasurementBasedApproaches');

        $this->assertSame('MP1', $ws->getCell('B23')->getValue());
        $this->assertSame('S01', $ws->getCell('C23')->getValue());
        $this->assertStringContainsString('QAL3', $ws->getCell('D23')->getValue());
        $this->assertSame('EN 14181', $ws->getCell('H23')->getValue());

        // Row 24's illustrative S04 is not this facility's.
        $this->assertNull($ws->getCell('C24')->getValue());
    }

    // ---------------------------------------------------------------------
    // Entry
    // ---------------------------------------------------------------------

    public function test_a_measurement_point_can_be_recorded_and_removed(): void
    {
        $this->post(route('mrv.saveInstrument'), [
            'facility_id' => $this->facility->id,
            'year' => 2026,
            'instrument_code' => 'MP1',
            'emission_source_code' => 'S01',
            'type' => 'NDIR analyser',
            'location_id' => 'Stack 2, plane 18 m',
            'range_lower' => 0,
            'range_upper' => 5000,
            'range_unit' => 'mg/Nm3',
            'specified_uncertainty_pct' => 2.5,
        ])->assertRedirect();

        $point = MrvMeasuringInstrument::where('instrument_code', 'MP1')->firstOrFail();

        $this->assertSame('S01', $point->emission_source_code);
        $this->assertSame('Stack 2, plane 18 m', $point->location_id);
        $this->assertEquals(5000, $point->range_upper);

        $this->delete(route('mrv.deleteInstrument', $point->id))->assertRedirect();
        $this->assertNull(MrvMeasuringInstrument::find($point->id));
    }

    public function test_an_inverted_instrument_range_is_rejected(): void
    {
        // An analyser cannot read from 5000 down to 0, and an inverted range
        // makes the achieved-uncertainty assessment meaningless.
        $this->post(route('mrv.saveInstrument'), [
            'facility_id' => $this->facility->id,
            'year' => 2026,
            'instrument_code' => 'MP1',
            'range_lower' => 5000,
            'range_upper' => 0,
        ])->assertSessionHasErrors('range_upper');
    }

    public function test_saving_a_point_twice_updates_rather_than_duplicates(): void
    {
        $payload = [
            'facility_id' => $this->facility->id,
            'year' => 2026,
            'instrument_code' => 'MP1',
            'type' => 'NDIR analyser',
        ];

        $this->post(route('mrv.saveInstrument'), $payload);
        $this->post(route('mrv.saveInstrument'), array_merge($payload, ['type' => 'FTIR analyser']));

        $points = MrvMeasuringInstrument::where('facility_id', $this->facility->id)->get();

        $this->assertCount(1, $points);
        $this->assertSame('FTIR analyser', $points->first()->type);
    }

    public function test_a_measured_sources_tier_can_be_entered(): void
    {
        $this->post(route('mrv.saveSource'), [
            'facility_id' => $this->facility->id,
            'year' => 2026,
            'source_code' => 'S01',
            'name' => 'EAF stack',
            'methodology' => 'measurement',
            'tier_level' => 4,
            'uncertainty_pct' => 1.8,
            'emission_stream_type' => 'CO2 emission sources',
            'accuracy_source' => 'CEMS, QAL2 certified',
        ])->assertRedirect();

        $source = MrvEmissionSource::where('source_code', 'S01')->firstOrFail();

        $this->assertSame(4, (int) $source->tier_level);
        $this->assertEquals(1.8, $source->uncertainty_pct);
        $this->assertSame('CO2 emission sources', $source->emission_stream_type);
        $this->assertSame('CEMS, QAL2 certified', $source->accuracy_source);
    }

    // ---------------------------------------------------------------------
    // The workspace
    // ---------------------------------------------------------------------

    public function test_the_workspace_prompts_when_a_source_is_measured_and_nothing_is_recorded(): void
    {
        $this->source('S01', 'measurement');

        $this->get(route('mrv.index', ['facility_id' => $this->facility->id, 'year' => 2026]))
            ->assertOk()
            ->assertSee('is <strong>measurement-based</strong>', false);
    }

    public function test_no_prompt_when_nothing_is_measured(): void
    {
        $this->source('S01', 'calculation');

        $this->get(route('mrv.index', ['facility_id' => $this->facility->id, 'year' => 2026]))
            ->assertOk()
            ->assertSee('this sheet can stay empty', false);
    }

    public function test_the_plan_now_covers_eight_sections(): void
    {
        $this->get(route('mrv.index', ['facility_id' => $this->facility->id, 'year' => 2026]))
            ->assertOk()
            ->assertSee('of 8 sections started', false);
    }

    public function test_the_real_template_accepts_the_measurement_sheets(): void
    {
        config(['mrv.ead_template' => null]);

        if (! is_file(EadWorkbookFiller::templatePath())) {
            $this->markTestSkipped('EAD template not installed.');
        }

        $this->source('S01', 'measurement', ['tier_level' => 3, 'materiality' => 'major']);

        MrvMeasuringInstrument::create([
            'company_id' => $this->company->id,
            'facility_id' => $this->facility->id,
            'reporting_year' => 2026,
            'instrument_code' => 'MP1',
            'emission_source_code' => 'S01',
            'procedures' => 'Hourly aggregation',
        ]);

        $this->post(route('mrv.saveReport'), [
            'facility_id' => $this->facility->id,
            'year' => 2026,
            'section' => 'measurement',
            'measurement_approach' => 'NDIR on Stack 2.',
        ]);

        // The fixture's coordinates are only meaningful if they are the real
        // workbook's coordinates too.
        $book = app(EadWorkbookFiller::class)->fill($this->facility, 2026, $this->report());

        $measured = $book->getSheetByName('3e1_Emission Sources (Measured)');
        $this->assertSame('S01', $measured->getCell('B9')->getValue());
        $this->assertSame(3, $measured->getCell('C40')->getValue());

        $measurement = $book->getSheetByName('3e2_MeasurementBasedApproaches');
        $this->assertSame('NDIR on Stack 2.', $measurement->getCell('B7')->getValue());
        $this->assertSame('MP1', $measurement->getCell('B23')->getValue());

        // 3d2(c) on the real file, not just the fixture: this table is written
        // at hardcoded rows with a stride of two, and its worked examples ship
        // filled. If either is wrong here, a submission carries EAD's "Rotary
        // meter" as the operator's own instrument.
        MrvMeasuringInstrument::create([
            'company_id' => $this->company->id,
            'facility_id' => $this->facility->id,
            'reporting_year' => 2026,
            'instrument_code' => 'MI01',
            'source_stream_code' => 'F01',
            'type' => 'Ultrasonic flow meter',
            'range_unit' => 'Nm3/h',
            'specified_uncertainty_pct' => 1.2,
        ]);

        $book = app(EadWorkbookFiller::class)->fill($this->facility, 2026, $this->report());
        $calc = $book->getSheetByName('3d2_ Calculation Approaches');

        $this->assertSame('MI01', $calc->getCell('B93')->getValue());
        $this->assertSame('Ultrasonic flow meter', $calc->getCell('D93')->getValue());
        $this->assertNull($calc->getCell('M93')->getValue(), 'EAD\'s "Illustrative" marker survived into the submission.');
        $this->assertNull($calc->getCell('D95')->getValue(), 'EAD\'s "Weigh bridge" example survived into the submission.');
    }
}

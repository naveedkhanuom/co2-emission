<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\EmissionRecord;
use App\Models\Facilities;
use App\Models\MrvEmissionSource;
use App\Models\MrvSourceStream;
use App\Models\User;
use App\Services\MRV\EadWorkbookFiller;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;
use Tests\TenantTestCase;

/**
 * The EAD workbook export, end to end: Scope 1 records → prefill → filled
 * submission.
 *
 * This is the product's highest-consequence output — a document filed with a
 * regulator — and until now the only MRV tests were a unit test of the
 * calculator. Three defects had survived in consequence, each pinned below.
 *
 * WHY A SYNTHETIC TEMPLATE
 *
 * EAD's real workbook cannot be committed: it carries a confidentiality notice
 * and is issued per operator (see config/mrv.php). A test that needs it would
 * be skipped in CI, which is where regression tests earn their keep. So most
 * tests here build a fixture that reproduces the structural contract the filler
 * depends on — sheet names, anchor labels, the illustrative rows, and a
 * cross-sheet formula — and assert against that.
 *
 * The last test closes the loop by checking those same anchors against the real
 * v8.1 template when it happens to be installed. If EAD's layout and this
 * fixture ever disagree, that is the test that says so.
 */
class EadWorkbookExportTest extends TenantTestCase
{
    private Company $company;

    private Facilities $facility;

    private string $fixture;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Emirates Steel Industries PJSC',
            'industry_type' => 'manufacturing',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'MRV Officer',
            'email' => 'mrv-'.uniqid().'@example.test',
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
            'name' => 'Musaffah Plant',
            'mrv_enabled' => true,
            'parent_entity' => 'Emirates Steel Arkan Group',
            'environmental_permit_no' => 'EP-2026-118',
        ]);

        $this->fixture = $this->makeTemplateFixture();
        config(['mrv.ead_template' => $this->fixture]);
    }

    protected function tearDown(): void
    {
        if (isset($this->fixture) && is_file($this->fixture)) {
            @unlink($this->fixture);
        }

        parent::tearDown();
    }

    /**
     * A stand-in for the EAD workbook carrying only what the filler relies on:
     * the four sheets it writes, the labels its version guard checks, the
     * illustrative rows the real template ships with, and one cross-sheet
     * formula that must survive clearing.
     */
    private function makeTemplateFixture(): string
    {
        $book = new Spreadsheet;

        $identifiers = $book->getActiveSheet();
        $identifiers->setTitle('2c1_ Identifiers');
        $identifiers->setCellValue('D5', 'Entity/Company name ');
        $identifiers->setCellValue('D7', 'Facility Name (as stated in the Environmental Permit)');

        $facility = $book->createSheet();
        $facility->setTitle('2c2_Facility Description');
        $facility->setCellValue('C42', 'Emission source ID');
        $facility->setCellValue('C74', 'Source Stream ID');

        // The real template pre-fills all 25 source rows with placeholders.
        $placeholders = ['x', 'xx', 'xxx', 'y', 'yy', 'yyy', 'z', 'zz', 'zzz', 'xyz',
            'a', 'aa', 'aaa', 'b', 'bb', 'bbb', 'c', 'cc', 'ccc', 'abc', 'd', 'dd', 'ddd', 'e', 'ee'];
        foreach ($placeholders as $i => $name) {
            $row = 43 + $i;
            $facility->setCellValue("C{$row}", sprintf('S%02d', $i + 1));
            $facility->setCellValue("D{$row}", $name);
            $facility->setCellValue("J{$row}", 'Calculation-based');
        }

        // …and one illustrative stream, complete with equipment detail.
        $facility->setCellValue('C75', 'F01');
        $facility->setCellValue('D75', 'xyz');
        $facility->setCellValue('H75', 'MWh');
        $facility->setCellValue('J75', 'Gas fired heaters');
        $facility->setCellValue('K75', 100);

        $tiers = $book->createSheet();
        $tiers->setTitle('3d1_Source Streams (Calculated)');
        $tiers->setCellValue('B9', 'Source stream ID');
        $tiers->setCellValue('C40', 'Tier level used ');
        // Column B is the template's own cross-sheet lookup — clearing it would
        // break the workbook's wiring, so the filler must step over it.
        $tiers->setCellValue('B10', "='2c2_Facility Description'!C75");
        $tiers->setCellValue('C10', 'Raw meal; Cement clinker');
        $tiers->setCellValue('E10', 105000);
        $tiers->setCellValue('H10', 'Illustrative');
        $tiers->setCellValue('C41', 3);
        $tiers->setCellValue('I41', 'Illustrative');

        $calc = $book->createSheet();
        $calc->setTitle('3d2_ Calculation Approaches');
        $calc->setCellValue('B59', 'Source Stream ID');

        $measured = $book->createSheet();
        $measured->setTitle('3e1_Emission Sources (Measured)');
        $measured->setCellValue('B8', 'Emission source ID');
        $measured->setCellValue('C39', 'Tier level used ');

        $measurement = $book->createSheet();
        $measurement->setTitle('3e2_MeasurementBasedApproaches');
        $measurement->setCellValue('B22', 'Measurement point ID');

        $fallback = $book->createSheet();
        $fallback->setTitle('3f_Fallback Approach');
        $fallback->setCellValue('B8', 'Please provide a concise description of the monitoring approach, including formulae, used to determine your annual CO2 or CO2(e) emissions in the text box below.');
        $calc->setCellValue('B60', 'F03');
        $calc->setCellValue('C60', 'Crude oil');
        $calc->setCellValue('F60', '42.3 TJ/Gg');

        $path = tempnam(sys_get_temp_dir(), 'ead').'.xlsx';
        (new XlsxWriter($book))->save($path);

        return $path;
    }

    private function scope1Record(array $overrides = []): EmissionRecord
    {
        return EmissionRecord::create(array_merge([
            'company_id' => $this->company->id,
            'entry_date' => '2026-03-14',
            'scope' => 1,
            'facility' => $this->facility->name,
            'facility_id' => $this->facility->id,
            'emission_source' => 'Natural gas',
            'activity_data' => 1000,
            'activity_unit' => 'm3',
            'emission_factor' => 2.02,
            'co2e_value' => 2020,
            'status' => 'active',
        ], $overrides));
    }

    private function export(): Spreadsheet
    {
        return IOFactory::createReader('Xlsx')->load($this->exportToFile());
    }

    private function exportToFile(): string
    {
        $book = app(EadWorkbookFiller::class)->fill($this->facility, 2026);
        $path = tempnam(sys_get_temp_dir(), 'out').'.xlsx';
        (new XlsxWriter($book))->save($path);

        return $path;
    }

    // ---------------------------------------------------------------------
    // The illustrative rows
    // ---------------------------------------------------------------------

    public function test_the_templates_worked_example_does_not_become_the_operators_inventory(): void
    {
        // Two real emission sources against a template carrying twenty-five
        // placeholders. Every row the operator did not fill used to survive
        // into the submission, so a facility with two sources filed a document
        // declaring twenty-five — the other twenty-three named "xxx", "y",
        // "zz", one of them claiming a measurement-based methodology.
        foreach (['S01' => 'Gas boiler', 'S02' => 'Standby generator'] as $code => $name) {
            MrvEmissionSource::create([
                'company_id' => $this->company->id,
                'facility_id' => $this->facility->id,
                'reporting_year' => 2026,
                'source_code' => $code,
                'name' => $name,
                'methodology' => 'calculation',
                'total_co2e' => 500,
            ]);
        }

        $ws = $this->export()->getSheetByName('2c2_Facility Description');

        $this->assertSame('Gas boiler', $ws->getCell('D43')->getValue());
        $this->assertSame('Standby generator', $ws->getCell('D44')->getValue());

        for ($row = 45; $row <= 67; $row++) {
            $this->assertNull(
                $ws->getCell("D{$row}")->getValue(),
                "Row {$row} still carries the template's illustrative placeholder — the submission ".
                'would declare an emission source that does not exist.'
            );
        }
    }

    public function test_a_real_stream_does_not_inherit_the_illustrations_equipment(): void
    {
        // set() skips blanks so it never clobbers the template. Combined with
        // pre-filled illustrative rows that meant a real stream with no
        // combustion device silently kept "Gas fired heaters / 100".
        MrvSourceStream::create([
            'company_id' => $this->company->id,
            'facility_id' => $this->facility->id,
            'reporting_year' => 2026,
            'stream_code' => 'F01',
            'description' => 'Natural gas',
            'classification' => 'fuel_combusted',
            'activity_level' => 1000,
            'activity_unit' => 'm3',
        ]);

        $ws = $this->export()->getSheetByName('2c2_Facility Description');

        $this->assertSame('Natural gas', $ws->getCell('D75')->getValue());
        $this->assertSame('m3', $ws->getCell('H75')->getValue());
        $this->assertNull($ws->getCell('J75')->getValue(), 'Inherited the illustration’s combustion device.');
        $this->assertNull($ws->getCell('K75')->getValue(), 'Inherited the illustration’s device capacity.');
    }

    public function test_clearing_a_table_leaves_the_templates_own_formulas_alone(): void
    {
        MrvSourceStream::create([
            'company_id' => $this->company->id,
            'facility_id' => $this->facility->id,
            'reporting_year' => 2026,
            'stream_code' => 'F01',
            'description' => 'Natural gas',
            'classification' => 'fuel_combusted',
        ]);

        $ws = $this->export()->getSheetByName('3d1_Source Streams (Calculated)');

        $this->assertSame(
            "='2c2_Facility Description'!C75",
            $ws->getCell('B10')->getValue(),
            'The cross-sheet lookup that gives 3d1 its stream ids was blanked.'
        );
    }

    // ---------------------------------------------------------------------
    // Identifiers
    // ---------------------------------------------------------------------

    public function test_the_operator_is_named_as_the_company_not_the_site(): void
    {
        // `$facility->parent_entity ? $facility->name : $facility->name` — a
        // ternary with identical branches, so the facility name was filed as
        // the reporting legal entity on every export.
        $ws = $this->export()->getSheetByName('2c1_ Identifiers');

        $this->assertSame('Emirates Steel Industries PJSC', $ws->getCell('H5')->getValue());
        $this->assertSame('Emirates Steel Arkan Group', $ws->getCell('H6')->getValue());
        $this->assertSame('Musaffah Plant', $ws->getCell('H7')->getValue());
    }

    // ---------------------------------------------------------------------
    // Tiers — 3d1, which was never written at all
    // ---------------------------------------------------------------------

    public function test_tier_and_uncertainty_reach_the_submission(): void
    {
        MrvSourceStream::create([
            'company_id' => $this->company->id,
            'facility_id' => $this->facility->id,
            'reporting_year' => 2026,
            'stream_code' => 'F01',
            'description' => 'Natural gas',
            'classification' => 'fuel_combusted',
            'fuel_type' => 'Natural gas',
            'materiality' => 'major',
            'tier_level' => 3,
            'uncertainty_pct' => 1.6,
            'estimated_co2e' => 105000,
        ]);

        $ws = $this->export()->getSheetByName('3d1_Source Streams (Calculated)');

        // (a) — what the stream contributes and how it is classified.
        $this->assertSame('Natural gas', $ws->getCell('C10')->getValue());
        $this->assertEquals(105000, $ws->getCell('E10')->getValue());
        $this->assertSame('Major', $ws->getCell('G10')->getValue());

        // (b) — how well it is known, 31 rows below.
        $this->assertSame(3, $ws->getCell('C41')->getValue());
        $this->assertSame('Major', $ws->getCell('D41')->getValue());
        $this->assertEquals(1.6, $ws->getCell('E41')->getValue());
        $this->assertSame('Natural gas', $ws->getCell('F41')->getValue());

        // The two markers that would otherwise label real data as an example.
        $this->assertNull($ws->getCell('H10')->getValue());
        $this->assertNull($ws->getCell('I41')->getValue());
    }

    public function test_an_unstated_materiality_is_left_blank_rather_than_guessed(): void
    {
        MrvSourceStream::create([
            'company_id' => $this->company->id,
            'facility_id' => $this->facility->id,
            'reporting_year' => 2026,
            'stream_code' => 'F01',
            'description' => 'Diesel',
            'classification' => 'fuel_combusted',
            'estimated_co2e' => 40,
        ]);

        $ws = $this->export()->getSheetByName('3d1_Source Streams (Calculated)');

        // The de-minimis rule is not implemented; a guessed band would be
        // presented to a regulator as the operator's own determination.
        $this->assertNull($ws->getCell('G10')->getValue());
        $this->assertNull($ws->getCell('F10')->getValue(), 'A "possible category" was invented.');
    }

    // ---------------------------------------------------------------------
    // Prefill — the round trip
    // ---------------------------------------------------------------------

    public function test_scope_1_records_carry_their_unit_all_the_way_into_the_workbook(): void
    {
        $this->scope1Record();
        $this->scope1Record(['activity_data' => 500, 'co2e_value' => 1010]);

        $this->post(route('mrv.prefill'), ['facility_id' => $this->facility->id, 'year' => 2026])
            ->assertRedirect();

        $stream = MrvSourceStream::where('facility_id', $this->facility->id)->firstOrFail();

        // prefill wrote activity_level but never activity_unit, so the
        // workbook showed a bare number beside a fuel type.
        $this->assertEquals(1500, $stream->activity_level);
        $this->assertSame('m3', $stream->activity_unit);

        $ws = $this->export()->getSheetByName('2c2_Facility Description');
        $this->assertEquals(1500, $ws->getCell('G75')->getValue());
        $this->assertSame('m3', $ws->getCell('H75')->getValue());
    }

    public function test_one_source_logged_in_two_units_becomes_two_streams(): void
    {
        // Summing these gave 1000 m3 + 400 kWh = "1400" of nothing, exported as
        // the stream's activity level.
        $this->scope1Record();
        $this->scope1Record(['activity_data' => 400, 'activity_unit' => 'kWh', 'co2e_value' => 80]);

        $this->post(route('mrv.prefill'), ['facility_id' => $this->facility->id, 'year' => 2026]);

        $streams = MrvSourceStream::where('facility_id', $this->facility->id)
            ->orderBy('stream_code')->get();

        $this->assertCount(2, $streams);
        $this->assertEqualsCanonicalizing(['m3', 'kWh'], $streams->pluck('activity_unit')->all());
        $this->assertEqualsCanonicalizing([1000.0, 400.0], $streams->pluck('activity_level')->map(fn ($v) => (float) $v)->all());

        // Qualified so the two are distinguishable in the submission.
        foreach ($streams as $stream) {
            $this->assertStringContainsString('Natural gas (', $stream->description);
        }

        // One physical source, whole, carrying the whole footprint.
        $sources = MrvEmissionSource::where('facility_id', $this->facility->id)->get();
        $this->assertCount(1, $sources);
        $this->assertEquals(2100, $sources->first()->total_co2e);
    }

    public function test_disagreeing_factors_are_left_for_the_operator_to_resolve(): void
    {
        // A fuel switch or a mid-year factor revision. Taking whichever record
        // came first would state one of them as the stream's factor.
        $this->scope1Record(['emission_factor' => 2.02]);
        $this->scope1Record(['emission_factor' => 1.88]);

        $this->post(route('mrv.prefill'), ['facility_id' => $this->facility->id, 'year' => 2026]);

        $this->assertNull(
            MrvSourceStream::where('facility_id', $this->facility->id)->firstOrFail()->emission_factor_value
        );
    }

    public function test_prefill_stays_idempotent(): void
    {
        $this->scope1Record();

        $this->post(route('mrv.prefill'), ['facility_id' => $this->facility->id, 'year' => 2026]);
        $this->post(route('mrv.prefill'), ['facility_id' => $this->facility->id, 'year' => 2026]);

        $this->assertSame(1, MrvSourceStream::where('facility_id', $this->facility->id)->count());
        $this->assertSame(1, MrvEmissionSource::where('facility_id', $this->facility->id)->count());
    }

    // ---------------------------------------------------------------------
    // The version guard
    // ---------------------------------------------------------------------

    public function test_a_template_whose_rows_have_moved_is_refused(): void
    {
        $book = IOFactory::createReader('Xlsx')->load($this->fixture);
        // EAD inserts a row above the source-stream table in some future
        // version: every hardcoded coordinate below it now points one row high.
        $book->getSheetByName('2c2_Facility Description')->insertNewRowBefore(70, 1);
        (new XlsxWriter($book))->save($this->fixture);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/does not match the layout|Source Stream ID/i');

        app(EadWorkbookFiller::class)->fill($this->facility, 2026);
    }

    public function test_a_workbook_that_is_not_the_ead_template_is_refused(): void
    {
        $book = new Spreadsheet;
        $book->getActiveSheet()->setTitle('Sheet1');
        (new XlsxWriter($book))->save($this->fixture);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/does not look like the EAD Deliverable C template/');

        app(EadWorkbookFiller::class)->fill($this->facility, 2026);
    }

    public function test_the_anchors_still_match_the_real_ead_template(): void
    {
        config(['mrv.ead_template' => null]);
        $path = EadWorkbookFiller::templatePath();

        if (! is_file($path)) {
            $this->markTestSkipped('EAD template not installed at '.$path);
        }

        // The whole point of the fixture is that it stands in for this file.
        // If they ever disagree, every test above is testing a fiction.
        app(EadWorkbookFiller::class)->fill($this->facility, 2026);

        $this->assertTrue(true, 'The real v8.1 template satisfies the version guard.');
    }
}

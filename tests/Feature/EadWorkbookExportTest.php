<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\EmissionRecord;
use App\Models\Facilities;
use App\Models\MrvEmissionSource;
use App\Models\MrvMeasuringInstrument;
use App\Models\MrvSourceStream;
use App\Models\User;
use App\Services\MRV\EadCapacityExceededException;
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
        $identifiers->setCellValue('C13', 'Description of the facility and its activities (including site diagrams if applicable) (*):');

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
        $calc->setCellValue('F24', 'Source (e.g., maintenance records, fuel logs)');
        $calc->setCellValue('B59', 'Source Stream ID');
        $calc->setCellValue('C91', 'Associated source stream (ID)');

        // 3d2(b)'s fuel table ships one illustrative row, and its activity
        // columns are the template's own lookups into 2c2 — which is what makes
        // the leftover dangerous rather than obvious: matched against a real
        // F01 they dress "Natural gas" in the operator's own figures.
        $calc->setCellValue('B25', 'F01');
        $calc->setCellValue('C25', 'Natural gas ');
        $calc->setCellValue('D25', "='2c2_Facility Description'!G75");
        $calc->setCellValue('F25', 'In-house technical data ');

        // 3d2(c) ships two worked examples, each spanning a merged row pair.
        $calc->setCellValue('B93', 'MI01');
        $calc->setCellValue('D93', 'Rotary meter');
        $calc->setCellValue('G93', 'Nm³/h');
        $calc->setCellValue('M93', 'Illustrative');
        $calc->setCellValue('J94', 1.5);
        $calc->setCellValue('B95', 'MI02');
        $calc->setCellValue('D95', 'Weigh bridge');
        $calc->setCellValue('M95', 'Illustrative');

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
        $calc->setCellValue('K60', 'Illustrative');

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

    // ---------------------------------------------------------------------
    // Capacity — EAD's tables are fixed blocks of rows
    // ---------------------------------------------------------------------

    /**
     * @param  int  $count  How many streams the facility holds.
     */
    private function makeStreams(int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            MrvSourceStream::create([
                'company_id' => $this->company->id,
                'facility_id' => $this->facility->id,
                'reporting_year' => 2026,
                'stream_code' => sprintf('F%02d', $i),
                'description' => "Natural gas line {$i}",
                'classification' => 'fuel_combusted',
                'activity_level' => 1000,
                'activity_unit' => 'MWh',
                'estimated_co2e' => 200,
            ]);
        }
    }

    /**
     * 2c2's stream table is rows 75..99 and 3d2's is 60..84 — twenty-five each.
     * The loops that fill them stopped at the last row and said nothing.
     *
     * Checked against the pre-fix filler: it produced a workbook carrying
     * twenty-five of the twenty-six streams, with no exception, no warning and
     * nothing in the file to show that one had been left out. That is a
     * misstatement to a regulator that looks exactly like a correct submission.
     */
    public function test_a_facility_with_more_streams_than_the_workbook_holds_is_refused(): void
    {
        $this->makeStreams(26);

        $this->expectException(EadCapacityExceededException::class);
        $this->expectExceptionMessageMatches('/26 source streams, but the workbook holds 25/');

        app(EadWorkbookFiller::class)->fill($this->facility, 2026);
    }

    /**
     * The other half of the boundary: twenty-five must still export, and the
     * twenty-fifth must land on the table's last row. A guard that refuses at
     * the limit rather than past it would be its own outage.
     */
    public function test_a_facility_at_the_workbooks_limit_still_exports(): void
    {
        $this->makeStreams(25);

        $ws = $this->export()->getSheetByName('2c2_Facility Description');

        $this->assertSame('F01', $ws->getCell('C75')->getValue());
        $this->assertSame('F25', $ws->getCell('C99')->getValue());
    }

    /**
     * The refusal has to reach the operator as something they can act on —
     * which table, how many they hold, how many fit.
     */
    public function test_the_export_screen_says_which_table_overflowed(): void
    {
        $this->makeStreams(26);

        $response = $this->get(route('mrv.export', [
            'facility_id' => $this->facility->id,
            'year' => 2026,
        ]));

        $response->assertRedirect();
        $this->assertStringContainsString('26 source streams', session('error'));
        $this->assertStringContainsString('holds 25', session('error'));
    }

    /**
     * Finding out at the moment you need the file is too late, so the workspace
     * says so while the operator can still do something about it.
     */
    public function test_the_workspace_warns_before_the_operator_reaches_the_export(): void
    {
        $this->makeStreams(26);

        $this->get(route('mrv.index', ['facility_id' => $this->facility->id, 'year' => 2026]))
            ->assertOk()
            ->assertSee('does not fit the EAD workbook');
    }

    /**
     * A template whose rows have moved and a template that is not installed are
     * opposite problems: one is fixed by checking the version of the file that
     * is there, the other by putting a file there at all.
     *
     * Checked against the pre-fix controller, which caught RuntimeException
     * once and answered both with "the template is not installed" — sending an
     * administrator to look for a file sitting in front of them.
     */
    public function test_a_moved_template_is_reported_as_a_version_problem_not_a_missing_file(): void
    {
        $book = IOFactory::createReader('Xlsx')->load($this->fixture);
        $book->getSheetByName('2c2_Facility Description')->insertNewRowBefore(70, 1);
        (new XlsxWriter($book))->save($this->fixture);

        $response = $this->get(route('mrv.export', [
            'facility_id' => $this->facility->id,
            'year' => 2026,
        ]));

        $response->assertRedirect();
        $this->assertStringContainsString('not the version this export was built for', session('error'));
        $this->assertStringNotContainsString('not installed', session('error'));
    }

    // ---------------------------------------------------------------------
    // 2c1 (b) — the mandatory description
    // ---------------------------------------------------------------------

    /**
     * EAD marks this field mandatory (sheet 1b: "it has been indicated with an
     * asterisk"), and nothing wrote it — so every submission this product
     * generated went in with a mandatory field blank, while the facility had
     * carried a description all along.
     */
    public function test_the_facility_description_reaches_the_mandatory_2c1_field(): void
    {
        $this->facility->update([
            'description' => 'Integrated steel plant: DRI module, EAF meltshop and a 120 MW captive CCGT.',
        ]);

        $ws = $this->export()->getSheetByName('2c1_ Identifiers');

        $this->assertStringContainsString('EAF meltshop', (string) $ws->getCell('C16')->getValue());
    }

    /**
     * K11 answers C11, 'If "other", please specify' — and only then. A value
     * left over from an earlier draft must not sit under a sector that has
     * since been picked from the list, contradicting it.
     */
    public function test_the_other_sector_explanation_reaches_k11(): void
    {
        $this->facility->update([
            'primary_sector' => 'Other',
            'primary_sector_other' => 'Desalination and power cogeneration',
        ]);

        $ws = $this->export()->getSheetByName('2c2_Facility Description');

        $this->assertSame('Other', $ws->getCell('K10')->getValue());
        $this->assertSame('Desalination and power cogeneration', $ws->getCell('K11')->getValue());
    }

    public function test_a_named_sector_leaves_k11_alone(): void
    {
        $this->facility->update([
            'primary_sector' => 'Industrial Processes',
            'primary_sector_other' => 'Left over from an earlier draft',
        ]);

        $ws = $this->export()->getSheetByName('2c2_Facility Description');

        $this->assertSame('Industrial Processes', $ws->getCell('K10')->getValue());
        $this->assertNull($ws->getCell('K11')->getValue());
    }

    // ---------------------------------------------------------------------
    // 3d2 (b) — the fuel table, which was never written at all
    // ---------------------------------------------------------------------

    private function stream(array $overrides = []): MrvSourceStream
    {
        return MrvSourceStream::create(array_merge([
            'company_id' => $this->company->id,
            'facility_id' => $this->facility->id,
            'reporting_year' => 2026,
            'stream_code' => 'F01',
            'description' => 'Natural gas',
            'classification' => 'fuel_combusted',
            'activity_level' => 1000,
            'activity_unit' => 'm3',
        ], $overrides));
    }

    public function test_a_combusted_fuel_reaches_the_fuel_table(): void
    {
        // 3d2's fuel table was the one operator table the filler never touched.
        // A facility could describe its gas completely on 2c2 and still submit
        // a combustion sheet that did not mention it.
        $this->stream([
            'stream_code' => 'F02',
            'fuel_type' => 'Diesel',
            'information_source' => 'Fuel delivery notes',
        ]);

        $ws = $this->export()->getSheetByName('3d2_ Calculation Approaches');

        $this->assertSame('F02', $ws->getCell('B25')->getValue());
        $this->assertSame('Diesel', $ws->getCell('C25')->getValue());
        $this->assertSame('Fuel delivery notes', $ws->getCell('F25')->getValue());
    }

    public function test_the_illustrative_fuel_row_does_not_survive(): void
    {
        // Nothing burned, so the table is submitted empty. Left alone it filed
        // the template's own "F01 / Natural gas / In-house technical data" as
        // the facility's fuel.
        $this->stream(['classification' => 'output', 'description' => 'Clinker']);

        $ws = $this->export()->getSheetByName('3d2_ Calculation Approaches');

        $this->assertNull($ws->getCell('B25')->getValue());
        $this->assertNull($ws->getCell('C25')->getValue(), 'Filed the illustration’s fuel type.');
        $this->assertNull($ws->getCell('F25')->getValue(), 'Filed the illustration’s information source.');
    }

    public function test_the_fuel_tables_activity_lookups_are_left_to_compute(): void
    {
        // D and E are the template's own INDEX/MATCH into 2c2. Clearing them
        // would silently drop the activity data off the combustion sheet.
        $this->stream();

        $ws = $this->export()->getSheetByName('3d2_ Calculation Approaches');

        $this->assertSame("='2c2_Facility Description'!G75", $ws->getCell('D25')->getValue());
    }

    public function test_only_combusted_fuels_reach_the_fuel_table(): void
    {
        // EAD splits the sheet in two and its own note says so: process inputs
        // and outputs answer the table below, not this one.
        // The fuel is deliberately not F01: matching the illustration's own id
        // would let this pass on the leftover row it is meant to rule out.
        $this->stream(['stream_code' => 'F01', 'classification' => 'other_input', 'fuel_type' => 'Limestone']);
        $this->stream(['stream_code' => 'F02', 'classification' => 'output', 'fuel_type' => 'Clinker']);
        $this->stream(['stream_code' => 'F03', 'fuel_type' => 'Natural gas']);

        $ws = $this->export()->getSheetByName('3d2_ Calculation Approaches');

        $this->assertSame('F03', $ws->getCell('B25')->getValue());
        $this->assertNull($ws->getCell('B26')->getValue(), 'A non-fuel stream was listed as a combusted fuel.');
    }

    public function test_a_fuel_with_no_type_recorded_still_appears(): void
    {
        // Membership is the classification the operator chose, not whether a
        // later field happens to be filled in. Dropping the row would take a
        // declared fuel off the combustion sheet without saying so.
        $this->stream(['fuel_type' => null, 'description' => 'Refinery off-gas']);

        $ws = $this->export()->getSheetByName('3d2_ Calculation Approaches');

        $this->assertSame('F01', $ws->getCell('B25')->getValue());
        $this->assertSame('Refinery off-gas', $ws->getCell('C25')->getValue());
    }

    public function test_a_real_stream_is_not_left_marked_illustrative(): void
    {
        // EAD's marker sits in K, one column past the table, so clearing only
        // the data left it standing beside the operator's first real stream.
        $this->stream([
            'stream_code' => 'F04',
            'classification' => 'other_input',
            'description' => 'Limestone',
            'net_calorific_value' => 42.3,
            'ncv_unit' => 'TJ/Gg',
        ]);

        $ws = $this->export()->getSheetByName('3d2_ Calculation Approaches');

        $this->assertSame('F04', $ws->getCell('B60')->getValue());
        $this->assertNull($ws->getCell('K60')->getValue(), 'A real stream is still marked "Illustrative".');
    }

    public function test_a_facility_that_burns_all_twenty_five_streams_still_exports(): void
    {
        // The fuel table holds 25 rows, the same as 2c2's stream block, so an
        // all-combustion facility has to fit. Sizing it per classification
        // would have refused this one.
        for ($i = 1; $i <= 25; $i++) {
            $this->stream(['stream_code' => sprintf('F%02d', $i), 'fuel_type' => "Fuel {$i}"]);
        }

        $ws = $this->export()->getSheetByName('3d2_ Calculation Approaches');

        $this->assertSame('F01', $ws->getCell('B25')->getValue());
        $this->assertSame('F25', $ws->getCell('B49')->getValue());
    }

    // ---------------------------------------------------------------------
    // 3d2 (c) — the measuring instruments
    // ---------------------------------------------------------------------

    private function instrument(array $overrides = []): MrvMeasuringInstrument
    {
        return MrvMeasuringInstrument::create(array_merge([
            'company_id' => $this->company->id,
            'facility_id' => $this->facility->id,
            'reporting_year' => 2026,
            'instrument_code' => 'MI01',
            'source_stream_code' => 'F01',
            'type' => 'Coriolis flow meter',
            'location_id' => 'LOC-7',
            'range_unit' => 'Nm3/h',
            'range_lower' => 0,
            'range_upper' => 500,
            'specified_uncertainty_pct' => 1.5,
            'use_range_lower' => 100,
            'use_range_upper' => 400,
        ], $overrides));
    }

    /**
     * The specification half of an instrument — range, uncertainty, the part of
     * the range actually used — was captured by the workspace, validated,
     * stored, and then never exported. It went into the database and stopped.
     */
    public function test_an_instruments_specification_reaches_3d2(): void
    {
        $this->instrument();

        $ws = $this->export()->getSheetByName('3d2_ Calculation Approaches');

        $this->assertSame('MI01', $ws->getCell('B93')->getValue());
        $this->assertSame('F01', $ws->getCell('C93')->getValue());
        $this->assertSame('Coriolis flow meter', $ws->getCell('D93')->getValue());
        $this->assertSame('LOC-7', $ws->getCell('F93')->getValue());
        $this->assertSame('Nm3/h', $ws->getCell('G93')->getValue());
        $this->assertEquals(500, $ws->getCell('I93')->getValue());
        $this->assertEquals(1.5, $ws->getCell('J93')->getValue());
        $this->assertEquals(400, $ws->getCell('L93')->getValue());
    }

    /**
     * Nothing wrote this table, so nothing cleared it either: EAD's own
     * "Rotary meter" and "Weigh bridge" examples shipped inside every
     * submission, describing instruments the facility does not own.
     */
    public function test_the_templates_illustrative_instruments_do_not_survive(): void
    {
        $this->instrument();

        $ws = $this->export()->getSheetByName('3d2_ Calculation Approaches');

        $this->assertNull($ws->getCell('D95')->getValue(), 'The "Weigh bridge" example is still in the submission.');
        $this->assertNull($ws->getCell('M93')->getValue(), 'The "Illustrative" marker is still in the submission.');
        $this->assertNull($ws->getCell('J94')->getValue(), 'The example\'s second uncertainty row survived.');
    }

    /**
     * Each instrument owns a merged row PAIR, so the second one starts at 95
     * rather than 94. Writing them one row apart would put every instrument
     * after the first inside its neighbour's merge.
     */
    public function test_instruments_are_written_one_per_row_pair(): void
    {
        $this->instrument();
        $this->instrument(['instrument_code' => 'MI02', 'type' => 'Weighbridge']);
        $this->instrument(['instrument_code' => 'MI03', 'type' => 'Orifice plate']);

        $ws = $this->export()->getSheetByName('3d2_ Calculation Approaches');

        $this->assertSame('MI01', $ws->getCell('B93')->getValue());
        $this->assertSame('MI02', $ws->getCell('B95')->getValue());
        $this->assertSame('MI03', $ws->getCell('B97')->getValue());
        $this->assertNull($ws->getCell('B94')->getValue());
    }

    /**
     * 3d2(c) asks for the instruments that determine ACTIVITY DATA. A CEMS
     * measurement point is not one; it belongs on 3e2(b), which still lists it.
     * Writing it here with every specification column blank would misdescribe
     * the monitoring.
     */
    public function test_a_measurement_point_with_no_specification_stays_off_3d2(): void
    {
        MrvMeasuringInstrument::create([
            'company_id' => $this->company->id,
            'facility_id' => $this->facility->id,
            'reporting_year' => 2026,
            'instrument_code' => 'MI09',
            'emission_source_code' => 'S03',
            'procedures' => 'Stack CEMS, hourly averaging, QAL2 every five years.',
        ]);

        $book = $this->export();

        $this->assertNull($book->getSheetByName('3d2_ Calculation Approaches')->getCell('B93')->getValue());
        $this->assertSame('MI09', $book->getSheetByName('3e2_MeasurementBasedApproaches')->getCell('B23')->getValue());
    }

    /**
     * The mirror of the 3d2 filter. A weighbridge is not a point where
     * emissions are continuously measured, so it has no business on 3e2 — and
     * while every instrument was listed there, a facility with twenty-five
     * meters and no CEMS was refused for overflowing a fifteen-row table it
     * never belonged in.
     */
    public function test_a_meter_with_no_procedures_stays_off_3e2(): void
    {
        $this->instrument();

        $book = $this->export();

        $this->assertNull($book->getSheetByName('3e2_MeasurementBasedApproaches')->getCell('B23')->getValue());
        $this->assertSame('MI01', $book->getSheetByName('3d2_ Calculation Approaches')->getCell('B93')->getValue());
    }

    /**
     * Twenty-five slots — 93, 95 … 141 — and the twenty-sixth has nowhere to go.
     *
     * This test first asserted a limit of nine, because the table was mapped
     * from the MI1..MI23 labels in column B (which stop at 109) rather than
     * from the dropdown fill on column C (which does not). A guard that refuses
     * a valid tenth instrument is its own outage, so the boundary is pinned
     * from both sides below.
     */
    public function test_more_instruments_than_3d2_holds_is_refused(): void
    {
        for ($i = 1; $i <= 26; $i++) {
            $this->instrument(['instrument_code' => sprintf('MI%02d', $i)]);
        }

        $this->expectException(EadCapacityExceededException::class);
        $this->expectExceptionMessageMatches('/26 measuring instruments, but the workbook holds 25/');

        app(EadWorkbookFiller::class)->fill($this->facility, 2026);
    }

    /**
     * The other side of that boundary: twenty-five must export, and the
     * twenty-fifth must land on the table's own last slot rather than past it.
     */
    public function test_the_full_twenty_five_instruments_still_export(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            $this->instrument(['instrument_code' => sprintf('MI%02d', $i)]);
        }

        $ws = $this->export()->getSheetByName('3d2_ Calculation Approaches');

        $this->assertSame('MI01', $ws->getCell('B93')->getValue());
        $this->assertSame('MI25', $ws->getCell('B141')->getValue());
    }
}

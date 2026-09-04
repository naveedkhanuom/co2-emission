<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Facilities;
use App\Models\MrvFacilityReport;
use App\Models\User;
use App\Services\MRV\EadWorkbookFiller;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Spatie\Permission\PermissionRegistrar;
use Tests\TenantTestCase;

/**
 * The monitoring plan's narrative and tabular sections — sheets 2c1 contacts,
 * 2c2 products, 3g methane, 4h verification and data gaps, 4I management and
 * QA, and 4J mitigation measures.
 *
 * Every one of these had a JSON column on MrvFacilityReport from the day the
 * MRV layer was written, no controller action that could write to it, and no
 * form. Five sheets of a fourteen-sheet regulatory workbook exported blank
 * however complete the operator believed their plan to be.
 *
 * The real template is not needed for most of this: as in EadWorkbookExportTest
 * the fixture reproduces the structural contract, and one test checks it still
 * agrees with the genuine v8.1 when that happens to be installed.
 */
class MrvMonitoringPlanTest extends TenantTestCase
{
    private Company $company;

    private Facilities $facility;

    private string $fixture;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Ruwais Refining Co '.uniqid(),
            'industry_type' => 'energy',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'MRV Officer',
            'email' => 'plan-'.uniqid().'@example.test',
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

        $identifiers = $book->getActiveSheet();
        $identifiers->setTitle('2c1_ Identifiers');
        $identifiers->setCellValue('D5', 'Entity/Company name ');
        $identifiers->setCellValue('D7', 'Facility Name (as stated in the Environmental Permit)');

        $facility = $book->createSheet();
        $facility->setTitle('2c2_Facility Description');
        $facility->setCellValue('C42', 'Emission source ID');
        $facility->setCellValue('C74', 'Source Stream ID');
        // Products ship with P01/P02 worked examples.
        $facility->setCellValue('D17', 'Refinery products');
        $facility->setCellValue('G17', 1);
        $facility->setCellValue('D18', 'EAF high alloy steel');

        $tiers = $book->createSheet();
        $tiers->setTitle('3d1_Source Streams (Calculated)');
        $tiers->setCellValue('B9', 'Source stream ID');
        $tiers->setCellValue('C40', 'Tier level used ');

        $calc = $book->createSheet();
        $calc->setTitle('3d2_ Calculation Approaches');
        $calc->setCellValue('B59', 'Source Stream ID');

        $measured = $book->createSheet();
        $measured->setTitle('3e1_Emission Sources (Measured)');
        $measured->setCellValue('B8', 'Emission source ID');
        $measured->setCellValue('C39', 'Tier level used ');
        // The illustrative first row EAD ships.
        $measured->setCellValue('B9', 'S01');
        $measured->setCellValue('D9', 'Major');
        $measured->setCellValue('E9', 'Illustrative');
        $measured->setCellValue('C40', 3);
        $measured->setCellValue('I40', 'Illustrative');

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

        $management = $book->createSheet();
        $management->setTitle('4I - Management & QA');
        // The worked example EAD ships in the responsibilities table.
        $management->setCellValue('C7', 'HSEQ deputy head of unit');
        $management->setCellValue('F7', 'Overall responsibility for Monitoring & Reporting');
        $management->setCellValue('E20', '• Responsible person maintains a calendar');
        $management->setCellValue('E21', '• Responsible person orders external experts');
        $management->setCellValue('E22', '• Responsible person keeps records');

        $book->createSheet()->setTitle('4J - Mitigation Measures');

        $path = tempnam(sys_get_temp_dir(), 'plan').'.xlsx';
        (new XlsxWriter($book))->save($path);

        return $path;
    }

    private function save(string $section, array $payload)
    {
        return $this->post(route('mrv.saveReport'), array_merge([
            'facility_id' => $this->facility->id,
            'year' => 2026,
            'section' => $section,
        ], $payload));
    }

    private function report(): MrvFacilityReport
    {
        return MrvFacilityReport::where('facility_id', $this->facility->id)
            ->where('reporting_year', 2026)
            ->firstOrFail();
    }

    private function sheet(string $name)
    {
        $book = app(EadWorkbookFiller::class)->fill($this->facility, 2026, $this->report());
        $path = tempnam(sys_get_temp_dir(), 'out').'.xlsx';
        (new XlsxWriter($book))->save($path);

        return IOFactory::createReader('Xlsx')->load($path)->getSheetByName($name);
    }

    // ---------------------------------------------------------------------

    public function test_contacts_reach_2c1(): void
    {
        $this->save('contacts', [
            'contacts' => [
                'primary' => [
                    'title' => 'Eng.', 'first_name' => 'Fatima', 'surname' => 'Al Mansoori',
                    'job_title' => 'HSE Manager', 'organisation' => '', 'telephone' => '+971 2 555 0100',
                    'email' => 'f.almansoori@example.ae',
                ],
                'alternate' => ['first_name' => 'Omar', 'surname' => 'Haddad', 'email' => 'o.haddad@example.ae'],
            ],
        ])->assertRedirect();

        $ws = $this->sheet('2c1_ Identifiers');

        // Primary block runs H30..H36 in title…email order.
        $this->assertSame('Eng.', $ws->getCell('H30')->getValue());
        $this->assertSame('Fatima', $ws->getCell('H31')->getValue());
        $this->assertSame('Al Mansoori', $ws->getCell('H32')->getValue());
        $this->assertSame('f.almansoori@example.ae', $ws->getCell('H36')->getValue());

        // Alternate block runs H38..H44.
        $this->assertSame('Omar', $ws->getCell('H39')->getValue());
        $this->assertSame('o.haddad@example.ae', $ws->getCell('H44')->getValue());
    }

    public function test_an_invalid_contact_email_is_rejected(): void
    {
        $this->save('contacts', [
            'contacts' => ['primary' => ['email' => 'not-an-email']],
        ])->assertSessionHasErrors('contacts.primary.email');
    }

    public function test_products_reach_2c2_and_the_worked_example_is_cleared(): void
    {
        $this->save('products', [
            'products' => [
                ['id' => 'P01', 'category' => 'Refinery products', 'technology' => 'Hydrocracking',
                    'energy_related' => 1, 'process_emissions' => 1,
                    'capacity' => 500000, 'capacity_unit' => 't/yr', 'actual' => 470000, 'actual_unit' => 't'],
                // Left entirely blank — not a product, and must not export a row.
                ['id' => 'P02'],
            ],
        ])->assertRedirect();

        $this->assertCount(1, $this->report()->products);

        $ws = $this->sheet('2c2_Facility Description');

        $this->assertSame('Refinery products', $ws->getCell('D17')->getValue());
        $this->assertSame('Hydrocracking', $ws->getCell('E17')->getValue());
        $this->assertEquals(1, $ws->getCell('G17')->getValue());
        $this->assertEquals(1, $ws->getCell('H17')->getValue());
        $this->assertEquals(500000, $ws->getCell('I17')->getValue());
        $this->assertSame('t/yr', $ws->getCell('J17')->getValue());

        // Row 18's "EAF high alloy steel" is the template's example, and this
        // refinery does not make steel.
        $this->assertNull($ws->getCell('D18')->getValue());
    }

    public function test_a_product_category_outside_the_eu_benchmark_list_is_rejected(): void
    {
        $this->save('products', [
            'products' => [['id' => 'P01', 'category' => 'Artisanal cheese']],
        ])->assertSessionHasErrors('products.0.category');
    }

    public function test_methane_is_only_written_when_the_facility_says_it_occurs(): void
    {
        $this->save('methane', [
            'methane_present' => 1,
            'methane' => [
                'annual_volume' => '412 t CH4',
                'estimated_co2e' => '11,536 tCO2e (AR5)',
                'estimation_source' => 'IPCC AR5, GWP100 = 28',
                'key_sources' => 'Flare tip leakage, compressor seals',
                'determination_procedures' => 'Quarterly OGI survey',
                'ldar_title' => 'LDAR-2026',
                'ldar_description' => 'Optical gas imaging, quarterly',
                'ldar_person' => 'Reliability Engineer, ext. 4412',
            ],
        ])->assertRedirect();

        $ws = $this->sheet('3g_Methane');

        $this->assertSame('412 t CH4', $ws->getCell('E9')->getValue());
        $this->assertSame('11,536 tCO2e (AR5)', $ws->getCell('E10')->getValue());
        $this->assertSame('Flare tip leakage, compressor seals', $ws->getCell('E13')->getValue());
        $this->assertSame('LDAR-2026', $ws->getCell('E18')->getValue());
        $this->assertSame('Reliability Engineer, ext. 4412', $ws->getCell('E20')->getValue());
    }

    public function test_a_facility_with_no_methane_submits_an_empty_3g(): void
    {
        // Answers from an earlier draft, then the operator determines there is
        // no methane. A half-filled 3g in that state is a worse answer than
        // none — 2c2!I69 tells the reader whether to look at all.
        $this->save('methane', [
            'methane_present' => 1,
            'methane' => ['annual_volume' => '412 t CH4'],
        ]);

        $this->save('methane', ['methane' => ['annual_volume' => '412 t CH4']]);

        $this->assertFalse($this->report()->methane_present);
        $this->assertNull($this->sheet('3g_Methane')->getCell('E9')->getValue());
    }

    public function test_the_fall_back_approach_reaches_3f(): void
    {
        $this->save('fallback', [
            'fallback_description' => 'Mass balance across the reformer, with carbon content by monthly lab assay.',
            'fallback_justification' => 'Overall uncertainty assessed at 4.1%, below the 7.5% threshold. Workings in FB-2026-01.',
        ])->assertRedirect();

        $ws = $this->sheet('3f_Fallback Approach');

        // Two merged input blocks: (a) B9:K17, (b) B21:K29.
        $this->assertStringContainsString('Mass balance', $ws->getCell('B9')->getValue());
        $this->assertStringContainsString('4.1%', $ws->getCell('B21')->getValue());
    }

    public function test_clearing_the_fall_back_boxes_clears_the_sheet(): void
    {
        $this->save('fallback', [
            'fallback_description' => 'Mass balance across the reformer.',
            'fallback_justification' => 'Uncertainty 4.1%.',
        ]);

        // The operator stops using the fall-back approach and empties the form.
        // validate() omits absent keys, so a partial fill() would have kept the
        // old text — leaving a justification in a regulatory submission for an
        // approach no longer in use.
        $this->save('fallback', []);

        $report = $this->report();

        $this->assertNull($report->fallback_description);
        $this->assertNull($report->fallback_justification);
        $this->assertNull($this->sheet('3f_Fallback Approach')->getCell('B9')->getValue());
    }

    public function test_the_workspace_prompts_when_a_source_uses_fall_back_and_nothing_is_recorded(): void
    {
        \App\Models\MrvEmissionSource::create([
            'company_id' => $this->company->id,
            'facility_id' => $this->facility->id,
            'reporting_year' => 2026,
            'source_code' => 'S01',
            'name' => 'Reformer',
            'methodology' => 'fallback',
        ]);

        $response = $this->get(route('mrv.index', ['facility_id' => $this->facility->id, 'year' => 2026]));

        $response->assertOk();
        $response->assertSee('is monitored with the', false);
        $response->assertSee('fall-back approach', false);
    }

    public function test_no_prompt_when_nothing_uses_fall_back(): void
    {
        \App\Models\MrvEmissionSource::create([
            'company_id' => $this->company->id,
            'facility_id' => $this->facility->id,
            'reporting_year' => 2026,
            'source_code' => 'S01',
            'name' => 'Boiler',
            'methodology' => 'calculation',
        ]);

        $this->get(route('mrv.index', ['facility_id' => $this->facility->id, 'year' => 2026]))
            ->assertOk()
            ->assertSee('this sheet can stay empty', false);
    }

    public function test_verification_and_data_gaps_reach_4h(): void
    {
        $this->save('verification', [
            'verification_text' => 'Verified by an accredited third party against ISO 14064-3.',
            'data_gaps' => [
                ['ref' => 'F01', 'from' => '2026-03-01', 'until' => '2026-03-14',
                    'description' => 'Flow meter offline during turnaround; substituted with the prior-year average.',
                    'estimated_co2e' => 812.5, 'source' => 'Prior-year average'],
                ['ref' => '', 'description' => ''],
            ],
        ])->assertRedirect();

        $this->assertCount(1, $this->report()->data_gaps);

        $ws = $this->sheet('4h_Verification and Data Gaps');

        $this->assertStringContainsString('ISO 14064-3', $ws->getCell('C7')->getValue());
        $this->assertSame('F01', $ws->getCell('C20')->getValue());
        $this->assertEquals(812.5, $ws->getCell('H20')->getValue());
        $this->assertSame('Prior-year average', $ws->getCell('J20')->getValue());
    }

    public function test_a_gap_that_ends_before_it_starts_is_rejected(): void
    {
        $this->save('verification', [
            'data_gaps' => [['ref' => 'F01', 'from' => '2026-05-01', 'until' => '2026-04-01']],
        ])->assertSessionHasErrors('data_gaps.0.until');
    }

    public function test_management_and_qa_reach_4i_over_the_worked_example(): void
    {
        $this->save('management', [
            'management' => [
                'responsibilities' => [
                    ['post' => 'Sustainability Manager', 'duties' => 'Owns the monitoring plan and the annual return.'],
                    ['post' => '', 'duties' => ''],
                ],
                'equipment_qa' => [
                    'title' => 'Instrument calibration',
                    'reference' => 'QA-114',
                    'description' => "Annual calibration by an accredited lab.\nMonthly zero/span checks.\nOut-of-tolerance handled under QA-118.",
                    'responsible_post' => 'Metering Supervisor',
                    'records_location' => 'Maximo',
                ],
                'data_validation' => ['title' => 'Annual data review', 'responsible_post' => 'Finance Controller'],
                'further_details' => 'Reviewed at the annual ISO 14001 audit.',
            ],
        ])->assertRedirect();

        $this->assertCount(1, $this->report()->management['responsibilities']);

        $ws = $this->sheet('4I - Management & QA');

        $this->assertSame('Sustainability Manager', $ws->getCell('C7')->getValue());
        $this->assertStringContainsString('monitoring plan', $ws->getCell('F7')->getValue());
        $this->assertSame('Instrument calibration', $ws->getCell('E17')->getValue());
        $this->assertSame('Metering Supervisor', $ws->getCell('E23')->getValue());

        // The description block is three merged rows; one line each, the way
        // the template's own example lays it out.
        $this->assertSame('Annual calibration by an accredited lab.', $ws->getCell('E20')->getValue());
        $this->assertSame('Monthly zero/span checks.', $ws->getCell('E21')->getValue());
        $this->assertStringContainsString('QA-118', $ws->getCell('E22')->getValue());

        $this->assertSame('Annual data review', $ws->getCell('E28')->getValue());
        $this->assertStringContainsString('ISO 14001', $ws->getCell('C37')->getValue());
    }

    public function test_a_shorter_description_does_not_leave_the_previous_ones_tail_behind(): void
    {
        $this->save('management', [
            'management' => ['equipment_qa' => ['description' => "Line one.\nLine two.\nLine three."]],
        ]);

        $this->save('management', [
            'management' => ['equipment_qa' => ['description' => 'Just the one line now.']],
        ]);

        $ws = $this->sheet('4I - Management & QA');

        $this->assertSame('Just the one line now.', $ws->getCell('E20')->getValue());
        $this->assertNull($ws->getCell('E21')->getValue());
        $this->assertNull($ws->getCell('E22')->getValue());
    }

    public function test_a_description_longer_than_its_rows_is_folded_not_truncated(): void
    {
        $this->save('management', [
            'management' => ['equipment_qa' => ['description' => "One.\nTwo.\nThree.\nFour.\nFive."]],
        ]);

        $ws = $this->sheet('4I - Management & QA');

        $this->assertSame('One.', $ws->getCell('E20')->getValue());
        $this->assertSame('Two.', $ws->getCell('E21')->getValue());

        // An operator's procedure is not ours to cut short.
        $last = $ws->getCell('E22')->getValue();
        foreach (['Three.', 'Four.', 'Five.'] as $line) {
            $this->assertStringContainsString($line, $last);
        }
    }

    public function test_mitigation_measures_reach_4j_across_all_scopes(): void
    {
        $this->save('mitigation', [
            'mitigation_measures' => [
                ['description' => 'Waste-heat recovery on Train 3', 'category' => 'Emission Reduction',
                    'scope' => 1, 'ghg' => 'CO₂', 'start_year' => 2025, 'status' => 'Implemented',
                    'baseline' => '4,200 (2022)', 'reporting_year_reduction' => 1150,
                    'expected_annual_reduction' => 1400, 'methodology' => 'IPMVP Option C',
                    'verification' => 'Third-party verified'],
                ['description' => 'Solar PPA for site electricity', 'category' => 'Emission Avoidance',
                    'scope' => 2, 'status' => 'Planned'],
                ['description' => ''],
            ],
            'mitigation_additional' => 'A further two measures are in feasibility study.',
        ])->assertRedirect();

        $stored = $this->report()->mitigation_measures;
        $this->assertCount(2, $stored['measures']);

        $ws = $this->sheet('4J - Mitigation Measures');

        $this->assertSame('Waste-heat recovery on Train 3', $ws->getCell('C7')->getValue());
        $this->assertSame('Emission Reduction', $ws->getCell('D7')->getValue());
        $this->assertEquals(1, $ws->getCell('E7')->getValue());
        $this->assertEquals(2025, $ws->getCell('G7')->getValue());
        $this->assertEquals(1150, $ws->getCell('J7')->getValue());
        $this->assertSame('Third-party verified', $ws->getCell('M7')->getValue());

        // Scope 2 belongs here — 4J is the one sheet that is not Scope 1 only.
        $this->assertSame('Solar PPA for site electricity', $ws->getCell('C8')->getValue());
        $this->assertEquals(2, $ws->getCell('E8')->getValue());

        $this->assertStringContainsString('feasibility study', $ws->getCell('C17')->getValue());
    }

    public function test_an_invented_mitigation_status_is_rejected(): void
    {
        $this->save('mitigation', [
            'mitigation_measures' => [['description' => 'Something', 'status' => 'Nearly done']],
        ])->assertSessionHasErrors('mitigation_measures.0.status');
    }

    public function test_saving_one_section_leaves_the_others_alone(): void
    {
        // The workbook is filled over weeks by different people. A combined
        // form would mean one person's save wiping another's work.
        $this->save('contacts', ['contacts' => ['primary' => ['surname' => 'Al Mansoori']]]);
        $this->save('mitigation', ['mitigation_measures' => [['description' => 'Waste-heat recovery']]]);
        $this->save('verification', ['verification_text' => 'Third-party verified.']);

        $report = $this->report();

        $this->assertSame('Al Mansoori', data_get($report->contacts, 'primary.surname'));
        $this->assertSame('Waste-heat recovery', data_get($report->mitigation_measures, 'measures.0.description'));
        $this->assertSame('Third-party verified.', $report->verification_text);
    }

    public function test_an_unknown_section_is_rejected(): void
    {
        $this->save('sabotage', [])->assertSessionHasErrors('section');
    }

    public function test_the_plan_sections_render_on_the_workspace(): void
    {
        $this->save('contacts', ['contacts' => ['primary' => ['surname' => 'Al Mansoori']]]);

        $response = $this->get(route('mrv.index', ['facility_id' => $this->facility->id, 'year' => 2026]));

        $response->assertOk();
        $response->assertSee('Monitoring Plan Details', false);
        // Eight: contacts, products, measurement, fall-back, methane,
        // verification, management, mitigation.
        $response->assertSee('of 8 sections started', false);
        $response->assertSee('Al Mansoori', false);
    }

    public function test_the_real_template_accepts_every_section(): void
    {
        config(['mrv.ead_template' => null]);

        if (! is_file(EadWorkbookFiller::templatePath())) {
            $this->markTestSkipped('EAD template not installed.');
        }

        $this->save('contacts', ['contacts' => ['primary' => ['surname' => 'Al Mansoori']]]);
        $this->save('methane', ['methane_present' => 1, 'methane' => ['annual_volume' => '412 t']]);
        $this->save('fallback', ['fallback_description' => 'Mass balance.']);
        $this->save('verification', ['verification_text' => 'Verified.']);
        $this->save('management', ['management' => ['equipment_qa' => ['title' => 'Calibration']]]);
        $this->save('mitigation', ['mitigation_measures' => [['description' => 'Waste-heat recovery']]]);
        $this->save('products', ['products' => [['id' => 'P01', 'category' => 'Refinery products']]]);

        // Cell coordinates in the fixture are only meaningful if they are the
        // real workbook's coordinates too.
        $book = app(EadWorkbookFiller::class)->fill($this->facility, 2026, $this->report());

        $this->assertSame('412 t', $book->getSheetByName('3g_Methane')->getCell('E9')->getValue());
        $this->assertSame('Mass balance.', $book->getSheetByName('3f_Fallback Approach')->getCell('B9')->getValue());
        $this->assertSame('Verified.', $book->getSheetByName('4h_Verification and Data Gaps')->getCell('C7')->getValue());
        $this->assertSame('Calibration', $book->getSheetByName('4I - Management & QA')->getCell('E17')->getValue());
        $this->assertSame('Waste-heat recovery', $book->getSheetByName('4J - Mitigation Measures')->getCell('C7')->getValue());
        $this->assertSame('Refinery products', $book->getSheetByName('2c2_Facility Description')->getCell('D17')->getValue());
    }
}

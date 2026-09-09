<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\EmissionRecord;
use App\Models\Facilities;
use App\Models\MrvEmissionSource;
use App\Models\MrvMeasuringInstrument;
use App\Models\MrvSourceStream;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;
use Tests\TenantTestCase;

/**
 * Everything the EAD export can carry, the operator can now enter.
 *
 * Several columns had a database field and an export path but no form field,
 * so the workbook's cells for them were permanently blank — the combustion
 * device and its capacity (2c2 J–L) and the source of accuracy (3d1 G).
 * Emission sources were worse: nothing could edit them at all. Prefill
 * invented one per distinct Scope 1 source and stamped it CO₂-only,
 * energy-related and calculation-based, and no screen could correct that.
 *
 * For a cement or lime plant those defaults are wrong in the expensive
 * direction: calcination is process CO₂, frequently the larger half of the
 * inventory, and EAD asks for combustion and process emissions to be reported
 * separately.
 */
class MrvDataEntryTest extends TenantTestCase
{
    private Company $company;

    private Facilities $facility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Al Ain Cement Co '.uniqid(),
            'industry_type' => 'manufacturing',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'MRV Officer',
            'email' => 'mrv-entry-'.uniqid().'@example.test',
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
            'name' => 'Al Ain Kiln Line 2',
            'mrv_enabled' => true,
        ]);
    }

    private function sourcePayload(array $overrides = []): array
    {
        return array_merge([
            'facility_id' => $this->facility->id,
            'year' => 2026,
            'source_code' => 'S01',
            'name' => 'Cement kiln 1',
            'methodology' => 'calculation',
        ], $overrides);
    }

    // ---------------------------------------------------------------------
    // Stream fields that were stored and exported but unenterable
    // ---------------------------------------------------------------------

    public function test_the_combustion_device_and_its_capacity_can_be_recorded(): void
    {
        $this->post(route('mrv.saveStream'), [
            'facility_id' => $this->facility->id,
            'year' => 2026,
            'stream_code' => 'F01',
            'classification' => 'fuel_combusted',
            'combustion_device' => 'Rotary kiln, precalciner',
            'device_capacity' => 3200,
            'device_capacity_unit' => 't/day',
            'accuracy_source' => 'Lab. Analysis',
        ])->assertRedirect();

        $stream = MrvSourceStream::where('facility_id', $this->facility->id)->firstOrFail();

        $this->assertSame('Rotary kiln, precalciner', $stream->combustion_device);
        $this->assertEquals(3200, $stream->device_capacity);
        $this->assertSame('t/day', $stream->device_capacity_unit);
        $this->assertSame('Lab. Analysis', $stream->accuracy_source);
    }

    public function test_an_unknown_stream_classification_is_rejected(): void
    {
        // The workbook's own cell is a dropdown bound to sheet 4k's wording.
        $this->post(route('mrv.saveStream'), [
            'facility_id' => $this->facility->id,
            'year' => 2026,
            'stream_code' => 'F01',
            'classification' => 'something_invented',
        ])->assertSessionHasErrors('classification');
    }

    // ---------------------------------------------------------------------
    // Emission sources — no editor existed at all
    // ---------------------------------------------------------------------

    public function test_a_process_emission_source_can_be_described_as_one(): void
    {
        $this->post(route('mrv.saveSource'), $this->sourcePayload([
            'name' => 'Clinker calcination',
            'description' => 'CO2 released from limestone in the precalciner.',
            'associated_product' => 'P01',
            'ghg_types' => 'CO2, CH4',
            'process_emissions' => 1,
            'materiality' => 'major',
            'total_co2e' => 412000,
        ]))->assertRedirect();

        $source = MrvEmissionSource::where('facility_id', $this->facility->id)->firstOrFail();

        $this->assertSame('Clinker calcination', $source->name);
        $this->assertSame('P01', $source->associated_product);
        $this->assertSame('CO2, CH4', $source->ghg_types);
        $this->assertTrue($source->process_emissions);
        $this->assertSame('major', $source->materiality);
        $this->assertEquals(412000, $source->total_co2e);
    }

    public function test_clearing_a_checkbox_actually_clears_it(): void
    {
        // An unticked box is absent from the payload, not false. Resolved
        // implicitly, "energy related" could be set but never unset.
        $this->post(route('mrv.saveSource'), $this->sourcePayload(['energy_related' => 1]));

        $this->assertTrue(
            MrvEmissionSource::where('source_code', 'S01')->firstOrFail()->energy_related
        );

        $this->post(route('mrv.saveSource'), $this->sourcePayload());

        $this->assertFalse(
            MrvEmissionSource::where('source_code', 'S01')->firstOrFail()->energy_related
        );
    }

    public function test_an_unknown_methodology_is_rejected(): void
    {
        $this->post(route('mrv.saveSource'), $this->sourcePayload([
            'methodology' => 'vibes',
        ]))->assertSessionHasErrors('methodology');
    }

    public function test_a_source_still_carrying_streams_is_not_deleted(): void
    {
        $this->post(route('mrv.saveSource'), $this->sourcePayload());
        $source = MrvEmissionSource::where('source_code', 'S01')->firstOrFail();

        MrvSourceStream::create([
            'company_id' => $this->company->id,
            'facility_id' => $this->facility->id,
            'reporting_year' => 2026,
            'stream_code' => 'F01',
            'emission_source_code' => 'S01',
            'classification' => 'fuel_combusted',
        ]);

        // 2c2 column E is that reference; orphaning it would export a stream
        // pointing at a source that is not in the submission.
        $this->delete(route('mrv.deleteSource', $source->id))->assertRedirect();

        $this->assertNotNull(MrvEmissionSource::find($source->id));
        $this->assertStringContainsString('still has 1 source stream', session('error'));
    }

    public function test_a_source_with_no_streams_is_deleted(): void
    {
        $this->post(route('mrv.saveSource'), $this->sourcePayload());
        $source = MrvEmissionSource::where('source_code', 'S01')->firstOrFail();

        $this->delete(route('mrv.deleteSource', $source->id))->assertRedirect();

        $this->assertNull(MrvEmissionSource::find($source->id));
    }

    // ---------------------------------------------------------------------
    // Prefill must not undo the corrections it made necessary
    // ---------------------------------------------------------------------

    public function test_re_running_prefill_keeps_the_operators_corrections(): void
    {
        EmissionRecord::create([
            'company_id' => $this->company->id,
            'entry_date' => '2026-05-02',
            'scope' => 1,
            'facility' => $this->facility->name,
            'facility_id' => $this->facility->id,
            'emission_source' => 'Kiln fuel',
            'activity_data' => 100,
            'activity_unit' => 't',
            'co2e_value' => 300,
            'status' => 'active',
        ]);

        $this->post(route('mrv.prefill'), ['facility_id' => $this->facility->id, 'year' => 2026]);

        $source = MrvEmissionSource::where('facility_id', $this->facility->id)->firstOrFail();
        $this->assertTrue($source->energy_related, 'Precondition: prefill guesses energy-related.');

        // The operator corrects the guess.
        $this->post(route('mrv.saveSource'), $this->sourcePayload([
            'source_code' => $source->source_code,
            'name' => $source->name,
            'ghg_types' => 'CO2, CH4',
            'process_emissions' => 1,
            'methodology' => 'measurement',
        ]));

        // …and later re-runs prefill, which is idempotent by design and
        // therefore encouraged. It used to reassert every guess.
        $this->post(route('mrv.prefill'), ['facility_id' => $this->facility->id, 'year' => 2026]);

        $source->refresh();

        $this->assertTrue($source->process_emissions, 'The process-emissions correction was undone.');
        $this->assertFalse($source->energy_related);
        $this->assertSame('CO2, CH4', $source->ghg_types);
        $this->assertSame('measurement', $source->methodology);

        // What IS derived from the records still refreshes.
        $this->assertEquals(300, $source->total_co2e);
    }

    public function test_re_running_prefill_keeps_a_corrected_stream_classification(): void
    {
        EmissionRecord::create([
            'company_id' => $this->company->id,
            'entry_date' => '2026-05-02',
            'scope' => 1,
            'facility' => $this->facility->name,
            'facility_id' => $this->facility->id,
            'emission_source' => 'Raw meal',
            'activity_data' => 50,
            'activity_unit' => 't',
            'co2e_value' => 120,
            'status' => 'active',
        ]);

        $this->post(route('mrv.prefill'), ['facility_id' => $this->facility->id, 'year' => 2026]);

        $stream = MrvSourceStream::where('facility_id', $this->facility->id)->firstOrFail();

        $this->post(route('mrv.saveStream'), [
            'facility_id' => $this->facility->id,
            'year' => 2026,
            'stream_code' => $stream->stream_code,
            'classification' => 'other_input',
            'tier_level' => 3,
            'accuracy_source' => 'Lab. Analysis',
        ]);

        $this->post(route('mrv.prefill'), ['facility_id' => $this->facility->id, 'year' => 2026]);

        $stream->refresh();

        $this->assertSame('other_input', $stream->classification);
        $this->assertSame(3, $stream->tier_level);
        $this->assertSame('Lab. Analysis', $stream->accuracy_source);
        $this->assertEquals(50, $stream->activity_level, 'Derived activity should still refresh.');
    }

    // ---------------------------------------------------------------------
    // The controlled vocabularies
    // ---------------------------------------------------------------------

    public function test_the_export_labels_come_from_the_same_list_the_form_offers(): void
    {
        // The form's options and the filler's labels used to be two hardcoded
        // lists. Adding a methodology to one and not the other produced a
        // value that saved cleanly and exported blank.
        foreach (array_keys(config('mrv.methodologies')) as $key) {
            $this->post(route('mrv.saveSource'), $this->sourcePayload(['methodology' => $key]))
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(
            ['Calculation-based', 'Measurement-based', 'Fall-back'],
            array_values(config('mrv.methodologies')),
            'These strings are the workbook’s own dropdown values and are not ours to reword.'
        );

        $this->assertSame(
            ['Fuel combusted', 'Other input', 'Output'],
            array_values(config('mrv.stream_types'))
        );
    }

    public function test_the_primary_activity_list_matches_the_templates_own(): void
    {
        // Sheet 4k, "List of Primary Activities". 2c2's cell is bound to it,
        // so a value not on this list fails EAD's validation.
        $this->assertSame([
            'Combustion of fuels',
            'Production of coke',
            'Metal ore roasting or sintering',
            'Production of iron or steel',
            'Production of aluminium',
            'Production of cement clinker',
            'Production of glass',
            'Other',
        ], config('mrv.primary_activities'));

        // Sheet 4k rows 13–68 — 56 EU product benchmarks, transcribed verbatim
        // including "[Primary] Aluminium" and the German chemical spellings.
        $this->assertContains('Grey cement clinker', config('mrv.product_benchmarks'));
        $this->assertContains('[Primary] Aluminium', config('mrv.product_benchmarks'));
        $this->assertCount(56, config('mrv.product_benchmarks'));
    }

    // ---------------------------------------------------------------------
    // Which records belong to this facility
    // ---------------------------------------------------------------------

    private function scope1Record(array $overrides = []): EmissionRecord
    {
        return EmissionRecord::create(array_merge([
            'company_id' => $this->company->id,
            'entry_date' => '2026-04-11',
            'scope' => 1,
            'facility' => $this->facility->name,
            'facility_id' => $this->facility->id,
            'emission_source' => 'Natural Gas',
            'activity_data' => 500,
            'activity_unit' => 'MWh',
            'co2e_value' => 90,
            'status' => 'active',
        ], $overrides));
    }

    /**
     * Prefill matched Scope 1 records on the facility NAME. Nothing stops one
     * company from having two facilities with the same name — there is no
     * unique constraint, and "Boiler House" at two sites is ordinary — so both
     * facilities' records were pulled into one regulated submission.
     *
     * Checked against the pre-fix controller: the second facility's 700 tCO2e
     * arrived in this facility's streams, and its total read 790 instead of 90.
     */
    public function test_prefill_leaves_a_same_named_facilitys_records_alone(): void
    {
        $twin = Facilities::create([
            'company_id' => $this->company->id,
            'name' => $this->facility->name,
            'mrv_enabled' => true,
        ]);

        $this->scope1Record();
        $this->scope1Record([
            'facility_id' => $twin->id,
            'emission_source' => 'Diesel',
            'co2e_value' => 700,
        ]);

        $this->post(route('mrv.prefill'), [
            'facility_id' => $this->facility->id,
            'year' => 2026,
        ])->assertRedirect();

        $streams = MrvSourceStream::where('facility_id', $this->facility->id)->get();

        $this->assertCount(1, $streams, 'The twin facility\'s records were prefilled into this submission.');
        $this->assertEquals(90, $streams->first()->estimated_co2e);
    }

    /**
     * The other half: a record written before facility_id existed carries only
     * the name, and dropping those would quietly shrink an operator's prefill.
     */
    public function test_a_record_that_predates_facility_ids_still_prefills(): void
    {
        $this->scope1Record(['facility_id' => null, 'emission_source' => 'Legacy boiler']);

        $this->post(route('mrv.prefill'), [
            'facility_id' => $this->facility->id,
            'year' => 2026,
        ])->assertRedirect();

        $this->assertSame(1, MrvSourceStream::where('facility_id', $this->facility->id)->count());
    }

    // ---------------------------------------------------------------------
    // What has been checked, and what has not
    // ---------------------------------------------------------------------

    /**
     * Calculated streams are recomputed from the EU-ETS formula and a
     * disagreement surfaces as a mismatch. A measured or fall-back figure is
     * whatever the operator typed — the CEMS numeric path is phase 2 — and it
     * reaches the workbook regardless. Presenting the two as one number would
     * show unverified figures as verified, so the screen keeps them apart and
     * says which is which.
     */
    public function test_a_measured_source_is_shown_as_reported_rather_than_recomputed(): void
    {
        MrvEmissionSource::create([
            'company_id' => $this->company->id,
            'facility_id' => $this->facility->id,
            'reporting_year' => 2026,
            'source_code' => 'S01',
            'name' => 'Kiln stack (CEMS)',
            'methodology' => 'measurement',
            'total_co2e' => 41000,
        ]);

        $this->get(route('mrv.index', ['facility_id' => $this->facility->id, 'year' => 2026]))
            ->assertOk()
            ->assertSee('Measured / fall-back sources')
            ->assertSee('41,000.00')
            ->assertSee('not recomputed');
    }

    /**
     * 2c1(b) is mandatory, so it needs somewhere to be typed. The facility
     * settings form is where every other regulatory identifier is captured.
     */
    public function test_the_facility_description_can_be_entered_from_the_mrv_screen(): void
    {
        $this->post(route('mrv.enableFacility'), [
            'facility_id' => $this->facility->id,
            'mrv_enabled' => 1,
            'description' => 'Two kiln lines and a captive limestone quarry.',
        ])->assertRedirect();

        $this->assertSame(
            'Two kiln lines and a captive limestone quarry.',
            $this->facility->fresh()->description
        );
    }

    /**
     * Saving one setting must not blank the others — the form posts every
     * field, and an absent description used to mean "leave it alone".
     */
    public function test_saving_other_settings_does_not_wipe_the_description(): void
    {
        $this->facility->update(['description' => 'Existing description.']);

        $this->post(route('mrv.enableFacility'), [
            'facility_id' => $this->facility->id,
            'mrv_enabled' => 1,
            'primary_sector' => 'Cement',
        ])->assertRedirect();

        $this->assertSame('Existing description.', $this->facility->fresh()->description);
    }

    // ---------------------------------------------------------------------
    // Values the workbook binds to a list
    // ---------------------------------------------------------------------

    /**
     * 2c2 K10 is a dropdown. It had been a free-text box here since the MRV
     * layer was written, so "Oil & Gas" or "energy" saved cleanly and then
     * failed EAD's own validation on receipt.
     */
    public function test_a_sector_outside_the_workbooks_list_is_rejected(): void
    {
        $this->post(route('mrv.enableFacility'), [
            'facility_id' => $this->facility->id,
            'mrv_enabled' => 1,
            'primary_sector' => 'Oil & Gas',
        ])->assertSessionHasErrors('primary_sector');
    }

    public function test_a_sector_on_the_list_is_accepted(): void
    {
        $this->post(route('mrv.enableFacility'), [
            'facility_id' => $this->facility->id,
            'mrv_enabled' => 1,
            'primary_sector' => 'Industrial Processes',
        ])->assertRedirect();

        $this->assertSame('Industrial Processes', $this->facility->fresh()->primary_sector);
    }

    /**
     * C11 asks it, so choosing Other without answering leaves the workbook's
     * own follow-up blank.
     */
    public function test_choosing_other_requires_saying_what_other_is(): void
    {
        $this->post(route('mrv.enableFacility'), [
            'facility_id' => $this->facility->id,
            'mrv_enabled' => 1,
            'primary_sector' => 'Other',
        ])->assertSessionHasErrors('primary_sector_other');

        $this->post(route('mrv.enableFacility'), [
            'facility_id' => $this->facility->id,
            'mrv_enabled' => 1,
            'primary_sector' => 'Other',
            'primary_sector_other' => 'Desalination and power cogeneration',
        ])->assertRedirect();

        $this->assertSame('Desalination and power cogeneration', $this->facility->fresh()->primary_sector_other);
    }

    /**
     * 3d2(c) column C is a dropdown over the source stream IDs, and the
     * instrument form had no field for it at all — the column could be
     * exported and never filled.
     */
    public function test_a_measurement_point_can_name_its_source_stream(): void
    {
        $this->post(route('mrv.saveInstrument'), [
            'facility_id' => $this->facility->id,
            'year' => 2026,
            'instrument_code' => 'MI01',
            'source_stream_code' => 'F02',
            'type' => 'Coriolis meter',
        ])->assertRedirect();

        $this->assertSame(
            'F02',
            MrvMeasuringInstrument::where('facility_id', $this->facility->id)->firstOrFail()->source_stream_code
        );
    }
}

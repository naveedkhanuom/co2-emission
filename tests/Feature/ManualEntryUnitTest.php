<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\EmissionFactor;
use App\Models\EmissionRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Tests\TenantTestCase;

/**
 * GHG-26 — Manual Entry silently discarded the activity unit.
 *
 * The form has a unit dropdown, but it posted `activity_unit` while the
 * controller reads `activityUnit`. The form is submitted with new
 * FormData(form), which serialises by name attribute, so the value went nowhere
 * — leaving "1000" with no record of 1000 of what, on the primary entry screen.
 */
class ManualEntryUnitTest extends TenantTestCase
{
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Manual Unit Co '.uniqid(),
            'industry_type' => 'manufacturing',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Manual Unit User',
            'email' => 'manual-unit-'.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->givePermissionTo(['create-emission-record', 'edit-emission-record', 'list-emission-records']);

        $this->actingAs($user);
        app()->instance('current_company_id', $this->company->id);
        app()->instance('current_company', $this->company);
    }

    /**
     * The field name the form actually submits has to be the one the controller
     * reads. This is the whole bug, so it is asserted against the markup rather
     * than only through the endpoint.
     */
    public function test_the_form_posts_the_unit_under_the_name_the_controller_reads(): void
    {
        $markup = file_get_contents(resource_path('views/emission_records/index.blade.php'));

        $this->assertMatchesRegularExpression(
            '/id="activityUnitSelect"\s+name="activityUnit"/',
            $markup,
            'The unit select does not post under activityUnit, so its value is discarded.'
        );
    }

    public function test_a_manual_entry_stores_its_unit(): void
    {
        $this->postJson(route('emission-records.store'), [
            'entryDate' => '2025-07-01',
            'facilitySelect' => 'Plant A',
            'scopeSelect' => 1,
            'emissionSourceSelect' => 'Diesel (Stationary)',
            'activityData' => 1000,
            'activityUnit' => 'liters',
            'emissionFactor' => 0.00268,
            'co2eValue' => 2.68,
            'confidenceLevel' => 'medium',
            'dataSource' => 'manual',
        ])->assertOk();

        $record = EmissionRecord::where('company_id', $this->company->id)->latest('id')->firstOrFail();

        $this->assertSame('liters', $record->activity_unit);
    }

    /**
     * The edit form repopulated from `d.unit`, which is not a column on an
     * emission record, so reopening a record never restored its unit.
     */
    public function test_the_edit_form_reads_the_unit_from_the_right_field(): void
    {
        $markup = file_get_contents(resource_path('views/emission_records/index.blade.php'));

        $this->assertStringContainsString(
            'd.activity_unit',
            $markup,
            'The edit form does not repopulate the unit from activity_unit.'
        );
        $this->assertStringNotContainsString(
            'if (d.unit)',
            $markup,
            'The edit form still reads a `unit` field that does not exist.'
        );
    }

    /**
     * The record endpoint has to expose the unit, or the edit form has nothing
     * to repopulate from.
     */
    public function test_the_record_endpoint_exposes_the_unit(): void
    {
        $this->postJson(route('emission-records.store'), [
            'entryDate' => '2025-07-01',
            'facilitySelect' => 'Plant A',
            'scopeSelect' => 1,
            'emissionSourceSelect' => 'Diesel (Stationary)',
            'activityData' => 1000,
            'activityUnit' => 'liters',
            'emissionFactor' => 0.00268,
            'co2eValue' => 2.68,
            'confidenceLevel' => 'medium',
            'dataSource' => 'manual',
        ])->assertOk();

        $record = EmissionRecord::where('company_id', $this->company->id)->latest('id')->firstOrFail();

        // show() returns the model itself, so attributes sit at the root — which
        // is what the edit form's `d.activity_unit` reads.
        $this->getJson('/emission-records/'.$record->id)
            ->assertOk()
            ->assertJsonPath('activity_unit', 'liters');
    }

    /**
     * GHG-27 — the factor library column must hold the small end of the range,
     * or compiling the built-in catalogue into it (GHG-04) would zero out every
     * biogenic and low-carbon factor as it was written.
     */
    public function test_the_factor_library_can_store_the_smallest_catalogue_factor(): void
    {
        $column = DB::selectOne(
            "SELECT COLUMN_TYPE t FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'emission_factors'
               AND COLUMN_NAME = 'factor_value'"
        )->t;

        $this->assertSame('decimal(20,10)', $column);

        $factor = EmissionFactor::withoutGlobalScopes()->firstOrFail();
        $original = $factor->factor_value;

        // Biogas per kWh.
        $factor->update(['factor_value' => 1.9620e-7]);

        $this->assertGreaterThan(
            0,
            (float) $factor->fresh()->factor_value,
            'The smallest catalogue factor was rounded to zero on write.'
        );

        $factor->update(['factor_value' => $original]);
    }
}

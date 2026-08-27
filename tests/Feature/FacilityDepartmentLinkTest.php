<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\EmissionRecord;
use App\Models\Facilities;
use Tests\TenantTestCase;

/**
 * GHG-08 — emission records reference their facility and department by id,
 * not only by the name typed at the time.
 *
 * The harm the finding names is precise: renaming a facility orphaned every
 * historical record filed under the old name, with no migration and no
 * warning. Two spellings of one site became two sites, and nothing said so.
 *
 * The name columns remain, because reporting and analytics still group by
 * them. They are now a denormalised label kept in step with the row they
 * point at, rather than the only thing tying a record to a place.
 */
class FacilityDepartmentLinkTest extends TenantTestCase
{
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Link Test Co '.uniqid(),
            'industry_type' => 'manufacturing',
            'is_active' => true,
        ]);

        app()->instance('current_company_id', $this->company->id);
        app()->instance('current_company', $this->company);
    }

    private function facility(string $name): Facilities
    {
        return Facilities::create([
            'company_id' => $this->company->id,
            'name' => $name,
        ]);
    }

    private function department(string $name, Facilities $facility): Department
    {
        return Department::create([
            'company_id' => $this->company->id,
            'facility_id' => $facility->id,
            'name' => $name,
        ]);
    }

    private function record(array $overrides = []): EmissionRecord
    {
        return EmissionRecord::create(array_merge([
            'company_id' => $this->company->id,
            'entry_date' => '2025-07-01',
            'facility' => 'Plant A',
            'scope' => 1,
            'emission_source' => 'Diesel (Stationary)',
            'activity_data' => 100,
            'co2e_value' => 0.27,
            'confidence_level' => 'high',
            'status' => 'active',
        ], $overrides));
    }

    public function test_a_record_links_itself_to_the_facility_it_names(): void
    {
        $facility = $this->facility('Plant A');

        $record = $this->record();

        $this->assertSame($facility->id, $record->facility_id);
        $this->assertTrue($record->facilityRecord()->is($facility));
    }

    public function test_matching_ignores_case_and_surrounding_space(): void
    {
        $facility = $this->facility('Plant A');

        $this->assertSame($facility->id, $this->record(['facility' => '  plant a  '])->facility_id);
    }

    public function test_a_department_links_within_its_facility(): void
    {
        $facility = $this->facility('Plant A');
        $department = $this->department('Operations', $facility);

        $record = $this->record(['department' => 'Operations']);

        $this->assertSame($department->id, $record->department_id);
    }

    /**
     * The finding itself. Before this, the rename left every historical record
     * behind under the old spelling.
     */
    public function test_renaming_a_facility_carries_its_history_with_it(): void
    {
        $facility = $this->facility('Plant A');
        $record = $this->record();

        $facility->update(['name' => 'Plant A (North)']);

        $this->assertSame(
            'Plant A (North)',
            $record->fresh()->facility,
            'History filed under the old name must follow the rename.'
        );
    }

    public function test_renaming_a_department_carries_its_history_with_it(): void
    {
        $facility = $this->facility('Plant A');
        $department = $this->department('Operations', $facility);
        $record = $this->record(['department' => 'Operations']);

        $department->update(['name' => 'Site Operations']);

        $this->assertSame('Site Operations', $record->fresh()->department);
    }

    /**
     * A rename must only move records actually linked to that row. A record
     * that was never linked is not evidence of anything, and renaming its
     * label would be a guess dressed as a correction.
     */
    public function test_a_rename_leaves_unlinked_records_alone(): void
    {
        $facility = $this->facility('Plant A');
        $orphan = $this->record(['facility' => 'Plant Z']);

        $this->assertNull($orphan->facility_id);

        $facility->update(['name' => 'Plant A (North)']);

        $this->assertSame('Plant Z', $orphan->fresh()->facility);
    }

    public function test_a_rename_does_not_reach_another_companys_records(): void
    {
        $facility = $this->facility('Plant A');
        $mine = $this->record();

        $other = Company::create([
            'name' => 'Other Co '.uniqid(),
            'industry_type' => 'manufacturing',
            'is_active' => true,
        ]);

        $theirs = EmissionRecord::withoutGlobalScope('company')->create([
            'company_id' => $other->id,
            'entry_date' => '2025-07-01',
            'facility' => 'Plant A',
            'scope' => 1,
            'emission_source' => 'Diesel (Stationary)',
            'activity_data' => 100,
            'co2e_value' => 0.27,
            'confidence_level' => 'high',
            'status' => 'active',
        ]);

        $facility->update(['name' => 'Plant A (North)']);

        $this->assertSame('Plant A (North)', $mine->fresh()->facility);
        $this->assertSame(
            'Plant A',
            EmissionRecord::withoutGlobalScope('company')->find($theirs->id)->facility,
            "Another company's identically named facility must be untouched."
        );
    }

    public function test_an_unmatched_name_leaves_the_record_visibly_unlinked(): void
    {
        $record = $this->record(['facility' => 'A Site That Does Not Exist']);

        $this->assertNull($record->facility_id);
        $this->assertSame('A Site That Does Not Exist', $record->facility);
    }

    /**
     * An id passed deliberately is an instruction, not a guess to be
     * overwritten by whatever the name happens to match.
     */
    public function test_an_explicit_id_wins_over_the_name(): void
    {
        $this->facility('Plant A');
        $other = $this->facility('Plant B');

        $record = $this->record(['facility' => 'Plant A', 'facility_id' => $other->id]);

        $this->assertSame($other->id, $record->facility_id);
    }

    public function test_deleting_a_facility_keeps_the_emissions_recorded_against_it(): void
    {
        $facility = $this->facility('Plant A');
        $record = $this->record();

        $facility->delete();

        $survivor = EmissionRecord::withoutGlobalScope('company')->find($record->id);

        $this->assertNotNull($survivor, 'Losing a facility must never delete its emissions.');
        $this->assertNull($survivor->facility_id);
        $this->assertSame('Plant A', $survivor->facility, 'The name it was filed under is kept.');
    }
}

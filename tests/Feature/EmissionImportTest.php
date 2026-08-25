<?php

namespace Tests\Feature;

use App\Imports\EmissionsImport;
use App\Models\Company;
use App\Models\EmissionRecord;
use App\Models\ReportingPeriod;
use App\Models\User;
use App\Support\Gwp;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * The Excel import path, which previously had no test coverage at all.
 *
 * Two rules it has to honour, both of which it used to break:
 *
 *  1. A locked reporting period is final. EmissionRecordController refuses every
 *     write into one; the import did not, so a spreadsheet was a way around the
 *     lock — and with overwrite on, updateOrCreate could restate figures inside
 *     a signed-off inventory with nothing left to notice.
 *
 *  2. Imported rows get the same enrichment as hand-entered ones. The import ran
 *     EmissionFigureVerifier alone, so imported records carried a NULL
 *     gwp_version — a figure that states no GWP basis, which CSRD/ESRS E1 and
 *     CDP both require.
 */
class EmissionImportTest extends TestCase
{
    use DatabaseTransactions;

    private Company $company;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Import Test Co '.uniqid(),
            'industry_type' => 'manufacturing',
            'is_active' => true,
        ]);

        $this->user = User::create([
            'name' => 'Import Test User',
            'email' => 'import-test-'.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->user);
        app()->instance('current_company_id', $this->company->id);
        app()->instance('current_company', $this->company);
    }

    /**
     * The import reads columns by a caller-supplied mapping, so the mapping and
     * the row keys have to agree. Heading-row keys are already normalised.
     */
    private function mapping(): array
    {
        return [
            'facility' => 'facility',
            'department' => 'department',
            'entry_date' => 'entry_date',
            'scope' => 'scope',
            'activity_data' => 'activity_data',
            'emission_factor' => 'emission_factor',
            'co2e_value' => 'co2e_value',
            'emission_source' => 'emission_source',
        ];
    }

    private function row(string $date, array $overrides = []): array
    {
        return array_merge([
            'facility' => 'Plant A',
            'department' => 'Operations',
            'entry_date' => $date,
            'scope' => 1,
            'activity_data' => 1000,
            'emission_factor' => 0.00268,
            'co2e_value' => 2.68,
            'emission_source' => 'Diesel (Stationary)',
        ], $overrides);
    }

    private function lockYear(int $year): void
    {
        ReportingPeriod::create([
            'company_id' => $this->company->id,
            'year' => $year,
            'status' => 'locked',
        ]);
    }

    /**
     * Run a row through the importer the way Laravel Excel does, persisting
     * whatever model comes back.
     */
    private function import(EmissionsImport $import, array $rows): void
    {
        foreach ($rows as $row) {
            $model = $import->model($row);
            if ($model !== null) {
                $model->save();
            }
        }
    }

    public function test_a_row_in_an_open_year_is_imported(): void
    {
        $import = new EmissionsImport(false, $this->mapping());
        $this->import($import, [$this->row('2025-03-14')]);

        $this->assertSame(0, $import->getLockedSkippedCount());
        $this->assertDatabaseHas('emission_records', [
            'company_id' => $this->company->id,
            'entry_date' => '2025-03-14',
            'emission_source' => 'Diesel (Stationary)',
        ]);
    }

    public function test_a_row_in_a_locked_year_is_rejected(): void
    {
        $this->lockYear(2024);

        $import = new EmissionsImport(false, $this->mapping());
        $this->import($import, [$this->row('2024-06-01')]);

        $this->assertSame(1, $import->getLockedSkippedCount());
        $this->assertSame([2024], $import->getLockedYears());
        $this->assertDatabaseMissing('emission_records', [
            'company_id' => $this->company->id,
            'entry_date' => '2024-06-01',
        ]);
    }

    /**
     * The lock must not turn one bad row into a failed file: rows in open years
     * still land, and only the locked ones are held back.
     */
    public function test_a_locked_row_does_not_discard_the_open_rows_beside_it(): void
    {
        $this->lockYear(2024);

        $import = new EmissionsImport(false, $this->mapping());
        $this->import($import, [
            $this->row('2024-06-01'),
            $this->row('2025-06-01'),
            $this->row('2024-09-30'),
        ]);

        $this->assertSame(2, $import->getLockedSkippedCount());
        $this->assertSame(3, $import->getProcessedCount());
        $this->assertDatabaseHas('emission_records', [
            'company_id' => $this->company->id,
            'entry_date' => '2025-06-01',
        ]);
        $this->assertSame(
            0,
            EmissionRecord::where('company_id', $this->company->id)
                ->whereYear('entry_date', 2024)
                ->count()
        );
    }

    /**
     * The dangerous case: overwrite mode uses updateOrCreate, so without the
     * guard a spreadsheet could restate an existing figure inside a locked year.
     */
    public function test_overwrite_cannot_restate_a_record_in_a_locked_year(): void
    {
        $existing = EmissionRecord::create([
            'company_id' => $this->company->id,
            'entry_date' => '2024-06-01',
            'facility' => 'Plant A',
            'department' => 'Operations',
            'scope' => 1,
            'emission_source' => 'Diesel (Stationary)',
            'activity_data' => 1000,
            'emission_factor' => 0.00268,
            'co2e_value' => 2.68,
            'confidence_level' => 'medium',
            'data_source' => 'manual',
            'status' => 'active',
        ]);

        $this->lockYear(2024);

        $import = new EmissionsImport(true, $this->mapping());
        $this->import($import, [
            $this->row('2024-06-01', ['activity_data' => 9999, 'co2e_value' => 26.8]),
        ]);

        $this->assertSame(1, $import->getLockedSkippedCount());
        $this->assertSame(2.68, round((float) $existing->fresh()->co2e_value, 2));
    }

    /**
     * Locking one year must not block every other year.
     */
    public function test_only_the_locked_year_is_blocked(): void
    {
        $this->lockYear(2024);

        $import = new EmissionsImport(false, $this->mapping());
        $this->import($import, [$this->row('2023-01-15'), $this->row('2025-01-15')]);

        $this->assertSame(0, $import->getLockedSkippedCount());
        $this->assertSame(
            2,
            EmissionRecord::where('company_id', $this->company->id)
                ->whereIn('entry_date', ['2023-01-15', '2025-01-15'])
                ->count()
        );
    }

    /**
     * A year locked for another tenant says nothing about this one.
     */
    public function test_another_companys_lock_does_not_block_this_import(): void
    {
        $other = Company::create([
            'name' => 'Other Co '.uniqid(),
            'industry_type' => 'manufacturing',
            'is_active' => true,
        ]);

        ReportingPeriod::create([
            'company_id' => $other->id,
            'year' => 2024,
            'status' => 'locked',
        ]);

        $import = new EmissionsImport(false, $this->mapping());
        $this->import($import, [$this->row('2024-06-01')]);

        $this->assertSame(0, $import->getLockedSkippedCount());
        $this->assertDatabaseHas('emission_records', [
            'company_id' => $this->company->id,
            'entry_date' => '2024-06-01',
        ]);
    }

    /**
     * A rejected row must not leave a facility or department behind — the
     * resolvers firstOrCreate(), so ordering matters.
     */
    public function test_a_rejected_row_creates_no_facility_or_department(): void
    {
        $this->lockYear(2024);

        $import = new EmissionsImport(false, $this->mapping());
        $this->import($import, [
            $this->row('2024-06-01', ['facility' => 'Ghost Plant', 'department' => 'Ghost Dept']),
        ]);

        $this->assertDatabaseMissing('facilities', ['name' => 'Ghost Plant']);
        $this->assertDatabaseMissing('departments', ['name' => 'Ghost Dept']);
    }

    /**
     * GHG-02: imported rows now get the full enrichment, not just verification.
     */
    public function test_imported_rows_are_stamped_with_a_gwp_basis(): void
    {
        $import = new EmissionsImport(false, $this->mapping());
        $this->import($import, [$this->row('2025-04-02')]);

        $record = EmissionRecord::where('company_id', $this->company->id)
            ->where('entry_date', '2025-04-02')
            ->firstOrFail();

        $this->assertNotNull($record->gwp_version, 'Imported record carries no GWP basis.');
        $this->assertSame(Gwp::factorBasis(), $record->gwp_version);
    }

    /**
     * Enrichment runs the verifier as its first step, so the spreadsheet's own
     * co2e column is still not taken on trust.
     */
    public function test_a_mismatched_co2e_column_is_corrected_and_held_for_review(): void
    {
        $import = new EmissionsImport(false, $this->mapping());
        $this->import($import, [
            // 1000 x 0.00268 = 2.68, not 99.
            $this->row('2025-05-20', ['co2e_value' => 99]),
        ]);

        $record = EmissionRecord::where('company_id', $this->company->id)
            ->where('entry_date', '2025-05-20')
            ->firstOrFail();

        $this->assertSame(2.68, round((float) $record->co2e_value, 2));
        $this->assertSame('draft', $record->status);
    }
}

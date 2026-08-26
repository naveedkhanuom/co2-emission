<?php

namespace Tests\Feature;

use App\Imports\EmissionsImport;
use App\Models\Company;
use App\Models\EmissionRecord;
use App\Models\ReportingPeriod;
use App\Models\User;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TenantTestCase;

/**
 * GHG-12 — the import reads in chunks rather than materialising the whole file.
 *
 * Unlike the other import tests, this one goes through Laravel Excel itself with
 * a real file on disk, so it covers the reader wiring and not just model().
 * That path had no coverage at all.
 */
class EmissionImportChunkingTest extends TenantTestCase
{
    private Company $company;

    private string $csvPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Chunk Test Co '.uniqid(),
            'industry_type' => 'manufacturing',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Chunk Test User',
            'email' => 'chunk-test-'.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($user);
        app()->instance('current_company_id', $this->company->id);
        app()->instance('current_company', $this->company);

        $this->csvPath = storage_path('app/chunk-test-'.uniqid().'.csv');
    }

    protected function tearDown(): void
    {
        if (isset($this->csvPath) && file_exists($this->csvPath)) {
            unlink($this->csvPath);
        }

        parent::tearDown();
    }

    private function writeCsv(int $rows, string $date = '2025-05-01'): void
    {
        $handle = fopen($this->csvPath, 'w');
        fputcsv($handle, ['facility', 'department', 'entry_date', 'scope', 'activity_data', 'emission_factor', 'co2e_value', 'emission_source']);

        for ($i = 0; $i < $rows; $i++) {
            fputcsv($handle, ['Plant A', 'Operations', $date, 1, 1000, 0.00268, 2.68, 'Diesel (Stationary)']);
        }

        fclose($handle);
    }

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

    private function recordCount(): int
    {
        return EmissionRecord::where('company_id', $this->company->id)->count();
    }

    public function test_the_importer_declares_chunked_reading(): void
    {
        $import = new EmissionsImport(false, $this->mapping());

        $this->assertInstanceOf(WithChunkReading::class, $import);
        $this->assertGreaterThan(0, $import->chunkSize());
    }

    /**
     * The important property: a file spanning several chunks imports completely,
     * and the counters accumulate across chunk boundaries rather than resetting.
     */
    public function test_a_file_larger_than_one_chunk_imports_completely(): void
    {
        $import = new EmissionsImport(false, $this->mapping());
        $rows = $import->chunkSize() * 2 + 25;

        $this->writeCsv($rows);
        $before = $this->recordCount();

        Excel::import($import, $this->csvPath);

        $this->assertSame($rows, $import->getProcessedCount(), 'Counters reset across chunks.');
        $this->assertSame(0, $import->getSkippedCount());
        $this->assertSame($rows, $this->recordCount() - $before);
    }

    /**
     * The lock guard has to hold across chunk boundaries too — the year cache is
     * per-instance, and chunks share the instance.
     */
    public function test_the_period_lock_still_applies_across_chunks(): void
    {
        ReportingPeriod::create([
            'company_id' => $this->company->id,
            'year' => 2024,
            'status' => 'locked',
        ]);

        $import = new EmissionsImport(false, $this->mapping());
        $rows = $import->chunkSize() + 10;

        $this->writeCsv($rows, '2024-05-01');
        $before = $this->recordCount();

        Excel::import($import, $this->csvPath);

        $this->assertSame($rows, $import->getLockedSkippedCount());
        $this->assertSame([2024], $import->getLockedYears());
        $this->assertSame($before, $this->recordCount());
    }
}

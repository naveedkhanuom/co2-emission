<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\EmissionRecord;
use App\Models\ReportingPeriod;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;
use Tests\TenantTestCase;

/**
 * Manual Entry's bulk path wrote into locked reporting periods.
 *
 * EmissionRecordController::store() has two branches. The bulk branch —
 * `if ($request->has('entries'))` — loops EmissionRecord::create() and returns
 * before ever reaching the assertPeriodOpen() call that guards the single-entry
 * path below it.
 *
 * That made it worse than the import hole this platform already closed: bulk
 * rows are written with the status from the request, which defaults to
 * 'active', so they land straight in the reported inventory without passing
 * through the review queue that would otherwise have caught them. A locked year
 * is a signed-off inventory; silently restating it is the failure this feature
 * exists to prevent.
 */
class BulkEntryPeriodLockTest extends TenantTestCase
{
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Bulk Lock Co '.uniqid(),
            'industry_type' => 'manufacturing',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Bulk Lock User',
            'email' => 'bulk-lock-'.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->givePermissionTo(['create-emission-record', 'list-emission-records']);

        $this->actingAs($user);
        app()->instance('current_company_id', $this->company->id);
        app()->instance('current_company', $this->company);
    }

    private function lockYear(int $year): void
    {
        ReportingPeriod::create([
            'company_id' => $this->company->id,
            'year' => $year,
            'status' => 'locked',
            'locked_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function entry(string $date): array
    {
        return [
            'entryDate' => $date,
            'facilitySelect' => 'Plant A',
            'scopeSelect' => 1,
            'emissionSourceSelect' => 'Diesel Combustion',
            'co2eValue' => 12.5,
            'confidenceLevel' => 'high',
            'dataSource' => 'manual',
        ];
    }

    private function recordCount(): int
    {
        return EmissionRecord::where('company_id', $this->company->id)->count();
    }

    public function test_bulk_entry_cannot_write_into_a_locked_period(): void
    {
        $this->lockYear(2024);
        $before = $this->recordCount();

        $response = $this->postJson(route('emission-records.store'), [
            'entries' => [$this->entry('2024-06-01')],
        ]);

        $response->assertStatus(422);
        $this->assertSame($before, $this->recordCount(), 'A locked year was written to through the bulk path.');
    }

    /**
     * The loop must be guarded per row, not once for the batch. A batch whose
     * first rows are fine and whose later row falls in a locked year is the
     * case a single up-front check would wave through.
     */
    public function test_a_locked_row_later_in_the_batch_is_still_caught(): void
    {
        $this->lockYear(2024);
        $before = $this->recordCount();

        $response = $this->postJson(route('emission-records.store'), [
            'entries' => [
                $this->entry('2025-06-01'),
                $this->entry('2024-06-01'),
            ],
        ]);

        $response->assertStatus(422);
        $this->assertSame(
            $before,
            $this->recordCount(),
            'The batch was rejected but rows before the locked one were still committed.'
        );
    }

    public function test_bulk_entry_still_writes_to_an_open_period(): void
    {
        $this->lockYear(2024);
        $before = $this->recordCount();

        $response = $this->postJson(route('emission-records.store'), [
            'entries' => [$this->entry('2025-06-01')],
        ]);

        $response->assertOk();
        $this->assertSame($before + 1, $this->recordCount());
    }

    /**
     * The single-entry path was already guarded. Asserted here so that a future
     * refactor moving the check cannot close one door while opening the other.
     */
    public function test_single_entry_cannot_write_into_a_locked_period(): void
    {
        $this->lockYear(2024);
        $before = $this->recordCount();

        $response = $this->postJson(route('emission-records.store'), $this->entry('2024-06-01'));

        $response->assertStatus(422);
        $this->assertSame($before, $this->recordCount());
    }
}

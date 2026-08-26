<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ImportHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TenantTestCase;

/**
 * GHG-11 — allocating import references.
 *
 * import_id carries a UNIQUE index across all tenants. The old implementation
 * read the most recent row and incremented its number, which had two failure
 * modes: a malformed value read as 0 restarted the sequence at 1 forever, and
 * two concurrent imports both computed the same next number so the second
 * insert failed with a 500 on a valid upload.
 */
class ImportIdAllocationTest extends TenantTestCase
{
    private Company $company;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Import Id Co '.uniqid(),
            'industry_type' => 'manufacturing',
            'is_active' => true,
        ]);

        $this->user = User::create([
            'name' => 'Import Id User',
            'email' => 'import-id-'.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->user);
        app()->instance('current_company_id', $this->company->id);
        app()->instance('current_company', $this->company);
    }

    private function attributes(): array
    {
        return [
            'company_id' => $this->company->id,
            'file_name' => 'test.xlsx',
            'file_size' => 1024,
            'import_type' => 'excel',
            'status' => 'processing',
            'user_id' => $this->user->id,
            'started_at' => now(),
        ];
    }

    private function highestNumber(): int
    {
        return (int) DB::table('import_history')
            ->whereRaw("import_id REGEXP '^IMP-[0-9]+$'")
            ->selectRaw('MAX(CAST(SUBSTRING(import_id, 5) AS UNSIGNED)) AS n')
            ->value('n');
    }

    public function test_it_allocates_the_next_number_after_the_highest_in_use(): void
    {
        $expected = $this->highestNumber() + 1;

        $record = ImportHistory::startNew($this->attributes());

        $this->assertSame('IMP-'.str_pad((string) $expected, 4, '0', STR_PAD_LEFT), $record->import_id);
    }

    public function test_consecutive_imports_get_consecutive_references(): void
    {
        $first = ImportHistory::startNew($this->attributes());
        $second = ImportHistory::startNew($this->attributes());

        $this->assertSame(
            1,
            (int) substr($second->import_id, 4) - (int) substr($first->import_id, 4)
        );
    }

    /**
     * The old implementation took the LATEST row's number. A newer row with a
     * lower number (or a malformed one) therefore rewound the sequence and
     * collided with an existing reference on every subsequent import.
     */
    public function test_a_malformed_reference_does_not_rewind_the_sequence(): void
    {
        $highest = $this->highestNumber();

        // Insert a malformed row AFTER the well-formed ones, so it is "latest".
        DB::table('import_history')->insert($this->attributes() + [
            'import_id' => 'IMP-BROKEN',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = ImportHistory::startNew($this->attributes());

        $this->assertSame(
            'IMP-'.str_pad((string) ($highest + 1), 4, '0', STR_PAD_LEFT),
            $record->import_id,
            'A malformed reference restarted the sequence.'
        );
    }

    /**
     * Simulates losing the race: the reference generate() is about to return is
     * already taken. The allocation must step past it rather than failing.
     */
    public function test_it_steps_past_a_reference_taken_by_a_concurrent_import(): void
    {
        $contested = ImportHistory::generateImportId();

        // Another import gets there first.
        DB::table('import_history')->insert($this->attributes() + [
            'import_id' => $contested,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = ImportHistory::startNew($this->attributes());

        $this->assertNotSame($contested, $record->import_id);
        $this->assertSame(
            (int) substr($contested, 4) + 1,
            (int) substr($record->import_id, 4)
        );
    }

    /**
     * References stay unique across tenants — the index is global, so the
     * generator must not be company-scoped.
     */
    public function test_references_are_unique_across_companies(): void
    {
        $other = Company::create([
            'name' => 'Other Import Co '.uniqid(),
            'industry_type' => 'manufacturing',
            'is_active' => true,
        ]);

        $mine = ImportHistory::startNew($this->attributes());
        $theirs = ImportHistory::startNew(array_merge($this->attributes(), ['company_id' => $other->id]));

        $this->assertNotSame($mine->import_id, $theirs->import_id);
    }

    /**
     * A failure that is not a duplicate key must surface, not be retried away.
     */
    public function test_an_unrelated_failure_is_not_swallowed(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        // status is an enum; this value is not a member.
        ImportHistory::startNew(array_merge($this->attributes(), ['status' => 'not-a-valid-status']));
    }
}

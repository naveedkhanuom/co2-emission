<?php

namespace Tests\Feature;

use App\Models\EmissionFactor;
use App\Models\FactorImport;
use App\Services\Factors\Import\DefraFlatFileImporter;
use Tests\TenantTestCase;

/**
 * Importing the DESNZ/DEFRA published factor set.
 *
 * The value of this pipeline is that a stored factor is the publisher's number,
 * traceable to the exact file it came from — so these tests are mostly about the
 * ways that claim could quietly become false:
 *
 *  - two DEFRA activities collapsing onto one emission source, storing one
 *    factor where the publisher gave several
 *  - a blank in the source file becoming a zero in the database
 *  - the gas split not adding up to the total it was taken from
 *  - the link back to the import run being silently dropped
 *
 * They run against the real committed source file rather than a fixture. A
 * fixture would test the parser against my understanding of the format; this
 * tests it against DESNZ's actual workbook, which is the thing that can change
 * under us.
 */
class DefraFactorImportTest extends TenantTestCase
{
    private function sourcePath(): string
    {
        $path = database_path('factors/sources/defra-2026-flat.xlsx');

        if (! is_file($path)) {
            $this->markTestSkipped('DEFRA source file not present — see database/factors/sources/MANIFEST.md');
        }

        return $path;
    }

    private function importer(): DefraFlatFileImporter
    {
        return app(DefraFlatFileImporter::class);
    }

    /**
     * The file must be the one the manifest describes. If DESNZ revises a file in
     * place, or a byte changes in transit, every factor traced to this hash is
     * making a claim that is no longer checkable.
     */
    public function test_the_committed_source_file_matches_its_recorded_hash(): void
    {
        $this->assertSame(
            'a9a455ab396dae226d510c7be6233748416d490c41a5d20f3dc7a0c45feecd5e',
            hash_file('sha256', $this->sourcePath()),
            'The DEFRA source file is not the one recorded in MANIFEST.md.'
        );
    }

    public function test_it_parses_every_activity_unit_pair_in_the_file(): void
    {
        $groups = $this->importer()->parse($this->sourcePath());

        // 3,997 is the count of distinct (scope, full category path, unit)
        // combinations in the 2026 file. A DROP means rows are being merged —
        // which is how 498 factors were silently lost when the grouping key used
        // the composed display name instead of DEFRA's own category path.
        $this->assertSame(3997, count($groups));
    }

    /**
     * Names are composed from category levels, so a future edition renaming a
     * level could reintroduce the collision. parse() refuses rather than merging.
     */
    public function test_no_two_activities_share_a_source_name_and_unit(): void
    {
        $groups = $this->importer()->parse($this->sourcePath());

        $keys = array_map(
            static fn ($g) => mb_strtolower($g['name'].'|'.$g['unit']),
            $groups
        );

        $this->assertSame(
            count($keys),
            count(array_unique($keys)),
            'Two DEFRA activities compose to the same emission source and unit, '
            .'so one would overwrite the other.'
        );
    }

    /**
     * DEFRA leaves a factor blank where it publishes none — "Aggregates >
     * Re-use", for instance. Blank is not zero, and writing zero would state an
     * emission figure the publisher declined to give.
     */
    public function test_rows_with_no_published_total_are_skipped_not_zeroed(): void
    {
        $result = $this->importer()->import($this->sourcePath(), '2026', pretend: true);

        $this->assertGreaterThan(0, $result['skipped']);
        $this->assertSame($result['parsed'], $result['written'] + $result['skipped']);
    }

    public function test_a_pretend_run_writes_nothing(): void
    {
        $before = EmissionFactor::count();
        $imports = FactorImport::count();

        $this->importer()->import($this->sourcePath(), '2026', pretend: true);

        $this->assertSame($before, EmissionFactor::count());
        $this->assertSame($imports, FactorImport::count());
    }

    /**
     * The whole point of the factor_imports table: a factor must lead back to
     * the file it came from. factor_import_id was missing from EmissionFactor's
     * $fillable at first, so mass assignment dropped it — the rows saved
     * happily, and their provenance was simply gone.
     */
    public function test_every_imported_factor_links_to_its_import_run(): void
    {
        $result = $this->importer()->import($this->sourcePath(), '2026');
        $import = $result['import'];

        $this->assertNotNull($import);
        $this->assertSame(
            $result['written'],
            EmissionFactor::where('factor_import_id', $import->id)->count(),
            'Imported factors are not linked to their import run.'
        );

        $this->assertSame(hash_file('sha256', $this->sourcePath()), $import->source_file_hash);
        $this->assertSame('DEFRA/DESNZ', $import->dataset_name);
        $this->assertSame('ar5', $import->gwp_version);
    }

    /**
     * DEFRA's per-gas figures are already CO2e-weighted, so they should sum to
     * the published total. If someone ever re-weights them by a GWP the methane
     * column jumps ~28x and this catches it.
     *
     * Tolerance is 1%: DESNZ rounds its published components, and three rows in
     * the 2026 set are off by up to 0.31% in the source file itself.
     */
    public function test_the_gas_split_sums_to_the_published_total(): void
    {
        $this->importer()->import($this->sourcePath(), '2026');

        $rows = EmissionFactor::whereNotNull('co2_factor')
            ->where('dataset_name', 'DEFRA/DESNZ')
            ->where('factor_value', '>', 0)
            ->get();

        $this->assertGreaterThan(1000, $rows->count(), 'Too few rows with a gas breakdown to be meaningful.');

        $offenders = $rows->filter(function ($row) {
            $sum = (float) $row->co2_factor + (float) $row->ch4_factor + (float) $row->n2o_factor;

            return abs($sum - (float) $row->factor_value) / (float) $row->factor_value > 0.01;
        });

        $this->assertCount(
            0,
            $offenders,
            'Gas components do not sum to the published total for: '
            .$offenders->take(3)->pluck('source_reference')->implode('; ')
        );
    }

    /**
     * Re-importing must not double the library, and must leave the previous
     * edition's rows readable — a figure computed against them still points at
     * them.
     */
    public function test_reimporting_supersedes_rather_than_duplicating(): void
    {
        $this->importer()->import($this->sourcePath(), '2026');
        $activeAfterFirst = EmissionFactor::where('dataset_name', 'DEFRA/DESNZ')->where('is_active', true)->count();

        $second = $this->importer()->import($this->sourcePath(), '2026');

        $this->assertSame(
            $activeAfterFirst,
            EmissionFactor::where('dataset_name', 'DEFRA/DESNZ')->where('is_active', true)->count(),
            'A second import left more than one active row per activity.'
        );

        $this->assertGreaterThan(0, $second['superseded'], 'Nothing was superseded on re-import.');

        $superseded = EmissionFactor::where('dataset_name', 'DEFRA/DESNZ')->where('is_active', false)->first();
        $this->assertNotNull($superseded, 'The previous rows were removed rather than retired.');
        $this->assertNotNull($superseded->valid_to, 'A superseded row does not record when it stopped applying.');
    }

    /**
     * Importing DEFRA must not disturb another publisher's rows for the same
     * activity. Two publishers disagreeing is the reason for holding both.
     */
    public function test_it_leaves_other_publishers_factors_alone(): void
    {
        $others = EmissionFactor::where('dataset_name', '!=', 'DEFRA/DESNZ')
            ->orWhereNull('dataset_name');

        $before = $others->count();
        $activeBefore = (clone $others)->where('is_active', true)->count();

        $this->importer()->import($this->sourcePath(), '2026');

        $this->assertSame($before, $others->count(), 'Importing DEFRA changed another publisher\'s row count.');
        $this->assertSame(
            $activeBefore,
            (clone $others)->where('is_active', true)->count(),
            'Importing DEFRA retired another publisher\'s factors.'
        );
    }
}

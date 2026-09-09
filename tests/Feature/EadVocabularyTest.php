<?php

namespace Tests\Feature;

use App\Services\MRV\EadWorkbookFiller;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

/**
 * The controlled vocabularies in config/mrv.php, checked against the workbook
 * they were transcribed from.
 *
 * WHY THIS EXISTS
 *
 * Several cells in the template are dropdowns bound to a fixed list. A value
 * that is not on the list is one EAD's validation rejects, so writing one
 * produces a submission that fails on receipt rather than on export — the
 * failure mode assertCellMapFits() guards against one level up, where the cell
 * is right and the value is not.
 *
 * config/mrv.php was built by transcribing sheet 4k. That is why the primary
 * SECTOR list was missed for as long as it was: its options live inline in
 * K10's own data validation and never appear on 4k, so a transcription pass
 * could not have found it. The sector shipped as free text as a result.
 *
 * A test comparing config against a list typed into the test would not have
 * caught that, and would not catch the next one: it confirms we have not
 * changed OUR copy, not that our copy still matches EAD's. So this reads the
 * lists out of the workbook itself.
 *
 * Skips when the template is not installed — it cannot be committed (see
 * config/mrv.php) — which means CI proves nothing here and a machine with the
 * workbook proves everything. Run it before accepting a new template version.
 */
class EadVocabularyTest extends TestCase
{
    private ?Spreadsheet $book = null;

    protected function setUp(): void
    {
        parent::setUp();

        config(['mrv.ead_template' => null]);
        $path = EadWorkbookFiller::templatePath();

        if (! is_file($path)) {
            $this->markTestSkipped('EAD template not installed at '.$path);
        }

        $this->book = IOFactory::createReaderForFile($path)->load($path);
    }

    /**
     * Resolve a cell's dropdown to the values it actually accepts.
     *
     * Two forms appear in this workbook: an inline quoted list, and a reference
     * into sheet 4k. Both are resolved here so a list that MOVES from one form
     * to the other still compares.
     *
     * @return array<int, string>
     */
    private function allowedValues(string $sheet, string $coordinate): array
    {
        $formula = $this->book->getSheetByName($sheet)?->getCell($coordinate)->getDataValidation()->getFormula1();

        $this->assertNotEmpty(
            $formula,
            "{$sheet}!{$coordinate} has no data validation — either the template moved it, or it is no longer a dropdown."
        );

        // Inline: "Energy, Transport, …"
        if (str_starts_with($formula, '"')) {
            return array_values(array_filter(array_map(
                'trim',
                explode(',', trim($formula, '"'))
            )));
        }

        // A reference: '4k - Reference Lists'!$B$3:$B$10
        preg_match("/^'?([^'!]+)'?!\\\$?([A-Z]+)\\\$?(\d+):\\\$?[A-Z]+\\\$?(\d+)$/", $formula, $m);
        $this->assertNotEmpty($m, "Could not read the list reference: {$formula}");

        [, $listSheet, $column, $from, $to] = $m;
        $ws = $this->book->getSheetByName($listSheet);
        $this->assertNotNull($ws, "The list sheet \"{$listSheet}\" is missing.");

        $values = [];
        for ($row = (int) $from; $row <= (int) $to; $row++) {
            $value = trim((string) $ws->getCell($column.$row)->getValue());
            if ($value !== '') {
                $values[] = $value;
            }
        }

        return $values;
    }

    /**
     * The one that was missed. K10's options are inline in the cell, not on
     * sheet 4k, so the transcription that produced every other list here never
     * saw them.
     */
    public function test_the_primary_sector_list_matches_the_workbooks_own(): void
    {
        $this->assertSame(
            $this->allowedValues('2c2_Facility Description', 'K10'),
            config('mrv.primary_sectors')
        );
    }

    public function test_the_primary_activity_list_matches_the_workbooks_own(): void
    {
        $this->assertSame(
            $this->allowedValues('2c2_Facility Description', 'K12'),
            config('mrv.primary_activities')
        );
    }

    public function test_the_product_benchmark_list_matches_the_workbooks_own(): void
    {
        $this->assertSame(
            $this->allowedValues('2c2_Facility Description', 'D17'),
            config('mrv.product_benchmarks')
        );
    }

    /**
     * These two are stored as key => label, and it is the LABEL the workbook
     * has to accept — the key is ours.
     */
    public function test_the_methodology_labels_match_the_workbooks_own(): void
    {
        $this->assertSame(
            $this->allowedValues('2c2_Facility Description', 'J43'),
            array_values(config('mrv.methodologies'))
        );
    }

    public function test_the_source_stream_type_labels_match_the_workbooks_own(): void
    {
        $this->assertSame(
            $this->allowedValues('2c2_Facility Description', 'F75'),
            array_values(config('mrv.stream_types'))
        );
    }

    /**
     * Four cells reference IDs defined elsewhere in the submission rather than
     * a fixed vocabulary. They are not config lists, but the ranges they point
     * at pin the table sizes the filler writes — if EAD widens a table, this
     * says so before a row silently lands outside it.
     */
    public function test_the_id_dropdowns_still_point_at_the_tables_we_fill(): void
    {
        $expected = [
            ['2c2_Facility Description', 'E43', '$C$17:$C$26'],       // source → product
            ['2c2_Facility Description', 'E75', '$C$43:$C$67'],       // stream → emission source
            ['3d2_ Calculation Approaches', 'C93', "'2c2_Facility Description'!\$C\$75:\$C\$99"],
            ['3e2_MeasurementBasedApproaches', 'C23', "'2c2_Facility Description'!\$C\$43:\$C\$67"],
        ];

        foreach ($expected as [$sheet, $coordinate, $reference]) {
            $this->assertSame(
                $reference,
                $this->book->getSheetByName($sheet)?->getCell($coordinate)->getDataValidation()->getFormula1(),
                "{$sheet}!{$coordinate} no longer points where the export assumes."
            );
        }
    }
}

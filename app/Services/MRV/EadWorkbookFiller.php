<?php

namespace App\Services\MRV;

use App\Models\Facilities;
use App\Models\MrvEmissionSource;
use App\Models\MrvFacilityReport;
use App\Models\MrvMeasuringInstrument;
use App\Models\MrvSourceStream;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use RuntimeException;

/**
 * Fills the official EAD "Deliverable C" MRV workbook from stored MRV data, so the
 * download is a true EAD submission with EAD's own formatting, dropdowns and
 * inter-sheet formulas preserved.
 *
 * Strategy: load the template as a base and write values into its exact cells. The
 * cell map below was derived from the template's structure (header rows + sequential
 * data rows and merge ranges). Cells that the template computes itself (e.g. the
 * 3d2 activity-data lookups that pull from 2c2) are intentionally NOT written.
 *
 * Scope (phase 1): the quantitative core — 2c1 Identifiers, 2c2 Facility Description
 * (sector/activity, emission sources, source streams), and 3d2 Calculation inputs.
 * The narrative/governance sheets (3g, 4h, 4i, 4j) are left as the template's blank
 * inputs for the user to complete; writing them wrong would be worse than leaving
 * them empty in a regulatory document.
 *
 * If EAD ships a new template version with shifted rows, only the constants here and
 * the resolved template path need updating.
 */
class EadWorkbookFiller
{
    /**
     * Where the template lives when nothing overrides it — the value behind
     * config('mrv.ead_template'), repeated here only so the "not installed"
     * error can name it. Resolve through templatePath(), never this.
     */
    public const DEFAULT_TEMPLATE_PATH = 'storage/app/templates/ead_deliverable_c.xlsx';

    private const SHEET_IDENTIFIERS = '2c1_ Identifiers';

    private const SHEET_FACILITY = '2c2_Facility Description';

    private const SHEET_CALC = '3d2_ Calculation Approaches';

    private const SHEET_STREAM_TIERS = '3d1_Source Streams (Calculated)';

    /**
     * Labels that pin this cell map to the template it was written for (v8.1).
     *
     * Every write below is a hardcoded coordinate. If EAD issues a version that
     * inserts a row, those coordinates still resolve — they just land somewhere
     * else, and the result is a regulatory submission whose numbers sit under
     * the wrong headings. Nothing about the file would look wrong.
     *
     * So before writing anything, check that a handful of known labels are
     * still where the map expects them. Compared case-insensitively and with
     * whitespace collapsed, because several of these carry trailing spaces in
     * the template and a re-save could normalise them — that is cosmetic drift,
     * not a moved row.
     *
     * @var array<int, array{0: string, 1: string, 2: string}>
     */
    private const CELL_MAP_ANCHORS = [
        [self::SHEET_IDENTIFIERS, 'D5', 'Entity/Company name'],
        [self::SHEET_IDENTIFIERS, 'D7', 'Facility Name (as stated in the Environmental Permit)'],
        [self::SHEET_FACILITY, 'C42', 'Emission source ID'],
        [self::SHEET_FACILITY, 'C74', 'Source Stream ID'],
        [self::SHEET_STREAM_TIERS, 'B9', 'Source stream ID'],
        [self::SHEET_STREAM_TIERS, 'C40', 'Tier level used'],
        [self::SHEET_CALC, 'B59', 'Source Stream ID'],
        [self::SHEET_FALLBACK, 'B8', 'Please provide a concise description of the monitoring approach, including formulae, used to determine your annual CO2 or CO2(e) emissions in the text box below.'],
        [self::SHEET_MEASURED_SOURCES, 'B8', 'Emission source ID'],
        [self::SHEET_MEASURED_SOURCES, 'C39', 'Tier level used'],
        [self::SHEET_MEASUREMENT, 'B22', 'Measurement point ID'],
    ];

    // 3d1 (a) — materiality table, rows 10..34. Row-aligned 1:1 with the 2c2
    // source-stream table (75..99): the template's own column B is
    // ='2c2_Facility Description'!C75 and so on down. Writing these out of step
    // with 2c2 would label each stream's emissions with its neighbour's id.
    private const TIER_CLASS_FIRST_ROW = 10;

    // 3d1 (b) — tier/uncertainty table, rows 41..65: the same streams again,
    // 31 rows below (its column B is =B10 …), so one offset keeps all three
    // tables in step.
    private const TIER_TABLE_OFFSET = 31;

    // 3d2 — decomposed calculation inputs, rows 60..84.
    private const CALC_FIRST_ROW = 60;

    private const CALC_LAST_ROW = 84;

    private const SHEET_MEASURED_SOURCES = '3e1_Emission Sources (Measured)';

    private const SHEET_MEASUREMENT = '3e2_MeasurementBasedApproaches';

    /*
     * 3e1 (a) — measured sources, rows 9..33. Row-aligned 1:1 with the 2c2
     * emission-source table (43..67), because column C is
     * ='2c2_Facility Description'!G43 downward. The sheet cannot therefore be a
     * FILTERED list of measured sources: row 9 always reports source #1's
     * emissions whatever is written beside it, so the ordering has to mirror
     * 2c2 exactly and the measurement columns are filled only where they apply.
     */
    private const MEASURED_FIRST_ROW = 9;

    // 3e1 (b) — the same sources again, 31 rows below (its column B is =B9 …).
    private const MEASURED_TIER_OFFSET = 31;

    // 3e2 — the two narratives, then the measurement-point table, then comments.
    private const MEASUREMENT_APPROACH_CELL = 'B7';

    private const MEASUREMENT_DERIVATION_CELL = 'B9';

    private const MEASUREMENT_COMMENTS_CELL = 'B41';

    private const MEASUREMENT_POINT_FIRST_ROW = 23;

    private const MEASUREMENT_POINT_LAST_ROW = 38;

    private const SHEET_FALLBACK = '3f_Fallback Approach';

    // 3f — two merged input blocks: (a) the methodology at B9:K17, (b) the
    // justification at B21:K29. Each is written at the cell its block starts at.
    private const FALLBACK_DESCRIPTION_CELL = 'B9';

    private const FALLBACK_JUSTIFICATION_CELL = 'B21';

    private const SHEET_METHANE = '3g_Methane';

    private const SHEET_VERIFICATION = '4h_Verification and Data Gaps';

    private const SHEET_MANAGEMENT = '4I - Management & QA';

    private const SHEET_MITIGATION = '4J - Mitigation Measures';

    // 2c2 (b) — products P01..P10, rows 17..26.
    private const PRODUCT_FIRST_ROW = 17;

    private const PRODUCT_LAST_ROW = 26;

    /*
     * On these four sheets the labels sit in column B or C and the operator's
     * answer goes in a merged block to their right, so the map is a stored key
     * => the single cell that block starts at.
     */

    // 3g — every answer is a merged E:L block on the label's own row.
    private const METHANE_CELLS = [
        'annual_volume' => 'E9',
        'estimated_co2e' => 'E10',
        'estimation_source' => 'E11',
        'key_sources' => 'E13',
        'determination_procedures' => 'E14',
        'ldar_title' => 'E18',
        'ldar_description' => 'E19',
        'ldar_person' => 'E20',
    ];

    // 4I — the two procedure blocks EAD asks to see, each six fields deep.
    private const MANAGEMENT_PROCEDURE_CELLS = [
        'equipment_qa' => ['title' => 'E17', 'reference' => 'E18', 'diagram' => 'E19', 'description' => 'E20', 'responsible_post' => 'E23', 'records_location' => 'E24'],
        'data_validation' => ['title' => 'E28', 'reference' => 'E29', 'diagram' => 'E30', 'description' => 'E31', 'responsible_post' => 'E33', 'records_location' => 'E34'],
    ];

    // The description label is merged over several rows and the value rows
    // beneath it are separate merged blocks, so a long description is laid out
    // one line per row the way the template's own worked example is.
    private const MANAGEMENT_DESCRIPTION_ROWS = ['equipment_qa' => 3, 'data_validation' => 2];

    // 4I (a) — responsibilities, rows 7..11: C job title, F duties.
    private const RESPONSIBILITY_FIRST_ROW = 7;

    private const RESPONSIBILITY_LAST_ROW = 11;

    // 4h (b) — data gaps, rows 20..29. Column B is the template's own 1..10
    // numbering and is left alone.
    private const GAP_FIRST_ROW = 20;

    private const GAP_LAST_ROW = 29;

    // 4J (a) — mitigation measures, rows 7..14.
    private const MITIGATION_FIRST_ROW = 7;

    private const MITIGATION_LAST_ROW = 14;

    // 2c1 — facility identifiers live in column H at the label's row.
    private const ID_ROWS = [
        'entity' => 'H5',
        'parent' => 'H6',
        'facility' => 'H7',
        'licence' => 'H8',
        'permit' => 'H9',
        'address' => 'H10',
        'coordinates' => 'H11',
    ];

    // 2c1 — contact blocks: primary H30:H36, alternate H38:H44 (title…email order).
    private const CONTACT_FIELDS = ['title', 'first_name', 'surname', 'job_title', 'organisation', 'telephone', 'email'];

    private const PRIMARY_CONTACT_START = 30;

    private const ALT_CONTACT_START = 38;

    // 2c2 — emission sources table (rows 43..67): C..J.
    private const SRC_FIRST_ROW = 43;

    private const SRC_LAST_ROW = 67;

    // 2c2 — source streams table (rows 75..99): C..L.
    private const STREAM_FIRST_ROW = 75;

    private const STREAM_LAST_ROW = 99;

    public function __construct(private MrvCalculator $calculator) {}

    /**
     * The absolute path of the template to fill.
     *
     * This used to be storage_path('app/templates/…'), which was correct
     * before tenancy and wrong after it: FilesystemTenancyBootstrapper
     * suffixes storage_path() per tenant, so inside a tenant request — which
     * is the ONLY way this service is ever reached, since the app is served
     * exclusively on tenant subdomains — it resolved to
     * storage/tenant{id}/app/templates/…, a directory holding one client's
     * uploads that has never contained the template. Every export threw.
     *
     * The workbook is shared reference data, byte-identical for every client,
     * so it belongs outside per-tenant storage. Reading it from config also
     * lets a deployment point at the exact version EAD issued it — see
     * config/mrv.php for why it is not bundled.
     */
    public static function templatePath(): string
    {
        // `?:` not a config() default: the key exists, so a blank value would
        // be returned as '' rather than falling back. See config/mrv.php.
        return (string) (config('mrv.ead_template') ?: base_path(self::DEFAULT_TEMPLATE_PATH));
    }

    /**
     * Build the filled spreadsheet for one facility + reporting year.
     */
    public function fill(Facilities $facility, int $year, ?MrvFacilityReport $report = null): Spreadsheet
    {
        $path = self::templatePath();

        if (! is_file($path)) {
            throw new RuntimeException(
                "The EAD workbook template is not installed. Expected it at {$path}. ".
                'It is not shipped with the application — EAD issues it to each operator under a '.
                'confidentiality notice — so install your own copy there, or point '.
                'MRV_EAD_TEMPLATE_PATH at it.'
            );
        }

        $reader = IOFactory::createReaderForFile($path);
        $book = $reader->load($path);

        $this->assertCellMapFits($book, $path);

        // Fetched once and shared, because 2c2's stream table and BOTH of
        // 3d1's tables are row-aligned by the template's own formulas. Two
        // queries could not disagree about order today, but a later `orderBy`
        // change in one of them would silently file each stream's tier under
        // the previous stream's id — so there is only one ordering to change.
        $streams = MrvSourceStream::where('facility_id', $facility->id)
            ->where('reporting_year', $year)
            ->orderBy('stream_code')
            ->get();

        // Sources are shared for the same reason, and it matters more: 3e1's
        // emissions column is ='2c2_Facility Description'!G43 downward, so the
        // two sheets must list sources in the SAME ORDER or every measured
        // source's tier lands beside a different source's emissions. One
        // ordering, one place to change it.
        $sources = MrvEmissionSource::where('facility_id', $facility->id)
            ->where('reporting_year', $year)
            ->orderBy('source_code')
            ->get();

        $instruments = MrvMeasuringInstrument::where('facility_id', $facility->id)
            ->where('reporting_year', $year)
            ->orderBy('instrument_code')
            ->get();

        $this->fillIdentifiers($book, $facility, $report);
        $this->fillFacilityDescription($book, $facility, $year, $report, $streams, $sources);
        $this->fillStreamTiers($book, $streams);
        $this->fillCalculation($book, $streams);
        $this->fillMeasuredSources($book, $sources);
        $this->fillMeasurement($book, $report, $instruments);
        $this->fillFallback($book, $report);
        $this->fillMethane($book, $report);
        $this->fillVerification($book, $report);
        $this->fillManagement($book, $report);
        $this->fillMitigation($book, $report);

        // Land the user on the first content sheet.
        $book->setActiveSheetIndexByName(self::SHEET_IDENTIFIERS);

        return $book;
    }

    /**
     * Refuse to fill a template this cell map was not written for.
     *
     * Every coordinate here is hardcoded to v8.1. A version that inserts a row
     * does not break the write — it relocates it, and produces a submission
     * whose figures sit under the wrong headings while looking perfectly
     * ordinary. That failure is silent, lands in a regulatory document, and
     * would most likely be noticed by the regulator rather than by us.
     *
     * Failing loudly costs an operator one clear error. Not checking costs them
     * a misfiled inventory.
     */
    private function assertCellMapFits(Spreadsheet $book, string $path): void
    {
        foreach (self::CELL_MAP_ANCHORS as [$sheetName, $coordinate, $expected]) {
            $ws = $book->getSheetByName($sheetName);

            if (! $ws) {
                throw new RuntimeException(
                    "This does not look like the EAD Deliverable C template: it has no \"{$sheetName}\" sheet. ".
                    "Checked {$path}."
                );
            }

            $actual = $this->normaliseLabel((string) $ws->getCell($coordinate)->getValue());

            if ($actual !== $this->normaliseLabel($expected)) {
                throw new RuntimeException(
                    'The EAD template does not match the layout this export was built for (v8.1). '.
                    "Expected \"{$expected}\" at {$sheetName}!{$coordinate}, found \"{$actual}\". ".
                    'Filling it anyway would write values into the wrong cells of a regulatory '.
                    "submission, so the export has stopped. Checked {$path}."
                );
            }
        }
    }

    /** Case- and whitespace-insensitive, so cosmetic drift is not read as a moved row. */
    private function normaliseLabel(string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $value)));
    }

    /**
     * Blank a table's data rows before writing the real ones.
     *
     * The template ships PRE-POPULATED with illustrative rows — the 2c2
     * emission-source table carries a full 25 of them, named "x", "xx", "xxx",
     * "y", "yy", "z"… each with a source id and a methodology. They are there
     * as guidance for someone filling the workbook by hand, who overwrites or
     * deletes them as they go.
     *
     * An export that writes three real sources over the first three rows leaves
     * the other twenty-two in place, and the operator files a submission
     * declaring twenty-five emission sources, twenty-two of which do not exist.
     * The same trap is set per-cell: set() deliberately skips blanks so it does
     * not clobber the template, so a real stream with no combustion device
     * inherited the illustration's "Gas fired heaters / 100 MW".
     *
     * Clearing first is therefore not tidiness — it is the difference between a
     * submission that describes the facility and one that describes the
     * example. It runs unconditionally: a facility with no data should export
     * an empty table, never a worked example presented as its own inventory.
     *
     * Formula cells are stepped over. Several of these columns are the
     * template's own cross-sheet lookups, and blanking them would break the
     * workbook's internal wiring.
     */
    private function clearTable($ws, int $firstRow, int $lastRow, string $firstCol, string $lastCol): void
    {
        $from = Coordinate::columnIndexFromString($firstCol);
        $to = Coordinate::columnIndexFromString($lastCol);

        for ($row = $firstRow; $row <= $lastRow; $row++) {
            for ($col = $from; $col <= $to; $col++) {
                $cell = $ws->getCell(Coordinate::stringFromColumnIndex($col).$row);
                $value = $cell->getValue();

                if (is_string($value) && str_starts_with($value, '=')) {
                    continue;
                }

                $cell->setValue(null);
            }
        }
    }

    private function fillIdentifiers(Spreadsheet $book, Facilities $facility, ?MrvFacilityReport $report): void
    {
        $ws = $book->getSheetByName(self::SHEET_IDENTIFIERS);
        if (! $ws) {
            return;
        }

        // 2c1 asks for three distinct names on three consecutive rows: the
        // operating COMPANY (r5), its group/parent if any (r6), and the
        // FACILITY as named on the environmental permit (r7).
        //
        // This line used to read `$facility->parent_entity ? $facility->name :
        // $facility->name` — a ternary with identical branches, so the facility
        // name was written as the operator's legal entity on every export. The
        // company is the entity that holds the permit and carries the
        // obligation; naming a site instead misidentifies who is reporting.
        $this->set($ws, self::ID_ROWS['entity'], $facility->company?->name);
        $this->set($ws, self::ID_ROWS['parent'], $facility->parent_entity);
        $this->set($ws, self::ID_ROWS['facility'], $facility->name);
        $this->set($ws, self::ID_ROWS['licence'], $facility->economic_licence_number);
        $this->set($ws, self::ID_ROWS['permit'], $facility->environmental_permit_no);
        $this->set($ws, self::ID_ROWS['address'], $facility->address);
        $this->set($ws, self::ID_ROWS['coordinates'], $facility->coordinates);

        $contacts = $report?->contacts ?? [];
        $this->fillContact($ws, self::PRIMARY_CONTACT_START, $contacts['primary'] ?? []);
        $this->fillContact($ws, self::ALT_CONTACT_START, $contacts['alternate'] ?? []);
    }

    private function fillContact($ws, int $startRow, array $contact): void
    {
        foreach (self::CONTACT_FIELDS as $i => $field) {
            if (! empty($contact[$field])) {
                $this->set($ws, 'H'.($startRow + $i), $contact[$field]);
            }
        }
    }

    /**
     * @param  Collection<int, MrvSourceStream>  $streams
     * @param  Collection<int, MrvEmissionSource>  $sources
     */
    private function fillFacilityDescription(Spreadsheet $book, Facilities $facility, int $year, ?MrvFacilityReport $report, Collection $streams, Collection $sources): void
    {
        $ws = $book->getSheetByName(self::SHEET_FACILITY);
        if (! $ws) {
            return;
        }

        // Primary sector / activity (yellow dropdowns).
        $this->set($ws, 'K10', $facility->primary_sector);
        $this->set($ws, 'K12', $facility->primary_activity);

        $this->fillProducts($ws, $report);

        // Estimated annual emissions (G30) + justification (C33 text box).
        $estimated = $report?->estimated_annual_co2e;
        if ($estimated === null) {
            $estimated = MrvSourceStream::where('facility_id', $facility->id)
                ->where('reporting_year', $year)
                ->sum('estimated_co2e');
        }
        $this->set($ws, 'G30', $estimated ? (float) $estimated : null);
        $this->set($ws, 'C33', $report?->estimation_justification);

        // Emission sources table (C..J, rows 43..67). All 25 rows ship filled
        // with illustrative placeholders — see clearTable().
        $this->clearTable($ws, self::SRC_FIRST_ROW, self::SRC_LAST_ROW, 'C', 'J');

        $row = self::SRC_FIRST_ROW;
        foreach ($sources as $s) {
            if ($row > self::SRC_LAST_ROW) {
                break;
            }
            $this->set($ws, "C{$row}", $s->source_code);
            $this->set($ws, "D{$row}", $s->name ?: $s->description);
            $this->set($ws, "E{$row}", $s->associated_product);
            $this->set($ws, "F{$row}", $s->ghg_types);
            $this->set($ws, "G{$row}", $s->total_co2e !== null ? (float) $s->total_co2e : null);
            $this->set($ws, "H{$row}", $s->energy_related ? 1 : null);
            $this->set($ws, "I{$row}", $s->process_emissions ? 1 : null);
            $this->set($ws, "J{$row}", $this->methodologyLabel($s->methodology));
            $row++;
        }

        // Source streams table (C..L, rows 75..99).
        $this->clearTable($ws, self::STREAM_FIRST_ROW, self::STREAM_LAST_ROW, 'C', 'L');

        $row = self::STREAM_FIRST_ROW;
        $methanePresent = false;
        foreach ($streams as $st) {
            if ($row > self::STREAM_LAST_ROW) {
                break;
            }
            $this->set($ws, "C{$row}", $st->stream_code);
            $this->set($ws, "D{$row}", $st->description);
            $this->set($ws, "E{$row}", $st->emission_source_code);
            $this->set($ws, "F{$row}", $this->classificationLabel($st->classification));
            $this->set($ws, "G{$row}", $st->activity_level !== null ? (float) $st->activity_level : null);
            $this->set($ws, "H{$row}", $st->activity_unit);
            $this->set($ws, "I{$row}", $st->fuel_type);
            $this->set($ws, "J{$row}", $st->combustion_device);
            $this->set($ws, "K{$row}", $st->device_capacity !== null ? (float) $st->device_capacity : null);
            $this->set($ws, "L{$row}", $st->device_capacity_unit);
            $row++;
        }

        // Methane present? flag at I69 (boolean dropdown).
        if ($report?->methane_present) {
            $methanePresent = true;
        }
        if ($methanePresent) {
            $this->set($ws, 'I69', true);
        }
    }

    /**
     * 3d1 — the two tables that make a calculated stream auditable: what it
     * contributes, and how well it is known.
     *
     * These columns exist on MrvSourceStream and are collected by the MRV entry
     * screen, and until now nothing carried them into the workbook. An operator
     * could record that a stream is Tier 3 at ±1.6% uncertainty and export a
     * submission that said nothing about either — which for a regulator is the
     * difference between a monitoring plan and a number.
     *
     * Row alignment is load-bearing. The template computes 3d1's stream ids
     * from 2c2 (column B is ='2c2_Facility Description'!C75 downwards) and the
     * tier table computes its own from the table above it (=B10 …). Nothing is
     * written into column B for that reason; the ordering of $streams is what
     * keeps each row's figures under the right id.
     *
     * Deliberately NOT written:
     *   - (a) F "Possible category" — a suggestion the template offers; the
     *     de-minimis rule that produces it (<1 kt or <2%, capped at 20 kt) is
     *     not implemented, and a guessed materiality band is worse than none.
     *   - (b) H "Permitted level of uncertainty per chosen Tier" — an MRR
     *     Annex II threshold. It is not stored, and inventing a regulatory
     *     limit is the one thing this class must never do.
     *
     * @param  Collection<int, MrvSourceStream>  $streams
     */
    private function fillStreamTiers(Spreadsheet $book, Collection $streams): void
    {
        $ws = $book->getSheetByName(self::SHEET_STREAM_TIERS);
        if (! $ws) {
            return;
        }

        $firstRow = self::TIER_CLASS_FIRST_ROW;
        $lastRow = $firstRow + (self::STREAM_LAST_ROW - self::STREAM_FIRST_ROW);
        $offset = self::TIER_TABLE_OFFSET;

        // (a) C description, E estimated emissions, G selected category.
        // (b) C tier, D category, E uncertainty, F fuel stream type, G source
        // of accuracy. Both tables carry an "Illustrative" marker on their
        // first row (H10 / I41) which is cleared with everything else.
        $this->clearTable($ws, $firstRow, $lastRow, 'C', 'H');
        $this->clearTable($ws, $firstRow + $offset, $lastRow + $offset, 'C', 'I');

        $row = $firstRow;

        foreach ($streams as $st) {
            if ($row > $lastRow) {
                break;
            }

            $materiality = $this->materialityLabel($st->materiality);
            $tierRow = $row + $offset;

            $this->set($ws, "C{$row}", $st->description);
            $this->set($ws, "E{$row}", $st->estimated_co2e !== null ? (float) $st->estimated_co2e : null);
            $this->set($ws, "G{$row}", $materiality);

            $this->set($ws, "C{$tierRow}", $st->tier_level !== null ? (int) $st->tier_level : null);
            $this->set($ws, "D{$tierRow}", $materiality);
            $this->set($ws, "E{$tierRow}", $st->uncertainty_pct !== null ? (float) $st->uncertainty_pct : null);
            $this->set($ws, "F{$tierRow}", $st->fuel_type);
            $this->set($ws, "G{$tierRow}", $st->accuracy_source);

            $row++;
        }
    }

    /**
     * @param  Collection<int, MrvSourceStream>  $streams
     */
    private function fillCalculation(Spreadsheet $book, Collection $streams): void
    {
        $ws = $book->getSheetByName(self::SHEET_CALC);
        if (! $ws) {
            return;
        }

        // "Other inputs / outputs" table (rows 60..84): B stream id, C type,
        // F NCV, G EF, H oxidation, I conversion, J information source.
        // (Columns D/E are template formulas that pull activity from 2c2 —
        // clearTable steps over them, and nothing here writes them.)
        //
        // Only streams with a net calorific value appear: this table is the
        // decomposed EU-ETS input set, and a stream carrying only a combined
        // factor has nothing to say in it.
        $decomposed = $streams->filter(fn (MrvSourceStream $st) => $st->net_calorific_value !== null);

        $this->clearTable($ws, self::CALC_FIRST_ROW, self::CALC_LAST_ROW, 'B', 'J');

        $row = self::CALC_FIRST_ROW;
        foreach ($decomposed as $st) {
            if ($row > self::CALC_LAST_ROW) {
                break;
            }
            $this->set($ws, "B{$row}", $st->stream_code);
            $this->set($ws, "C{$row}", $st->fuel_type ?: $st->description);
            $this->set($ws, "F{$row}", $this->combine($st->net_calorific_value, $st->ncv_unit));
            $this->set($ws, "G{$row}", $this->combine($st->emission_factor_value, $st->ef_unit));
            $this->set($ws, "H{$row}", $st->oxidation_factor !== null ? (float) $st->oxidation_factor : null);
            $this->set($ws, "I{$row}", $st->conversion_factor !== null ? (float) $st->conversion_factor : null);
            $this->set($ws, "J{$row}", $st->information_source);
            $row++;
        }
    }

    /**
     * 3e1 — the emission sources determined by measurement, and how well.
     *
     * Positional, not filtered. Column C is the template's own lookup into 2c2
     * (`='2c2_Facility Description'!G43` downward), so row 9 reports the FIRST
     * emission source's total whatever id is written next to it. Listing only
     * the measured sources would put each one's tier beside a different
     * source's emissions.
     *
     * So the (a) table mirrors 2c2's ordering for every source, and the columns
     * that are specific to measurement — the category in (a), and the whole of
     * (b) — are written only for sources that actually declare the measurement
     * methodology. A reader can tell which sources are measured by which rows
     * carry a tier, which is what the sheet is for.
     *
     * Column H of (b) — permitted uncertainty per Annex VIII — is left alone
     * for the same reason as 3d1's: it is a regulatory threshold, it is not
     * stored, and inventing one is the thing this class must never do.
     *
     * @param  Collection<int, MrvEmissionSource>  $sources
     */
    private function fillMeasuredSources(Spreadsheet $book, Collection $sources): void
    {
        $ws = $book->getSheetByName(self::SHEET_MEASURED_SOURCES);
        if (! $ws) {
            return;
        }

        $firstRow = self::MEASURED_FIRST_ROW;
        $lastRow = $firstRow + (self::SRC_LAST_ROW - self::SRC_FIRST_ROW);
        $offset = self::MEASURED_TIER_OFFSET;

        // (a) B id, D category — C is the template's lookup and E carries an
        // "Illustrative" marker on the first row.
        $this->clearTable($ws, $firstRow, $lastRow, 'B', 'E');
        // (b) C tier, D category, E uncertainty, F stream type, G accuracy —
        // B is a formula, H is the Annex VIII threshold, I marks illustrative.
        $this->clearTable($ws, $firstRow + $offset, $lastRow + $offset, 'C', 'I');

        $row = $firstRow;

        foreach ($sources as $source) {
            if ($row > $lastRow) {
                break;
            }

            // Written for every source, measured or not, so the id beside each
            // row matches the emissions the formula pulls into it.
            $this->set($ws, "B{$row}", $source->source_code);

            if ($source->methodology === 'measurement') {
                $materiality = $this->materialityLabel($source->materiality);
                $tierRow = $row + $offset;

                $this->set($ws, "D{$row}", $materiality);

                $this->set($ws, "C{$tierRow}", $source->tier_level !== null ? (int) $source->tier_level : null);
                $this->set($ws, "D{$tierRow}", $materiality);
                $this->set($ws, "E{$tierRow}", $source->uncertainty_pct !== null ? (float) $source->uncertainty_pct : null);
                $this->set($ws, "F{$tierRow}", $source->emission_stream_type);
                $this->set($ws, "G{$tierRow}", $source->accuracy_source);
            }

            $row++;
        }
    }

    /**
     * 3e2 — how the measurement is actually done, and where.
     *
     * @param  Collection<int, \App\Models\MrvMeasuringInstrument>  $instruments
     */
    private function fillMeasurement(Spreadsheet $book, ?MrvFacilityReport $report, Collection $instruments): void
    {
        $ws = $book->getSheetByName(self::SHEET_MEASUREMENT);
        if (! $ws) {
            return;
        }

        $this->set($ws, self::MEASUREMENT_APPROACH_CELL, $report?->measurement_approach);

        // B9:K15 — seven rows of merged block. The derivation is the answer an
        // assurer reads hardest, so it gets laid out rather than clipped into
        // one line.
        $this->writeWrapped($ws, self::MEASUREMENT_DERIVATION_CELL, 7, $report?->measurement_derivation);

        $this->set($ws, self::MEASUREMENT_COMMENTS_CELL, $report?->measurement_comments);

        // Measurement points. Rows 23..25 ship with an illustrative MI1/S03/S04.
        $this->clearTable($ws, self::MEASUREMENT_POINT_FIRST_ROW, self::MEASUREMENT_POINT_LAST_ROW, 'B', 'I');

        $row = self::MEASUREMENT_POINT_FIRST_ROW;

        foreach ($instruments as $instrument) {
            if ($row > self::MEASUREMENT_POINT_LAST_ROW) {
                break;
            }

            $this->set($ws, "B{$row}", $instrument->instrument_code);
            $this->set($ws, "C{$row}", $instrument->emission_source_code);
            $this->set($ws, "D{$row}", $instrument->procedures);
            $this->set($ws, "H{$row}", $instrument->relevant_procedures);
            $this->set($ws, "I{$row}", $instrument->relevant_source);

            $row++;
        }
    }

    /**
     * 3f — monitoring that does not use the tier system, and the case for it.
     *
     * Written whenever the operator has recorded either half, rather than
     * gated on whether a source currently declares the fall-back methodology.
     *
     * The two failure directions are not symmetric. Exporting a justification
     * for an approach nobody is using is confusing; NOT exporting one while a
     * source does use fall-back is an incomplete regulatory submission, and the
     * competent authority may ask for the workings behind exactly this section.
     * So it errs toward writing, and the workspace prompts when a fall-back
     * source exists with nothing recorded here.
     */
    private function fillFallback(Spreadsheet $book, ?MrvFacilityReport $report): void
    {
        $ws = $book->getSheetByName(self::SHEET_FALLBACK);
        if (! $ws) {
            return;
        }

        $this->set($ws, self::FALLBACK_DESCRIPTION_CELL, $report?->fallback_description);
        $this->set($ws, self::FALLBACK_JUSTIFICATION_CELL, $report?->fallback_justification);
    }

    /**
     * 3g — methane, written only when the operator said methane occurs here.
     *
     * The gate matters: 2c2!I69 drives a formula that tells the reader to fill
     * this sheet, and a facility that answered "no" should hand in a sheet that
     * is empty rather than one carrying half-answers from an earlier draft.
     */
    private function fillMethane(Spreadsheet $book, ?MrvFacilityReport $report): void
    {
        $ws = $book->getSheetByName(self::SHEET_METHANE);
        if (! $ws || ! $report?->methane_present) {
            return;
        }

        $methane = $report->methane ?? [];

        foreach (self::METHANE_CELLS as $key => $cell) {
            $this->set($ws, $cell, $methane[$key] ?? null);
        }
    }

    /** 4h — the verification narrative, and the data-gaps table. */
    private function fillVerification(Spreadsheet $book, ?MrvFacilityReport $report): void
    {
        $ws = $book->getSheetByName(self::SHEET_VERIFICATION);
        if (! $ws) {
            return;
        }

        // C7:L15 is one merged block; the text goes in the cell it starts at.
        $this->set($ws, 'C7', $report?->verification_text);

        // C..K, leaving column B — the template's own 1..10 row numbering.
        $this->clearTable($ws, self::GAP_FIRST_ROW, self::GAP_LAST_ROW, 'C', 'K');

        $row = self::GAP_FIRST_ROW;

        foreach ($report?->data_gaps ?? [] as $gap) {
            if ($row > self::GAP_LAST_ROW) {
                break;
            }

            $this->set($ws, "C{$row}", $gap['ref'] ?? null);
            $this->set($ws, "D{$row}", $gap['from'] ?? null);
            $this->set($ws, "E{$row}", $gap['until'] ?? null);
            $this->set($ws, "F{$row}", $gap['description'] ?? null);
            $this->set($ws, "H{$row}", isset($gap['estimated_co2e']) ? (float) $gap['estimated_co2e'] : null);
            $this->set($ws, "J{$row}", $gap['source'] ?? null);

            $row++;
        }
    }

    /** 4I — responsibilities and the two procedures. */
    private function fillManagement(Spreadsheet $book, ?MrvFacilityReport $report): void
    {
        $ws = $book->getSheetByName(self::SHEET_MANAGEMENT);
        if (! $ws) {
            return;
        }

        $management = $report?->management ?? [];

        // Row 7 ships as a worked example — "HSEQ deputy head of unit" — so
        // the table is cleared before writing for the same reason 2c2's is.
        $this->clearTable($ws, self::RESPONSIBILITY_FIRST_ROW, self::RESPONSIBILITY_LAST_ROW, 'C', 'M');

        $row = self::RESPONSIBILITY_FIRST_ROW;

        foreach ($management['responsibilities'] ?? [] as $person) {
            if ($row > self::RESPONSIBILITY_LAST_ROW) {
                break;
            }

            $this->set($ws, "C{$row}", $person['post'] ?? null);
            $this->set($ws, "F{$row}", $person['duties'] ?? null);

            $row++;
        }

        foreach (self::MANAGEMENT_PROCEDURE_CELLS as $key => $cells) {
            $procedure = $management[$key] ?? [];

            foreach ($cells as $field => $cell) {
                if ($field === 'description') {
                    continue;
                }

                $this->set($ws, $cell, $procedure[$field] ?? null);
            }

            $this->writeWrapped(
                $ws,
                $cells['description'],
                self::MANAGEMENT_DESCRIPTION_ROWS[$key],
                $procedure['description'] ?? null,
            );
        }

        $this->set($ws, 'C37', $management['further_details'] ?? null);
    }

    /**
     * Lay a multi-line value across the rows the template gives it.
     *
     * The description label is merged down several rows, and each value row
     * beside it is its own merged block — so a paragraph written into the first
     * cell alone would render as one clipped line above empty boxes. The
     * template's own example puts one bullet per row, and this does the same.
     * Anything past the last row is folded into it rather than dropped: an
     * operator's procedure is not ours to truncate.
     */
    private function writeWrapped($ws, string $firstCell, int $rows, ?string $value): void
    {
        $column = preg_replace('/\d+/', '', $firstCell);
        $firstRow = (int) preg_replace('/\D+/', '', $firstCell);

        // Clear the block first, or a shorter description leaves the tail of a
        // longer previous one — or of the template's worked example — behind.
        for ($i = 0; $i < $rows; $i++) {
            $ws->getCell($column.($firstRow + $i))->setValue(null);
        }

        if (! filled($value)) {
            return;
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($value));

        if (count($lines) > $rows) {
            $lines = array_merge(
                array_slice($lines, 0, $rows - 1),
                [implode(' ', array_slice($lines, $rows - 1))],
            );
        }

        foreach ($lines as $i => $line) {
            $this->set($ws, $column.($firstRow + $i), $line);
        }
    }

    /** 4J — mitigation measures, one row each, plus the free-text addendum. */
    private function fillMitigation(Spreadsheet $book, ?MrvFacilityReport $report): void
    {
        $ws = $book->getSheetByName(self::SHEET_MITIGATION);
        if (! $ws) {
            return;
        }

        $stored = $report?->mitigation_measures ?? [];

        $this->clearTable($ws, self::MITIGATION_FIRST_ROW, self::MITIGATION_LAST_ROW, 'C', 'M');

        $row = self::MITIGATION_FIRST_ROW;

        foreach ($stored['measures'] ?? [] as $measure) {
            if ($row > self::MITIGATION_LAST_ROW) {
                break;
            }

            $this->set($ws, "C{$row}", $measure['description'] ?? null);
            $this->set($ws, "D{$row}", $measure['category'] ?? null);
            $this->set($ws, "E{$row}", isset($measure['scope']) ? (int) $measure['scope'] : null);
            $this->set($ws, "F{$row}", $measure['ghg'] ?? null);
            $this->set($ws, "G{$row}", isset($measure['start_year']) ? (int) $measure['start_year'] : null);
            $this->set($ws, "H{$row}", $measure['status'] ?? null);
            $this->set($ws, "I{$row}", $measure['baseline'] ?? null);
            $this->set($ws, "J{$row}", isset($measure['reporting_year_reduction']) ? (float) $measure['reporting_year_reduction'] : null);
            $this->set($ws, "K{$row}", isset($measure['expected_annual_reduction']) ? (float) $measure['expected_annual_reduction'] : null);
            $this->set($ws, "L{$row}", $measure['methodology'] ?? null);
            $this->set($ws, "M{$row}", $measure['verification'] ?? null);

            $row++;
        }

        // 4J (b) — C17:L25, a merged block. EAD asks for "N/A" rather than a
        // blank when there is nothing to add, so say so on the operator's
        // behalf only when they have recorded measures and left this empty.
        $this->set($ws, 'C17', $stored['additional'] ?? null);
    }

    /** 2c2 (b) — products whose production causes the facility's emissions. */
    private function fillProducts($ws, ?MrvFacilityReport $report): void
    {
        // D..L: column C is the template's own P01..P10 numbering, which the
        // operator refers to from the emission-source table.
        $this->clearTable($ws, self::PRODUCT_FIRST_ROW, self::PRODUCT_LAST_ROW, 'D', 'L');

        $row = self::PRODUCT_FIRST_ROW;

        foreach ($report?->products ?? [] as $product) {
            if ($row > self::PRODUCT_LAST_ROW) {
                break;
            }

            $this->set($ws, "D{$row}", $product['category'] ?? null);
            $this->set($ws, "E{$row}", $product['technology'] ?? null);
            $this->set($ws, "G{$row}", ! empty($product['energy_related']) ? 1 : null);
            $this->set($ws, "H{$row}", ! empty($product['process_emissions']) ? 1 : null);
            $this->set($ws, "I{$row}", isset($product['capacity']) ? (float) $product['capacity'] : null);
            $this->set($ws, "J{$row}", $product['capacity_unit'] ?? null);
            $this->set($ws, "K{$row}", isset($product['actual']) ? (float) $product['actual'] : null);
            $this->set($ws, "L{$row}", $product['actual_unit'] ?? null);

            $row++;
        }
    }

    /** Set a cell only when there is a value to write (never clobber template content with blanks). */
    private function set($ws, string $coord, $value): void
    {
        if ($value === null || $value === '') {
            return;
        }
        $ws->setCellValue($coord, $value);
    }

    private function combine($value, ?string $unit): ?string
    {
        if ($value === null) {
            return null;
        }
        $num = rtrim(rtrim(number_format((float) $value, 6, '.', ''), '0'), '.');

        return trim($num.' '.($unit ?? ''));
    }

    /**
     * Stored key → the exact string the workbook's dropdown accepts.
     *
     * Read from config rather than matched inline so the form that offers the
     * options and the export that writes them cannot drift apart: adding a
     * methodology to one and not the other used to mean a value that saved
     * cleanly and then silently exported as blank.
     */
    private function methodologyLabel(?string $m): ?string
    {
        return config('mrv.methodologies')[$m] ?? null;
    }

    /** The 3d1 dropdown wording for a stored materiality band. */
    private function materialityLabel(?string $m): ?string
    {
        return match ($m) {
            'major' => 'Major',
            'minor' => 'Minor',
            'de_minimis' => 'De-minimis',
            default => null,
        };
    }

    /** Stored key → the workbook's own source-stream-type wording. */
    private function classificationLabel(?string $c): ?string
    {
        return config('mrv.stream_types')[$c] ?? null;
    }
}

<?php

namespace App\Services\MRV;

use App\Models\Facilities;
use App\Models\MrvEmissionSource;
use App\Models\MrvFacilityReport;
use App\Models\MrvSourceStream;
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
    /** Path to the bundled template, relative to storage/app. */
    public const TEMPLATE_PATH = 'templates/ead_deliverable_c.xlsx';

    private const SHEET_IDENTIFIERS = '2c1_ Identifiers';
    private const SHEET_FACILITY    = '2c2_Facility Description';
    private const SHEET_CALC        = '3d2_ Calculation Approaches';

    // 2c1 — facility identifiers live in column H at the label's row.
    private const ID_ROWS = [
        'entity'      => 'H5',
        'parent'      => 'H6',
        'facility'    => 'H7',
        'licence'     => 'H8',
        'permit'      => 'H9',
        'address'     => 'H10',
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
     * Build the filled spreadsheet for one facility + reporting year.
     */
    public function fill(Facilities $facility, int $year, ?MrvFacilityReport $report = null): Spreadsheet
    {
        $path = storage_path('app/' . self::TEMPLATE_PATH);
        if (!is_file($path)) {
            throw new RuntimeException("EAD template not found at {$path}.");
        }

        $reader = IOFactory::createReaderForFile($path);
        $book = $reader->load($path);

        $this->fillIdentifiers($book, $facility, $report);
        $this->fillFacilityDescription($book, $facility, $year, $report);
        $this->fillCalculation($book, $facility, $year);

        // Land the user on the first content sheet.
        $book->setActiveSheetIndexByName(self::SHEET_IDENTIFIERS);

        return $book;
    }

    private function fillIdentifiers(Spreadsheet $book, Facilities $facility, ?MrvFacilityReport $report): void
    {
        $ws = $book->getSheetByName(self::SHEET_IDENTIFIERS);
        if (!$ws) {
            return;
        }

        $this->set($ws, self::ID_ROWS['entity'], $facility->parent_entity ? $facility->name : $facility->name);
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
            if (!empty($contact[$field])) {
                $this->set($ws, 'H' . ($startRow + $i), $contact[$field]);
            }
        }
    }

    private function fillFacilityDescription(Spreadsheet $book, Facilities $facility, int $year, ?MrvFacilityReport $report): void
    {
        $ws = $book->getSheetByName(self::SHEET_FACILITY);
        if (!$ws) {
            return;
        }

        // Primary sector / activity (yellow dropdowns).
        $this->set($ws, 'K10', $facility->primary_sector);
        $this->set($ws, 'K12', $facility->primary_activity);

        // Estimated annual emissions (G30) + justification (C33 text box).
        $estimated = $report?->estimated_annual_co2e;
        if ($estimated === null) {
            $estimated = MrvSourceStream::where('facility_id', $facility->id)
                ->where('reporting_year', $year)
                ->sum('estimated_co2e');
        }
        $this->set($ws, 'G30', $estimated ? (float) $estimated : null);
        $this->set($ws, 'C33', $report?->estimation_justification);

        // Emission sources table (C..J, rows 43..67).
        $sources = MrvEmissionSource::where('facility_id', $facility->id)
            ->where('reporting_year', $year)
            ->orderBy('source_code')
            ->get();

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
        $streams = MrvSourceStream::where('facility_id', $facility->id)
            ->where('reporting_year', $year)
            ->orderBy('stream_code')
            ->get();

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

    private function fillCalculation(Spreadsheet $book, Facilities $facility, int $year): void
    {
        $ws = $book->getSheetByName(self::SHEET_CALC);
        if (!$ws) {
            return;
        }

        // "Other inputs / outputs" table (rows 60..84): B stream id, C type,
        // F NCV, G EF, H oxidation, I conversion, J information source.
        // (Columns D/E are template formulas that pull activity from 2c2 — left alone.)
        $streams = MrvSourceStream::where('facility_id', $facility->id)
            ->where('reporting_year', $year)
            ->whereNotNull('net_calorific_value')
            ->orderBy('stream_code')
            ->get();

        $row = 60;
        foreach ($streams as $st) {
            if ($row > 84) {
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
        return trim($num . ' ' . ($unit ?? ''));
    }

    private function methodologyLabel(?string $m): ?string
    {
        return match ($m) {
            'calculation' => 'Calculation-based',
            'measurement' => 'Measurement-based',
            'fallback' => 'Fall-back',
            default => null,
        };
    }

    private function classificationLabel(?string $c): ?string
    {
        return match ($c) {
            'fuel_combusted' => 'Fuel combusted',
            'other_input' => 'Other input',
            'output' => 'Output',
            default => null,
        };
    }
}

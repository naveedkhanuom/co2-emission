<?php

namespace App\Services\Factors\Import;

use App\Models\EmissionFactor;
use App\Models\EmissionSource;
use App\Models\FactorImport;
use App\Models\FactorOrganization;
use App\Support\Auditing;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Imports the DESNZ/DEFRA "UK Government GHG Conversion Factors" flat file.
 *
 * DESNZ publishes this edition explicitly "for automatic processing only": one
 * sheet, one row per (activity, unit, gas), which is why it needs no
 * table-boundary guessing. See database/factors/sources/MANIFEST.md for the file,
 * its hash and its licence.
 *
 * SHAPE
 *
 *   ID | Scope | Level 1 | Level 2 | Level 3 | Level 4 | Column Text | UOM
 *      | GHG/Unit | GHG Conversion Factor
 *
 * A single factor spans up to four rows sharing everything but GHG/Unit:
 *
 *   kg CO2e                     -> factor_value   (the total)
 *   kg CO2e of CO2 per unit     -> co2_factor
 *   kg CO2e of CH4 per unit     -> ch4_factor
 *   kg CO2e of N2O per unit     -> n2o_factor
 *
 * THE GAS FIGURES ARE ALREADY CO2e-WEIGHTED
 *
 * "kg CO2e OF CH4" is the methane contribution already multiplied by its GWP —
 * not raw kg CH4. They must not be weighted again. This differs from
 * config/scope1_sources.php, whose ch4/n2o are raw kg per TJ and DO need
 * weighting, and conflating the two overstates the split by ~28x for methane.
 *
 * UNITS ARE THE PUBLISHER'S, NOT CONVERTED
 *
 * Factors are stored in the UOM DEFRA states, and factor_value stays in
 * kgCO2e per unit as published. Converting on the way in would mean the stored
 * number is no longer the number in the published file, which defeats being able
 * to check one against the other. Callers convert at the point of use.
 */
class DefraFlatFileImporter
{
    public const SHEET = 'Factors by Category';

    public const DATASET = 'DEFRA/DESNZ';

    public const ORGANISATION_CODE = 'DEFRA';

    /** DESNZ 2026 factors are computed on AR5 GWPs. */
    public const GWP_VERSION = 'ar5';

    public const SOURCE_URL = 'https://www.gov.uk/government/publications/greenhouse-gas-reporting-conversion-factors-2026';

    /** GHG/Unit label -> the emission_factors column it populates. */
    private const GAS_COLUMNS = [
        'kg co2e of co2 per unit' => 'co2_factor',
        'kg co2e of ch4 per unit' => 'ch4_factor',
        'kg co2e of n2o per unit' => 'n2o_factor',
    ];

    /** GHG/Unit labels carrying net calorific value rather than a gas. */
    private const NCV_LABELS = ['kwh (net cv)', 'kwh (net)'];

    private const TOTAL_LABEL = 'kg co2e';

    /**
     * @param  string  $path  The .xlsx to read.
     * @param  string  $version  Edition, e.g. "2026".
     * @param  bool  $pretend  Parse and report without writing.
     * @return array{parsed:int, written:int, sources:int, superseded:int, skipped:int, import:?FactorImport}
     */
    public function import(string $path, string $version, bool $pretend = false): array
    {
        $groups = $this->parse($path);

        $result = [
            'parsed' => count($groups),
            'written' => 0,
            'sources' => 0,
            'superseded' => 0,
            'skipped' => 0,
            'import' => null,
        ];

        if ($pretend) {
            foreach ($groups as $group) {
                $this->totalFor($group) === null ? $result['skipped']++ : $result['written']++;
            }

            return $result;
        }

        // Reference data, not a person's change. Without this the import writes
        // one audit_logs row per factor — thousands of entries with user_id NULL
        // in the trail an assurer reads. See App\Support\Auditing.
        return Auditing::without(function () use ($groups, $path, $version, $result) {
            $import = $this->openImport($path, $version);

            DB::transaction(function () use ($groups, $import, &$result) {
                foreach ($groups as $group) {
                    $outcome = $this->writeGroup($group, $import);

                    $result['written'] += $outcome['written'];
                    $result['sources'] += $outcome['source_created'];
                    $result['superseded'] += $outcome['superseded'];
                    $result['skipped'] += $outcome['skipped'];
                }

                $import->update([
                    'factors_written' => $result['written'],
                    'sources_created' => $result['sources'],
                    'factors_superseded' => $result['superseded'],
                ]);
            });

            $result['import'] = $import->fresh();

            return $result;
        });
    }

    /**
     * Collapse the flat rows into one entry per (scope, activity, unit).
     *
     * @return array<string, array{scope:string, name:string, unit:string, reference:string, values:array<string,float>}>
     */
    public function parse(string $path): array
    {
        // Parsed once per process, per file.
        //
        // Reading this workbook is by far the expensive part — 8,742 rows through
        // PhpSpreadsheet — and it is read repeatedly in both places that matter:
        // `tenants:each factors:import` re-reads it for every client, and the
        // test suite parses it once per test. Eight parses in one PHP process
        // exhausted a 128M limit even with the worksheets disconnected.
        //
        // Keyed on inode-ish identity rather than the path alone so replacing the
        // file mid-process cannot serve stale groups.
        $key = $path.'|'.(@filemtime($path) ?: 0).'|'.(@filesize($path) ?: 0);

        if (isset(self::$parsed[$key])) {
            return self::$parsed[$key];
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $book = $reader->load($path);
        $sheet = $book->getSheetByName(self::SHEET);

        if ($sheet === null) {
            $book->disconnectWorksheets();

            throw new \RuntimeException(
                'Sheet "'.self::SHEET.'" not found in '.basename($path).'. '
                .'DESNZ may have changed the workbook layout — check the file before trusting an import.'
            );
        }

        $rows = $sheet->toArray(null, true, false, false);

        // Release the workbook before building the groups, and explicitly:
        // PhpSpreadsheet holds circular references between the spreadsheet, its
        // worksheets and their cell collections, so letting $book fall out of
        // scope does NOT free them. Running this once is survivable; running it
        // for each tenant in turn under `tenants:each` exhausted a 128M limit on
        // the third client.
        $book->disconnectWorksheets();
        unset($book, $sheet, $reader);

        $groups = [];

        foreach ($rows as $row) {
            [$id, $scope, $l1, $l2, $l3, $l4, $columnText, $uom, $ghg, $value] =
                array_pad(array_map(static fn ($c) => trim((string) $c), array_slice($row, 0, 10)), 10, '');

            // Title block, blank spacers and the header rows all lack these two.
            if ($l1 === '' || $uom === '' || $scope === '') {
                continue;
            }

            if (! preg_match('/^Scope\s*[123]$|^Outside of Scopes$/i', $scope)) {
                continue;
            }

            // Grouped on DEFRA's OWN full category path, never on the composed
            // display name. Keying on the name merged rows that are genuinely
            // different factors: "Passenger vehicles > Cars > Mini > Diesel"
            // (kgCO2e/km) and "SECR kWh pass & delivery vehs > Cars > Mini >
            // Diesel" (kWh/km) share every level except the first. Dropping
            // Level 1 from the key silently combined their values into one row —
            // 498 of 3,997 groups, and wrong rather than merely missing.
            $key = mb_strtolower(implode('|', [$scope, $l1, $l2, $l3, $l4, $columnText, $uom]));

            $groups[$key] ??= [
                'scope' => $scope,
                'name' => $this->sourceName($l1, $l2, $l3, $l4, $columnText),
                'unit' => $uom,
                'reference' => $this->reference($l1, $l2, $l3, $l4, $columnText),
                'values' => [],
            ];

            if (is_numeric($value)) {
                $groups[$key]['values'][mb_strtolower($ghg)] = (float) $value;
            }
        }

        $this->assertNamesUnique($groups);

        return self::$parsed[$key] = $groups;
    }

    /**
     * Parsed workbooks, keyed by path + mtime + size. Static so it survives
     * across the separate importer instances that `tenants:each` resolves per
     * tenant — which is the case it exists for.
     *
     * @var array<string, array<string, array{scope:string, name:string, unit:string, reference:string, values:array<string,float>}>>
     */
    private static array $parsed = [];

    /** Drop the parse cache. For tests that rewrite the source file. */
    public static function flushCache(): void
    {
        self::$parsed = [];
    }

    /**
     * The activity name — the deepest levels, qualified by the top-level
     * category.
     *
     * The Level 1 qualifier is NOT decoration. DEFRA reuses the same lower
     * levels under different top categories: "Cars > Mini > Diesel" appears both
     * under "Passenger vehicles" (kgCO2e/km) and under "SECR kWh pass & delivery
     * vehs" (kWh/km). Without Level 1 those are indistinguishable to a user
     * reading the factor library, and were indistinguishable to this importer
     * until they collided.
     *
     * Names are checked for uniqueness after parsing — see assertNamesUnique().
     */
    private function sourceName(string $l1, string $l2, string $l3, string $l4, string $columnText): string
    {
        $leaf = array_values(array_filter([$l3, $l4, $columnText], static fn ($p) => $p !== ''));

        if ($leaf === []) {
            $leaf = array_values(array_filter([$l2], static fn ($p) => $p !== ''));
        }

        $name = implode(' — ', $leaf);

        // Qualifiers in ONE parenthetical — "Butane (Fuels > Gaseous fuels)" —
        // rather than one each, which produced "Butane (Fuels) (Gaseous fuels)".
        $qualifiers = array_values(array_filter(
            [$l1, $l2],
            static fn ($p) => $p !== '' && ! str_contains(mb_strtolower($name), mb_strtolower($p))
        ));

        if ($name === '') {
            return mb_substr(implode(' > ', $qualifiers), 0, 255);
        }

        if ($qualifiers !== []) {
            $name .= ' ('.implode(' > ', $qualifiers).')';
        }

        return mb_substr($name, 0, 255);
    }

    /**
     * Refuse to import if two different DEFRA rows would land on the same
     * emission source and unit.
     *
     * firstOrCreate() on the name means a collision does not error — the second
     * row quietly attaches to the first one's source and supersedes its factor.
     * The import then reports success having stored one number where the
     * publisher gave two. Names are composed from category levels, and levels can
     * be renamed between editions, so this stays a live risk rather than a
     * one-time fix.
     *
     * @param  array<string, array{name:string, unit:string, reference:string}>  $groups
     *
     * @throws \RuntimeException
     */
    private function assertNamesUnique(array $groups): void
    {
        $seen = [];
        $clashes = [];

        foreach ($groups as $group) {
            $key = mb_strtolower($group['name'].'|'.$group['unit']);

            if (isset($seen[$key])) {
                $clashes[$key] = [$seen[$key], $group['reference']];

                continue;
            }

            $seen[$key] = $group['reference'];
        }

        if ($clashes === []) {
            return;
        }

        $detail = collect($clashes)->take(5)
            ->map(static fn ($pair, $key) => "  {$key}\n    <- {$pair[0]}\n    <- {$pair[1]}")
            ->implode("\n");

        throw new \RuntimeException(
            count($clashes).' DEFRA activities share an emission-source name and unit, '
            ."so importing would store one factor where the publisher gave several:\n\n".$detail
            ."\n\nsourceName() needs another category level to tell them apart."
        );
    }

    private function reference(string $l1, string $l2, string $l3, string $l4, string $columnText): string
    {
        $path = implode(' > ', array_values(array_filter([$l1, $l2, $l3, $l4, $columnText], static fn ($p) => $p !== '')));

        return mb_substr('DEFRA/DESNZ '.$path, 0, 255);
    }

    /** @param array{values:array<string,float>} $group */
    private function totalFor(array $group): ?float
    {
        return $group['values'][self::TOTAL_LABEL] ?? null;
    }

    private function openImport(string $path, string $version): FactorImport
    {
        $organisation = FactorOrganization::where('code', self::ORGANISATION_CODE)->first();

        return FactorImport::create([
            'dataset_name' => self::DATASET,
            'dataset_version' => $version,
            'organization_id' => $organisation?->id,
            'source_file' => basename($path),
            'source_file_hash' => hash_file('sha256', $path),
            'source_url' => self::SOURCE_URL,
            'gwp_version' => self::GWP_VERSION,
            'imported_at' => now(),
            'imported_by' => auth()->user()?->name ?? 'console',
        ]);
    }

    /**
     * @param  array{scope:string, name:string, unit:string, reference:string, values:array<string,float>}  $group
     * @return array{written:int, source_created:int, superseded:int, skipped:int}
     */
    private function writeGroup(array $group, FactorImport $import): array
    {
        $total = $this->totalFor($group);

        // No total means DEFRA published only a component or a calorific value
        // for this row. There is no factor to store, and inventing one from the
        // parts would be exactly the fabrication this pipeline exists to avoid.
        if ($total === null) {
            return ['written' => 0, 'source_created' => 0, 'superseded' => 0, 'skipped' => 1];
        }

        $scope = $this->scopeNumber($group['scope']);
        $sourceCreated = 0;

        $source = EmissionSource::firstOrCreate(
            ['name' => $group['name']],
            ['scope' => $scope, 'description' => $group['reference']]
        );

        if ($source->wasRecentlyCreated) {
            $sourceCreated = 1;
        }

        $attributes = [
            'emission_source_id' => $source->id,
            'organization_id' => $import->organization_id,
            'factor_import_id' => $import->id,
            'unit' => mb_substr($group['unit'], 0, 255),
            'factor_value' => $total,
            // States the basis the value above is stored in, which is DEFRA's
            // own and NOT the tCO2e the application computes in. Without it the
            // resolver read these as tonnes and priced activity 1000x too high.
            'factor_unit' => EmissionFactor::UNIT_KG,
            'region' => 'UK',
            'dataset_name' => $import->dataset_name,
            'dataset_version' => $import->dataset_version,
            'gwp_version' => $import->gwp_version,
            'source_reference' => $group['reference'],
            'valid_from' => now()->toDateString(),
            'is_active' => true,
        ];

        foreach (self::GAS_COLUMNS as $label => $column) {
            if (isset($group['values'][$label])) {
                $attributes[$column] = $group['values'][$label];
            }
        }

        // Net calorific value, WHEN DEFRA gives it alongside the factor. In the
        // 2026 flat file it almost never does: the kWh figures live under their
        // own top-level categories ("SECR kWh pass & delivery vehs"), which makes
        // them separate activities rather than components of these rows, and
        // matching the two across category paths would be guesswork.
        //
        // NCV therefore comes from EPA's Hub, whose Table 1 publishes heat
        // content in the same row as the factor. Kept here because the labels do
        // appear in the file and a future edition may co-locate them.
        foreach (self::NCV_LABELS as $label) {
            if (isset($group['values'][$label])) {
                $attributes['net_calorific_value'] = $group['values'][$label];
                $attributes['ncv_unit'] = 'kWh per '.$group['unit'];
                break;
            }
        }

        $superseded = $this->supersedePrevious($source->id, $group['unit'], $import);

        EmissionFactor::create($attributes);

        return ['written' => 1, 'source_created' => $sourceCreated, 'superseded' => $superseded, 'skipped' => 0];
    }

    /**
     * Retire this publisher's previous row for the same source and unit.
     *
     * Superseded, never updated: a figure computed last year still points at the
     * row that produced it, and that row must keep saying what it said. Scoped to
     * this dataset so importing DEFRA never touches EPA's or a client's own rows
     * for the same activity — different publishers disagreeing is the point of
     * holding several.
     */
    private function supersedePrevious(int $sourceId, string $unit, FactorImport $import): int
    {
        return EmissionFactor::where('emission_source_id', $sourceId)
            ->where('unit', $unit)
            ->where('dataset_name', $import->dataset_name)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'valid_to' => now()->toDateString(),
            ]);
    }

    private function scopeNumber(string $scope): int
    {
        return match (mb_strtolower(trim($scope))) {
            'scope 1' => 1,
            'scope 2' => 2,
            // "Outside of Scopes" is where DEFRA puts biogenic CO2 — reported
            // alongside an inventory but never inside a scope total. Filed under
            // 3 so it is reachable, and distinguishable by its source_reference.
            default => 3,
        };
    }
}

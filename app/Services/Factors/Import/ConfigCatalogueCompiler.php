<?php

namespace App\Services\Factors\Import;

use App\Models\EmissionFactor;
use App\Models\EmissionSource;
use App\Models\FactorImport;
use App\Models\FactorOrganization;
use App\Services\Factors\BuiltInFactorCatalog;
use App\Support\Auditing;
use App\Support\Gwp;
use Illuminate\Support\Facades\DB;

/**
 * GHG-04 — compiles the built-in config catalogue into emission_factors rows.
 *
 * WHY
 *
 * Scope 1 and 2 entry priced activity from config/scope1_sources.php and
 * config/scope2_sources.php, while Manual Entry and Scope 3 priced it from the
 * emission_factors table. Two catalogues, and which one answered depended on
 * which screen the user typed on. Records from the config path stored provenance
 * as a LABEL STRING — "Built-in Scope 1 catalogue v1" — where the database path
 * stored an emission_factor_id. The first reads like provenance; only the second
 * is, because only the second can be followed to a row that can be inspected,
 * versioned and superseded.
 *
 * Compiling the config catalogue into rows is what lets the resolver point at one
 * place, which is what makes emission_factor_id always populated.
 *
 * THE NUMBERS DO NOT CHANGE
 *
 * Values come from BuiltInFactorCatalog::entries(), which prices through the same
 * factorFor() the entry pages use. A compiled row therefore carries exactly the
 * number that page would have calculated. That is the whole safety property of
 * this migration: switching the resolver must not restate a single figure.
 *
 * THE GRID IS NORMALISED
 *
 * All 13 grid-priced Scope 2 sources use the same regional factor and differ only
 * in what the electricity was for. They compile to ONE canonical source with a row
 * per (region, unit) — 44 rows, not 572 — so a record's emission_factor_id points
 * at "DEWA 2023, 0.3876 kgCO2/kWh", which is the real answer, rather than at a
 * synthetic per-source row. This is also most of GHG-18: 22 regional grid factors
 * that existed only in a config file become queryable rows.
 *
 * ATTRIBUTION IS NOT GUESSED
 *
 * Publisher comes from the catalogue's own citation. About 35 entries cite nobody;
 * those get dataset_name "Built-in (unattributed)" and NO organisation rather than
 * being filed under IPCC on a hunch. An assurer would far rather read "unknown"
 * than a confident wrong citation.
 */
class ConfigCatalogueCompiler
{
    public const DATASET = 'Built-in catalogue';

    public const UNATTRIBUTED = 'Built-in (unattributed)';

    /**
     * Publisher codes matched against the catalogue's citation, in priority
     * order, mapped to the name used if the organisation has to be created.
     *
     * Order matters. DEFRA and EPA are checked before IPCC because a note naming
     * both — "DEFRA 2025, per IPCC method" — belongs to the body that published
     * the number, not the body whose method produced it.
     *
     * Several of these are NOT in FactorOrganizationsSeeder, which ships only
     * IPCC, DEFRA, EPA, COUNTRY, EGRID and GWP. The 22 regional grid factors cite
     * IEA, DEWA and DESNZ, and with no matching row every one of them was
     * reported as unattributed — 42 factors losing a citation they actually had.
     * Created on demand rather than requiring a seeder change, so a new
     * publisher appearing in the catalogue cannot silently go unattributed.
     */
    private const ATTRIBUTION = [
        // DESNZ before DEFRA: it is the same UK department after its rename, and
        // a note saying "DESNZ 2024" should file under the existing DEFRA row
        // rather than mint a second organisation for the same body.
        'DESNZ' => ['code' => 'DEFRA', 'name' => 'DEFRA/DESNZ (UK Government GHG Conversion Factors)'],
        'DEFRA' => ['code' => 'DEFRA', 'name' => 'DEFRA/DESNZ (UK Government GHG Conversion Factors)'],
        'EGRID' => ['code' => 'EGRID', 'name' => 'US EPA eGRID'],
        'EPA' => ['code' => 'EPA', 'name' => 'US EPA (Environmental Protection Agency)'],
        'DEWA' => ['code' => 'DEWA', 'name' => 'Dubai Electricity & Water Authority'],
        'IEA' => ['code' => 'IEA', 'name' => 'International Energy Agency'],
        'IPCC' => ['code' => 'IPCC', 'name' => 'IPCC (Intergovernmental Panel on Climate Change)'],
    ];

    /**
     * @param  bool  $pretend  Compile and report without writing.
     * @return array{entries:int, written:int, sources:int, superseded:int, unattributed:int, import:?FactorImport}
     */
    public function compile(BuiltInFactorCatalog $catalog, bool $pretend = false): array
    {
        $entries = $catalog->entries();

        $result = [
            'entries' => count($entries),
            'written' => 0,
            'sources' => 0,
            'superseded' => 0,
            'unattributed' => 0,
            'import' => null,
        ];

        if ($pretend) {
            foreach ($entries as $entry) {
                if ($this->organisationFor($entry) === null) {
                    $result['unattributed']++;
                }
            }
            $result['written'] = count($entries);

            return $result;
        }

        // Reference data, not a person's edit — see App\Support\Auditing.
        return Auditing::without(function () use ($entries, $result) {
            $import = $this->openImport();

            DB::transaction(function () use ($entries, $import, &$result) {
                foreach ($entries as $entry) {
                    $outcome = $this->writeEntry($entry, $import);

                    $result['written'] += 1;
                    $result['sources'] += $outcome['source_created'];
                    $result['superseded'] += $outcome['superseded'];
                    $result['unattributed'] += $outcome['unattributed'];
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

    private function openImport(): FactorImport
    {
        return FactorImport::create([
            'dataset_name' => self::DATASET,
            // The catalogue's own declared revision, not a hand-maintained
            // constant. config/scope1_sources.php carries 'version', and
            // FactorCatalogueVersionTest fails if the numbers move without it.
            'dataset_version' => (string) config('scope1_sources.version', 'unversioned'),
            'organization_id' => null,
            'source_file' => 'config/scope1_sources.php + config/scope2_sources.php',
            'source_file_hash' => $this->catalogueHash(),
            'source_url' => null,
            'gwp_version' => Gwp::factorBasis(),
            'imported_at' => now(),
            'imported_by' => auth()->user()?->name ?? 'console',
        ]);
    }

    /**
     * A hash over the two config files, so a compiled row can be tied to the
     * exact catalogue content that produced it — the same guarantee the
     * published-file importers get from hashing their .xlsx.
     */
    private function catalogueHash(): string
    {
        $parts = [];

        foreach (['scope1_sources.php', 'scope2_sources.php'] as $file) {
            $path = config_path($file);
            $parts[] = is_file($path) ? hash_file('sha256', $path) : 'missing';
        }

        return hash('sha256', implode('|', $parts));
    }

    /**
     * @param  array{scope:int, source:string, description:?string, unit:string, value:float, reference:?string, region:?string, is_grid:bool, co2:?float, ch4:?float, n2o:?float, ncv:?float}  $entry
     * @return array{source_created:int, superseded:int, unattributed:int}
     */
    private function writeEntry(array $entry, FactorImport $import): array
    {
        $organisation = $this->organisationFor($entry);
        $sourceCreated = 0;

        $source = EmissionSource::firstOrCreate(
            ['name' => $entry['source']],
            ['scope' => $entry['scope'], 'description' => $entry['description'] ?? $entry['reference']]
        );

        if ($source->wasRecentlyCreated) {
            $sourceCreated = 1;
        }

        $datasetName = $organisation === null ? self::UNATTRIBUTED : self::DATASET;

        $superseded = $this->supersedePrevious($source->id, $entry, $datasetName);

        $attributes = [
            'emission_source_id' => $source->id,
            'organization_id' => $organisation?->id,
            'factor_import_id' => $import->id,
            'unit' => $entry['unit'],
            'factor_value' => $entry['value'],
            // BuiltInFactorCatalog::factorFor() divides by 1000, so what arrives
            // here is already tonnes. Stated rather than assumed — this column is
            // the only thing distinguishing these rows from DEFRA's kg ones.
            'factor_unit' => EmissionFactor::UNIT_TONNE,
            'region' => $entry['region'],
            'dataset_name' => $datasetName,
            'dataset_version' => $import->dataset_version,
            'gwp_version' => $import->gwp_version,
            'source_reference' => $entry['reference'] ? mb_substr($entry['reference'], 0, 255) : null,
            'valid_from' => now()->toDateString(),
            'is_active' => true,
        ];

        // Combustion components, stored raw as the catalogue states them — co2 in
        // kg per unit, ch4/n2o in kg per TJ, ncv in TJ per unit — because that is
        // what MRV recomputes from, and converting on the way in would lose the
        // published basis.
        //
        // ONLY when the catalogue genuinely decomposes the factor, which a
        // non-zero NCV is the marker for. Distance-based rows do not:
        //
        //     Van - Diesel [litres]  co2=2.676  ch4=3.90  ncv=0.0000359
        //     Van - Diesel [km]      co2=0.317  ch4=0     ncv=0
        //
        // That 0.317 is DEFRA's kgCO2e PER KM — a composite that already contains
        // the methane and nitrous oxide contributions. Writing it to co2_factor
        // would put a CO2e figure in a column meaning "CO2 only", and pair it
        // with ch4 = n2o = 0. The total stays correct; the gas attribution does
        // not, and MRV reads exactly these columns — it would report a vehicle
        // fleet as 100% CO2 with zero CH4 and N2O.
        //
        // Leaving them null says "this factor is not decomposed", which is true.
        // A composite mislabelled as CO2 is a confident wrong answer.
        if ($entry['ncv'] !== null && $entry['ncv'] > 0) {
            $attributes['co2_factor'] = $entry['co2'];
            $attributes['ch4_factor'] = $entry['ch4'];
            $attributes['n2o_factor'] = $entry['n2o'];
            $attributes['net_calorific_value'] = $entry['ncv'];
            $attributes['ncv_unit'] = 'TJ per '.$entry['unit'];
        }

        EmissionFactor::create($attributes);

        return [
            'source_created' => $sourceCreated,
            'superseded' => $superseded,
            'unattributed' => $organisation === null ? 1 : 0,
        ];
    }

    /**
     * Retire this catalogue's previous row for the same source, unit and region.
     *
     * Scoped to the built-in datasets so recompiling never touches DEFRA's or
     * EPA's imported rows, or a factor a client added themselves. Region is part
     * of the key because the grid holds one row per region for the same source
     * and unit — without it, compiling would retire 21 regions to leave the 22nd.
     */
    private function supersedePrevious(int $sourceId, array $entry, string $datasetName): int
    {
        return EmissionFactor::where('emission_source_id', $sourceId)
            ->where('unit', $entry['unit'])
            ->when($entry['region'] === null,
                fn ($q) => $q->whereNull('region'),
                fn ($q) => $q->where('region', $entry['region'])
            )
            ->whereIn('dataset_name', [self::DATASET, self::UNATTRIBUTED])
            ->where('is_active', true)
            ->update(['is_active' => false, 'valid_to' => now()->toDateString()]);
    }

    /**
     * The publisher named in the entry's own citation, or null when it names
     * none.
     *
     * Null is a real answer here, not a fallback. Roughly 35 catalogue entries
     * carry no attribution, and assigning them to IPCC because most of the
     * catalogue is IPCC would put a citation on a client's figure that nobody
     * published.
     */
    private function organisationFor(array $entry): ?FactorOrganization
    {
        $citation = mb_strtoupper((string) ($entry['reference'] ?? ''));

        if ($citation === '') {
            return null;
        }

        foreach (self::ATTRIBUTION as $needle => $publisher) {
            if (str_contains($citation, $needle)) {
                return $this->organisation($publisher['code'], $publisher['name']);
            }
        }

        return null;
    }

    /** @var array<string, FactorOrganization> */
    private array $organisations = [];

    private function organisation(string $code, string $name): FactorOrganization
    {
        return $this->organisations[$code] ??= FactorOrganization::firstOrCreate(
            ['code' => $code],
            ['name' => $name]
        );
    }
}

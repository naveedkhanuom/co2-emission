<?php

namespace App\Services\Factors;

use App\Support\Gwp;

/**
 * Derives an emission factor server-side from the built-in Scope 1 catalogue
 * (config/scope1_sources.php) — the same catalogue the entry page ships to the
 * browser.
 *
 * WHY THIS EXISTS
 *
 * The Scope 1 entry page computes CO2e in JavaScript and posts only the result.
 * EmissionFigureVerifier can only check a figure against `activity × factor`, so
 * with no factor stored it had nothing to check and the browser's number was
 * taken on trust — which is how 19 of 58 records ended up with no factor and no
 * provenance at all.
 *
 * Posting the factor from the browser alongside the total would not have fixed
 * that: both numbers would come from the same untrusted client, so a stale
 * bundle or a tampered payload produces a factor and a total that agree
 * perfectly. The server has to derive the factor itself, which is what this does.
 *
 * THE ARITHMETIC
 *
 * Mirrors calcCO2e() in resources/views/scope1_entry/script.blade.php exactly.
 * Because the per-unit factor is independent of quantity, it can be reduced to a
 * single tCO2e-per-unit number:
 *
 *   combustion   (co2 + ncv × ch4 × GWP_CH4 + ncv × n2o × GWP_N2O) / 1000
 *   fugitive     gwp / 1000        (gwpM3 when the unit is m3)
 *
 * where co2 is kgCO2 per unit, ncv is TJ per unit, and ch4/n2o are kg per TJ.
 *
 * GWPs come from App\Support\Gwp rather than the GWP_CH4 / GWP_N2O constants at
 * the top of the config. They agree today (both AR5: 28 and 265) — taking them
 * from Gwp means re-basing to AR6 moves this and config/gwp.php together instead
 * of letting them drift.
 */
class BuiltInFactorCatalog
{
    /**
     * Bump when the catalogue's numbers change, so a stored figure stays
     * traceable to the revision that produced it.
     */
    public const VERSION = 'v1';

    /** Sub-categories of config/scope1_sources.php that hold source lists. */
    private const SCOPE1_GROUPS = ['stationary', 'mobile', 'fugitive'];

    /** Sub-categories of config/scope2_sources.php that hold source lists. */
    private const SCOPE2_GROUPS = ['electricity', 'heating', 'cooling'];

    /**
     * kWh contained in one unit of purchased energy, keyed on the canonical unit
     * key. Mirrors KWH_PER_UNIT in resources/views/scope2_entry/script.blade.php;
     * the two must stay in step or the browser and the server will price the same
     * activity differently.
     *
     * Keyed on the unit key, never the display label: 'kWh' and 'kWh (thermal)'
     * are one unit under two labels, while 'ton' (tonnes steam) and 'ton-hr'
     * (Ton-hours) are different units whose labels both begin "ton".
     */
    private const KWH_PER_UNIT = [
        'kwh' => 1.0,
        'mwh' => 1000.0,
        'gj' => 277.778,
        'mmbtu' => 293.071,
        'ton-hr' => 3.517,
        'ton' => 694.4,
    ];

    /** @var array<string, array>|null */
    private ?array $index = null;

    /** @var array<string, array>|null */
    private ?array $scope2Index = null;

    /**
     * Resolve tCO2e per unit for an activity, or null when it cannot be derived.
     *
     * Returning null is a normal outcome, not an error: an unrecognised source,
     * a free-text "other" entry, or an ambiguous unit all land here, and the
     * caller must leave the record exactly as it would have been before.
     *
     * @param  int  $scope  GHG Protocol scope. 1 and 2 are supported; 3 is not —
     *                      its categories are spend- and form-driven and have no
     *                      single activity × factor shape to derive.
     * @param  string|null  $source  The catalogue source name as entered.
     * @param  string|null  $unit  Canonical unit key ("liters", "kg", "kWh", …).
     * @param  array{region?: string|null, ef_override?: float|int|string|null}  $context
     *                                                                                     Scope 2 only: the selected grid region, and a factor
     *                                                                                     the user entered by hand (custom region or override).
     */
    public function resolve(int $scope, ?string $source, ?string $unit, array $context = []): ?ResolvedFactor
    {
        if ($source === null || trim($source) === '') {
            return null;
        }

        if ($scope === 2) {
            return $this->resolveScope2($source, $unit, $context);
        }

        if ($scope !== 1) {
            return null;
        }

        $entry = $this->find($source, $unit);

        if ($entry === null) {
            return null;
        }

        $value = $this->factorFor($entry['src'], $entry['unit']);

        if ($value === null) {
            return null;
        }

        return new ResolvedFactor(
            value: $value,
            unit: (string) $entry['unit']['u'],
            source: (string) $entry['src']['name'],
            reference: $entry['src']['note'] ?? null,
            gwpVersion: Gwp::factorBasis(),
            catalogueVersion: self::VERSION,
        );
    }

    /**
     * Purchased energy: tCO2e per unit = kWh-per-unit × kgCO2e-per-kWh / 1000.
     *
     * Mirrors calcCO2e() in resources/views/scope2_entry/script.blade.php. Two
     * inputs live only in that form and must be passed in:
     *
     *  - region       which grid factor an `isGrid` source is priced at, by NAME
     *                 (an index would repoint if the config were reordered)
     *  - ef_override  a kgCO2/kWh the user typed, either for the "Custom" grid
     *                 region or through the EF-override control
     *
     * @param  array{region?: string|null, ef_override?: float|int|string|null}  $context
     */
    private function resolveScope2(string $source, ?string $unit, array $context): ?ResolvedFactor
    {
        $entry = $this->findScope2($source, $unit);

        if ($entry === null) {
            return null;
        }

        $src = $entry['src'];
        $unitKey = (string) ($entry['unit']['u'] ?? '');
        $kwhPerUnit = self::KWH_PER_UNIT[$this->normalise($unitKey)] ?? null;

        // A unit with no kWh conversion cannot be priced. Refusing is the whole
        // point: treating it as kWh is the silent 277x undercount this replaced.
        if ($kwhPerUnit === null) {
            return null;
        }

        $override = $context['ef_override'] ?? null;
        $userSupplied = $override !== null && $override !== '';

        if ($userSupplied) {
            $ef = (float) $override;
            $reference = 'User-entered factor '.$ef.' kgCO2e/kWh';
        } elseif (! empty($src['isGrid'])) {
            $grid = $this->gridFactor($context['region'] ?? null);

            // A grid source priced without a region, at an unknown region, or at
            // the "Custom" row with nothing entered, has no factor to use.
            if ($grid === null) {
                return null;
            }

            $ef = (float) $grid['co2'];
            $reference = sprintf('Grid factor %s — %s', $grid['region'], $grid['src'] ?? 'unspecified');
        } elseif (array_key_exists('efPerKwh', $src)) {
            $ef = (float) $src['efPerKwh'];
            $reference = $src['note'] ?? null;
        } else {
            return null;
        }

        return new ResolvedFactor(
            value: $kwhPerUnit * $ef / 1000,
            unit: $unitKey,
            source: (string) $src['name'],
            reference: $reference,
            gwpVersion: Gwp::factorBasis(),
            catalogueVersion: self::VERSION,
            catalogue: 'Scope 2',
            userSupplied: $userSupplied,
        );
    }

    /**
     * The grid row for a region name, or null when it cannot be priced.
     *
     * The "Custom (enter manually)" row carries co2 = 0 as a placeholder; without
     * an accompanying override it means "the user has not said yet", not "zero
     * emissions", so it resolves to null rather than silently pricing at zero.
     *
     * @return array{region: string, co2: float|int, src?: string}|null
     */
    private function gridFactor(?string $region): ?array
    {
        if ($region === null || trim($region) === '') {
            return null;
        }

        $wanted = $this->normalise($region);

        foreach (config('scope2_sources.grid_ef', []) as $row) {
            if ($this->normalise((string) ($row['region'] ?? '')) !== $wanted) {
                continue;
            }

            if (str_contains($this->normalise((string) $row['region']), 'custom')) {
                return null;
            }

            return $row;
        }

        return null;
    }

    /**
     * Locate a source and the unit row to price it in.
     *
     * @return array{src: array, unit: array, group: string}|null
     */
    /**
     * Every priceable entry in the built-in catalogue, with both its computed
     * tCO2e-per-unit AND the raw components behind it.
     *
     * Exists so the catalogue can be COMPILED into emission_factors rows without
     * anyone re-implementing factorFor(). The pricing formula has one home, and
     * a compiled row has to carry the same number the entry page would have
     * calculated — otherwise switching the resolver to the database silently
     * changes every figure.
     *
     * Grid-priced Scope 2 sources are emitted once per REGION, under a single
     * canonical source name, rather than once per (source x region). All 13 grid
     * sources — EV charging, green tariff, landlord-supplied and the rest — use
     * the same regional factor and differ only in what the electricity was for.
     * Emitting the cross product would store the same 22 numbers 26 times and
     * make a record's emission_factor_id point at a synthetic row, when the
     * honest answer to "what factor produced this" is "DEWA 2023, 0.3876".
     *
     * @return array<int, array{
     *     scope:int, source:string, description:?string, unit:string,
     *     value:float, reference:?string, region:?string, is_grid:bool,
     *     co2:?float, ch4:?float, n2o:?float, ncv:?float
     * }>
     */
    public function entries(): array
    {
        $entries = [];

        foreach (self::SCOPE1_GROUPS as $group) {
            foreach (config("scope1_sources.{$group}", []) as $src) {
                foreach ($src['units'] ?? [] as $unit) {
                    $value = $this->factorFor($src, $unit);

                    if ($value === null) {
                        continue;
                    }

                    $entries[] = [
                        'scope' => 1,
                        'source' => (string) $src['name'],
                        'description' => $src['desc'] ?? null,
                        'unit' => (string) $unit['u'],
                        'value' => $value,
                        'reference' => $src['note'] ?? null,
                        'region' => null,
                        'is_grid' => false,
                        // Fugitive rows price straight off a GWP and carry no
                        // combustion components; recording zeros there would
                        // claim a breakdown that does not exist.
                        'co2' => empty($src['isFug']) ? (float) ($unit['co2'] ?? 0) : null,
                        'ch4' => empty($src['isFug']) ? (float) ($unit['ch4'] ?? 0) : null,
                        'n2o' => empty($src['isFug']) ? (float) ($unit['n2o'] ?? 0) : null,
                        'ncv' => empty($src['isFug']) ? (float) ($unit['ncv'] ?? 0) : null,
                    ];
                }
            }
        }

        foreach (self::SCOPE2_GROUPS as $group) {
            foreach (config("scope2_sources.{$group}", []) as $src) {
                foreach ($src['units'] ?? [] as $unit) {
                    $unitKey = (string) ($unit['u'] ?? '');
                    $kwhPerUnit = self::KWH_PER_UNIT[$this->normalise($unitKey)] ?? null;

                    if ($kwhPerUnit === null) {
                        continue;
                    }

                    if (empty($src['isGrid'])) {
                        if (! array_key_exists('efPerKwh', $src)) {
                            continue;
                        }

                        $entries[] = [
                            'scope' => 2,
                            'source' => (string) $src['name'],
                            'description' => $src['desc'] ?? null,
                            'unit' => $unitKey,
                            'value' => $kwhPerUnit * (float) $src['efPerKwh'] / 1000,
                            'reference' => $src['note'] ?? null,
                            'region' => null,
                            'is_grid' => false,
                            'co2' => null, 'ch4' => null, 'n2o' => null, 'ncv' => null,
                        ];
                    }
                }
            }
        }

        // The grid, once per region rather than once per grid source.
        foreach (config('scope2_sources.grid_ef', []) as $row) {
            $region = (string) ($row['region'] ?? '');
            $ef = (float) ($row['co2'] ?? 0);

            // "Custom (enter manually)" carries co2 = 0 as a placeholder meaning
            // "the user has not said yet", not "zero emissions".
            if ($region === '' || $ef <= 0 || str_contains($this->normalise($region), 'custom')) {
                continue;
            }

            foreach (self::GRID_UNITS as $unitKey) {
                $entries[] = [
                    'scope' => 2,
                    'source' => self::GRID_SOURCE,
                    'description' => 'Purchased grid electricity, priced by regional grid factor',
                    'unit' => $unitKey,
                    'value' => self::KWH_PER_UNIT[$this->normalise($unitKey)] * $ef / 1000,
                    'reference' => sprintf('Grid factor %s — %s', $region, $row['src'] ?? 'unspecified'),
                    'region' => $region,
                    'is_grid' => true,
                    'co2' => null, 'ch4' => null, 'n2o' => null, 'ncv' => null,
                ];
            }
        }

        return $entries;
    }

    /**
     * The canonical emission source that regional grid factors are filed under.
     *
     * A Scope 2 record still stores what the electricity was FOR in its own
     * emission_source column ("EV Charging", "Green Tariff"); this is what
     * priced it.
     */
    public const GRID_SOURCE = 'Purchased Electricity (Grid)';

    /** Units the 13 grid sources are offered in — all of them use these two. */
    private const GRID_UNITS = ['kWh', 'MWh'];

    /**
     * Is this Scope 2 source priced by grid region?
     *
     * Thirteen sources carry `isGrid` — EV charging, green tariff,
     * landlord-supplied and the rest — and none of them has a factor of its own:
     * the number comes entirely from which grid the electricity came off.
     *
     * Callers need this to REFUSE. A grid source with no region must not be
     * priced, and the failure it prevents is specific: without it, the library
     * matched a generic seeded electricity row and priced UAE electricity at
     * roughly a global average, then presented that as the client's own figure.
     * A wrong number that looks settled is worse than an unverified one.
     */
    public function isGridSource(?string $source): bool
    {
        if ($source === null || trim($source) === '') {
            return false;
        }

        $wanted = $this->normalise($source);

        if ($wanted === $this->normalise(self::GRID_SOURCE)) {
            return true;
        }

        foreach (self::SCOPE2_GROUPS as $group) {
            foreach (config("scope2_sources.{$group}", []) as $src) {
                if (! empty($src['isGrid']) && $this->normalise((string) $src['name']) === $wanted) {
                    return true;
                }
            }
        }

        return false;
    }

    private function find(string $source, ?string $unit): ?array
    {
        return $this->findIn($this->sourceIndex(), $source, $unit);
    }

    /**
     * Same lookup against the Scope 2 catalogue.
     *
     * @return array{src: array, unit: array, group: string}|null
     */
    private function findScope2(string $source, ?string $unit): ?array
    {
        return $this->findIn($this->scope2Index(), $source, $unit);
    }

    /**
     * @param  array<string, array>  $index
     * @return array{src: array, unit: array, group: string}|null
     */
    private function findIn(array $index, string $source, ?string $unit): ?array
    {
        $key = $this->normalise($source);
        $src = $index[$key] ?? null;

        if ($src === null) {
            return null;
        }

        $units = $src['units'] ?? [];

        if ($units === []) {
            return null;
        }

        if ($unit !== null && trim($unit) !== '') {
            $wanted = $this->normalise($unit);

            foreach ($units as $row) {
                // Match the canonical key first, then the display label, so a
                // record that stored "Liters (L)" still resolves.
                if ($this->normalise((string) ($row['u'] ?? '')) === $wanted
                    || $this->normalise((string) ($row['label'] ?? '')) === $wanted) {
                    return ['src' => $src, 'unit' => $row, 'group' => $src['_group']];
                }
            }

            // A unit was given and it is not one this source offers. Something is
            // wrong with the record; do not silently price it in a different unit.
            return null;
        }

        // No unit recorded. Only safe when the source offers exactly one — most
        // offer kg AND tonnes, or litres AND gallons, whose factors differ by
        // three orders of magnitude. Guessing between them would be worse than
        // leaving the record unverifiable.
        if (count($units) !== 1) {
            return null;
        }

        return ['src' => $src, 'unit' => $units[0], 'group' => $src['_group']];
    }

    /**
     * tCO2e per one unit, or null when the catalogue row carries no numbers.
     */
    private function factorFor(array $src, array $unit): ?float
    {
        if (! empty($src['isFug'])) {
            $gwp = (($unit['u'] ?? null) === 'm3' && isset($src['gwpM3']))
                ? $src['gwpM3']
                : ($src['gwp'] ?? null);

            return $gwp === null ? null : (float) $gwp / 1000;
        }

        // A combustion row must carry at least one of the numeric keys; a row
        // with none is a fugitive-shaped entry we should not price.
        if (! array_key_exists('co2', $unit) && ! array_key_exists('ncv', $unit)) {
            return null;
        }

        $co2 = (float) ($unit['co2'] ?? 0);
        $ncv = (float) ($unit['ncv'] ?? 0);
        $ch4 = (float) ($unit['ch4'] ?? 0);
        $n2o = (float) ($unit['n2o'] ?? 0);

        $gwpCh4 = Gwp::factor('ch4', Gwp::factorBasis());
        $gwpN2o = Gwp::factor('n2o', Gwp::factorBasis());

        // Zero is a legitimate answer here — a battery-electric fleet has no
        // direct Scope 1 emissions, and biogenic CO2 is deliberately not counted.
        return ($co2 + $ncv * $ch4 * $gwpCh4 + $ncv * $n2o * $gwpN2o) / 1000;
    }

    /**
     * Sources keyed by normalised name, built once per instance.
     *
     * @return array<string, array>
     */
    private function sourceIndex(): array
    {
        if ($this->index !== null) {
            return $this->index;
        }

        $index = [];
        $config = config('scope1_sources', []);

        foreach (self::SCOPE1_GROUPS as $group) {
            foreach ($config[$group] ?? [] as $src) {
                if (! isset($src['name'])) {
                    continue;
                }
                $src['_group'] = $group;
                $index[$this->normalise($src['name'])] = $src;
            }
        }

        return $this->index = $index;
    }

    /**
     * Scope 2 sources keyed by normalised name, built once per instance.
     *
     * @return array<string, array>
     */
    private function scope2Index(): array
    {
        if ($this->scope2Index !== null) {
            return $this->scope2Index;
        }

        $index = [];
        $config = config('scope2_sources', []);

        foreach (self::SCOPE2_GROUPS as $group) {
            foreach ($config[$group] ?? [] as $src) {
                if (! isset($src['name'])) {
                    continue;
                }
                $src['_group'] = $group;
                $index[$this->normalise($src['name'])] = $src;
            }
        }

        return $this->scope2Index = $index;
    }

    private function normalise(string $value): string
    {
        return mb_strtolower(trim($value));
    }
}

<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Converts a quantity between compatible units of measure.
 *
 * Why this exists: emission activity data is entered in many units (kWh vs MWh,
 * kg vs tonne, L vs m³). The single most common data-entry error in carbon
 * accounting is a unit mismatch — e.g. entering MWh against a per-kWh factor,
 * which overstates emissions 1000×. This service lets the entry forms normalise
 * a user's value to the unit the emission factor expects.
 *
 * Each dimension has a base unit; every unit declares its multiplier TO that
 * base. Conversion is: value × (from→base) ÷ (to→base).
 */
class UnitConverter
{
    /**
     * Canonical units per dimension → multiplier to the dimension's base unit.
     * The first unit listed in each group is the base (multiplier 1).
     */
    private const DIMENSIONS = [
        'energy' => [ // base: kWh
            'kWh'   => 1.0,
            'Wh'    => 0.001,
            'MWh'   => 1000.0,
            'GWh'   => 1_000_000.0,
            'J'     => 2.777_777_8e-7,
            'kJ'    => 2.777_777_8e-4,
            'MJ'    => 0.277_777_8,
            'GJ'    => 277.777_8,
            'TJ'    => 277_777.8,     // terajoule — common in EU-ETS / EAD NCV & EF
            'PJ'    => 277_777_778.0, // petajoule
            'BTU'   => 2.930_71e-4,
            'mmBTU' => 293.071,
            'therm' => 29.3001,
        ],
        'mass' => [ // base: kg
            'kg'    => 1.0,
            'g'     => 0.001,
            't'     => 1000.0,        // metric tonne
            'kt'    => 1_000_000.0,   // kilotonne
            'Gg'    => 1_000_000.0,   // gigagram (= 1000 t) — EU-ETS NCV denominator
            'lb'    => 0.453_592_37,
            'oz'    => 0.028_349_5,
        ],
        'volume' => [ // base: L
            'L'      => 1.0,
            'mL'     => 0.001,
            'm3'     => 1000.0,
            'gal_us' => 3.785_411_8,
            'gal_uk' => 4.546_09,
            'ft3'    => 28.316_8,
            'bbl'    => 158.987,      // oil barrel
        ],
        'distance' => [ // base: km
            'km'  => 1.0,
            'm'   => 0.001,
            'mi'  => 1.609_344,
            'nmi' => 1.852,
            'ft'  => 0.000_304_8,
        ],
        'area' => [ // base: m2
            'm2'   => 1.0,
            'ha'   => 10_000.0,
            'km2'  => 1_000_000.0,
            'ft2'  => 0.092_903,
            'acre' => 4046.86,
        ],
    ];

    /**
     * Lower-cased free-text variants → canonical unit key. Lets us accept what
     * users actually type (m³, tonnes, litre, kwh…).
     */
    private const ALIASES = [
        // energy
        'wh' => 'Wh', 'kwh' => 'kWh', 'kw h' => 'kWh', 'mwh' => 'MWh', 'gwh' => 'GWh',
        'j' => 'J', 'joule' => 'J', 'joules' => 'J', 'kj' => 'kJ', 'mj' => 'MJ', 'gj' => 'GJ',
        'tj' => 'TJ', 'terajoule' => 'TJ', 'pj' => 'PJ', 'petajoule' => 'PJ',
        'btu' => 'BTU', 'mmbtu' => 'mmBTU', 'therm' => 'therm', 'therms' => 'therm',
        // mass
        'g' => 'g', 'gram' => 'g', 'grams' => 'g', 'kg' => 'kg', 'kgs' => 'kg', 'kilogram' => 'kg', 'kilograms' => 'kg',
        't' => 't', 'tonne' => 't', 'tonnes' => 't', 'ton' => 't', 'tons' => 't', 'mt' => 't', 'metric ton' => 't',
        'kt' => 'kt', 'kilotonne' => 'kt', 'kilotonnes' => 'kt',
        'gg' => 'Gg', 'gigagram' => 'Gg', 'gigagrams' => 'Gg',
        'lb' => 'lb', 'lbs' => 'lb', 'pound' => 'lb', 'pounds' => 'lb', 'oz' => 'oz', 'ounce' => 'oz', 'ounces' => 'oz',
        // volume
        'l' => 'L', 'litre' => 'L', 'litres' => 'L', 'liter' => 'L', 'liters' => 'L',
        'ml' => 'mL', 'millilitre' => 'mL',
        'm3' => 'm3', 'm³' => 'm3', 'cubic metre' => 'm3', 'cubic meter' => 'm3', 'cbm' => 'm3',
        'gal' => 'gal_us', 'gallon' => 'gal_us', 'gallons' => 'gal_us', 'gal_us' => 'gal_us',
        'us gallon' => 'gal_us', 'gal_uk' => 'gal_uk', 'imperial gallon' => 'gal_uk',
        'ft3' => 'ft3', 'cubic foot' => 'ft3', 'cubic feet' => 'ft3', 'cf' => 'ft3',
        'bbl' => 'bbl', 'barrel' => 'bbl', 'barrels' => 'bbl',
        // distance
        'm' => 'm', 'metre' => 'm', 'meter' => 'm', 'metres' => 'm', 'meters' => 'm',
        'km' => 'km', 'kilometre' => 'km', 'kilometer' => 'km', 'kms' => 'km',
        'mi' => 'mi', 'mile' => 'mi', 'miles' => 'mi', 'nmi' => 'nmi', 'nautical mile' => 'nmi',
        'ft' => 'ft', 'foot' => 'ft', 'feet' => 'ft',
        // area
        'm2' => 'm2', 'm²' => 'm2', 'sqm' => 'm2', 'square metre' => 'm2', 'square meter' => 'm2',
        'ha' => 'ha', 'hectare' => 'ha', 'hectares' => 'ha', 'km2' => 'km2', 'km²' => 'km2',
        'ft2' => 'ft2', 'sqft' => 'ft2', 'square foot' => 'ft2', 'square feet' => 'ft2',
        'acre' => 'acre', 'acres' => 'acre',
    ];

    /**
     * Resolve a free-text unit to its canonical key, or null if unknown.
     */
    public function normalize(?string $unit): ?string
    {
        if ($unit === null) {
            return null;
        }

        $key = strtolower(trim($unit));
        $key = preg_replace('/\s+/', ' ', $key);

        if (isset(self::ALIASES[$key])) {
            return self::ALIASES[$key];
        }

        // Already canonical?
        foreach (self::DIMENSIONS as $units) {
            foreach (array_keys($units) as $canonical) {
                if (strtolower($canonical) === $key) {
                    return $canonical;
                }
            }
        }

        return null;
    }

    /**
     * Which dimension (energy/mass/…) a unit belongs to, or null.
     */
    public function dimensionOf(?string $unit): ?string
    {
        $canonical = $this->normalize($unit);
        if ($canonical === null) {
            return null;
        }

        foreach (self::DIMENSIONS as $dimension => $units) {
            if (isset($units[$canonical])) {
                return $dimension;
            }
        }

        return null;
    }

    /**
     * Can a value in $from be converted to $to (same dimension)?
     */
    public function areCompatible(?string $from, ?string $to): bool
    {
        $df = $this->dimensionOf($from);
        $dt = $this->dimensionOf($to);

        return $df !== null && $df === $dt;
    }

    /**
     * Convert $value from one unit to another.
     *
     * @throws InvalidArgumentException if either unit is unknown or they are
     *                                  not in the same dimension.
     */
    public function convert(float $value, string $from, string $to): float
    {
        $cFrom = $this->normalize($from);
        $cTo   = $this->normalize($to);

        if ($cFrom === null) {
            throw new InvalidArgumentException("Unknown unit: {$from}");
        }
        if ($cTo === null) {
            throw new InvalidArgumentException("Unknown unit: {$to}");
        }

        $dimension = $this->dimensionOf($cFrom);
        if ($dimension !== $this->dimensionOf($cTo)) {
            throw new InvalidArgumentException("Cannot convert {$from} to {$to}: different unit types.");
        }

        if ($cFrom === $cTo) {
            return $value;
        }

        $units = self::DIMENSIONS[$dimension];

        return $value * $units[$cFrom] / $units[$cTo];
    }

    /**
     * Like convert(), but returns null instead of throwing on a bad/incompatible
     * unit. Convenient for "convert if possible, otherwise leave as-is" flows.
     */
    public function tryConvert(float $value, ?string $from, ?string $to): ?float
    {
        if (!$this->areCompatible($from, $to)) {
            return null;
        }

        return $this->convert($value, $from, $to);
    }

    /**
     * Split a compound "rate" unit like "TJ/Gg" or "tCO2 / TJ" into its numerator
     * and denominator parts. Accepts "/", " per ", and unicode division slashes.
     *
     * @return array{num: string, den: string}|null
     */
    public function parseRate(?string $unit): ?array
    {
        if ($unit === null) {
            return null;
        }

        $normalised = str_replace(['∕', '⁄', ' per ', ' PER '], '/', $unit);
        $parts = explode('/', $normalised);
        if (count($parts) !== 2) {
            return null;
        }

        $num = trim($parts[0]);
        $den = trim($parts[1]);
        if ($num === '' || $den === '') {
            return null;
        }

        return ['num' => $num, 'den' => $den];
    }

    /**
     * All supported units grouped by dimension (canonical keys), for UI lists.
     *
     * @return array<string, string[]>
     */
    public function units(): array
    {
        $out = [];
        foreach (self::DIMENSIONS as $dimension => $units) {
            $out[$dimension] = array_keys($units);
        }

        return $out;
    }
}

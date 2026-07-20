<?php

namespace App\Services\MRV;

use App\Models\EmissionFactor;
use App\Models\MrvSourceStream;
use App\Services\UnitConverter;
use InvalidArgumentException;

/**
 * Calculation-based emissions for the regulated MRV layer (EAD / EU-ETS).
 *
 * The global engine uses one combined factor: CO₂e = Activity × Factor. A regulated
 * MRV submission instead decomposes that into the EU-ETS combustion formula:
 *
 *     Emissions [t CO₂] = Activity × NCV × EF × Oxidation × Conversion
 *
 * where
 *   - Activity is fuel/material amount (mass, volume) or already an energy quantity,
 *   - NCV  (Net Calorific Value) is energy per amount, e.g. "TJ/Gg" or "MWh/m3",
 *   - EF   (Emission Factor) is mass-CO₂ per energy, e.g. "tCO2/TJ", OR — for process
 *          emissions — mass-CO₂ per amount, e.g. "tCO2/t",
 *   - Oxidation / Conversion are dimensionless (default 1).
 *
 * The result is always returned in tonnes CO₂(e), the platform's canonical unit.
 *
 * This service does the dimensional bookkeeping so a unit mismatch surfaces as an
 * error instead of a silently wrong number — the single most damaging class of bug
 * in carbon accounting.
 */
class MrvCalculator
{
    public function __construct(private UnitConverter $units) {}

    /**
     * Compute the calculation-based emissions for a source stream, in tonnes CO₂.
     */
    public function co2eForStream(MrvSourceStream $stream): float
    {
        return $this->calculate(
            activity: (float) ($stream->activity_level ?? 0),
            activityUnit: $stream->activity_unit ?? '',
            ncv: $stream->net_calorific_value !== null ? (float) $stream->net_calorific_value : null,
            ncvUnit: $stream->ncv_unit,
            ef: (float) ($stream->emission_factor_value ?? 0),
            efUnit: $stream->ef_unit ?? '',
            oxidation: $stream->oxidation_factor !== null ? (float) $stream->oxidation_factor : 1.0,
            conversion: $stream->conversion_factor !== null ? (float) $stream->conversion_factor : 1.0,
        );
    }

    /**
     * The EU-ETS combustion / process formula with full unit normalisation.
     *
     * @param float       $activity    Activity data value.
     * @param string      $activityUnit Unit of the activity data (e.g. "t", "MWh", "m3").
     * @param float|null  $ncv         Net calorific value; null for process emissions
     *                                  or when activity is already an energy quantity.
     * @param string|null $ncvUnit     Compound NCV unit (e.g. "TJ/Gg"); required if $ncv given.
     * @param float       $ef          Emission factor value.
     * @param string      $efUnit      Compound EF unit (e.g. "tCO2/TJ" or "tCO2/t").
     * @param float       $oxidation   Oxidation factor (dimensionless, default 1).
     * @param float       $conversion  Conversion factor (dimensionless, default 1).
     *
     * @return float Emissions in tonnes CO₂(e).
     *
     * @throws InvalidArgumentException on a unit that is unknown or dimensionally
     *                                  incompatible with the chain.
     */
    public function calculate(
        float $activity,
        string $activityUnit,
        ?float $ncv,
        ?string $ncvUnit,
        float $ef,
        string $efUnit,
        float $oxidation = 1.0,
        float $conversion = 1.0,
    ): float {
        // 1. Determine the energy (or amount) quantity the EF will act on.
        if ($ncv !== null) {
            $rate = $this->units->parseRate($ncvUnit);
            if ($rate === null) {
                throw new InvalidArgumentException("NCV unit must be a rate like 'TJ/Gg', got: " . var_export($ncvUnit, true));
            }
            // Convert the activity into the NCV's denominator unit, then apply NCV.
            $activityInNcvDen = $this->units->convert($activity, $activityUnit, $rate['den']);
            $energy = $activityInNcvDen * $ncv;          // expressed in NCV numerator unit (e.g. TJ)
            $energyUnit = $rate['num'];
        } else {
            // No NCV: the EF is applied directly to the activity (energy quantity,
            // or — for process emissions — the material amount itself).
            $energy = $activity;
            $energyUnit = $activityUnit;
        }

        // 2. Apply the emission factor: EF = massCO2 / (energy or amount).
        $efRate = $this->units->parseRate($efUnit);
        if ($efRate === null) {
            throw new InvalidArgumentException("EF unit must be a rate like 'tCO2/TJ', got: " . var_export($efUnit, true));
        }

        $energyInEfDen = $this->units->convert($energy, $energyUnit, $efRate['den']);
        $massCo2 = $energyInEfDen * $ef * $oxidation * $conversion; // in EF numerator mass unit (e.g. tCO2)

        // 3. Normalise the CO₂ mass to tonnes.
        $massUnit = $this->stripGasLabel($efRate['num']); // "tCO2e" -> "t"

        return $this->units->convert($massCo2, $massUnit, 't');
    }

    /**
     * Verify that an emission factor's combined value reconciles with its decomposed
     * components: factor_value (t CO₂e per unit) ≈ NCV × EF × Oxidation × Conversion
     * applied to one activity unit. Returns true when within the relative tolerance,
     * so the two representations can never silently diverge.
     *
     * Returns null when the factor lacks decomposed components (nothing to check).
     */
    public function reconciles(EmissionFactor $factor, float $tolerance = 0.01): ?bool
    {
        if (!$factor->hasDecomposedComponents()) {
            return null;
        }

        $combined = (float) $factor->factor_value;
        if ($combined <= 0) {
            return null;
        }

        try {
            $decomposed = $this->calculate(
                activity: 1.0,
                activityUnit: $factor->unit,
                ncv: (float) $factor->net_calorific_value,
                ncvUnit: $factor->ncv_unit,
                ef: (float) $factor->ef_per_energy,
                efUnit: $factor->ef_per_energy_unit ?? '',
                oxidation: $factor->oxidation_factor !== null ? (float) $factor->oxidation_factor : 1.0,
                conversion: $factor->conversion_factor !== null ? (float) $factor->conversion_factor : 1.0,
            );
        } catch (InvalidArgumentException $e) {
            return false;
        }

        return abs($decomposed - $combined) / $combined <= $tolerance;
    }

    /**
     * Strip a CO₂/CO₂e gas label from a mass unit so it can be normalised.
     * "tCO2e" -> "t", "kg CO₂" -> "kg".
     */
    private function stripGasLabel(string $unit): string
    {
        $cleaned = preg_replace('/\s*co(?:2|₂)e?\s*/i', '', $unit);

        return trim((string) $cleaned);
    }
}

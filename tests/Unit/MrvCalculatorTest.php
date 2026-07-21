<?php

namespace Tests\Unit;

use App\Models\EmissionFactor;
use App\Services\MRV\MrvCalculator;
use App\Services\UnitConverter;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MrvCalculatorTest extends TestCase
{
    private MrvCalculator $calc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calc = new MrvCalculator(new UnitConverter());
    }

    /**
     * The crude-oil illustration from the EAD template (sheet 3d2):
     * NCV 42.3 TJ/Gg, EF 73.3 tCO2/TJ, oxidation 1.
     * 1000 t crude oil = 1 Gg → 42.3 TJ → × 73.3 = 3100.59 tCO2.
     */
    public function test_combustion_with_ncv_matches_ead_example(): void
    {
        $result = $this->calc->calculate(
            activity: 1000, activityUnit: 't',
            ncv: 42.3, ncvUnit: 'TJ/Gg',
            ef: 73.3, efUnit: 'tCO2/TJ',
        );

        $this->assertEqualsWithDelta(3100.59, $result, 1e-2);
    }

    /** Oxidation factor scales the result linearly. */
    public function test_oxidation_factor_is_applied(): void
    {
        $result = $this->calc->calculate(
            activity: 1000, activityUnit: 't',
            ncv: 42.3, ncvUnit: 'TJ/Gg',
            ef: 73.3, efUnit: 'tCO2/TJ',
            oxidation: 0.99,
        );

        $this->assertEqualsWithDelta(3100.59 * 0.99, $result, 1e-2);
    }

    /** Activity already in energy units → no NCV needed. */
    public function test_energy_activity_without_ncv(): void
    {
        // 10,000 MWh natural gas × 0.18 tCO2/MWh = 1800 tCO2.
        $result = $this->calc->calculate(
            activity: 10000, activityUnit: 'MWh',
            ncv: null, ncvUnit: null,
            ef: 0.18, efUnit: 'tCO2/MWh',
        );

        $this->assertEqualsWithDelta(1800.0, $result, 1e-6);
    }

    /** Process emissions: EF is mass-CO2 per material amount (no energy). */
    public function test_process_emissions_mass_per_mass(): void
    {
        // 1000 t cement clinker × 0.525 tCO2/t = 525 tCO2.
        $result = $this->calc->calculate(
            activity: 1000, activityUnit: 't',
            ncv: null, ncvUnit: null,
            ef: 0.525, efUnit: 'tCO2/t',
        );

        $this->assertEqualsWithDelta(525.0, $result, 1e-6);
    }

    /** EF numerator in kg CO2 is normalised back to tonnes. */
    public function test_ef_in_kg_is_normalised_to_tonnes(): void
    {
        // 1000 GJ × 56.1 kgCO2/GJ = 56,100 kgCO2 = 56.1 tCO2.
        $result = $this->calc->calculate(
            activity: 1000, activityUnit: 'GJ',
            ncv: null, ncvUnit: null,
            ef: 56.1, efUnit: 'kgCO2/GJ',
        );

        $this->assertEqualsWithDelta(56.1, $result, 1e-6);
    }

    /** Unit families are interchangeable as long as dimensions line up. */
    public function test_unit_normalisation_across_families(): void
    {
        // Same physical fuel expressed via NCV in MWh/t instead of TJ/Gg.
        // 500 t × 11.75 MWh/t = 5875 MWh × 0.2 tCO2/MWh = 1175 tCO2.
        $result = $this->calc->calculate(
            activity: 500, activityUnit: 't',
            ncv: 11.75, ncvUnit: 'MWh/t',
            ef: 0.2, efUnit: 'tCO2/MWh',
        );

        $this->assertEqualsWithDelta(1175.0, $result, 1e-6);
    }

    public function test_throws_on_dimensionally_incompatible_chain(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Mass activity with an energy-based EF and no NCV to bridge them.
        $this->calc->calculate(
            activity: 1000, activityUnit: 't',
            ncv: null, ncvUnit: null,
            ef: 0.18, efUnit: 'tCO2/MWh',
        );
    }

    public function test_throws_when_ncv_unit_is_not_a_rate(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calc->calculate(
            activity: 1000, activityUnit: 't',
            ncv: 42.3, ncvUnit: 'TJ',   // missing denominator
            ef: 73.3, efUnit: 'tCO2/TJ',
        );
    }

    /** A factor whose decomposed parts match its combined value reconciles. */
    public function test_reconciles_true_when_components_match_combined(): void
    {
        // 1 t fuel → NCV 42.3 TJ/Gg → 0.0423 TJ → × 73.3 = 3.10059 tCO2/t.
        $factor = $this->makeFactor([
            'unit' => 't',
            'factor_value' => 3.10059,
            'net_calorific_value' => 42.3,
            'ncv_unit' => 'TJ/Gg',
            'ef_per_energy' => 73.3,
            'ef_per_energy_unit' => 'tCO2/TJ',
        ]);

        $this->assertTrue($this->calc->reconciles($factor));
    }

    public function test_reconciles_false_when_components_diverge(): void
    {
        $factor = $this->makeFactor([
            'unit' => 't',
            'factor_value' => 2.0,            // wrong: real decomposed value ≈ 3.10
            'net_calorific_value' => 42.3,
            'ncv_unit' => 'TJ/Gg',
            'ef_per_energy' => 73.3,
            'ef_per_energy_unit' => 'tCO2/TJ',
        ]);

        $this->assertFalse($this->calc->reconciles($factor));
    }

    public function test_reconciles_null_when_no_components(): void
    {
        $factor = $this->makeFactor(['unit' => 't', 'factor_value' => 2.0]);

        $this->assertNull($this->calc->reconciles($factor));
    }

    private function makeFactor(array $attributes): EmissionFactor
    {
        $factor = new EmissionFactor();
        $factor->setRawAttributes($attributes);

        return $factor;
    }
}

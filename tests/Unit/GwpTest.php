<?php

namespace Tests\Unit;

use App\Support\Gwp;
use Tests\TestCase;

/**
 * Global Warming Potential conversion.
 *
 * Every disclosure framework requires the GWP set to be stated, and the stated
 * set has to match the arithmetic that produced the figure. These pin both the
 * conversion values and the fallback behaviour, so a change to config/gwp.php
 * cannot silently re-base every number in the system.
 */
class GwpTest extends TestCase
{
    /** One tonne of CH4 is 28 tCO2e under AR5 and 27.9 under AR6. */
    public function test_methane_conversion_differs_by_assessment_report(): void
    {
        $this->assertSame(28.0, Gwp::toCo2e(['ch4' => 1], 'ar5'));
        $this->assertSame(27.9, Gwp::toCo2e(['ch4' => 1], 'ar6'));
        $this->assertSame(25.0, Gwp::toCo2e(['ch4' => 1], 'ar4'));
    }

    /** CO2 is the reference gas — always exactly 1. */
    public function test_co2_is_the_reference_gas(): void
    {
        foreach (Gwp::VERSIONS as $version) {
            $this->assertSame(1.0, Gwp::factor('co2', $version), "CO2 must be 1 under {$version}.");
        }
    }

    /** Mixed gas masses add up. */
    public function test_mixed_gases_sum_correctly(): void
    {
        // 1 t CO2 + 1 t CH4 + 1 t N2O under AR5 = 1 + 28 + 265.
        $this->assertSame(294.0, Gwp::toCo2e(['co2' => 1, 'ch4' => 1, 'n2o' => 1], 'ar5'));
    }

    /** An unrecognised gas contributes nothing rather than throwing. */
    public function test_unknown_gas_is_ignored(): void
    {
        $this->assertSame(1.0, Gwp::toCo2e(['co2' => 1, 'unobtainium' => 1000], 'ar5'));
    }

    /**
     * An invalid version must fall back to the default rather than silently
     * returning zero — a zero factor would wipe out real emissions.
     */
    public function test_invalid_version_falls_back_to_default(): void
    {
        $default = config('gwp.default');

        $this->assertSame($default, Gwp::normalize('not-a-report'));
        $this->assertSame($default, Gwp::normalize(null));
        $this->assertSame(Gwp::factor('ch4', $default), Gwp::factor('ch4', 'not-a-report'));
        $this->assertGreaterThan(0, Gwp::factor('ch4', 'not-a-report'));
    }

    public function test_version_validity(): void
    {
        $this->assertTrue(Gwp::isValid('ar5'));
        $this->assertFalse(Gwp::isValid('ar99'));
        $this->assertFalse(Gwp::isValid(null));
    }

    /**
     * Records are stamped with the basis the bundled factor tables were built
     * on, NOT the company's preferred set — otherwise a record would claim a
     * GWP set its own arithmetic did not use.
     */
    public function test_factor_basis_is_the_stamped_version(): void
    {
        $this->assertSame(config('gwp.factor_basis'), Gwp::factorBasis());
        $this->assertTrue(Gwp::isValid(Gwp::factorBasis()));
    }

    /** Every declared version has a full gas set and a human label. */
    public function test_every_version_is_complete(): void
    {
        foreach (Gwp::VERSIONS as $version) {
            foreach (['co2', 'ch4', 'n2o'] as $gas) {
                $this->assertGreaterThan(
                    0,
                    Gwp::factor($gas, $version),
                    "Missing {$gas} factor for {$version}."
                );
            }

            $this->assertNotSame(strtoupper($version), Gwp::label($version), "Missing label for {$version}.");
        }
    }

    /** Options are offered newest-first, which is what the UI relies on. */
    public function test_options_are_newest_first(): void
    {
        $this->assertSame(['ar6', 'ar5', 'ar4'], array_keys(Gwp::options()));
    }
}

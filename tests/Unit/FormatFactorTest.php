<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * GHG-28 — rendering emission factors on the GHG Protocol report.
 *
 * Factors span roughly 1.96e-7 to 12.2, so a fixed decimal count is wrong at one
 * end. number_format($factor, 2) printed diesel's 0.0026847 as "0.00" on a report
 * that shows activity, factor and CO2e in the same row — so the arithmetic
 * visibly failed to reconcile for a reader checking it.
 */
class FormatFactorTest extends TestCase
{
    public function test_a_small_factor_is_no_longer_flattened_to_zero(): void
    {
        // Diesel (Stationary) per litre.
        $this->assertSame('0.002685', format_factor(0.0026847237));
    }

    public function test_the_smallest_catalogue_factor_survives(): void
    {
        // Biogas per kWh — what a two-decimal format erased completely.
        $formatted = format_factor(1.9620e-7);

        $this->assertNotSame('0.00', $formatted);
        $this->assertGreaterThan(0, (float) $formatted);
    }

    public function test_a_large_factor_stays_readable(): void
    {
        // C2F6 (PFC-116) per kg.
        $this->assertSame('12.20', format_factor(12.2));
    }

    public function test_a_grid_factor_keeps_its_precision(): void
    {
        // UAE (Abu Dhabi / ADWEC) expressed per kWh.
        $this->assertSame('0.0004041', format_factor(0.0004041));
    }

    public function test_zero_renders_as_zero(): void
    {
        $this->assertSame('0.00', format_factor(0));
        $this->assertSame('0.00', format_factor(0.0));
    }

    /**
     * Columns of factors should still line up, so never fewer than two decimals.
     */
    public function test_at_least_two_decimals_are_always_shown(): void
    {
        foreach ([1, 5.0, 12.2, 100] as $value) {
            $this->assertMatchesRegularExpression(
                '/\.\d{2,}$/',
                format_factor($value),
                "format_factor({$value}) dropped below two decimals."
            );
        }
    }

    public function test_significant_figures_are_configurable(): void
    {
        $this->assertSame('0.0026847', format_factor(0.0026847237, 5));
    }

    /**
     * Whatever it prints must still reconcile: activity x printed factor should
     * land within rounding distance of the reported total.
     */
    public function test_the_printed_factor_still_reconciles_with_the_total(): void
    {
        $factor = 0.0026847237;
        $activity = 1000;
        $reported = $activity * $factor;

        $printed = (float) format_factor($factor);

        $this->assertEqualsWithDelta(
            $reported,
            $activity * $printed,
            $reported * 0.005,
            'A reader recomputing from the printed factor would not reach the reported total.'
        );
    }
}

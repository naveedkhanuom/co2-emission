<?php

namespace Tests\Unit;

use App\Services\Factors\BuiltInFactorCatalog;
use App\Support\Gwp;
use Tests\TestCase;

/**
 * The server-side Scope 1 factor derivation.
 *
 * The expected values here are computed from config/scope1_sources.php by hand
 * rather than read back from the class, so the test fails if the arithmetic
 * drifts away from what calcCO2e() does in the browser.
 */
class BuiltInFactorCatalogTest extends TestCase
{
    private BuiltInFactorCatalog $catalog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->catalog = new BuiltInFactorCatalog;
    }

    /**
     * Diesel (Stationary), litres:
     *   co2 2.676, ncv 3.59e-5, ch4 3, n2o 0.6, AR5 GWPs 28 / 265
     *   (2.676 + 3.59e-5 × 3 × 28 + 3.59e-5 × 0.6 × 265) / 1000
     */
    public function test_a_combustion_factor_matches_the_browser_formula(): void
    {
        $expected = (2.676 + 3.59e-5 * 3 * 28 + 3.59e-5 * 0.6 * 265) / 1000;

        $resolved = $this->catalog->resolve(1, 'Diesel (Stationary)', 'liters');

        $this->assertNotNull($resolved);
        $this->assertEqualsWithDelta($expected, $resolved->value, 1e-12);
        $this->assertSame('liters', $resolved->unit);
        $this->assertSame(Gwp::factorBasis(), $resolved->gwpVersion);
    }

    /**
     * The unit is what makes the factor meaningful: the same fuel in gallons is
     * a different number entirely. This is why an unknown unit must never be
     * guessed at.
     */
    public function test_the_same_source_in_a_different_unit_gives_a_different_factor(): void
    {
        $litres = $this->catalog->resolve(1, 'Diesel (Stationary)', 'liters');
        $gallons = $this->catalog->resolve(1, 'Diesel (Stationary)', 'gallons');

        $this->assertNotNull($litres);
        $this->assertNotNull($gallons);
        $this->assertGreaterThan($litres->value * 3, $gallons->value);
    }

    public function test_a_fugitive_factor_is_its_gwp_over_a_thousand(): void
    {
        // Methane Leakage: gwp 28.
        $resolved = $this->catalog->resolve(1, 'Methane Leakage', 'kg');

        $this->assertNotNull($resolved);
        $this->assertEqualsWithDelta(28 / 1000, $resolved->value, 1e-12);
    }

    /**
     * A gas measured by volume needs its volumetric GWP, not its mass GWP.
     */
    public function test_a_fugitive_gas_in_cubic_metres_uses_the_volumetric_gwp(): void
    {
        // Methane Leakage: gwp 28, gwpM3 18.76.
        $resolved = $this->catalog->resolve(1, 'Methane Leakage', 'm3');

        $this->assertNotNull($resolved);
        $this->assertEqualsWithDelta(18.76 / 1000, $resolved->value, 1e-12);
    }

    /**
     * Zero is a real answer, not a failure to resolve — a battery-electric fleet
     * has no direct Scope 1 emissions. Returning null here would leave the
     * record unverifiable for no reason.
     */
    public function test_a_zero_emission_source_resolves_to_zero_not_null(): void
    {
        $resolved = $this->catalog->resolve(1, 'Fleet - Battery Electric', 'km');

        $this->assertNotNull($resolved);
        $this->assertSame(0.0, $resolved->value);
    }

    public function test_an_unknown_source_resolves_to_null(): void
    {
        $this->assertNull($this->catalog->resolve(1, 'Unobtainium Combustion', 'kg'));
    }

    /**
     * A unit the source does not offer means the record is inconsistent. Pricing
     * it in some other unit would invent a number.
     */
    public function test_a_unit_the_source_does_not_offer_resolves_to_null(): void
    {
        $this->assertNull($this->catalog->resolve(1, 'Diesel (Stationary)', 'furlongs'));
    }

    /**
     * The dangerous ambiguity: most sources offer kg AND tonnes, or litres AND
     * gallons. With no unit recorded there is no safe choice, and the factors
     * differ by three orders of magnitude.
     */
    public function test_a_multi_unit_source_with_no_unit_resolves_to_null(): void
    {
        $this->assertNull($this->catalog->resolve(1, 'Diesel (Stationary)', null));
    }

    /**
     * When a source offers exactly one unit there is nothing to get wrong.
     */
    public function test_a_single_unit_source_resolves_without_a_unit(): void
    {
        $resolved = $this->catalog->resolve(1, 'Fleet - Battery Electric', null);

        $this->assertNotNull($resolved);
        $this->assertSame('km', $resolved->unit);
    }

    public function test_matching_ignores_case_and_surrounding_space(): void
    {
        $resolved = $this->catalog->resolve(1, '  diesel (STATIONARY) ', ' LITERS ');

        $this->assertNotNull($resolved);
        $this->assertSame('Diesel (Stationary)', $resolved->source);
    }

    /**
     * Older records may carry the display label rather than the canonical key.
     */
    public function test_a_display_label_resolves_as_well_as_the_unit_key(): void
    {
        $byKey = $this->catalog->resolve(1, 'Diesel (Stationary)', 'liters');
        $byLabel = $this->catalog->resolve(1, 'Diesel (Stationary)', 'Liters (L)');

        $this->assertNotNull($byLabel);
        $this->assertSame($byKey->value, $byLabel->value);
    }

    /**
     * Scope 2 depends on the selected grid region and an optional EF override,
     * neither of which the entry form posts. Until they are plumbed through, the
     * server cannot reproduce the browser's arithmetic and must not guess.
     */
    public function test_scope_2_is_not_resolved_yet_by_design(): void
    {
        $this->assertNull($this->catalog->resolve(2, 'Grid Electricity', 'kWh'));
    }

    public function test_scope_3_is_not_resolved(): void
    {
        $this->assertNull($this->catalog->resolve(3, 'Business Travel', 'km'));
    }

    public function test_an_empty_source_resolves_to_null(): void
    {
        $this->assertNull($this->catalog->resolve(1, '', 'kg'));
        $this->assertNull($this->catalog->resolve(1, null, 'kg'));
    }

    /**
     * The provenance string is what ends up on the record when no database
     * factor row can be locked.
     */
    public function test_the_resolved_factor_carries_its_citation(): void
    {
        $resolved = $this->catalog->resolve(1, 'Diesel (Stationary)', 'liters');

        $this->assertNotNull($resolved);
        $this->assertSame('IPCC 74100 kgCO2/TJ', $resolved->reference);
        $this->assertStringContainsString(BuiltInFactorCatalog::VERSION, $resolved->datasetLabel());
        $this->assertStringContainsString('IPCC 74100', $resolved->datasetLabel());
    }

    /**
     * Every catalogue entry must price without error — a source that resolves to
     * null is one the entry page can still write an unverifiable record for.
     */
    public function test_every_catalogue_source_resolves_in_every_unit_it_offers(): void
    {
        $unresolved = [];

        foreach (['stationary', 'mobile', 'fugitive'] as $group) {
            foreach (config("scope1_sources.{$group}", []) as $src) {
                foreach ($src['units'] ?? [] as $unit) {
                    if ($this->catalog->resolve(1, $src['name'], $unit['u']) === null) {
                        $unresolved[] = $src['name'].' / '.$unit['u'];
                    }
                }
            }
        }

        $this->assertSame([], $unresolved, 'Catalogue entries that cannot be priced.');
    }

    /**
     * Guards the reason for widening emission_records.emission_factor to
     * decimal(20,10): the smallest catalogue factor must survive being stored.
     */
    public function test_the_smallest_factor_survives_the_column_precision(): void
    {
        $smallest = null;

        foreach (['stationary', 'mobile', 'fugitive'] as $group) {
            foreach (config("scope1_sources.{$group}", []) as $src) {
                foreach ($src['units'] ?? [] as $unit) {
                    $resolved = $this->catalog->resolve(1, $src['name'], $unit['u']);
                    if ($resolved && $resolved->value > 0 && ($smallest === null || $resolved->value < $smallest)) {
                        $smallest = $resolved->value;
                    }
                }
            }
        }

        $this->assertNotNull($smallest);

        // emission_records.emission_factor is decimal(20,10).
        $stored = round($smallest, 10);
        $this->assertGreaterThan(0, $stored, 'Smallest factor rounds to zero at the column precision.');
        $this->assertEqualsWithDelta(
            $smallest,
            $stored,
            $smallest * 0.005,
            'Storing the smallest factor loses more than the verifier tolerance.'
        );
    }
}

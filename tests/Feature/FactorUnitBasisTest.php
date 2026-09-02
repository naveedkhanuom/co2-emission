<?php

namespace Tests\Feature;

use App\Models\EmissionFactor;
use App\Models\EmissionSource;
use App\Services\EmissionFigureVerifier;
use App\Services\Factors\LibraryFactorCatalog;
use Tests\TenantTestCase;

/**
 * `emission_factors.factor_value` did not have one unit, and nothing said so.
 *
 * BuiltInFactorCatalog divides by 1000 and stores tCO2e per activity unit.
 * DefraFlatFileImporter deliberately stores the publisher's own number, which
 * DEFRA publishes in kgCO2e, so that a row stays checkable against the file in
 * database/factors/sources/. Both wrote to the same column:
 *
 *     Diesel, litres — built-in catalogue   0.00268   tCO2e per litre
 *     Diesel, litres — DEFRA/DESNZ 2026     2.66155   kgCO2e per litre
 *
 * ResolvedFactor::$value is documented "tCO2e per one unit of activity", and
 * LibraryFactorCatalog assigned factor_value to it unconverted — so a
 * DEFRA-sourced factor priced activity 1000x too high, and the verifier
 * confirmed the result because activity x factor is arithmetically true no
 * matter what the two numbers measure.
 *
 * These tests pin the three parts of the fix: the basis is recorded, the
 * resolver converts, and a dimensional mismatch is caught.
 */
class FactorUnitBasisTest extends TenantTestCase
{
    private function source(string $name, int $scope): EmissionSource
    {
        return EmissionSource::create(['name' => $name, 'scope' => $scope]);
    }

    // ---------------------------------------------------------------- basis

    public function test_a_kilogram_factor_resolves_to_tonnes(): void
    {
        $source = $this->source('Unit Basis Diesel '.uniqid(), 1);

        EmissionFactor::create([
            'emission_source_id' => $source->id,
            'unit' => 'litres',
            'factor_value' => 2.66155,          // DEFRA's own number
            'factor_unit' => EmissionFactor::UNIT_KG,
            'dataset_name' => 'DEFRA/DESNZ',
            'dataset_version' => '2026',
            'is_active' => true,
        ]);

        $resolved = app(LibraryFactorCatalog::class)->resolve(1, $source->name, 'litres');

        $this->assertNotNull($resolved);

        // 2.66155 kgCO2e/litre is 0.00266155 tCO2e/litre. Before the fix this
        // returned 2.66155 and every figure priced with it was 1000x too big.
        $this->assertEqualsWithDelta(0.00266155, $resolved->value, 1e-9);
    }

    public function test_a_tonne_factor_is_passed_through_unchanged(): void
    {
        $source = $this->source('Unit Basis Coal '.uniqid(), 1);

        EmissionFactor::create([
            'emission_source_id' => $source->id,
            'unit' => 'kg',
            'factor_value' => 0.0024311056,
            'factor_unit' => EmissionFactor::UNIT_TONNE,
            'dataset_name' => 'Built-in catalogue',
            'dataset_version' => '2026.1',
            'is_active' => true,
        ]);

        $resolved = app(LibraryFactorCatalog::class)->resolve(1, $source->name, 'kg');

        $this->assertNotNull($resolved);
        $this->assertEqualsWithDelta(0.0024311056, $resolved->value, 1e-12);
    }

    public function test_a_row_with_no_stated_basis_is_read_as_tonnes(): void
    {
        // The 271 legacy seeded rows carry no dataset and no basis. They were
        // compiled at the tonne basis, so history must keep reading that way —
        // a default of kg would divide every one of them by 1000.
        $source = $this->source('Unit Basis Legacy '.uniqid(), 1);

        $factor = EmissionFactor::create([
            'emission_source_id' => $source->id,
            'unit' => 'liters',
            'factor_value' => 0.00268,
            'is_active' => true,
        ]);

        $this->assertEqualsWithDelta(0.00268, $factor->valueInTonnes(), 1e-12);
    }

    // ------------------------------------------------------- unit matching

    public function test_the_locked_factor_is_the_one_priced_per_the_activity_unit(): void
    {
        // The sharp case for unit matching: one source carrying two rows with
        // the SAME numeric factor under different units. Matching on source name
        // and value alone cannot tell them apart, so it took whichever sorted
        // last — here the per-mile row, for an entry measured in litres.
        //
        // Both rows share a basis and a value so that conversion cannot be what
        // separates them; only the unit can.
        $source = $this->source('Average car — Petrol '.uniqid(), 1);

        $litres = EmissionFactor::create([
            'emission_source_id' => $source->id,
            'unit' => 'litres',
            'factor_value' => 0.25993,
            'factor_unit' => EmissionFactor::UNIT_TONNE,
            'dataset_name' => 'DEFRA/DESNZ',
            'dataset_version' => '2026',
            'is_active' => true,
        ]);

        // Created second, so it wins `orderByDesc('id')` and is what the old
        // matcher would have returned.
        $miles = EmissionFactor::create([
            'emission_source_id' => $source->id,
            'unit' => 'miles',
            'factor_value' => 0.25993,
            'factor_unit' => EmissionFactor::UNIT_TONNE,
            'dataset_name' => 'DEFRA/DESNZ',
            'dataset_version' => '2026',
            'is_active' => true,
        ]);

        $enriched = app(\App\Services\EmissionEnrichmentService::class)->enrich([
            'scope' => 1,
            'emission_source' => $source->name,
            'activity_data' => 23.789,
            'activity_unit' => 'litres',
            'emission_factor' => 0.25993,
            'co2e_value' => 6.1835,
        ]);

        $this->assertSame(
            $litres->id,
            $enriched['emission_factor_id'] ?? null,
            'A litres entry must lock the per-litre row, not the per-mile one.'
        );
        $this->assertNotSame($miles->id, $enriched['emission_factor_id'] ?? null);
    }

    public function test_the_live_mismatched_record_no_longer_locks_its_factor(): void
    {
        // Reproduces record #4 in the live acme database: 23.789 LITRES priced
        // by a DEFRA factor published per MILE, stored active with a locked
        // factor id and a passing verification.
        //
        // Two independent guards now refuse it — the unit does not match, and
        // the record's tonne-basis figure does not match the row's converted
        // kilogram value. Either alone is enough; the test asserts the outcome.
        $source = $this->source('Average car — Petrol '.uniqid(), 1);

        $miles = EmissionFactor::create([
            'emission_source_id' => $source->id,
            'unit' => 'miles',
            'factor_value' => 0.25993,
            'factor_unit' => EmissionFactor::UNIT_KG,
            'dataset_name' => 'DEFRA/DESNZ',
            'dataset_version' => '2026',
            'is_active' => true,
        ]);

        $enriched = app(\App\Services\EmissionEnrichmentService::class)->enrich([
            'scope' => 1,
            'emission_source' => $source->name,
            'activity_data' => 23.789,
            'activity_unit' => 'liter',
            'emission_factor' => 0.25993,
            'co2e_value' => 6.1835,
        ]);

        $this->assertNotSame(
            $miles->id,
            $enriched['emission_factor_id'] ?? null,
            'A per-mile factor must not be locked to an activity measured in litres.'
        );
    }

    public function test_a_matching_unit_still_locks_the_factor(): void
    {
        // The guard above must not cost provenance to correct entries.
        $source = $this->source('Unit Match Diesel '.uniqid(), 1);

        $factor = EmissionFactor::create([
            'emission_source_id' => $source->id,
            'unit' => 'litres',
            'factor_value' => 2.66155,
            'factor_unit' => EmissionFactor::UNIT_KG,
            'dataset_name' => 'DEFRA/DESNZ',
            'dataset_version' => '2026',
            'is_active' => true,
        ]);

        // The record stores tonnes, the row stores kg — the match has to be made
        // on a common basis, which is what broke when conversion was introduced.
        $enriched = app(\App\Services\EmissionEnrichmentService::class)->enrich([
            'scope' => 1,
            'emission_source' => $source->name,
            'activity_data' => 100,
            'activity_unit' => 'litres',
            'emission_factor' => 0.00266155,
            'co2e_value' => 0.266155,
        ]);

        $this->assertSame($factor->id, $enriched['emission_factor_id'] ?? null);
    }

    // ---------------------------------------------------- dimension check

    public function test_a_dimensional_mismatch_is_held_for_review(): void
    {
        $verifier = app(EmissionFigureVerifier::class);

        $data = $verifier->verify([
            'activity_data' => 23.789,
            'activity_unit' => 'liter',
            'emission_factor' => 0.25993,
            'co2e_value' => 6.1835,
            'status' => 'active',
        ], 'miles');

        $this->assertSame('draft', $data['status']);
        $this->assertStringContainsString('liter', (string) $data['notes']);
        $this->assertStringContainsString('miles', (string) $data['notes']);
    }

    public function test_arithmetic_alone_does_not_catch_a_dimensional_mismatch(): void
    {
        // Why the dimension check had to be added rather than tightened: the
        // arithmetic is exactly right, which is the whole problem.
        $verifier = app(EmissionFigureVerifier::class);

        $this->assertFalse(
            $verifier->isMaterialMismatch(6.1835, 23.789 * 0.25993)
        );
    }

    public function test_spelling_variants_of_the_same_unit_are_not_a_mismatch(): void
    {
        $verifier = app(EmissionFigureVerifier::class);

        foreach ([['litres', 'liters'], ['tonnes', 'tonne'], ['m³', 'm3'], ['kWh', 'kwh']] as [$activity, $factor]) {
            $data = $verifier->verify([
                'activity_data' => 10,
                'activity_unit' => $activity,
                'emission_factor' => 0.5,
                'co2e_value' => 5,
                'status' => 'active',
            ], $factor);

            $this->assertSame(
                'active',
                $data['status'],
                "'{$activity}' and '{$factor}' are the same unit and must not be held."
            );
        }
    }

    public function test_a_record_with_no_resolved_factor_is_not_held(): void
    {
        // Nothing to disagree with. These records are already visibly unverified
        // and holding them would flood the review queue.
        $verifier = app(EmissionFigureVerifier::class);

        $data = $verifier->verify([
            'activity_data' => 10,
            'activity_unit' => 'litres',
            'emission_factor' => 0.5,
            'co2e_value' => 5,
            'status' => 'active',
        ], null);

        $this->assertSame('active', $data['status']);
    }
}

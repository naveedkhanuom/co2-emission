<?php

namespace Tests\Feature;

use App\Models\EmissionFactor;
use App\Models\EmissionSource;
use App\Services\AI\FactorMatchingService;
use Tests\TenantTestCase;

/**
 * AI document extraction priced a fuel receipt 1000x too high.
 *
 * Reproduced from a real upload: a petrol receipt for 23.789 litres came back
 * as 55.456 tCO2e. The correct answer is 55.456 KILOGRAMS — 0.055 tCO2e. The
 * factor column on screen read 2331.16 kgCO2e/litre against a real DEFRA value
 * of 2.33116.
 *
 * FactorMatchingService returned `factor_value` straight from the column, with
 * a docblock asserting it was already tCO2e. That held while the library was
 * only the compiled built-in catalogue; it stopped holding when DEFRA — whose
 * rows are kgCO2e as published — was imported. DocumentEmissionExtractor then
 * multiplied by 1000 for display, giving the number a second thousandfold.
 *
 * The same defect as the one closed in LibraryFactorCatalog, in the second
 * resolver: a door that was missed.
 */
class AiExtractionFactorMatchTest extends TenantTestCase
{
    /** DEFRA's real "Aviation spirit" rows, which is what the receipt matched. */
    private function defraSourceWithBothUnits(): EmissionSource
    {
        $source = EmissionSource::create([
            'name' => 'Aviation spirit (Fuels > Liquid fuels) '.uniqid(),
            'scope' => 1,
        ]);

        EmissionFactor::create([
            'emission_source_id' => $source->id,
            'unit' => 'litres',
            'factor_value' => 2.33116,
            'factor_unit' => EmissionFactor::UNIT_KG,
            'dataset_name' => 'DEFRA/DESNZ',
            'dataset_version' => '2026',
            'is_active' => true,
        ]);

        EmissionFactor::create([
            'emission_source_id' => $source->id,
            'unit' => 'tonnes',
            'factor_value' => 3193.6948,
            'factor_unit' => EmissionFactor::UNIT_KG,
            'dataset_name' => 'DEFRA/DESNZ',
            'dataset_version' => '2026',
            'is_active' => true,
        ]);

        return $source;
    }

    public function test_a_defra_factor_is_returned_in_tonnes(): void
    {
        $source = $this->defraSourceWithBothUnits();

        $match = app(FactorMatchingService::class)->match($source->name, 'liter', null);

        $this->assertNotNull($match);

        // 2.33116 kgCO2e/litre is 0.00233116 tCO2e/litre. Before the fix this
        // returned 2.33116, which the extractor then showed as 2331.16.
        $this->assertEqualsWithDelta(0.00233116, $match['factor_value'], 1e-10);
    }

    public function test_the_receipt_from_the_report_now_prices_correctly(): void
    {
        $source = $this->defraSourceWithBothUnits();

        $match = app(FactorMatchingService::class)->match($source->name, 'liter', null);

        // What DocumentEmissionExtractor::applyLibraryFactor() computes.
        $co2e = 23.789 * $match['factor_value'];
        $displayedFactor = $match['factor_value'] * 1000;

        // 55.456 kgCO2e, i.e. 0.055456 tCO2e — not the 55.456 tCO2e on screen.
        $this->assertEqualsWithDelta(0.0554560, $co2e, 1e-6);
        $this->assertEqualsWithDelta(2.33116, $displayedFactor, 1e-6);
    }

    public function test_a_tonne_basis_row_is_still_returned_unchanged(): void
    {
        // The compiled built-in catalogue is genuinely tonnes, and converting it
        // as well would swap one thousandfold error for another.
        $source = EmissionSource::create(['name' => 'Builtin Petrol '.uniqid(), 'scope' => 1]);

        EmissionFactor::create([
            'emission_source_id' => $source->id,
            'unit' => 'liters',
            'factor_value' => 0.00231,
            'factor_unit' => EmissionFactor::UNIT_TONNE,
            'dataset_name' => 'Built-in catalogue',
            'dataset_version' => '2026.1',
            'is_active' => true,
        ]);

        $match = app(FactorMatchingService::class)->match($source->name, 'litres', null);

        $this->assertEqualsWithDelta(0.00231, $match['factor_value'], 1e-10);
    }

    public function test_no_factor_is_returned_when_nothing_is_published_per_that_unit(): void
    {
        // The dangerous fallback: the service used to return ANY row for the
        // source when the unit did not match, so a quantity in litres could be
        // priced with a per-tonne factor — and be badged "Library", which tells
        // a reviewer the figure was checked against a real source.
        $source = EmissionSource::create(['name' => 'Tonnes Only Fuel '.uniqid(), 'scope' => 1]);

        EmissionFactor::create([
            'emission_source_id' => $source->id,
            'unit' => 'tonnes',
            'factor_value' => 3193.6948,
            'factor_unit' => EmissionFactor::UNIT_KG,
            'dataset_name' => 'DEFRA/DESNZ',
            'dataset_version' => '2026',
            'is_active' => true,
        ]);

        $match = app(FactorMatchingService::class)->match($source->name, 'litres', null);

        $this->assertNull(
            $match,
            'A per-tonne factor was offered for a quantity measured in litres.'
        );
    }

    public function test_the_right_unit_is_chosen_when_several_are_published(): void
    {
        $source = $this->defraSourceWithBothUnits();

        $match = app(FactorMatchingService::class)->match($source->name, 'litres', null);

        $this->assertSame('litres', $match['unit']);
    }

    public function test_a_caller_that_states_no_unit_still_gets_a_match(): void
    {
        // Unit is optional on the extracted item; requiring one would withdraw
        // library matching from every line that does not carry a unit.
        $source = $this->defraSourceWithBothUnits();

        $this->assertNotNull(app(FactorMatchingService::class)->match($source->name, null, null));
    }
}

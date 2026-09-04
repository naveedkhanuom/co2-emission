<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\EmissionFactor;
use App\Models\EmissionRecord;
use App\Models\Facilities;
use App\Models\MrvSourceStream;
use App\Models\User;
use App\Services\Factors\BuiltInFactorCatalog;
use App\Services\Factors\Import\ConfigCatalogueCompiler;
use App\Services\MRV\MrvCalculator;
use Spatie\Permission\PermissionRegistrar;
use Tests\TenantTestCase;

/**
 * GHG-04, last phase — the built-in catalogue decomposed into the EU-ETS form
 * the regulated MRV layer needs.
 *
 *     Emissions = Activity × NCV × EF × Oxidation × Conversion
 *
 * The tier system rates the accuracy of NCV and EF SEPARATELY, so a combined
 * tCO2e-per-litre factor cannot express a tier at all. `ef_per_energy` was
 * populated on zero rows, which meant sheet 3d2 of the EAD workbook filled for
 * no streams and every tier an operator recorded was a claim about a
 * calculation this application could not perform.
 *
 * THE PROPERTY THIS RESTS ON
 *
 * The EF is DERIVED — co2 / ncv — from the two numbers already committed in
 * config/scope1_sources.php, not transcribed from the IPCC value quoted in the
 * source's note. Derivation is the only form that cannot move a client's
 * total, because NCV × EF reproduces `co2` by construction. Taking the quoted
 * figure instead would give MRV a different answer from the entry page for the
 * same fuel — GHG-04's original defect, reopened inside a single row.
 *
 * test_every_decomposed_factor_reproduces_its_combined_value is that property.
 * If it fails, the decomposition has silently restated client figures.
 */
class EuEtsDecompositionTest extends TenantTestCase
{
    private function compile(): array
    {
        return app(ConfigCatalogueCompiler::class)
            ->compile(app(BuiltInFactorCatalog::class));
    }

    public function test_every_decomposed_factor_reproduces_its_combined_value(): void
    {
        $this->compile();

        $calculator = app(MrvCalculator::class);

        $decomposed = EmissionFactor::where('is_active', true)
            ->whereNotNull('ef_per_energy')
            ->whereNotNull('net_calorific_value')
            ->get();

        $this->assertGreaterThan(80, $decomposed->count(), 'Almost nothing was decomposed.');

        foreach ($decomposed as $factor) {
            // 1,000 units of activity through the EU-ETS formula...
            $euEts = $calculator->calculate(
                activity: 1000,
                activityUnit: $factor->unit,
                ncv: (float) $factor->net_calorific_value,
                ncvUnit: $factor->ncv_unit,
                ef: (float) $factor->ef_per_energy,
                efUnit: $factor->ef_per_energy_unit,
                oxidation: (float) $factor->oxidation_factor,
                conversion: (float) $factor->conversion_factor,
            );

            // ...must equal the CO2 the combined catalogue states for the same
            // 1,000 units. co2_factor is kg per unit; the formula returns tonnes.
            $combined = 1000 * (float) $factor->co2_factor / 1000;

            $this->assertEqualsWithDelta(
                $combined,
                $euEts,
                max(abs($combined) * 0.0001, 0.000001),
                "{$factor->unit} factor #{$factor->id}: the EU-ETS decomposition does not reproduce "
                .'the combined figure, so MRV would report a different number from the entry page.'
            );
        }
    }

    public function test_the_decomposition_carries_everything_the_formula_needs(): void
    {
        $this->compile();

        $factor = EmissionFactor::where('is_active', true)
            ->whereNotNull('ef_per_energy')
            ->whereNotNull('co2_factor')
            ->where('co2_factor', '>', 0)
            ->firstOrFail();

        $this->assertSame('tCO2/TJ', $factor->ef_per_energy_unit);
        $this->assertNotNull($factor->ncv_unit);
        $this->assertEquals(1.0, $factor->oxidation_factor);
        $this->assertEquals(1.0, $factor->conversion_factor);

        // The citation travels with the row, so a regulated submission can say
        // where its calculation factors came from without reading config.
        $this->assertNotNull($factor->ipcc_reference);
    }

    public function test_a_zero_carbon_fuel_decomposes_to_a_zero_factor_not_a_null_one(): void
    {
        $this->compile();

        // Biomass and agricultural residues: the CO2 is biogenic and not
        // counted, which is a real answer of zero. Leaving these undecomposed
        // would exclude every biogenic stream from the MRV workbook.
        $zeroCarbon = EmissionFactor::where('is_active', true)
            ->whereNotNull('net_calorific_value')
            ->where('co2_factor', 0)
            ->get();

        $this->assertGreaterThan(0, $zeroCarbon->count());

        foreach ($zeroCarbon as $factor) {
            $this->assertNotNull($factor->ef_per_energy, 'A zero-carbon fuel was left undecomposed.');
            $this->assertEquals(0.0, $factor->ef_per_energy);
        }
    }

    public function test_a_composite_factor_is_left_undecomposed(): void
    {
        $this->compile();

        // Distance-based rows price kgCO2e per km — a composite that already
        // contains the CH4 and N2O contributions. There is no NCV and no
        // meaningful energy-basis EF, and inventing one would claim a
        // decomposition that does not exist.
        $composite = EmissionFactor::where('is_active', true)
            ->whereNull('net_calorific_value')
            ->first();

        $this->assertNotNull($composite);
        $this->assertNull($composite->ef_per_energy);
    }

    public function test_recompiling_reports_where_a_factor_disagrees_with_its_own_citation(): void
    {
        $result = app(ConfigCatalogueCompiler::class)
            ->compile(app(BuiltInFactorCatalog::class), pretend: true);

        // Real finding, not hypothetical: 26 entries derive an EF that differs
        // from the IPCC value quoted in their own note by more than 1% —
        // Natural Gas in therms by 10.3%, the signature of a gross calorific
        // value paired with a citation stated on a net one.
        //
        // Surfaced, never corrected: `co2` is what prices every historical
        // figure, so changing it restates them, and that is a decision for
        // whoever signs the inventory off.
        $this->assertNotEmpty($result['divergent']);

        $therms = collect($result['divergent'])
            ->firstWhere(fn ($row) => $row['source'] === 'Natural Gas' && $row['unit'] === 'therms');

        $this->assertNotNull($therms, 'The largest known divergence stopped being reported.');
        $this->assertLessThan(-5.0, $therms['divergence']);
    }

    public function test_the_reported_count_matches_what_is_actually_decomposed(): void
    {
        $result = $this->compile();

        $written = EmissionFactor::where('is_active', true)
            ->whereNotNull('ef_per_energy')
            ->count();

        // The counter tested co2 as well as ncv, so it under-reported by every
        // biogenic entry — a summary that disagreed with the table it summarised.
        $this->assertSame($written, $result['decomposed']);
    }

    // ---------------------------------------------------------------------
    // Carrying it through to a source stream
    // ---------------------------------------------------------------------

    private function facilityWithScope1(): Facilities
    {
        $company = Company::create([
            'name' => 'Decomposition Co '.uniqid(),
            'industry_type' => 'energy',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'MRV Officer',
            'email' => 'decomp-'.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->givePermissionTo(['list-reports', 'create-report', 'edit-report']);
        $this->actingAs($user);
        app()->instance('current_company_id', $company->id);
        app()->instance('current_company', $company);

        $facility = Facilities::create([
            'company_id' => $company->id,
            'name' => 'Decomp Plant '.uniqid(),
            'mrv_enabled' => true,
        ]);

        $factor = EmissionFactor::where('is_active', true)
            ->whereNotNull('ef_per_energy')
            ->where('co2_factor', '>', 0)
            ->firstOrFail();

        EmissionRecord::create([
            'company_id' => $company->id,
            'entry_date' => '2026-04-01',
            'scope' => 1,
            'facility' => $facility->name,
            'facility_id' => $facility->id,
            'emission_source' => 'Boiler fuel',
            'activity_data' => 1000,
            'activity_unit' => $factor->unit,
            'emission_factor_id' => $factor->id,
            'emission_factor' => $factor->factor_value,
            'co2e_value' => 1000 * (float) $factor->factor_value,
            'status' => 'active',
        ]);

        return $facility;
    }

    public function test_prefill_carries_the_decomposition_onto_the_stream(): void
    {
        $this->compile();
        $facility = $this->facilityWithScope1();

        $this->post(route('mrv.prefill'), ['facility_id' => $facility->id, 'year' => 2026])
            ->assertRedirect();

        $stream = MrvSourceStream::where('facility_id', $facility->id)->firstOrFail();

        // The operator picked a fuel the platform already knows everything
        // about; they should not have to type four numbers back in.
        $this->assertNotNull($stream->net_calorific_value);
        $this->assertNotNull($stream->ncv_unit);
        $this->assertSame('tCO2/TJ', $stream->ef_unit);
        $this->assertEquals(1.0, $stream->oxidation_factor);
        $this->assertNotNull($stream->emission_factor_id);

        // And it must be the ENERGY-basis factor, not the combined per-unit one
        // that was written a step earlier — pairing an energy quantity with a
        // per-litre factor is wrong by the calorific value itself.
        $factor = EmissionFactor::find($stream->emission_factor_id);
        $this->assertEqualsWithDelta(
            (float) $factor->ef_per_energy,
            (float) $stream->emission_factor_value,
            0.000001,
            'The stream kept the combined factor while claiming an energy-basis unit.'
        );
    }

    public function test_a_measured_calorific_value_survives_a_re_prefill(): void
    {
        $this->compile();
        $facility = $this->facilityWithScope1();

        $this->post(route('mrv.prefill'), ['facility_id' => $facility->id, 'year' => 2026]);

        $stream = MrvSourceStream::where('facility_id', $facility->id)->firstOrFail();

        // EU-ETS Tier 3 and 4 are DEFINED by using a site-specific calorific
        // value from laboratory analysis instead of a published default.
        $stream->update(['net_calorific_value' => 0.0000412, 'tier_level' => 4]);

        $this->post(route('mrv.prefill'), ['facility_id' => $facility->id, 'year' => 2026]);

        $stream->refresh();

        $this->assertEquals(
            0.0000412,
            (float) $stream->net_calorific_value,
            'A lab-measured NCV was replaced with a catalogue average, silently demoting the tier.'
        );
        $this->assertSame(4, $stream->tier_level);
    }

    public function test_records_priced_with_different_factors_get_no_decomposition(): void
    {
        $this->compile();
        $facility = $this->facilityWithScope1();

        $factors = EmissionFactor::where('is_active', true)
            ->whereNotNull('ef_per_energy')
            ->where('co2_factor', '>', 0)
            ->limit(2)->get();

        // A second record under the same source and unit, priced differently.
        $existing = EmissionRecord::withoutGlobalScope('company')
            ->where('facility_id', $facility->id)->firstOrFail();

        EmissionRecord::create(array_merge($existing->only([
            'company_id', 'entry_date', 'scope', 'facility', 'facility_id',
            'emission_source', 'activity_unit', 'status',
        ]), [
            'activity_data' => 500,
            'emission_factor_id' => $factors->last()->id,
            'emission_factor' => $factors->last()->factor_value,
            'co2e_value' => 12,
        ]));

        $this->post(route('mrv.prefill'), ['facility_id' => $facility->id, 'year' => 2026]);

        $stream = MrvSourceStream::where('facility_id', $facility->id)->firstOrFail();

        // Two different factors under one stream means it is not one material.
        // Picking either would state a decomposition that priced half the
        // activity.
        $this->assertNull($stream->ef_unit);
        $this->assertNull($stream->net_calorific_value);
    }
}

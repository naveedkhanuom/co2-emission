<?php

namespace Tests\Feature;

use App\Models\EmissionFactor;
use App\Models\EmissionSource;
use App\Services\Factors\BuiltInFactorCatalog;
use App\Services\Factors\Import\ConfigCatalogueCompiler;
use App\Support\Gwp;
use Tests\TenantTestCase;

/**
 * Two defects in the Scope 1 catalogue, and the invariants that keep them shut.
 *
 * Both are the quiet kind: neither produces a wrong TOTAL, and neither would show
 * up on the entry screen. They corrupt what the figure is made of, and what reads
 * that is regulated MRV reporting and an assurer.
 */
class Scope1CatalogueIntegrityTest extends TenantTestCase
{
    private function compile(): array
    {
        return app(ConfigCatalogueCompiler::class)->compile(app(BuiltInFactorCatalog::class));
    }

    /**
     * A distance factor is a CO2e COMPOSITE, not a CO2 measurement.
     *
     *     Van - Diesel [litres]  co2=2.676  ch4=3.90  ncv=0.0000359
     *     Van - Diesel [km]      co2=0.317  ch4=0     ncv=0
     *
     * DEFRA's 0.317 kgCO2e/km already contains the methane and nitrous oxide.
     * Writing it to co2_factor puts a CO2e number in a column meaning "CO2 only",
     * next to ch4 = n2o = 0 — so MRV would report a vehicle fleet as 100% CO2.
     */
    public function test_distance_factors_do_not_claim_a_gas_breakdown(): void
    {
        $this->compile();

        $offenders = [];

        foreach (app(BuiltInFactorCatalog::class)->entries() as $entry) {
            if ($entry['scope'] !== 1 || ($entry['ncv'] ?? null) === null || $entry['ncv'] > 0) {
                continue;
            }

            $source = EmissionSource::where('name', $entry['source'])->where('scope', 1)->first();

            $factor = EmissionFactor::where('emission_source_id', $source?->id)
                ->where('unit', $entry['unit'])
                ->where('dataset_name', 'like', 'Built-in%')
                ->where('is_active', true)
                ->first();

            if ($factor && $factor->co2_factor !== null) {
                $offenders[] = $entry['source'].' ['.$entry['unit'].'] co2_factor='.$factor->co2_factor;
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'A composite CO2e factor was stored as though it were a CO2-only measurement.'
        );
    }

    /**
     * The rows that DO decompose must keep doing so — the fix above must not
     * have thrown away real component data.
     */
    public function test_combustion_factors_keep_their_gas_breakdown(): void
    {
        $this->compile();

        $source = EmissionSource::where('name', 'Van - Diesel')->where('scope', 1)->first();
        $this->assertNotNull($source, 'The catalogue no longer contains Van - Diesel.');

        $litres = EmissionFactor::where('emission_source_id', $source->id)
            ->where('unit', 'liters')
            ->where('dataset_name', 'like', 'Built-in%')
            ->where('is_active', true)
            ->first();

        $this->assertNotNull($litres);
        $this->assertNotNull($litres->co2_factor, 'A real combustion breakdown was discarded.');
        $this->assertNotNull($litres->net_calorific_value);
        $this->assertGreaterThan(0, (float) $litres->ch4_factor);
    }

    /**
     * A row that decomposes must state a calorific value, and one that states a
     * calorific value must decompose. The two travel together — NCV is what
     * converts the per-TJ gas factors into per-unit ones, so a breakdown without
     * it cannot be recomputed and is not usable for MRV.
     */
    public function test_a_gas_breakdown_and_a_calorific_value_travel_together(): void
    {
        $this->compile();

        $inconsistent = EmissionFactor::where('dataset_name', 'like', 'Built-in%')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where(function ($inner) {
                    $inner->whereNotNull('co2_factor')->whereNull('net_calorific_value');
                })->orWhere(function ($inner) {
                    $inner->whereNull('co2_factor')->whereNotNull('net_calorific_value');
                });
            })
            ->count();

        $this->assertSame(0, $inconsistent, 'A factor claims a gas breakdown without the NCV needed to recompute it.');
    }

    /**
     * The browser and the server must price with the same GWPs.
     *
     * config/scope1_sources.php declares its own GWP_CH4 / GWP_N2O and the entry
     * script reads them; BuiltInFactorCatalog deliberately reads App\Support\Gwp
     * instead. They agree today because both are AR5 — but config/gwp.php already
     * carries 'default' => 'ar6' and a note to bump the basis later. On that day
     * the server would re-price and these constants would not, so the page would
     * show one number and the record would store another.
     *
     * The controller now overwrites them, and this pins the two together.
     */
    public function test_the_entry_page_is_sent_the_gwps_the_server_prices_with(): void
    {
        $company = \App\Models\Company::create([
            'name' => 'Scope1 GWP Co '.uniqid(),
            'industry_type' => 'manufacturing',
            'is_active' => true,
        ]);

        $user = \App\Models\User::create([
            'name' => 'Scope1 GWP User',
            'email' => 'scope1-gwp-'.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
        ]);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $user->givePermissionTo(['list-emission-records', 'create-emission-record']);
        $this->actingAs($user);
        app()->instance('current_company_id', $company->id);
        app()->instance('current_company', $company);

        $view = $this->get(route('scope1_entry.index'))->assertOk()->original;
        $sources = json_decode($view->getData()['sourcesJson'], true);

        $basis = Gwp::factorBasis();

        $this->assertEquals(
            Gwp::factor('ch4', $basis),
            $sources['GWP_CH4'],
            'The entry page would price methane differently from the server.'
        );
        $this->assertEquals(
            Gwp::factor('n2o', $basis),
            $sources['GWP_N2O'],
            'The entry page would price nitrous oxide differently from the server.'
        );
    }

    /**
     * The fallback constants still baked into the config must match the basis the
     * app actually runs on, so the entry script's `sources.GWP_CH4 || 28` default
     * is not quietly wrong if the override is ever removed.
     */
    public function test_the_config_fallback_constants_match_the_current_basis(): void
    {
        $basis = Gwp::factorBasis();

        $this->assertEquals(Gwp::factor('ch4', $basis), config('scope1_sources.GWP_CH4'));
        $this->assertEquals(Gwp::factor('n2o', $basis), config('scope1_sources.GWP_N2O'));
    }
}

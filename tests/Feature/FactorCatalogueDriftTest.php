<?php

namespace Tests\Feature;

use App\Models\EmissionFactor;
use App\Services\Factors\BuiltInFactorCatalog;
use App\Services\Factors\Import\ConfigCatalogueCompiler;
use Illuminate\Support\Facades\Artisan;
use Tests\TenantTestCase;

/**
 * Detecting a tenant whose compiled factors have fallen behind the catalogue.
 *
 * The built-in catalogue is authored in config/scope1_sources.php and compiled
 * into `emission_factors`. Two representations, one of them hand-edited.
 *
 * ConfigCatalogueCompileTest already asserts that compiling does not change a
 * figure — but it compiles inside the test, so it can only ever say "if you
 * compile, the values match". It cannot notice that a REAL tenant was never
 * recompiled after a config edit, which is the failure that actually happens:
 * edit a factor, deploy, and the Scope 1/2 entry pages read the new number from
 * config while everything reading the library — the factor list, reports, and
 * the NCV and energy-basis EF the MRV workbook's tiers are built from — serves
 * the old one. Nothing looks wrong from either side.
 *
 * So the check has to run against stored rows, and it has to be able to FAIL —
 * a check that only ever passes is documentation.
 */
class FactorCatalogueDriftTest extends TenantTestCase
{
    private function compile(): void
    {
        app(ConfigCatalogueCompiler::class)->compile(app(BuiltInFactorCatalog::class));
    }

    private function check(): int
    {
        return Artisan::call('factors:check-drift');
    }

    public function test_a_freshly_compiled_tenant_has_no_drift(): void
    {
        $this->compile();

        $this->assertSame(0, $this->check(), Artisan::output());
    }

    public function test_a_stored_factor_that_no_longer_matches_the_catalogue_fails(): void
    {
        $this->compile();

        $factor = EmissionFactor::where('is_active', true)
            ->where('dataset_name', ConfigCatalogueCompiler::DATASET)
            ->where('factor_value', '>', 0)
            ->firstOrFail();

        // Same observable end state as editing the config and deploying without
        // recompiling: the stored figure and the catalogue disagree.
        $factor->update(['factor_value' => (float) $factor->factor_value * 1.05]);

        $this->assertSame(1, $this->check(), 'A 5% divergence went unreported.');
        $this->assertStringContainsString('do not match the deployed catalogue', Artisan::output());
    }

    public function test_recompiling_clears_the_drift(): void
    {
        $this->compile();

        $factor = EmissionFactor::where('is_active', true)
            ->where('dataset_name', ConfigCatalogueCompiler::DATASET)
            ->where('factor_value', '>', 0)
            ->firstOrFail();

        $factor->update(['factor_value' => (float) $factor->factor_value * 1.05]);
        $this->assertSame(1, $this->check());

        $this->compile();

        $this->assertSame(0, $this->check(), Artisan::output());
    }

    public function test_a_tenant_that_was_never_compiled_is_reported_as_missing(): void
    {
        // Retire everything the catalogue compiled, so the library can no
        // longer answer for any of it — a tenant provisioned before the
        // catalogue was compiled looks exactly like this.
        EmissionFactor::whereIn('dataset_name', [
            ConfigCatalogueCompiler::DATASET,
            ConfigCatalogueCompiler::UNATTRIBUTED,
        ])->update(['is_active' => false]);

        $this->assertSame(1, $this->check());

        $output = Artisan::output();

        $this->assertStringContainsString('cannot be resolved from the library', $output);
        $this->assertStringContainsString('never been compiled', $output);
    }

    public function test_a_zero_carbon_factor_is_compared_exactly(): void
    {
        $this->compile();

        // A relative tolerance against zero either waves everything through or
        // rejects everything, so zero is compared exactly. A biogenic fuel that
        // acquires a non-zero stored value is real drift.
        $zero = EmissionFactor::where('is_active', true)
            ->where('dataset_name', ConfigCatalogueCompiler::DATASET)
            ->where('factor_value', 0)
            ->first();

        if (! $zero) {
            $this->markTestSkipped('No zero-valued factor in the catalogue to exercise this with.');
        }

        $this->assertSame(0, $this->check());

        $zero->update(['factor_value' => 0.0001]);

        $this->assertSame(1, $this->check(), 'A zero factor became non-zero and was not reported.');
    }

    public function test_the_check_writes_nothing(): void
    {
        $this->compile();

        $before = EmissionFactor::where('is_active', true)->count();
        $checksum = EmissionFactor::where('is_active', true)->sum('factor_value');

        $this->check();

        $this->assertSame($before, EmissionFactor::where('is_active', true)->count());
        $this->assertEquals($checksum, EmissionFactor::where('is_active', true)->sum('factor_value'));
    }

    public function test_it_refuses_to_run_without_a_tenant(): void
    {
        // Guarded like every other data-touching command: run centrally it
        // would report on the leftover pre-tenancy tables and mean nothing.
        tenancy()->end();

        $this->assertSame(1, Artisan::call('factors:check-drift'));
        $this->assertStringContainsString('tenant', mb_strtolower(Artisan::output()));

        $this->initializeTestTenant();
    }
}

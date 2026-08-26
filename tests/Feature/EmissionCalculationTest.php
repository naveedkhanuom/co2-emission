<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\EioFactor;
use App\Models\EmissionFactor;
use App\Models\EmissionRecord;
use App\Models\EmissionSource;
use App\Models\EnergyAttributeCertificate;
use App\Services\DisclosureReportService;
use App\Services\EmissionEnrichmentService;
use App\Support\Gwp;
use Tests\TenantTestCase;

/**
 * The arithmetic every customer's reported figures depend on.
 *
 * Scope note: the activity-based figure (`activity_data × emission_factor`) is
 * computed in the BROWSER and posted to the server, which stores it as given —
 * so there is no server-side calculation to test for that path, only the
 * derivations built on top of it. What IS computed server-side, and is covered
 * here: the spend-based EIO estimate, the per-gas split, Scope 2 dual
 * reporting, and the disclosure totals.
 */
class EmissionCalculationTest extends TenantTestCase
{
    private EmissionEnrichmentService $enrichment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enrichment = app(EmissionEnrichmentService::class);
    }

    private function makeCompany(): Company
    {
        return Company::create([
            'name' => 'Calc Test Co',
            'industry_type' => 'manufacturing',
            'is_active' => true,
        ]);
    }

    /** A factor carrying a per-gas breakdown, for split testing. */
    private function makeFactorWithGases(): EmissionFactor
    {
        $source = EmissionSource::create([
            'name' => 'Calc Test Gas Source '.uniqid(),
            'scope' => 1,
        ]);

        return EmissionFactor::create([
            'emission_source_id' => $source->id,
            'factor_value' => 0.00268,
            'unit' => 'L',
            'co2_factor' => 1,
            'ch4_factor' => 0.001,
            'n2o_factor' => 0.0001,
        ]);
    }

    private function makeRecord(Company $company, array $attributes = []): EmissionRecord
    {
        return EmissionRecord::create(array_merge([
            'company_id' => $company->id,
            'entry_date' => now()->startOfYear()->addMonth(),
            'scope' => 1,
            'facility' => 'Main Site',
            'emission_source' => 'Diesel',
            'activity_data' => 1000,
            'emission_factor' => 0.00268,
            'co2e_value' => 2.68,
            'status' => 'active',
        ], $attributes));
    }

    // ---------------------------------------------------------------
    // Spend-based estimate — computed server-side and authoritative
    // ---------------------------------------------------------------

    /** Spend × factor, normalised to tonnes. Seeded factors are kg-based. */
    public function test_spend_based_estimate_multiplies_and_normalises_to_tonnes(): void
    {
        EioFactor::create([
            'sector_code' => 'CALCTEST',
            'sector_name' => 'Calc Test Sector',
            'country' => 'USA',
            'currency' => 'USD',
            'emission_factor' => 0.5,
            'factor_unit' => 'kgCO2e/USD',
            'is_active' => true,
        ]);

        // 1000 USD × 0.5 kgCO2e/USD = 500 kg = 0.5 tonnes.
        $result = EioFactor::calculateFromSpend(1000, 'CALCTEST', 'USA', 'USD');

        $this->assertEqualsWithDelta(0.5, $result, 1e-6);
    }

    /** A tonne-denominated factor is not divided again. */
    public function test_tonne_denominated_factor_is_not_rescaled(): void
    {
        EioFactor::create([
            'sector_code' => 'CALCTONNE',
            'sector_name' => 'Calc Test Tonnes',
            'country' => 'USA',
            'currency' => 'USD',
            'emission_factor' => 0.0005,
            'factor_unit' => 'tCO2e/USD',
            'is_active' => true,
        ]);

        // 1000 USD × 0.0005 tCO2e/USD = 0.5 tonnes, already in tonnes.
        $this->assertEqualsWithDelta(0.5, EioFactor::calculateFromSpend(1000, 'CALCTONNE', 'USA', 'USD'), 1e-6);
    }

    /**
     * Spend in one currency against a factor denominated in another must be
     * converted first — otherwise an AED spend against a USD factor is
     * overstated by the exchange ratio.
     */
    public function test_spend_is_converted_to_the_factor_currency(): void
    {
        EioFactor::create([
            'sector_code' => 'CALCFX',
            'sector_name' => 'Calc Test FX',
            'country' => 'USA',
            'currency' => 'USD',
            'emission_factor' => 0.5,
            'factor_unit' => 'kgCO2e/USD',
            'is_active' => true,
        ]);

        $sameCurrency = EioFactor::calculateFromSpend(1000, 'CALCFX', 'USA', 'USD');
        $foreignCurrency = EioFactor::calculateFromSpend(1000, 'CALCFX', 'USA', 'AED');

        $this->assertNotNull($foreignCurrency);
        $this->assertLessThan(
            $sameCurrency,
            $foreignCurrency,
            '1000 AED is worth less than 1000 USD, so it must produce fewer emissions.'
        );
    }

    /** An unknown sector yields null rather than a fabricated zero. */
    public function test_unknown_sector_returns_null(): void
    {
        $this->assertNull(EioFactor::calculateFromSpend(1000, 'NO-SUCH-SECTOR', 'USA', 'USD'));
    }

    // ---------------------------------------------------------------
    // Per-gas split
    // ---------------------------------------------------------------

    /**
     * The service's own contract: the parts always sum to the whole. If they
     * drift, the gas breakdown on a disclosure will not reconcile with the
     * total it was derived from.
     */
    public function test_gas_split_sums_back_to_the_total(): void
    {
        $company = $this->makeCompany();
        $factor = $this->makeFactorWithGases();

        // Internally consistent: 1000 × 0.1 = 100, so the verifier leaves it be.
        $enriched = $this->enrichment->enrich([
            'company_id' => $company->id,
            'scope' => 1,
            'emission_source' => 'Diesel',
            'activity_data' => 1000,
            'emission_factor' => 0.1,
            'co2e_value' => 100.0,
        ], ['emission_factor_id' => $factor->id]);

        $sum = $enriched['co2e_co2'] + $enriched['co2e_ch4'] + $enriched['co2e_n2o'];

        $this->assertEqualsWithDelta(100.0, $sum, 0.001);
        $this->assertGreaterThan($enriched['co2e_ch4'], $enriched['co2e_co2'], 'CO2 should dominate this factor.');
    }

    /** The GWP set changes the proportions, since CH4 and N2O are re-weighted. */
    public function test_gas_split_depends_on_the_gwp_set(): void
    {
        $company = $this->makeCompany();
        $factor = $this->makeFactorWithGases();

        $base = ['company_id' => $company->id, 'scope' => 1, 'emission_source' => 'Diesel',
            'activity_data' => 1000, 'emission_factor' => 0.1, 'co2e_value' => 100.0];

        $ar4 = $this->enrichment->enrich($base + ['gwp_version' => 'ar4'], ['emission_factor_id' => $factor->id]);
        $ar6 = $this->enrichment->enrich($base + ['gwp_version' => 'ar6'], ['emission_factor_id' => $factor->id]);

        $this->assertNotEquals($ar4['co2e_ch4'], $ar6['co2e_ch4']);
        $this->assertEqualsWithDelta(100.0, $ar4['co2e_co2'] + $ar4['co2e_ch4'] + $ar4['co2e_n2o'], 0.001);
        $this->assertEqualsWithDelta(100.0, $ar6['co2e_co2'] + $ar6['co2e_ch4'] + $ar6['co2e_n2o'], 0.001);
    }

    /** Records are stamped with the factor basis, so the claim matches the math. */
    public function test_record_is_stamped_with_the_factor_basis_gwp(): void
    {
        $company = $this->makeCompany();

        $enriched = $this->enrichment->enrich([
            'company_id' => $company->id,
            'scope' => 1,
            'emission_source' => 'Diesel',
            'co2e_value' => 10.0,
        ]);

        $this->assertSame(Gwp::factorBasis(), $enriched['gwp_version']);
    }

    // ---------------------------------------------------------------
    // Scope 2 dual reporting
    // ---------------------------------------------------------------

    /**
     * A market-based figure must never overwrite the location-based one. Both
     * are reported; conflating them double-counts or hides a real figure.
     */
    public function test_market_based_does_not_overwrite_location_based(): void
    {
        $company = $this->makeCompany();

        $cert = EnergyAttributeCertificate::create([
            'company_id' => $company->id,
            'name' => 'Green tariff',
            'emission_factor' => 0.0001,
        ]);

        $enriched = $this->enrichment->enrich([
            'company_id' => $company->id,
            'scope' => 2,
            'emission_source' => 'Electricity',
            'activity_data' => 1000,
            'emission_factor' => 0.0005,
            'co2e_value' => 0.5,
        ], ['energy_attribute_certificate_id' => $cert->id]);

        // Location-based is untouched.
        $this->assertSame(0.5, $enriched['co2e_value']);

        // Market-based is scaled by the factor ratio: 0.0001 / 0.0005 = 0.2.
        $this->assertEqualsWithDelta(0.1, $enriched['market_based_co2e'], 1e-6);
        $this->assertSame('market_based', $enriched['scope2_method']);
    }

    /** A certificate belonging to another company must be ignored. */
    public function test_certificate_from_another_company_is_ignored(): void
    {
        $mine = $this->makeCompany();
        $theirs = $this->makeCompany();

        $foreignCert = EnergyAttributeCertificate::create([
            'company_id' => $theirs->id,
            'name' => 'Their tariff',
            'emission_factor' => 0.0,
        ]);

        $enriched = $this->enrichment->enrich([
            'company_id' => $mine->id,
            'scope' => 2,
            'emission_source' => 'Electricity',
            'activity_data' => 1000,
            'emission_factor' => 0.0005,
            'co2e_value' => 0.5,
        ], ['energy_attribute_certificate_id' => $foreignCert->id]);

        $this->assertArrayNotHasKey('market_based_factor', $enriched);
        $this->assertSame('location_based', $enriched['scope2_method']);
        $this->assertSame(0.5, $enriched['co2e_value']);
    }

    /**
     * With no market-based figure recorded, the market-based total falls back
     * to the location-based one so dual-reporting sums stay well-defined.
     */
    public function test_market_based_falls_back_to_location_based(): void
    {
        $company = $this->makeCompany();
        $record = $this->makeRecord($company, ['scope' => 2, 'co2e_value' => 4.0, 'market_based_co2e' => null]);

        $this->assertSame(4.0, $record->marketBasedCo2e());
    }

    // ---------------------------------------------------------------
    // Disclosure reconciliation
    // ---------------------------------------------------------------

    /** Disclosure totals must equal the sum of the underlying records. */
    public function test_disclosure_totals_reconcile_with_the_records(): void
    {
        $company = $this->makeCompany();
        $year = (int) now()->year;

        $this->makeRecord($company, ['scope' => 1, 'co2e_value' => 10.0]);
        $this->makeRecord($company, ['scope' => 2, 'co2e_value' => 20.0]);
        $this->makeRecord($company, ['scope' => 3, 'co2e_value' => 30.0]);

        $data = app(DisclosureReportService::class)->build($company->id, $year);

        $this->assertSame(10.0, (float) $data['totals']['scope1']);
        $this->assertSame(20.0, (float) $data['totals']['scope2_location']);
        $this->assertSame(30.0, (float) $data['totals']['scope3']);
        $this->assertSame(60.0, (float) $data['totals']['total_location']);
    }

    /**
     * Only finalised records reach a disclosure. Both non-final states —
     * `draft` and the intermediate `reviewed` — must be excluded, or unapproved
     * figures end up in a filing.
     */
    public function test_only_active_records_are_disclosed(): void
    {
        $company = $this->makeCompany();

        $this->makeRecord($company, ['co2e_value' => 10.0, 'status' => 'active']);
        $this->makeRecord($company, ['co2e_value' => 99.0, 'status' => 'draft']);
        $this->makeRecord($company, ['co2e_value' => 55.0, 'status' => 'reviewed']);

        $data = app(DisclosureReportService::class)->build($company->id, (int) now()->year);

        $this->assertSame(10.0, (float) $data['totals']['scope1'], 'Unapproved data must not be reported.');
    }

    /** Records from another year do not leak into this year's disclosure. */
    public function test_disclosure_is_year_scoped(): void
    {
        $company = $this->makeCompany();

        $this->makeRecord($company, ['co2e_value' => 10.0]);
        $this->makeRecord($company, ['co2e_value' => 77.0, 'entry_date' => now()->subYears(3)]);

        $data = app(DisclosureReportService::class)->build($company->id, (int) now()->year);

        $this->assertSame(10.0, (float) $data['totals']['scope1']);
    }

    /** Another company's emissions never appear in this company's disclosure. */
    public function test_disclosure_is_company_scoped(): void
    {
        $mine = $this->makeCompany();
        $theirs = $this->makeCompany();

        $this->makeRecord($mine, ['co2e_value' => 10.0]);
        $this->makeRecord($theirs, ['co2e_value' => 500.0]);

        $data = app(DisclosureReportService::class)->build($mine->id, (int) now()->year);

        $this->assertSame(10.0, (float) $data['totals']['scope1']);
    }

    /**
     * The gas breakdown attributes any unallocated residual to CO2. It must
     * account for the whole inventory without inflating it — a disclosure whose
     * gases exceed its total is an immediate assurance finding.
     */
    public function test_gas_breakdown_accounts_for_the_total_without_inflating_it(): void
    {
        $company = $this->makeCompany();

        // Records with no per-gas split at all — the residual case.
        $this->makeRecord($company, ['scope' => 1, 'co2e_value' => 10.0]);
        $this->makeRecord($company, ['scope' => 2, 'co2e_value' => 5.0]);

        $data = app(DisclosureReportService::class)->build($company->id, (int) now()->year);

        $gasSum = array_sum($data['gases']);

        $this->assertEqualsWithDelta((float) $data['totals']['total_location'], $gasSum, 0.01);
    }

    /** Scope 3 category rows must sum to the reported Scope 3 total. */
    public function test_scope3_categories_sum_to_the_scope3_total(): void
    {
        $company = $this->makeCompany();
        $category = \App\Models\Scope3Category::orderBy('sort_order')->firstOrFail();

        $this->makeRecord($company, ['scope' => 3, 'scope3_category_id' => $category->id, 'co2e_value' => 12.0]);
        $this->makeRecord($company, ['scope' => 3, 'scope3_category_id' => $category->id, 'co2e_value' => 8.0]);

        $data = app(DisclosureReportService::class)->build($company->id, (int) now()->year);

        $categorySum = array_sum(array_column($data['scope3_by_category'], 'co2e'));

        $this->assertSame(20.0, (float) $data['totals']['scope3']);
        $this->assertEqualsWithDelta(20.0, $categorySum, 0.01);
    }

    // ---------------------------------------------------------------
    // Server-side verification of the client-supplied figure
    // ---------------------------------------------------------------

    /**
     * The figure arrives from the browser. A value that does not match the
     * record's own activity × factor is corrected to the server's arithmetic
     * and the record is held as a draft, so it cannot reach a disclosure
     * without a human seeing it.
     */
    public function test_mismatched_client_figure_is_corrected_and_held_for_review(): void
    {
        $company = $this->makeCompany();

        $enriched = $this->enrichment->enrich([
            'company_id' => $company->id,
            'scope' => 1,
            'emission_source' => 'Diesel',
            'activity_data' => 1000,
            'emission_factor' => 0.00268,
            'co2e_value' => 999999.0, // should be 2.68
            'status' => 'active',
        ]);

        $this->assertEqualsWithDelta(2.68, $enriched['co2e_value'], 1e-6);
        $this->assertSame('draft', $enriched['status'], 'A mismatched record must not stay active.');
        $this->assertStringContainsString('Held for review', $enriched['notes']);
    }

    /** A correct figure passes through untouched and stays active. */
    public function test_correct_figure_is_left_alone(): void
    {
        $company = $this->makeCompany();

        $enriched = $this->enrichment->enrich([
            'company_id' => $company->id,
            'scope' => 1,
            'emission_source' => 'Diesel',
            'activity_data' => 1000,
            'emission_factor' => 0.00268,
            'co2e_value' => 2.68,
            'status' => 'active',
        ]);

        $this->assertSame(2.68, (float) $enriched['co2e_value']);
        $this->assertSame('active', $enriched['status']);
        $this->assertArrayNotHasKey('notes', $enriched);
    }

    /** Rounding noise must not flood the review queue with false positives. */
    public function test_rounding_differences_are_tolerated(): void
    {
        $verifier = app(\App\Services\EmissionFigureVerifier::class);

        // 0.1% out — display rounding, not an error.
        $this->assertFalse($verifier->isMaterialMismatch(2.6827, 2.68));

        // 10% out — a real discrepancy.
        $this->assertTrue($verifier->isMaterialMismatch(2.95, 2.68));
    }

    /** A missing figure is computed rather than treated as a discrepancy. */
    public function test_missing_figure_is_calculated(): void
    {
        $company = $this->makeCompany();

        $enriched = $this->enrichment->enrich([
            'company_id' => $company->id,
            'scope' => 1,
            'emission_source' => 'Diesel',
            'activity_data' => 500,
            'emission_factor' => 0.002,
            'co2e_value' => null,
            'status' => 'active',
        ]);

        $this->assertEqualsWithDelta(1.0, $enriched['co2e_value'], 1e-6);
        $this->assertSame('active', $enriched['status'], 'Filling a blank is not a discrepancy.');
    }

    /**
     * Records entered as a total CO2e with no activity data or factor are a
     * legitimate path and must not be touched.
     */
    public function test_total_only_entry_is_not_verifiable_and_passes_through(): void
    {
        $company = $this->makeCompany();

        $enriched = $this->enrichment->enrich([
            'company_id' => $company->id,
            'scope' => 3,
            'emission_source' => 'Business Travel',
            'activity_data' => null,
            'emission_factor' => null,
            'co2e_value' => 42.0,
            'status' => 'active',
        ]);

        $this->assertSame(42.0, (float) $enriched['co2e_value']);
        $this->assertSame('active', $enriched['status']);
    }

    /** The spend-based path is already authoritative and must be left alone. */
    public function test_spend_based_records_are_not_second_guessed(): void
    {
        $company = $this->makeCompany();

        $enriched = $this->enrichment->enrich([
            'company_id' => $company->id,
            'scope' => 3,
            'emission_source' => 'Purchased Goods & Services',
            'calculation_method' => 'spend-based',
            'activity_data' => 1000,
            'emission_factor' => 0.5,
            'co2e_value' => 0.25, // EioFactor's figure, not activity × factor
            'status' => 'active',
        ]);

        $this->assertSame(0.25, (float) $enriched['co2e_value']);
        $this->assertSame('active', $enriched['status']);
    }

    /** An existing note is preserved, not overwritten by the review note. */
    public function test_existing_notes_are_preserved(): void
    {
        $verifier = app(\App\Services\EmissionFigureVerifier::class);

        $result = $verifier->verify([
            'activity_data' => 1000,
            'emission_factor' => 0.00268,
            'co2e_value' => 500.0,
            'notes' => 'Meter reading taken by site team.',
        ]);

        $this->assertStringContainsString('Meter reading taken by site team.', $result['notes']);
        $this->assertStringContainsString('Held for review', $result['notes']);
    }

    /**
     * A spreadsheet column is as untrusted as a browser. Imported rows are
     * verified too — and an import writes `active` by default, so an unchecked
     * bad column would reach a report directly.
     */
    public function test_imported_rows_are_verified(): void
    {
        $verifier = app(\App\Services\EmissionFigureVerifier::class);

        $result = $verifier->verify([
            'activity_data' => 200,
            'emission_factor' => 0.01,
            'co2e_value' => 2000.0, // should be 2
            'status' => 'active',
            'data_source' => 'import',
        ]);

        $this->assertEqualsWithDelta(2.0, $result['co2e_value'], 1e-6);
        $this->assertSame('draft', $result['status']);
    }
}

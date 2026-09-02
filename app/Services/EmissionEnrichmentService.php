<?php

namespace App\Services;

use App\Models\EmissionFactor;
use App\Models\EnergyAttributeCertificate;
use App\Support\Gwp;

/**
 * Enriches an emission-record payload with the compliance metadata the
 * disclosure frameworks require:
 *   - GWP set snapshot (config/gwp.php, per company default)
 *   - factor locking: the exact EmissionFactor id + dataset provenance string
 *   - per-gas CO2e split (CO2/CH4/N2O) derived from the factor's gas breakdown
 *   - Scope 2 dual reporting: location-based (kept as co2e_value) plus a
 *     market-based figure from an Energy Attribute Certificate.
 *
 * Every value it adds is optional and additive — records still save if no factor
 * or certificate can be resolved, so existing entry flows keep working unchanged.
 */
class EmissionEnrichmentService
{
    /**
     * @param  array  $data  The record attributes being persisted (mutated copy returned).
     * @param  array  $context  Optional hints: emission_factor_id, energy_attribute_certificate_id,
     *                          market_based_co2e, scope2_method.
     */
    public function __construct(protected EmissionFigureVerifier $verifier) {}

    public function enrich(array $data, array $context = []): array
    {
        $companyId = $data['company_id'] ?? null;
        $scope = (int) ($data['scope'] ?? 0);

        // 0. Resolve the factor FIRST, so the verifier below can be told which
        //    activity unit it is priced per.
        //
        //    This used to run after verification, which meant the check had no
        //    way to notice that a per-mile factor had been paired with a
        //    litres activity: 23.789 x 0.25993 is arithmetically true whatever
        //    the two numbers mean, so a dimensionally nonsensical record passed.
        //    Resolution depends on nothing the verifier produces — it keys on
        //    the source name, unit and factor — so hoisting it is safe.
        $factor = $this->resolveFactor($data, $context);

        // 1. Verify the figure against its own activity data and factor BEFORE
        //    anything is derived from it. The activity-based value arrives from
        //    the client, and everything below (the gas split, the market-based
        //    figure) treats co2e_value as authoritative — so an unchecked value
        //    would propagate consistently and invisibly.
        $data = $this->verifier->verify($data, $factor?->unit);

        // 2. GWP set — stamp the basis the figure was ACTUALLY computed under, i.e.
        //    the basis of the bundled factor/source tables (currently AR5), so the
        //    record's stated GWP set always matches its co2e_value. (The company's
        //    aspirational preference from onboarding does not drive the math yet.)
        if (empty($data['gwp_version'])) {
            $data['gwp_version'] = Gwp::factorBasis();
        }

        // 3. Lock the resolved factor (for provenance/audit).
        if ($factor) {
            $data['emission_factor_id'] = $factor->id;

            // provenanceLabel(), not datasetLabel(): the citation belongs on the
            // record, not just the edition. The short form stamped
            // "Built-in catalogue 2026.1" over a resolver-supplied
            // "…— IPCC 74100 kgCO2/TJ, NCV 26.5 GJ/t", which made the stored
            // provenance weaker precisely when the factor had been locked to a
            // real row.
            $data['factor_dataset'] = $factor->provenanceLabel() ?? ($factor->organization?->code ?? null);

            // 3. Per-gas CO2e split. Computed as GWP-weighted proportions of the
            //    authoritative co2e_value so the parts always sum to the whole —
            //    this sidesteps any kg/tonne unit ambiguity in the factor.
            $split = $this->gasSplit($factor, (float) ($data['co2e_value'] ?? 0), $data['gwp_version']);
            if ($split) {
                $data['co2e_co2'] = $split['co2'];
                $data['co2e_ch4'] = $split['ch4'];
                $data['co2e_n2o'] = $split['n2o'];
            }
        }

        // 4. Scope 2 dual reporting.
        if ($scope === 2) {
            $data['scope2_method'] = $context['scope2_method'] ?? ($data['scope2_method'] ?? 'location_based');
            $data = $this->applyMarketBased($data, $context);
        }

        return $data;
    }

    /**
     * Find the EmissionFactor row that produced this record, for locking.
     * Prefers an explicit id; otherwise matches the source, unit and value.
     *
     * MATCHES ON UNIT, which it did not used to.
     *
     * Matching on source name and factor value alone let a record entered in
     * litres lock onto a factor published per MILE, because a name can carry
     * rows for several units and nothing compared them. That produced a stored
     * figure with a real emission_factor_id, full provenance and a passing
     * verification — every signal an assurer looks at saying the number was
     * checked — for a figure that priced fuel volume at a distance rate.
     *
     * COMPARES IN TONNES, not on the raw column.
     *
     * `emission_factor` on the record is always tCO2e; `factor_value` is in
     * whatever basis its publisher used. An exact `where('factor_value', ...)`
     * therefore silently stopped matching DEFRA rows the moment the resolver
     * started converting them. Candidates are compared through
     * valueInTonnes() instead, on the same basis the record stores.
     */
    protected function resolveFactor(array $data, array $context): ?EmissionFactor
    {
        $id = $context['emission_factor_id'] ?? ($data['emission_factor_id'] ?? null);
        if ($id) {
            return EmissionFactor::with('organization')->find($id);
        }

        $sourceName = $data['emission_source'] ?? null;
        $factorValue = $data['emission_factor'] ?? null;
        if (! $sourceName || $factorValue === null || ! is_numeric($factorValue)) {
            return null;
        }

        $activityUnit = $data['activity_unit'] ?? null;

        $candidates = EmissionFactor::with('organization')
            ->whereHas('emissionSource', fn ($q) => $q->where('name', $sourceName))
            // Only when the record states one. A record with no unit cannot be
            // dimension-checked, and refusing to lock a factor for it would
            // withdraw provenance from entries that have always had it.
            ->when(
                filled($activityUnit),
                fn ($q) => $q->whereRaw('LOWER(TRIM(unit)) = ?', [mb_strtolower(trim((string) $activityUnit))])
            )
            ->when(isset($data['factor_organization_id']), fn ($q) => $q->where('organization_id', $data['factor_organization_id']))
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get();

        $target = (float) $factorValue;

        foreach ($candidates as $candidate) {
            if ($this->sameFactor($candidate->valueInTonnes(), $target)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Whether a candidate row's factor is the one the record was priced with.
     *
     * Compared with a tolerance rather than for equality: the record stores a
     * rounded decimal, and a converted kg row divides by 1000, so exact
     * equality would reject the very rows this is meant to find. The window is
     * far tighter than the gap between any two real factors for the same
     * activity and unit.
     */
    protected function sameFactor(float $candidate, float $target): bool
    {
        if (abs($target) < 1e-12) {
            return abs($candidate) < 1e-12;
        }

        return abs($candidate - $target) / abs($target) < 1e-6;
    }

    /**
     * Split co2e_value into CO2/CH4/N2O contributions using the factor's per-gas
     * factors weighted by GWP. Returns null if the factor has no breakdown.
     *
     * @return array{co2: float, ch4: float, n2o: float}|null
     */
    protected function gasSplit(EmissionFactor $factor, float $co2e, ?string $gwpVersion): ?array
    {
        if (! $factor->hasGasBreakdown() || $co2e <= 0) {
            return null;
        }

        $weights = [
            'co2' => (float) ($factor->co2_factor ?? 0) * Gwp::factor('co2', $gwpVersion),
            'ch4' => (float) ($factor->ch4_factor ?? 0) * Gwp::factor('ch4', $gwpVersion),
            'n2o' => (float) ($factor->n2o_factor ?? 0) * Gwp::factor('n2o', $gwpVersion),
        ];
        $sum = array_sum($weights);
        if ($sum <= 0) {
            return null;
        }

        return [
            'co2' => round($co2e * $weights['co2'] / $sum, 4),
            'ch4' => round($co2e * $weights['ch4'] / $sum, 4),
            'n2o' => round($co2e * $weights['n2o'] / $sum, 4),
        ];
    }

    /**
     * Derive the market-based Scope 2 figure. If a certificate is linked, apply
     * its factor to the activity data; otherwise honour an explicitly supplied
     * market_based_co2e. Location-based stays in co2e_value untouched.
     */
    protected function applyMarketBased(array $data, array $context): array
    {
        $certId = $context['energy_attribute_certificate_id']
            ?? ($data['energy_attribute_certificate_id'] ?? null);

        if ($certId) {
            $cert = EnergyAttributeCertificate::find($certId);
            // Tenant guard: ignore a certificate from another company.
            if ($cert && (! isset($data['company_id']) || $cert->company_id == $data['company_id'])) {
                $data['energy_attribute_certificate_id'] = $cert->id;
                $data['market_based_factor'] = $cert->emission_factor;

                $activity = $data['activity_data'] ?? null;
                if ($activity !== null && $data['emission_factor']) {
                    // Scale location-based co2e by the factor ratio so the
                    // market-based figure shares the same unit basis.
                    $ratio = (float) $data['emission_factor'] > 0
                        ? (float) $cert->emission_factor / (float) $data['emission_factor']
                        : 0;
                    $data['market_based_co2e'] = round((float) ($data['co2e_value'] ?? 0) * $ratio, 4);
                } else {
                    $data['market_based_co2e'] = $context['market_based_co2e'] ?? ($data['market_based_co2e'] ?? null);
                }
                $data['scope2_method'] = 'market_based';

                return $data;
            }
        }

        // No certificate: accept an explicit market-based value if provided.
        if (isset($context['market_based_co2e']) && $context['market_based_co2e'] !== null) {
            $data['market_based_co2e'] = $context['market_based_co2e'];
        }

        return $data;
    }
}

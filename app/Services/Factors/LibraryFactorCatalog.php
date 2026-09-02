<?php

namespace App\Services\Factors;

use App\Models\EmissionFactor;
use App\Models\EmissionSource;

/**
 * Derives a factor from the client's own factor library — the seeded
 * emission_sources / emission_factors tables.
 *
 * WHY THIS EXISTS
 *
 * BuiltInFactorCatalog reads config/scope1_sources.php and
 * config/scope2_sources.php, so it can only answer for Scope 1 and 2. Scope 3
 * has no config catalogue and never will: its 94 sources and their factors are
 * seeded reference data, and clients may add their own. Without a resolver
 * that reads them, a Scope 3 entry stored the browser's total with no factor
 * beside it, and EmissionFigureVerifier had nothing to check it against.
 *
 * ZERO FACTORS ARE REFUSED
 *
 * Eleven seeded Scope 3 factors are zero — the category-level placeholders
 * like "Purchased Goods & Services [unit]", which cannot have a per-unit
 * factor because the category is inherently spend-based. Pricing activity
 * against one of those would report a confident, arithmetically checkable
 * ZERO for real emissions, which is worse than storing no factor at all: an
 * unverified figure is visibly unverified, whereas a verified zero looks
 * settled. So a zero is treated as "no factor available".
 *
 * CALLED FOR SCOPE 3 ONLY
 *
 * The method takes a scope because matching on it is what stops a Scope 1
 * source name being priced with a Scope 3 number — but EmissionRecordController
 * only reaches here for Scope 3, on purpose.
 *
 * Scope 2 must not use it: that scope is priced by grid region, and the
 * library's generic electricity row is not any particular region's factor.
 * Falling back to it would price UAE electricity at a global average and then
 * flag the client's correct regional figure as an error. Scope 2 with no
 * region is meant to save unverified, which is the honest outcome.
 *
 * Scope 1 does not need it: config/scope1_sources.php already carries 244
 * sources through BuiltInFactorCatalog.
 */
class LibraryFactorCatalog
{
    /**
     * Find a factor for this source and unit in the client's library.
     *
     * @param  int  $scope  1, 2 or 3 — matched against the source's own scope
     *                      so a name collision across scopes cannot be priced
     *                      with the wrong number.
     */
    public function resolve(int $scope, ?string $source, ?string $unit, array $context = []): ?ResolvedFactor
    {
        if ($source === null || trim($source) === '' || $unit === null || trim($unit) === '') {
            return null;
        }

        $region = trim((string) ($context['region'] ?? ''));

        // Scope 2 grid electricity is priced by REGION, not by what the
        // electricity was used for. All 13 grid-priced sources — EV charging,
        // green tariff, landlord-supplied and the rest — share one regional
        // factor, and the catalogue compiles them to a single canonical source
        // with a row per region.
        //
        // So a grid entry resolves against that source rather than its own name,
        // and the record still records what the electricity was for in its own
        // emission_source column. "What factor produced this figure" then answers
        // "DEWA 2023, 0.3876 kgCO2/kWh", which is the truthful answer.
        $lookupName = ($scope === 2 && $region !== '')
            ? BuiltInFactorCatalog::GRID_SOURCE
            : $source;

        // Grid electricity without a region cannot be priced, and must not be
        // guessed at. Every row under the grid source belongs to a specific grid;
        // with no region the query matched all 22 and took whichever sorted
        // last — "World Average" — then presented it as that client's figure.
        // French nuclear and South African coal differ by more than an order of
        // magnitude, so picking arbitrarily is not a rounding error.
        //
        // Refusing means the record saves unverified, which is the honest
        // outcome and the one the entry form already handles.
        // Checked against every grid-priced source, not just the canonical one:
        // a record stores what the electricity was FOR ("Purchased Electricity
        // (Location-Based)"), and matching only the canonical name let those
        // through to be priced off a generic seeded electricity row at
        // 0.55 kgCO2e/kWh — a real number, for nobody's grid in particular.
        if ($scope === 2 && $region === '' && app(BuiltInFactorCatalog::class)->isGridSource($source)) {
            return null;
        }

        $sourceRow = EmissionSource::query()
            ->where('scope', $scope)
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($lookupName))])
            ->first();

        if (! $sourceRow) {
            return null;
        }

        $factor = EmissionFactor::query()
            ->with('organization')
            ->where('emission_source_id', $sourceRow->id)
            ->whereRaw('LOWER(TRIM(unit)) = ?', [mb_strtolower(trim($unit))])
            // A region was asked for: match it exactly. Falling back to a
            // region-less row would price UAE electricity at a global average
            // and then present it as that client's regional figure.
            ->when($region !== '', fn ($q) => $q->whereRaw('LOWER(TRIM(region)) = ?', [mb_strtolower($region)]))
            // No region asked for: prefer a row that claims none. A row carrying
            // a region is THAT region's factor, and answering a region-less
            // question with it silently attributes one grid's intensity to
            // another. Regioned rows stay available as a last resort — refusing
            // outright would make a UK-only DEFRA activity unpriceable for a user
            // who did not think to state a region.
            ->when($region === '', fn ($q) => $q->orderByRaw('region IS NULL DESC'))
            // Prefer the publisher the caller asked for, without excluding the
            // others — a company that prefers DEFRA should still get a factor
            // when only IPCC publishes one for that activity.
            ->when(
                ! empty($context['organization_id']),
                fn ($q) => $q->orderByRaw('organization_id = ? DESC', [(int) $context['organization_id']])
            )
            ->when(
                // Only consider rows the library still considers current, when
                // that column is populated. A withdrawn factor should not price
                // new activity.
                true,
                fn ($query) => $query->where(function ($q) {
                    $q->whereNull('is_active')->orWhere('is_active', true);
                })
            )
            ->orderByDesc('id')
            ->first();

        if (! $factor) {
            return null;
        }

        // Converted, NOT the raw column. ResolvedFactor::$value is tCO2e per
        // activity unit, and factor_value is whatever basis its publisher used —
        // DEFRA's rows are kgCO2e. Passing the raw value through is what made a
        // DEFRA-priced record 1000x too large while still passing verification.
        $value = $factor->valueInTonnes();

        // A zero means two different things, and the difference matters.
        //
        // On a SEEDED row with no dataset behind it, zero is a placeholder — the
        // category-level Scope 3 entries like "Purchased Goods & Services [unit]"
        // that cannot have a per-unit factor. Pricing against one would report a
        // confident, arithmetically checkable ZERO for real emissions, which is
        // worse than storing no factor: an unverified figure is visibly
        // unverified, whereas a verified zero looks settled.
        //
        // On a row compiled or imported from a published catalogue, zero is a
        // MEASUREMENT. A battery-electric van has no direct Scope 1 emissions;
        // ammonia has a GWP of zero; a REC carries none by construction. Those
        // are real published values with real citations, and refusing them sent
        // 13 catalogue entries down the config fallback to be stored with a label
        // string instead of a factor id.
        //
        // The discriminator is provenance: a row that names its dataset came from
        // somewhere that meant it.
        if ($value === 0.0 && blank($factor->dataset_name)) {
            return null;
        }

        return new ResolvedFactor(
            value: $value,
            unit: (string) $factor->unit,
            source: (string) $sourceRow->name,
            reference: $this->reference($factor),
            gwpVersion: (string) ($factor->gwp_version ?: config('gwp.version', 'ar5')),
            catalogueVersion: (string) ($factor->dataset_version ?: 'library'),
            catalogue: 'Scope '.$scope,
            emissionFactorId: (int) $factor->id,
        );
    }

    /**
     * The strongest citation the row carries, so the stored figure can be
     * defended later without going back to the database to find out where the
     * number came from.
     */
    protected function reference(EmissionFactor $factor): ?string
    {
        $parts = array_filter([
            $factor->organization->name ?? null,
            $factor->dataset_name ?: null,
            $factor->source_reference ?: null,
        ]);

        return $parts === [] ? null : implode(', ', array_unique($parts));
    }
}

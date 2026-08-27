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
    public function resolve(int $scope, ?string $source, ?string $unit): ?ResolvedFactor
    {
        if ($source === null || trim($source) === '' || $unit === null || trim($unit) === '') {
            return null;
        }

        $sourceRow = EmissionSource::query()
            ->where('scope', $scope)
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($source))])
            ->first();

        if (! $sourceRow) {
            return null;
        }

        $factor = EmissionFactor::query()
            ->with('organization')
            ->where('emission_source_id', $sourceRow->id)
            ->whereRaw('LOWER(TRIM(unit)) = ?', [mb_strtolower(trim($unit))])
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

        $value = (float) $factor->factor_value;

        // See the class docblock: a zero is a placeholder, not a measurement.
        if ($value === 0.0) {
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

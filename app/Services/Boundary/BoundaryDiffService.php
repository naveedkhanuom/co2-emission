<?php

namespace App\Services\Boundary;

use App\Models\BoundaryAssessment;
use App\Models\BoundaryItem;
use App\Models\Company;
use Illuminate\Support\Collection;

/**
 * Compares a company's current inventory boundary with the one it reported
 * before.
 *
 * A boundary that changes between years is exactly what an assurer looks for,
 * because under the GHG Protocol a significant change in boundary is one of the
 * triggers for recalculating the base year. Without a diff, a source quietly
 * entering or leaving the inventory shows up only as an unexplained jump in the
 * totals.
 *
 * The comparison is descriptive, not automatic: it flags what changed and why
 * that might require recalculation, and leaves the decision to a person — the
 * significance threshold is a reporting policy, not something to infer.
 */
class BoundaryDiffService
{
    /**
     * Diff the company's active boundary against the one it superseded.
     * Returns null when there is nothing to compare (first-ever assessment).
     *
     * @return array<string, mixed>|null
     */
    public function forCompany(Company $company): ?array
    {
        $current = $company->activeBoundaryAssessment();

        if (! $current) {
            return null;
        }

        $previous = $this->previousFor($current);

        return $previous ? $this->compare($current, $previous) : null;
    }

    /**
     * The assessment this one replaced: the most recent non-draft assessment
     * that is not the current one, ordered by reporting year then version.
     */
    public function previousFor(BoundaryAssessment $current): ?BoundaryAssessment
    {
        return BoundaryAssessment::withoutGlobalScope('company')
            ->where('company_id', $current->company_id)
            ->where('id', '!=', $current->id)
            ->where('status', '!=', 'draft')
            ->orderByDesc('reporting_year')
            ->orderByDesc('version')
            ->first();
    }

    /**
     * @return array{
     *   previous: array<string, mixed>,
     *   current: array<string, mixed>,
     *   added: Collection,
     *   removed: Collection,
     *   changed: Collection,
     *   requires_recalculation: bool,
     *   reasons: array<int, string>,
     *   has_changes: bool
     * }
     */
    public function compare(BoundaryAssessment $current, BoundaryAssessment $previous): array
    {
        $currentItems = $this->includedBySignature($current);
        $previousItems = $this->includedBySignature($previous);

        $added = $currentItems->reject(fn ($item, $sig) => $previousItems->has($sig))->values();
        $removed = $previousItems->reject(fn ($item, $sig) => $currentItems->has($sig))->values();

        // Sources present in both, but whose assessed materiality moved.
        $changed = $currentItems
            ->filter(fn ($item, $sig) => $previousItems->has($sig)
                && $previousItems[$sig]->materiality !== $item->materiality)
            ->map(fn ($item, $sig) => [
                'item' => $item,
                'from' => $previousItems[$sig]->materiality,
                'to' => $item->materiality,
            ])
            ->values();

        $reasons = $this->recalculationReasons($added, $removed, $previous, $current);

        return [
            'previous' => [
                'year' => (int) $previous->reporting_year,
                'version' => (int) $previous->version,
                'included' => $previousItems->count(),
            ],
            'current' => [
                'year' => (int) $current->reporting_year,
                'version' => (int) $current->version,
                'included' => $currentItems->count(),
            ],
            'added' => $added,
            'removed' => $removed,
            'changed' => $changed,
            'requires_recalculation' => ! empty($reasons),
            'reasons' => $reasons,
            'has_changes' => $added->isNotEmpty() || $removed->isNotEmpty() || $changed->isNotEmpty(),
        ];
    }

    /**
     * Included items keyed by their cross-year signature.
     *
     * @return Collection<string, BoundaryItem>
     */
    protected function includedBySignature(BoundaryAssessment $assessment): Collection
    {
        return $assessment->items()
            ->where('decision', 'included')
            ->with('scope3Category')
            ->get()
            ->keyBy(fn (BoundaryItem $item) => $item->signature());
    }

    /**
     * Why this change might require a base-year recalculation.
     *
     * The GHG Protocol requires recalculation for significant changes in
     * inventory boundary, structure or methodology. We surface the candidates —
     * high-materiality sources entering or leaving, Scope 3 categories moving
     * in or out, and a change of reporting year — rather than asserting that
     * the company's own significance threshold has been crossed.
     *
     * @return array<int, string>
     */
    protected function recalculationReasons(
        Collection $added,
        Collection $removed,
        BoundaryAssessment $previous,
        BoundaryAssessment $current,
    ): array {
        $reasons = [];

        $materialAdded = $added->where('materiality', 'high');
        $materialRemoved = $removed->where('materiality', 'high');

        if ($materialAdded->isNotEmpty()) {
            $reasons[] = sprintf(
                '%d high-impact %s entered the boundary: %s.',
                $materialAdded->count(),
                $materialAdded->count() === 1 ? 'source' : 'sources',
                $materialAdded->pluck('suggested_name')->take(3)->implode(', ')
            );
        }

        if ($materialRemoved->isNotEmpty()) {
            $reasons[] = sprintf(
                '%d high-impact %s left the boundary: %s.',
                $materialRemoved->count(),
                $materialRemoved->count() === 1 ? 'source' : 'sources',
                $materialRemoved->pluck('suggested_name')->take(3)->implode(', ')
            );
        }

        $categoriesAdded = $added->where('scope', 3)->pluck('scope3_category_id')->filter()->unique();
        $categoriesRemoved = $removed->where('scope', 3)->pluck('scope3_category_id')->filter()->unique();

        if ($categoriesAdded->isNotEmpty()) {
            $reasons[] = sprintf(
                '%d Scope 3 %s newly assessed as relevant.',
                $categoriesAdded->count(),
                $categoriesAdded->count() === 1 ? 'category was' : 'categories were'
            );
        }

        if ($categoriesRemoved->isNotEmpty()) {
            $reasons[] = sprintf(
                '%d Scope 3 %s no longer reported.',
                $categoriesRemoved->count(),
                $categoriesRemoved->count() === 1 ? 'category is' : 'categories are'
            );
        }

        if ($reasons && $previous->reporting_year !== $current->reporting_year) {
            $reasons[] = sprintf(
                'The boundary changed between reporting years %d and %d, so year-on-year totals are not directly comparable until the base year is reviewed.',
                $previous->reporting_year,
                $current->reporting_year
            );
        }

        return $reasons;
    }
}

<?php

namespace App\Services\Boundary;

use App\Models\BoundaryAssessment;
use App\Models\BoundaryItem;
use App\Models\Company;
use App\Models\EmissionRecord;
use Illuminate\Support\Collection;

/**
 * Boundary coverage — "14 of 19 things you said you'd measure have data for
 * 2026".
 *
 * This is the number that turns a one-off setup wizard into something users
 * come back to: it converts the boundary from a document into a work queue, and
 * gives Data Health a to-do list that is specific to the company rather than
 * generic.
 */
class BoundaryCoverageService
{
    /**
     * Coverage for a company's active boundary in a given year.
     *
     * @return array{
     *   has_boundary: bool, year: int, total: int, covered: int, percent: float,
     *   gaps: \Illuminate\Support\Collection<int, BoundaryItem>,
     *   assessment: ?BoundaryAssessment
     * }
     */
    public function forCompany(Company $company, ?int $year = null): array
    {
        $year ??= (int) now()->year;
        $assessment = $company->activeBoundaryAssessment();

        if (! $assessment) {
            return [
                'has_boundary' => false,
                'year' => $year,
                'total' => 0,
                'covered' => 0,
                'percent' => 0.0,
                'gaps' => collect(),
                'assessment' => null,
            ];
        }

        $items = $assessment->includedItems()
            ->with(['scope3Category', 'emissionSource'])
            ->get();

        if ($items->isEmpty()) {
            return [
                'has_boundary' => true,
                'year' => $year,
                'total' => 0,
                'covered' => 0,
                'percent' => 0.0,
                'gaps' => collect(),
                'assessment' => $assessment,
            ];
        }

        $signatures = $this->recordSignatures($company->id, $year);

        [$covered, $gaps] = $items->partition(
            fn (BoundaryItem $item) => $this->isCovered($item, $signatures)
        );

        return [
            'has_boundary' => true,
            'year' => $year,
            'total' => $items->count(),
            'covered' => $covered->count(),
            'percent' => round($covered->count() / $items->count() * 100, 1),
            'gaps' => $gaps->values(),
            'assessment' => $assessment,
        ];
    }

    /**
     * One query for every record signature in the year, so coverage costs a
     * single round trip rather than one per boundary item.
     *
     * Note `emission_records` identifies its source by NAME (`emission_source`,
     * a string), not by a foreign key — there is no `emission_source_id` column
     * on that table. Scope 1 and 2 coverage therefore has to match on text.
     *
     * @return array{scopes: int[], categories: int[], names: string[]}
     */
    protected function recordSignatures(int $companyId, int $year): array
    {
        $records = EmissionRecord::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereYear('entry_date', $year)
            ->get(['scope', 'scope3_category_id', 'emission_source']);

        return [
            'scopes' => $records->pluck('scope')->unique()->filter()->values()->all(),
            'categories' => $records->pluck('scope3_category_id')->unique()->filter()->values()->all(),
            'names' => $records->pluck('emission_source')
                ->filter()
                ->map(fn ($n) => $this->normaliseName((string) $n))
                ->filter()
                ->unique()
                ->values()
                ->all(),
        ];
    }

    /**
     * An item counts as covered when a record exists that plausibly belongs to
     * it: by Scope 3 category (exact and reliable), otherwise by source name.
     */
    protected function isCovered(BoundaryItem $item, array $signatures): bool
    {
        if ($item->scope === 3 && $item->scope3_category_id) {
            return in_array($item->scope3_category_id, $signatures['categories'], true);
        }

        // Try the catalogue name first — it is the vocabulary entry screens use
        // — then fall back to the advisor's own descriptive name.
        $candidates = array_filter([
            $item->emissionSource?->name,
            $item->suggested_name,
        ]);

        foreach ($candidates as $candidate) {
            $needle = $this->normaliseName($candidate);

            if ($needle === '') {
                continue;
            }

            foreach ($signatures['names'] as $name) {
                if ($this->namesMatch($needle, $name)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Normalise a source name for comparison.
     *
     * Entered sources are free text and inconsistent — "Scope 3 - 7. Employee
     * Commuting", "Company Fleet - Diesel", "Purchased Electricity
     * (Location-Based)". Strip the scope/category prefix and punctuation so
     * only the meaningful words remain.
     */
    protected function normaliseName(string $name): string
    {
        $name = mb_strtolower(trim($name));

        // Drop a leading "scope 3 - 7." style prefix.
        $name = (string) preg_replace('/^scope\s*[123]\s*[-–:]?\s*\d*\.?\s*/u', '', $name);

        // Punctuation and separators become spaces; collapse the result.
        $name = (string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', $name);

        return trim((string) preg_replace('/\s+/', ' ', $name));
    }

    /**
     * Whether two normalised source names plausibly describe the same thing.
     *
     * A deliberate heuristic: substring either way, or every meaningful word of
     * the shorter name present in the longer. Coverage drives a progress bar
     * and a to-do list, so it errs towards UNDER-counting — telling someone a
     * gap is still open when it is closed is far less harmful than telling them
     * it is closed when it is not.
     */
    protected function namesMatch(string $a, string $b): bool
    {
        if ($a === '' || $b === '') {
            return false;
        }

        if (str_contains($a, $b) || str_contains($b, $a)) {
            return true;
        }

        $stopWords = ['and', 'the', 'of', 'for', 'from', 'our', 'we', 'in', 'on', 'to', 'a'];

        $tokens = function (string $value) use ($stopWords): array {
            return array_values(array_filter(
                explode(' ', $value),
                fn ($t) => mb_strlen($t) > 2 && ! in_array($t, $stopWords, true)
            ));
        };

        $left = $tokens($a);
        $right = $tokens($b);

        if (empty($left) || empty($right)) {
            return false;
        }

        [$shorter, $longer] = count($left) <= count($right) ? [$left, $right] : [$right, $left];

        return empty(array_diff($shorter, $longer));
    }

    /**
     * The highest-impact gaps first — what the user should close next.
     *
     * @return Collection<int, BoundaryItem>
     */
    public function topGaps(Company $company, ?int $year = null, int $limit = 5): Collection
    {
        return $this->forCompany($company, $year)['gaps']
            ->sortBy(fn (BoundaryItem $item) => [$item->materialityRank(), -(float) $item->typical_share_pct])
            ->take($limit)
            ->values();
    }
}

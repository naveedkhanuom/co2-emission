<?php

namespace App\Services\Boundary;

use App\Models\BoundaryAssessment;
use App\Models\Company;
use App\Models\Scope3Category;
use Illuminate\Support\Collection;

/**
 * Builds the Boundary Statement — the auditor-facing artefact the advisor
 * exists to produce.
 *
 * Two things a reporting company owes and cannot generate from its numbers
 * alone:
 *
 *  - a description of the **inventory boundary** (ESRS E1 / GHG Protocol
 *    Chapter 4): which entities, which sources, on what basis;
 *  - a screening of **all 15 Scope 3 categories** with a written justification
 *    for every one excluded (GHG Protocol Corporate Value Chain Standard).
 *
 * Both fall out of the decisions the user already made on the checklist, so the
 * statement is a rendering of the audit trail rather than a new document to
 * maintain.
 */
class BoundaryStatementService
{
    public function __construct(
        protected BoundaryCoverageService $coverage,
        protected BoundaryDiffService $diff,
    ) {}

    /**
     * Assemble the statement for one assessment.
     *
     * @return array{
     *   meta: array<string, mixed>,
     *   narrative: string,
     *   scope3_screening: array<int, array<string, mixed>>,
     *   included: Collection,
     *   excluded: Collection,
     *   deferred: Collection,
     *   coverage: array<string, mixed>
     * }
     */
    public function build(Company $company, BoundaryAssessment $assessment): array
    {
        $items = $assessment->items()
            ->with(['scope3Category', 'emissionSource', 'acceptedBy'])
            ->get();

        $included = $items->where('decision', 'included')->values();
        $excluded = $items->where('decision', 'excluded')->values();
        $deferred = $items->where('decision', 'deferred')->values();

        $boundaryKey = $company->getSetting('consolidation_approach', config('boundary.default'))
            ?? config('boundary.default');

        $consolidation = config("boundary.labels.$boundaryKey")
            ?? config('boundary.labels.'.config('boundary.default'));

        return [
            'meta' => [
                'company' => $company->name,
                'country' => $company->country,
                'industry' => $company->industry_type,
                'business_description' => $company->business_description,
                'year' => (int) $assessment->reporting_year,
                'version' => (int) $assessment->version,
                'status' => $assessment->status,
                'consolidation' => $consolidation,
                'generated_on' => now()->format('Y-m-d H:i'),
                // Provenance — an assurer will ask how the boundary was derived
                // and who signed it off.
                'method' => $assessment->isAiGenerated() ? 'AI-assisted, human-approved' : 'Industry templates, human-approved',
                'model' => $assessment->model,
                'prompt_version' => $assessment->prompt_version,
                'confidence' => $assessment->confidence,
                'completed_by' => $assessment->completedBy?->name,
                'completed_at' => $assessment->completed_at?->format('Y-m-d'),
            ],
            'narrative' => $this->narrative($company, $assessment, $included, $excluded, $consolidation),
            'scope3_screening' => $this->scope3Screening($items),
            'included' => $included,
            'excluded' => $excluded,
            'deferred' => $deferred,
            'coverage' => $this->coverage->forCompany($company, (int) $assessment->reporting_year),
            // Changes against the boundary this one replaced. A boundary change
            // is a GHG Protocol trigger for base-year recalculation, so it
            // belongs on the statement rather than only in the UI.
            'changes' => ($previous = $this->diff->previousFor($assessment))
                ? $this->diff->compare($assessment, $previous)
                : null,
        ];
    }

    /**
     * Plain-language boundary description, suitable for pasting into a
     * sustainability statement. Built from the decisions on record — never
     * invented.
     */
    protected function narrative(
        Company $company,
        BoundaryAssessment $assessment,
        Collection $included,
        Collection $excluded,
        string $consolidation,
    ): string {
        $year = $assessment->reporting_year;
        $scopes = $included->pluck('scope')->unique()->sort()->values();

        $scopeText = $scopes->isEmpty()
            ? 'no scopes'
            : 'Scope '.$scopes->join(', ', ' and ');

        $categoriesCovered = $included->where('scope', 3)
            ->pluck('scope3_category_id')->filter()->unique()->count();

        $parts = [];

        $parts[] = sprintf(
            '%s reports greenhouse gas emissions for the %d reporting year using the %s consolidation approach.',
            $company->name,
            $year,
            mb_strtolower($consolidation)
        );

        $parts[] = sprintf(
            'The inventory covers %s, comprising %d emission source%s identified as relevant to the organisation.',
            $scopeText,
            $included->count(),
            $included->count() === 1 ? '' : 's'
        );

        if ($categoriesCovered > 0) {
            $parts[] = sprintf(
                'Of the 15 Scope 3 categories defined by the GHG Protocol Corporate Value Chain Standard, %d %s determined to be relevant and %s included in the inventory.',
                $categoriesCovered,
                $categoriesCovered === 1 ? 'was' : 'were',
                $categoriesCovered === 1 ? 'is' : 'are'
            );
        }

        if ($excluded->isNotEmpty()) {
            $parts[] = sprintf(
                '%d source%s %s assessed as not relevant to the organisation; the justification for each exclusion is recorded in this statement.',
                $excluded->count(),
                $excluded->count() === 1 ? '' : 's',
                $excluded->count() === 1 ? 'was' : 'were'
            );
        }

        $parts[] = $assessment->isAiGenerated()
            ? 'The boundary was scoped with AI assistance against the organisation\'s business profile and reviewed and approved by a responsible person before adoption.'
            : 'The boundary was scoped from sector emission-source templates and reviewed and approved by a responsible person before adoption.';

        return implode(' ', $parts);
    }

    /**
     * All 15 Scope 3 categories with their relevance decision and reason.
     *
     * The Corporate Value Chain Standard requires a decision on every category,
     * so a category the company never touched is reported as "not screened"
     * rather than silently omitted — an omission would read as a decision.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function scope3Screening(Collection $items): array
    {
        $byCategory = $items->where('scope', 3)
            ->filter(fn ($i) => $i->scope3_category_id !== null)
            ->groupBy('scope3_category_id');

        $out = [];

        foreach (Scope3Category::orderBy('sort_order')->get() as $category) {
            $categoryItems = $byCategory->get($category->id, collect());

            $included = $categoryItems->where('decision', 'included');
            $excluded = $categoryItems->where('decision', 'excluded');

            if ($included->isNotEmpty()) {
                $status = 'Relevant — included';
                $reason = $included->pluck('rationale')->filter()->first()
                    ?? 'Determined relevant to the organisation.';
            } elseif ($excluded->isNotEmpty()) {
                $status = 'Not relevant — excluded';
                $reason = $excluded->pluck('exclusion_reason')->filter()->first()
                    ?? 'Excluded; no justification recorded.';
            } elseif ($categoryItems->isNotEmpty()) {
                $status = 'Not yet determined';
                $reason = 'Screening not completed for this category.';
            } else {
                $status = 'Not screened';
                $reason = 'This category was not assessed.';
            }

            $out[] = [
                'number' => (int) $category->sort_order,
                'name' => $category->name,
                'status' => $status,
                'reason' => $reason,
                'sources' => $included->pluck('suggested_name')->implode(', '),
            ];
        }

        return $out;
    }

    /**
     * Flat rows for the spreadsheet export.
     *
     * @param  array<string, mixed>  $statement
     * @return array<int, array<int, string>>
     */
    public function screeningRows(array $statement): array
    {
        return array_map(fn ($row) => [
            (string) $row['number'],
            $row['name'],
            $row['status'],
            $row['reason'],
            $row['sources'],
        ], $statement['scope3_screening']);
    }
}

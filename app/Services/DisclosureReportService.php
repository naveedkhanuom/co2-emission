<?php

namespace App\Services;

use App\Models\Company;
use App\Models\EmissionRecord;
use App\Models\Scope3Category;
use App\Support\Gwp;

/**
 * Builds a regulatory-disclosure dataset from a company's emission inventory and
 * maps it to the datapoints of the major frameworks:
 *   - CSRD / ESRS E1 (Climate change)
 *   - CDP Climate Change questionnaire (C6/C7)
 *   - GRI 305 (Emissions)
 *
 * All three draw from one internally consistent inventory so the numbers can
 * never diverge between filings. Figures are tCO2e under the company's chosen
 * GWP set; Scope 2 is dual-reported (location- and market-based).
 */
class DisclosureReportService
{
    public const FRAMEWORKS = [
        'esrs_e1' => 'CSRD / ESRS E1 — Climate Change',
        'cdp' => 'CDP Climate Change (C6–C7)',
        'gri_305' => 'GRI 305 — Emissions',
    ];

    /**
     * Assemble the inventory for one company + reporting year.
     */
    public function build(int $companyId, int $year, ?string $gwpVersion = null): array
    {
        // Figures are computed on the bundled factor basis (config gwp.factor_basis),
        // so the disclosure must state THAT GWP set — the stated set has to match
        // the math. The company's preferred version is future-facing until the
        // factor tables are re-based. (Param kept for signature compatibility.)
        $gwpVersion = Gwp::factorBasis();

        $records = EmissionRecord::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->whereYear('entry_date', $year)
            ->get();

        $scope1 = (float) $records->where('scope', 1)->sum('co2e_value');
        $scope2Loc = (float) $records->where('scope', 2)->sum('co2e_value');
        $scope2Mkt = (float) $records->where('scope', 2)
            ->reduce(fn ($c, $r) => $c + $r->marketBasedCo2e(), 0.0);
        $scope3 = (float) $records->where('scope', 3)->sum('co2e_value');

        // Gas breakdown: sum the per-gas split where records carry it; lump the
        // remainder (records without a breakdown) into CO2 as the conservative
        // default, since CO2 dominates most inventories.
        $co2 = (float) $records->sum('co2e_co2');
        $ch4 = (float) $records->sum('co2e_ch4');
        $n2o = (float) $records->sum('co2e_n2o');
        $other = (float) $records->sum('co2e_other');
        $biogenic = (float) $records->sum('biogenic_co2');
        $totalGross = $scope1 + $scope2Loc + $scope3;
        $accountedByGas = $co2 + $ch4 + $n2o + $other;
        $unallocated = max($totalGross - $accountedByGas, 0);
        $co2 += $unallocated; // attribute unspecified residual to CO2

        $scope3ByCategory = $this->scope3ByCategory($records);

        $company = Company::find($companyId);

        // Organizational boundary chosen during onboarding (falls back to the
        // GHG Protocol default when a company predates the setting).
        $boundaryKey = $company?->getSetting('consolidation_approach', config('boundary.default'))
            ?? config('boundary.default');
        $consolidation = config("boundary.labels.$boundaryKey")
            ?? config('boundary.labels.'.config('boundary.default'));

        return [
            'meta' => [
                'company' => $company?->name ?? 'Company',
                'country' => $company?->country,
                'year' => $year,
                'gwp_version' => $gwpVersion,
                'gwp_label' => Gwp::label($gwpVersion),
                'generated_on' => now()->format('Y-m-d H:i'),
                'record_count' => $records->count(),
                'consolidation' => $consolidation,
            ],
            'totals' => [
                'scope1' => round($scope1, 2),
                'scope2_location' => round($scope2Loc, 2),
                'scope2_market' => round($scope2Mkt, 2),
                'scope3' => round($scope3, 2),
                'total_location' => round($scope1 + $scope2Loc + $scope3, 2),
                'total_market' => round($scope1 + $scope2Mkt + $scope3, 2),
                'biogenic_co2' => round($biogenic, 2),
            ],
            'gases' => [
                'co2' => round($co2, 2),
                'ch4' => round($ch4, 2),
                'n2o' => round($n2o, 2),
                'other' => round($other, 2),
            ],
            'scope3_by_category' => $scope3ByCategory,
            'boundary' => $this->boundarySummary($company),
        ];
    }

    /**
     * Operational-boundary summary from the company's active Boundary
     * Assessment, if it has one.
     *
     * ESRS E1 and the Scope 3 Standard both require the reporting boundary to
     * be described and every excluded Scope 3 category justified — information
     * the numbers alone cannot supply. Companies that have not scoped a
     * boundary get null, and the disclosure simply omits those rows rather than
     * asserting anything unevidenced.
     *
     * @return array<string, mixed>|null
     */
    protected function boundarySummary(?Company $company): ?array
    {
        $assessment = $company?->activeBoundaryAssessment();

        if (! $assessment) {
            return null;
        }

        $items = $assessment->items()->get(['scope', 'scope3_category_id', 'decision']);

        $relevantCategories = $items
            ->where('decision', 'included')
            ->where('scope', 3)
            ->pluck('scope3_category_id')
            ->filter()
            ->unique();

        $excludedCategories = $items
            ->where('decision', 'excluded')
            ->where('scope', 3)
            ->pluck('scope3_category_id')
            ->filter()
            ->unique()
            ->diff($relevantCategories);

        return [
            'assessment_id' => $assessment->id,
            'year' => (int) $assessment->reporting_year,
            'version' => (int) $assessment->version,
            'method' => $assessment->isAiGenerated() ? 'AI-assisted, human-approved' : 'Sector templates, human-approved',
            'included_sources' => $items->where('decision', 'included')->count(),
            'excluded_sources' => $items->where('decision', 'excluded')->count(),
            'scope3_relevant' => $relevantCategories->count(),
            'scope3_excluded' => $excludedCategories->count(),
            'scope3_total' => Scope3Category::count(),
        ];
    }

    /**
     * Scope 3 emissions grouped by the 15 GHG Protocol categories.
     *
     * @return array<int, array{number:int, name:string, co2e:float}>
     */
    protected function scope3ByCategory($records): array
    {
        $categories = Scope3Category::orderBy('sort_order')->get(['id', 'name', 'sort_order']);
        $byId = $records->where('scope', 3)->groupBy('scope3_category_id');

        $out = [];
        foreach ($categories as $cat) {
            $out[] = [
                'number' => (int) $cat->sort_order,
                'name' => $cat->name,
                'co2e' => round((float) optional($byId->get($cat->id))->sum('co2e_value'), 2),
            ];
        }

        // Uncategorised Scope 3 (no category FK).
        $uncat = (float) optional($byId->get(null))->sum('co2e_value');
        if ($uncat > 0) {
            $out[] = ['number' => 0, 'name' => 'Uncategorised Scope 3', 'co2e' => round($uncat, 2)];
        }

        return $out;
    }

    /**
     * Render the inventory as a flat list of datapoints for one framework.
     * Each row: ['ref' => code, 'label' => text, 'value' => string|number, 'unit' => string].
     */
    public function datapoints(string $framework, array $data): array
    {
        return match ($framework) {
            'esrs_e1' => $this->esrsE1($data),
            'cdp' => $this->cdp($data),
            'gri_305' => $this->gri305($data),
            default => [],
        };
    }

    protected function esrsE1(array $d): array
    {
        $t = $d['totals'];

        return array_merge([
            ['ref' => 'E1-6 §44(a)', 'label' => 'Gross Scope 1 GHG emissions', 'value' => $t['scope1'], 'unit' => 'tCO₂e'],
            ['ref' => 'E1-6 §44(b)', 'label' => 'Gross Scope 2 GHG emissions (location-based)', 'value' => $t['scope2_location'], 'unit' => 'tCO₂e'],
            ['ref' => 'E1-6 §44(b)', 'label' => 'Gross Scope 2 GHG emissions (market-based)', 'value' => $t['scope2_market'], 'unit' => 'tCO₂e'],
            ['ref' => 'E1-6 §44(c)', 'label' => 'Gross Scope 3 GHG emissions', 'value' => $t['scope3'], 'unit' => 'tCO₂e'],
            ['ref' => 'E1-6 §44(d)', 'label' => 'Total GHG emissions (location-based)', 'value' => $t['total_location'], 'unit' => 'tCO₂e'],
            ['ref' => 'E1-6 §44(d)', 'label' => 'Total GHG emissions (market-based)', 'value' => $t['total_market'], 'unit' => 'tCO₂e'],
            ['ref' => 'E1-6 §48', 'label' => 'Biogenic CO₂ emissions (reported separately)', 'value' => $t['biogenic_co2'], 'unit' => 'tCO₂'],
            ['ref' => 'E1 GWP', 'label' => 'GWP assessment basis applied', 'value' => $d['meta']['gwp_label'], 'unit' => ''],
            ['ref' => 'E1 boundary', 'label' => 'Consolidation approach', 'value' => $d['meta']['consolidation'], 'unit' => ''],
        ], $this->boundaryDatapoints($d));
    }

    /**
     * Operational-boundary datapoints, appended when the company has scoped a
     * boundary. Empty otherwise — an unevidenced boundary claim is worse than
     * no claim on a filing.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function boundaryDatapoints(array $d): array
    {
        $b = $d['boundary'] ?? null;

        if (! $b) {
            return [];
        }

        return [
            ['ref' => 'E1 boundary', 'label' => 'Operational boundary determined by', 'value' => $b['method'], 'unit' => ''],
            ['ref' => 'E1 boundary', 'label' => 'Emission sources included in the inventory boundary', 'value' => $b['included_sources'], 'unit' => 'sources'],
            ['ref' => 'E1 boundary', 'label' => 'Emission sources excluded with recorded justification', 'value' => $b['excluded_sources'], 'unit' => 'sources'],
            ['ref' => 'E1-6 Scope 3', 'label' => 'Scope 3 categories screened as relevant', 'value' => $b['scope3_relevant'].' of '.$b['scope3_total'], 'unit' => ''],
            ['ref' => 'E1-6 Scope 3', 'label' => 'Scope 3 categories excluded as not relevant', 'value' => $b['scope3_excluded'].' of '.$b['scope3_total'], 'unit' => ''],
        ];
    }

    protected function cdp(array $d): array
    {
        $t = $d['totals'];
        $g = $d['gases'];

        return [
            ['ref' => 'C6.1', 'label' => 'Scope 1 emissions', 'value' => $t['scope1'], 'unit' => 'metric tons CO₂e'],
            ['ref' => 'C6.3', 'label' => 'Scope 2, location-based', 'value' => $t['scope2_location'], 'unit' => 'metric tons CO₂e'],
            ['ref' => 'C6.3', 'label' => 'Scope 2, market-based', 'value' => $t['scope2_market'], 'unit' => 'metric tons CO₂e'],
            ['ref' => 'C6.5', 'label' => 'Scope 3 total', 'value' => $t['scope3'], 'unit' => 'metric tons CO₂e'],
            ['ref' => 'C6.7', 'label' => 'Biogenic CO₂', 'value' => $t['biogenic_co2'], 'unit' => 'metric tons CO₂'],
            ['ref' => 'C6.10', 'label' => 'CO₂ (of total)', 'value' => $g['co2'], 'unit' => 'metric tons CO₂e'],
            ['ref' => 'C6.10', 'label' => 'CH₄ (of total)', 'value' => $g['ch4'], 'unit' => 'metric tons CO₂e'],
            ['ref' => 'C6.10', 'label' => 'N₂O (of total)', 'value' => $g['n2o'], 'unit' => 'metric tons CO₂e'],
            ['ref' => 'C5.1', 'label' => 'Global warming potentials used', 'value' => $d['meta']['gwp_label'], 'unit' => ''],
        ];
    }

    protected function gri305(array $d): array
    {
        $t = $d['totals'];

        return [
            ['ref' => '305-1', 'label' => 'Direct (Scope 1) GHG emissions', 'value' => $t['scope1'], 'unit' => 'tCO₂e'],
            ['ref' => '305-1-c', 'label' => 'Biogenic CO₂ emissions', 'value' => $t['biogenic_co2'], 'unit' => 'tCO₂'],
            ['ref' => '305-2 (LB)', 'label' => 'Energy indirect (Scope 2) — location-based', 'value' => $t['scope2_location'], 'unit' => 'tCO₂e'],
            ['ref' => '305-2 (MB)', 'label' => 'Energy indirect (Scope 2) — market-based', 'value' => $t['scope2_market'], 'unit' => 'tCO₂e'],
            ['ref' => '305-3', 'label' => 'Other indirect (Scope 3) GHG emissions', 'value' => $t['scope3'], 'unit' => 'tCO₂e'],
            ['ref' => '305-1/2/3-d', 'label' => 'GWP source basis', 'value' => $d['meta']['gwp_label'], 'unit' => ''],
            ['ref' => '305-1/2/3-e', 'label' => 'Consolidation approach', 'value' => $d['meta']['consolidation'], 'unit' => ''],
        ];
    }
}

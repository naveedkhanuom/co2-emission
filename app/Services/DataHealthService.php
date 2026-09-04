<?php

namespace App\Services;

use App\Models\Company;
use App\Models\EmissionRecord;
use App\Models\Scope3Category;
use App\Services\Boundary\BoundaryCoverageService;
use Illuminate\Support\Facades\DB;

/**
 * What have I done, what is missing, and what should I do next — for one
 * company in one reporting year.
 *
 * WHY THIS IS A SERVICE AND NOT A CONTROLLER METHOD
 *
 * It was a controller method, and only /data-health could ask it. That page is
 * a good page nobody visits: the answer it computes — a prioritised, deep-linked
 * list of the next things worth doing — is exactly what a client needs on the
 * screen they actually land on, which is the dashboard.
 *
 * So the assessment moved here and the dashboard reads it too. Data Health
 * renders all of it; the dashboard shows the top few steps and links across.
 * One implementation, so the two screens cannot come to different conclusions
 * about what a client should do next.
 *
 * Everything is derived from data that already exists — EmissionRecord is
 * company-scoped by HasCompanyScope — so there is nothing to keep in step and
 * no table to migrate.
 */
class DataHealthService
{
    public function __construct(private BoundaryCoverageService $boundaryCoverage) {}

    /**
     * The full assessment: per-scope coverage, Scope 3 breadth, time coverage,
     * data-quality mix, boundary coverage, dimension scores, an overall health
     * score, and the prioritised next steps.
     *
     * @return array<string, mixed>
     */
    public function assess(?Company $company, ?int $year = null): array
    {
        $year ??= (int) ($company?->getSetting('base_year', now()->year) ?? now()->year);

        // How far through the reporting year we are — used so months that
        // haven't happened yet don't count against completeness.
        $monthsElapsed = ($year === (int) now()->year) ? (int) now()->month : 12;

        $enabledScopes = collect($company?->scopes_enabled ?: [1, 2, 3])
            ->map(fn ($s) => (int) $s)->filter()->unique()->sort()->values();

        // ---- Per-scope coverage (any status counts as "has data") ----
        $scopeAgg = EmissionRecord::selectRaw('scope, COUNT(*) as cnt, MAX(entry_date) as last_date, SUM(co2e_value) as total')
            ->groupBy('scope')->get()->keyBy('scope');

        $scopeMeta = [
            1 => ['label' => 'Scope 1', 'desc' => 'Direct — fuel, vehicles, refrigerants', 'route' => 'scope1_entry.index', 'icon' => 'fa-fire'],
            2 => ['label' => 'Scope 2', 'desc' => 'Purchased electricity, heat & steam',   'route' => 'scope2_entry.index', 'icon' => 'fa-bolt'],
            3 => ['label' => 'Scope 3', 'desc' => 'Value chain — travel, goods, waste',     'route' => 'scope3_entry.index', 'icon' => 'fa-sitemap'],
        ];

        $scopes = [];
        foreach ([1, 2, 3] as $s) {
            $row = $scopeAgg->get($s);
            $scopes[$s] = [
                'label' => $scopeMeta[$s]['label'],
                'desc' => $scopeMeta[$s]['desc'],
                'route' => $scopeMeta[$s]['route'],
                'icon' => $scopeMeta[$s]['icon'],
                'enabled' => $enabledScopes->contains($s),
                'count' => (int) ($row->cnt ?? 0),
                'total' => round((float) ($row->total ?? 0), 2),
                'last_date' => $row->last_date ?? null,
                'has_data' => (int) ($row->cnt ?? 0) > 0,
            ];
        }

        // ---- Scope 3 category completeness (15 GHG Protocol categories) ----
        $allCats = Scope3Category::where('is_active', true)->orderBy('sort_order')->orderBy('code')->get();
        $catsWithData = EmissionRecord::where('scope', 3)
            ->whereNotNull('scope3_category_id')
            ->distinct()->pluck('scope3_category_id');
        $missingCats = $allCats->whereNotIn('id', $catsWithData)->values();

        $scope3 = [
            'enabled' => $enabledScopes->contains(3),
            'total' => $allCats->count(),
            'covered' => $allCats->count() - $missingCats->count(),
            'missing_list' => $missingCats->take(6)->map(fn ($c) => trim(($c->code ? $c->code.' ' : '').$c->name))->all(),
            'missing_more' => max(0, $missingCats->count() - 6),
        ];

        // ---- Records awaiting review (not yet finalised as active) ----
        $pendingReview = EmissionRecord::where('status', '!=', 'active')->count();

        // ---- Time coverage for the reporting year ----
        $monthsCovered = EmissionRecord::where('status', 'active')
            ->whereYear('entry_date', $year)
            ->distinct()->count(DB::raw('MONTH(entry_date)'));

        // ---- Data-quality mix ----
        $dq = EmissionRecord::where('status', 'active')
            ->selectRaw('data_quality, COUNT(*) as c')->groupBy('data_quality')->pluck('c', 'data_quality');
        $dqTotal = (int) $dq->sum();
        $dqPrimary = (int) ($dq['primary'] ?? 0);
        $dqSecondary = (int) ($dq['secondary'] ?? 0);
        $dqEstimated = (int) ($dq['estimated'] ?? 0);

        $totalRecords = (int) $scopeAgg->sum('cnt');

        // ---- Boundary coverage ----
        // Every other dimension measures data the company happened to enter.
        // This one measures it against what the company itself decided it needs
        // to measure, which is the only completeness figure that means anything
        // to an auditor.
        $boundary = $company
            ? $this->boundaryCoverage->forCompany($company, $year)
            : ['has_boundary' => false, 'total' => 0, 'covered' => 0, 'percent' => 0.0, 'gaps' => collect(), 'year' => $year];

        $boundary['top_gaps'] = $boundary['gaps']
            ->sortBy(fn ($item) => [$item->materialityRank(), -(float) $item->typical_share_pct])
            ->take(3)
            ->values();

        // ---- Dimension scores (0-100). Null = not applicable, excluded from average. ----
        $enabledCount = max(1, $enabledScopes->count());
        $enabledWith = $enabledScopes->filter(fn ($s) => $scopes[$s]['has_data'])->count();
        $dimCoverage = round($enabledWith / $enabledCount * 100);
        $dimScope3 = $scope3['enabled'] ? round($scope3['covered'] / max(1, $scope3['total']) * 100) : null;
        $dimTime = round($monthsCovered / max(1, $monthsElapsed) * 100);
        $dimTime = min(100, $dimTime);
        $dimReview = $totalRecords > 0 ? round(($totalRecords - $pendingReview) / $totalRecords * 100) : null;
        $dimQuality = $dqTotal > 0 ? round((($dqPrimary * 100) + ($dqSecondary * 50)) / $dqTotal) : null;

        // A company that has never run the Boundary Advisor is not penalised —
        // the dimension is simply not applicable, and a next step invites them
        // to scope it. Scoring it zero would drop every existing tenant's health
        // score overnight for a feature they have not been asked to use yet.
        $dimBoundary = ($boundary['has_boundary'] && $boundary['total'] > 0)
            ? (int) round($boundary['percent'])
            : null;

        $dimensions = [
            ['key' => 'boundary', 'label' => 'Boundary coverage', 'score' => $dimBoundary, 'hint' => $boundary['has_boundary']
                ? ($boundary['total'] > 0
                    ? "{$boundary['covered']} of {$boundary['total']} items you committed to measure"
                    : 'No items accepted into your boundary yet')
                : 'Boundary not scoped yet'],
            ['key' => 'coverage', 'label' => 'Scope coverage',   'score' => $dimCoverage, 'hint' => "$enabledWith of {$enabledScopes->count()} enabled scopes have data"],
            ['key' => 'scope3',   'label' => 'Scope 3 breadth',  'score' => $dimScope3,   'hint' => $scope3['enabled'] ? "{$scope3['covered']} of {$scope3['total']} categories" : 'Scope 3 not enabled'],
            ['key' => 'time',     'label' => 'Time coverage',     'score' => $dimTime,     'hint' => "$monthsCovered of $monthsElapsed months in $year"],
            ['key' => 'review',   'label' => 'Reviewed & final',  'score' => $dimReview,   'hint' => $pendingReview > 0 ? "$pendingReview awaiting review" : 'All records finalised'],
            ['key' => 'quality',  'label' => 'Data quality',      'score' => $dimQuality,  'hint' => $dqTotal > 0 ? "$dqPrimary measured · $dqEstimated estimated" : 'No data yet'],
        ];

        $applicable = collect($dimensions)->pluck('score')->filter(fn ($v) => $v !== null);
        $healthScore = $applicable->count() ? (int) round($applicable->avg()) : 0;

        return [
            'company' => $company,
            'year' => $year,
            'healthScore' => $healthScore,
            'dimensions' => $dimensions,
            'scopes' => $scopes,
            'enabledScopes' => $enabledScopes,
            'scope3' => $scope3,
            'pendingReview' => $pendingReview,
            'monthsCovered' => $monthsCovered,
            'monthsElapsed' => $monthsElapsed,
            'totalRecords' => $totalRecords,
            'boundary' => $boundary,
            'dqPrimary' => $dqPrimary,
            'dqSecondary' => $dqSecondary,
            'dqEstimated' => $dqEstimated,
            'dqTotal' => $dqTotal,
            'steps' => $this->steps(
                $year, $enabledScopes, $scopes, $scope3, $boundary,
                $pendingReview, $totalRecords, $monthsCovered, $monthsElapsed,
                $dqTotal, $dqEstimated,
            ),
        ];
    }

    /**
     * The prioritised next steps, most impactful first.
     *
     * Order is the whole point: scoping the boundary comes before entering
     * anything, because without it the client is guessing at what to measure —
     * which is the problem the Boundary Advisor exists to solve. Everything
     * below it is ordered by how much it changes the number a client would
     * report.
     *
     * @param  \Illuminate\Support\Collection<int, int>  $enabledScopes
     * @param  array<int, array<string, mixed>>  $scopes
     * @param  array<string, mixed>  $scope3
     * @param  array<string, mixed>  $boundary
     * @return array<int, array<string, mixed>>
     */
    private function steps(
        int $year,
        $enabledScopes,
        array $scopes,
        array $scope3,
        array $boundary,
        int $pendingReview,
        int $totalRecords,
        int $monthsCovered,
        int $monthsElapsed,
        int $dqTotal,
        int $dqEstimated,
    ): array {
        $steps = [];

        if (! $boundary['has_boundary']) {
            $steps[] = [
                'sev' => 'high', 'icon' => 'fa-compass-drafting',
                'title' => 'Work out what you actually need to measure',
                'sub' => 'Answer a few questions about your business and we will list the emissions that belong in your inventory.',
                'cta' => 'Scope my boundary', 'route' => 'boundary.index',
            ];
        } elseif ($boundary['top_gaps']->isNotEmpty()) {
            $gapCount = $boundary['gaps']->count();
            $names = $boundary['top_gaps']->pluck('suggested_name')->implode(', ');

            $steps[] = [
                'sev' => $boundary['top_gaps']->first()->materiality === 'high' ? 'high' : 'medium',
                'icon' => 'fa-bullseye',
                'title' => "$gapCount ".($gapCount === 1 ? 'item in your boundary has' : 'items in your boundary have')." no data for $year",
                'sub' => 'Highest impact first: '.$names,
                'cta' => 'See my boundary', 'route' => 'boundary.index',
            ];
        }

        foreach ($enabledScopes as $s) {
            if (! $scopes[$s]['has_data']) {
                $steps[] = [
                    'sev' => 'high', 'icon' => $scopes[$s]['icon'],
                    'title' => "Start your {$scopes[$s]['label']} data",
                    'sub' => $scopes[$s]['desc'],
                    'cta' => 'Add data', 'route' => $scopes[$s]['route'],
                ];
            }
        }

        if ($pendingReview > 0) {
            $steps[] = [
                'sev' => 'medium', 'icon' => 'fa-clipboard-check',
                'title' => "$pendingReview ".($pendingReview === 1 ? 'entry is' : 'entries are').' awaiting review',
                'sub' => 'Review and finalise so they count toward your reported totals.',
                'cta' => 'Review now', 'route' => 'review_data.index',
            ];
        }

        if ($scope3['enabled'] && $scope3['covered'] < $scope3['total'] && $scopes[3]['has_data']) {
            $missing = $scope3['total'] - $scope3['covered'];
            $steps[] = [
                'sev' => 'medium', 'icon' => 'fa-layer-group',
                'title' => "$missing Scope 3 ".($missing === 1 ? 'category has' : 'categories have').' no data yet',
                'sub' => $scope3['missing_list'] ? 'Missing: '.implode(', ', array_slice($scope3['missing_list'], 0, 3)).($scope3['missing_more'] || count($scope3['missing_list']) > 3 ? '…' : '') : 'Fill the remaining value-chain categories.',
                'cta' => 'Add Scope 3', 'route' => 'scope3_entry.index',
            ];
        }

        if ($totalRecords > 0 && $monthsCovered < $monthsElapsed) {
            $gap = $monthsElapsed - $monthsCovered;
            $steps[] = [
                'sev' => 'low', 'icon' => 'fa-calendar-day',
                'title' => "You're missing data for $gap ".($gap === 1 ? 'month' : 'months')." in $year",
                'sub' => 'A complete monthly series makes trends and reports reliable.',
                'cta' => 'Add entries', 'route' => 'scope1_entry.index',
            ];
        }

        if ($dqTotal > 0 && $dqEstimated > 0 && ($dqEstimated / $dqTotal) >= 0.4) {
            $pct = round($dqEstimated / $dqTotal * 100);
            $steps[] = [
                'sev' => 'low', 'icon' => 'fa-star-half-stroke',
                'title' => "$pct% of your data is estimated",
                'sub' => 'Replace estimates with metered/invoiced figures to raise data quality.',
                'cta' => 'Review quality', 'route' => 'data_quality.index',
            ];
        }

        if ($steps === []) {
            $steps[] = [
                'sev' => 'done', 'icon' => 'fa-circle-check',
                'title' => "Your inventory looks complete for $year",
                'sub' => 'Nice work — keep entering data as new bills and activities come in.',
                'cta' => 'View reports', 'route' => 'reports.index',
            ];
        }

        return $steps;
    }
}

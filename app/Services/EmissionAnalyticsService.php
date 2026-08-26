<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Department;
use App\Models\EmissionRecord;
use App\Models\Facilities;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EmissionAnalyticsService
{
    /**
     * Apply shared filters to an EmissionRecord query.
     * EmissionRecord uses HasCompanyScope, so company scoping is automatic.
     */
    public function applyFilters($query, array $filters)
    {
        $query->where('status', 'active');

        // Date range
        $dateRange = $filters['date_range'] ?? '12';
        $customStart = $filters['start_date'] ?? null;
        $customEnd = $filters['end_date'] ?? null;

        if ($dateRange === 'ytd') {
            $query->whereYear('entry_date', Carbon::now()->year);
        } elseif ($dateRange === '3') {
            $query->where('entry_date', '>=', Carbon::now()->subMonths(3)->startOfMonth());
        } elseif ($dateRange === 'custom' && $customStart && $customEnd) {
            try {
                $start = Carbon::parse($customStart)->startOfDay();
                $end = Carbon::parse($customEnd)->endOfDay();
                if ($end->lt($start)) {
                    [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
                }
                $query->whereBetween('entry_date', [$start, $end]);
            } catch (\Throwable $e) {
                $query->where('entry_date', '>=', Carbon::now()->subMonths(12)->startOfMonth());
            }
        } else {
            $query->where('entry_date', '>=', Carbon::now()->subMonths(12)->startOfMonth());
        }

        // Facility filter (stored as string name in emission_records)
        if (! empty($filters['facility'])) {
            $facility = Facilities::find($filters['facility']);
            if ($facility) {
                $query->where('facility', $facility->name);
            }
        }

        // Department filter (stored as string name)
        if (! empty($filters['department'])) {
            $department = Department::find($filters['department']);
            if ($department) {
                $query->where('department', $department->name);
            }
        }

        // Scope filter
        if (! empty($filters['scope'])) {
            $query->where('scope', $filters['scope']);
        }

        // Category / emission source filter
        if (! empty($filters['category'])) {
            $query->where('emission_source', $filters['category']);
        }

        return $query;
    }

    /**
     * Build a fresh filtered query.
     */
    protected function baseQuery(array $filters)
    {
        return $this->applyFilters(EmissionRecord::query(), $filters);
    }

    /**
     * Resolve the concrete [start, end] dates the filters describe. Mirrors the
     * date logic in applyFilters() so period-based metrics (YoY, waterfall,
     * intensity proration) all use the same window.
     */
    protected function resolveCurrentPeriod(array $filters): array
    {
        $now = Carbon::now();
        $dateRange = $filters['date_range'] ?? '12';

        if ($dateRange === 'ytd') {
            return [$now->copy()->startOfYear(), $now->copy()->endOfMonth()];
        }
        if ($dateRange === '3') {
            return [$now->copy()->subMonths(3)->startOfMonth(), $now->copy()->endOfMonth()];
        }
        if ($dateRange === 'custom' && ! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $start = Carbon::parse($filters['start_date'])->startOfDay();
            $end = Carbon::parse($filters['end_date'])->endOfDay();
            if ($end->lt($start)) {
                [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
            }

            return [$start, $end];
        }

        return [$now->copy()->subMonths(12)->startOfMonth(), $now->copy()->endOfMonth()];
    }

    /**
     * Previous window = the same length immediately before the current window.
     * Uses an exact day-length so custom ranges line up correctly (a month-based
     * approximation drifts for partial-month ranges).
     */
    protected function previousPeriodBounds(Carbon $currentStart, Carbon $currentEnd): array
    {
        $lengthInDays = $currentStart->diffInDays($currentEnd);
        $previousEnd = $currentStart->copy()->subDay()->endOfDay();
        $previousStart = $previousEnd->copy()->subDays($lengthInDays)->startOfDay();

        return [$previousStart, $previousEnd];
    }

    /**
     * Get emissions breakdown by a given dimension with optional drill-down parent.
     */
    public function getBreakdownByDimension(string $dimension, array $filters, ?string $parentValue = null)
    {
        $query = $this->baseQuery($filters);

        // Apply parent filter for drill-down
        if ($parentValue !== null) {
            switch ($dimension) {
                case 'source':
                    // Drilling into a specific scope
                    $query->where('scope', $parentValue);
                    break;
                case 'detail':
                    // Drilling into a specific source
                    $query->where('emission_source', $parentValue);
                    break;
            }
        }

        // Build query based on dimension
        if ($dimension === 'scope') {
            $results = $query
                ->select(
                    DB::raw('scope as raw_value'),
                    DB::raw("CASE scope WHEN 1 THEN 'Scope 1 - Direct' WHEN 2 THEN 'Scope 2 - Indirect' WHEN 3 THEN 'Scope 3 - Value Chain' END as label"),
                    DB::raw('SUM(co2e_value) as value'),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('scope')
                ->orderByDesc('value')
                ->get();
        } elseif ($dimension === 'month') {
            $results = $query
                ->select(
                    DB::raw("DATE_FORMAT(entry_date, '%Y-%m') as label"),
                    DB::raw("DATE_FORMAT(entry_date, '%Y-%m') as raw_value"),
                    DB::raw('SUM(co2e_value) as value'),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy(DB::raw("DATE_FORMAT(entry_date, '%Y-%m')"))
                ->orderBy('label')
                ->get();
        } else {
            $col = match ($dimension) {
                'source' => 'emission_source',
                'facility' => 'facility',
                'department' => 'department',
                'site' => 'facility',
                default => 'emission_source',
            };
            $results = $query
                ->select(
                    DB::raw("{$col} as label"),
                    DB::raw("{$col} as raw_value"),
                    DB::raw('SUM(co2e_value) as value'),
                    DB::raw('COUNT(*) as count')
                )
                ->whereNotNull($col)
                ->where($col, '!=', '')
                ->groupBy($col)
                ->orderByDesc('value')
                ->limit(20)
                ->get();
        }

        $total = $results->sum('value');

        return $results->map(function ($item) use ($total) {
            return [
                'label' => $item->label,
                'raw_value' => $item->raw_value,
                'value' => round((float) $item->value, 2),
                'count' => (int) $item->count,
                'percentage' => $total > 0 ? round(($item->value / $total) * 100, 1) : 0,
            ];
        })->values();
    }

    /**
     * Get intensity metrics: total, per employee, per revenue.
     */
    public function getIntensityMetrics(array $filters)
    {
        $total = (float) $this->baseQuery($filters)->sum('co2e_value');
        // Use the canonical company-context helper (not the raw session value),
        // so an account owner who switched companies gets headcount/revenue for the
        // SAME company the emission records are scoped to.
        $companyId = current_company_id() ?? (auth()->user()->company_id ?? null);
        $company = $companyId ? Company::withoutGlobalScopes()->find($companyId) : null;

        $employeeCount = $company?->employee_count ?? null;
        $annualRevenue = $company?->annual_revenue ?? null;
        $currency = $company?->currency ?? 'USD';

        // Prorate annual revenue to the selected period so the top-line per-revenue
        // figure compares like-with-like (partial-period emissions vs the matching
        // slice of annual revenue), consistent with the monthly trend below.
        [$periodStart, $periodEnd] = $this->resolveCurrentPeriod($filters);
        $periodDays = $periodStart->diffInDays($periodEnd) + 1;
        $periodRevenue = $annualRevenue !== null ? $annualRevenue * ($periodDays / 365.25) : null;

        // Monthly intensity trend
        $dateRange = $filters['date_range'] ?? '12';
        $trendQuery = $this->baseQuery($filters);
        $monthlyData = $trendQuery
            ->select(
                DB::raw("DATE_FORMAT(entry_date, '%Y-%m') as month"),
                DB::raw("DATE_FORMAT(entry_date, '%b %Y') as month_label"),
                DB::raw('SUM(co2e_value) as total')
            )
            ->groupBy(DB::raw("DATE_FORMAT(entry_date, '%Y-%m')"), DB::raw("DATE_FORMAT(entry_date, '%b %Y')"))
            ->orderBy('month')
            ->get();

        $monthlyIntensity = $monthlyData->map(function ($item) use ($employeeCount, $annualRevenue) {
            $monthCount = 1;

            return [
                'month' => $item->month_label,
                'total' => round((float) $item->total, 2),
                'per_employee' => $employeeCount ? round((float) $item->total / $employeeCount, 4) : null,
                'per_revenue' => $annualRevenue ? round(((float) $item->total / ($annualRevenue / 12)) * 1000000, 4) : null,
            ];
        })->values();

        // Scope breakdown for intensity
        $scopeBreakdown = $this->baseQuery($filters)
            ->select('scope', DB::raw('SUM(co2e_value) as total'))
            ->groupBy('scope')
            ->orderBy('scope')
            ->get()
            ->map(function ($item) use ($employeeCount, $periodRevenue) {
                $scopeLabel = match ((int) $item->scope) {
                    1 => 'Scope 1',
                    2 => 'Scope 2',
                    3 => 'Scope 3',
                    default => 'Unknown',
                };

                return [
                    'scope' => $scopeLabel,
                    'total' => round((float) $item->total, 2),
                    'per_employee' => $employeeCount ? round((float) $item->total / $employeeCount, 4) : null,
                    'per_revenue' => $periodRevenue ? round(((float) $item->total / $periodRevenue) * 1000000, 4) : null,
                ];
            })->values();

        return [
            'total_emissions' => round($total, 2),
            'employee_count' => $employeeCount,
            'annual_revenue' => $annualRevenue,
            'currency' => $currency,
            'per_employee' => $employeeCount ? round($total / $employeeCount, 4) : null,
            'per_revenue' => $periodRevenue ? round(($total / $periodRevenue) * 1000000, 4) : null,
            'monthly_trend' => $monthlyIntensity,
            'scope_breakdown' => $scopeBreakdown,
        ];
    }

    /**
     * Get year-over-year comparison data.
     */
    public function getYearOverYear(array $filters, string $period = 'monthly')
    {
        // Determine the effective date range for "current" period
        [$currentStart, $currentEnd] = $this->resolveCurrentPeriod($filters);

        // Previous period = same duration immediately before the current window.
        [$previousStart, $previousEnd] = $this->previousPeriodBounds($currentStart, $currentEnd);

        // Build base filters without date (we'll apply date manually)
        $filtersNoDate = $filters;
        $filtersNoDate['date_range'] = 'custom';

        // Current period data
        $filtersNoDate['start_date'] = $currentStart->toDateString();
        $filtersNoDate['end_date'] = $currentEnd->toDateString();
        $currentData = $this->getGroupedByPeriod($filtersNoDate, $period);

        // Previous period data
        $filtersNoDate['start_date'] = $previousStart->toDateString();
        $filtersNoDate['end_date'] = $previousEnd->toDateString();
        $previousData = $this->getGroupedByPeriod($filtersNoDate, $period);

        // Calculate changes
        $currentTotal = collect($currentData)->sum('value');
        $previousTotal = collect($previousData)->sum('value');
        $totalDelta = $currentTotal - $previousTotal;
        $totalPct = $previousTotal > 0 ? round(($totalDelta / $previousTotal) * 100, 1) : ($currentTotal > 0 ? 100 : 0);

        return [
            'current_period' => [
                'start' => $currentStart->format('M Y'),
                'end' => $currentEnd->format('M Y'),
                'data' => $currentData,
                'total' => round($currentTotal, 2),
            ],
            'previous_period' => [
                'start' => $previousStart->format('M Y'),
                'end' => $previousEnd->format('M Y'),
                'data' => $previousData,
                'total' => round($previousTotal, 2),
            ],
            'change' => [
                'absolute' => round($totalDelta, 2),
                'percentage' => $totalPct,
                'direction' => $totalDelta > 0 ? 'increase' : ($totalDelta < 0 ? 'decrease' : 'unchanged'),
            ],
        ];
    }

    /**
     * Group emissions by time period (monthly/quarterly/annual).
     */
    protected function getGroupedByPeriod(array $filters, string $period)
    {
        $query = $this->baseQuery($filters);

        $groupSql = match ($period) {
            'quarterly' => "CONCAT(YEAR(entry_date), ' Q', QUARTER(entry_date))",
            'annual' => 'YEAR(entry_date)',
            default => "DATE_FORMAT(entry_date, '%b %Y')",
        };

        return $query
            ->select(
                DB::raw("{$groupSql} as label"),
                DB::raw('SUM(co2e_value) as value')
            )
            ->groupBy(DB::raw($groupSql))
            // Order by an aggregate (earliest date in each group) so the sort is
            // chronological AND valid under MySQL's ONLY_FULL_GROUP_BY (which is
            // on by default on the server): ordering by a non-grouped date
            // expression like DATE_FORMAT(entry_date,'%Y-%m') is rejected there.
            ->orderBy(DB::raw('MIN(entry_date)'))
            ->get()
            ->map(fn ($item) => [
                'label' => (string) $item->label,
                'value' => round((float) $item->value, 2),
            ])
            ->values()
            ->toArray();
    }

    /**
     * Get waterfall data showing what changed between current and previous period by source.
     */
    public function getWaterfallData(array $filters)
    {
        [$currentStart, $currentEnd] = $this->resolveCurrentPeriod($filters);
        [$previousStart, $previousEnd] = $this->previousPeriodBounds($currentStart, $currentEnd);

        $filtersNoDate = $filters;
        $filtersNoDate['date_range'] = 'custom';

        // Current period by source
        $filtersNoDate['start_date'] = $currentStart->toDateString();
        $filtersNoDate['end_date'] = $currentEnd->toDateString();
        $currentBySource = $this->baseQuery($filtersNoDate)
            ->select('emission_source', DB::raw('SUM(co2e_value) as total'))
            ->whereNotNull('emission_source')
            ->where('emission_source', '!=', '')
            ->groupBy('emission_source')
            ->pluck('total', 'emission_source')
            ->map(fn ($v) => (float) $v);

        // Previous period by source
        $filtersNoDate['start_date'] = $previousStart->toDateString();
        $filtersNoDate['end_date'] = $previousEnd->toDateString();
        $previousBySource = $this->baseQuery($filtersNoDate)
            ->select('emission_source', DB::raw('SUM(co2e_value) as total'))
            ->whereNotNull('emission_source')
            ->where('emission_source', '!=', '')
            ->groupBy('emission_source')
            ->pluck('total', 'emission_source')
            ->map(fn ($v) => (float) $v);

        // Merge all sources
        $allSources = $currentBySource->keys()->merge($previousBySource->keys())->unique();

        $waterfall = $allSources->map(function ($source) use ($currentBySource, $previousBySource) {
            $current = $currentBySource->get($source, 0);
            $previous = $previousBySource->get($source, 0);
            $delta = $current - $previous;

            return [
                'category' => $source,
                'current' => round($current, 2),
                'previous' => round($previous, 2),
                'delta' => round($delta, 2),
                'type' => $delta >= 0 ? 'increase' : 'decrease',
            ];
        })
            ->sortByDesc(fn ($item) => abs($item['delta']))
            ->take(15)
            ->values();

        return $waterfall;
    }

    /**
     * Get top emission hotspots grouped by a dimension.
     */
    public function getHotspots(array $filters, string $groupBy = 'source', int $limit = 20)
    {
        $query = $this->baseQuery($filters);

        $col = match ($groupBy) {
            'facility' => 'facility',
            'department' => 'department',
            'supplier' => 'supplier_id',
            default => 'emission_source',
        };

        // Group by the contributor only (not scope) so a source spanning more
        // than one scope is a single row — otherwise it is double-listed and the
        // Pareto/cumulative curve and contributor count are wrong.
        if ($groupBy === 'supplier') {
            $results = $query
                ->join('suppliers', 'emission_records.supplier_id', '=', 'suppliers.id')
                ->select(
                    'suppliers.name as name',
                    DB::raw('MAX(emission_records.scope) as scope'),
                    DB::raw('COUNT(DISTINCT emission_records.scope) as scope_count'),
                    DB::raw('SUM(emission_records.co2e_value) as value'),
                    DB::raw('COUNT(*) as count')
                )
                ->whereNotNull('emission_records.supplier_id')
                ->groupBy('suppliers.name')
                ->orderByDesc('value')
                ->limit($limit)
                ->get();
        } else {
            $results = $query
                ->select(
                    DB::raw("{$col} as name"),
                    DB::raw('MAX(scope) as scope'),
                    DB::raw('COUNT(DISTINCT scope) as scope_count'),
                    DB::raw('SUM(co2e_value) as value'),
                    DB::raw('COUNT(*) as count')
                )
                ->whereNotNull($col)
                ->where($col, '!=', '')
                ->groupBy($col)
                ->orderByDesc('value')
                ->limit($limit)
                ->get();
        }

        $grandTotal = (float) $this->baseQuery($filters)->sum('co2e_value');
        $cumulative = 0;

        return $results->map(function ($item) use ($grandTotal, &$cumulative) {
            $value = round((float) $item->value, 2);
            $pct = $grandTotal > 0 ? round(($value / $grandTotal) * 100, 1) : 0;
            $cumulative += $pct;
            $scopeLabel = ((int) $item->scope_count) > 1 ? 'Mixed' : match ((int) $item->scope) {
                1 => 'Scope 1',
                2 => 'Scope 2',
                3 => 'Scope 3',
                default => 'Unknown',
            };

            return [
                'name' => $item->name,
                'scope' => $scopeLabel,
                'value' => $value,
                'count' => (int) $item->count,
                'percentage' => $pct,
                'cumulative' => round($cumulative, 1),
            ];
        })->values();
    }

    /**
     * Get treemap data: scope → source hierarchy.
     */
    public function getTreemapData(array $filters)
    {
        $query = $this->baseQuery($filters);

        $results = $query
            ->select(
                'scope',
                'emission_source',
                DB::raw('SUM(co2e_value) as total')
            )
            ->whereNotNull('emission_source')
            ->where('emission_source', '!=', '')
            ->groupBy('scope', 'emission_source')
            ->orderByDesc('total')
            ->get();

        $scopeColors = [
            1 => '#2e7d32',
            2 => '#0277bd',
            3 => '#f57c00',
        ];

        $scopeLabels = [
            1 => 'Scope 1 - Direct',
            2 => 'Scope 2 - Indirect',
            3 => 'Scope 3 - Value Chain',
        ];

        // Group by scope for treemap series
        $grouped = $results->groupBy('scope');
        $series = [];

        foreach ($grouped as $scope => $items) {
            $series[] = [
                'name' => $scopeLabels[$scope] ?? "Scope {$scope}",
                'color' => $scopeColors[$scope] ?? '#999999',
                'data' => $items->map(fn ($item) => [
                    'x' => $item->emission_source,
                    'y' => round((float) $item->total, 2),
                ])->values()->toArray(),
            ];
        }

        return $series;
    }
}

<?php

namespace App\Services;

use App\Models\EmissionRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Detects noteworthy changes in a company's emissions inventory that a human
 * should look at: a source spiking month-over-month, a large new source
 * appearing, or data quality slipping.
 *
 * Comparison is between the latest month that has data and the month before it
 * (not the calendar month), so detection works on the freshest records even
 * when the inventory isn't up to today. All queries are scoped to one company_id
 * explicitly, so this is safe to run as a console batch across every tenant.
 */
class AnomalyDetectionService
{
    /** A source must rise at least this fraction to count as a spike. */
    private const SPIKE_RATIO = 0.30;          // +30%
    /** …and by at least this many tCO2e, so tiny sources don't cry wolf. */
    private const SPIKE_MIN_DELTA = 1.0;
    /** A previous-month baseline below this is treated as "effectively zero". */
    private const BASELINE_FLOOR = 0.5;
    /** A source appearing from ~nothing to at least this much is a "new source". */
    private const NEW_SOURCE_MIN = 5.0;
    /** Below this baseline, a percentage change is meaningless — report the jump
     *  as an absolute "surge" instead of an exploding percentage. */
    private const LOW_BASE = 10.0;
    /** Data-quality score drop (0–100 points) that warrants an alert. */
    private const QUALITY_DROP_POINTS = 10.0;
    /** Don't score data quality on a month with fewer than this many records. */
    private const QUALITY_MIN_RECORDS = 5;
    /** Cap spike alerts per run so a messy month can't flood the inbox. */
    private const MAX_SPIKES = 5;
    /** A month needs at least this many records to be a fair comparison point.
     *  Skips sparse trailing/partial months (e.g. a lone straggler record). */
    private const MIN_MONTH_RECORDS = 3;

    /**
     * @return array<int, array<string, mixed>> Detected anomalies (may be empty).
     */
    public function detectForCompany(int $companyId): array
    {
        // Compare the two most recent "material" months — months with enough
        // records to be meaningful. Using calendar-latest would compare against a
        // partial in-progress month (or a lone straggler) and miss the real move.
        $months = $this->materialMonths($companyId);
        if (count($months) < 2) {
            return [];
        }

        $currMonth = $months[count($months) - 1];
        $prevMonth = $months[count($months) - 2];

        $currStart = Carbon::createFromFormat('Y-m', $currMonth)->startOfMonth();
        $currEnd   = $currStart->copy()->endOfMonth();
        $prevStart = Carbon::createFromFormat('Y-m', $prevMonth)->startOfMonth();
        $prevEnd   = $prevStart->copy()->endOfMonth();
        $period      = $currStart->format('Y-m');
        $periodLabel = $currStart->format('M Y');
        $prevLabel   = $prevStart->format('M Y');

        $current  = $this->sumBySource($companyId, $currStart, $currEnd);
        $previous = $this->sumBySource($companyId, $prevStart, $prevEnd);

        $anomalies = [];
        $spikes = [];

        foreach ($current as $source => $currVal) {
            $prevVal = (float) ($previous[$source] ?? 0.0);
            $delta = $currVal - $prevVal;

            // Ignore immaterial movements and sources too small to matter.
            if ($delta < self::SPIKE_MIN_DELTA || $currVal < self::NEW_SOURCE_MIN) {
                continue;
            }

            if ($prevVal < self::BASELINE_FLOOR) {
                // Source appears from nothing.
                $spikes[] = [
                    'type'     => 'new_source',
                    'key'      => "new_source|{$source}|{$period}",
                    'period'   => $period,
                    'severity' => $currVal >= 100 ? 'danger' : 'info',
                    'icon'     => 'fa-circle-plus',
                    'title'    => "New emission source: {$source}",
                    'message'  => "{$source} appears in {$periodLabel} at " . $this->fmt($currVal) . " tCO₂e with no prior activity. Confirm the activity data is correct.",
                    'url'      => $this->safeRoute('analytics.index'),
                    'metadata' => ['current' => round($currVal, 2), 'previous' => round($prevVal, 2), 'period' => $period],
                    '_delta'   => $delta,
                ];
            } elseif ($prevVal < self::LOW_BASE) {
                // Surge off a negligible base — a percentage would be nonsense.
                $spikes[] = [
                    'type'     => 'emission_spike',
                    'key'      => "emission_spike|{$source}|{$period}",
                    'period'   => $period,
                    'severity' => $currVal >= 100 ? 'danger' : 'warning',
                    'icon'     => 'fa-arrow-trend-up',
                    'title'    => "Emissions surge: {$source}",
                    'message'  => "{$source} surged from " . $this->fmt($prevVal) . " to " . $this->fmt($currVal) . " tCO₂e ({$periodLabel} vs {$prevLabel}), from a very low base. Verify the activity data.",
                    'url'      => $this->safeRoute('analytics.index'),
                    'metadata' => ['current' => round($currVal, 2), 'previous' => round($prevVal, 2), 'delta' => round($delta, 2), 'period' => $period],
                    '_delta'   => $delta,
                ];
            } elseif ($currVal >= $prevVal * (1 + self::SPIKE_RATIO)) {
                // Genuine spike over a real baseline.
                $pct = round(($delta / $prevVal) * 100);
                $spikes[] = [
                    'type'     => 'emission_spike',
                    'key'      => "emission_spike|{$source}|{$period}",
                    'period'   => $period,
                    'severity' => $pct >= 75 ? 'danger' : 'warning',
                    'icon'     => 'fa-arrow-trend-up',
                    'title'    => "Emissions spike: {$source}",
                    'message'  => "{$source} rose {$pct}% (" . $this->fmt($prevVal) . " → " . $this->fmt($currVal) . " tCO₂e), {$periodLabel} vs {$prevLabel}. Open Analytics to investigate.",
                    'url'      => $this->safeRoute('analytics.index'),
                    'metadata' => ['current' => round($currVal, 2), 'previous' => round($prevVal, 2), 'delta' => round($delta, 2), 'percent' => $pct, 'period' => $period],
                    '_delta'   => $delta,
                ];
            }
        }

        // Keep only the biggest spikes by absolute change.
        usort($spikes, fn ($a, $b) => $b['_delta'] <=> $a['_delta']);
        foreach (array_slice($spikes, 0, self::MAX_SPIKES) as $spike) {
            unset($spike['_delta']);
            $anomalies[] = $spike;
        }

        // Data-quality regression.
        if ($quality = $this->qualityDrop($companyId, $currStart, $currEnd, $prevStart, $prevEnd, $period, $periodLabel)) {
            $anomalies[] = $quality;
        }

        return $anomalies;
    }

    private function baseQuery(int $companyId)
    {
        return EmissionRecord::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'active');
    }

    /**
     * Months (YYYY-MM, ascending) that have enough records to compare fairly.
     *
     * @return array<int, string>
     */
    private function materialMonths(int $companyId): array
    {
        return $this->baseQuery($companyId)
            ->select(DB::raw("DATE_FORMAT(entry_date, '%Y-%m') as ym"), DB::raw('COUNT(*) as c'))
            ->groupBy('ym')
            ->havingRaw('COUNT(*) >= ?', [self::MIN_MONTH_RECORDS])
            ->orderBy('ym')
            ->pluck('ym')
            ->all();
    }

    /**
     * @return array<string, float> emission_source => total tCO2e
     */
    private function sumBySource(int $companyId, Carbon $start, Carbon $end): array
    {
        return $this->baseQuery($companyId)
            ->whereBetween('entry_date', [$start, $end])
            ->whereNotNull('emission_source')
            ->where('emission_source', '!=', '')
            ->select('emission_source', DB::raw('SUM(co2e_value) as total'))
            ->groupBy('emission_source')
            ->pluck('total', 'emission_source')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    /**
     * Overall data-quality score (0–100) for a month, or null if too few records.
     */
    private function qualityScore(int $companyId, Carbon $start, Carbon $end): ?array
    {
        $counts = $this->baseQuery($companyId)
            ->whereBetween('entry_date', [$start, $end])
            ->select('data_quality', DB::raw('COUNT(*) as c'))
            ->groupBy('data_quality')
            ->pluck('c', 'data_quality');

        $total = (int) $counts->sum();
        if ($total < self::QUALITY_MIN_RECORDS) {
            return null;
        }

        $primary = (int) ($counts['primary'] ?? 0);
        $secondary = (int) ($counts['secondary'] ?? 0);
        $score = (($primary * 100) + ($secondary * 50)) / $total;

        return ['score' => round($score, 1), 'total' => $total];
    }

    private function qualityDrop(int $companyId, Carbon $currStart, Carbon $currEnd, Carbon $prevStart, Carbon $prevEnd, string $period, string $periodLabel): ?array
    {
        $curr = $this->qualityScore($companyId, $currStart, $currEnd);
        $prev = $this->qualityScore($companyId, $prevStart, $prevEnd);

        if (! $curr || ! $prev) {
            return null;
        }

        $drop = $prev['score'] - $curr['score'];
        if ($drop < self::QUALITY_DROP_POINTS) {
            return null;
        }

        return [
            'type'     => 'quality_drop',
            'key'      => "quality_drop|{$period}",
            'period'   => $period,
            'severity' => 'warning',
            'icon'     => 'fa-gauge-simple-high',
            'title'    => 'Data quality dropped',
            'message'  => "Your data-quality score fell from {$prev['score']} to {$curr['score']} in {$periodLabel} (more estimated, fewer primary records). Review recent entries.",
            'url'      => $this->safeRoute('data_quality.index'),
            'metadata' => ['current' => $curr['score'], 'previous' => $prev['score'], 'drop' => round($drop, 1), 'period' => $period],
        ];
    }

    private function fmt(float $v): string
    {
        return number_format($v, $v < 100 ? 1 : 0);
    }

    /**
     * route() needs APP_URL in the console; fall back to a relative path.
     */
    private function safeRoute(string $name): string
    {
        try {
            return route($name);
        } catch (\Throwable $e) {
            return '/' . str_replace('.', '/', $name);
        }
    }
}

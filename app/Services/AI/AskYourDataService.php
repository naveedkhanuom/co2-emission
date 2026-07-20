<?php

namespace App\Services\AI;

use App\Services\EmissionAnalyticsService;

/**
 * "Ask Your Data" — a natural-language assistant over the company's emissions
 * inventory.
 *
 * Grounding is the whole point: Claude never queries the database and never
 * invents a figure. The server computes a company-scoped snapshot with
 * EmissionAnalyticsService (which is bounded by HasCompanyScope, so it can only
 * ever see the current tenant) and hands that snapshot to Claude as the ONLY
 * source of numbers. Every value the assistant states must come from the
 * snapshot; if the snapshot doesn't contain it, the assistant says so.
 *
 * If no API key is configured the service still returns a useful deterministic
 * summary from the same snapshot, so the feature degrades gracefully.
 */
class AskYourDataService
{
    /** Bump when the system prompt changes, for traceability. */
    public const PROMPT_VERSION = 'ask-your-data-v1';

    public function __construct(
        private EmissionAnalyticsService $analytics,
        private ClaudeService $claude,
    ) {
    }

    public function enabled(): bool
    {
        return $this->claude->enabled();
    }

    /**
     * Answer a question, grounded in the company-scoped snapshot.
     *
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array{answer: string, grounded: bool, ai: bool}
     */
    public function answer(string $question, array $history, array $filters): array
    {
        $snapshot = $this->buildSnapshot($filters);

        // No inventory in range: don't call the model for an empty dataset.
        if (($snapshot['totals']['total_tco2e'] ?? 0) <= 0) {
            return [
                'answer'   => "There's no active emissions data for the selected period (" . $snapshot['period'] . "). Try widening the date range, or add records under Scope 1–3 Entry first.",
                'grounded' => true,
                'ai'       => false,
            ];
        }

        if (! $this->enabled()) {
            return [
                'answer'   => $this->deterministicSummary($snapshot),
                'grounded' => true,
                'ai'       => false,
            ];
        }

        $messages = $this->buildMessages($history, $question);
        $reply = $this->claude->chat($messages, $this->systemPrompt($snapshot), [
            'temperature' => 0.2,
        ]);

        if ($reply === null) {
            // API failed — fall back to the computed summary rather than erroring.
            return [
                'answer'   => $this->deterministicSummary($snapshot)
                    . "\n\n_(The AI assistant is temporarily unavailable, so this is an automatic summary.)_",
                'grounded' => true,
                'ai'       => false,
            ];
        }

        return [
            'answer'   => trim($reply),
            'grounded' => true,
            'ai'       => true,
        ];
    }

    /**
     * Compute the company-scoped data snapshot the assistant reasons over.
     *
     * @return array<string, mixed>
     */
    public function buildSnapshot(array $filters): array
    {
        $company = current_company();

        $scope = $this->analytics->getBreakdownByDimension('scope', $filters);
        $sources = $this->analytics->getBreakdownByDimension('source', $filters);
        $facilities = $this->analytics->getBreakdownByDimension('facility', $filters);
        $departments = $this->analytics->getBreakdownByDimension('department', $filters);
        $yoy = $this->analytics->getYearOverYear($filters, 'monthly');
        $intensity = $this->analytics->getIntensityMetrics($filters);

        $scopeTotals = ['scope_1' => 0.0, 'scope_2' => 0.0, 'scope_3' => 0.0];
        foreach ($scope as $row) {
            $key = 'scope_' . (int) $row['raw_value'];
            if (isset($scopeTotals[$key])) {
                $scopeTotals[$key] = $row['value'];
            }
        }
        $total = round(array_sum($scopeTotals), 2);

        $pct = fn ($v) => $total > 0 ? round(($v / $total) * 100, 1) : 0;

        $trim = fn ($rows, $n) => collect($rows)->take($n)->map(fn ($r) => [
            'name'   => $r['label'],
            'tco2e'  => $r['value'],
            'percent' => $r['percentage'],
        ])->values()->all();

        return [
            'company'  => $company->name ?? 'Your company',
            'period'   => $this->periodLabel($filters),
            'unit'     => 'tCO2e (metric tonnes CO2-equivalent)',
            'totals'   => [
                'total_tco2e'   => $total,
                'scope_1_tco2e' => $scopeTotals['scope_1'],
                'scope_2_tco2e' => $scopeTotals['scope_2'],
                'scope_3_tco2e' => $scopeTotals['scope_3'],
                'scope_1_pct'   => $pct($scopeTotals['scope_1']),
                'scope_2_pct'   => $pct($scopeTotals['scope_2']),
                'scope_3_pct'   => $pct($scopeTotals['scope_3']),
            ],
            'top_sources'     => $trim($sources, 10),
            'top_facilities'  => $trim($facilities, 8),
            'top_departments' => $trim($departments, 8),
            'monthly_trend'   => collect($intensity['monthly_trend'] ?? [])
                ->map(fn ($m) => ['month' => $m['month'], 'tco2e' => $m['total']])->values()->all(),
            'year_over_year'  => [
                'current_period'  => $yoy['current_period']['start'] . ' – ' . $yoy['current_period']['end'],
                'current_tco2e'   => $yoy['current_period']['total'],
                'previous_period' => $yoy['previous_period']['start'] . ' – ' . $yoy['previous_period']['end'],
                'previous_tco2e'  => $yoy['previous_period']['total'],
                'change_tco2e'    => $yoy['change']['absolute'],
                'change_percent'  => $yoy['change']['percentage'],
                'direction'       => $yoy['change']['direction'],
            ],
            'intensity' => [
                'per_employee_tco2e'          => $intensity['per_employee'],
                'per_million_revenue_tco2e'   => $intensity['per_revenue'],
                'employee_count'              => $intensity['employee_count'],
                'annual_revenue'              => $intensity['annual_revenue'],
                'currency'                    => $intensity['currency'],
            ],
        ];
    }

    private function systemPrompt(array $snapshot): string
    {
        $json = json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
You are the "Ask Your Data" assistant inside a corporate greenhouse-gas (GHG) accounting platform. You help sustainability, finance and operations staff understand their own emissions inventory.

You are given a DATA SNAPSHOT below: a pre-computed, company-scoped summary of this organisation's emissions for the selected reporting period. This snapshot is your ONLY source of numbers.

STRICT RULES:
- Use ONLY numbers present in the snapshot. Never estimate, extrapolate or invent a figure. If a number is not in the snapshot, say you don't have it and suggest which filter or view (Analytics, a scope entry, a different date range) would surface it.
- All emission values are in tCO2e (metric tonnes CO2-equivalent). Always state the unit. Format large numbers with thousands separators (e.g. 12,430 tCO2e).
- Follow the GHG Protocol framing: Scope 1 = direct, Scope 2 = purchased energy (indirect), Scope 3 = value chain.
- Be concise and specific. Lead with the number that answers the question, then one or two sentences of context. Use short markdown: bold figures, small bullet lists, and — for multi-row breakdowns — a compact markdown table. Do not use markdown headings.
- When useful, point the user to the relevant screen: Analytics (breakdown, hotspots, year-over-year), Scope 1/2/3 Entry, Reports, or Disclosure Reports.
- Do not give regulatory or legal assurance. You summarise the data; a human verifies it.
- If asked to change data or perform an action, explain that you are read-only and describe where in the app they can do it.

DATA SNAPSHOT (period: {$snapshot['period']}):
{$json}
PROMPT;
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array<int, array{role: string, content: string}>
     */
    private function buildMessages(array $history, string $question): array
    {
        $messages = [];

        // Keep only the last few turns to bound token cost, and sanitise roles.
        foreach (array_slice($history, -6) as $turn) {
            $role = ($turn['role'] ?? '') === 'assistant' ? 'assistant' : 'user';
            $content = trim((string) ($turn['content'] ?? ''));
            if ($content !== '') {
                $messages[] = ['role' => $role, 'content' => mb_substr($content, 0, 2000)];
            }
        }

        $messages[] = ['role' => 'user', 'content' => mb_substr($question, 0, 2000)];

        // The Messages API requires the first message to be from the user.
        if (($messages[0]['role'] ?? 'user') !== 'user') {
            array_shift($messages);
        }

        return $messages;
    }

    /**
     * A useful answer when the AI provider is unavailable — pure computed facts.
     */
    private function deterministicSummary(array $snapshot): string
    {
        $t = $snapshot['totals'];
        $yoy = $snapshot['year_over_year'];
        $topSource = $snapshot['top_sources'][0]['name'] ?? null;
        $topSourceVal = $snapshot['top_sources'][0]['tco2e'] ?? null;

        $fmt = fn ($v) => number_format((float) $v, 0);

        $lines = [];
        $lines[] = "**{$snapshot['company']} — {$snapshot['period']}**";
        $lines[] = "Total emissions: **" . $fmt($t['total_tco2e']) . " tCO₂e**";
        $lines[] = "• Scope 1: " . $fmt($t['scope_1_tco2e']) . " tCO₂e ({$t['scope_1_pct']}%)";
        $lines[] = "• Scope 2: " . $fmt($t['scope_2_tco2e']) . " tCO₂e ({$t['scope_2_pct']}%)";
        $lines[] = "• Scope 3: " . $fmt($t['scope_3_tco2e']) . " tCO₂e ({$t['scope_3_pct']}%)";

        if ($topSource) {
            $lines[] = "Largest source: **{$topSource}** (" . $fmt($topSourceVal) . " tCO₂e).";
        }

        if (($yoy['previous_tco2e'] ?? 0) > 0) {
            $dir = $yoy['direction'] === 'increase' ? 'up' : ($yoy['direction'] === 'decrease' ? 'down' : 'flat');
            $lines[] = "Versus the previous period, emissions are **{$dir} {$yoy['change_percent']}%**.";
        }

        return implode("\n", $lines);
    }

    private function periodLabel(array $filters): string
    {
        $range = $filters['date_range'] ?? '12';

        return match ($range) {
            'ytd'    => 'Year to date',
            '3'      => 'Last 3 months',
            'custom' => trim(($filters['start_date'] ?? '?') . ' to ' . ($filters['end_date'] ?? '?')),
            default  => 'Last 12 months',
        };
    }
}

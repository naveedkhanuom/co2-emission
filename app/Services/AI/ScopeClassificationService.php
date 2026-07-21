<?php

namespace App\Services\AI;

use App\Models\Scope3Category;
use Illuminate\Support\Str;

/**
 * Classifies a free-text activity description into the GHG Protocol scope it
 * belongs to (1/2/3), the Scope 3 category (1–15) where relevant, a suggested
 * emission source and calculation method, plus a confidence score and short
 * reasoning the user can sanity-check.
 *
 * Uses Claude when configured; otherwise falls back to a deterministic keyword
 * heuristic so the feature still works without an API key. Output is always a
 * normalised, validated array — never raw model text.
 */
class ScopeClassificationService
{
    /** Bump when the prompt/schema changes, so stored results stay traceable. */
    public const PROMPT_VERSION = 'scope-classifier-v1';

    public function __construct(protected ClaudeService $claude)
    {
    }

    /**
     * @return array{
     *   scope:int, scope3_category_id:?int, scope3_category_number:?int,
     *   scope3_category_name:?string, suggested_source:?string,
     *   calculation_method:string, confidence:float, reasoning:string,
     *   needs_clarification:bool, clarification:?string, source:string
     * }
     */
    public function classify(string $description): array
    {
        $description = trim($description);
        $categories = Scope3Category::orderBy('sort_order')->get(['id', 'name', 'sort_order', 'category_type']);

        if ($this->claude->enabled()) {
            $ai = $this->classifyWithClaude($description, $categories);
            if ($ai !== null) {
                return $ai;
            }
        }

        return $this->classifyWithKeywords($description, $categories);
    }

    /**
     * Ask Claude for a structured classification, then validate every field
     * against our own data so a hallucinated category can never slip through.
     */
    protected function classifyWithClaude(string $description, $categories): ?array
    {
        $categoryList = $categories
            ->map(fn ($c) => "{$c->sort_order}. {$c->name} ({$c->category_type})")
            ->implode("\n");

        $system = <<<SYS
You are a GHG accounting expert classifying a company activity under the GHG Protocol Corporate Standard.

Scopes:
- Scope 1: direct emissions from owned/controlled sources (on-site fuel combustion, company vehicles/fleet, fugitive refrigerant leaks, industrial process emissions).
- Scope 2: indirect emissions from purchased energy the company consumes (purchased electricity, district heating/cooling, steam).
- Scope 3: all other indirect value-chain emissions, split into 15 categories:
{$categoryList}

Rules:
- Choose exactly one scope (1, 2, or 3).
- If and only if scope is 3, set scope3_category_number to the matching category number (1-15); otherwise null.
- calculation_method is "spend-based" when the description is expressed in money/spend, otherwise "activity-based".
- confidence is 0.0-1.0 reflecting how certain you are.
- If the description is too vague to classify, set needs_clarification true and ask one short clarifying question.
- Keep reasoning to one or two plain sentences a non-expert understands.

Return JSON with keys: scope (int), scope3_category_number (int|null), suggested_source (string), calculation_method (string), confidence (number), reasoning (string), needs_clarification (bool), clarification (string|null).
SYS;

        $result = $this->claude->json(
            "Classify this activity:\n\n\"{$description}\"",
            $system,
            ['temperature' => 0, 'max_tokens' => 600]
        );

        if (!is_array($result) || !isset($result['scope'])) {
            return null;
        }

        $scope = (int) $result['scope'];
        if (!in_array($scope, [1, 2, 3], true)) {
            return null;
        }

        $catNumber = $scope === 3 ? $this->intOrNull($result['scope3_category_number'] ?? null) : null;
        $category = $catNumber ? $categories->firstWhere('sort_order', $catNumber) : null;

        $confidence = (float) ($result['confidence'] ?? 0.5);
        $confidence = max(0.0, min(1.0, $confidence));

        return [
            'scope'                  => $scope,
            'scope3_category_id'     => $category?->id,
            'scope3_category_number' => $category?->sort_order ?? ($scope === 3 ? $catNumber : null),
            'scope3_category_name'   => $category?->name,
            'suggested_source'       => $this->cleanString($result['suggested_source'] ?? null, 100),
            'calculation_method'     => ($result['calculation_method'] ?? '') === 'spend-based' ? 'spend-based' : 'activity-based',
            'confidence'             => round($confidence, 2),
            'reasoning'              => $this->cleanString($result['reasoning'] ?? '', 500) ?? '',
            'needs_clarification'    => (bool) ($result['needs_clarification'] ?? false),
            'clarification'          => $this->cleanString($result['clarification'] ?? null, 300),
            'source'                 => 'ai',
        ];
    }

    /**
     * Deterministic keyword fallback. Lower confidence by design — it exists so
     * the Scope Finder still gives an answer when the AI provider is unavailable.
     */
    protected function classifyWithKeywords(string $description, $categories): array
    {
        $text = Str::lower($description);
        $has = fn (array $words) => collect($words)->contains(fn ($w) => str_contains($text, $w));

        // Scope 2 — purchased energy.
        if ($has(['electricity', 'grid power', 'kwh', 'district heating', 'district cooling', 'purchased steam', 'purchased heat'])) {
            return $this->result(2, null, $categories, 'Purchased Electricity (Location-based)', $text, 'Purchased energy the company consumes is Scope 2.');
        }

        // Scope 1 — direct combustion / fleet / fugitive / process.
        if ($has(['refrigerant', 'hfc', 'r404', 'r410', 'fugitive', 'fire suppression', 'sf6'])) {
            return $this->result(1, null, $categories, 'Refrigerant (HFCs)', $text, 'Gases leaking from owned equipment are direct (fugitive) Scope 1 emissions.');
        }
        if ($has(['boiler', 'furnace', 'natural gas', 'generator', 'genset', 'on-site', 'onsite', 'lpg', 'propane', 'kerosene', 'fuel oil', 'diesel'])) {
            return $this->result(1, null, $categories, 'Natural Gas', $text, 'Fuel burned on-site in owned equipment is direct Scope 1.');
        }
        if ($has(['company car', 'company vehicle', 'fleet', 'owned truck', 'owned van'])) {
            return $this->result(1, null, $categories, 'Fleet - Diesel', $text, 'Fuel burned in company-owned vehicles is direct Scope 1.');
        }

        // Scope 3 — by category keyword.
        $map = [
            ['words' => ['business travel', 'flight', 'hotel', 'air travel', 'taxi'], 'num' => 6, 'source' => 'Business Travel'],
            ['words' => ['commute', 'commuting'], 'num' => 7, 'source' => 'Employee Commuting'],
            ['words' => ['waste', 'landfill', 'recycling', 'incineration'], 'num' => 5, 'source' => 'Waste Generated in Operations'],
            ['words' => ['purchased goods', 'raw material', 'supplier', 'procurement', 'bought', 'purchase'], 'num' => 1, 'source' => 'Purchased Goods & Services'],
            ['words' => ['capital', 'machinery', 'equipment purchase', 'building'], 'num' => 2, 'source' => 'Capital Goods'],
            ['words' => ['inbound', 'upstream transport', 'freight in'], 'num' => 4, 'source' => 'Upstream Transportation & Distribution'],
            ['words' => ['outbound', 'downstream transport', 'delivery to customer', 'shipping to customer'], 'num' => 9, 'source' => 'Downstream Transportation & Distribution'],
            ['words' => ['use of sold', 'product use', 'customers use'], 'num' => 11, 'source' => 'Use of Sold Products'],
            ['words' => ['end-of-life', 'end of life', 'product disposal'], 'num' => 12, 'source' => 'End-of-Life Treatment of Sold Products'],
            ['words' => ['investment', 'equity', 'portfolio', 'financed'], 'num' => 15, 'source' => 'Investments'],
            ['words' => ['franchise'], 'num' => 14, 'source' => 'Franchises'],
        ];
        foreach ($map as $m) {
            if ($has($m['words'])) {
                return $this->result(3, $m['num'], $categories, $m['source'], $text, 'This is an indirect value-chain activity (Scope 3, category ' . $m['num'] . ').');
            }
        }

        // Unknown — ask for more detail.
        return [
            'scope'                  => 3,
            'scope3_category_id'     => null,
            'scope3_category_number' => null,
            'scope3_category_name'   => null,
            'suggested_source'       => null,
            'calculation_method'     => str_contains($text, '$') || str_contains($text, 'spend') ? 'spend-based' : 'activity-based',
            'confidence'             => 0.2,
            'reasoning'              => 'Could not confidently classify from the description.',
            'needs_clarification'    => true,
            'clarification'          => 'Can you add more detail — what was consumed or purchased, and does your company own the source?',
            'source'                 => 'keyword',
        ];
    }

    /** Build a normalised keyword-fallback result. */
    protected function result(int $scope, ?int $catNumber, $categories, string $source, string $text, string $reasoning): array
    {
        $category = $catNumber ? $categories->firstWhere('sort_order', $catNumber) : null;

        return [
            'scope'                  => $scope,
            'scope3_category_id'     => $category?->id,
            'scope3_category_number' => $category?->sort_order ?? $catNumber,
            'scope3_category_name'   => $category?->name,
            'suggested_source'       => $source,
            'calculation_method'     => str_contains($text, '$') || str_contains($text, 'spend') ? 'spend-based' : 'activity-based',
            'confidence'             => 0.55,
            'reasoning'              => $reasoning,
            'needs_clarification'    => false,
            'clarification'          => null,
            'source'                 => 'keyword',
        ];
    }

    protected function intOrNull($v): ?int
    {
        return is_numeric($v) ? (int) $v : null;
    }

    protected function cleanString($v, int $max): ?string
    {
        if (!is_string($v) || trim($v) === '') {
            return null;
        }
        return Str::limit(trim($v), $max, '');
    }
}

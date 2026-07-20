<?php

namespace App\Services\AI;

use App\Models\Scope3Category;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Turns a plain-language activity sentence — e.g. "Today I used 200 litres of
 * diesel in a company car" — into a fully populated emission-record draft:
 * scope, Scope 3 category, emission source, activity quantity + unit, a
 * runtime-estimated emission factor, and the resulting CO2e — ready to pre-fill
 * the entry form for a human to confirm (never auto-saved).
 *
 * Per the form's convention, the emission factor is expressed in tCO2e per unit
 * and CO2e = activity x factor. The model is asked for kgCO2e per unit (the
 * familiar basis) and we convert to tonnes. When the AI provider is unavailable
 * it degrades to a scope/source guess and asks the user to fill the numbers.
 */
class NaturalLanguageEntryService
{
    public const PROMPT_VERSION = 'nl-entry-v1';

    /** Units the entry form accepts; the model must express data in one of these. */
    public const FORM_UNITS = ['kWh', 'liters', 'm3', 'km', 'kg'];

    public function __construct(
        protected ClaudeService $claude,
        protected ScopeClassificationService $classifier,
    ) {
    }

    public function parse(string $text): array
    {
        $text = trim($text);
        $categories = Scope3Category::orderBy('sort_order')->get(['id', 'name', 'sort_order', 'category_type']);

        if ($this->claude->enabled()) {
            $ai = $this->parseWithClaude($text, $categories);
            if ($ai !== null) {
                return $ai;
            }
        }

        return $this->fallback($text);
    }

    protected function parseWithClaude(string $text, $categories): ?array
    {
        $categoryList = $categories
            ->map(fn ($c) => "{$c->sort_order}. {$c->name} ({$c->category_type})")
            ->implode("\n");
        $units = implode(', ', self::FORM_UNITS);
        $today = now()->toDateString();

        $system = <<<SYS
You are a GHG accounting assistant. Convert a plain-language activity into a structured emission entry under the GHG Protocol Corporate Standard.

Scopes:
- Scope 1: direct emissions from owned/controlled sources (on-site fuel combustion, company vehicles/fleet, fugitive refrigerant leaks, industrial processes).
- Scope 2: indirect emissions from purchased energy consumed (electricity, district heating/cooling, steam).
- Scope 3: other indirect value-chain emissions, in 15 categories:
{$categoryList}

Extraction rules:
- quantity: the numeric amount of activity (number only). null if none stated.
- unit: express the quantity using EXACTLY ONE of these units, converting if needed: {$units}. (Use "m3" for cubic metres.)
- factor_kg_per_unit: the best-practice emission factor in kgCO2e per the chosen unit (e.g. diesel ~2.68 kgCO2e/litre, petrol ~2.31, natural gas ~0.18/kWh, grid electricity ~0.4/kWh). Give your best authoritative estimate.
- factor_source: short citation for the factor (e.g. "DEFRA 2024", "IPCC", "IEA grid avg"), or "AI estimate".
- emission_source: a concise source name (e.g. "Diesel (Stationary)", "Fleet - Diesel", "Purchased Electricity (Location-based)").
- scope: 1, 2, or 3. If scope is 3, set scope3_category_number (1-15); else null.
- calculation_method: "spend-based" if expressed in money, else "activity-based".
- date: an ISO date (YYYY-MM-DD) if the text implies one ("today" = {$today}), else null.
- confidence: 0.0-1.0.
- Ownership matters: a "car"/"vehicle" is Scope 1 only if company-owned; if it might be a rental/personal/business-trip vehicle, set needs_clarification true and ask which it is.
- If quantity, unit, or ownership is unclear, set needs_clarification true with one short question.

Return JSON: scope (int), scope3_category_number (int|null), emission_source (string), quantity (number|null), unit (string), factor_kg_per_unit (number|null), factor_source (string), calculation_method (string), date (string|null), confidence (number), reasoning (string), needs_clarification (bool), clarification (string|null).
SYS;

        $result = $this->claude->json(
            "Activity: \"{$text}\"",
            $system,
            ['temperature' => 0, 'max_tokens' => 1024]
        );

        if (!is_array($result) || !isset($result['scope'])) {
            return null;
        }

        $scope = (int) $result['scope'];
        if (!in_array($scope, [1, 2, 3], true)) {
            return null;
        }

        $catNumber = $scope === 3 ? (is_numeric($result['scope3_category_number'] ?? null) ? (int) $result['scope3_category_number'] : null) : null;
        $category = $catNumber ? $categories->firstWhere('sort_order', $catNumber) : null;

        $quantity = is_numeric($result['quantity'] ?? null) ? (float) $result['quantity'] : null;
        $factorKg = is_numeric($result['factor_kg_per_unit'] ?? null) ? (float) $result['factor_kg_per_unit'] : null;
        $factorT = $factorKg !== null ? $factorKg / 1000 : null;          // tCO2e per unit
        $co2e = ($quantity !== null && $factorT !== null) ? round($quantity * $factorT, 4) : null;

        $confidence = max(0.0, min(1.0, (float) ($result['confidence'] ?? 0.5)));

        return [
            'date'                   => $this->normalizeDate($result['date'] ?? null),
            'scope'                  => $scope,
            'scope3_category_id'     => $category?->id,
            'scope3_category_number' => $category?->sort_order ?? ($scope === 3 ? $catNumber : null),
            'scope3_category_name'   => $category?->name,
            'emission_source'        => $this->clean($result['emission_source'] ?? null, 100) ?? 'Unspecified',
            'activity_data'          => $quantity,
            'unit'                   => $this->normalizeUnit($result['unit'] ?? null),
            'factor_kg_per_unit'     => $factorKg !== null ? round($factorKg, 6) : null,
            'emission_factor'        => $factorT !== null ? round($factorT, 8) : null,
            'co2e_value'             => $co2e,
            'calculation_method'     => ($result['calculation_method'] ?? '') === 'spend-based' ? 'spend-based' : 'activity-based',
            'confidence'             => round($confidence, 2),
            'confidence_level'       => $this->confidenceLevel($confidence),
            'reasoning'              => $this->clean($result['reasoning'] ?? '', 800) ?? '',
            'factor_basis'           => 'ai_estimated',
            'factor_note'            => $this->clean($result['factor_source'] ?? null, 80) ?? 'AI estimate',
            'needs_clarification'    => (bool) ($result['needs_clarification'] ?? false),
            'clarification'          => $this->clean($result['clarification'] ?? null, 800),
            'source'                 => 'ai',
        ];
    }

    /**
     * Without the AI provider we cannot reliably extract a quantity or a factor,
     * so reuse the scope classifier for scope/source and ask the user to enter
     * the numbers manually.
     */
    protected function fallback(string $text): array
    {
        $c = $this->classifier->classify($text);

        return [
            'date'                   => now()->toDateString(),
            'scope'                  => $c['scope'],
            'scope3_category_id'     => $c['scope3_category_id'],
            'scope3_category_number' => $c['scope3_category_number'],
            'scope3_category_name'   => $c['scope3_category_name'],
            'emission_source'        => $c['suggested_source'] ?? 'Unspecified',
            'activity_data'          => null,
            'unit'                   => 'liters',
            'factor_kg_per_unit'     => null,
            'emission_factor'        => null,
            'co2e_value'             => null,
            'calculation_method'     => $c['calculation_method'],
            'confidence'             => $c['confidence'],
            'confidence_level'       => $this->confidenceLevel($c['confidence']),
            'reasoning'              => $c['reasoning'],
            'factor_basis'           => 'manual',
            'factor_note'            => 'AI offline — enter the factor manually',
            'needs_clarification'    => true,
            'clarification'          => 'AI is offline, so I identified the scope and source only. Please enter the quantity and emission factor.',
            'source'                 => 'keyword',
        ];
    }

    protected function normalizeUnit(?string $unit): string
    {
        $u = Str::lower(trim((string) $unit));
        $map = [
            'l' => 'liters', 'litre' => 'liters', 'litres' => 'liters', 'liter' => 'liters', 'liters' => 'liters',
            'kwh' => 'kWh', 'mwh' => 'kWh',
            'm3' => 'm3', 'm³' => 'm3', 'cubic metre' => 'm3', 'cubic meter' => 'm3',
            'km' => 'km', 'kilometre' => 'km', 'kilometer' => 'km',
            'kg' => 'kg', 'kgs' => 'kg', 'kilogram' => 'kg',
        ];
        return $map[$u] ?? ($unit ? trim($unit) : 'liters');
    }

    protected function normalizeDate($value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }
        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function confidenceLevel(float $c): string
    {
        return match (true) {
            $c >= 0.8 => 'high',
            $c >= 0.5 => 'medium',
            $c >= 0.3 => 'low',
            default   => 'estimated',
        };
    }

    protected function clean($v, int $max): ?string
    {
        if (!is_string($v) || trim($v) === '') {
            return null;
        }
        return Str::limit(trim($v), $max, '…');
    }
}

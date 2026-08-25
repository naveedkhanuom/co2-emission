<?php

namespace App\Services\Boundary;

use App\Models\Company;
use App\Models\EmissionSource;
use App\Models\Scope3Category;
use App\Services\AI\ClaudeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Produces the boundary checklist: which emission parameters this specific
 * company needs to measure, why, and where the data lives.
 *
 * Design rule — the AI ranks and justifies, the catalogue constrains. Claude
 * only ever selects from data we already hold (industry templates, the global
 * emission-source catalogue, the 15 Scope 3 categories) and every identifier it
 * returns is resolved against the database before it is stored. A hallucinated
 * source or category is dropped, never persisted.
 *
 * The all-15-categories sweep required by the GHG Protocol Scope 3 Standard is
 * enforced in code (see fillUnscreenedCategories) rather than trusted to the
 * prompt, so a silent omission by the model cannot produce an incomplete
 * screening.
 */
class BoundaryRecommendationService
{
    /** Bump when the prompt or output schema changes, so stored results stay traceable. */
    public const PROMPT_VERSION = 'boundary-advisor-v1';

    private const MATERIALITIES = ['high', 'medium', 'low'];

    private const RELEVANCES = ['relevant', 'not_relevant', 'unknown'];

    private const ACTION_TYPES = ['bill', 'supplier', 'import', 'spend', 'manual'];

    public function __construct(
        protected ClaudeService $claude,
        protected BoundaryInterviewService $interview,
    ) {}

    /**
     * Build the recommendation for a company.
     *
     * @param  array<int, array>  $questions  As returned by BoundaryInterviewService
     * @param  array<string, string>  $answers  question key => chosen option value
     * @return array{items: array<int, array>, summary: string, generator: string, model: ?string, prompt_version: string, confidence: float}
     */
    public function generate(Company $company, array $questions, array $answers): array
    {
        $implications = $this->interview->implicationsFor($questions, $answers);
        $categories = Scope3Category::orderBy('sort_order')->get(['id', 'name', 'sort_order', 'category_type']);
        $templates = $company->getIndustryTemplates();

        if ($this->claude->enabled()) {
            $ai = $this->generateWithClaude($company, $questions, $answers, $implications, $categories, $templates);
            if ($ai !== null) {
                return $ai;
            }
        }

        return $this->generateFromTemplates($company, $implications, $categories, $templates);
    }

    /**
     * Ask Claude to rank and justify a boundary, constrained to our catalogue.
     */
    protected function generateWithClaude(
        Company $company,
        array $questions,
        array $answers,
        array $implications,
        Collection $categories,
        Collection $templates,
    ): ?array {
        $categoryList = $categories
            ->map(fn ($c) => "{$c->sort_order}. {$c->name} ({$c->category_type})")
            ->implode("\n");

        $templateList = $templates
            ->map(fn ($t) => "- [scope {$t->scope}] {$t->name} — source: {$t->emission_source}, unit: {$t->unit}"
                .($t->description ? " — {$t->description}" : ''))
            ->implode("\n");

        $sourceList = EmissionSource::orderBy('scope')->pluck('name')->implode(' | ');

        $transcript = $this->transcript($questions, $answers);
        $profile = $this->interview->profileSummary($company);
        $tags = $implications['tags'] ? implode(', ', $implications['tags']) : 'none';

        $system = <<<SYS
You are a GHG accounting expert scoping a company's operational boundary under the GHG Protocol Corporate Standard and the Corporate Value Chain (Scope 3) Standard.

Produce the list of emission parameters THIS company must measure, and screen every Scope 3 category.

Scope rules:
- Scope 1: direct emissions from sources the company owns or controls — on-site fuel combustion, company-operated vehicles, fugitive refrigerant/gas leaks, and process emissions from the reaction itself.
- Scope 2: purchased energy the company consumes — electricity, district heating/cooling, steam.
- Scope 3: all other value-chain emissions, in these 15 categories:
{$categoryList}

Critical boundary distinctions you must apply correctly:
- An asset the company OWNS but a CUSTOMER operates (rental vehicles, leased-out property, colocation racks) is category 13 (downstream leased assets) — NOT Scope 1. Scope 1 covers only what the company's own staff operate.
- An asset the company LEASES IN, where the landlord controls and pays for the energy, is category 8 — not Scope 2.
- Materials bought by a subcontractor on the company's behalf are still the company's category 1.
- Process emissions (e.g. clinker calcination, chemical reactions) are Scope 1 but separate from fuel combustion — list them as their own item.

Industry templates available for this company (prefer these; they carry the right units):
{$templateList}

Emission sources in the platform catalogue (use these names verbatim where one fits):
{$sourceList}

Rules for your answer:
- Return an item for every parameter the company should measure, plus an entry for EVERY one of the 15 Scope 3 categories with its relevance. Never silently omit a category.
- `scope` must be 1, 2 or 3. For scope 3, `scope3_category_number` must be 1-15. For scope 1 and 2 it must be null.
- `emission_source` should be a name from the catalogue above when one fits; otherwise use a short descriptive name.
- `relevance`: "relevant" if the company should measure it, "not_relevant" if it genuinely does not apply, "unknown" if you cannot tell from what you were told.
- Every "not_relevant" MUST have a one-sentence `rationale` justifying the exclusion — this is an audit record.
- `materiality`: "high" if it is likely a top-3 contributor for this company, "low" if it is a rounding error.
- `data_hint`: where a non-expert would actually find this number (e.g. "your DEWA bills", "fuel card statements", "ask your main concrete supplier").
- `action_type`: one of bill, supplier, import, spend, manual — how the user should get the data into the system.
- `rationale`: one plain sentence, specific to THIS company. No jargon, no "Scope 3 category 13".
- `typical_share_pct`: rough share of total footprint, 0-100. Estimates are fine.

Return JSON:
{"summary":"2-3 plain sentences describing this company's inventory boundary","confidence":0.0-1.0,"items":[{"scope":1,"scope3_category_number":null,"name":"...","emission_source":"...","unit":"...","materiality":"high","relevance":"relevant","rationale":"...","data_hint":"...","action_type":"bill","typical_share_pct":25}]}
SYS;

        $prompt = <<<PROMPT
Company profile:
{$profile}

Answers to the clarifying questions:
{$transcript}

Boundary signals derived from those answers: {$tags}

Scope the operational boundary for this company.
PROMPT;

        $result = $this->claude->json($prompt, $system, [
            'temperature' => 0.1,
            'max_tokens' => 8000,
            'timeout' => (int) config('services.anthropic.boundary_timeout', 150),
        ]);

        if (! is_array($result) || empty($result['items']) || ! is_array($result['items'])) {
            return null;
        }

        $items = [];
        foreach ($result['items'] as $raw) {
            $item = $this->normaliseItem($raw, $categories, 'ai');
            if ($item !== null) {
                $items[] = $item;
            }
        }

        if (empty($items)) {
            return null;
        }

        $items = $this->fillUnscreenedCategories($items, $categories, 'ai');
        $items = $this->applyImplications($items, $implications, $categories);

        $confidence = (float) ($result['confidence'] ?? 0.7);

        return [
            'items' => $this->sortItems($items),
            'summary' => $this->cleanString($result['summary'] ?? null, 1000)
                ?? 'Boundary scoped from your business profile and answers.',
            'generator' => 'ai',
            'model' => config('services.anthropic.model'),
            'prompt_version' => self::PROMPT_VERSION,
            'confidence' => round(max(0.0, min(1.0, $confidence)), 2),
        ];
    }

    /**
     * Deterministic fallback: industry templates plus the implications of the
     * user's answers. Lower confidence by design — this exists so the advisor
     * still produces a usable boundary with no AI provider configured.
     */
    protected function generateFromTemplates(
        Company $company,
        array $implications,
        Collection $categories,
        Collection $templates,
    ): array {
        $items = [];

        foreach ($templates as $template) {
            $scope = (int) $template->scope;
            $categoryNumber = $scope === 3
                ? $this->guessCategoryNumber($template->emission_source, $template->name)
                : null;

            $items[] = $this->normaliseItem([
                'scope' => $scope,
                'scope3_category_number' => $categoryNumber,
                'name' => $template->name,
                'emission_source' => $template->emission_source,
                'unit' => $template->unit,
                'materiality' => $template->priority <= 1 ? 'high' : ($template->priority <= 3 ? 'medium' : 'low'),
                'relevance' => 'relevant',
                'rationale' => $template->description
                    ?: 'Typical for '.str_replace('_', ' ', (string) $company->industry_type).' companies.',
                'data_hint' => $this->defaultDataHint($template->emission_source, $scope),
                'action_type' => $this->defaultActionType($template->emission_source, $scope),
                'typical_share_pct' => null,
            ], $categories, 'template');
        }

        $items = array_values(array_filter($items));
        $items = $this->fillUnscreenedCategories($items, $categories, 'template');
        $items = $this->applyImplications($items, $implications, $categories);

        return [
            'items' => $this->sortItems($items),
            'summary' => 'Starting boundary based on typical '
                .str_replace('_', ' ', (string) $company->industry_type)
                .' operations and your answers. Review each line and exclude anything that does not apply to you.',
            'generator' => 'template',
            'model' => null,
            'prompt_version' => self::PROMPT_VERSION,
            'confidence' => 0.45,
        ];
    }

    /**
     * Validate one proposed item against our own data. Returns null when the
     * item cannot be trusted — a dropped row is always safer than a fabricated
     * source or category on an audited inventory.
     *
     * @return array<string, mixed>|null
     */
    protected function normaliseItem(mixed $raw, Collection $categories, string $source): ?array
    {
        if (! is_array($raw)) {
            return null;
        }

        $scope = (int) ($raw['scope'] ?? 0);
        if (! in_array($scope, [1, 2, 3], true)) {
            return null;
        }

        $name = $this->cleanString($raw['name'] ?? null, 190);
        if ($name === null) {
            return null;
        }

        // Scope 3 must resolve to a real category row; an unresolvable one is
        // dropped rather than stored against a guessed id.
        $category = null;
        if ($scope === 3) {
            $number = is_numeric($raw['scope3_category_number'] ?? null)
                ? (int) $raw['scope3_category_number']
                : null;

            $category = $number ? $categories->firstWhere('sort_order', $number) : null;

            if ($category === null) {
                return null;
            }
        }

        $relevance = in_array($raw['relevance'] ?? null, self::RELEVANCES, true)
            ? $raw['relevance']
            : 'relevant';

        $materiality = in_array($raw['materiality'] ?? null, self::MATERIALITIES, true)
            ? $raw['materiality']
            : 'medium';

        $actionType = in_array($raw['action_type'] ?? null, self::ACTION_TYPES, true)
            ? $raw['action_type']
            : $this->defaultActionType((string) ($raw['emission_source'] ?? $name), $scope);

        $rationale = $this->cleanString($raw['rationale'] ?? null, 500);

        // An exclusion with no justification is not an audit record. Demote it
        // to "unknown" so the user is asked rather than silently omitting it.
        if ($relevance === 'not_relevant' && $rationale === null) {
            $relevance = 'unknown';
        }

        $share = $raw['typical_share_pct'] ?? null;
        $share = is_numeric($share) ? max(0.0, min(100.0, (float) $share)) : null;

        return [
            'scope' => $scope,
            'scope3_category_id' => $category?->id,
            'scope3_category_number' => $category?->sort_order,
            'emission_source_id' => $this->resolveEmissionSourceId($raw['emission_source'] ?? null, $scope),
            'suggested_name' => $name,
            'suggested_unit' => $this->cleanString($raw['unit'] ?? null, 20),
            'materiality' => $materiality,
            'relevance' => $relevance,
            'decision' => 'pending',
            'rationale' => $rationale,
            'data_hint' => $this->cleanString($raw['data_hint'] ?? null, 500)
                ?? $this->defaultDataHint((string) ($raw['emission_source'] ?? $name), $scope),
            'action_type' => $actionType,
            'typical_share_pct' => $share,
            'confidence' => $source === 'ai' ? 0.8 : 0.45,
            'source' => $source,
        ];
    }

    /**
     * Resolve a free-text source name to a row in the global emission_sources
     * catalogue. Unmatched names leave the FK null — the descriptive
     * suggested_name still carries the meaning, and no invented id is stored.
     */
    protected function resolveEmissionSourceId(mixed $name, int $scope): ?int
    {
        if (! is_string($name) || trim($name) === '') {
            return null;
        }

        $name = trim($name);

        $exact = EmissionSource::whereRaw('LOWER(name) = ?', [Str::lower($name)])->first();
        if ($exact) {
            return $exact->id;
        }

        return EmissionSource::where('scope', $scope)
            ->where('name', 'like', '%'.$name.'%')
            ->value('id');
    }

    /**
     * Guarantee the Scope 3 Standard's all-15 screening in code. Any category
     * the generator did not mention is added as "unknown" so the user is
     * explicitly asked, instead of the omission passing as a decision.
     *
     * @param  array<int, array>  $items
     * @return array<int, array>
     */
    protected function fillUnscreenedCategories(array $items, Collection $categories, string $source): array
    {
        $seen = collect($items)
            ->where('scope', 3)
            ->pluck('scope3_category_number')
            ->filter()
            ->unique()
            ->all();

        foreach ($categories as $category) {
            if (in_array($category->sort_order, $seen, true)) {
                continue;
            }

            $items[] = [
                'scope' => 3,
                'scope3_category_id' => $category->id,
                'scope3_category_number' => $category->sort_order,
                'emission_source_id' => null,
                'suggested_name' => $category->name,
                'suggested_unit' => null,
                'materiality' => 'low',
                'relevance' => 'unknown',
                'decision' => 'pending',
                'rationale' => 'Not yet screened. The GHG Protocol requires a decision on all 15 Scope 3 categories — mark this included or excluded with a reason.',
                'data_hint' => null,
                'action_type' => 'manual',
                'typical_share_pct' => null,
                'confidence' => 0.3,
                'source' => $source,
            ];
        }

        return $items;
    }

    /**
     * Overlay what the user explicitly told us. Their answers outrank the
     * model: a category they said applies can never come back as not relevant.
     *
     * @param  array<int, array>  $items
     * @return array<int, array>
     */
    protected function applyImplications(array $items, array $implications, Collection $categories): array
    {
        $relevant = $implications['categories'] ?? [];
        $exclude = $implications['exclude_categories'] ?? [];

        foreach ($items as &$item) {
            $number = $item['scope3_category_number'] ?? null;
            if ($number === null) {
                continue;
            }

            if (in_array($number, $relevant, true) && $item['relevance'] !== 'relevant') {
                $item['relevance'] = 'relevant';
                $item['rationale'] = 'You told us this applies to your business.';
            }

            if (in_array($number, $exclude, true) && $item['relevance'] === 'unknown') {
                $item['relevance'] = 'not_relevant';
                $item['rationale'] = 'You told us this does not apply to your business.';
            }
        }

        return $items;
    }

    /**
     * Relevant first, then by materiality and estimated share — so the
     * checklist opens on the things that actually move the number.
     *
     * @param  array<int, array>  $items
     * @return array<int, array>
     */
    protected function sortItems(array $items): array
    {
        $relevanceRank = ['relevant' => 0, 'unknown' => 1, 'not_relevant' => 2];
        $materialityRank = ['high' => 0, 'medium' => 1, 'low' => 2];

        usort($items, function ($a, $b) use ($relevanceRank, $materialityRank) {
            return [$relevanceRank[$a['relevance']] ?? 1, $a['scope'], $materialityRank[$a['materiality']] ?? 1, -(float) ($a['typical_share_pct'] ?? 0)]
                <=> [$relevanceRank[$b['relevance']] ?? 1, $b['scope'], $materialityRank[$b['materiality']] ?? 1, -(float) ($b['typical_share_pct'] ?? 0)];
        });

        return $items;
    }

    /**
     * Render the Q&A for the prompt, using the labels the user actually saw.
     */
    protected function transcript(array $questions, array $answers): string
    {
        $lines = [];

        foreach ($questions as $question) {
            $chosen = $answers[$question['key']] ?? null;
            if ($chosen === null) {
                continue;
            }

            $label = collect($question['options'])->firstWhere('value', $chosen)['label'] ?? $chosen;
            $lines[] = "Q: {$question['question']}\nA: {$label}";
        }

        return $lines ? implode("\n\n", $lines) : 'No questions were answered.';
    }

    /**
     * Best-effort category number for a template row on the fallback path.
     */
    protected function guessCategoryNumber(string $emissionSource, string $name): ?int
    {
        $text = Str::lower($emissionSource.' '.$name);

        $map = [
            1 => ['purchased goods', 'raw material', 'feedstock', 'fabric', 'consumable', 'procurement', 'catering', 'laundry', 'subcontract'],
            2 => ['capital goods', 'machinery', 'hardware', 'vehicle manufacturing', 'server'],
            3 => ['fuel & energy', 'well-to-tank', 't&d'],
            4 => ['upstream transport', 'inbound', 'raw material transport'],
            5 => ['waste', 'wastewater'],
            6 => ['business travel', 'academic travel'],
            7 => ['commut'],
            8 => ['upstream leased'],
            9 => ['downstream transport', 'outbound', 'haulage', 'product transportation'],
            10 => ['processing of sold'],
            11 => ['use of sold'],
            12 => ['end-of-life', 'end of life'],
            13 => ['downstream leased', 'rental fleet', 'customer-driven'],
            14 => ['franchise'],
            15 => ['investment', 'financed'],
        ];

        foreach ($map as $number => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($text, $needle)) {
                    return $number;
                }
            }
        }

        // A Scope 3 template we cannot place is most often purchased goods.
        return 1;
    }

    /**
     * How the user should get this number into the system, reusing what the
     * platform already has: bill OCR, supplier surveys, CSV import, spend-based
     * estimation.
     */
    protected function defaultActionType(string $source, int $scope): string
    {
        $text = Str::lower($source);

        return match (true) {
            $scope === 2,
            str_contains($text, 'electricity'),
            str_contains($text, 'district'),
            str_contains($text, 'steam'),
            str_contains($text, 'natural gas') => 'bill',

            str_contains($text, 'diesel'),
            str_contains($text, 'gasoline'),
            str_contains($text, 'fleet'),
            str_contains($text, 'fuel'),
            str_contains($text, 'travel'),
            str_contains($text, 'commut'),
            str_contains($text, 'leased'),
            str_contains($text, 'rental') => 'import',

            str_contains($text, 'purchased goods'),
            str_contains($text, 'capital'),
            str_contains($text, 'supplier'),
            str_contains($text, 'waste'),
            str_contains($text, 'processing') => 'supplier',

            str_contains($text, 'investment'),
            str_contains($text, 'financed') => 'spend',

            default => 'manual',
        };
    }

    /**
     * Plain-language pointer to where the number lives, for the fallback path.
     */
    protected function defaultDataHint(string $source, int $scope): string
    {
        $text = Str::lower($source);

        return match (true) {
            str_contains($text, 'electricity') => 'Your monthly electricity bills — upload them and we will read the kWh.',
            str_contains($text, 'district') => 'Your district cooling invoices.',
            str_contains($text, 'natural gas') => 'Your gas bills or meter readings.',
            str_contains($text, 'diesel'),
            str_contains($text, 'gasoline'),
            str_contains($text, 'fleet') => 'Fuel card statements or fuel purchase invoices.',
            str_contains($text, 'refrigerant') => 'Your HVAC maintenance log — technicians record how much gas was topped up.',
            str_contains($text, 'waste') => 'Your waste contractor’s collection reports.',
            str_contains($text, 'purchased goods'),
            str_contains($text, 'capital') => 'Start from purchase spend in your accounts, then ask key suppliers for actual figures.',
            str_contains($text, 'travel') => 'Your travel agent or expense system.',
            str_contains($text, 'commut') => 'A short staff survey — how they travel and how far.',
            str_contains($text, 'investment') => 'Your portfolio or loan book, by sector and outstanding balance.',
            default => $scope === 3
                ? 'Ask the supplier or contractor responsible, or estimate from spend to begin with.'
                : 'Your own operational records or meter readings.',
        };
    }

    protected function cleanString(mixed $value, int $max): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return Str::limit(trim($value), $max, '');
    }
}

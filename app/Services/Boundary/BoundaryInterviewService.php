<?php

namespace App\Services\Boundary;

use App\Models\Company;
use App\Services\AI\ClaudeService;
use Illuminate\Support\Str;

/**
 * Builds the short clarifying interview shown between the profile step and the
 * boundary recommendation.
 *
 * Two layers: a deterministic bank in config/boundary_questions.php (which
 * always runs), plus up to `max_ai_questions` company-specific questions from
 * Claude when a provider is configured. The deterministic layer is what makes
 * the advisor usable with no API key, and it is also what the fallback
 * recommendation path reads its implications from.
 *
 * Every question is multiple-choice. The people answering are facility, finance
 * and ops staff, not carbon accountants — free text would produce answers we
 * cannot act on.
 */
class BoundaryInterviewService
{
    /** Bump when the AI question prompt changes, so stored transcripts stay traceable. */
    public const PROMPT_VERSION = 'boundary-interview-v1';

    public function __construct(protected ClaudeService $claude) {}

    /**
     * Assemble the questions to ask this company.
     *
     * @return array<int, array{key:string, question:string, help:?string, options:array<int, array{value:string, label:string}>, source:string}>
     */
    public function questionsFor(Company $company): array
    {
        $questions = $this->deterministicQuestions($company);
        $max = (int) config('boundary_questions.max_questions', 8);

        $questions = $this->trimToCap($questions, $max);

        if ($this->claude->enabled() && count($questions) < $max) {
            $room = min(
                $max - count($questions),
                (int) config('boundary_questions.max_ai_questions', 3)
            );

            foreach ($this->aiQuestions($company, $questions, $room) as $extra) {
                $questions[] = $extra;
            }
        }

        return array_values($questions);
    }

    /**
     * Trim to the cap by dropping the most GENERIC questions, never the
     * specific ones.
     *
     * A car-rental company assembles ten questions; naively slicing the tail
     * would discard the two sub-industry questions that decide its boundary and
     * keep six generic ones. Specificity wins: sub-industry, then industry,
     * then common. Display order is restored afterwards so the interview still
     * opens with the easy general questions.
     *
     * @param  array<int, array>  $questions
     * @return array<int, array>
     */
    protected function trimToCap(array $questions, int $max): array
    {
        if (count($questions) <= $max) {
            return $questions;
        }

        $ranked = [];
        foreach ($questions as $position => $question) {
            $ranked[] = ['position' => $position, 'question' => $question];
        }

        // Stable sort: most specific first, original order preserved within a tier.
        usort($ranked, function ($a, $b) {
            return [$this->specificityRank($a['question']), $a['position']]
                <=> [$this->specificityRank($b['question']), $b['position']];
        });

        $kept = array_slice($ranked, 0, $max);

        // Back to the order the user should read them in.
        usort($kept, fn ($a, $b) => $a['position'] <=> $b['position']);

        return array_column($kept, 'question');
    }

    /**
     * Lower is more specific, and therefore more worth keeping.
     */
    protected function specificityRank(array $question): int
    {
        return match ($question['source'] ?? 'common') {
            'sub_industry' => 0,
            'industry' => 1,
            default => 2,
        };
    }

    /**
     * The config-driven bank: common questions, then the ones this industry and
     * sub-industry routinely get wrong.
     *
     * @return array<int, array>
     */
    public function deterministicQuestions(Company $company): array
    {
        $bank = config('boundary_questions');

        // Keyed by the tier they came from — trimToCap uses this to drop
        // generic questions before specific ones.
        $sets = [
            'common' => $bank['common'] ?? [],
            'industry' => $bank['by_industry'][$company->industry_type] ?? [],
            'sub_industry' => $bank['by_sub_industry'][$company->sub_industry] ?? [],
        ];

        $out = [];
        foreach ($sets as $tier => $set) {
            foreach ($set as $key => $definition) {
                $out[] = $this->normaliseQuestion($key, $definition, $tier);
            }
        }

        return $out;
    }

    /**
     * Ask Claude for a few questions specific to THIS company's description that
     * the bank does not already cover. Returns [] on any failure — the bank
     * questions alone are always enough to proceed.
     *
     * @param  array<int, array>  $existing
     * @return array<int, array>
     */
    protected function aiQuestions(Company $company, array $existing, int $limit): array
    {
        if ($limit < 1) {
            return [];
        }

        $asked = collect($existing)->pluck('question')->implode("\n- ");

        $system = <<<SYS
You are a GHG accounting expert scoping a company's inventory boundary under the GHG Protocol Corporate Standard.

Your job is to ask up to {$limit} SHORT clarifying questions whose answers would CHANGE which emission sources belong in this company's boundary. Do not ask questions that are merely informative.

Rules:
- Every question must be answerable by a non-expert (facility, finance or operations staff). No jargon: no "Scope 3", no "category 13", no "fugitive".
- Every question must have 2-4 mutually exclusive multiple-choice options, plus nothing else. Do not ask for free text or numbers.
- Do not repeat, rephrase or narrow any question already being asked.
- Prefer questions about ownership, control and who operates or pays for something — those determine the boundary.
- If you cannot add a genuinely useful question, return an empty list.

These questions are ALREADY being asked:
- {$asked}

Return JSON: {"questions":[{"key":"snake_case_id","question":"...","help":"one short sentence explaining why it matters","options":[{"value":"snake_case","label":"..."}]}]}
SYS;

        $profile = $this->profileSummary($company);

        $result = $this->claude->json(
            "Company profile:\n\n{$profile}",
            $system,
            ['temperature' => 0.2, 'max_tokens' => 1200, 'timeout' => 60]
        );

        if (! is_array($result) || ! isset($result['questions']) || ! is_array($result['questions'])) {
            return [];
        }

        $existingKeys = collect($existing)->pluck('key')->all();
        $out = [];

        foreach ($result['questions'] as $raw) {
            if (count($out) >= $limit) {
                break;
            }

            $question = $this->normaliseAiQuestion($raw, $existingKeys);
            if ($question !== null) {
                $existingKeys[] = $question['key'];
                $out[] = $question;
            }
        }

        return $out;
    }

    /**
     * Validate one AI-proposed question. Anything malformed is dropped rather
     * than shown — a broken question is worse than one fewer question.
     */
    protected function normaliseAiQuestion(mixed $raw, array $existingKeys): ?array
    {
        if (! is_array($raw)) {
            return null;
        }

        $text = $this->cleanString($raw['question'] ?? null, 200);
        $options = $raw['options'] ?? null;

        if ($text === null || ! is_array($options)) {
            return null;
        }

        $key = Str::slug((string) ($raw['key'] ?? $text), '_');
        $key = Str::limit($key, 40, '');

        if ($key === '' || in_array($key, $existingKeys, true)) {
            return null;
        }

        $clean = [];
        foreach ($options as $option) {
            if (! is_array($option)) {
                continue;
            }

            $label = $this->cleanString($option['label'] ?? null, 120);
            if ($label === null) {
                continue;
            }

            $value = Str::slug((string) ($option['value'] ?? $label), '_');
            $value = Str::limit($value, 40, '');

            if ($value === '') {
                continue;
            }

            $clean[] = ['value' => $value, 'label' => $label, 'implies' => []];
        }

        // A single-option question decides nothing; more than four is a survey.
        if (count($clean) < 2 || count($clean) > 4) {
            return null;
        }

        $clean[] = ['value' => 'not_sure', 'label' => 'Not sure', 'implies' => []];

        return [
            'key' => $key,
            'question' => $text,
            'help' => $this->cleanString($raw['help'] ?? null, 200),
            'options' => $clean,
            'source' => 'ai',
        ];
    }

    /**
     * Turn a config entry into the shape the UI and the recommendation service
     * both consume.
     */
    protected function normaliseQuestion(string $key, array $definition, string $source): array
    {
        $options = [];
        foreach ($definition['options'] ?? [] as $value => $option) {
            $options[] = [
                'value' => (string) $value,
                'label' => $option['label'] ?? (string) $value,
                'implies' => $option['implies'] ?? [],
            ];
        }

        return [
            'key' => $key,
            'question' => $definition['question'] ?? $key,
            'help' => $definition['help'] ?? null,
            'options' => $options,
            'source' => $source,
        ];
    }

    /**
     * Fold the user's answers into the boundary hints the recommendation layer
     * acts on. This is what lets the deterministic path produce a tailored
     * result without any AI involvement.
     *
     * @param  array<int, array>  $questions  As returned by questionsFor()
     * @param  array<string, string>  $answers  question key => chosen option value
     * @return array{scopes:int[], categories:int[], exclude_categories:int[], tags:string[]}
     */
    public function implicationsFor(array $questions, array $answers): array
    {
        $scopes = [];
        $categories = [];
        $exclude = [];
        $tags = [];

        foreach ($questions as $question) {
            $chosen = $answers[$question['key']] ?? null;
            if ($chosen === null) {
                continue;
            }

            foreach ($question['options'] as $option) {
                if ($option['value'] !== $chosen) {
                    continue;
                }

                $implies = $option['implies'] ?? [];
                $scopes = array_merge($scopes, $implies['scopes'] ?? []);
                $categories = array_merge($categories, $implies['categories'] ?? []);
                $exclude = array_merge($exclude, $implies['exclude_categories'] ?? []);
                $tags = array_merge($tags, $implies['tags'] ?? []);
            }
        }

        // An explicit "relevant" always beats an "exclude" from another answer:
        // over-reporting a category is recoverable, omitting one is a finding.
        $exclude = array_values(array_diff(array_unique($exclude), $categories));

        sort($scopes);
        sort($categories);
        sort($exclude);

        return [
            'scopes' => array_values(array_unique($scopes)),
            'categories' => array_values(array_unique($categories)),
            'exclude_categories' => $exclude,
            'tags' => array_values(array_unique($tags)),
        ];
    }

    /**
     * Readable profile block used in both AI prompts.
     */
    public function profileSummary(Company $company): string
    {
        $lines = array_filter([
            'Industry: '.($company->industry_type ?: 'unspecified'),
            $company->sub_industry ? 'Sub-industry: '.$company->sub_industry : null,
            $company->country ? 'Country: '.$company->country : null,
            $company->employee_count ? 'Employees: '.$company->employee_count : null,
            $company->business_description
                ? 'What they do: '.$company->business_description
                : null,
        ]);

        return implode("\n", $lines);
    }

    protected function cleanString(mixed $value, int $max): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return Str::limit(trim($value), $max, '');
    }
}

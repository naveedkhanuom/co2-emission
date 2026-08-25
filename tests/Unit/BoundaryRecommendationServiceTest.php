<?php

namespace Tests\Unit;

use App\Models\Scope3Category;
use App\Services\AI\ClaudeService;
use App\Services\Boundary\BoundaryInterviewService;
use App\Services\Boundary\BoundaryRecommendationService;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * The validation layer is the safety net that lets us put a language model in
 * front of audited carbon data: the AI ranks and justifies, but nothing it
 * returns is stored until it has been resolved against our own catalogue.
 *
 * These tests drive that layer directly with hand-built categories, so no
 * database is required and the guarantees are checked in isolation.
 */
class BoundaryRecommendationServiceTest extends TestCase
{
    private BoundaryRecommendationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Anonymous subclass: exposes the protected validation pipeline and
        // stubs the one method that would hit the database.
        $this->service = new class(new ClaudeService, new BoundaryInterviewService(new ClaudeService)) extends BoundaryRecommendationService
        {
            /** @var array<string, int> */
            public array $knownSources = ['natural gas' => 7, 'purchased electricity (location-based)' => 12];

            protected function resolveEmissionSourceId(mixed $name, int $scope): ?int
            {
                if (! is_string($name)) {
                    return null;
                }

                return $this->knownSources[mb_strtolower(trim($name))] ?? null;
            }

            public function normalise(mixed $raw, Collection $categories, string $source): ?array
            {
                return $this->normaliseItem($raw, $categories, $source);
            }

            public function fillGaps(array $items, Collection $categories, string $source): array
            {
                return $this->fillUnscreenedCategories($items, $categories, $source);
            }

            public function overlay(array $items, array $implications, Collection $categories): array
            {
                return $this->applyImplications($items, $implications, $categories);
            }
        };
    }

    /** The 15 GHG Protocol Scope 3 categories, without touching the database. */
    private function categories(): Collection
    {
        return collect(range(1, 15))->map(function (int $n) {
            $category = new Scope3Category([
                'name' => "Category {$n}",
                'sort_order' => $n,
                'category_type' => $n <= 8 ? 'upstream' : 'downstream',
            ]);
            $category->id = 100 + $n;

            return $category;
        });
    }

    private function validItem(array $overrides = []): array
    {
        return array_merge([
            'scope' => 1,
            'scope3_category_number' => null,
            'name' => 'Standby generator diesel',
            'emission_source' => 'Natural Gas',
            'unit' => 'L',
            'materiality' => 'high',
            'relevance' => 'relevant',
            'rationale' => 'You told us you run backup generators.',
            'data_hint' => 'Fuel card statements.',
            'action_type' => 'import',
            'typical_share_pct' => 12,
        ], $overrides);
    }

    /**
     * The Scope 3 Standard requires a decision on all 15 categories. We enforce
     * that in code rather than trusting the prompt, so a model that silently
     * omits a category cannot produce an incomplete screening.
     */
    public function test_all_fifteen_scope3_categories_are_always_screened(): void
    {
        $categories = $this->categories();

        $items = $this->service->fillGaps(
            [$this->service->normalise($this->validItem(), $categories, 'ai')],
            $categories,
            'ai'
        );

        $screened = collect($items)
            ->where('scope', 3)
            ->pluck('scope3_category_number')
            ->unique()
            ->sort()
            ->values()
            ->all();

        $this->assertSame(range(1, 15), $screened);
    }

    /** Unscreened categories arrive as "unknown", never as a silent exclusion. */
    public function test_unscreened_categories_are_marked_unknown_not_excluded(): void
    {
        $categories = $this->categories();
        $items = $this->service->fillGaps([], $categories, 'ai');

        foreach ($items as $item) {
            $this->assertSame('unknown', $item['relevance']);
            $this->assertSame('pending', $item['decision']);
        }
    }

    /** A category number outside 1-15 cannot be resolved, so the item is dropped. */
    public function test_hallucinated_category_number_is_dropped(): void
    {
        $result = $this->service->normalise(
            $this->validItem(['scope' => 3, 'scope3_category_number' => 27]),
            $this->categories(),
            'ai'
        );

        $this->assertNull($result);
    }

    /** A Scope 3 item with no category at all is equally unusable. */
    public function test_scope3_item_without_category_is_dropped(): void
    {
        $result = $this->service->normalise(
            $this->validItem(['scope' => 3, 'scope3_category_number' => null]),
            $this->categories(),
            'ai'
        );

        $this->assertNull($result);
    }

    /** Scope must be 1, 2 or 3 — there is no scope 4. */
    public function test_out_of_range_scope_is_dropped(): void
    {
        $this->assertNull(
            $this->service->normalise($this->validItem(['scope' => 4]), $this->categories(), 'ai')
        );
    }

    /**
     * An unknown source name is not a reason to lose the item — the descriptive
     * name still carries the meaning. But no invented foreign key is stored.
     */
    public function test_unmatched_emission_source_leaves_fk_null_but_keeps_item(): void
    {
        $result = $this->service->normalise(
            $this->validItem(['emission_source' => 'Unobtainium Combustion']),
            $this->categories(),
            'ai'
        );

        $this->assertNotNull($result);
        $this->assertNull($result['emission_source_id']);
        $this->assertSame('Standby generator diesel', $result['suggested_name']);
    }

    /** A known source name resolves to the catalogue row. */
    public function test_known_emission_source_resolves_to_catalogue_id(): void
    {
        $result = $this->service->normalise($this->validItem(), $this->categories(), 'ai');

        $this->assertSame(7, $result['emission_source_id']);
    }

    /**
     * An exclusion with no written justification is not an audit record. Rather
     * than storing a bare "not relevant", we demote it to unknown so the user
     * is forced to decide and give a reason.
     */
    public function test_exclusion_without_a_reason_is_demoted_to_unknown(): void
    {
        $result = $this->service->normalise(
            $this->validItem(['relevance' => 'not_relevant', 'rationale' => null]),
            $this->categories(),
            'ai'
        );

        $this->assertSame('unknown', $result['relevance']);
    }

    /** An exclusion WITH a reason is kept as given. */
    public function test_justified_exclusion_is_preserved(): void
    {
        $result = $this->service->normalise(
            $this->validItem(['relevance' => 'not_relevant', 'rationale' => 'You do not sell physical products.']),
            $this->categories(),
            'ai'
        );

        $this->assertSame('not_relevant', $result['relevance']);
        $this->assertSame('You do not sell physical products.', $result['rationale']);
    }

    /** Garbage enum values fall back to safe defaults rather than being stored. */
    public function test_invalid_enums_fall_back_to_defaults(): void
    {
        $result = $this->service->normalise(
            $this->validItem(['materiality' => 'catastrophic', 'relevance' => 'maybe', 'action_type' => 'telepathy']),
            $this->categories(),
            'ai'
        );

        $this->assertSame('medium', $result['materiality']);
        $this->assertSame('relevant', $result['relevance']);
        $this->assertContains($result['action_type'], ['bill', 'supplier', 'import', 'spend', 'manual']);
    }

    /** A share estimate outside 0-100 is clamped, not stored as given. */
    public function test_typical_share_is_clamped(): void
    {
        $high = $this->service->normalise($this->validItem(['typical_share_pct' => 250]), $this->categories(), 'ai');
        $low = $this->service->normalise($this->validItem(['typical_share_pct' => -5]), $this->categories(), 'ai');

        $this->assertSame(100.0, $high['typical_share_pct']);
        $this->assertSame(0.0, $low['typical_share_pct']);
    }

    /** An item with no name is meaningless on a checklist. */
    public function test_item_without_a_name_is_dropped(): void
    {
        $this->assertNull(
            $this->service->normalise($this->validItem(['name' => '  ']), $this->categories(), 'ai')
        );
    }

    /**
     * What the user explicitly told us outranks the model. If they said they
     * ship to customers, category 9 cannot come back as unscreened.
     */
    public function test_user_answers_override_model_relevance(): void
    {
        $categories = $this->categories();
        $items = $this->service->fillGaps([], $categories, 'ai');

        $items = $this->service->overlay($items, [
            'categories' => [9],
            'exclude_categories' => [14],
            'tags' => [],
        ], $categories);

        $byNumber = collect($items)->keyBy('scope3_category_number');

        $this->assertSame('relevant', $byNumber[9]['relevance']);
        $this->assertSame('not_relevant', $byNumber[14]['relevance']);
        $this->assertNotNull($byNumber[14]['rationale'], 'An exclusion always needs a reason on record.');
    }

    /** Template-sourced items carry lower confidence than AI ones, by design. */
    public function test_template_items_carry_lower_confidence(): void
    {
        $ai = $this->service->normalise($this->validItem(), $this->categories(), 'ai');
        $template = $this->service->normalise($this->validItem(), $this->categories(), 'template');

        $this->assertGreaterThan($template['confidence'], $ai['confidence']);
    }
}

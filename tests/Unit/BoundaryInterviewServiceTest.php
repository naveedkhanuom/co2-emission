<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Services\AI\ClaudeService;
use App\Services\Boundary\BoundaryInterviewService;
use Tests\TestCase;

/**
 * The interview is the deterministic half of the Boundary Advisor: it must
 * produce correct boundary implications with no AI provider involved. These
 * tests exercise the config question bank directly — no database is touched.
 */
class BoundaryInterviewServiceTest extends TestCase
{
    private BoundaryInterviewService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BoundaryInterviewService(new ClaudeService);
    }

    private function company(string $industry, ?string $subIndustry = null): Company
    {
        $company = new Company([
            'name' => 'Test Co',
            'industry_type' => $industry,
        ]);
        $company->sub_industry = $subIndustry;

        return $company;
    }

    /**
     * The classic misclassification: a rental company owns the cars, so it
     * assumes customer driving is Scope 1. Under the GHG Protocol it is
     * category 13 (downstream leased assets), because the customer operates
     * the vehicle. If this ever regresses, every rental customer's inventory
     * is wrong.
     */
    public function test_customer_driven_fleet_maps_to_category_13_not_scope_1(): void
    {
        $questions = $this->service->deterministicQuestions($this->company('transportation', 'car_rental'));

        $implications = $this->service->implicationsFor($questions, [
            'who_drives' => 'customers',
        ]);

        $this->assertContains(13, $implications['categories'], 'Customer-operated fleet must be category 13.');
        $this->assertNotContains(1, $implications['scopes'], 'Customer-operated fleet must NOT be Scope 1.');
        $this->assertContains('downstream_leased', $implications['tags']);
    }

    /** The mirror case: when the company's own staff drive, it IS Scope 1. */
    public function test_staff_driven_fleet_maps_to_scope_1(): void
    {
        $questions = $this->service->deterministicQuestions($this->company('transportation'));

        $implications = $this->service->implicationsFor($questions, [
            'who_drives' => 'our_staff',
        ]);

        $this->assertContains(1, $implications['scopes']);
        $this->assertNotContains(13, $implications['categories']);
    }

    /**
     * A leased building where the landlord pays the bills is category 8, not
     * the tenant's Scope 2 — the tenant never controls that energy.
     */
    public function test_landlord_paid_energy_maps_to_category_8(): void
    {
        $questions = $this->service->deterministicQuestions($this->company('technology'));

        $implications = $this->service->implicationsFor($questions, [
            'premises' => 'lease_landlord_pays',
        ]);

        $this->assertContains(8, $implications['categories']);
        $this->assertNotContains(2, $implications['scopes']);
    }

    /**
     * Over-reporting a category is recoverable; omitting one is an assurance
     * finding. So when one answer marks a category relevant and another would
     * exclude it, relevant wins.
     */
    public function test_relevance_beats_exclusion_when_answers_conflict(): void
    {
        $questions = [[
            'key' => 'a',
            'options' => [['value' => 'yes', 'label' => 'Yes', 'implies' => ['categories' => [9]]]],
        ], [
            'key' => 'b',
            'options' => [['value' => 'no', 'label' => 'No', 'implies' => ['exclude_categories' => [9]]]],
        ]];

        $implications = $this->service->implicationsFor($questions, ['a' => 'yes', 'b' => 'no']);

        $this->assertContains(9, $implications['categories']);
        $this->assertNotContains(9, $implications['exclude_categories']);
    }

    /** Sub-industry questions are layered on top of the industry ones. */
    public function test_sub_industry_questions_are_appended(): void
    {
        $keys = collect($this->service->deterministicQuestions($this->company('transportation', 'car_rental')))
            ->pluck('key')
            ->all();

        $this->assertContains('premises', $keys, 'Common questions must always be present.');
        $this->assertContains('who_drives', $keys, 'Industry questions must be present.');
        $this->assertContains('fuel_policy', $keys, 'Sub-industry questions must be present.');
    }

    /**
     * Friction kills completion. The interview is capped, and the cap includes
     * anything the AI adds.
     */
    public function test_question_count_is_capped(): void
    {
        $questions = $this->service->questionsFor($this->company('transportation', 'car_rental'));

        $this->assertLessThanOrEqual(
            (int) config('boundary_questions.max_questions'),
            count($questions)
        );
    }

    /**
     * A car-rental company assembles more questions than the cap allows. The
     * two that decide its boundary are the sub-industry ones, and they are last
     * in assembly order — so a naive slice would drop exactly the questions
     * worth asking. Specificity must win over position.
     */
    public function test_cap_drops_generic_questions_before_specific_ones(): void
    {
        $company = $this->company('transportation', 'car_rental');

        $assembled = $this->service->deterministicQuestions($company);
        $this->assertGreaterThan(
            (int) config('boundary_questions.max_questions'),
            count($assembled),
            'This test is only meaningful when assembly exceeds the cap.'
        );

        $keys = collect($this->service->questionsFor($company))->pluck('key')->all();

        $this->assertContains('fuel_policy', $keys, 'Sub-industry questions must survive the cap.');
        $this->assertContains('rental_duration', $keys, 'Sub-industry questions must survive the cap.');
        $this->assertContains('who_drives', $keys, 'Industry questions must survive the cap.');
    }

    /** Trimming must not reorder what survives — the interview still reads naturally. */
    public function test_cap_preserves_display_order(): void
    {
        $company = $this->company('transportation', 'car_rental');

        $assembledOrder = collect($this->service->deterministicQuestions($company))->pluck('key')->all();
        $keptOrder = collect($this->service->questionsFor($company))->pluck('key')->all();

        $expected = array_values(array_filter($assembledOrder, fn ($k) => in_array($k, $keptOrder, true)));

        $this->assertSame($expected, array_values(array_filter($keptOrder, fn ($k) => in_array($k, $assembledOrder, true))));
    }

    /** Every option must be selectable — a question with no options decides nothing. */
    public function test_every_bank_question_has_at_least_two_options(): void
    {
        foreach (array_keys(config('boundary_questions.by_industry')) as $industry) {
            foreach ($this->service->deterministicQuestions($this->company($industry)) as $question) {
                $this->assertGreaterThanOrEqual(
                    2,
                    count($question['options']),
                    "Question {$question['key']} in {$industry} needs at least two options."
                );
            }
        }
    }

    /** Unanswered questions contribute nothing rather than defaulting. */
    public function test_unanswered_questions_are_ignored(): void
    {
        $questions = $this->service->deterministicQuestions($this->company('retail'));

        $implications = $this->service->implicationsFor($questions, []);

        $this->assertSame([], $implications['scopes']);
        $this->assertSame([], $implications['categories']);
    }
}

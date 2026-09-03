<?php

namespace App\Http\Controllers;

use App\Exports\BoundaryStatementExport;
use App\Models\BoundaryAssessment;
use App\Models\BoundaryItem;
use App\Models\Company;
use App\Models\IndustryEmissionTemplate;
use App\Services\AI\ClaudeService;
use App\Services\Boundary\BoundaryCoverageService;
use App\Services\Boundary\BoundaryDiffService;
use App\Services\Boundary\BoundaryInterviewService;
use App\Services\Boundary\BoundaryRecommendationService;
use App\Services\Boundary\BoundaryStatementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

/**
 * The Boundary Advisor — works out WHAT a company needs to measure, before it
 * ever enters a number.
 *
 * The rest of the platform can calculate anything the user enters, but a
 * construction firm, a car-rental company or a hospital does not know what to
 * enter. This walks them from a plain-language description of their business to
 * a decided, justified inventory boundary they can act on.
 *
 * The advisor only ever produces drafts a human accepts: it never creates,
 * edits or deletes an emission record.
 */
class BoundaryController extends Controller
{
    public function __construct(
        private BoundaryInterviewService $interview,
        private BoundaryRecommendationService $recommender,
        private BoundaryCoverageService $coverage,
        private BoundaryDiffService $diff,
    ) {
        $this->middleware('auth');
        $this->middleware('permission:list-boundary|create-boundary|edit-boundary', ['only' => ['index', 'export']]);
        $this->middleware('permission:create-boundary', ['only' => ['start', 'generate', 'restart']]);
        $this->middleware('permission:edit-boundary', ['only' => ['decide', 'acceptRecommended', 'activate']]);
    }

    /**
     * The boundary workspace: the live checklist if one exists, otherwise the
     * profile step that starts the wizard.
     */
    public function index()
    {
        $company = $this->resolveCompany();

        if (! $company) {
            return redirect()->route('home')
                ->with('error', 'Select a company before scoping its boundary.');
        }

        $assessment = BoundaryAssessment::where('company_id', $company->id)
            ->whereIn('status', ['draft', 'active'])
            ->orderByRaw("FIELD(status, 'draft', 'active')")
            ->latest('reporting_year')
            ->latest('version')
            ->first();

        $items = $assessment
            ? $assessment->items()->with(['scope3Category', 'emissionSource'])->byPriority()->get()
            : collect();

        return view('boundary.index', [
            'company' => $company,
            'assessment' => $assessment,
            'items' => $items,
            'coverage' => $assessment && $assessment->status === 'active'
                ? $this->coverage->forCompany($company)
                : null,
            // What changed since the boundary this one replaced — the trigger an
            // assurer looks for when year-on-year totals move.
            'diff' => $assessment && $assessment->status === 'active'
                ? $this->diff->forCompany($company)
                : null,
            'industries' => $this->industryOptions(),
            'subIndustries' => $company->industry_type
                ? IndustryEmissionTemplate::subIndustriesFor($company->industry_type)
                : [],
            'aiEnabled' => app(ClaudeService::class)->enabled(),
            'currentYear' => (int) now()->year,

            // Arriving straight from the setup wizard rather than from the
            // sidebar. The screen is identical either way — this only changes
            // the greeting, so a client who was just told "one thing left"
            // recognises where they landed instead of meeting a cold form.
            'fromOnboarding' => $company->getSetting('onboarding_stage')
                === OnboardingController::STAGE_BOUNDARY,

            // Default the boundary to the year the client already nominated in
            // setup, so the two screens do not quietly disagree about which
            // year is being scoped. Falls back to the current year, and is
            // ignored if the stored value sits outside the selectable range.
            'defaultReportingYear' => $this->defaultReportingYear($company),
        ]);
    }

    /**
     * Which reporting year the boundary wizard should preselect.
     *
     * The base year set during onboarding, when it is one of the years the
     * dropdown actually offers — otherwise selecting it would match no option
     * and the browser would silently fall back to the first (a year ahead),
     * which is worse than defaulting to today.
     */
    private function defaultReportingYear(Company $company): int
    {
        $currentYear = (int) now()->year;
        $baseYear = (int) $company->getSetting('base_year', 0);

        return ($baseYear >= $currentYear - 3 && $baseYear <= $currentYear + 1)
            ? $baseYear
            : $currentYear;
    }

    /**
     * Step 1 → 2. Save the business profile and return the clarifying questions.
     *
     * A draft assessment is created here rather than at generation time so the
     * questions a user was actually asked are on record even if they abandon
     * the wizard part-way.
     */
    public function start(Request $request): JsonResponse
    {
        $company = $this->resolveCompany();

        if (! $company) {
            return response()->json(['success' => false, 'message' => 'No company selected.'], 422);
        }

        $validated = $request->validate([
            'industry_type' => 'required|in:'.implode(',', array_keys($this->industryOptions())),
            'sub_industry' => 'nullable|string|max:50',
            'business_description' => 'required|string|min:10|max:1000',
            'reporting_year' => 'nullable|integer|min:2000|max:'.(now()->year + 1),
        ], [
            'business_description.required' => 'Tell us in a sentence or two what your company does.',
            'business_description.min' => 'A little more detail will give you a much better result.',
        ]);

        $company->update([
            'industry_type' => $validated['industry_type'],
            'sub_industry' => $validated['sub_industry'] ?? null,
            'business_description' => $validated['business_description'],
        ]);

        $year = (int) ($validated['reporting_year'] ?? now()->year);
        $questions = $this->interview->questionsFor($company);

        $assessment = BoundaryAssessment::create([
            'company_id' => $company->id,
            'reporting_year' => $year,
            'status' => 'draft',
            'version' => $this->nextVersion($company, $year),
            'profile_snapshot' => [
                'industry_type' => $company->industry_type,
                'sub_industry' => $company->sub_industry,
                'business_description' => $company->business_description,
                'country' => $company->country,
                'employee_count' => $company->employee_count,
            ],
            'questions' => $questions,
            'prompt_version' => BoundaryInterviewService::PROMPT_VERSION,
        ]);

        return response()->json([
            'success' => true,
            'assessment_id' => $assessment->id,
            'questions' => $questions,
        ]);
    }

    /**
     * Step 2 → 3. Record the answers and produce the boundary checklist.
     */
    public function generate(Request $request, string $assessmentId): JsonResponse
    {
        $company = $this->resolveCompany();
        $assessment = $this->findAssessment($assessmentId, $company);

        if (! $assessment) {
            return response()->json(['success' => false, 'message' => 'Assessment not found.'], 404);
        }

        if (demo_route_restricted('boundary.generate')) {
            return response()->json([
                'success' => false,
                'message' => 'Generating a boundary is disabled for demo accounts.',
            ], 403);
        }

        $validated = $request->validate([
            'answers' => 'required|array|min:1',
            'answers.*' => 'nullable|string|max:100',
        ], [
            'answers.required' => 'Answer at least one question so we can tailor your boundary.',
        ]);

        $questions = $assessment->questions ?? [];
        $answers = array_filter($validated['answers'], fn ($a) => $a !== null && $a !== '');

        $result = $this->recommender->generate($company, $questions, $answers);

        DB::transaction(function () use ($assessment, $questions, $answers, $result, $company) {
            // Regenerating replaces the previous draft checklist, but never
            // touches decisions on an already-active assessment.
            $assessment->items()->delete();

            $assessment->update([
                'questions' => $this->mergeAnswers($questions, $answers),
                'summary' => $result['summary'],
                'generator' => $result['generator'],
                'model' => $result['model'],
                'prompt_version' => $result['prompt_version'],
                'confidence' => $result['confidence'],
                'generated_at' => now(),
            ]);

            foreach ($result['items'] as $item) {
                unset($item['scope3_category_number']);

                $assessment->items()->create(array_merge($item, [
                    'company_id' => $company->id,
                ]));
            }
        });

        return response()->json([
            'success' => true,
            'redirect' => route('boundary.index'),
            'message' => $result['generator'] === 'ai'
                ? 'Your boundary is ready. Review each line before you accept it.'
                : 'Starting boundary built from industry templates. Review each line before you accept it.',
        ]);
    }

    /**
     * Step 3. Include, exclude or defer a single checklist item.
     *
     * An exclusion without a written reason is rejected — that reason is the
     * evidence an assurer asks for when a category is reported as not relevant.
     */
    public function decide(Request $request, string $itemId): JsonResponse
    {
        $company = $this->resolveCompany();
        $item = $this->findItem($itemId, $company);

        if (! $item) {
            return response()->json(['success' => false, 'message' => 'Item not found.'], 404);
        }

        $validated = $request->validate([
            'decision' => 'required|in:included,excluded,deferred,pending',
            'exclusion_reason' => 'required_if:decision,excluded|nullable|string|min:5|max:1000',
        ], [
            'exclusion_reason.required_if' => 'Say why this does not apply — it goes on the audit record.',
            'exclusion_reason.min' => 'Give a little more detail for the audit record.',
        ]);

        $item->update([
            'decision' => $validated['decision'],
            'exclusion_reason' => $validated['decision'] === 'excluded'
                ? $validated['exclusion_reason']
                : null,
            'relevance' => match ($validated['decision']) {
                'included' => 'relevant',
                'excluded' => 'not_relevant',
                default => $item->relevance,
            },
            'accepted_by' => Auth::id(),
            'accepted_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'item' => $item->only(['id', 'decision', 'relevance', 'exclusion_reason']),
            'progress' => $this->progressFor($item->assessment),
        ]);
    }

    /**
     * Accept every item the advisor marked relevant, in one click. Items it was
     * unsure about are deliberately left for the user to decide.
     */
    public function acceptRecommended(string $assessmentId): JsonResponse
    {
        $company = $this->resolveCompany();
        $assessment = $this->findAssessment($assessmentId, $company);

        if (! $assessment) {
            return response()->json(['success' => false, 'message' => 'Assessment not found.'], 404);
        }

        $count = $assessment->items()
            ->where('decision', 'pending')
            ->where('relevance', 'relevant')
            ->update([
                'decision' => 'included',
                'accepted_by' => Auth::id(),
                'accepted_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => $count.' '.str('item')->plural($count).' added to your boundary.',
            'progress' => $this->progressFor($assessment->fresh()),
        ]);
    }

    /**
     * Step 4. Freeze the assessment and make it the company's live boundary.
     *
     * The previous active assessment is superseded rather than deleted, so a
     * boundary change between years stays visible to an auditor.
     */
    public function activate(string $assessmentId)
    {
        $company = $this->resolveCompany();
        $assessment = $this->findAssessment($assessmentId, $company);

        if (! $assessment) {
            abort(404);
        }

        $undecided = $assessment->items()->where('decision', 'pending')->count();

        if ($undecided > 0) {
            return back()->with('error', "Decide the remaining {$undecided} ".str('item')->plural($undecided).' before activating your boundary.');
        }

        DB::transaction(function () use ($assessment, $company) {
            BoundaryAssessment::where('company_id', $company->id)
                ->where('status', 'active')
                ->where('id', '!=', $assessment->id)
                ->update(['status' => 'superseded']);

            $assessment->update([
                'status' => 'active',
                'completed_by' => Auth::id(),
                'completed_at' => now(),
            ]);

            // Switch on the scopes the boundary actually uses, so the entry
            // screens stop offering scopes this company does not report.
            $scopes = $assessment->includedItems()->distinct()->pluck('scope')->sort()->values()->all();
            if ($scopes) {
                $company->update(['scopes_enabled' => $scopes]);
            }

            // The setup chain ends here: wizard → boundary → live. Clearing the
            // stage is what stops the "finish setting up" greeting following a
            // client around after they have finished setting up.
            $company->setSetting('onboarding_stage', 'complete', 'string');
        });

        return redirect()->route('boundary.index')
            ->with('success', 'Your inventory boundary is live. Work through the gaps to complete your first year.');
    }

    /**
     * The Boundary Statement — the auditor-facing artefact.
     *
     * PDF is the document a company files or hands to an assurer; the
     * spreadsheet is the Scope 3 screening table on its own, which is what
     * gets pasted into a CDP or ESRS workbook. Both render the same
     * BoundaryStatementService output so they cannot disagree.
     */
    public function export(Request $request, string $assessmentId, BoundaryStatementService $statementService)
    {
        $company = $this->resolveCompany();
        $assessment = $this->findAssessment($assessmentId, $company);

        if (! $assessment) {
            abort(404);
        }

        $statement = $statementService->build($company, $assessment);
        $slug = 'boundary-statement-'.$assessment->reporting_year.'-v'.$assessment->version;
        $format = $request->get('format');

        if (in_array($format, ['excel', 'csv'], true)) {
            return Excel::download(
                new BoundaryStatementExport($statementService->screeningRows($statement), (int) $assessment->reporting_year),
                $slug.($format === 'csv' ? '.csv' : '.xlsx'),
                $format === 'csv' ? ExcelWriter::CSV : ExcelWriter::XLSX
            );
        }

        return Pdf::loadView('reports.boundary.statement', ['statement' => $statement])
            ->setPaper('a4')
            ->download($slug.'.pdf');
    }

    /**
     * Start a fresh assessment — a new year, or a changed business.
     */
    public function restart()
    {
        $company = $this->resolveCompany();

        if ($company) {
            BoundaryAssessment::where('company_id', $company->id)
                ->where('status', 'draft')
                ->delete();
        }

        return redirect()->route('boundary.index')->with('restart', true);
    }

    /**
     * Fold the chosen answers back into the stored question definitions, so the
     * transcript reads as a Q&A rather than a set of opaque option keys.
     *
     * @param  array<int, array>  $questions
     * @param  array<string, string>  $answers
     * @return array<int, array>
     */
    private function mergeAnswers(array $questions, array $answers): array
    {
        foreach ($questions as &$question) {
            $chosen = $answers[$question['key']] ?? null;
            $question['answer'] = $chosen;
            $question['answer_label'] = $chosen
                ? (collect($question['options'])->firstWhere('value', $chosen)['label'] ?? $chosen)
                : null;
        }

        return $questions;
    }

    /**
     * @return array{total:int, decided:int, included:int, pending:int}
     */
    private function progressFor(?BoundaryAssessment $assessment): array
    {
        if (! $assessment) {
            return ['total' => 0, 'decided' => 0, 'included' => 0, 'pending' => 0];
        }

        $items = $assessment->items()->get(['decision']);

        return [
            'total' => $items->count(),
            'decided' => $items->where('decision', '!=', 'pending')->count(),
            'included' => $items->where('decision', 'included')->count(),
            'pending' => $items->where('decision', 'pending')->count(),
        ];
    }

    /**
     * Next version number for this company and year.
     */
    private function nextVersion(Company $company, int $year): int
    {
        return (int) BoundaryAssessment::where('company_id', $company->id)
            ->where('reporting_year', $year)
            ->max('version') + 1;
    }

    /**
     * Resolve an assessment for the current company.
     *
     * Deliberately NOT using implicit route-model binding. SetCompanyConnection
     * is appended to the `web` middleware group, so it runs AFTER
     * SubstituteBindings — at binding time `current_company_id` is not yet
     * bound, and HasCompanyScope falls through to its deny-everything branch
     * for anyone who is not an account owner. Implicit binding therefore 404s on rows the user
     * genuinely owns.
     *
     * The global scope is dropped and the tenant check made explicit here, so
     * the ownership guarantee is unchanged — it is simply enforced where the
     * company context actually exists.
     */
    private function findAssessment(string|int $id, ?Company $company): ?BoundaryAssessment
    {
        if (! $company) {
            return null;
        }

        return BoundaryAssessment::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->find($id);
    }

    /**
     * Resolve a checklist item for the current company. See findAssessment for
     * why implicit binding is avoided.
     */
    private function findItem(string|int $id, ?Company $company): ?BoundaryItem
    {
        if (! $company) {
            return null;
        }

        return BoundaryItem::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->find($id);
    }

    private function resolveCompany(): ?Company
    {
        $user = Auth::user();

        if ($user && $user->company_id) {
            return $user->company;
        }

        return current_company();
    }

    /**
     * Industry enum values (must match the companies table) → friendly labels.
     * Mirrors OnboardingController so both wizards offer the same list.
     *
     * @return array<string, string>
     */
    private function industryOptions(): array
    {
        return [
            'manufacturing' => 'Manufacturing',
            'energy' => 'Energy & Utilities',
            'transportation' => 'Transportation & Logistics',
            'agriculture' => 'Agriculture',
            'construction' => 'Construction',
            'retail' => 'Retail & Wholesale',
            'healthcare' => 'Healthcare',
            'education' => 'Education',
            'technology' => 'Technology & IT',
            'finance' => 'Finance & Insurance',
            'hospitality' => 'Hospitality & Tourism',
            'mining' => 'Mining',
            'chemical' => 'Chemicals',
            'textile' => 'Textiles',
            'food_beverage' => 'Food & Beverage',
            'other' => 'Something else',
        ];
    }
}

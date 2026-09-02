<?php

namespace App\Http\Controllers;

use App\Services\AI\AskYourDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * "Ask Your Data" AI assistant. Answers are grounded in a company-scoped
 * snapshot computed by AskYourDataService — the tenant boundary is enforced by
 * HasCompanyScope inside the analytics queries, so nothing here needs to pass or
 * trust a company id from the request.
 */
class AskController extends Controller
{
    public function __construct(private AskYourDataService $assistant)
    {
        $this->middleware('auth');

        // Same gate as AnalyticsController, because it answers from the same
        // EmissionAnalyticsService snapshot. Without it this was a read-anything
        // channel: a user deliberately kept off the dashboard could still ask
        // the assistant for the totals it renders, in prose. Reusing
        // list-dashboard rather than minting an "ask" permission keeps the two
        // surfaces from drifting apart, and adds nothing to seed.
        $this->middleware('permission:list-dashboard');
    }

    public function index()
    {
        return view('assistant.index', [
            'aiEnabled' => $this->assistant->enabled(),
            'suggestions' => [
                'What were our total emissions and how are they split by scope?',
                'Which emission source is our biggest hotspot?',
                'How do this period’s emissions compare to the previous one?',
                'Which facility has the highest footprint?',
                'What is our emissions intensity per employee?',
            ],
        ]);
    }

    public function ask(Request $request)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:2000',
            'history' => 'sometimes|array',
            'history.*.role' => 'required_with:history|string|in:user,assistant',
            'history.*.content' => 'required_with:history|string',
            'date_range' => 'sometimes|string',
            'start_date' => 'sometimes|nullable|date',
            'end_date' => 'sometimes|nullable|date',
            'facility' => 'sometimes|nullable',
            'department' => 'sometimes|nullable',
            'scope' => 'sometimes|nullable',
        ]);

        $filters = [
            'date_range' => $validated['date_range'] ?? '12',
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'facility' => $validated['facility'] ?? null,
            'department' => $validated['department'] ?? null,
            'scope' => $validated['scope'] ?? null,
        ];

        try {
            $result = $this->assistant->answer(
                $validated['question'],
                $validated['history'] ?? [],
                $filters,
            );
        } catch (\Throwable $e) {
            Log::error('Ask Your Data failed', ['error' => $e->getMessage()]);

            return response()->json([
                'answer' => 'Something went wrong answering that. Please try rephrasing, or open the Analytics page directly.',
                'ai' => false,
            ], 200);
        }

        // Traceability: record that an AI query was run, and against which company.
        Log::info('Ask Your Data query', [
            'company_id' => current_company_id(),
            'user_id' => auth()->id(),
            'prompt_version' => AskYourDataService::PROMPT_VERSION,
            'ai' => $result['ai'],
        ]);

        return response()->json($result);
    }
}

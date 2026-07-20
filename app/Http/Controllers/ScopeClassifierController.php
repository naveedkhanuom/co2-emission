<?php

namespace App\Http\Controllers;

use App\Services\AI\ScopeClassificationService;
use Illuminate\Http\Request;

class ScopeClassifierController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the Scope Finder page — helps users who don't know which scope an activity belongs to.
     */
    public function index()
    {
        return view('scope_classifier.index', [
            'aiEnabled' => app(\App\Services\AI\ClaudeService::class)->enabled(),
        ]);
    }

    /**
     * AI-classify a free-text activity description into scope / Scope 3 category /
     * suggested source. Returns JSON consumed by the Scope Finder UI.
     */
    public function classify(Request $request, ScopeClassificationService $service)
    {
        $validated = $request->validate([
            'description' => 'required|string|min:3|max:500',
        ]);

        $result = $service->classify($validated['description']);

        // Map scope -> the entry page the user should jump to.
        $entryRoutes = [
            1 => route('scope1_entry.index'),
            2 => route('scope2_entry.index'),
            3 => route('scope3_entry.index'),
        ];
        $result['entry_url'] = $entryRoutes[$result['scope']] ?? null;

        return response()->json([
            'status' => true,
            'data'   => $result,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\DataHealthService;

/**
 * "Data Health" — a single at-a-glance page that answers three questions for a
 * non-expert user: what have I done, what's missing, and what needs my attention?
 *
 * The assessment itself lives in DataHealthService, because the dashboard shows
 * the same next steps and the two screens must not be able to reach different
 * conclusions. This page renders all of it; the dashboard shows the top few.
 */
class DataHealthController extends Controller
{
    public function __construct(private DataHealthService $health)
    {
        $this->middleware('auth');
    }

    public function index()
    {
        return view('data_health.index', $this->health->assess(current_company()));
    }
}

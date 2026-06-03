<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\EmissionAnalyticsService;
use App\Models\Facilities;
use App\Models\Department;
use App\Models\EmissionRecord;

class AnalyticsController extends Controller
{
    protected EmissionAnalyticsService $analyticsService;

    public function __construct(EmissionAnalyticsService $analyticsService)
    {
        $this->middleware('auth');
        $this->middleware('permission:list-dashboard', ['only' => ['index']]);
        $this->analyticsService = $analyticsService;
    }

    /**
     * Main analytics page.
     */
    public function index(Request $request)
    {
        $filters = $this->extractFilters($request);

        // Filter options for dropdowns (auto-scoped to company via HasCompanyScope)
        $facilities = Facilities::all();
        $departments = Department::all();
        $emissionCategories = EmissionRecord::where('status', 'active')
            ->distinct()
            ->pluck('emission_source')
            ->filter()
            ->values()
            ->toArray();

        // Initial data for each tab
        $breakdownData = $this->analyticsService->getBreakdownByDimension('scope', $filters);
        $intensityData = $this->analyticsService->getIntensityMetrics($filters);
        $yoyData = $this->analyticsService->getYearOverYear($filters, 'monthly');
        $waterfallData = $this->analyticsService->getWaterfallData($filters);
        $hotspotsData = $this->analyticsService->getHotspots($filters, 'source', 20);
        $treemapData = $this->analyticsService->getTreemapData($filters);

        return view('analytics.index', compact(
            'facilities',
            'departments',
            'emissionCategories',
            'breakdownData',
            'intensityData',
            'yoyData',
            'waterfallData',
            'hotspotsData',
            'treemapData',
            'filters'
        ));
    }

    /**
     * AJAX: Breakdown drill-down data.
     */
    public function breakdown(Request $request)
    {
        $filters = $this->extractFilters($request);
        $dimension = $request->get('dimension', 'scope');
        $parent = $request->get('parent');

        $data = $this->analyticsService->getBreakdownByDimension($dimension, $filters, $parent);

        return response()->json(['data' => $data]);
    }

    /**
     * AJAX: Intensity metrics data.
     */
    public function intensity(Request $request)
    {
        $filters = $this->extractFilters($request);
        $data = $this->analyticsService->getIntensityMetrics($filters);

        return response()->json(['data' => $data]);
    }

    /**
     * AJAX: Year-over-year comparison data.
     */
    public function yearOverYear(Request $request)
    {
        $filters = $this->extractFilters($request);
        $period = $request->get('period', 'monthly');

        $yoyData = $this->analyticsService->getYearOverYear($filters, $period);
        $waterfallData = $this->analyticsService->getWaterfallData($filters);

        return response()->json([
            'yoy' => $yoyData,
            'waterfall' => $waterfallData,
        ]);
    }

    /**
     * AJAX: Hotspot analysis data.
     */
    public function hotspots(Request $request)
    {
        $filters = $this->extractFilters($request);
        $groupBy = $request->get('group_by', 'source');
        $limit = min((int) $request->get('limit', 20), 50);

        $hotspotsData = $this->analyticsService->getHotspots($filters, $groupBy, $limit);
        $treemapData = $this->analyticsService->getTreemapData($filters);

        return response()->json([
            'hotspots' => $hotspotsData,
            'treemap' => $treemapData,
        ]);
    }

    /**
     * Extract filter parameters from request.
     */
    protected function extractFilters(Request $request): array
    {
        return [
            'date_range' => $request->get('date_range', '12'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
            'facility' => $request->get('facility', ''),
            'department' => $request->get('department', ''),
            'scope' => $request->get('scope', ''),
            'category' => $request->get('category', ''),
        ];
    }
}

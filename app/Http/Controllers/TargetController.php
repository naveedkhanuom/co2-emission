<?php

namespace App\Http\Controllers;

use App\Models\EmissionRecord;
use App\Models\Target;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Helpers\CompanyHelper;

class TargetController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:list-targets|create-target|edit-target|delete-target', ['only' => ['index', 'getData', 'show']]);
        $this->middleware('permission:create-target|edit-target', ['only' => ['storeOrUpdate']]);
        $this->middleware('permission:delete-target', ['only' => ['destroy']]);
    }
    
    /**
     * Get current company ID from context.
     */
    protected function getCurrentCompanyId()
    {
        return CompanyHelper::currentCompanyId();
    }

    public function index()
    {
        $query = Target::query()->latest();

        // Basic server-side filtering (optional, used by UI filters)
        if (request('status') && request('status') !== 'all') {
            $query->where('status', request('status'));
        }
        if (request('type') && request('type') !== 'all') {
            $query->where('type', request('type'));
        }
        if (request('scope') && request('scope') !== 'all') {
            $query->where('scope', request('scope'));
        }

        $targets = $query->get();

        // Compute KPI counts
        $activeTargets = $targets->where('status', '!=', 'completed')->count();
        $onTrackCount  = $targets->where('status', 'on-track')->count();
        $atRiskCount   = $targets->where('status', 'at-risk')->count();

        // Compute total reduction (simple estimation using emission_records current year vs baseline)
        $totalReduction = 0.0;
        $nowYear = (int) now()->format('Y');

        foreach ($targets as $t) {
            $current = $this->sumEmissionsForTargetScope($t, $nowYear);
            $baseline = $t->baseline_emissions;

            if ($baseline !== null && $current !== null) {
                $totalReduction += max(0, (float) $baseline - (float) $current);
            }

            // Attach computed UI fields (used in Blade cards)
            $t->current_emissions = $current;
            $t->progress_percent = $this->computeProgressPercent($t, $current);
        }

        // Status distribution for donut chart
        $statusDistribution = [
            'on-track'   => $onTrackCount,
            'at-risk'    => $atRiskCount,
            'off-track'  => $targets->where('status', 'off-track')->count(),
            'completed'  => $targets->where('status', 'completed')->count(),
        ];

        // Calculate progress chart data (actual emissions vs target trajectory)
        $progressChartData = $this->calculateProgressChartData($targets);
        
        // Calculate scenario chart data
        $scenarioChartData = $this->calculateScenarioChartData($targets);
        
        // Ensure chart data is always an array/object (not null)
        if (!is_array($progressChartData)) {
            $progressChartData = ['years' => [], 'actual' => [], 'target' => []];
        }
        if (!is_array($scenarioChartData)) {
            $scenarioChartData = ['years' => [], 'baseline' => [], 'accelerated' => [], 'innovative' => [], 'transformational' => [], 'sbtiTarget' => []];
        }

        return view('targets.index', compact(
            'targets',
            'activeTargets',
            'onTrackCount',
            'atRiskCount',
            'totalReduction',
            'statusDistribution',
            'progressChartData',
            'scenarioChartData'
        ));
    }

    public function getData(Request $request)
    {
        $query = Target::query()->latest();

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }
        if ($request->filled('scope') && $request->scope !== 'all') {
            $query->where('scope', $request->scope);
        }

        return DataTables::of($query)
            ->addColumn('actions', function ($row) {
                return '<div class="d-flex gap-1">
                    <button class="btn btn-sm btn-info" onclick="viewTargetReport(' . $row->id . ')"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-sm btn-warning" onclick="editTarget(' . $row->id . ')"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-danger" onclick="deleteTarget(' . $row->id . ')"><i class="fas fa-trash"></i></button>
                </div>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function show($id)
    {
        return response()->json(Target::findOrFail($id));
    }

    public function storeOrUpdate(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|integer|exists:targets,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:sbt,net-zero,carbon-neutral,regulatory,internal',
            'scope' => 'required|string|max:10',
            'target_year' => 'required|integer|min:2000|max:2100',
            'baseline_year' => 'nullable|integer|min:2000|max:2100',
            'baseline_emissions' => 'nullable|numeric|min:0',
            'target_emissions' => 'nullable|numeric|min:0',
            'reduction_percent' => 'nullable|numeric|min:0|max:100',
            'strategy' => 'nullable|string|max:255',
            'review_frequency' => 'required|in:monthly,quarterly,biannual,annual',
            'responsible_person' => 'nullable|string|max:255',
            'status' => 'required|in:on-track,at-risk,off-track,completed',
            'description' => 'nullable|string',
        ]);

        $companyId = $this->getCurrentCompanyId();
        
        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'No company selected. Please select a company first.'
            ], 400);
        }
        
        $data = $validated;
        unset($data['id']);
        $data['company_id'] = $companyId;
        $data['created_by'] = auth()->id();
        
        // If updating, verify target belongs to current company
        if (isset($validated['id']) && $validated['id']) {
            $existingTarget = Target::find($validated['id']);
            if ($existingTarget && $existingTarget->company_id != $companyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this target.'
                ], 403);
            }
        }

        $target = Target::updateOrCreate(
            ['id' => $validated['id'] ?? null],
            $data
        );

        return response()->json([
            'success' => true,
            'message' => ($validated['id'] ?? null) ? 'Target updated successfully' : 'Target created successfully',
            'data' => $target,
        ]);
    }

    public function destroy($id)
    {
        $target = Target::findOrFail($id);
        
        // Verify target belongs to current company
        $companyId = $this->getCurrentCompanyId();
        if ($companyId && $target->company_id != $companyId) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this target.'
            ], 403);
        }
        
        $target->delete();

        return response()->json([
            'success' => true,
            'message' => 'Target deleted successfully',
        ]);
    }

    private function sumEmissionsForTargetScope(Target $t, int $year): float
    {
        $scopes = match ($t->scope) {
            '1' => [1],
            '2' => [2],
            '3' => [3],
            '1-2' => [1, 2],
            'all', 'all-scopes' => [1, 2, 3],
            default => is_numeric($t->scope) ? [(int) $t->scope] : [1, 2, 3],
        };

        // Automatically scoped to current company via HasCompanyScope trait
        return (float) EmissionRecord::where('status', 'active')
            ->whereYear('entry_date', $year)
            ->whereIn('scope', $scopes)
            ->sum('co2e_value');
    }

    private function computeProgressPercent(Target $t, float $currentEmissions): float
    {
        // Uses baseline_emissions and target_emissions as the main reference.
        $baseline = $t->baseline_emissions;
        $target = $t->target_emissions;

        if ($baseline === null || $target === null) {
            return 0.0;
        }

        $baseline = (float) $baseline;
        $target = (float) $target;

        $denom = ($baseline - $target);
        if ($denom == 0.0) {
            return 0.0;
        }

        $progress = ($baseline - $currentEmissions) / $denom; // 0..1
        $progress = max(0.0, min(1.0, $progress));

        return round($progress * 100, 1);
    }

    /**
     * Calculate progress chart data: actual emissions vs target trajectory
     */
    private function calculateProgressChartData($targets)
    {
        $currentYear = (int) now()->format('Y');
        $startYear = max(2020, $currentYear - 5); // Show last 5 years + future
        $endYear = min(2050, $currentYear + 10); // Show up to 10 years ahead
        
        $years = [];
        $actualEmissions = [];
        $targetTrajectory = [];
        
        // Get the primary target (first active target, or first target overall)
        $primaryTarget = $targets->where('status', '!=', 'completed')->first() 
                      ?? $targets->first();
        
        if (!$primaryTarget || !$primaryTarget->baseline_emissions || !$primaryTarget->target_emissions) {
            // Return empty data if no valid target
            for ($year = $startYear; $year <= $endYear; $year++) {
                $years[] = (string) $year;
                $actualEmissions[] = 0;
                $targetTrajectory[] = 0;
            }
            return [
                'years' => $years,
                'actual' => $actualEmissions,
                'target' => $targetTrajectory,
            ];
        }
        
        $baseline = (float) $primaryTarget->baseline_emissions;
        $targetEmissions = (float) $primaryTarget->target_emissions;
        $baselineYear = $primaryTarget->baseline_year ?? $startYear;
        $targetYear = $primaryTarget->target_year;
        
        // Calculate linear trajectory from baseline to target
        $yearsDiff = $targetYear - $baselineYear;
        $emissionsDiff = $baseline - $targetEmissions;
        $annualReduction = $yearsDiff > 0 ? ($emissionsDiff / $yearsDiff) : 0;
        
        for ($year = $startYear; $year <= $endYear; $year++) {
            $years[] = (string) $year;
            
            // Calculate actual emissions for this year
            $actual = $this->sumEmissionsForTargetScope($primaryTarget, $year);
            $actualEmissions[] = round($actual, 2);
            
            // Calculate target trajectory
            if ($year <= $baselineYear) {
                $targetTrajectory[] = round($baseline, 2);
            } elseif ($year >= $targetYear) {
                $targetTrajectory[] = round($targetEmissions, 2);
            } else {
                // Linear interpolation
                $progress = ($year - $baselineYear) / $yearsDiff;
                $trajectoryValue = $baseline - ($emissionsDiff * $progress);
                $targetTrajectory[] = round(max($targetEmissions, $trajectoryValue), 2);
            }
        }
        
        return [
            'years' => $years,
            'actual' => $actualEmissions,
            'target' => $targetTrajectory,
        ];
    }

    /**
     * Calculate scenario chart data: baseline, accelerated, innovative, transformational
     */
    private function calculateScenarioChartData($targets)
    {
        $currentYear = (int) now()->format('Y');
        $startYear = $currentYear;
        $endYear = min(2050, $currentYear + 10);
        
        $years = [];
        $baseline = [];
        $accelerated = [];
        $innovative = [];
        $transformational = [];
        $sbtiTarget = [];
        
        // Get primary target for calculations
        $primaryTarget = $targets->where('status', '!=', 'completed')->first() 
                      ?? $targets->first();
        
        if (!$primaryTarget || !$primaryTarget->baseline_emissions || !$primaryTarget->target_emissions) {
            // Return empty data
            for ($year = $startYear; $year <= $endYear; $year++) {
                $years[] = (string) $year;
                $baseline[] = 0;
                $accelerated[] = 0;
                $innovative[] = 0;
                $transformational[] = 0;
                $sbtiTarget[] = 0;
            }
            return [
                'years' => $years,
                'baseline' => $baseline,
                'accelerated' => $accelerated,
                'innovative' => $innovative,
                'transformational' => $transformational,
                'sbtiTarget' => $sbtiTarget,
            ];
        }
        
        $baselineEmissions = (float) $primaryTarget->baseline_emissions;
        $targetEmissions = (float) $primaryTarget->target_emissions;
        $baselineYear = $primaryTarget->baseline_year ?? $startYear;
        $targetYear = $primaryTarget->target_year;
        
        // Get current actual emissions
        $currentActual = $this->sumEmissionsForTargetScope($primaryTarget, $currentYear);
        if ($currentActual == 0) {
            $currentActual = $baselineEmissions; // Fallback to baseline
        }
        
        $yearsDiff = $targetYear - $currentYear;
        $emissionsDiff = $currentActual - $targetEmissions;
        
        for ($year = $startYear; $year <= $endYear; $year++) {
            $years[] = (string) $year;
            
            $yearProgress = $yearsDiff > 0 ? (($year - $currentYear) / $yearsDiff) : 0;
            $yearProgress = max(0, min(1, $yearProgress));
            
            // Baseline: Current trajectory (moderate reduction)
            $baselineReduction = $emissionsDiff * $yearProgress * 0.3; // 30% of needed reduction
            $baseline[] = round(max($targetEmissions, $currentActual - $baselineReduction), 2);
            
            // Accelerated: Fast-track (50% reduction rate)
            $acceleratedReduction = $emissionsDiff * $yearProgress * 0.5;
            $accelerated[] = round(max($targetEmissions, $currentActual - $acceleratedReduction), 2);
            
            // Innovative: New technologies (70% reduction rate)
            $innovativeReduction = $emissionsDiff * $yearProgress * 0.7;
            $innovative[] = round(max($targetEmissions, $currentActual - $innovativeReduction), 2);
            
            // Transformational: Business model change (90% reduction rate)
            $transformationalReduction = $emissionsDiff * $yearProgress * 0.9;
            $transformational[] = round(max($targetEmissions, $currentActual - $transformationalReduction), 2);
            
            // SBTi Target: Linear path to target
            $sbtiReduction = $emissionsDiff * $yearProgress;
            $sbtiTarget[] = round(max($targetEmissions, $currentActual - $sbtiReduction), 2);
        }
        
        return [
            'years' => $years,
            'baseline' => $baseline,
            'accelerated' => $accelerated,
            'innovative' => $innovative,
            'transformational' => $transformational,
            'sbtiTarget' => $sbtiTarget,
        ];
    }
}


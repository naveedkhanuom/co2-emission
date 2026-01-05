<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmissionRecord;
use App\Models\EmissionSource;
use App\Models\Facilities;
use App\Models\Department;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request, $companyId = 1)
    {
        // Get filter parameters
        $dateRange = $request->get('date_range', '12');
        $facilityFilter = $request->get('facility', '');
        $departmentFilter = $request->get('department', '');
        $categoryFilter = $request->get('category', '');
        
        // Build base query with filters
        $baseQuery = function() use ($dateRange, $facilityFilter, $departmentFilter, $categoryFilter) {
            $query = EmissionRecord::where('status', 'active');
            
            // Apply date range filter
            if ($dateRange === 'ytd') {
                $query->whereYear('entry_date', Carbon::now()->year);
            } elseif ($dateRange === '3') {
                $query->where('entry_date', '>=', Carbon::now()->subMonths(3)->startOfMonth());
            } elseif ($dateRange === 'custom') {
                // Custom range would need additional parameters
                // For now, default to last 12 months
                $query->where('entry_date', '>=', Carbon::now()->subMonths(12)->startOfMonth());
            } else {
                // Default: last 12 months
                $query->where('entry_date', '>=', Carbon::now()->subMonths(12)->startOfMonth());
            }
            
            // Apply facility filter (note: facility is stored as string name, not ID)
            if (!empty($facilityFilter)) {
                $facility = Facilities::find($facilityFilter);
                if ($facility) {
                    $query->where('facility', $facility->name);
                }
            }
            
            // Apply department filter (note: department is stored as string name, not ID)
            if (!empty($departmentFilter)) {
                $department = Department::find($departmentFilter);
                if ($department) {
                    $query->where('department', $department->name);
                }
            }
            
            // Apply category filter
            if (!empty($categoryFilter)) {
                $query->where('emission_source', $categoryFilter);
            }
            
            return $query;
        };
        
        // Calculate total emissions with filters
        $totalEmissions = $baseQuery()->sum('co2e_value');
        
        // Calculate emissions by scope with filters
        $scope1Emissions = $baseQuery()->where('scope', 1)->sum('co2e_value');
        $scope2Emissions = $baseQuery()->where('scope', 2)->sum('co2e_value');
        $scope3Emissions = $baseQuery()->where('scope', 3)->sum('co2e_value');
        
        // Calculate current month vs previous month for percentage change
        $currentMonthStart = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now()->endOfMonth();
        $previousMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $previousMonthEnd = Carbon::now()->subMonth()->endOfMonth();
        
        // Current month totals (with filters except date)
        $currentMonthQuery = EmissionRecord::where('status', 'active')
            ->whereBetween('entry_date', [$currentMonthStart, $currentMonthEnd]);
        if (!empty($facilityFilter)) {
            $facility = Facilities::find($facilityFilter);
            if ($facility) {
                $currentMonthQuery->where('facility', $facility->name);
            }
        }
        if (!empty($departmentFilter)) {
            $department = Department::find($departmentFilter);
            if ($department) {
                $currentMonthQuery->where('department', $department->name);
            }
        }
        if (!empty($categoryFilter)) {
            $currentMonthQuery->where('emission_source', $categoryFilter);
        }
        $currentMonthTotal = $currentMonthQuery->sum('co2e_value');
        
        // Previous month totals (with filters except date)
        $previousMonthQuery = EmissionRecord::where('status', 'active')
            ->whereBetween('entry_date', [$previousMonthStart, $previousMonthEnd]);
        if (!empty($facilityFilter)) {
            $facility = Facilities::find($facilityFilter);
            if ($facility) {
                $previousMonthQuery->where('facility', $facility->name);
            }
        }
        if (!empty($departmentFilter)) {
            $department = Department::find($departmentFilter);
            if ($department) {
                $previousMonthQuery->where('department', $department->name);
            }
        }
        if (!empty($categoryFilter)) {
            $previousMonthQuery->where('emission_source', $categoryFilter);
        }
        $previousMonthTotal = $previousMonthQuery->sum('co2e_value');
        
        // Calculate percentage change (comparing current month to previous month)
        $percentageChange = $previousMonthTotal > 0 
            ? (($currentMonthTotal - $previousMonthTotal) / $previousMonthTotal) * 100 
            : ($currentMonthTotal > 0 ? 100 : 0);
        
        // Previous month scope totals (with filters)
        $prevScope1 = EmissionRecord::where('status', 'active')
            ->where('scope', 1)
            ->whereBetween('entry_date', [$previousMonthStart, $previousMonthEnd]);
        if (!empty($facilityFilter)) {
            $facility = Facilities::find($facilityFilter);
            if ($facility) {
                $prevScope1->where('facility', $facility->name);
            }
        }
        if (!empty($departmentFilter)) {
            $department = Department::find($departmentFilter);
            if ($department) {
                $prevScope1->where('department', $department->name);
            }
        }
        if (!empty($categoryFilter)) {
            $prevScope1->where('emission_source', $categoryFilter);
        }
        $prevScope1 = $prevScope1->sum('co2e_value');
        
        $prevScope2 = EmissionRecord::where('status', 'active')
            ->where('scope', 2)
            ->whereBetween('entry_date', [$previousMonthStart, $previousMonthEnd]);
        if (!empty($facilityFilter)) {
            $facility = Facilities::find($facilityFilter);
            if ($facility) {
                $prevScope2->where('facility', $facility->name);
            }
        }
        if (!empty($departmentFilter)) {
            $department = Department::find($departmentFilter);
            if ($department) {
                $prevScope2->where('department', $department->name);
            }
        }
        if (!empty($categoryFilter)) {
            $prevScope2->where('emission_source', $categoryFilter);
        }
        $prevScope2 = $prevScope2->sum('co2e_value');
        
        $prevScope3 = EmissionRecord::where('status', 'active')
            ->where('scope', 3)
            ->whereBetween('entry_date', [$previousMonthStart, $previousMonthEnd]);
        if (!empty($facilityFilter)) {
            $facility = Facilities::find($facilityFilter);
            if ($facility) {
                $prevScope3->where('facility', $facility->name);
            }
        }
        if (!empty($departmentFilter)) {
            $department = Department::find($departmentFilter);
            if ($department) {
                $prevScope3->where('department', $department->name);
            }
        }
        if (!empty($categoryFilter)) {
            $prevScope3->where('emission_source', $categoryFilter);
        }
        $prevScope3 = $prevScope3->sum('co2e_value');
        
        // Current month scope totals (with filters)
        $currentScope1 = EmissionRecord::where('status', 'active')
            ->where('scope', 1)
            ->whereBetween('entry_date', [$currentMonthStart, $currentMonthEnd]);
        if (!empty($facilityFilter)) {
            $facility = Facilities::find($facilityFilter);
            if ($facility) {
                $currentScope1->where('facility', $facility->name);
            }
        }
        if (!empty($departmentFilter)) {
            $department = Department::find($departmentFilter);
            if ($department) {
                $currentScope1->where('department', $department->name);
            }
        }
        if (!empty($categoryFilter)) {
            $currentScope1->where('emission_source', $categoryFilter);
        }
        $currentScope1 = $currentScope1->sum('co2e_value');
        
        $currentScope2 = EmissionRecord::where('status', 'active')
            ->where('scope', 2)
            ->whereBetween('entry_date', [$currentMonthStart, $currentMonthEnd]);
        if (!empty($facilityFilter)) {
            $facility = Facilities::find($facilityFilter);
            if ($facility) {
                $currentScope2->where('facility', $facility->name);
            }
        }
        if (!empty($departmentFilter)) {
            $department = Department::find($departmentFilter);
            if ($department) {
                $currentScope2->where('department', $department->name);
            }
        }
        if (!empty($categoryFilter)) {
            $currentScope2->where('emission_source', $categoryFilter);
        }
        $currentScope2 = $currentScope2->sum('co2e_value');
        
        $currentScope3 = EmissionRecord::where('status', 'active')
            ->where('scope', 3)
            ->whereBetween('entry_date', [$currentMonthStart, $currentMonthEnd]);
        if (!empty($facilityFilter)) {
            $facility = Facilities::find($facilityFilter);
            if ($facility) {
                $currentScope3->where('facility', $facility->name);
            }
        }
        if (!empty($departmentFilter)) {
            $department = Department::find($departmentFilter);
            if ($department) {
                $currentScope3->where('department', $department->name);
            }
        }
        if (!empty($categoryFilter)) {
            $currentScope3->where('emission_source', $categoryFilter);
        }
        $currentScope3 = $currentScope3->sum('co2e_value');
        
        // Calculate scope percentage changes (comparing current month to previous month)
        $scope1Change = $prevScope1 > 0 ? (($currentScope1 - $prevScope1) / $prevScope1) * 100 : ($currentScope1 > 0 ? 100 : 0);
        $scope2Change = $prevScope2 > 0 ? (($currentScope2 - $prevScope2) / $prevScope2) * 100 : ($currentScope2 > 0 ? 100 : 0);
        $scope3Change = $prevScope3 > 0 ? (($currentScope3 - $prevScope3) / $prevScope3) * 100 : ($currentScope3 > 0 ? 100 : 0);
        
        // Monthly trend data (last 12 months)
        $monthlyTrend = [];
        $monthlyTarget = [];
        $months = [];
        
        // Calculate average monthly emission for target calculation
        $avgMonthlyEmission = $totalEmissions > 0 ? ($totalEmissions / 12) : 0;
        
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();
            $months[] = $date->format('M');
            
            // For monthly trend, always show last 12 months but apply other filters
            $monthQuery = EmissionRecord::where('status', 'active')
                ->whereBetween('entry_date', [$monthStart, $monthEnd]);
            
            // Apply filters (except date range - monthly chart always shows last 12 months)
            if (!empty($facilityFilter)) {
                $facility = Facilities::find($facilityFilter);
                if ($facility) {
                    $monthQuery->where('facility', $facility->name);
                }
            }
            if (!empty($departmentFilter)) {
                $department = Department::find($departmentFilter);
                if ($department) {
                    $monthQuery->where('department', $department->name);
                }
            }
            if (!empty($categoryFilter)) {
                $monthQuery->where('emission_source', $categoryFilter);
            }
            
            $monthTotal = $monthQuery->sum('co2e_value');
            
            $monthlyTrend[] = round((float) $monthTotal, 2);
            // Target: 5% reduction goal (example calculation)
            $reductionPercent = ($i * 0.005); // Gradually reduce target by 0.5% per month
            $monthlyTarget[] = round($avgMonthlyEmission * (1 - $reductionPercent), 2);
        }
        
        // Ensure arrays are not empty for charts
        if (empty($monthlyTrend)) {
            $monthlyTrend = array_fill(0, 12, 0);
            $monthlyTarget = array_fill(0, 12, 0);
        }
        
        // Emissions by source (top 10) with filters
        $sourceQuery = $baseQuery()
            ->select('emission_source', DB::raw('SUM(co2e_value) as total'))
            ->groupBy('emission_source')
            ->orderByDesc('total')
            ->limit(10);
        $emissionsBySource = $sourceQuery->get();
        
        $sourceNames = $emissionsBySource->pluck('emission_source')->toArray();
        $sourceValues = $emissionsBySource->pluck('total')->map(function($value) {
            return (float) $value;
        })->toArray();
        
        // Ensure we have at least empty arrays if no data
        if (empty($sourceNames)) {
            $sourceNames = [];
            $sourceValues = [];
        }
        
        // Recent emission records (latest 8) with filters
        $recentRecords = $baseQuery()
            ->orderBy('entry_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();
        
        // Total record count with filters
        $totalRecords = $baseQuery()->count();
        
        // Get facilities and departments for filters
        $facilities = Facilities::all();
        $departments = Department::all();
        
        // Get unique emission sources for filter
        $emissionCategories = EmissionRecord::where('status', 'active')
            ->distinct()
            ->pluck('emission_source')
            ->toArray();
        
        return view('home', compact(
            'totalEmissions',
            'scope1Emissions',
            'scope2Emissions',
            'scope3Emissions',
            'percentageChange',
            'scope1Change',
            'scope2Change',
            'scope3Change',
            'monthlyTrend',
            'monthlyTarget',
            'months',
            'sourceNames',
            'sourceValues',
            'recentRecords',
            'totalRecords',
            'facilities',
            'departments',
            'emissionCategories',
            'dateRange',
            'facilityFilter',
            'departmentFilter',
            'categoryFilter'
        ));
    }
}

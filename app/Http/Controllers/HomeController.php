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
        $this->middleware('permission:list-dashboard', ['only' => ['index']]);
    }

    public function index(Request $request, $companyId = 1)
    {
        // Get filter parameters
        $dateRange = $request->get('date_range', '12');
        $facilityFilter = $request->get('facility', '');
        $departmentFilter = $request->get('department', '');
        $categoryFilter = $request->get('category', '');
        $customStartDate = $request->get('start_date');
        $customEndDate = $request->get('end_date');

        $parsedCustomStart = null;
        $parsedCustomEnd = null;
        if (!empty($customStartDate)) {
            try {
                $parsedCustomStart = Carbon::parse($customStartDate)->startOfDay();
            } catch (\Throwable $e) {
                $parsedCustomStart = null;
            }
        }
        if (!empty($customEndDate)) {
            try {
                $parsedCustomEnd = Carbon::parse($customEndDate)->endOfDay();
            } catch (\Throwable $e) {
                $parsedCustomEnd = null;
            }
        }
        
        // Build base query with filters
        // Note: EmissionRecord uses HasCompanyScope trait, so it's automatically scoped to current company
        $baseQuery = function() use ($dateRange, $facilityFilter, $departmentFilter, $categoryFilter, $parsedCustomStart, $parsedCustomEnd) {
            $query = EmissionRecord::where('status', 'active');
            
            // Apply date range filter
            if ($dateRange === 'ytd') {
                $query->whereYear('entry_date', Carbon::now()->year);
            } elseif ($dateRange === '3') {
                $query->where('entry_date', '>=', Carbon::now()->subMonths(3)->startOfMonth());
            } elseif ($dateRange === 'custom') {
                if ($parsedCustomStart && $parsedCustomEnd) {
                    // If user swapped dates, normalize them.
                    if ($parsedCustomEnd->lt($parsedCustomStart)) {
                        [$parsedCustomStart, $parsedCustomEnd] = [$parsedCustomEnd->copy()->startOfDay(), $parsedCustomStart->copy()->endOfDay()];
                    }
                    $query->whereBetween('entry_date', [$parsedCustomStart, $parsedCustomEnd]);
                } else {
                    // If custom dates not provided/invalid, fallback to last 12 months
                    $query->where('entry_date', '>=', Carbon::now()->subMonths(12)->startOfMonth());
                }
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
        // Automatically scoped to current company via HasCompanyScope trait
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
        // Automatically scoped to current company via HasCompanyScope trait
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
        // Automatically scoped to current company via HasCompanyScope trait
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
        
        // Automatically scoped to current company via HasCompanyScope trait
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
        
        // Automatically scoped to current company via HasCompanyScope trait
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
        // Automatically scoped to current company via HasCompanyScope trait
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
        
        // Automatically scoped to current company via HasCompanyScope trait
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
        
        // Automatically scoped to current company via HasCompanyScope trait
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
        
        // Resolve effective date range for monthly trend (same logic as baseQuery)
        $trendStart = Carbon::now()->subMonths(12)->startOfMonth();
        $trendEnd = Carbon::now()->endOfMonth();
        if ($dateRange === 'ytd') {
            $trendStart = Carbon::now()->startOfYear();
            $trendEnd = Carbon::now()->endOfMonth();
        } elseif ($dateRange === '3') {
            $trendStart = Carbon::now()->subMonths(3)->startOfMonth();
        } elseif ($dateRange === 'custom' && $parsedCustomStart && $parsedCustomEnd) {
            $trendStart = $parsedCustomStart->copy()->startOfMonth();
            $trendEnd = $parsedCustomEnd->copy()->endOfMonth();
            if ($trendEnd->lt($trendStart)) {
                $trendEnd = $trendStart->copy()->endOfMonth();
            }
        }

        // Monthly trend: one point per month within the effective range
        $monthlyTrend = [];
        $monthlyTarget = [];
        $months = [];
        $cursor = $trendStart->copy();
        $monthIndex = 0;
        $numMonths = max(1, (int) $cursor->diffInMonths($trendEnd) + 1);
        $avgMonthlyEmission = $numMonths > 0 && $totalEmissions > 0 ? ($totalEmissions / $numMonths) : 0;

        while ($cursor->lte($trendEnd)) {
            $monthStart = $cursor->copy()->startOfMonth();
            $monthEnd = $cursor->copy()->endOfMonth();
            $months[] = $cursor->format('M Y');

            $monthQuery = EmissionRecord::where('status', 'active')
                ->whereBetween('entry_date', [$monthStart, $monthEnd]);
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
            $reductionPercent = $monthIndex * 0.005;
            $monthlyTarget[] = round($avgMonthlyEmission * (1 - $reductionPercent), 2);

            $cursor->addMonth();
            $monthIndex++;
        }

        if (empty($monthlyTrend)) {
            $monthlyTrend = [0];
            $monthlyTarget = [0];
            $months = [$trendStart->format('M Y')];
        }
        
        // Emissions by source (top 10) with filters
        // Automatically scoped to current company via HasCompanyScope trait
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
        
        // Recent emission records with server-side pagination (respects filters)
        // Automatically scoped to current company via HasCompanyScope trait
        $recentRecords = $baseQuery()
            ->orderBy('entry_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();
        
        // Get facilities and departments for filters (automatically scoped to current company via HasCompanyScope)
        $facilities = Facilities::all();
        $departments = Department::all();
        
        // Get unique emission sources for filter
        // Automatically scoped to current company via HasCompanyScope trait
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
            'facilities',
            'departments',
            'emissionCategories',
            'dateRange',
            'facilityFilter',
            'departmentFilter',
            'categoryFilter',
            'customStartDate',
            'customEndDate'
        ));
    }
}

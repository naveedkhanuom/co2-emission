<?php

namespace App\Http\Controllers;

use App\Models\Scope3Category;
use App\Models\EmissionRecord;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\CompanyHelper;

class Scope3Controller extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:list-scope3|create-scope3|edit-scope3|delete-scope3', ['only' => ['index', 'calculator', 'getScope3Summary', 'getCategories', 'getCategoryDetails']]);
    }

    /**
     * Scope 3 Calculator — all 15 categories with data entry. Pass data for "Save to emission records".
     */
    public function calculator()
    {
        $companyId = CompanyHelper::currentCompanyId();
        if (!$companyId) {
            return redirect()->route('home')->with('error', 'Please select a company first.');
        }

        // Map calculator category number (1–15) to scope3_category_id and emission source name for saving to emission_records
        $scope3Categories = Scope3Category::active()->orderBy('sort_order')->get();
        $categoryMap = [];
        $emissionSourceNames = [
            1 => 'Scope 3 - 1. Purchased Goods & Services',
            2 => 'Scope 3 - 2. Capital Goods',
            3 => 'Scope 3 - 3. Fuel & Energy Related Activities',
            4 => 'Scope 3 - 4. Upstream Transportation & Distribution',
            5 => 'Scope 3 - 5. Waste Generated in Operations',
            6 => 'Scope 3 - 6. Business Travel',
            7 => 'Scope 3 - 7. Employee Commuting',
            8 => 'Scope 3 - 8. Upstream Leased Assets',
            9 => 'Scope 3 - 9. Downstream Transportation & Distribution',
            10 => 'Scope 3 - 10. Processing of Sold Products',
            11 => 'Scope 3 - 11. Use of Sold Products',
            12 => 'Scope 3 - 12. End-of-Life Treatment of Sold Products',
            13 => 'Scope 3 - 13. Downstream Leased Assets',
            14 => 'Scope 3 - 14. Franchises',
            15 => 'Scope 3 - 15. Investments',
        ];
        foreach ($scope3Categories as $cat) {
            $categoryMap[$cat->sort_order] = [
                'scope3_category_id' => $cat->id,
                'emission_source_name' => $emissionSourceNames[$cat->sort_order] ?? ('Scope 3 - ' . $cat->sort_order . '. ' . $cat->name),
            ];
        }

        $facilities = function_exists('facilities') ? facilities() : collect();
        $sites = function_exists('sites') ? sites() : collect();

        return view('scope3.calculator', [
            'storeUrl' => route('emission-records.store'),
            'categoryMap' => $categoryMap,
            'facilities' => $facilities,
            'sites' => $sites,
        ]);
    }

    /**
     * Improved Scope 3 Calculator with auto-populated emission factors.
     */
    public function calculatorImproved()
    {
        $companyId = CompanyHelper::currentCompanyId();
        if (!$companyId) {
            return redirect()->route('home')->with('error', 'Please select a company first.');
        }

        // Get category mapping
        $scope3Categories = Scope3Category::active()->orderBy('sort_order')->get();
        $categoryMap = [];
        $emissionSourceNames = [
            1 => 'Scope 3 - 1. Purchased Goods & Services',
            2 => 'Scope 3 - 2. Capital Goods',
            3 => 'Scope 3 - 3. Fuel & Energy Related Activities',
            4 => 'Scope 3 - 4. Upstream Transportation & Distribution',
            5 => 'Scope 3 - 5. Waste Generated in Operations',
            6 => 'Scope 3 - 6. Business Travel',
            7 => 'Scope 3 - 7. Employee Commuting',
            8 => 'Scope 3 - 8. Upstream Leased Assets',
            9 => 'Scope 3 - 9. Downstream Transportation & Distribution',
            10 => 'Scope 3 - 10. Processing of Sold Products',
            11 => 'Scope 3 - 11. Use of Sold Products',
            12 => 'Scope 3 - 12. End-of-Life Treatment of Sold Products',
            13 => 'Scope 3 - 13. Downstream Leased Assets',
            14 => 'Scope 3 - 14. Franchises',
            15 => 'Scope 3 - 15. Investments',
        ];
        foreach ($scope3Categories as $cat) {
            $categoryMap[$cat->sort_order] = [
                'scope3_category_id' => $cat->id,
                'emission_source_name' => $emissionSourceNames[$cat->sort_order] ?? ('Scope 3 - ' . $cat->sort_order . '. ' . $cat->name),
            ];
        }

        $facilities = function_exists('facilities') ? facilities() : collect();
        $sites = function_exists('sites') ? sites() : collect();

        // Get EIO factors for spend-based calculations
        $eioFactors = \App\Models\EioFactor::where('is_active', true)
            ->select('sector_code', 'sector_name', 'emission_factor', 'factor_unit', 'currency', 'data_source')
            ->get();

        // Get relevant emission factors for activity-based calculations
        $emissionFactors = \App\Models\EmissionFactor::with('emissionSource')
            ->whereHas('emissionSource', function($query) {
                $query->where('name', 'like', 'Scope 3%');
            })
            ->select('id', 'emission_source_id', 'factor_value', 'unit', 'region')
            ->get();

        return view('scope3.calculator-improved', [
            'storeUrl' => route('emission-records.store'),
            'categoryMap' => $categoryMap,
            'facilities' => $facilities,
            'sites' => $sites,
            'eioFactors' => $eioFactors,
            'emissionFactors' => $emissionFactors,
        ]);
    }

    /**
     * Display Scope 3 overview dashboard.
     */
    public function index()
    {
        $companyId = CompanyHelper::currentCompanyId();
        
        if (!$companyId) {
            return redirect()->route('home')->with('error', 'Please select a company first.');
        }

        $categoriesCollection = Scope3Category::active()
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category_type');

        // Ensure upstream and downstream keys exist as collections
        $categories = [
            'upstream' => $categoriesCollection->get('upstream', collect([])),
            'downstream' => $categoriesCollection->get('downstream', collect([]))
        ];

        // Get emissions by category for current year
        $currentYear = now()->year;

        $emissionsByCategory = EmissionRecord::where('company_id', $companyId)
            ->where('scope', 3)
            ->whereYear('entry_date', $currentYear)
            ->select('scope3_category_id', DB::raw('SUM(co2e_value) as total'))
            ->groupBy('scope3_category_id')
            ->pluck('total', 'scope3_category_id');

        // Get total Scope 3 emissions
        $totalScope3 = EmissionRecord::where('company_id', $companyId)
            ->where('scope', 3)
            ->whereYear('entry_date', $currentYear)
            ->sum('co2e_value');

        // Get data quality breakdown
        $dataQualityBreakdown = EmissionRecord::where('company_id', $companyId)
            ->where('scope', 3)
            ->whereYear('entry_date', $currentYear)
            ->select('data_quality', DB::raw('SUM(co2e_value) as total'))
            ->groupBy('data_quality')
            ->get();

        // Get calculation method breakdown
        $methodBreakdown = EmissionRecord::where('company_id', $companyId)
            ->where('scope', 3)
            ->whereYear('entry_date', $currentYear)
            ->select('calculation_method', DB::raw('SUM(co2e_value) as total'))
            ->groupBy('calculation_method')
            ->get();

        return view('scope3.index', compact(
            'categories',
            'emissionsByCategory',
            'totalScope3',
            'dataQualityBreakdown',
            'methodBreakdown'
        ));
    }

    /**
     * Get detailed emissions for a specific category.
     */
    public function getCategoryDetails($categoryId)
    {
        $category = Scope3Category::findOrFail($categoryId);
        $companyId = CompanyHelper::currentCompanyId();

        if (!$companyId) {
            return response()->json(['error' => 'No company selected'], 400);
        }

        $emissions = EmissionRecord::where('company_id', $companyId)
            ->where('scope3_category_id', $categoryId)
            ->with(['supplier', 'scope3Category', 'site'])
            ->latest('entry_date')
            ->get();

        $total = $emissions->sum('co2e_value');
        $primaryData = $emissions->where('data_quality', 'primary')->sum('co2e_value');
        $primaryPercentage = $total > 0 ? ($primaryData / $total) * 100 : 0;

        // Get emissions by supplier
        $bySupplier = EmissionRecord::where('company_id', $companyId)
            ->where('scope3_category_id', $categoryId)
            ->whereNotNull('supplier_id')
            ->select('supplier_id', DB::raw('SUM(co2e_value) as total'))
            ->groupBy('supplier_id')
            ->with('supplier')
            ->get();

        return response()->json([
            'category' => $category,
            'emissions' => $emissions,
            'total' => $total,
            'primary_data_percentage' => round($primaryPercentage, 2),
            'by_supplier' => $bySupplier,
        ]);
    }

    /**
     * Get Scope 3 summary statistics.
     */
    public function getScope3Summary(Request $request)
    {
        $companyId = CompanyHelper::currentCompanyId();
        
        if (!$companyId) {
            return response()->json(['error' => 'No company selected'], 400);
        }

        $year = $request->input('year', now()->year);

        // Total Scope 3 emissions
        $totalScope3 = EmissionRecord::where('company_id', $companyId)
            ->where('scope', 3)
            ->whereYear('entry_date', $year)
            ->sum('co2e_value');

        // By category
        $byCategory = EmissionRecord::where('company_id', $companyId)
            ->where('scope', 3)
            ->whereYear('entry_date', $year)
            ->select('scope3_category_id', DB::raw('SUM(co2e_value) as total'))
            ->groupBy('scope3_category_id')
            ->with('scope3Category')
            ->get()
            ->map(function ($item) {
                return [
                    'category_code' => $item->scope3Category->code ?? 'N/A',
                    'category_name' => $item->scope3Category->name ?? 'Unknown',
                    'total' => (float) $item->total,
                ];
            });

        // By data quality
        $byDataQuality = EmissionRecord::where('company_id', $companyId)
            ->where('scope', 3)
            ->whereYear('entry_date', $year)
            ->select('data_quality', DB::raw('SUM(co2e_value) as total'))
            ->groupBy('data_quality')
            ->get()
            ->map(function ($item) {
                return [
                    'quality' => $item->data_quality,
                    'total' => (float) $item->total,
                ];
            });

        // By calculation method
        $byMethod = EmissionRecord::where('company_id', $companyId)
            ->where('scope', 3)
            ->whereYear('entry_date', $year)
            ->select('calculation_method', DB::raw('SUM(co2e_value) as total'))
            ->groupBy('calculation_method')
            ->get()
            ->map(function ($item) {
                return [
                    'method' => $item->calculation_method,
                    'total' => (float) $item->total,
                ];
            });

        // Upstream vs Downstream
        $upstreamTotal = EmissionRecord::where('company_id', $companyId)
            ->where('scope', 3)
            ->whereYear('entry_date', $year)
            ->whereHas('scope3Category', function ($query) {
                $query->where('category_type', 'upstream');
            })
            ->sum('co2e_value');

        $downstreamTotal = EmissionRecord::where('company_id', $companyId)
            ->where('scope', 3)
            ->whereYear('entry_date', $year)
            ->whereHas('scope3Category', function ($query) {
                $query->where('category_type', 'downstream');
            })
            ->sum('co2e_value');

        return response()->json([
            'total_scope3' => (float) $totalScope3,
            'upstream_total' => (float) $upstreamTotal,
            'downstream_total' => (float) $downstreamTotal,
            'by_category' => $byCategory,
            'by_data_quality' => $byDataQuality,
            'by_calculation_method' => $byMethod,
            'year' => $year,
        ]);
    }

    /**
     * Get all Scope 3 categories.
     */
    public function getCategories()
    {
        $categories = Scope3Category::active()
            ->orderBy('sort_order')
            ->get();

        return response()->json($categories);
    }
}

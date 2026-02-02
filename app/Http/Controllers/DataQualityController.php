<?php

namespace App\Http\Controllers;

use App\Models\EmissionRecord;
use App\Models\Supplier;
use Illuminate\Http\Request;
use App\Helpers\CompanyHelper;
use Illuminate\Support\Facades\DB;

class DataQualityController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:list-data-quality|create-data-quality|edit-data-quality|delete-data-quality', ['only' => ['index', 'getSummary']]);
        $this->middleware('permission:edit-data-quality', ['only' => ['updateQuality']]);
    }

    /**
     * Get data quality dashboard statistics.
     */
    public function index()
    {
        $companyId = CompanyHelper::currentCompanyId();
        
        if (!$companyId) {
            return redirect()->route('home')->with('error', 'Please select a company first.');
        }

        $currentYear = now()->year;

        // Overall data quality breakdown
        $qualityBreakdown = EmissionRecord::where('company_id', $companyId)
            ->whereYear('entry_date', $currentYear)
            ->select('data_quality', DB::raw('COUNT(*) as count'), DB::raw('SUM(co2e_value) as total'))
            ->groupBy('data_quality')
            ->get();

        // Scope 3 specific quality
        $scope3Quality = EmissionRecord::where('company_id', $companyId)
            ->where('scope', 3)
            ->whereYear('entry_date', $currentYear)
            ->select('data_quality', DB::raw('COUNT(*) as count'), DB::raw('SUM(co2e_value) as total'))
            ->groupBy('data_quality')
            ->get();

        // Supplier data quality scores
        $suppliers = Supplier::where('company_id', $companyId)
            ->withCount('emissionRecords')
            ->get()
            ->map(function ($supplier) use ($currentYear) {
                $supplier->quality_score = $supplier->getDataQualityScore();
                $supplier->total_emissions = $supplier->getTotalEmissions($currentYear);
                return $supplier;
            })
            ->sortByDesc('quality_score');

        // Data quality trends (last 12 months)
        $trends = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();

            $monthData = EmissionRecord::where('company_id', $companyId)
                ->whereBetween('entry_date', [$monthStart, $monthEnd])
                ->select('data_quality', DB::raw('COUNT(*) as count'))
                ->groupBy('data_quality')
                ->get();

            $trends[] = [
                'month' => $date->format('M Y'),
                'primary' => $monthData->where('data_quality', 'primary')->sum('count'),
                'secondary' => $monthData->where('data_quality', 'secondary')->sum('count'),
                'estimated' => $monthData->where('data_quality', 'estimated')->sum('count'),
            ];
        }

        // Overall quality score (0-100)
        $totalRecords = EmissionRecord::where('company_id', $companyId)
            ->whereYear('entry_date', $currentYear)
            ->count();

        $primaryRecords = EmissionRecord::where('company_id', $companyId)
            ->whereYear('entry_date', $currentYear)
            ->where('data_quality', 'primary')
            ->count();

        $secondaryRecords = EmissionRecord::where('company_id', $companyId)
            ->whereYear('entry_date', $currentYear)
            ->where('data_quality', 'secondary')
            ->count();

        $overallScore = $totalRecords > 0 
            ? (($primaryRecords * 100) + ($secondaryRecords * 50)) / $totalRecords 
            : 0;

        return view('data_quality.index', compact(
            'qualityBreakdown',
            'scope3Quality',
            'suppliers',
            'trends',
            'overallScore',
            'totalRecords'
        ));
    }

    /**
     * Get data quality summary API.
     */
    public function getSummary(Request $request)
    {
        $companyId = CompanyHelper::currentCompanyId();
        $year = $request->input('year', now()->year);

        $summary = EmissionRecord::where('company_id', $companyId)
            ->whereYear('entry_date', $year)
            ->select(
                'data_quality',
                DB::raw('COUNT(*) as record_count'),
                DB::raw('SUM(co2e_value) as total_emissions')
            )
            ->groupBy('data_quality')
            ->get();

        $totalRecords = $summary->sum('record_count');
        $primaryCount = $summary->where('data_quality', 'primary')->sum('record_count');
        $primaryPercentage = $totalRecords > 0 ? ($primaryCount / $totalRecords) * 100 : 0;

        return response()->json([
            'summary' => $summary,
            'total_records' => $totalRecords,
            'primary_percentage' => round($primaryPercentage, 2),
            'overall_score' => $this->calculateOverallScore($summary, $totalRecords),
        ]);
    }

    /**
     * Calculate overall data quality score.
     */
    private function calculateOverallScore($summary, $totalRecords)
    {
        if ($totalRecords === 0) {
            return 0;
        }

        $primaryCount = $summary->where('data_quality', 'primary')->sum('record_count');
        $secondaryCount = $summary->where('data_quality', 'secondary')->sum('record_count');

        return round((($primaryCount * 100) + ($secondaryCount * 50)) / $totalRecords, 2);
    }

    /**
     * Update data quality for a record.
     */
    public function updateQuality(Request $request, $id)
    {
        $record = EmissionRecord::findOrFail($id);
        
        $companyId = CompanyHelper::currentCompanyId();
        if ($record->company_id != $companyId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'data_quality' => 'required|in:primary,secondary,estimated',
        ]);

        $record->update(['data_quality' => $request->data_quality]);

        // Update supplier data quality if applicable
        if ($record->supplier_id) {
            $supplier = Supplier::find($record->supplier_id);
            if ($supplier) {
                $supplier->updateDataQuality();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Data quality updated successfully',
            'data' => $record
        ]);
    }
}

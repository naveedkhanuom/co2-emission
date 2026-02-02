<?php

namespace App\Http\Controllers;

use App\Models\EmissionRecord;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GHGReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:list-reports|create-report|edit-report|delete-report', ['only' => ['index']]);
    }
    
    public function index(Request $request)
    {
        // Get filter parameters
        $year = $request->get('year', date('Y'));
        $facility = $request->get('facility', '');
        $startDate = $request->get('start_date', $year . '-01-01');
        $endDate = $request->get('end_date', $year . '-12-31');
        
        // Get all emission records for the period
        // Automatically scoped to current company via HasCompanyScope trait
        $query = EmissionRecord::where('status', 'active')
            ->whereBetween('entry_date', [$startDate, $endDate]);
        
        if ($facility) {
            $query->where('facility', $facility);
        }
        
        $records = $query->orderBy('scope')
            ->orderBy('facility')
            ->orderBy('emission_source')
            ->get();
        
        // Organize data by scope
        $scope1Data = $this->organizeScope1Data($records->where('scope', 1));
        $scope2Data = $this->organizeScope2Data($records->where('scope', 2));
        $scope3Data = $this->organizeScope3Data($records->where('scope', 3));
        
        // Calculate totals
        $scope1Total = $records->where('scope', 1)->sum('co2e_value');
        $scope2Total = $records->where('scope', 2)->sum('co2e_value');
        $scope3Total = $records->where('scope', 3)->sum('co2e_value');
        $grandTotal = $scope1Total + $scope2Total + $scope3Total;
        
        // Get facilities for filter
        // Automatically scoped to current company via HasCompanyScope trait
        $facilities = EmissionRecord::where('status', 'active')
            ->distinct()
            ->pluck('facility')
            ->sort()
            ->values();
        
        // Prepare chart data
        $chartData = $this->prepareChartData($records, $startDate, $endDate);
        
        return view('reports.ghg_protocol', compact(
            'scope1Data',
            'scope2Data',
            'scope3Data',
            'scope1Total',
            'scope2Total',
            'scope3Total',
            'grandTotal',
            'year',
            'facility',
            'startDate',
            'endDate',
            'facilities',
            'chartData'
        ));
    }
    
    private function organizeScope1Data($records)
    {
        $organized = [
            'stationary_combustion' => [],
            'mobile_combustion' => [],
            'fugitive_emissions' => [],
            'process_emissions' => [],
        ];
        
        foreach ($records as $record) {
            $source = strtolower($record->emission_source);
            $facility = $record->facility;
            
            // Categorize by emission source type
            if (strpos($source, 'natural gas') !== false || 
                strpos($source, 'diesel') !== false || 
                strpos($source, 'fuel') !== false ||
                strpos($source, 'combustion') !== false) {
                
                if (strpos($source, 'vehicle') !== false || 
                    strpos($source, 'car') !== false || 
                    strpos($source, 'mobile') !== false ||
                    strpos($source, 'petrol') !== false ||
                    strpos($source, 'gasoline') !== false) {
                    $category = 'mobile_combustion';
                } else {
                    $category = 'stationary_combustion';
                }
            } elseif (strpos($source, 'refrigerant') !== false || 
                      strpos($source, 'hfc') !== false || 
                      strpos($source, 'fugitive') !== false) {
                $category = 'fugitive_emissions';
            } elseif (strpos($source, 'process') !== false || 
                      strpos($source, 'industrial') !== false) {
                $category = 'process_emissions';
            } else {
                $category = 'stationary_combustion'; // Default
            }
            
            $key = $facility . '|' . $record->emission_source;
            
            if (!isset($organized[$category][$key])) {
                $organized[$category][$key] = [
                    'facility' => $facility,
                    'source' => $record->emission_source,
                    'activity_data' => 0,
                    'activity_unit' => $this->getUnitFromSource($record->emission_source),
                    'emission_factor' => $record->emission_factor ?? 0,
                    'factor_unit' => $this->getFactorUnit($record->emission_source),
                    'co2e' => 0,
                ];
            }
            
            $organized[$category][$key]['activity_data'] += $record->activity_data ?? 0;
            $organized[$category][$key]['co2e'] += $record->co2e_value;
        }
        
        // Calculate category totals
        foreach ($organized as $category => $items) {
            $organized[$category]['_total'] = array_sum(array_column($items, 'co2e'));
        }
        
        return $organized;
    }
    
    private function organizeScope2Data($records)
    {
        $organized = [];
        
        foreach ($records as $record) {
            $key = $record->facility . '|' . $record->emission_source;
            
            if (!isset($organized[$key])) {
                $organized[$key] = [
                    'facility' => $record->facility,
                    'source' => $record->emission_source,
                    'activity_data' => 0,
                    'activity_unit' => 'KWH',
                    'emission_factor' => $record->emission_factor ?? 0,
                    'factor_unit' => 'kg CO2e/KWh',
                    'co2e' => 0,
                ];
            }
            
            $organized[$key]['activity_data'] += $record->activity_data ?? 0;
            $organized[$key]['co2e'] += $record->co2e_value;
        }
        
        $organized['_total'] = array_sum(array_column($organized, 'co2e'));
        
        return $organized;
    }
    
    private function organizeScope3Data($records)
    {
        // Initialize all 15 Scope 3 categories according to GHG Protocol
        $organized = [
            'purchased_goods' => [],           // Category 1
            'capital_goods' => [],             // Category 2
            'fuel_energy' => [],               // Category 3
            'upstream_transport' => [],         // Category 4
            'waste_operations' => [],          // Category 5
            'business_travel' => [],           // Category 6
            'employee_commute' => [],          // Category 7
            'upstream_leased' => [],           // Category 8
            'downstream_transport' => [],      // Category 9
            'processing_sold' => [],           // Category 10
            'use_sold' => [],                  // Category 11
            'end_life_sold' => [],            // Category 12
            'downstream_leased' => [],         // Category 13
            'franchises' => [],               // Category 14
            'investments' => [],               // Category 15
        ];
        
        foreach ($records as $record) {
            $source = strtolower($record->emission_source);
            $facility = $record->facility;
            
            // Categorize Scope 3 based on source name or value
            $category = $this->categorizeScope3Source($source, $record->emission_source);
            
            $key = $facility . '|' . $record->emission_source;
            
            if (!isset($organized[$category][$key])) {
                $organized[$category][$key] = [
                    'facility' => $facility,
                    'source' => $record->emission_source,
                    'activity_data' => 0,
                    'activity_unit' => $this->getUnitFromSource($record->emission_source),
                    'emission_factor' => $record->emission_factor ?? 0,
                    'factor_unit' => $this->getFactorUnit($record->emission_source),
                    'co2e' => 0,
                ];
            }
            
            $organized[$category][$key]['activity_data'] += $record->activity_data ?? 0;
            $organized[$category][$key]['co2e'] += $record->co2e_value;
        }
        
        // Calculate category totals
        foreach ($organized as $category => $items) {
            if (is_array($items) && !empty($items)) {
                $organized[$category]['_total'] = array_sum(array_column($items, 'co2e'));
            }
        }
        
        return $organized;
    }
    
    /**
     * Categorize Scope 3 emission source into one of 15 GHG Protocol categories
     */
    private function categorizeScope3Source($source, $originalSource)
    {
        // Check for explicit category indicators in source name
        if (strpos($source, 'capital goods') !== false || 
            strpos($source, 'capital-goods') !== false ||
            strpos($source, 'equipment') !== false && strpos($source, 'purchased') === false) {
            return 'capital_goods';
        }
        
        if (strpos($source, 'fuel') !== false && 
            (strpos($source, 'energy') !== false || strpos($source, 'related') !== false)) {
            return 'fuel_energy';
        }
        
        if ((strpos($source, 'upstream') !== false || strpos($source, 'upstream-transport') !== false) && 
            (strpos($source, 'transport') !== false || strpos($source, 'distribution') !== false)) {
            return 'upstream_transport';
        }
        
        if (strpos($source, 'waste') !== false && 
            (strpos($source, 'operations') !== false || strpos($source, 'waste-operations') !== false || 
             strpos($source, 'generated') !== false)) {
            return 'waste_operations';
        }
        
        if (strpos($source, 'business') !== false && 
            (strpos($source, 'travel') !== false || strpos($source, 'business-travel') !== false)) {
            return 'business_travel';
        }
        
        if (strpos($source, 'employee') !== false && 
            (strpos($source, 'commute') !== false || strpos($source, 'commuting') !== false || 
             strpos($source, 'employee-commute') !== false)) {
            return 'employee_commute';
        }
        
        if (strpos($source, 'upstream') !== false && 
            (strpos($source, 'leased') !== false || strpos($source, 'upstream-leased') !== false)) {
            return 'upstream_leased';
        }
        
        if ((strpos($source, 'downstream') !== false || strpos($source, 'downstream-transport') !== false) && 
            (strpos($source, 'transport') !== false || strpos($source, 'distribution') !== false)) {
            return 'downstream_transport';
        }
        
        if (strpos($source, 'processing') !== false && 
            (strpos($source, 'sold') !== false || strpos($source, 'processing-sold') !== false)) {
            return 'processing_sold';
        }
        
        if (strpos($source, 'use') !== false && 
            (strpos($source, 'sold') !== false || strpos($source, 'use-sold') !== false)) {
            return 'use_sold';
        }
        
        if (strpos($source, 'end') !== false && 
            (strpos($source, 'life') !== false || strpos($source, 'end-life') !== false || 
             strpos($source, 'disposal') !== false)) {
            return 'end_life_sold';
        }
        
        if (strpos($source, 'downstream') !== false && 
            (strpos($source, 'leased') !== false || strpos($source, 'downstream-leased') !== false)) {
            return 'downstream_leased';
        }
        
        if (strpos($source, 'franchise') !== false || strpos($source, 'franchises') !== false) {
            return 'franchises';
        }
        
        if (strpos($source, 'investment') !== false || strpos($source, 'investments') !== false) {
            return 'investments';
        }
        
        // Default categorization based on common patterns
        if (strpos($source, 'purchased') !== false || 
            strpos($source, 'goods') !== false || 
            strpos($source, 'services') !== false ||
            strpos($source, 'material') !== false ||
            strpos($source, 'purchased-goods') !== false) {
            return 'purchased_goods';
        }
        
        // Legacy support for old "transportation" entries
        if (strpos($source, 'transport') !== false || 
            strpos($source, 'distribution') !== false ||
            strpos($source, 'delivery') !== false ||
            strpos($source, 'logistics') !== false) {
            // Default to downstream if not specified
            return 'downstream_transport';
        }
        
        // Legacy support for old "waste" entries
        if (strpos($source, 'waste') !== false) {
            return 'waste_operations';
        }
        
        // Default fallback
        return 'purchased_goods';
    }
    
    private function getUnitFromSource($source)
    {
        $source = strtolower($source);
        
        if (strpos($source, 'electricity') !== false) return 'KWH';
        if (strpos($source, 'gas') !== false && strpos($source, 'natural') !== false) return 'Kgs';
        if (strpos($source, 'diesel') !== false) return 'Litres';
        if (strpos($source, 'petrol') !== false || strpos($source, 'gasoline') !== false) return 'Litres';
        if (strpos($source, 'km') !== false || strpos($source, 'kilometer') !== false) return 'KM';
        if (strpos($source, 'ton') !== false || strpos($source, 'tonne') !== false) return 'Ton';
        if (strpos($source, 'refrigerant') !== false || strpos($source, 'hfc') !== false) return 'Kgs';
        
        return 'Unit';
    }
    
    private function getFactorUnit($source)
    {
        $source = strtolower($source);
        
        if (strpos($source, 'electricity') !== false) return 'kg CO2e/KWh';
        if (strpos($source, 'gas') !== false && strpos($source, 'natural') !== false) return 'kg CO2e per Kg';
        if (strpos($source, 'diesel') !== false) return 'kg CO2e per Litre';
        if (strpos($source, 'petrol') !== false || strpos($source, 'gasoline') !== false) {
            if (strpos($source, 'km') !== false) return 'kg CO2e per KM';
            return 'kg CO2e per Litre';
        }
        if (strpos($source, 'km') !== false || strpos($source, 'kilometer') !== false) return 'kg CO2e per KM';
        if (strpos($source, 'ton') !== false || strpos($source, 'tonne') !== false) return 'g CO2e per gram of Material';
        if (strpos($source, 'refrigerant') !== false || strpos($source, 'hfc') !== false) return 'kg CO2e per Kg';
        
        return 'kg CO2e/Unit';
    }
    
    private function prepareChartData($records, $startDate, $endDate)
    {
        // Scope breakdown for pie chart
        $scopeBreakdown = [
            'Scope 1' => $records->where('scope', 1)->sum('co2e_value'),
            'Scope 2' => $records->where('scope', 2)->sum('co2e_value'),
            'Scope 3' => $records->where('scope', 3)->sum('co2e_value'),
        ];
        
        // Monthly trend data
        $monthlyData = [];
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        for ($date = $start->copy()->startOfMonth(); $date->lte($end); $date->addMonth()) {
            $monthKey = $date->format('M Y');
            $monthlyData[$monthKey] = [
                'scope1' => 0,
                'scope2' => 0,
                'scope3' => 0,
            ];
        }
        
        foreach ($records as $record) {
            $monthKey = Carbon::parse($record->entry_date)->format('M Y');
            if (isset($monthlyData[$monthKey])) {
                $scope = 'scope' . $record->scope;
                $monthlyData[$monthKey][$scope] += $record->co2e_value;
            }
        }
        
        // Facility-wise comparison
        $facilityData = [];
        foreach ($records->groupBy('facility') as $facilityName => $facilityRecords) {
            $facilityData[$facilityName] = [
                'scope1' => $facilityRecords->where('scope', 1)->sum('co2e_value'),
                'scope2' => $facilityRecords->where('scope', 2)->sum('co2e_value'),
                'scope3' => $facilityRecords->where('scope', 3)->sum('co2e_value'),
                'total' => $facilityRecords->sum('co2e_value'),
            ];
        }
        
        // Scope 1 category breakdown
        $scope1Categories = [
            'Stationary Combustion' => 0,
            'Mobile Combustion' => 0,
            'Fugitive Emissions' => 0,
            'Process Emissions' => 0,
        ];
        
        foreach ($records->where('scope', 1) as $record) {
            $source = strtolower($record->emission_source);
            if (strpos($source, 'natural gas') !== false || 
                (strpos($source, 'diesel') !== false && strpos($source, 'vehicle') === false && strpos($source, 'car') === false)) {
                $scope1Categories['Stationary Combustion'] += $record->co2e_value;
            } elseif (strpos($source, 'vehicle') !== false || 
                      strpos($source, 'car') !== false || 
                      strpos($source, 'petrol') !== false ||
                      strpos($source, 'gasoline') !== false) {
                $scope1Categories['Mobile Combustion'] += $record->co2e_value;
            } elseif (strpos($source, 'refrigerant') !== false || 
                      strpos($source, 'hfc') !== false || 
                      strpos($source, 'fugitive') !== false) {
                $scope1Categories['Fugitive Emissions'] += $record->co2e_value;
            } elseif (strpos($source, 'process') !== false || 
                      strpos($source, 'industrial') !== false) {
                $scope1Categories['Process Emissions'] += $record->co2e_value;
            } else {
                $scope1Categories['Stationary Combustion'] += $record->co2e_value;
            }
        }
        
        return [
            'scope_breakdown' => $scopeBreakdown,
            'monthly_trend' => $monthlyData,
            'facility_comparison' => $facilityData,
            'scope1_categories' => $scope1Categories,
        ];
    }
}


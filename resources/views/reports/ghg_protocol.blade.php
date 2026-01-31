<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GHG Protocol Report - {{ $year }}</title>
    <!-- ApexCharts CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.css">
    <style>
        @page {
            margin: 20mm;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #000;
            margin: 0;
            padding: 0;
        }
        
        .header {
            text-align: right;
            margin-bottom: 20px;
            font-size: 9pt;
            color: #666;
        }
        
        .logo-section {
            text-align: center;
            margin: 20px 0 30px 0;
        }
        
        .logo-section img {
            max-height: 80px;
            max-width: 300px;
            object-fit: contain;
        }
        
        .company-name {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            margin: 20px 0;
            color: #1a1a1a;
        }
        
        .report-title {
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            margin: 15px 0;
            color: #0066cc;
        }
        
        .report-subtitle {
            text-align: center;
            font-size: 11pt;
            margin-bottom: 20px;
            color: #666;
        }
        
        .filters {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
        }
        
        .filters label {
            font-weight: bold;
            margin-right: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 9pt;
        }
        
        table th {
            background-color: #0066cc;
            color: white;
            padding: 8px;
            text-align: left;
            border: 1px solid #004499;
            font-weight: bold;
        }
        
        table td {
            padding: 6px 8px;
            border: 1px solid #ddd;
        }
        
        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .section-header {
            background-color: #e6f2ff;
            font-weight: bold;
            font-size: 11pt;
            padding: 10px;
            margin-top: 20px;
            border-left: 4px solid #0066cc;
        }
        
        .subsection-header {
            background-color: #f0f8ff;
            font-weight: bold;
            padding: 8px;
            margin-top: 15px;
            border-left: 3px solid #0066cc;
        }
        
        .total-row {
            background-color: #e6f2ff;
            font-weight: bold;
        }
        
        .grand-total-row {
            background-color: #0066cc;
            color: white;
            font-weight: bold;
            font-size: 11pt;
        }
        
        .number {
            text-align: right;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 8pt;
            color: #666;
            display: flex;
            justify-content: space-between;
        }
        
        .no-data {
            text-align: center;
            padding: 20px;
            color: #999;
            font-style: italic;
        }
        
        .charts-section {
            margin: 30px 0;
            page-break-inside: avoid;
        }
        
        .chart-container {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            page-break-inside: avoid;
        }
        
        .chart-title {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 15px;
            color: #0066cc;
            text-align: center;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }
        
        @media print {
            .filters {
                display: none;
            }
            
            .no-print {
                display: none;
            }
            
            .charts-grid {
                grid-template-columns: 1fr;
            }
            
            .chart-container {
                page-break-inside: avoid;
            }
        }
        
        @media (max-width: 768px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        GREEN CRESCENT ENVIRONMENTAL ENGINEERING CONSULTANTS
    </div>
    
    <div class="logo-section">
        @if(file_exists(public_path('logo.png')))
            <img src="{{ asset('logo.png') }}" alt="Green Crescent Logo">
        @elseif(file_exists(public_path('logo.jpg')))
            <img src="{{ asset('logo.jpg') }}" alt="Green Crescent Logo">
        @else
            <img src="https://cdn.prod.website-files.com/68ce511f0ec3dbdca3e16b5b/68ce5272a15164172603c206_logo%20green.avif" 
                 alt="Green Crescent Logo" 
                 onerror="this.style.display='none';">
        @endif
    </div>
    
    <div class="company-name">
        GREEN CRESCENT ENVIRONMENTAL ENGINEERING CONSULTANTS
    </div>
    
    <div class="report-title">
        GHG PROTOCOL REPORT
    </div>
    
    <div class="report-subtitle">
        Greenhouse Gas Emission Calculation Report<br>
        Period: {{ date('F d', strtotime($startDate)) }} - {{ date('F d, Y', strtotime($endDate)) }}
    </div>
    
    <!-- Charts Section -->
    <div class="charts-section">
        <div class="charts-grid">
            <!-- Scope Breakdown Pie Chart -->
            <div class="chart-container">
                <div class="chart-title">Emissions by Scope</div>
                <div id="scopeBreakdownChart"></div>
            </div>
            
            <!-- Scope 1 Categories Chart -->
            <div class="chart-container">
                <div class="chart-title">Scope 1 Emissions by Category</div>
                <div id="scope1CategoriesChart"></div>
            </div>
        </div>
        
        <!-- Monthly Trend Chart -->
        <div class="chart-container">
            <div class="chart-title">Monthly Emissions Trend</div>
            <div id="monthlyTrendChart"></div>
        </div>
        
        <!-- Facility Comparison Chart -->
        @if(count($chartData['facility_comparison']) > 0)
        <div class="chart-container">
            <div class="chart-title">Emissions by Facility</div>
            <div id="facilityComparisonChart"></div>
        </div>
        @endif
    </div>
    
    <div class="filters no-print">
        <form method="GET" action="{{ route('reports.ghg_protocol') }}">
            <label>Year:</label>
            <input type="number" name="year" value="{{ $year }}" min="2000" max="2100" style="width: 80px;">
            
            <label style="margin-left: 20px;">Facility:</label>
            <select name="facility" style="width: 200px;">
                <option value="">All Facilities</option>
                @foreach($facilities as $fac)
                    <option value="{{ $fac }}" {{ $facility === $fac ? 'selected' : '' }}>{{ $fac }}</option>
                @endforeach
            </select>
            
            <button type="submit" style="margin-left: 20px; padding: 5px 15px;">Generate Report</button>
            <button type="button" onclick="window.print()" style="margin-left: 10px; padding: 5px 15px;">Print / PDF</button>
        </form>
    </div>
    
    <!-- SCOPE 1: DIRECT EMISSIONS -->
    <div class="section-header">
        SCOPE-1 GHG EMISSION CALCULATION
    </div>
    
    @php
        $scope1ItemNumber = 1;
    @endphp
    
    <!-- A. Stationary Combustion -->
    @if(!empty($scope1Data['stationary_combustion']) && count($scope1Data['stationary_combustion']) > 1)
    <div class="subsection-header">
        A. Stationary Combustion
    </div>
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">Sr. No.</th>
                <th style="width: 30%;">Particulars</th>
                <th style="width: 12%;">Activity Data (A)</th>
                <th style="width: 12%;">Emission Factor (E)</th>
                <th style="width: 15%;">Carbon Footprint</th>
            </tr>
            <tr>
                <th></th>
                <th></th>
                <th style="font-size: 8pt;">UOM | Value</th>
                <th style="font-size: 8pt;">UOM | Value</th>
                <th style="font-size: 8pt;">In Kg CO₂e</th>
            </tr>
        </thead>
        <tbody>
            @foreach($scope1Data['stationary_combustion'] as $key => $item)
                @if($key !== '_total')
                <tr>
                    <td class="number">{{ $scope1ItemNumber++ }}</td>
                    <td>{{ $item['facility'] }} ({{ $item['source'] }})</td>
                    <td>
                        <div style="display: flex; justify-content: space-between;">
                            <span>{{ $item['activity_unit'] }}</span>
                            <span class="number">{{ number_format($item['activity_data'], 2) }}</span>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="font-size: 8pt;">{{ $item['factor_unit'] }}</span>
                            <span class="number">{{ number_format($item['emission_factor'], 2) }}</span>
                        </div>
                    </td>
                    <td class="number">{{ number_format($item['co2e'], 2) }}</td>
                </tr>
                @endif
            @endforeach
            <tr class="total-row">
                <td colspan="4" class="number"><strong>Total Stationary Combustion</strong></td>
                <td class="number"><strong>{{ number_format($scope1Data['stationary_combustion']['_total'] ?? 0, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>
    @endif
    
    <!-- B. Mobile Combustion -->
    @if(!empty($scope1Data['mobile_combustion']) && count($scope1Data['mobile_combustion']) > 1)
    <div class="subsection-header">
        B. Mobile Combustion
    </div>
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">Sr. No.</th>
                <th style="width: 30%;">Particulars</th>
                <th style="width: 12%;">Activity Data (A)</th>
                <th style="width: 12%;">Emission Factor (E)</th>
                <th style="width: 15%;">Carbon Footprint</th>
            </tr>
            <tr>
                <th></th>
                <th></th>
                <th style="font-size: 8pt;">UOM | Value</th>
                <th style="font-size: 8pt;">UOM | Value</th>
                <th style="font-size: 8pt;">In Kg CO₂e</th>
            </tr>
        </thead>
        <tbody>
            @foreach($scope1Data['mobile_combustion'] as $key => $item)
                @if($key !== '_total')
                <tr>
                    <td class="number">{{ $scope1ItemNumber++ }}</td>
                    <td>{{ $item['facility'] }} ({{ $item['source'] }})</td>
                    <td>
                        <div style="display: flex; justify-content: space-between;">
                            <span>{{ $item['activity_unit'] }}</span>
                            <span class="number">{{ number_format($item['activity_data'], 2) }}</span>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="font-size: 8pt;">{{ $item['factor_unit'] }}</span>
                            <span class="number">{{ number_format($item['emission_factor'], 2) }}</span>
                        </div>
                    </td>
                    <td class="number">{{ number_format($item['co2e'], 2) }}</td>
                </tr>
                @endif
            @endforeach
            <tr class="total-row">
                <td colspan="4" class="number"><strong>Total Mobile Combustion</strong></td>
                <td class="number"><strong>{{ number_format($scope1Data['mobile_combustion']['_total'] ?? 0, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>
    @endif
    
    <!-- C. Fugitive Emissions -->
    @if(!empty($scope1Data['fugitive_emissions']) && count($scope1Data['fugitive_emissions']) > 1)
    <div class="subsection-header">
        C. Fugitive Emission
    </div>
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">Sr. No.</th>
                <th style="width: 30%;">Particulars</th>
                <th style="width: 12%;">Activity Data (A)</th>
                <th style="width: 12%;">Emission Factor (E)</th>
                <th style="width: 15%;">Carbon Footprint</th>
            </tr>
            <tr>
                <th></th>
                <th></th>
                <th style="font-size: 8pt;">UOM | Value</th>
                <th style="font-size: 8pt;">UOM | Value</th>
                <th style="font-size: 8pt;">In Kg CO₂e</th>
            </tr>
        </thead>
        <tbody>
            @foreach($scope1Data['fugitive_emissions'] as $key => $item)
                @if($key !== '_total')
                <tr>
                    <td class="number">{{ $scope1ItemNumber++ }}</td>
                    <td>{{ $item['facility'] }} ({{ $item['source'] }})</td>
                    <td>
                        <div style="display: flex; justify-content: space-between;">
                            <span>{{ $item['activity_unit'] }}</span>
                            <span class="number">{{ number_format($item['activity_data'], 2) }}</span>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="font-size: 8pt;">{{ $item['factor_unit'] }}</span>
                            <span class="number">{{ number_format($item['emission_factor'], 2) }}</span>
                        </div>
                    </td>
                    <td class="number">{{ number_format($item['co2e'], 2) }}</td>
                </tr>
                @endif
            @endforeach
            <tr class="total-row">
                <td colspan="4" class="number"><strong>Total Fugitive Emission</strong></td>
                <td class="number"><strong>{{ number_format($scope1Data['fugitive_emissions']['_total'] ?? 0, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>
    @endif
    
    <!-- D. Process Emissions -->
    @if(!empty($scope1Data['process_emissions']) && count($scope1Data['process_emissions']) > 1)
    <div class="subsection-header">
        D. Process Emission
    </div>
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">Sr. No.</th>
                <th style="width: 30%;">Particulars</th>
                <th style="width: 12%;">Activity Data (A)</th>
                <th style="width: 12%;">Emission Factor (E)</th>
                <th style="width: 15%;">Carbon Footprint</th>
            </tr>
            <tr>
                <th></th>
                <th></th>
                <th style="font-size: 8pt;">UOM | Value</th>
                <th style="font-size: 8pt;">UOM | Value</th>
                <th style="font-size: 8pt;">In Kg CO₂e</th>
            </tr>
        </thead>
        <tbody>
            @foreach($scope1Data['process_emissions'] as $key => $item)
                @if($key !== '_total')
                <tr>
                    <td class="number">{{ $scope1ItemNumber++ }}</td>
                    <td>{{ $item['facility'] }} ({{ $item['source'] }})</td>
                    <td>
                        <div style="display: flex; justify-content: space-between;">
                            <span>{{ $item['activity_unit'] }}</span>
                            <span class="number">{{ number_format($item['activity_data'], 2) }}</span>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="font-size: 8pt;">{{ $item['factor_unit'] }}</span>
                            <span class="number">{{ number_format($item['emission_factor'], 2) }}</span>
                        </div>
                    </td>
                    <td class="number">{{ number_format($item['co2e'], 2) }}</td>
                </tr>
                @endif
            @endforeach
            <tr class="total-row">
                <td colspan="4" class="number"><strong>Total Process Emission</strong></td>
                <td class="number"><strong>{{ number_format($scope1Data['process_emissions']['_total'] ?? 0, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>
    @endif
    
    <table>
        <tbody>
            <tr class="grand-total-row">
                <td colspan="4" class="number"><strong>Total Scope-1 GHG Emission</strong></td>
                <td class="number"><strong>{{ number_format($scope1Total, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>
    
    <!-- SCOPE 2: INDIRECT EMISSIONS FROM ENERGY -->
    <div class="section-header">
        SCOPE-2 GHG EMISSION CALCULATION
    </div>
    
    @if(!empty($scope2Data) && count($scope2Data) > 1)
    <div class="subsection-header">
        A. Purchased Electricity from Grid
    </div>
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">Item No.</th>
                <th style="width: 30%;">Description</th>
                <th style="width: 12%;">Unit</th>
                <th style="width: 12%;">Activity Data</th>
                <th style="width: 12%;">Emission Factor</th>
                <th style="width: 15%;">Calculated CO2e</th>
            </tr>
        </thead>
        <tbody>
            @php $scope2ItemNumber = 15; @endphp
            @foreach($scope2Data as $key => $item)
                @if($key !== '_total')
                <tr>
                    <td class="number">{{ $scope2ItemNumber++ }}</td>
                    <td>{{ $item['source'] }} at {{ $item['facility'] }}</td>
                    <td>{{ $item['activity_unit'] }}</td>
                    <td class="number">{{ number_format($item['activity_data'], 2) }}</td>
                    <td class="number">{{ number_format($item['emission_factor'], 2) }} {{ $item['factor_unit'] }}</td>
                    <td class="number">{{ number_format($item['co2e'], 2) }}</td>
                </tr>
                @endif
            @endforeach
            <tr class="total-row">
                <td colspan="5" class="number"><strong>Total Scope-2 GHG Emission</strong></td>
                <td class="number"><strong>{{ number_format($scope2Data['_total'] ?? 0, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>
    @else
    <div class="no-data">No Scope 2 emissions data available for the selected period.</div>
    @endif
    
    <!-- SCOPE 3: OTHER INDIRECT EMISSIONS -->
    <div class="section-header">
        SCOPE-3 GHG EMISSION CALCULATION
    </div>
    
    @php
        $scope3ItemNumber = 24;
        $hasScope3Data = false;
        
        // Define all 15 Scope 3 categories with their labels
        $scope3Categories = [
            'purchased_goods' => ['label' => '1. Purchased Goods and Services', 'section' => 'A. Upstream Emissions'],
            'capital_goods' => ['label' => '2. Capital Goods', 'section' => 'A. Upstream Emissions'],
            'fuel_energy' => ['label' => '3. Fuel and Energy Related Activities', 'section' => 'A. Upstream Emissions'],
            'upstream_transport' => ['label' => '4. Upstream Transportation and Distribution', 'section' => 'A. Upstream Emissions'],
            'waste_operations' => ['label' => '5. Waste Generated in Operations', 'section' => 'A. Upstream Emissions'],
            'business_travel' => ['label' => '6. Business Travel', 'section' => 'A. Upstream Emissions'],
            'employee_commute' => ['label' => '7. Employee Commuting', 'section' => 'A. Upstream Emissions'],
            'upstream_leased' => ['label' => '8. Upstream Leased Assets', 'section' => 'A. Upstream Emissions'],
            'downstream_transport' => ['label' => '9. Downstream Transportation and Distribution', 'section' => 'B. Downstream Emissions'],
            'processing_sold' => ['label' => '10. Processing of Sold Products', 'section' => 'B. Downstream Emissions'],
            'use_sold' => ['label' => '11. Use of Sold Products', 'section' => 'B. Downstream Emissions'],
            'end_life_sold' => ['label' => '12. End-of-Life Treatment of Sold Products', 'section' => 'B. Downstream Emissions'],
            'downstream_leased' => ['label' => '13. Downstream Leased Assets', 'section' => 'B. Downstream Emissions'],
            'franchises' => ['label' => '14. Franchises', 'section' => 'B. Downstream Emissions'],
            'investments' => ['label' => '15. Investments', 'section' => 'B. Downstream Emissions'],
        ];
        
        $currentSection = '';
    @endphp
    
    @foreach($scope3Categories as $categoryKey => $categoryInfo)
        @if(!empty($scope3Data[$categoryKey]) && count($scope3Data[$categoryKey]) > 1)
            @php $hasScope3Data = true; @endphp
            
            @if($currentSection !== $categoryInfo['section'])
                @php $currentSection = $categoryInfo['section']; @endphp
                <div class="section-header" style="margin-top: 30px;">
                    {{ $categoryInfo['section'] }}
                </div>
            @endif
            
            <div class="subsection-header">
                {{ $categoryInfo['label'] }}
            </div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 5%;">Item No.</th>
                        <th style="width: 25%;">Description</th>
                        <th style="width: 10%;">Unit</th>
                        <th style="width: 15%;">Activity Data</th>
                        <th style="width: 15%;">Emission Factor</th>
                        <th style="width: 15%;">Calculated CO2e</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($scope3Data[$categoryKey] as $key => $item)
                        @if($key !== '_total')
                        <tr>
                            <td class="number">{{ $scope3ItemNumber++ }}</td>
                            <td>{{ $item['source'] }}@if(isset($item['facility']) && $item['facility']) - {{ $item['facility'] }}@endif</td>
                            <td>{{ $item['activity_unit'] }}</td>
                            <td class="number">{{ number_format($item['activity_data'], 2) }}</td>
                            <td class="number">{{ number_format($item['emission_factor'], 4) }} {{ $item['factor_unit'] }}</td>
                            <td class="number">{{ number_format($item['co2e'], 2) }}</td>
                        </tr>
                        @endif
                    @endforeach
                    <tr class="total-row">
                        <td colspan="5" class="number"><strong>Total {{ $categoryInfo['label'] }} Emission</strong></td>
                        <td class="number"><strong>{{ number_format($scope3Data[$categoryKey]['_total'] ?? 0, 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        @endif
    @endforeach
    
    @if(!$hasScope3Data)
    <div class="no-data">No Scope 3 emissions data available for the selected period.</div>
    @endif
    
    <table>
        <tbody>
            <tr class="total-row">
                <td colspan="4" class="number"><strong>Total Scope-3 GHG Emission</strong></td>
                <td class="number"><strong>{{ number_format($scope3Total, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>
    
    <!-- GRAND TOTAL -->
    <table>
        <tbody>
            <tr class="grand-total-row">
                <td colspan="4" class="number"><strong>Total GHG Emission (Scope-1 + Scope-2 + Scope-3)</strong></td>
                <td class="number"><strong>{{ number_format($grandTotal, 2) }} Kg CO₂e</strong></td>
            </tr>
        </tbody>
    </table>
    
    <div class="footer">
        <div>M-8057921557</div>
        <div>{{ date('F d, Y') }}</div>
        <div>GREEN CRESCENT ENVIRONMENTAL ENGINEERING CONSULTANTS</div>
    </div>
    
    <!-- ApexCharts JS -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.js"></script>
    
    <script>
        // Scope Breakdown Pie Chart
        var scopeBreakdownOptions = {
            series: [
                {{ number_format($chartData['scope_breakdown']['Scope 1'], 2) }},
                {{ number_format($chartData['scope_breakdown']['Scope 2'], 2) }},
                {{ number_format($chartData['scope_breakdown']['Scope 3'], 2) }}
            ],
            chart: {
                type: 'donut',
                height: 300
            },
            labels: ['Scope 1', 'Scope 2', 'Scope 3'],
            colors: ['#0066cc', '#4caf50', '#ff9800'],
            legend: {
                position: 'bottom'
            },
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return val.toFixed(1) + '%';
                }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val.toLocaleString('en-US', {maximumFractionDigits: 2}) + ' Kg CO₂e';
                    }
                }
            }
        };
        var scopeBreakdownChart = new ApexCharts(document.querySelector("#scopeBreakdownChart"), scopeBreakdownOptions);
        scopeBreakdownChart.render();
        
        // Scope 1 Categories Chart
        var scope1CategoriesOptions = {
            series: [{
                name: 'Emissions (Kg CO₂e)',
                data: [
                    {{ number_format($chartData['scope1_categories']['Stationary Combustion'], 2) }},
                    {{ number_format($chartData['scope1_categories']['Mobile Combustion'], 2) }},
                    {{ number_format($chartData['scope1_categories']['Fugitive Emissions'], 2) }},
                    {{ number_format($chartData['scope1_categories']['Process Emissions'], 2) }}
                ]
            }],
            chart: {
                type: 'bar',
                height: 300
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    distributed: false
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return val.toLocaleString('en-US', {maximumFractionDigits: 0});
                }
            },
            xaxis: {
                categories: ['Stationary Combustion', 'Mobile Combustion', 'Fugitive Emissions', 'Process Emissions']
            },
            colors: ['#0066cc'],
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val.toLocaleString('en-US', {maximumFractionDigits: 2}) + ' Kg CO₂e';
                    }
                }
            }
        };
        var scope1CategoriesChart = new ApexCharts(document.querySelector("#scope1CategoriesChart"), scope1CategoriesOptions);
        scope1CategoriesChart.render();
        
        // Monthly Trend Chart
        var monthlyData = @json($chartData['monthly_trend']);
        var monthlyCategories = Object.keys(monthlyData);
        var monthlyScope1 = Object.values(monthlyData).map(item => item.scope1);
        var monthlyScope2 = Object.values(monthlyData).map(item => item.scope2);
        var monthlyScope3 = Object.values(monthlyData).map(item => item.scope3);
        
        var monthlyTrendOptions = {
            series: [
                {
                    name: 'Scope 1',
                    data: monthlyScope1
                },
                {
                    name: 'Scope 2',
                    data: monthlyScope2
                },
                {
                    name: 'Scope 3',
                    data: monthlyScope3
                }
            ],
            chart: {
                type: 'line',
                height: 350
            },
            stroke: {
                width: 3,
                curve: 'smooth'
            },
            xaxis: {
                categories: monthlyCategories
            },
            colors: ['#0066cc', '#4caf50', '#ff9800'],
            legend: {
                position: 'top'
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val.toLocaleString('en-US', {maximumFractionDigits: 2}) + ' Kg CO₂e';
                    }
                }
            },
            dataLabels: {
                enabled: false
            }
        };
        var monthlyTrendChart = new ApexCharts(document.querySelector("#monthlyTrendChart"), monthlyTrendOptions);
        monthlyTrendChart.render();
        
        @if(count($chartData['facility_comparison']) > 0)
        // Facility Comparison Chart
        var facilityData = @json($chartData['facility_comparison']);
        var facilityNames = Object.keys(facilityData);
        var facilityScope1 = Object.values(facilityData).map(item => item.scope1);
        var facilityScope2 = Object.values(facilityData).map(item => item.scope2);
        var facilityScope3 = Object.values(facilityData).map(item => item.scope3);
        
        var facilityComparisonOptions = {
            series: [
                {
                    name: 'Scope 1',
                    data: facilityScope1
                },
                {
                    name: 'Scope 2',
                    data: facilityScope2
                },
                {
                    name: 'Scope 3',
                    data: facilityScope3
                }
            ],
            chart: {
                type: 'bar',
                height: 350,
                stacked: true
            },
            plotOptions: {
                bar: {
                    horizontal: false
                }
            },
            xaxis: {
                categories: facilityNames
            },
            colors: ['#0066cc', '#4caf50', '#ff9800'],
            legend: {
                position: 'top'
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val.toLocaleString('en-US', {maximumFractionDigits: 2}) + ' Kg CO₂e';
                    }
                }
            },
            dataLabels: {
                enabled: false
            }
        };
        var facilityComparisonChart = new ApexCharts(document.querySelector("#facilityComparisonChart"), facilityComparisonOptions);
        facilityComparisonChart.render();
        @endif
    </script>
</body>
</html>


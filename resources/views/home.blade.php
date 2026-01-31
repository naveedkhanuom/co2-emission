@extends('layouts.app')

@push('styles')
<style>
    /* Enhanced KPI Cards */
    .kpi-card {
        border-radius: 16px;
        border: none;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08), 0 4px 16px rgba(0, 0, 0, 0.04);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        height: 100%;
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        overflow: hidden;
        position: relative;
    }
    
    .kpi-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, transparent, currentColor, transparent);
        opacity: 0;
        transition: opacity 0.3s;
    }
    
    .kpi-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12), 0 4px 16px rgba(0, 0, 0, 0.08);
    }
    
    .kpi-card:hover::before {
        opacity: 1;
    }
    
    .kpi-card.card-total::before { color: #2e7d32; }
    .kpi-card.card-scope1::before { color: #4caf50; }
    .kpi-card.card-scope2::before { color: #0277bd; }
    .kpi-card.card-scope3::before { color: #f57c00; }
    
    .kpi-icon {
        width: 70px;
        height: 70px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        color: white;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .kpi-icon::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%);
        opacity: 0;
        transition: opacity 0.3s;
    }
    
    .kpi-card:hover .kpi-icon {
        transform: scale(1.1) rotate(5deg);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
    }
    
    .kpi-card:hover .kpi-icon::before {
        opacity: 1;
    }
    
    .kpi-icon.icon-total {
        background: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%);
    }
    
    .kpi-icon.icon-scope1 {
        background: linear-gradient(135deg, #4caf50 0%, #81c784 100%);
    }
    
    .kpi-icon.icon-scope2 {
        background: linear-gradient(135deg, #0277bd 0%, #03a9f4 100%);
    }
    
    .kpi-icon.icon-scope3 {
        background: linear-gradient(135deg, #f57c00 0%, #ff9800 100%);
    }
    
    .kpi-value {
        font-size: 2.25rem;
        font-weight: 700;
        margin: 12px 0 8px 0;
        background: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        line-height: 1.2;
    }
    
    .kpi-card.card-scope1 .kpi-value {
        background: linear-gradient(135deg, #4caf50 0%, #81c784 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    
    .kpi-card.card-scope2 .kpi-value {
        background: linear-gradient(135deg, #0277bd 0%, #03a9f4 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    
    .kpi-card.card-scope3 .kpi-value {
        background: linear-gradient(135deg, #f57c00 0%, #ff9800 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    
    .kpi-label {
        color: #6c757d;
        font-size: 0.875rem;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    
    .kpi-change {
        font-weight: 600;
        font-size: 0.875rem;
        margin-top: 8px;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .change-positive {
        color: #28a745;
    }
    
    .change-negative {
        color: #dc3545;
    }
    
    /* Enhanced Filters Section */
    .filters-section {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border-radius: 16px;
        padding: 28px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08), 0 4px 16px rgba(0, 0, 0, 0.04);
        margin-bottom: 24px;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .filters-section h5 {
        color: #2e7d32;
        font-weight: 700;
        margin-bottom: 20px;
        font-size: 1.25rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .filters-section h5::before {
        content: '';
        width: 4px;
        height: 24px;
        background: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%);
        border-radius: 2px;
    }
    
    .filter-label {
        font-weight: 600;
        margin-bottom: 10px;
        color: #495057;
        font-size: 0.875rem;
    }
    
    .form-select {
        border-radius: 10px;
        border: 1px solid #dee2e6;
        padding: 10px 15px;
        transition: all 0.3s;
        font-size: 0.9rem;
    }
    
    .form-select:focus {
        border-color: #2e7d32;
        box-shadow: 0 0 0 0.2rem rgba(46, 125, 50, 0.15);
        outline: none;
    }
    
    .btn {
        border-radius: 10px;
        padding: 10px 20px;
        font-weight: 600;
        transition: all 0.3s;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }
    
    .btn-success {
        background: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%);
        border: none;
    }
    
    .btn-success:hover {
        background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 100%);
    }
    
    /* Enhanced Chart Containers */
    .chart-container {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border-radius: 16px;
        padding: 28px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08), 0 4px 16px rgba(0, 0, 0, 0.04);
        margin-bottom: 24px;
        height: 100%;
        border: 1px solid rgba(0, 0, 0, 0.05);
        transition: all 0.3s;
    }
    
    .chart-container:hover {
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1), 0 8px 24px rgba(0, 0, 0, 0.06);
    }
    
    .chart-title {
        font-size: 1.3rem;
        font-weight: 700;
        color: #2e7d32;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        padding-bottom: 16px;
        border-bottom: 2px solid #e9ecef;
    }
    
    .chart-title::before {
        content: '';
        width: 4px;
        height: 24px;
        background: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%);
        border-radius: 2px;
    }
    
    /* Chart Specific Styles for Better Visibility */
    #trendChart {
        min-height: 420px;
    }
    
    #trendChart .apexcharts-canvas {
        background: transparent !important;
    }
    
    #trendChart .apexcharts-gridline {
        stroke: #e0e0e0;
        stroke-width: 1;
        stroke-dasharray: 3;
    }
    
    #trendChart .apexcharts-xaxis-tick,
    #trendChart .apexcharts-yaxis-tick {
        stroke: #dee2e6;
    }
    
    #trendChart .apexcharts-tooltip {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        border-radius: 8px;
        border: 1px solid #e0e0e0;
    }
    
    #trendChart .apexcharts-legend {
        padding: 10px 0;
    }
    
    #trendChart .apexcharts-datalabel {
        font-weight: 700;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }
    
    /* Enhanced Line Visibility */
    #trendChart .apexcharts-line-series path {
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
        stroke-width: 5 !important;
    }
    
    #trendChart .apexcharts-line-series[data:realIndex="0"] path {
        stroke: #00C853 !important;
        stroke-width: 5 !important;
    }
    
    #trendChart .apexcharts-line-series[data:realIndex="1"] path {
        stroke: #FF5722 !important;
        stroke-width: 4 !important;
    }
    
    #trendChart .apexcharts-marker {
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
    }
    
    /* Enhanced Data Table */
    .data-table-section {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border-radius: 16px;
        padding: 28px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08), 0 4px 16px rgba(0, 0, 0, 0.04);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .data-table-section h5 {
        color: #2e7d32;
        font-weight: 700;
        font-size: 1.25rem;
        margin: 0;
    }
    
    .input-group-text {
        background: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%);
        color: white;
        border: none;
        border-radius: 10px 0 0 10px;
    }
    
    .form-control {
        border-radius: 0 10px 10px 0;
        border: 1px solid #dee2e6;
        transition: all 0.3s;
    }
    
    .form-control:focus {
        border-color: #2e7d32;
        box-shadow: 0 0 0 0.2rem rgba(46, 125, 50, 0.15);
    }
    
    .table {
        margin-bottom: 0;
    }
    
    .table thead th {
        border-top: none;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        color: #495057;
        font-weight: 700;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 16px;
        border-bottom: 2px solid #dee2e6;
    }
    
    .table tbody tr {
        transition: all 0.2s;
        border-bottom: 1px solid #f1f3f4;
    }
    
    .table tbody tr:hover {
        background-color: #f8f9fa;
        transform: scale(1.01);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }
    
    .table tbody td {
        padding: 16px;
        vertical-align: middle;
    }
    
    .scope-badge {
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 700;
        display: inline-block;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .scope-1 {
        background: linear-gradient(135deg, rgba(46, 125, 50, 0.15) 0%, rgba(76, 175, 80, 0.15) 100%);
        color: #2e7d32;
        border: 1px solid rgba(46, 125, 50, 0.2);
    }
    
    .scope-2 {
        background: linear-gradient(135deg, rgba(3, 169, 244, 0.15) 0%, rgba(2, 119, 189, 0.15) 100%);
        color: #0277bd;
        border: 1px solid rgba(3, 169, 244, 0.2);
    }
    
    .scope-3 {
        background: linear-gradient(135deg, rgba(245, 124, 0, 0.15) 0%, rgba(255, 152, 0, 0.15) 100%);
        color: #f57c00;
        border: 1px solid rgba(245, 124, 0, 0.2);
    }
    
    .badge {
        padding: 6px 12px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.75rem;
    }
    
    .pagination .page-link {
        border-radius: 8px;
        margin: 0 4px;
        border: 1px solid #dee2e6;
        color: #495057;
        transition: all 0.3s;
    }
    
    .pagination .page-link:hover {
        background: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%);
        color: white;
        border-color: #2e7d32;
        transform: translateY(-2px);
    }
    
    .pagination .page-item.active .page-link {
        background: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%);
        border-color: #2e7d32;
        box-shadow: 0 2px 8px rgba(46, 125, 50, 0.3);
    }
    
    /* Enhanced Footer */
    .footer {
        text-align: center;
        padding: 32px 20px;
        color: #6c757d;
        font-size: 0.9rem;
        margin-top: 40px;
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        border-radius: 16px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }
    
    .footer p {
        margin: 8px 0;
    }
    
    /* Smooth Animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .kpi-card {
        animation: fadeInUp 0.6s ease-out;
    }
    
    .kpi-card:nth-child(1) { animation-delay: 0.1s; }
    .kpi-card:nth-child(2) { animation-delay: 0.2s; }
    .kpi-card:nth-child(3) { animation-delay: 0.3s; }
    .kpi-card:nth-child(4) { animation-delay: 0.4s; }
    
    .chart-container {
        animation: fadeInUp 0.6s ease-out;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .kpi-icon {
            width: 60px;
            height: 60px;
            font-size: 24px;
        }
        
        .kpi-value {
            font-size: 1.75rem;
        }
        
        .filters-section,
        .chart-container,
        .data-table-section {
            padding: 20px;
        }
    }
</style>
@endpush

@section('content')
    <!-- Main Content -->
    <div id="content">

       @include('layouts.top-nav') 
        <!-- KPI Summary Cards -->
        <div class="row mt-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card kpi-card card-total">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="kpi-label">TOTAL EMISSIONS</div>
                                <div class="kpi-value">{{ number_format($totalEmissions, 2) }} <span class="fs-6" style="-webkit-text-fill-color: #6c757d;">tCO₂e</span></div>
                                <div class="kpi-change {{ $percentageChange >= 0 ? 'change-positive' : 'change-negative' }}">
                                    <i class="fas fa-arrow-{{ $percentageChange >= 0 ? 'up' : 'down' }} me-1"></i> {{ number_format(abs($percentageChange), 1) }}% from last month
                                </div>
                            </div>
                            <div class="kpi-icon icon-total">
                                <i class="fas fa-globe-europe"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card kpi-card card-scope1">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="kpi-label">SCOPE 1 EMISSIONS</div>
                                <div class="kpi-value">{{ number_format($scope1Emissions, 2) }} <span class="fs-6" style="-webkit-text-fill-color: #6c757d;">tCO₂e</span></div>
                                <div class="kpi-change {{ $scope1Change >= 0 ? 'change-positive' : 'change-negative' }}">
                                    <i class="fas fa-arrow-{{ $scope1Change >= 0 ? 'up' : 'down' }} me-1"></i> {{ number_format(abs($scope1Change), 1) }}% from last month
                                </div>
                            </div>
                            <div class="kpi-icon icon-scope1">
                                <i class="fas fa-industry"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card kpi-card card-scope2">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="kpi-label">SCOPE 2 EMISSIONS</div>
                                <div class="kpi-value">{{ number_format($scope2Emissions, 2) }} <span class="fs-6" style="-webkit-text-fill-color: #6c757d;">tCO₂e</span></div>
                                <div class="kpi-change {{ $scope2Change >= 0 ? 'change-positive' : 'change-negative' }}">
                                    <i class="fas fa-arrow-{{ $scope2Change >= 0 ? 'up' : 'down' }} me-1"></i> {{ number_format(abs($scope2Change), 1) }}% from last month
                                </div>
                            </div>
                            <div class="kpi-icon icon-scope2">
                                <i class="fas fa-bolt"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card kpi-card card-scope3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="kpi-label">SCOPE 3 EMISSIONS</div>
                                <div class="kpi-value">{{ number_format($scope3Emissions, 2) }} <span class="fs-6" style="-webkit-text-fill-color: #6c757d;">tCO₂e</span></div>
                                <div class="kpi-change {{ $scope3Change >= 0 ? 'change-positive' : 'change-negative' }}">
                                    <i class="fas fa-arrow-{{ $scope3Change >= 0 ? 'up' : 'down' }} me-1"></i> {{ number_format(abs($scope3Change), 1) }}% from last month
                                </div>
                            </div>
                            <div class="kpi-icon icon-scope3">
                                <i class="fas fa-truck"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filters Section -->
        <div class="filters-section">
            <h5 class="mb-3"><i class="fas fa-filter me-2"></i>Filter Emissions Data</h5>
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="filter-label"><i class="fas fa-calendar-alt me-2"></i>Date Range</div>
                    <select class="form-select" id="dateRangeFilter">
                        <option value="12" {{ $dateRange == '12' ? 'selected' : '' }}>Last 12 Months</option>
                        <option value="ytd" {{ $dateRange == 'ytd' ? 'selected' : '' }}>Year to Date</option>
                        <option value="3" {{ $dateRange == '3' ? 'selected' : '' }}>Last Quarter</option>
                        <option value="custom" {{ $dateRange == 'custom' ? 'selected' : '' }}>Custom Range</option>
                    </select>
                    <div id="customDateRangeWrap" class="mt-2" style="display: none;">
                        <div class="row g-2">
                            <div class="col-6">
                                <input
                                    type="date"
                                    class="form-control"
                                    id="customStartDate"
                                    value="{{ request('start_date', '') }}"
                                    aria-label="Start date"
                                >
                            </div>
                            <div class="col-6">
                                <input
                                    type="date"
                                    class="form-control"
                                    id="customEndDate"
                                    value="{{ request('end_date', '') }}"
                                    aria-label="End date"
                                >
                            </div>
                        </div>
                        <div class="small text-muted mt-1">
                            Select start and end date (inclusive).
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="filter-label"><i class="fas fa-building me-2"></i>Facility</div>
                    <select class="form-select" id="facilityFilter">
                        <option value="" {{ empty($facilityFilter) ? 'selected' : '' }}>All Facilities</option>
                        @foreach($facilities as $facility)
                            <option value="{{ $facility->id }}" {{ $facilityFilter == $facility->id ? 'selected' : '' }}>{{ $facility->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="filter-label"><i class="fas fa-sitemap me-2"></i>Department</div>
                    <select class="form-select" id="departmentFilter">
                        <option value="" {{ empty($departmentFilter) ? 'selected' : '' }}>All Departments</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ $departmentFilter == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="filter-label"><i class="fas fa-tags me-2"></i>Emission Category</div>
                    <select class="form-select" id="categoryFilter">
                        <option value="" {{ empty($categoryFilter) ? 'selected' : '' }}>All Categories</option>
                        @foreach($emissionCategories as $category)
                            <option value="{{ $category }}" {{ $categoryFilter == $category ? 'selected' : '' }}>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-3">
                <button type="button" class="btn btn-outline-secondary" id="resetFiltersBtn">
                    <i class="fas fa-redo me-2"></i>Reset Filters
                </button>
                <button type="button" class="btn btn-success" id="applyFiltersBtn">
                    <i class="fas fa-filter me-2"></i>Apply Filters
                </button>
            </div>
        </div>
        
        <!-- Charts Section -->
        <div class="row">
            <!-- Monthly Trend Chart -->
            <div class="col-lg-8 mb-4">
                <div class="chart-container">
                    <div class="chart-title">
                        <i class="fas fa-chart-line me-2"></i>Monthly GHG Emissions Trend
                    </div>
                    <div id="trendChart"></div>
                </div>
            </div>
            
            <!-- Emissions by Scope Chart -->
            <div class="col-lg-4 mb-4">
                <div class="chart-container">
                    <div class="chart-title">
                        <i class="fas fa-chart-pie me-2"></i>Emissions by Scope
                    </div>
                    <div id="scopeChart"></div>
                </div>
            </div>
            
            <!-- Emissions by Source Chart -->
            <div class="col-lg-12 mb-4">
                <div class="chart-container">
                    <div class="chart-title">
                        <i class="fas fa-chart-bar me-2"></i>Emissions by Source
                    </div>
                    <div id="sourceChart"></div>
                </div>
            </div>
        </div>
        
        <!-- Data Table Preview -->
        <div class="data-table-section">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5><i class="fas fa-table me-2"></i>Recent Emissions Data</h5>
                <div class="input-group" style="width: 300px;">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" placeholder="Search emissions data...">
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th><i class="fas fa-calendar me-2"></i>Date</th>
                            <th><i class="fas fa-layer-group me-2"></i>Scope</th>
                            <th><i class="fas fa-fire me-2"></i>Source</th>
                            <th><i class="fas fa-chart-area me-2"></i>CO₂e Value</th>
                            <th><i class="fas fa-building me-2"></i>Facility</th>
                            <th><i class="fas fa-info-circle me-2"></i>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentRecords as $record)
                        <tr>
                            <td><strong>{{ $record->entry_date ? \Carbon\Carbon::parse($record->entry_date)->format('Y-m-d') : 'N/A' }}</strong></td>
                            <td><span class="scope-badge scope-{{ $record->scope }}">Scope {{ $record->scope }}</span></td>
                            <td>{{ $record->emission_source ?? 'N/A' }}</td>
                            <td><strong>{{ number_format($record->co2e_value, 2) }} tCO₂e</strong></td>
                            <td>{{ $record->facility ?? 'N/A' }}</td>
                            <td>
                                @if($record->status === 'active')
                                    <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Active</span>
                                @else
                                    <span class="badge bg-secondary"><i class="fas fa-file-alt me-1"></i>Draft</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-3x mb-3 d-block" style="opacity: 0.3;"></i>
                                No emission records found
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mt-4">
                <div class="text-muted">
                    <i class="fas fa-info-circle me-2"></i>Showing {{ $recentRecords->count() }} of {{ $totalRecords }} records
                </div>
                <nav aria-label="Table pagination">
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item disabled">
                            <a class="page-link" href="#" tabindex="-1">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        </li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item">
                            <a class="page-link" href="#">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>
                <i class="fas fa-leaf me-2" style="color: #2e7d32;"></i>
                <strong>GHG Emissions Monitoring System v2.1</strong> • Data last updated: {{ now()->format('F d, Y') }}
            </p>
            <p class="small">
                © {{ date('Y') }} Sustainability Analytics. All emissions data is measured in metric tons of CO₂ equivalent (tCO₂e).
            </p>
            <p class="small" style="margin-top: 12px;">
                Powered by <a href="https://altayaboon.com/" target="_blank" rel="noopener noreferrer" style="color: #2e7d32; text-decoration: none; font-weight: 600;">AL-TAYABOON INFORMATION TECHNOLOGY</a>
            </p>
        </div>
    </div>
    
    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.js"></script>
    
    <script>
        // Initialize charts when the DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Chart 1: Monthly Trend - Enhanced for Clarity
            const trendOptions = {
                series: [{
                    name: 'Total Emissions',
                    data: @json($monthlyTrend)
                }, {
                    name: 'Target',
                    data: @json($monthlyTarget)
                }],
                chart: {
                    height: 420,
                    type: 'line',
                    zoom: {
                        enabled: false
                    },
                    toolbar: {
                        show: true,
                        tools: {
                            download: true,
                            selection: false,
                            zoom: false,
                            zoomin: false,
                            zoomout: false,
                            pan: false,
                            reset: false
                        }
                    },
                    fontFamily: 'Segoe UI, Tahoma, Geneva, Verdana, sans-serif',
                    background: 'transparent'
                },
                colors: ['#00C853', '#FF5722'],
                dataLabels: {
                    enabled: true,
                    style: {
                        fontSize: '10px',
                        fontWeight: 700,
                        colors: ['#fff', '#fff'],
                        fontFamily: 'Segoe UI, Tahoma, Geneva, Verdana, sans-serif'
                    },
                    background: {
                        enabled: true,
                        foreColor: '#fff',
                        padding: 5,
                        borderRadius: 6,
                        borderWidth: 2,
                        borderColor: ['#00C853', '#FF5722'],
                        opacity: 0.95,
                        dropShadow: {
                            enabled: true,
                            top: 1,
                            left: 1,
                            blur: 2,
                            opacity: 0.3
                        }
                    },
                    offsetY: -10,
                    formatter: function(val) {
                        return val.toFixed(1);
                    }
                },
                stroke: {
                    curve: 'smooth',
                    width: [5, 4],
                    lineCap: 'round',
                    dashArray: [0, 8]
                },
                markers: {
                    size: [8, 7],
                    strokeWidth: [3, 3],
                    strokeColors: ['#fff', '#fff'],
                    fillColors: ['#00C853', '#FF5722'],
                    hover: {
                        size: [10, 9],
                        sizeOffset: 2
                    },
                    radius: 5
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shade: 'light',
                        type: 'vertical',
                        shadeIntensity: 0.5,
                        gradientToColors: ['#4CAF50', '#FF7043'],
                        inverseColors: false,
                        opacityFrom: 0.6,
                        opacityTo: 0.2,
                        stops: [0, 50, 100]
                    }
                },
                title: {
                    text: '',
                    align: 'left'
                },
                grid: {
                    borderColor: '#e0e0e0',
                    strokeDashArray: 3,
                    xaxis: {
                        lines: {
                            show: true
                        }
                    },
                    yaxis: {
                        lines: {
                            show: true
                        }
                    },
                    row: {
                        colors: ['transparent', 'rgba(0, 0, 0, 0.02)'],
                        opacity: 0.5
                    },
                    column: {
                        colors: ['transparent', 'rgba(0, 0, 0, 0.02)'],
                        opacity: 0.5
                    },
                    padding: {
                        top: 15,
                        right: 15,
                        bottom: 15,
                        left: 15
                    }
                },
                xaxis: {
                    categories: @json($months),
                    title: {
                        text: 'Month',
                        style: {
                            color: '#495057',
                            fontSize: '13px',
                            fontWeight: 700,
                            fontFamily: 'Segoe UI, Tahoma, Geneva, Verdana, sans-serif'
                        },
                        offsetY: 5
                    },
                    labels: {
                        style: {
                            colors: '#6c757d',
                            fontSize: '12px',
                            fontWeight: 600,
                            fontFamily: 'Segoe UI, Tahoma, Geneva, Verdana, sans-serif'
                        },
                        rotate: -45,
                        rotateAlways: false
                    },
                    axisBorder: {
                        show: true,
                        color: '#dee2e6',
                        offsetX: 0,
                        offsetY: 0
                    },
                    axisTicks: {
                        show: true,
                        color: '#dee2e6'
                    }
                },
                yaxis: {
                    title: {
                        text: 'Emissions (tCO₂e)',
                        style: {
                            color: '#495057',
                            fontSize: '13px',
                            fontWeight: 700,
                            fontFamily: 'Segoe UI, Tahoma, Geneva, Verdana, sans-serif'
                        },
                        offsetX: -5
                    },
                    labels: {
                        style: {
                            colors: '#6c757d',
                            fontSize: '12px',
                            fontWeight: 600,
                            fontFamily: 'Segoe UI, Tahoma, Geneva, Verdana, sans-serif'
                        },
                        formatter: function(val) {
                            return val.toFixed(0);
                        }
                    },
                    axisBorder: {
                        show: true,
                        color: '#dee2e6',
                        offsetX: 0,
                        offsetY: 0
                    },
                    axisTicks: {
                        show: true,
                        color: '#dee2e6'
                    }
                    @if(count($monthlyTrend) > 0)
                    ,min: {{ max(0, floor(min($monthlyTrend) * 0.85)) }}
                    ,max: {{ max(1, ceil(max($monthlyTrend) * 1.15)) }}
                    @endif
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right',
                    floating: false,
                    offsetY: 0,
                    offsetX: 0,
                    fontSize: '14px',
                    fontWeight: 700,
                    fontFamily: 'Segoe UI, Tahoma, Geneva, Verdana, sans-serif',
                    markers: {
                        width: 16,
                        height: 16,
                        radius: 8,
                        offsetX: -5,
                        offsetY: 0
                    },
                    itemMargin: {
                        horizontal: 20,
                        vertical: 8
                    },
                    labels: {
                        colors: '#495057',
                        useSeriesColors: false
                    }
                },
                tooltip: {
                    theme: 'light',
                    style: {
                        fontSize: '13px',
                        fontFamily: 'Segoe UI, Tahoma, Geneva, Verdana, sans-serif'
                    },
                    y: {
                        formatter: function(val) {
                            return val.toFixed(2) + " tCO₂e";
                        }
                    },
                    marker: {
                        show: true
                    },
                    fixed: {
                        enabled: false
                    },
                    followCursor: true,
                    intersect: false
                },
                @if(count($monthlyTarget) > 0 && array_sum($monthlyTarget) > 0)
                annotations: {
                    yaxis: [{
                        y: {{ array_sum($monthlyTarget) / count($monthlyTarget) }},
                        borderColor: '#FF5722',
                        borderWidth: 3,
                        borderDashArray: 8,
                        opacity: 0.7,
                        label: {
                            text: 'Avg Target',
                            style: {
                                color: '#fff',
                                background: '#FF5722',
                                fontSize: '10px',
                                fontWeight: 700,
                                padding: {
                                    left: 6,
                                    right: 6,
                                    top: 3,
                                    bottom: 3
                                }
                            },
                            offsetY: -8
                        }
                    }]
                }
                @endif
            };
            
            const trendChart = new ApexCharts(document.querySelector("#trendChart"), trendOptions);
            trendChart.render();
            
            // Chart 2: Emissions by Scope (use JSON so locale/decimals never break JS)
            const scopeSeries = @json([(float)$scope1Emissions, (float)$scope2Emissions, (float)$scope3Emissions]);
            const scopeTotal = {{ (float)$totalEmissions }};
            const scopeOptions = {
                series: scopeSeries,
                chart: {
                    type: 'donut',
                    height: 360,
                    dropShadow: {
                        enabled: true,
                        color: '#000',
                        top: 0,
                        left: 0,
                        blur: 10,
                        opacity: 0.1
                    }
                },
                colors: ['#2e7d32', '#0277bd', '#f57c00'],
                labels: ['Scope 1', 'Scope 2', 'Scope 3'],
                plotOptions: {
                    pie: {
                        donut: {
                            size: '70%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    showAlways: true,
                                    label: 'Total Emissions',
                                    color: '#6c757d',
                                    fontSize: '14px',
                                    fontWeight: 600,
                                    formatter: function() {
                                        return scopeTotal.toFixed(2);
                                    }
                                },
                                value: {
                                    fontSize: '28px',
                                    fontWeight: 700,
                                    color: '#2e7d32',
                                    formatter: function(val) {
                                        return val.toFixed(2);
                                    }
                                },
                                name: {
                                    show: true,
                                    fontSize: '12px',
                                    fontWeight: 600,
                                    color: '#6c757d',
                                    offsetY: -10
                                }
                            }
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    style: {
                        fontSize: '12px',
                        fontWeight: 600,
                        colors: ['#fff']
                    },
                    dropShadow: {
                        enabled: true,
                        top: 1,
                        left: 1,
                        blur: 1,
                        opacity: 0.8
                    }
                },
                legend: {
                    position: 'bottom',
                    horizontalAlign: 'center',
                    fontSize: '13px',
                    fontWeight: 600,
                    markers: {
                        width: 12,
                        height: 12,
                        radius: 6
                    },
                    itemMargin: {
                        horizontal: 10,
                        vertical: 5
                    }
                },
                tooltip: {
                    theme: 'light',
                    y: {
                        formatter: function(val) {
                            return val.toFixed(2) + " tCO₂e";
                        }
                    }
                },
                responsive: [{
                    breakpoint: 480,
                    options: {
                        chart: {
                            width: 200
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }]
            };
            
            const scopeChart = new ApexCharts(document.querySelector("#scopeChart"), scopeOptions);
            scopeChart.render();
            
            // Chart 3: Emissions by Source
            const sourceNames = @json($sourceNames ?? []);
            const sourceValues = @json($sourceValues ?? []);
            
            const sourceOptions = {
                series: [{
                    name: 'tCO₂e',
                    data: sourceValues.length > 0 ? sourceValues : [0]
                }],
                chart: {
                    type: 'bar',
                    height: 400,
                    toolbar: {
                        show: true,
                        tools: {
                            download: true,
                            selection: false,
                            zoom: false,
                            zoomin: false,
                            zoomout: false,
                            pan: false,
                            reset: false
                        }
                    },
                    dropShadow: {
                        enabled: true,
                        color: '#000',
                        top: 0,
                        left: 0,
                        blur: 10,
                        opacity: 0.1
                    }
                },
                plotOptions: {
                    bar: {
                        borderRadius: 8,
                        horizontal: true,
                        distributed: true,
                        barHeight: '70%',
                        dataLabels: {
                            position: 'center'
                        }
                    }
                },
                colors: ['#2e7d32', '#4caf50', '#81c784', '#0277bd', '#03a9f4', '#f57c00', '#ff9800', '#ffb74d', '#795548', '#a1887f'],
                dataLabels: {
                    enabled: sourceValues.length > 0,
                    formatter: function(val) {
                        return val.toFixed(2) + " tCO₂e";
                    },
                    style: {
                        fontSize: '11px',
                        fontWeight: 600,
                        colors: ['#fff']
                    },
                    offsetX: 0,
                    dropShadow: {
                        enabled: true,
                        top: 1,
                        left: 1,
                        blur: 1,
                        opacity: 0.8
                    }
                },
                xaxis: {
                    categories: sourceNames.length > 0 ? sourceNames : ['No data available'],
                    title: {
                        text: 'tCO₂e',
                        style: {
                            color: '#6c757d',
                            fontSize: '12px',
                            fontWeight: 600
                        }
                    },
                    labels: {
                        style: {
                            colors: '#6c757d',
                            fontSize: '11px'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            fontSize: '12px',
                            fontWeight: 600,
                            colors: '#6c757d'
                        }
                    }
                },
                grid: {
                    borderColor: '#e7e7e7',
                    strokeDashArray: 3,
                    xaxis: {
                        lines: {
                            show: true
                        }
                    },
                    yaxis: {
                        lines: {
                            show: false
                        }
                    },
                    padding: {
                        top: 10,
                        right: 10,
                        bottom: 10,
                        left: 10
                    }
                },
                title: {
                    text: '',
                    align: 'left'
                },
                tooltip: {
                    theme: 'light',
                    y: {
                        formatter: function(val) {
                            return val.toFixed(2) + " tCO₂e";
                        }
                    }
                }
            };
            
            const sourceChart = new ApexCharts(document.querySelector("#sourceChart"), sourceOptions);
            sourceChart.render();
            
            // Sidebar toggle for mobile
            document.getElementById('sidebarCollapse').addEventListener('click', function() {
                document.getElementById('sidebar').classList.toggle('active');
                document.getElementById('content').classList.toggle('active');
            });
            
            // Update active menu item
            const menuItems = document.querySelectorAll('.sidebar-menu a');
            menuItems.forEach(item => {
                item.addEventListener('click', function() {
                    menuItems.forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                });
            });
            
            // Apply filters button functionality
            document.getElementById('applyFiltersBtn').addEventListener('click', function() {
                const dateRange = document.getElementById('dateRangeFilter').value;
                const facility = document.getElementById('facilityFilter').value;
                const department = document.getElementById('departmentFilter').value;
                const category = document.getElementById('categoryFilter').value;
                const customStartDate = document.getElementById('customStartDate') ? document.getElementById('customStartDate').value : '';
                const customEndDate = document.getElementById('customEndDate') ? document.getElementById('customEndDate').value : '';
                
                // Build query string
                const params = new URLSearchParams();
                if (dateRange) params.append('date_range', dateRange);
                if (facility) params.append('facility', facility);
                if (department) params.append('department', department);
                if (category) params.append('category', category);
                if (dateRange === 'custom') {
                    if (customStartDate) params.append('start_date', customStartDate);
                    if (customEndDate) params.append('end_date', customEndDate);
                }
                
                // Redirect with filter parameters
                const url = '{{ route("home") }}' + (params.toString() ? '?' + params.toString() : '');
                window.location.href = url;
            });
            
            // Reset filters button functionality
            document.getElementById('resetFiltersBtn').addEventListener('click', function() {
                // Redirect to home without any parameters
                window.location.href = '{{ route("home") }}';
            });

            // Custom date range UI toggle
            const dateRangeFilterEl = document.getElementById('dateRangeFilter');
            const customDateRangeWrap = document.getElementById('customDateRangeWrap');
            const toggleCustomRange = () => {
                if (!dateRangeFilterEl || !customDateRangeWrap) return;
                customDateRangeWrap.style.display = (dateRangeFilterEl.value === 'custom') ? 'block' : 'none';
            };
            toggleCustomRange();
            dateRangeFilterEl.addEventListener('change', toggleCustomRange);
        });
    </script>
@endsection

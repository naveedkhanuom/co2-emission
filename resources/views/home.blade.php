@extends('layouts.app')

@section('content')
    <!-- Main Content -->
    <div id="content">

       @include('layouts.top-nav') 
        <!-- KPI Summary Cards -->
        <div class="row mt-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card kpi-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="kpi-label">TOTAL EMISSIONS</div>
                                <div class="kpi-value">{{ number_format($totalEmissions, 2) }} <span class="fs-6">tCO₂e</span></div>
                                <div class="kpi-change {{ $percentageChange >= 0 ? 'change-positive' : 'change-negative' }}">
                                    <i class="fas fa-arrow-{{ $percentageChange >= 0 ? 'up' : 'down' }} me-1"></i> {{ number_format(abs($percentageChange), 1) }}% from last month
                                </div>
                            </div>
                            <div class="kpi-icon" style="background-color: var(--primary-green);">
                                <i class="fas fa-globe-europe"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card kpi-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="kpi-label">SCOPE 1 EMISSIONS</div>
                                <div class="kpi-value">{{ number_format($scope1Emissions, 2) }} <span class="fs-6">tCO₂e</span></div>
                                <div class="kpi-change {{ $scope1Change >= 0 ? 'change-positive' : 'change-negative' }}">
                                    <i class="fas fa-arrow-{{ $scope1Change >= 0 ? 'up' : 'down' }} me-1"></i> {{ number_format(abs($scope1Change), 1) }}% from last month
                                </div>
                            </div>
                            <div class="kpi-icon" style="background-color: var(--light-green);">
                                <i class="fas fa-industry"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card kpi-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="kpi-label">SCOPE 2 EMISSIONS</div>
                                <div class="kpi-value">{{ number_format($scope2Emissions, 2) }} <span class="fs-6">tCO₂e</span></div>
                                <div class="kpi-change {{ $scope2Change >= 0 ? 'change-positive' : 'change-negative' }}">
                                    <i class="fas fa-arrow-{{ $scope2Change >= 0 ? 'up' : 'down' }} me-1"></i> {{ number_format(abs($scope2Change), 1) }}% from last month
                                </div>
                            </div>
                            <div class="kpi-icon" style="background-color: var(--primary-blue);">
                                <i class="fas fa-bolt"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card kpi-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="kpi-label">SCOPE 3 EMISSIONS</div>
                                <div class="kpi-value">{{ number_format($scope3Emissions, 2) }} <span class="fs-6">tCO₂e</span></div>
                                <div class="kpi-change {{ $scope3Change >= 0 ? 'change-positive' : 'change-negative' }}">
                                    <i class="fas fa-arrow-{{ $scope3Change >= 0 ? 'up' : 'down' }} me-1"></i> {{ number_format(abs($scope3Change), 1) }}% from last month
                                </div>
                            </div>
                            <div class="kpi-icon" style="background-color: var(--warning-orange);">
                                <i class="fas fa-truck"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filters Section -->
        <div class="filters-section">
            <h5 class="mb-3">Filter Emissions Data</h5>
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="filter-label">Date Range</div>
                    <select class="form-select" id="dateRangeFilter">
                        <option value="12" {{ $dateRange == '12' ? 'selected' : '' }}>Last 12 Months</option>
                        <option value="ytd" {{ $dateRange == 'ytd' ? 'selected' : '' }}>Year to Date</option>
                        <option value="3" {{ $dateRange == '3' ? 'selected' : '' }}>Last Quarter</option>
                        <option value="custom" {{ $dateRange == 'custom' ? 'selected' : '' }}>Custom Range</option>
                    </select>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="filter-label">Facility</div>
                    <select class="form-select" id="facilityFilter">
                        <option value="" {{ empty($facilityFilter) ? 'selected' : '' }}>All Facilities</option>
                        @foreach($facilities as $facility)
                            <option value="{{ $facility->id }}" {{ $facilityFilter == $facility->id ? 'selected' : '' }}>{{ $facility->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="filter-label">Department</div>
                    <select class="form-select" id="departmentFilter">
                        <option value="" {{ empty($departmentFilter) ? 'selected' : '' }}>All Departments</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ $departmentFilter == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="filter-label">Emission Category</div>
                    <select class="form-select" id="categoryFilter">
                        <option value="" {{ empty($categoryFilter) ? 'selected' : '' }}>All Categories</option>
                        @foreach($emissionCategories as $category)
                            <option value="{{ $category }}" {{ $categoryFilter == $category ? 'selected' : '' }}>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-2">
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
                    <div class="chart-title">Monthly GHG Emissions Trend</div>
                    <div id="trendChart"></div>
                </div>
            </div>
            
            <!-- Emissions by Scope Chart -->
            <div class="col-lg-4 mb-4">
                <div class="chart-container">
                    <div class="chart-title">Emissions by Scope</div>
                    <div id="scopeChart"></div>
                </div>
            </div>
            
            <!-- Emissions by Source Chart -->
            <div class="col-lg-12 mb-4">
                <div class="chart-container">
                    <div class="chart-title">Emissions by Source</div>
                    <div id="sourceChart"></div>
                </div>
            </div>
        </div>
        
        <!-- Data Table Preview -->
        <div class="data-table-section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Recent Emissions Data</h5>
                <div class="input-group" style="width: 300px;">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" placeholder="Search emissions data...">
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Scope</th>
                            <th>Source</th>
                            <th>CO₂e Value</th>
                            <th>Facility</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentRecords as $record)
                        <tr>
                            <td>{{ $record->entry_date ? \Carbon\Carbon::parse($record->entry_date)->format('Y-m-d') : 'N/A' }}</td>
                            <td><span class="scope-badge scope-{{ $record->scope }}">Scope {{ $record->scope }}</span></td>
                            <td>{{ $record->emission_source ?? 'N/A' }}</td>
                            <td>{{ number_format($record->co2e_value, 2) }} tCO₂e</td>
                            <td>{{ $record->facility ?? 'N/A' }}</td>
                            <td>
                                @if($record->status === 'active')
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Draft</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No emission records found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted">Showing {{ $recentRecords->count() }} of {{ $totalRecords }} records</div>
                <nav aria-label="Table pagination">
                    <ul class="pagination pagination-sm">
                        <li class="page-item disabled">
                            <a class="page-link" href="#" tabindex="-1">Previous</a>
                        </li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item">
                            <a class="page-link" href="#">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>GHG Emissions Monitoring System v2.1 • Data last updated: {{ now()->format('F d, Y') }}</p>
            <p class="small">© {{ date('Y') }} Sustainability Analytics. All emissions data is measured in metric tons of CO₂ equivalent (tCO₂e).</p>
        </div>
    </div>
    
    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.js"></script>
    
    <script>
        // Initialize charts when the DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Chart 1: Monthly Trend
            const trendOptions = {
                series: [{
                    name: 'Total Emissions',
                    data: @json($monthlyTrend)
                }, {
                    name: 'Target',
                    data: @json($monthlyTarget)
                }],
                chart: {
                    height: 350,
                    type: 'line',
                    zoom: {
                        enabled: false
                    },
                    toolbar: {
                        show: true
                    }
                },
                colors: ['#2e7d32', '#f57c00'],
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                title: {
                    text: '',
                    align: 'left'
                },
                grid: {
                    row: {
                        colors: ['#f3f3f3', 'transparent'],
                        opacity: 0.5
                    },
                },
                xaxis: {
                    categories: @json($months),
                    title: {
                        text: 'Month'
                    }
                },
                yaxis: {
                    title: {
                        text: 'tCO₂e'
                    }
                    @if(count($monthlyTrend) > 0)
                    ,min: {{ floor(min($monthlyTrend) * 0.9) }}
                    ,max: {{ ceil(max($monthlyTrend) * 1.1) }}
                    @endif
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right',
                    floating: true,
                    offsetY: -25,
                    offsetX: -5
                }
            };
            
            const trendChart = new ApexCharts(document.querySelector("#trendChart"), trendOptions);
            trendChart.render();
            
            // Chart 2: Emissions by Scope
            const scopeOptions = {
                series: [{{ number_format($scope1Emissions, 2) }}, {{ number_format($scope2Emissions, 2) }}, {{ number_format($scope3Emissions, 2) }}],
                chart: {
                    type: 'donut',
                    height: 320
                },
                colors: ['#2e7d32', '#03a9f4', '#f57c00'],
                labels: ['Scope 1', 'Scope 2', 'Scope 3'],
                plotOptions: {
                    pie: {
                        donut: {
                            size: '65%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total',
                                    color: '#5f6368',
                                    fontSize: '16px'
                                },
                                value: {
                                    fontSize: '24px',
                                    fontWeight: 'bold',
                                    color: '#3c4043'
                                }
                            }
                        }
                    }
                },
                dataLabels: {
                    enabled: false
                },
                legend: {
                    position: 'bottom',
                    horizontalAlign: 'center'
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
                    height: 350,
                    toolbar: {
                        show: true
                    }
                },
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        horizontal: true,
                        distributed: true
                    }
                },
                colors: ['#2e7d32', '#4caf50', '#81c784', '#0277bd', '#03a9f4', '#f57c00', '#ff9800', '#ffb74d', '#795548', '#a1887f'],
                dataLabels: {
                    enabled: sourceValues.length > 0,
                    formatter: function(val) {
                        return val + " tCO₂e";
                    },
                    style: {
                        fontSize: '12px',
                        colors: ['#fff']
                    }
                },
                xaxis: {
                    categories: sourceNames.length > 0 ? sourceNames : ['No data available'],
                    title: {
                        text: 'tCO₂e'
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            fontSize: '13px'
                        }
                    }
                },
                title: {
                    text: '',
                    align: 'left'
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val + " tCO₂e";
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
                
                // Build query string
                const params = new URLSearchParams();
                if (dateRange) params.append('date_range', dateRange);
                if (facility) params.append('facility', facility);
                if (department) params.append('department', department);
                if (category) params.append('category', category);
                
                // Redirect with filter parameters
                const url = '{{ route("home") }}' + (params.toString() ? '?' + params.toString() : '');
                window.location.href = url;
            });
            
            // Reset filters button functionality
            document.getElementById('resetFiltersBtn').addEventListener('click', function() {
                // Redirect to home without any parameters
                window.location.href = '{{ route("home") }}';
            });
        });
    </script>
@endsection

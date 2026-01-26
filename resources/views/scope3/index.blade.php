@extends('layouts.app')

@section('title', 'Scope 3 Emissions')
@section('page-title', 'Scope 3 Emissions')

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="container mt-4">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Total Scope 3</h6>
                                <h3 class="mb-0" id="totalScope3">0</h3>
                                <small class="text-muted">tCO2e</small>
                            </div>
                            <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                                <i class="fas fa-layer-group fa-2x text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Upstream</h6>
                                <h3 class="mb-0" id="upstreamTotal">0</h3>
                                <small class="text-muted">tCO2e</small>
                            </div>
                            <div class="bg-info bg-opacity-10 rounded-circle p-3">
                                <i class="fas fa-arrow-up fa-2x text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Downstream</h6>
                                <h3 class="mb-0" id="downstreamTotal">0</h3>
                                <small class="text-muted">tCO2e</small>
                            </div>
                            <div class="bg-success bg-opacity-10 rounded-circle p-3">
                                <i class="fas fa-arrow-down fa-2x text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Data Quality Score</h6>
                                <h3 class="mb-0" id="qualityScore">0</h3>
                                <small class="text-muted">/ 100</small>
                            </div>
                            <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                                <i class="fas fa-check-circle fa-2x text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Emissions by Category</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="categoryChart" height="300"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Data Quality Breakdown</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="qualityChart" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Categories Section -->
        <div class="row">
            <!-- Upstream Categories -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-arrow-up text-info me-2"></i>Upstream Categories
                        </h5>
                        <span class="badge bg-info">{{ isset($categories['upstream']) ? $categories['upstream']->count() : 0 }} Categories</span>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            @if(isset($categories['upstream']))
                                @foreach($categories['upstream'] as $category)
                                    <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center category-item" 
                                         data-category-id="{{ $category->id }}"
                                         style="cursor: pointer;">
                                        <div>
                                            <h6 class="mb-1">{{ $category->code }} - {{ $category->name }}</h6>
                                            <small class="text-muted">{{ Str::limit($category->description, 80) }}</small>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold text-primary">
                                                {{ number_format(($emissionsByCategory[$category->id] ?? 0) / 1000, 2) }} tCO2e
                                            </div>
                                            <button class="btn btn-sm btn-outline-primary mt-2 viewCategoryBtn" 
                                                    data-id="{{ $category->id }}">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="list-group-item text-center text-muted">
                                    No upstream categories found
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Downstream Categories -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-arrow-down text-success me-2"></i>Downstream Categories
                        </h5>
                        <span class="badge bg-success">{{ isset($categories['downstream']) ? $categories['downstream']->count() : 0 }} Categories</span>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            @if(isset($categories['downstream']))
                                @foreach($categories['downstream'] as $category)
                                    <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center category-item" 
                                         data-category-id="{{ $category->id }}"
                                         style="cursor: pointer;">
                                        <div>
                                            <h6 class="mb-1">{{ $category->code }} - {{ $category->name }}</h6>
                                            <small class="text-muted">{{ Str::limit($category->description, 80) }}</small>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold text-success">
                                                {{ number_format(($emissionsByCategory[$category->id] ?? 0) / 1000, 2) }} tCO2e
                                            </div>
                                            <button class="btn btn-sm btn-outline-success mt-2 viewCategoryBtn" 
                                                    data-id="{{ $category->id }}">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="list-group-item text-center text-muted">
                                    No downstream categories found
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calculation Method Breakdown -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Calculation Methods</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="methodChart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Category Details Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Category Details</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="categoryModalBody">
                <!-- Content will be loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .category-item:hover {
        background-color: #f8f9fa;
        transform: translateX(5px);
        transition: all 0.2s ease;
    }
    
    .card {
        border-radius: 0.5rem;
    }
    
    .card-header {
        border-bottom: 2px solid #f0f0f0;
    }
</style>
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
    $(document).ready(function() {
        // Load summary data
        loadSummary();

        // View Category Details
        $('.viewCategoryBtn').on('click', function(e) {
            e.stopPropagation();
            var categoryId = $(this).data('id');
            loadCategoryDetails(categoryId);
        });

        $('.category-item').on('click', function() {
            var categoryId = $(this).data('category-id');
            loadCategoryDetails(categoryId);
        });

        function loadSummary() {
            $.ajax({
                url: '{{ route("scope3.summary") }}',
                method: 'GET',
                success: function(response) {
                    // Update summary cards
                    $('#totalScope3').text((response.total_scope3 / 1000).toFixed(2));
                    $('#upstreamTotal').text((response.upstream_total / 1000).toFixed(2));
                    $('#downstreamTotal').text((response.downstream_total / 1000).toFixed(2));

                    // Calculate quality score
                    var primary = response.by_data_quality.find(q => q.quality === 'primary')?.total || 0;
                    var secondary = response.by_data_quality.find(q => q.quality === 'secondary')?.total || 0;
                    var total = response.total_scope3 || 1;
                    var score = ((primary * 100 + secondary * 50) / total).toFixed(1);
                    $('#qualityScore').text(score);

                    // Category Chart
                    var categoryLabels = response.by_category.map(c => c.category_code + ' - ' + c.category_name);
                    var categoryData = response.by_category.map(c => (c.total / 1000).toFixed(2));

                    var categoryCtx = document.getElementById('categoryChart').getContext('2d');
                    new Chart(categoryCtx, {
                        type: 'bar',
                        data: {
                            labels: categoryLabels,
                            datasets: [{
                                label: 'Emissions (tCO2e)',
                                data: categoryData,
                                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'tCO2e'
                                    }
                                },
                                x: {
                                    ticks: {
                                        maxRotation: 45,
                                        minRotation: 45
                                    }
                                }
                            }
                        }
                    });

                    // Quality Chart
                    var qualityLabels = response.by_data_quality.map(q => q.quality.charAt(0).toUpperCase() + q.quality.slice(1));
                    var qualityData = response.by_data_quality.map(q => (q.total / 1000).toFixed(2));
                    var qualityColors = ['#28a745', '#ffc107', '#6c757d'];

                    var qualityCtx = document.getElementById('qualityChart').getContext('2d');
                    new Chart(qualityCtx, {
                        type: 'doughnut',
                        data: {
                            labels: qualityLabels,
                            datasets: [{
                                data: qualityData,
                                backgroundColor: qualityColors,
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });

                    // Method Chart
                    var methodLabels = response.by_calculation_method.map(m => m.method ? m.method.replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'Unknown');
                    var methodData = response.by_calculation_method.map(m => (m.total / 1000).toFixed(2));

                    var methodCtx = document.getElementById('methodChart').getContext('2d');
                    new Chart(methodCtx, {
                        type: 'pie',
                        data: {
                            labels: methodLabels,
                            datasets: [{
                                data: methodData,
                                backgroundColor: ['#007bff', '#28a745', '#ffc107'],
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                },
                error: function(xhr) {
                    console.error('Error loading summary:', xhr);
                }
            });
        }

        function loadCategoryDetails(categoryId) {
            $.ajax({
                url: '/scope3/category/' + categoryId,
                method: 'GET',
                success: function(response) {
                    var html = '<div class="row g-3">';
                    html += '<div class="col-md-12"><h5>' + response.category.code + ' - ' + response.category.name + '</h5></div>';
                    html += '<div class="col-md-12"><p>' + response.category.description + '</p></div>';
                    
                    html += '<div class="col-md-4"><div class="card bg-light"><div class="card-body text-center">';
                    html += '<h3>' + (response.total / 1000).toFixed(2) + '</h3><small>Total Emissions (tCO2e)</small></div></div></div>';
                    
                    html += '<div class="col-md-4"><div class="card bg-light"><div class="card-body text-center">';
                    html += '<h3>' + response.primary_data_percentage + '%</h3><small>Primary Data</small></div></div></div>';
                    
                    html += '<div class="col-md-4"><div class="card bg-light"><div class="card-body text-center">';
                    html += '<h3>' + response.emissions.length + '</h3><small>Records</small></div></div></div>';

                    if (response.by_supplier && response.by_supplier.length > 0) {
                        html += '<div class="col-md-12"><h6 class="mt-3">Top Suppliers</h6><table class="table table-sm">';
                        html += '<thead><tr><th>Supplier</th><th>Emissions (tCO2e)</th></tr></thead><tbody>';
                        response.by_supplier.forEach(function(item) {
                            html += '<tr><td>' + (item.supplier?.name || 'N/A') + '</td><td>' + (item.total / 1000).toFixed(2) + '</td></tr>';
                        });
                        html += '</tbody></table></div>';
                    }

                    if (response.emissions && response.emissions.length > 0) {
                        html += '<div class="col-md-12"><h6 class="mt-3">Recent Records</h6><div class="table-responsive">';
                        html += '<table class="table table-sm table-hover"><thead><tr><th>Date</th><th>Source</th><th>Emissions (tCO2e)</th><th>Quality</th></tr></thead><tbody>';
                        response.emissions.slice(0, 10).forEach(function(emission) {
                            html += '<tr>';
                            html += '<td>' + new Date(emission.entry_date).toLocaleDateString() + '</td>';
                            html += '<td>' + (emission.emission_source || 'N/A') + '</td>';
                            html += '<td>' + (emission.co2e_value / 1000).toFixed(2) + '</td>';
                            html += '<td><span class="badge bg-' + (emission.data_quality === 'primary' ? 'success' : emission.data_quality === 'secondary' ? 'warning' : 'secondary') + '">' + emission.data_quality + '</span></td>';
                            html += '</tr>';
                        });
                        html += '</tbody></table></div></div>';
                    }

                    html += '</div>';
                    $('#categoryModalBody').html(html);
                    $('#categoryModal').modal('show');
                },
                error: function(xhr) {
                    alert('Error loading category details');
                }
            });
        }

        // Auto-hide success message
        setTimeout(function() {
            $('.alert-success').fadeOut('slow');
        }, 5000);
    });
</script>
@endpush

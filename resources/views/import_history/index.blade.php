@extends('layouts.app')

@section('title', 'Import History')
@section('page-title', 'Import History')

@section('content')
    <!-- Main Content -->
    <div id="content">
        @include('layouts.top-nav')
        
        <!-- Import Summary -->
        <div class="import-summary">
            <div class="row">
                <div class="col-md-8">
                    <h3 class="mb-3">Import Activity Summary</h3>
                    <p class="mb-0">Track and monitor all data import activities with detailed logs and performance metrics.</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <button class="btn btn-light" onclick="exportHistory()">
                        <i class="fas fa-download me-2"></i>Export History
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mt-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background-color: var(--primary-green);">
                        <i class="fas fa-file-import"></i>
                    </div>
                    <h3 class="mb-2" id="totalImports">0</h3>
                    <p class="text-muted mb-2">Total Imports</p>
                    <div class="d-flex align-items-center">
                        <span class="text-success fw-bold" id="importChange">+0%</span>
                        <span class="text-muted ms-2">from last month</span>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background-color: var(--light-green);">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3 class="mb-2" id="successRate">0%</h3>
                    <p class="text-muted mb-2">Success Rate</p>
                    <div class="progress progress-custom">
                        <div class="progress-bar bg-success" id="successRateBar" style="width: 0%"></div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background-color: var(--primary-blue);">
                        <i class="fas fa-database"></i>
                    </div>
                    <h3 class="mb-2" id="totalRecords">0</h3>
                    <p class="text-muted mb-2">Total Records</p>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-arrow-up text-success me-2"></i>
                        <span id="thisMonthRecords">0 this month</span>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background-color: var(--warning-orange);">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 class="mb-2" id="pendingReviews">0</h3>
                    <p class="text-muted mb-2">Pending Reviews</p>
                    <button class="btn btn-warning btn-sm mt-2" onclick="showPendingImports()">
                        <i class="fas fa-external-link-alt me-1"></i>Review Now
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Filter Panel -->
        <div class="filter-panel">
            <div class="row g-3">
                <div class="col-lg-3 col-md-6">
                    <label class="form-label">Date Range</label>
                    <select class="form-select" id="dateRangeFilter">
                        <option value="all">All Time</option>
                        <option value="today">Today</option>
                        <option value="week" selected>Last 7 Days</option>
                        <option value="month">Last 30 Days</option>
                        <option value="quarter">Last Quarter</option>
                        <option value="year">Last Year</option>
                    </select>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <label class="form-label">Import Status</label>
                    <select class="form-select" id="statusFilter">
                        <option value="all">All Status</option>
                        <option value="completed">Completed</option>
                        <option value="processing">Processing</option>
                        <option value="failed">Failed</option>
                        <option value="partial">Partially Completed</option>
                        <option value="queued">Queued</option>
                    </select>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <label class="form-label">Import Type</label>
                    <select class="form-select" id="typeFilter">
                        <option value="all">All Types</option>
                        <option value="csv">CSV/Excel</option>
                        <option value="api">API Integration</option>
                        <option value="manual">Manual Entry</option>
                        <option value="scheduled">Scheduled Import</option>
                    </select>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <label class="form-label">User</label>
                    <select class="form-select" id="userFilter">
                        <option value="all">All Users</option>
                        <option value="system">System</option>
                        @foreach(\App\Models\User::all() as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-12 mt-3 d-flex justify-content-between">
                    <button class="btn btn-outline-secondary" id="resetFilters">
                        <i class="fas fa-redo me-2"></i>Reset Filters
                    </button>
                    <div>
                        <button class="btn btn-success" id="applyFilters">
                            <i class="fas fa-filter me-2"></i>Apply Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="chart-container">
                    <div class="chart-title">Import Activity Trend</div>
                    <div id="importTrendChart"></div>
                </div>
            </div>
            
            <div class="col-lg-4 mb-4">
                <div class="chart-container">
                    <div class="chart-title">Import Status Distribution</div>
                    <div id="statusDistributionChart"></div>
                </div>
            </div>
        </div>
        
        <!-- Import History Table -->
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Imports</h5>
                <div class="d-flex gap-2">
                    <div class="input-group" style="width: 250px;">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" placeholder="Search imports..." id="searchInput">
                    </div>
                    <button class="btn btn-outline-primary" onclick="showBulkActions()">
                        <i class="fas fa-tasks me-2"></i>Bulk Actions
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="importHistoryTable" style="width:100%">
                        <thead>
                            <tr>
                                <th width="50">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="selectAll">
                                    </div>
                                </th>
                                <th>Import ID</th>
                                <th>Date & Time</th>
                                <th>File / Source</th>
                                <th>Type</th>
                                <th>Records</th>
                                <th>Status</th>
                                <th>Duration</th>
                                <th>User</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- DataTables will populate this -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Detailed View Sections -->
        <div class="row mt-4">
            <!-- Import Logs -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Import Logs</h5>
                        <button class="btn btn-sm btn-outline-primary" onclick="refreshLogs()">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="timeline p-4" id="importLogs">
                            <p class="text-muted text-center">Select an import to view logs</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Import Statistics -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Import Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6 mb-4">
                                <div class="display-6 text-primary" id="statSuccessRate">0%</div>
                                <div class="text-muted">Success Rate</div>
                            </div>
                            <div class="col-6 mb-4">
                                <div class="display-6 text-success" id="statAvgTime">0s</div>
                                <div class="text-muted">Avg. Processing Time</div>
                            </div>
                            <div class="col-6">
                                <div class="display-6 text-info" id="statTotalRecords">0</div>
                                <div class="text-muted">Total Records</div>
                            </div>
                            <div class="col-6">
                                <div class="display-6 text-warning" id="statFailed">0</div>
                                <div class="text-muted">Failed Last 30 Days</div>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h6 class="mb-3">Top Import Sources</h6>
                        <div id="importSourcesStats">
                            <!-- Will be populated dynamically -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Import Details Modal -->
    <div class="modal fade" id="importDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Import Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="importDetailsContent">
                        <!-- Content loaded dynamically -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="downloadImportReport()">
                        <i class="fas fa-download me-2"></i>Download Report
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bulk Actions Modal -->
    <div class="modal fade" id="bulkActionsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bulk Actions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Action</label>
                        <select class="form-select" id="bulkActionSelect">
                            <option value="">Choose an action...</option>
                            <option value="delete">Delete Selected</option>
                            <option value="export">Export Selected Logs</option>
                            <option value="archive">Archive Selected</option>
                        </select>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        This action will apply to <span id="selectedCount">0</span> selected imports.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="applyBulkAction()">Apply Action</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    /* Import Summary - system color scheme */
    .import-summary {
        background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
        color: white;
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 20px;
    }
    
    /* Statistics Cards */
    .stats-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        height: 100%;
    }
    
    .stats-icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: white;
        margin-bottom: 15px;
    }
    
    /* Status Badges */
    .status-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    
    .status-completed {
        background-color: rgba(46, 125, 50, 0.1);
        color: var(--primary-green);
    }
    
    .status-processing {
        background-color: rgba(3, 169, 244, 0.1);
        color: var(--light-blue);
    }
    
    .status-failed {
        background-color: rgba(211, 47, 47, 0.1);
        color: var(--danger-red);
    }
    
    .status-partial {
        background-color: rgba(255, 193, 7, 0.1);
        color: #ffc107;
    }
    
    .status-queued {
        background-color: rgba(108, 117, 125, 0.1);
        color: #6c757d;
    }
    
    /* Import Type Badges */
    .import-type {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        background-color: var(--gray-100);
        color: var(--gray-800);
    }
    
    .type-csv, .type-excel {
        background-color: rgba(46, 125, 50, 0.1);
        color: var(--primary-green);
    }
    
    .type-api {
        background-color: rgba(3, 169, 244, 0.1);
        color: var(--light-blue);
    }
    
    .type-manual {
        background-color: rgba(121, 85, 72, 0.1);
        color: #795548;
    }
    
    .type-scheduled {
        background-color: rgba(255, 193, 7, 0.1);
        color: #ffc107;
    }
    
    /* Filter Panel */
    .filter-panel {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        margin-bottom: 20px;
    }
    
    /* Chart Container */
    .chart-container {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        margin-bottom: 20px;
        height: 100%;
    }
    
    .chart-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--gray-800);
        margin-bottom: 15px;
    }
    
    /* Progress Bar */
    .progress-custom {
        height: 8px;
        border-radius: 4px;
        background-color: var(--gray-200);
    }
    
    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 5px;
    }
    
    .action-btn {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        background-color: var(--gray-100);
        color: var(--gray-600);
        transition: all 0.2s;
        cursor: pointer;
    }
    
    .action-btn:hover {
        background-color: var(--gray-200);
    }
    
    .view-btn:hover {
        background-color: rgba(3, 169, 244, 0.1);
        color: var(--light-blue);
    }
    
    .retry-btn:hover {
        background-color: rgba(40, 167, 69, 0.1);
        color: #28a745;
    }
    
    .delete-btn:hover {
        background-color: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }
    
    /* Log Levels */
    .log-level {
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .log-info {
        background-color: rgba(3, 169, 244, 0.1);
        color: var(--light-blue);
    }
    
    .log-warning {
        background-color: rgba(255, 193, 7, 0.1);
        color: #ffc107;
    }
    
    .log-error {
        background-color: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }
    
    .log-success {
        background-color: rgba(40, 167, 69, 0.1);
        color: #28a745;
    }
    
    /* Log Details */
    .log-details {
        background-color: var(--gray-50);
        border-radius: 8px;
        padding: 15px;
        font-family: monospace;
        font-size: 0.875rem;
        white-space: pre-wrap;
        word-break: break-word;
        max-height: 300px;
        overflow-y: auto;
        border-left: 3px solid var(--primary-blue);
    }
    
    /* Timeline */
    .timeline {
        position: relative;
        padding-left: 30px;
        max-height: 400px;
        overflow-y: auto;
    }
    
    .timeline::before {
        content: '';
        position: absolute;
        left: 10px;
        top: 0;
        bottom: 0;
        width: 2px;
        background-color: var(--gray-200);
    }
    
    .timeline-item {
        position: relative;
        margin-bottom: 20px;
    }
    
    .timeline-dot {
        position: absolute;
        left: -20px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background-color: var(--gray-600);
    }
    
    .timeline-dot.success {
        background-color: var(--light-green);
    }
    
    .timeline-dot.warning {
        background-color: var(--warning-orange);
    }
    
    .timeline-dot.error {
        background-color: var(--danger-red);
    }
</style>
@endpush

@push('scripts')
<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<!-- ApexCharts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.js"></script>

<script>
let importTable;
let trendChart, distributionChart;
let selectedImportId = null;

$(document).ready(function() {
    // Initialize DataTable
    importTable = $('#importHistoryTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("import_history.data") }}',
            data: function(d) {
                d.date_range = $('#dateRangeFilter').val();
                d.status = $('#statusFilter').val();
                d.type = $('#typeFilter').val();
                d.user = $('#userFilter').val();
            }
        },
        columns: [
            { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false },
            { data: 'import_id', name: 'import_id' },
            { data: 'created_at', name: 'created_at' },
            { data: 'file_name', name: 'file_name' },
            { data: 'type_badge', name: 'import_type', orderable: false },
            { data: 'total_records', name: 'total_records' },
            { data: 'status_badge', name: 'status', orderable: false },
            { data: 'processing_time', name: 'processing_time' },
            { data: 'user_avatar', name: 'user_id', orderable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false },
        ],
        order: [[2, 'desc']],
        pageLength: 10,
        responsive: true,
    });
    
    // Load statistics and charts
    loadStatistics();
    loadTrendData();
    loadStatusDistribution();
    loadImportSources();
    
    // Filter handlers
    $('#applyFilters').click(function() {
        importTable.draw();
        loadTrendData();
        loadStatusDistribution();
        showToast('Filters applied successfully', 'success');
    });
    
    $('#resetFilters').click(function() {
        $('#dateRangeFilter').val('week');
        $('#statusFilter').val('all');
        $('#typeFilter').val('all');
        $('#userFilter').val('all');
        importTable.draw();
        loadTrendData();
        loadStatusDistribution();
        showToast('Filters reset', 'info');
    });
    
    // Search handler
    $('#searchInput').on('keyup', function() {
        importTable.search(this.value).draw();
    });
    
    // Select all checkbox
    $('#selectAll').change(function() {
        $('.row-checkbox').prop('checked', this.checked);
        updateSelectedCount();
    });
    
    // Individual checkbox
    $(document).on('change', '.row-checkbox', function() {
        updateSelectedCount();
    });
});

function loadStatistics() {
    $.get('{{ route("import_history.statistics") }}')
        .done(function(data) {
            $('#totalImports').text(data.total_imports);
            $('#successRate').text(data.success_rate + '%');
            $('#successRateBar').css('width', data.success_rate + '%');
            $('#totalRecords').text(data.total_records.toLocaleString());
            $('#thisMonthRecords').text(data.this_month_records.toLocaleString() + ' this month');
            $('#pendingReviews').text(data.pending_reviews);
            $('#importChange').text((data.percentage_change >= 0 ? '+' : '') + data.percentage_change + '%');
            $('#statSuccessRate').text(data.success_rate + '%');
            $('#statAvgTime').text(data.avg_processing_time + 's');
            $('#statTotalRecords').text(data.total_records.toLocaleString());
            $('#statFailed').text(data.failed_last_30_days || 0);
        })
        .fail(function() {
            showToast('Failed to load statistics', 'error');
        });
}

function loadTrendData() {
    $.get('{{ route("import_history.trend") }}', {
        days: $('#dateRangeFilter').val() === 'week' ? 7 : 
              $('#dateRangeFilter').val() === 'month' ? 30 : 7
    })
        .done(function(data) {
            if (trendChart) {
                trendChart.destroy();
            }
            
            const options = {
                series: [{
                    name: 'Successful',
                    data: data.data.successful
                }, {
                    name: 'Failed',
                    data: data.data.failed
                }, {
                    name: 'Partial',
                    data: data.data.partial
                }],
                chart: {
                    height: 250,
                    type: 'line',
                    zoom: { enabled: false },
                    toolbar: { show: false }
                },
                colors: ['#2e7d32', '#d32f2f', '#f57c00'],
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 3 },
                xaxis: { categories: data.labels },
                yaxis: { title: { text: 'Number of Imports' } },
                legend: { position: 'top', horizontalAlign: 'right' }
            };
            
            trendChart = new ApexCharts(document.querySelector("#importTrendChart"), options);
            trendChart.render();
        })
        .fail(function() {
            showToast('Failed to load trend data', 'error');
        });
}

function loadImportSources() {
    $.get('{{ route("import_history.sources") }}')
        .done(function(data) {
            let html = '';
            if (data.sources && data.sources.length > 0) {
                data.sources.forEach(function(s) {
                    html += '<div class="d-flex justify-content-between align-items-center py-2 border-bottom"><span>' + s.type + '</span><span class="badge bg-primary">' + s.count + ' imports</span><span class="text-muted small">' + (s.records || 0).toLocaleString() + ' records</span></div>';
                });
            } else {
                html = '<p class="text-muted small mb-0">No import data yet</p>';
            }
            $('#importSourcesStats').html(html);
        });
}

function loadStatusDistribution() {
    $.get('{{ route("import_history.distribution") }}')
        .done(function(data) {
            if (distributionChart) {
                distributionChart.destroy();
            }
            
            const options = {
                series: [data.completed, data.processing, data.failed, data.partial, data.queued],
                chart: {
                    height: 250,
                    type: 'donut',
                },
                colors: ['#2e7d32', '#03a9f4', '#d32f2f', '#ffc107', '#6c757d'],
                labels: ['Completed', 'Processing', 'Failed', 'Partial', 'Queued'],
                plotOptions: {
                    pie: {
                        donut: {
                            size: '65%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total',
                                    fontSize: '14px'
                                }
                            }
                        }
                    }
                },
                dataLabels: { enabled: false },
                legend: { position: 'bottom' }
            };
            
            distributionChart = new ApexCharts(document.querySelector("#statusDistributionChart"), options);
            distributionChart.render();
        })
        .fail(function() {
            showToast('Failed to load distribution', 'error');
        });
}

function viewImportDetails(id) {
    selectedImportId = id;
    $.get('{{ url("import-history") }}/' + id)
        .done(function(data) {
            const details = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Import Information</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Import ID:</strong></td><td><code>${data.import_id}</code></td></tr>
                            <tr><td><strong>Date & Time:</strong></td><td>${data.created_at}</td></tr>
                            <tr><td><strong>File Name:</strong></td><td>${data.file_name || 'N/A'}</td></tr>
                            <tr><td><strong>Import Type:</strong></td><td>${data.import_type}</td></tr>
                            <tr><td><strong>User:</strong></td><td>${data.user ? data.user.name : 'System'}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Import Statistics</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Total Records:</strong></td><td>${data.total_records}</td></tr>
                            <tr><td><strong>Successful:</strong></td><td><span class="text-success">${data.successful_records}</span></td></tr>
                            <tr><td><strong>Failed:</strong></td><td><span class="text-danger">${data.failed_records}</span></td></tr>
                            <tr><td><strong>Processing Time:</strong></td><td>${data.processing_time ? data.processing_time + ' seconds' : 'N/A'}</td></tr>
                            <tr><td><strong>File Size:</strong></td><td>${data.formatted_file_size || 'N/A'}</td></tr>
                        </table>
                    </div>
                </div>
                ${data.error_message ? `<div class="alert alert-danger mt-3"><strong>Error:</strong> ${data.error_message}</div>` : ''}
            `;
            
            $('#importDetailsContent').html(details);
            new bootstrap.Modal(document.getElementById('importDetailsModal')).show();
            
            loadImportLogs(id);
        })
        .fail(function() {
            showToast('Failed to load import details', 'error');
        });
}

function loadImportLogs(id) {
    $.get('{{ url("import-history") }}/' + id + '/logs')
        .done(function(data) {
            let logsHtml = '';
            if (data.logs && data.logs.length > 0) {
                data.logs.forEach(function(log) {
                    const level = log.level || 'info';
                    const time = log.time || '';
                    const message = log.message || '';
                    
                    logsHtml += `
                        <div class="timeline-item">
                            <div class="timeline-dot ${level}"></div>
                            <div class="mb-2">
                                <span class="log-level log-${level} me-2">${level.toUpperCase()}</span>
                                <span class="text-muted small">${time}</span>
                            </div>
                            <p class="mb-0">${message}</p>
                        </div>
                    `;
                });
            } else {
                logsHtml = '<p class="text-muted text-center">No logs available</p>';
            }
            $('#importLogs').html(logsHtml);
        })
        .fail(function() {
            $('#importLogs').html('<p class="text-muted text-center">Failed to load logs</p>');
        });
}

function downloadImportFile(id) {
    window.location.href = '{{ url("import-history") }}/' + id + '/download';
}

function retryImport(id) {
    if (!confirm('Retry this import? The original file will be re-processed.')) return;
    showToast('Re-importing...', 'info');
    $.ajax({
        url: '{{ url("import-history") }}/' + id + '/retry',
        type: 'POST',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        data: { _token: '{{ csrf_token() }}' },
        success: function(data) {
            if (data.status === 'success') {
                showToast(data.message, 'success');
                importTable.draw();
                if (typeof bootstrap !== 'undefined' && selectedImportId) {
                    const modalEl = document.getElementById('importDetailsModal');
                    if (modalEl && bootstrap.Modal.getInstance(modalEl)) bootstrap.Modal.getInstance(modalEl).hide();
                }
            } else {
                showToast(data.message || 'Retry failed', 'error');
            }
        },
        error: function(xhr) {
            const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Retry failed';
            showToast(msg, 'error');
        }
    });
}

function deleteImport(id) {
    if (!confirm('Delete this import? This action cannot be undone.')) return;
    $.ajax({
        url: '{{ route("import_history.destroy", ["id" => "__ID__"]) }}'.replace('__ID__', id),
        type: 'DELETE',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function() {
            importTable.draw();
            loadStatistics();
            showToast('Import deleted', 'success');
        },
        error: function() {
            showToast('Failed to delete import', 'error');
        }
    });
}

function cancelImport(id) {
    if (!confirm('Cancel this import?')) return;
    $.ajax({
        url: '{{ url("import-history") }}/' + id + '/cancel',
        type: 'POST',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        data: { _token: '{{ csrf_token() }}' },
        success: function() {
            showToast('Import cancelled', 'success');
            importTable.draw();
        },
        error: function() {
            showToast('Failed to cancel import', 'error');
        }
    });
}

function runNowImport(id) {
    showToast('Run now is available for scheduled imports only.', 'info');
}

function showBulkActions() {
    new bootstrap.Modal(document.getElementById('bulkActionsModal')).show();
}

function applyBulkAction() {
    const action = $('#bulkActionSelect').val();
    const selected = $('.row-checkbox:checked').map(function() {
        return $(this).val();
    }).get();
    
    if (selected.length === 0) {
        showToast('Please select at least one import', 'error');
        return;
    }
    
    if (!action) {
        showToast('Please select an action', 'error');
        return;
    }
    
    if (action === 'export') {
        window.location.href = '{{ route("import_history.export_logs") }}?ids=' + selected.join(',');
        const modalEl = document.getElementById('bulkActionsModal');
        if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal.getInstance(modalEl)) {
            bootstrap.Modal.getInstance(modalEl).hide();
        }
        $('#bulkActionSelect').val('');
        $('.row-checkbox:checked').prop('checked', false);
        $('#selectAll').prop('checked', false);
        updateSelectedCount();
        return;
    }
    
    $.ajax({
        url: '{{ route("import_history.bulk_action") }}',
        type: 'POST',
        data: {
            action: action,
            ids: selected,
            _token: '{{ csrf_token() }}'
        },
        success: function(data) {
            importTable.draw();
            loadStatistics();
            const modalEl = document.getElementById('bulkActionsModal');
            if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal.getInstance(modalEl)) {
                bootstrap.Modal.getInstance(modalEl).hide();
            }
            $('#bulkActionSelect').val('');
            $('.row-checkbox:checked').prop('checked', false);
            $('#selectAll').prop('checked', false);
            updateSelectedCount();
            showToast(data.message || 'Bulk action completed', 'success');
        },
        error: function(xhr) {
            const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Bulk action failed';
            showToast(msg, 'error');
        }
    });
}

function updateSelectedCount() {
    const count = $('.row-checkbox:checked').length;
    $('#selectedCount').text(count);
}

function exportHistory() {
    const params = new URLSearchParams({
        date_range: $('#dateRangeFilter').val(),
        status: $('#statusFilter').val()
    });
    window.location.href = '{{ route("import_history.export") }}?' + params.toString();
}

function downloadImportReport() {
    if (selectedImportId) {
        window.location.href = '{{ url("import-history") }}/' + selectedImportId + '/report';
    }
}

function showPendingImports() {
    $('#statusFilter').val('partial');
    $('#applyFilters').click();
}

function refreshLogs() {
    if (selectedImportId) {
        loadImportLogs(selectedImportId);
        showToast('Logs refreshed', 'success');
    }
}

function showToast(message, type) {
    const toast = $('<div>').addClass('position-fixed bottom-0 end-0 p-3').css('z-index', '11');
    const bgColor = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : type === 'warning' ? 'bg-warning' : 'bg-info';
    const icon = type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle';
    
    toast.html(`
        <div class="toast show" role="alert">
            <div class="toast-header ${bgColor} text-white">
                <i class="fas fa-${icon} me-2"></i>
                <strong class="me-auto">${type.charAt(0).toUpperCase() + type.slice(1)}</strong>
                <button type="button" class="btn-close btn-close-white" onclick="$(this).closest('.position-fixed').remove()"></button>
            </div>
            <div class="toast-body">${message}</div>
        </div>
    `);
    
    $('body').append(toast);
    
    setTimeout(function() {
        toast.remove();
    }, 5000);
}
</script>
@endpush


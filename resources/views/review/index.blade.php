@extends('layouts.app')

@section('title', 'Data Review & Validation')
@section('page-title', 'Data Review & Validation')

@section('content')
    <!-- Main Content -->
    <div id="content">
        <!-- Top Navigation Bar -->
        @include('layouts.top-nav')
        
        <style>
            /* Data Summary Cards */
            .summary-card {
                background: white;
                border-radius: 10px;
                padding: 20px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
                height: 100%;
            }
            
            .summary-icon {
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
            
            /* Validation Tags */
            .validation-tag {
                padding: 3px 10px;
                border-radius: 12px;
                font-size: 0.75rem;
                font-weight: 600;
            }
            
            .valid-tag {
                background-color: rgba(76, 175, 80, 0.1);
                color: var(--light-green);
            }
            
            .warning-tag {
                background-color: rgba(245, 124, 0, 0.1);
                color: var(--warning-orange);
            }
            
            .error-tag {
                background-color: rgba(211, 47, 47, 0.1);
                color: var(--danger-red);
            }
            
            /* Data Quality Indicators */
            .quality-meter {
                height: 8px;
                border-radius: 4px;
                background-color: var(--gray-200);
                overflow: hidden;
                margin: 10px 0;
            }
            
            .quality-fill {
                height: 100%;
                border-radius: 4px;
            }
            
            /* Highlighted Rows */
            tr.data-warning {
                background-color: rgba(255, 193, 7, 0.05) !important;
            }
            
            tr.data-error {
                background-color: rgba(220, 53, 69, 0.05) !important;
            }
            
            tr.data-success {
                background-color: rgba(40, 167, 69, 0.05) !important;
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
            
            .edit-btn:hover {
                background-color: rgba(3, 169, 244, 0.1);
                color: var(--light-blue);
            }
            
            .delete-btn:hover {
                background-color: rgba(220, 53, 69, 0.1);
                color: #dc3545;
            }
            
            /* Filter Panel */
            .filter-panel {
                background: white;
                border-radius: 10px;
                padding: 20px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
                margin-bottom: 20px;
            }
            
            /* Status Badges */
            .status-badge {
                padding: 5px 12px;
                border-radius: 20px;
                font-size: 0.8rem;
                font-weight: 600;
            }
            
            .status-validated {
                background-color: rgba(76, 175, 80, 0.1);
                color: var(--light-green);
            }
            
            .status-pending {
                background-color: rgba(255, 193, 7, 0.1);
                color: #ffc107;
            }
            
            .status-rejected {
                background-color: rgba(220, 53, 69, 0.1);
                color: #dc3545;
            }
        </style>
        
        <!-- Data Summary Cards -->
        <div class="row mt-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="summary-card">
                    <div class="summary-icon" style="background-color: var(--primary-green);">
                        <i class="fas fa-database"></i>
                    </div>
                    <h4 class="mb-2">{{ number_format($totalRecords) }}</h4>
                    <p class="text-muted mb-2">Total Records</p>
                    <div class="d-flex align-items-center">
                        <div class="quality-meter" style="width: 100%;">
                            <div class="quality-fill" style="width: {{ $dataQuality }}%; background-color: var(--light-green);"></div>
                        </div>
                        <span class="ms-2 fw-bold">{{ $dataQuality }}%</span>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="summary-card">
                    <div class="summary-icon" style="background-color: var(--light-green);">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h4 class="mb-2">{{ number_format($validatedRecords) }}</h4>
                    <p class="text-muted mb-2">Validated Records</p>
                    <span class="valid-tag validation-tag">
                        <i class="fas fa-check me-1"></i> Ready for Reporting
                    </span>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="summary-card">
                    <div class="summary-icon" style="background-color: var(--warning-orange);">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h4 class="mb-2">{{ number_format($draftRecords) }}</h4>
                    <p class="text-muted mb-2">Requires Review</p>
                    <span class="warning-tag validation-tag">
                        <i class="fas fa-exclamation me-1"></i> Needs Attention
                    </span>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="summary-card">
                    <div class="summary-icon" style="background-color: var(--danger-red);">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <h4 class="mb-2">{{ number_format($invalidRecords) }}</h4>
                    <p class="text-muted mb-2">Invalid Records</p>
                    <span class="error-tag validation-tag">
                        <i class="fas fa-times me-1"></i> Requires Action
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Filter Panel -->
        <div class="filter-panel">
            <div class="row g-3">
                <div class="col-lg-3 col-md-6">
                    <label class="form-label">Data Status</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="active">Validated</option>
                        <option value="draft">Pending Review</option>
                    </select>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <label class="form-label">Data Source</label>
                    <select class="form-select" id="sourceFilter">
                        <option value="">All Sources</option>
                        <option value="import">CSV Import</option>
                        <option value="manual">Manual Entry</option>
                        <option value="api">API Integration</option>
                    </select>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <label class="form-label">Date Range</label>
                    <select class="form-select" id="dateFilter">
                        <option value="">All Dates</option>
                        <option value="last30">Last 30 Days</option>
                        <option value="last90">Last 90 Days</option>
                        <option value="ytd">Year to Date</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <label class="form-label">Scope</label>
                    <select class="form-select" id="scopeFilter">
                        <option value="">All Scopes</option>
                        <option value="1">Scope 1</option>
                        <option value="2">Scope 2</option>
                        <option value="3">Scope 3</option>
                    </select>
                </div>
                
                <div class="col-12 mt-3 d-flex justify-content-between">
                    <button class="btn btn-outline-secondary" id="resetFilters">
                        <i class="fas fa-redo me-2"></i>Reset Filters
                    </button>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary" id="exportData">
                            <i class="fas fa-download me-2"></i>Export Selection
                        </button>
                        <button class="btn btn-success" id="applyFilters">
                            <i class="fas fa-filter me-2"></i>Apply Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Validation Issues Summary -->
        @if($draftRecords > 0 || $invalidRecords > 0)
        <div class="alert alert-warning mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                <div class="flex-grow-1">
                    <h5 class="alert-heading mb-1">{{ $draftRecords + $invalidRecords }} Records Require Your Attention</h5>
                    <p class="mb-0">There are validation issues that need to be resolved before the next reporting cycle.</p>
                </div>
                <button class="btn btn-warning" onclick="showValidationIssues()">
                    <i class="fas fa-external-link-alt me-2"></i>Review Issues
                </button>
            </div>
        </div>
        @endif
        
        <!-- Data Review Table -->
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Emission Data Records</h5>
                <div class="d-flex gap-2">
                    <div class="input-group" style="width: 300px;">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" placeholder="Search records..." id="searchInput">
                    </div>
                    <button class="btn btn-outline-primary" onclick="showBulkActions()">
                        <i class="fas fa-tasks me-2"></i>Bulk Actions
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="dataReviewTable">
                        <thead>
                            <tr>
                                <th width="50">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="selectAll">
                                    </div>
                                </th>
                                <th>Date</th>
                                <th>Facility</th>
                                <th>Scope</th>
                                <th>Source</th>
                                <th>CO₂e Value</th>
                                <th>Status</th>
                                <th>Validation</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be loaded via DataTables -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted">
                        Showing <span id="showingCount">0</span> of <span id="totalCount">{{ number_format($totalRecords) }}</span> records
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-success" onclick="validateSelected()">
                                <i class="fas fa-check-circle me-2"></i>Validate Selected
                            </button>
                            <button class="btn btn-warning" onclick="flagForReview()">
                                <i class="fas fa-flag me-2"></i>Flag for Review
                            </button>
                            <button class="btn btn-danger" onclick="deleteSelected()">
                                <i class="fas fa-trash me-2"></i>Delete Selected
                            </button>
                            <button class="btn btn-outline-primary" onclick="exportSelected()">
                                <i class="fas fa-download me-2"></i>Export Selected
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Validation Summary</h6>
                    </div>
                    <div class="card-body">
                        @php
                            $validPercent = $totalRecords > 0 ? round(($validatedRecords / $totalRecords) * 100, 1) : 0;
                            $reviewPercent = $totalRecords > 0 ? round(($draftRecords / $totalRecords) * 100, 1) : 0;
                            $invalidPercent = $totalRecords > 0 ? round(($invalidRecords / $totalRecords) * 100, 1) : 0;
                        @endphp
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Valid Records</span>
                                <span>{{ $validPercent }}%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: {{ $validPercent }}%"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Requires Review</span>
                                <span>{{ $reviewPercent }}%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-warning" style="width: {{ $reviewPercent }}%"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Invalid Records</span>
                                <span>{{ $invalidPercent }}%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-danger" style="width: {{ $invalidPercent }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Common Issues</h6>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            @php
                                $missingFacility = \App\Models\EmissionRecord::where(function($q) {
                                    $q->whereNull('facility')->orWhere('facility', '');
                                })->count();
                                $missingDept = \App\Models\EmissionRecord::where(function($q) {
                                    $q->whereNull('department')->orWhere('department', '');
                                })->count();
                            @endphp
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between">
                                    <span>Missing Facility Data</span>
                                    <span class="badge bg-danger">{{ $missingFacility }}</span>
                                </div>
                            </div>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between">
                                    <span>Missing Department</span>
                                    <span class="badge bg-warning text-dark">{{ $missingDept }}</span>
                                </div>
                            </div>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between">
                                    <span>Pending Validation</span>
                                    <span class="badge bg-info">{{ $draftRecords }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Record Details Modal -->
    <div class="modal fade" id="recordModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Record Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="recordDetails">
                        <!-- Content loaded dynamically -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="editRecordBtn">Edit Record</button>
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
                            <option value="validate">Validate Selected</option>
                            <option value="reject">Reject Records</option>
                            <option value="delete">Delete Records</option>
                            <option value="export">Export Records</option>
                        </select>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        This action will apply to <span id="selectedCount">0</span> selected records.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="applyBulkAction()">Apply Action</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <!-- jQuery and DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        let dataTable;
        
        $(document).ready(function() {
            // Initialize DataTable
            dataTable = $('#dataReviewTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route("review_data.data") }}',
                    data: function(d) {
                        d.status = $('#statusFilter').val();
                        d.data_source = $('#sourceFilter').val();
                        d.scope = $('#scopeFilter').val();
                        d.date_from = $('#dateFrom').val();
                        d.date_to = $('#dateTo').val();
                    }
                },
                columns: [
                    { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false },
                    { data: 'entry_date', name: 'entry_date' },
                    { data: 'facility', name: 'facility' },
                    { data: 'scope_badge', name: 'scope', orderable: true, searchable: false },
                    { data: 'source_badge', name: 'data_source', orderable: true, searchable: false },
                    { data: 'co2e_formatted', name: 'co2e_value', orderable: true, searchable: false },
                    { data: 'status_badge', name: 'status', orderable: true, searchable: false },
                    { data: 'validation_tag', name: 'validation_tag', orderable: false, searchable: false },
                    { data: 'updated_formatted', name: 'updated_at', orderable: true, searchable: false },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                ],
                order: [[1, 'desc']],
                pageLength: 25,
                createdRow: function(row, data, dataIndex) {
                    // Add row classes based on validation status
                    // Check the validation tag HTML content
                    var validationHtml = data[7] || ''; // validation_tag column
                    if (validationHtml.indexOf('valid-tag') !== -1) {
                        $(row).addClass('data-success');
                    } else if (validationHtml.indexOf('error-tag') !== -1) {
                        $(row).addClass('data-error');
                    } else if (validationHtml.indexOf('warning-tag') !== -1) {
                        $(row).addClass('data-warning');
                    }
                },
                drawCallback: function(settings) {
                    // Update select all checkbox
                    $('#selectAll').prop('checked', false);
                    updateSelectedCount();
                }
            });
            
            // Search input
            $('#searchInput').on('keyup', function() {
                dataTable.search(this.value).draw();
            });
            
            // Select all checkbox
            $('#selectAll').on('change', function() {
                $('.row-checkbox').prop('checked', this.checked);
                updateSelectedCount();
            });
            
            // Apply filters
            $('#applyFilters').on('click', function() {
                dataTable.draw();
                showToast('Filters applied successfully', 'success');
            });
            
            // Reset filters
            $('#resetFilters').on('click', function() {
                $('#statusFilter, #sourceFilter, #scopeFilter, #dateFilter').val('');
                $('#dateFrom, #dateTo').val('').hide();
                dataTable.draw();
                showToast('Filters reset', 'info');
            });
        });
        
        // Record actions
        function editRecord(id) {
            window.location.href = '{{ route("emission_records.index") }}?edit=' + id;
        }
        
        function viewRecord(id) {
            $.ajax({
                url: '{{ url("review-data") }}/' + id,
                method: 'GET',
                success: function(record) {
                    const details = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Record Information</h6>
                                <table class="table table-sm">
                                    <tr><td><strong>ID:</strong></td><td>${record.id}</td></tr>
                                    <tr><td><strong>Date:</strong></td><td>${record.entry_date}</td></tr>
                                    <tr><td><strong>Facility:</strong></td><td>${record.facility || 'N/A'}</td></tr>
                                    <tr><td><strong>Scope:</strong></td><td>Scope ${record.scope}</td></tr>
                                    <tr><td><strong>Source:</strong></td><td>${record.emission_source}</td></tr>
                                    <tr><td><strong>Data Source:</strong></td><td>${record.data_source}</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6>Emission Data</h6>
                                <table class="table table-sm">
                                    <tr><td><strong>CO₂e Value:</strong></td><td>${parseFloat(record.co2e_value).toFixed(2)} tCO₂e</td></tr>
                                    <tr><td><strong>Activity Data:</strong></td><td>${record.activity_data || 'N/A'}</td></tr>
                                    <tr><td><strong>Emission Factor:</strong></td><td>${record.emission_factor || 'N/A'}</td></tr>
                                    <tr><td><strong>Department:</strong></td><td>${record.department || 'N/A'}</td></tr>
                                    <tr><td><strong>Confidence Level:</strong></td><td>${record.confidence_level}</td></tr>
                                </table>
                            </div>
                        </div>
                        ${record.notes ? '<div class="mt-3"><h6>Notes</h6><p>' + record.notes + '</p></div>' : ''}
                    `;
                    
                    $('#recordDetails').html(details);
                    $('#editRecordBtn').off('click').on('click', function() {
                        editRecord(record.id);
                    });
                    new bootstrap.Modal(document.getElementById('recordModal')).show();
                },
                error: function() {
                    showToast('Error loading record details', 'error');
                }
            });
        }
        
        function validateRecord(id) {
            $.ajax({
                url: '{{ url("review-data") }}/' + id + '/status',
                method: 'PUT',
                data: {
                    _token: '{{ csrf_token() }}',
                    status: 'active'
                },
                success: function() {
                    dataTable.draw();
                    showToast('Record validated successfully!', 'success');
                },
                error: function() {
                    showToast('Error validating record', 'error');
                }
            });
        }
        
        // Bulk actions
        function showBulkActions() {
            updateSelectedCount();
            new bootstrap.Modal(document.getElementById('bulkActionsModal')).show();
        }
        
        function updateSelectedCount() {
            const selected = $('.row-checkbox:checked').length;
            $('#selectedCount').text(selected);
        }
        
        function applyBulkAction() {
            const action = $('#bulkActionSelect').val();
            const selectedIds = $('.row-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
            
            if (selectedIds.length === 0) {
                showToast('Please select at least one record', 'error');
                return;
            }
            
            if (!action) {
                showToast('Please select an action', 'error');
                return;
            }
            
            if (action === 'delete' && !confirm(`Are you sure you want to delete ${selectedIds.length} records?`)) {
                return;
            }
            
            $.ajax({
                url: '{{ route("review_data.bulk_update") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    ids: selectedIds,
                    action: action
                },
                success: function(response) {
                    dataTable.draw();
                    $('#bulkActionsModal').modal('hide');
                    showToast(response.message, 'success');
                },
                error: function() {
                    showToast('Error performing bulk action', 'error');
                }
            });
        }
        
        function validateSelected() {
            const selectedIds = $('.row-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
            
            if (selectedIds.length === 0) {
                showToast('Please select at least one record', 'error');
                return;
            }
            
            $.ajax({
                url: '{{ route("review_data.bulk_update") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    ids: selectedIds,
                    action: 'validate'
                },
                success: function(response) {
                    dataTable.draw();
                    showToast(response.message, 'success');
                },
                error: function() {
                    showToast('Error validating records', 'error');
                }
            });
        }
        
        function flagForReview() {
            const selectedIds = $('.row-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
            
            if (selectedIds.length === 0) {
                showToast('Please select at least one record', 'error');
                return;
            }
            
            $.ajax({
                url: '{{ route("review_data.bulk_update") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    ids: selectedIds,
                    action: 'reject'
                },
                success: function(response) {
                    dataTable.draw();
                    showToast(response.message, 'success');
                },
                error: function() {
                    showToast('Error flagging records', 'error');
                }
            });
        }
        
        function deleteSelected() {
            const selectedIds = $('.row-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
            
            if (selectedIds.length === 0) {
                showToast('Please select at least one record', 'error');
                return;
            }
            
            if (!confirm(`Are you sure you want to delete ${selectedIds.length} records?`)) {
                return;
            }
            
            $.ajax({
                url: '{{ route("review_data.bulk_update") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    ids: selectedIds,
                    action: 'delete'
                },
                success: function(response) {
                    dataTable.draw();
                    showToast(response.message, 'success');
                },
                error: function() {
                    showToast('Error deleting records', 'error');
                }
            });
        }
        
        function exportSelected() {
            showToast('Export functionality coming soon', 'info');
        }
        
        function showValidationIssues() {
            $('#statusFilter').val('draft');
            $('#applyFilters').click();
        }
        
        // Toast notification
        function showToast(message, type) {
            const toast = document.createElement('div');
            toast.className = 'position-fixed bottom-0 end-0 p-3';
            toast.style.zIndex = '11';
            
            const bgColor = type === 'success' ? 'bg-success' : 
                           type === 'error' ? 'bg-danger' : 
                           type === 'warning' ? 'bg-warning' : 'bg-info';
            
            toast.innerHTML = `
                <div class="toast show" role="alert">
                    <div class="toast-header ${bgColor} text-white">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : 
                                        type === 'error' ? 'exclamation-circle' : 
                                        type === 'warning' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                        <strong class="me-auto">${type.charAt(0).toUpperCase() + type.slice(1)}</strong>
                        <button type="button" class="btn-close btn-close-white" onclick="this.parentElement.parentElement.remove()"></button>
                    </div>
                    <div class="toast-body">
                        ${message}
                    </div>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, 5000);
        }
        
        // Handle checkbox clicks in DataTables
        $(document).on('change', '.row-checkbox', function() {
            updateSelectedCount();
            const allChecked = $('.row-checkbox:checked').length === $('.row-checkbox').length;
            $('#selectAll').prop('checked', allChecked);
        });
    </script>
@endsection

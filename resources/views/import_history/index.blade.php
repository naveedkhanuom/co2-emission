@extends('layouts.app')

@section('title', 'Import History')
@section('page-title', 'Import History')

@section('content')
    <div id="content">
        @include('layouts.top-nav')

        <div class="import-history-app container-fluid mt-4">
            <!-- Topbar -->
            <div class="topbar">
                <h2><span class="sb"><i class="fas fa-file-import"></i></span> Import History</h2>
                <p>Track and monitor all data import activities with detailed logs and performance metrics.</p>
                <button type="button" class="btn-export-top" onclick="exportHistory()">
                    <i class="fas fa-download"></i> Export History
                </button>
            </div>

            <!-- Stats -->
            <div class="stats">
                <div class="sc">
                    <div class="si g"><i class="fas fa-file-import"></i></div>
                    <div style="flex:1;min-width:0;">
                        <div class="sv" id="totalImports">0</div>
                        <div class="sl">Total Imports</div>
                        <span class="stat-sub" id="importChange">+0%</span> <span class="stat-muted">from last month</span>
                    </div>
                </div>
                <div class="sc">
                    <div class="si suc"><i class="fas fa-check-circle"></i></div>
                    <div style="flex:1;min-width:0;">
                        <div class="sv" id="successRate">0%</div>
                        <div class="sl">Success Rate</div>
                        <div class="qm mt-2"><div class="qm-fill" id="successRateBar" style="width:0%"></div></div>
                    </div>
                </div>
                <div class="sc">
                    <div class="si b"><i class="fas fa-database"></i></div>
                    <div style="flex:1;min-width:0;">
                        <div class="sv" id="totalRecords">0</div>
                        <div class="sl">Total Records</div>
                        <span class="stat-sub"><i class="fas fa-arrow-up me-1"></i><span id="thisMonthRecords">0 this month</span></span>
                    </div>
                </div>
                <div class="sc">
                    <div class="si w"><i class="fas fa-clock"></i></div>
                    <div style="flex:1;min-width:0;">
                        <div class="sv" id="pendingReviews">0</div>
                        <div class="sl">Pending Reviews</div>
                        <button type="button" class="btn-review-now" onclick="showPendingImports()">
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
                    <div class="col-12 mt-3 d-flex justify-content-between flex-wrap gap-2">
                        <button type="button" class="btn btn-reset" id="resetFilters">
                            <i class="fas fa-redo me-2"></i>Reset Filters
                        </button>
                        <button type="button" class="btn btn-apply" id="applyFilters">
                            <i class="fas fa-filter me-2"></i>Apply Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Charts -->
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
            <div class="card import-datatable-card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <h5 class="mb-0">Recent Imports</h5>
                    <div class="d-flex gap-2">
                        <div class="input-group" style="width: 280px;">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" placeholder="Search imports..." id="searchInput">
                        </div>
                        <button type="button" class="btn btn-outline-primary" onclick="showBulkActions()">
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
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="table-info-text">
                            Showing <span id="showingCount">0</span> of <span id="totalCount">0</span> imports
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom cards -->
            <div class="row mt-4">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="tw tw-card">
                        <div class="card-head d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Recent Import Logs</h5>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshLogs()">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="timeline p-4" id="importLogs">
                                <p class="text-muted text-center mb-0">Select an import to view logs</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="tw tw-card">
                        <div class="card-head">
                            <h5 class="mb-0">Import Statistics</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center stat-blocks">
                                <div class="col-6 mb-4">
                                    <div class="stat-val text-primary" id="statSuccessRate">0%</div>
                                    <div class="stat-label">Success Rate</div>
                                </div>
                                <div class="col-6 mb-4">
                                    <div class="stat-val text-success" id="statAvgTime">0s</div>
                                    <div class="stat-label">Avg. Processing Time</div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-val text-info" id="statTotalRecords">0</div>
                                    <div class="stat-label">Total Records</div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-val text-warning" id="statFailed">0</div>
                                    <div class="stat-label">Failed Last 30 Days</div>
                                </div>
                            </div>
                            <hr class="my-4">
                            <h6 class="mb-3" style="font-size: 0.9375rem; font-weight: 700; color: var(--gray-800);">Top Import Sources</h6>
                            <div id="importSourcesStats"></div>
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
.import-history-app * { box-sizing: border-box; }
.import-history-app { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
/* Topbar */
.import-history-app .topbar { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; padding: 20px 24px; background: linear-gradient(135deg, #fff 0%, var(--gray-50) 100%); border: 1px solid var(--gray-200); border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.import-history-app .topbar h2 { font-size: 1.35rem; font-weight: 700; letter-spacing: -0.02em; display: flex; align-items: center; gap: 10px; margin: 0; color: var(--gray-800); }
.import-history-app .topbar h2 .sb { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%); color: #fff; font-size: 1rem; font-weight: 700; box-shadow: 0 2px 8px rgba(46,125,50,.25); }
.import-history-app .topbar p { color: var(--gray-600); font-size: 0.875rem; flex: 1; min-width: 180px; margin: 0; line-height: 1.4; }
.import-history-app .btn-export-top { margin-left: auto; padding: 10px 20px; border-radius: 10px; border: 1.5px solid var(--gray-200); background: #fff; color: var(--gray-700); font-size: 0.875rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
.import-history-app .btn-export-top:hover { background: var(--gray-50); border-color: var(--gray-300); color: var(--gray-800); }
/* Stats - same as review/scope */
.import-history-app .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
.import-history-app .sc { background: #fff; border: 1px solid var(--gray-200); border-radius: 14px; padding: 18px 20px; display: flex; align-items: center; gap: 14px; box-shadow: 0 2px 8px rgba(0,0,0,.06); transition: transform .2s, box-shadow .2s; }
.import-history-app .sc:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,.08); }
.import-history-app .sc .si { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0; }
.import-history-app .sc .si.g { background: linear-gradient(135deg, rgba(46,125,50,.15) 0%, rgba(76,175,80,.12) 100%); color: var(--primary-green); }
.import-history-app .sc .si.suc { background: linear-gradient(135deg, rgba(76,175,80,.2) 0%, rgba(129,199,132,.15) 100%); color: var(--light-green); }
.import-history-app .sc .si.b { background: linear-gradient(135deg, rgba(2,119,189,.12) 0%, rgba(3,169,244,.1) 100%); color: var(--primary-blue); }
.import-history-app .sc .si.w { background: linear-gradient(135deg, rgba(245,124,0,.15) 0%, rgba(255,152,0,.1) 100%); color: var(--warning-orange); }
.import-history-app .sc .sv { font-size: 1.5rem; font-weight: 700; letter-spacing: -0.02em; color: var(--gray-800); }
.import-history-app .sc .sl { font-size: 0.75rem; color: var(--gray-600); margin-top: 2px; font-weight: 500; text-transform: uppercase; letter-spacing: .04em; }
.import-history-app .sc .qm { height: 6px; border-radius: 3px; background: var(--gray-200); overflow: hidden; margin-top: 8px; flex: 1; min-width: 60px; }
.import-history-app .sc .qm-fill { height: 100%; border-radius: 3px; background: var(--light-green); }
.import-history-app .stat-sub { font-size: 0.8125rem; font-weight: 600; color: var(--light-green); }
.import-history-app .stat-muted { font-size: 0.75rem; color: var(--gray-600); margin-left: 4px; }
.import-history-app .btn-review-now { margin-top: 8px; padding: 6px 12px; border-radius: 8px; font-size: 0.8125rem; font-weight: 600; border: none; background: rgba(245,124,0,0.15); color: var(--warning-orange); cursor: pointer; }
.import-history-app .btn-review-now:hover { background: rgba(245,124,0,0.25); color: var(--warning-orange); }
/* Filter panel */
.import-history-app .filter-panel { background: #fff; border: 1px solid var(--gray-200); border-radius: 16px; padding: 20px 24px; box-shadow: 0 2px 8px rgba(0,0,0,.06); margin-bottom: 20px; }
.import-history-app .filter-panel .form-label { font-size: 13px; font-weight: 600; color: var(--gray-800); margin-bottom: 6px; }
.import-history-app .filter-panel .form-select { padding: 10px 14px; font-size: 14px; border: 1.5px solid var(--gray-200); border-radius: 10px; background: #fff; }
.import-history-app .filter-panel .form-select:focus { border-color: var(--primary-green); outline: none; }
.import-history-app .filter-panel .btn-reset { background: var(--gray-100); border: 1.5px solid var(--gray-200); color: var(--gray-600); padding: 8px 16px; border-radius: 10px; font-weight: 600; }
.import-history-app .filter-panel .btn-reset:hover { background: var(--gray-200); color: var(--gray-800); }
.import-history-app .filter-panel .btn-apply { background: var(--primary-green); color: #fff; border: none; padding: 8px 16px; border-radius: 10px; font-weight: 600; }
.import-history-app .filter-panel .btn-apply:hover { background: var(--dark-green); color: #fff; }
/* Chart container */
.import-history-app .chart-container { background: #fff; border: 1px solid var(--gray-200); border-radius: 16px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,.06); margin-bottom: 20px; height: 100%; }
.import-history-app .chart-title { font-size: 1rem; font-weight: 700; color: var(--gray-800); margin-bottom: 15px; }
/* DataTable card */
.import-history-app .import-datatable-card { background: #fff; border: 1px solid var(--gray-200); border-radius: 16px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.import-history-app .import-datatable-card .card-header { padding: 16px 20px; border-bottom: 1px solid var(--gray-200); background: linear-gradient(180deg, var(--gray-50) 0%, #fff 100%); }
.import-history-app .import-datatable-card .card-header h5 { font-size: 1.0625rem; font-weight: 700; color: var(--gray-800); }
.import-history-app .import-datatable-card .card-header .input-group { border-radius: 10px; overflow: hidden; border: 1px solid var(--gray-200); }
.import-history-app .import-datatable-card .card-header .input-group-text { background: var(--gray-50); border: none; color: var(--gray-600); padding: 10px 14px; }
.import-history-app .import-datatable-card .card-header .form-control { border: none; padding: 10px 14px; font-size: 0.875rem; }
.import-history-app .import-datatable-card .card-header .btn-outline-primary { border-radius: 10px; font-weight: 600; padding: 8px 16px; border: 1.5px solid var(--primary-green); color: var(--primary-green); }
.import-history-app .import-datatable-card .card-header .btn-outline-primary:hover { background: rgba(46,125,50,0.08); color: var(--primary-green); }
.import-history-app .import-datatable-card #importHistoryTable { width: 100% !important; border-collapse: separate; border-spacing: 0; }
.import-history-app .import-datatable-card #importHistoryTable thead th { background: var(--gray-100); color: var(--gray-600); font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; padding: 14px 16px; border: none; border-bottom: 1px solid var(--gray-200); }
.import-history-app .import-datatable-card #importHistoryTable thead th:first-child { padding-left: 20px; }
.import-history-app .import-datatable-card #importHistoryTable tbody td { padding: 14px 16px; font-size: 0.875rem; color: var(--gray-800); border: none; border-bottom: 1px solid var(--gray-100); vertical-align: middle; }
.import-history-app .import-datatable-card #importHistoryTable tbody td:first-child { padding-left: 20px; }
.import-history-app .import-datatable-card #importHistoryTable tbody tr:hover td { background: var(--gray-50); }
.import-history-app .import-datatable-card #importHistoryTable tbody tr:last-child td { border-bottom: none; }
.import-history-app .import-datatable-card #importHistoryTable .form-check-input { width: 1.1em; height: 1.1em; border-radius: 4px; border: 1.5px solid var(--gray-300); cursor: pointer; }
.import-history-app .import-datatable-card #importHistoryTable .form-check-input:checked { background-color: var(--primary-green); border-color: var(--primary-green); }
.import-history-app .import-datatable-card .card-footer { padding: 12px 20px; border-top: 1px solid var(--gray-200); background: var(--gray-50); font-size: 0.8125rem; color: var(--gray-600); }
.import-history-app .import-datatable-card .dataTables_wrapper { padding: 0; }
.import-history-app .import-datatable-card .dataTables_wrapper .dataTables_length,
.import-history-app .import-datatable-card .dataTables_wrapper .dataTables_filter { display: none; }
.import-history-app .import-datatable-card .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 6px 12px; margin: 0 2px; border-radius: 8px; border: 1px solid var(--gray-200); background: #fff; color: var(--gray-700) !important; font-size: 0.8125rem; font-weight: 600; }
.import-history-app .import-datatable-card .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: var(--gray-100) !important; border-color: var(--gray-300) !important; color: var(--gray-800) !important; }
.import-history-app .import-datatable-card .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: var(--primary-green) !important; border-color: var(--primary-green) !important; color: #fff !important; }
.import-history-app .import-datatable-card .dataTables_wrapper .dataTables_processing { background: rgba(255,255,255,0.95); border-radius: 10px; padding: 14px 24px; font-weight: 600; font-size: 0.875rem; color: var(--gray-700); border: 1px solid var(--gray-200); box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.import-history-app .import-datatable-card .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
/* Bottom cards */
.import-history-app .tw.tw-card { background: #fff; border: 1px solid var(--gray-200); border-radius: 16px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.06); height: 100%; }
.import-history-app .tw .card-head { padding: 16px 20px; border-bottom: 1px solid var(--gray-200); background: linear-gradient(180deg, var(--gray-50) 0%, #fff 100%); font-size: 1rem; font-weight: 700; color: var(--gray-800); }
.import-history-app .tw .stat-val { font-size: 1.5rem; font-weight: 700; }
.import-history-app .tw .stat-label { font-size: 0.8125rem; color: var(--gray-600); margin-top: 2px; }
/* Status/type badges (server-rendered in table) */
.import-history-app .status-completed { background: rgba(76,175,80,0.12); color: var(--light-green); padding: 4px 10px; border-radius: 100px; font-size: 0.75rem; font-weight: 600; }
.import-history-app .status-processing { background: rgba(3,169,244,0.12); color: var(--light-blue); padding: 4px 10px; border-radius: 100px; font-size: 0.75rem; font-weight: 600; }
.import-history-app .status-failed { background: rgba(211,47,47,0.12); color: var(--danger-red); padding: 4px 10px; border-radius: 100px; font-size: 0.75rem; font-weight: 600; }
.import-history-app .status-partial { background: rgba(255,193,7,0.12); color: #b38600; padding: 4px 10px; border-radius: 100px; font-size: 0.75rem; font-weight: 600; }
.import-history-app .status-queued { background: rgba(108,117,125,0.12); color: #6c757d; padding: 4px 10px; border-radius: 100px; font-size: 0.75rem; font-weight: 600; }
.import-history-app .action-buttons { display: flex; gap: 5px; }
.import-history-app .action-btn { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; border: none; background: var(--gray-100); color: var(--gray-600); transition: all .2s; cursor: pointer; }
.import-history-app .action-btn:hover { background: var(--gray-200); }
.import-history-app .view-btn:hover { background: rgba(3,169,244,0.1); color: var(--light-blue); }
.import-history-app .retry-btn:hover { background: rgba(40,167,69,0.1); color: #28a745; }
.import-history-app .delete-btn:hover { background: rgba(220,53,69,0.1); color: #dc3545; }
/* Timeline & logs */
.import-history-app .timeline { position: relative; padding-left: 30px; max-height: 400px; overflow-y: auto; }
.import-history-app .timeline::before { content: ''; position: absolute; left: 10px; top: 0; bottom: 0; width: 2px; background: var(--gray-200); }
.import-history-app .timeline-item { position: relative; margin-bottom: 20px; }
.import-history-app .timeline-dot { position: absolute; left: -20px; width: 12px; height: 12px; border-radius: 50%; background: var(--gray-600); }
.import-history-app .timeline-dot.success { background: var(--light-green); }
.import-history-app .timeline-dot.warning { background: var(--warning-orange); }
.import-history-app .timeline-dot.error { background: var(--danger-red); }
.import-history-app .log-level { padding: 3px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; }
.import-history-app .log-info { background: rgba(3,169,244,0.1); color: var(--light-blue); }
.import-history-app .log-warning { background: rgba(255,193,7,0.1); color: #ffc107; }
.import-history-app .log-error { background: rgba(220,53,69,0.1); color: #dc3545; }
.import-history-app .log-success { background: rgba(40,167,69,0.1); color: #28a745; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.js"></script>
<script>
let importTable;
let trendChart, distributionChart;
let selectedImportId = null;

$(document).ready(function() {
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
        drawCallback: function(settings) {
            var api = this.api();
            var info = api.page.info();
            $('#showingCount').text(info.recordsDisplay === 0 ? 0 : (info.start + 1) + '\u2013' + Math.min(info.end, info.recordsDisplay));
            $('#totalCount').text(info.recordsDisplay.toLocaleString());
            $('#selectAll').prop('checked', false);
            updateSelectedCount();
        }
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


@extends('layouts.app')

@push('styles')
<style>
.reports-app * { box-sizing: border-box; }
.reports-app { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
/* Topbar */
.reports-app .topbar { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; padding: 20px 24px; background: linear-gradient(135deg, #fff 0%, var(--gray-50) 100%); border: 1px solid var(--gray-200); border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.reports-app .topbar h2 { font-size: 1.35rem; font-weight: 700; letter-spacing: -0.02em; display: flex; align-items: center; gap: 10px; margin: 0; color: var(--gray-800); }
.reports-app .topbar h2 .sb { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%); color: #fff; font-size: 1rem; font-weight: 700; box-shadow: 0 2px 8px rgba(46,125,50,.25); }
.reports-app .topbar p { color: var(--gray-600); font-size: 0.875rem; flex: 1; min-width: 180px; margin: 0; line-height: 1.4; }
/* Stats */
.reports-app .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
.reports-app .sc { background: #fff; border: 1px solid var(--gray-200); border-radius: 14px; padding: 18px 20px; display: flex; align-items: center; gap: 14px; box-shadow: 0 2px 8px rgba(0,0,0,.06); transition: transform .2s, box-shadow .2s; }
.reports-app .sc:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,.08); }
.reports-app .sc .si { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0; }
.reports-app .sc .si.g { background: linear-gradient(135deg, rgba(46,125,50,.15) 0%, rgba(76,175,80,.12) 100%); color: var(--primary-green); }
.reports-app .sc .si.suc { background: linear-gradient(135deg, rgba(76,175,80,.2) 0%, rgba(129,199,132,.15) 100%); color: var(--light-green); }
.reports-app .sc .si.b { background: linear-gradient(135deg, rgba(2,119,189,.12) 0%, rgba(3,169,244,.1) 100%); color: var(--primary-blue); }
.reports-app .sc .si.w { background: linear-gradient(135deg, rgba(245,124,0,.15) 0%, rgba(255,152,0,.1) 100%); color: var(--warning-orange); }
.reports-app .sc .si.d { background: linear-gradient(135deg, rgba(211,47,47,.15) 0%, rgba(244,67,54,.1) 100%); color: var(--danger-red); }
.reports-app .sc .sv { font-size: 1.5rem; font-weight: 700; letter-spacing: -0.02em; color: var(--gray-800); }
.reports-app .sc .sl { font-size: 0.75rem; color: var(--gray-600); margin-top: 2px; font-weight: 500; text-transform: uppercase; letter-spacing: .04em; }
.reports-app .stat-sub { font-size: 0.8125rem; font-weight: 600; color: var(--light-green); }
.reports-app .stat-muted { font-size: 0.75rem; color: var(--gray-600); margin-left: 4px; }
.reports-app .btn-pending { margin-top: 8px; padding: 6px 12px; border-radius: 8px; font-size: 0.8125rem; font-weight: 600; border: none; background: rgba(211,47,47,0.12); color: var(--danger-red); cursor: pointer; }
.reports-app .btn-pending:hover { background: rgba(211,47,47,0.2); }
/* Quick action grid */
.reports-app .qa-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; }
.reports-app .qa-card { background: #fff; border: 1px solid var(--gray-200); border-radius: 14px; padding: 20px; text-align: center; cursor: pointer; transition: all .2s; box-shadow: 0 2px 8px rgba(0,0,0,.06); display: block; text-decoration: none; color: inherit; }
.reports-app .qa-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,.08); border-color: var(--gray-300); }
.reports-app .qa-card-link:hover { color: inherit; }
.reports-app .qa-card .qa-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 12px; }
.reports-app .qa-card .qa-icon.g { background: linear-gradient(135deg, rgba(46,125,50,.15) 0%, rgba(76,175,80,.12) 100%); color: var(--primary-green); }
.reports-app .qa-card .qa-icon.suc { background: linear-gradient(135deg, rgba(76,175,80,.2) 0%, rgba(129,199,132,.15) 100%); color: var(--light-green); }
.reports-app .qa-card .qa-icon.b { background: linear-gradient(135deg, rgba(2,119,189,.12) 0%, rgba(3,169,244,.1) 100%); color: var(--primary-blue); }
.reports-app .qa-card .qa-icon.w { background: linear-gradient(135deg, rgba(245,124,0,.15) 0%, rgba(255,152,0,.1) 100%); color: var(--warning-orange); }
.reports-app .qa-card h5 { font-size: 1rem; font-weight: 700; color: var(--gray-800); margin-bottom: 6px; }
.reports-app .qa-card p { font-size: 0.8125rem; color: var(--gray-600); margin: 0; }
/* Tabs */
.reports-app .reports-tabs.tw { background: #fff; border: 1px solid var(--gray-200); border-radius: 16px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.06); margin-bottom: 20px; }
.reports-app .nav-tabs-reports { border-bottom: 1px solid var(--gray-200); background: linear-gradient(180deg, var(--gray-50) 0%, #fff 100%); padding: 0 16px; }
.reports-app .nav-tabs-reports .nav-link { border: none; color: var(--gray-600); font-weight: 600; padding: 14px 18px; position: relative; font-size: 0.875rem; }
.reports-app .nav-tabs-reports .nav-link:hover { color: var(--gray-800); }
.reports-app .nav-tabs-reports .nav-link.active { color: var(--primary-green); background: transparent; }
.reports-app .nav-tabs-reports .nav-link.active::after { content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 3px; background: var(--primary-green); border-radius: 2px 2px 0 0; }
.reports-app .tab-content-reports { padding: 24px; }
/* Filter panel */
.reports-app .filter-panel { background: #fff; border: 1px solid var(--gray-200); border-radius: 16px; padding: 20px 24px; box-shadow: 0 2px 8px rgba(0,0,0,.06); margin-bottom: 20px; }
.reports-app .filter-panel .form-label { font-size: 13px; font-weight: 600; color: var(--gray-800); margin-bottom: 6px; }
.reports-app .filter-panel .form-select { padding: 10px 14px; font-size: 14px; border: 1.5px solid var(--gray-200); border-radius: 10px; background: #fff; }
.reports-app .filter-panel .form-select:focus { border-color: var(--primary-green); outline: none; }
.reports-app .filter-panel .btn-reset { background: var(--gray-100); border: 1.5px solid var(--gray-200); color: var(--gray-600); padding: 8px 16px; border-radius: 10px; font-weight: 600; }
.reports-app .filter-panel .btn-reset:hover { background: var(--gray-200); color: var(--gray-800); }
.reports-app .filter-panel .btn-outline { border: 1.5px solid var(--primary-green); color: var(--primary-green); background: #fff; padding: 8px 16px; border-radius: 10px; font-weight: 600; }
.reports-app .filter-panel .btn-outline:hover { background: rgba(46,125,50,0.08); color: var(--primary-green); }
.reports-app .filter-panel .btn-apply { background: var(--primary-green); color: #fff; border: none; padding: 8px 16px; border-radius: 10px; font-weight: 600; }
.reports-app .filter-panel .btn-apply:hover { background: var(--dark-green); }
/* Report cards (library grid) */
.reports-app .report-card { background: #fff; border: 1px solid var(--gray-200); border-radius: 14px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,.06); margin-bottom: 20px; transition: all .2s; border-left: 4px solid var(--primary-green); height: 100%; }
.reports-app .report-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,.08); }
.reports-app .report-card.executive { border-left-color: var(--primary-blue); }
.reports-app .report-card.regulatory { border-left-color: var(--warning-orange); }
.reports-app .report-card.internal { border-left-color: var(--light-green); }
.reports-app .report-card.public { border-left-color: #7b1fa2; }
.reports-app .report-status { padding: 4px 10px; border-radius: 100px; font-size: 0.75rem; font-weight: 600; }
.reports-app .status-published { background: rgba(76,175,80,0.12); color: var(--light-green); }
.reports-app .status-draft { background: rgba(255,193,7,0.12); color: #b38600; }
.reports-app .status-scheduled { background: rgba(3,169,244,0.12); color: var(--light-blue); }
.reports-app .status-archived { background: rgba(158,158,158,0.12); color: #9e9e9e; }
.reports-app .report-type { padding: 4px 10px; border-radius: 100px; font-size: 0.75rem; font-weight: 600; }
.reports-app .type-pdf { background: rgba(211,47,47,0.12); color: var(--danger-red); }
.reports-app .type-excel { background: rgba(46,125,50,0.12); color: var(--primary-green); }
.reports-app .type-pptx { background: rgba(245,124,0,0.12); color: var(--warning-orange); }
.reports-app .type-web { background: rgba(3,169,244,0.12); color: var(--light-blue); }
.reports-app .report-actions { display: flex; gap: 8px; margin-top: 15px; }
.reports-app .action-btn { flex: 1; padding: 8px 12px; border-radius: 8px; border: 1.5px solid var(--gray-200); background: #fff; color: var(--gray-600); font-size: 0.8125rem; display: flex; align-items: center; justify-content: center; gap: 5px; transition: all .2s; text-decoration: none; }
.reports-app .action-btn:hover { background: var(--gray-50); color: var(--gray-800); }
.reports-app .download-btn:hover { border-color: var(--primary-green); background: rgba(46,125,50,0.08); color: var(--primary-green); }
.reports-app .edit-btn:hover { border-color: var(--light-blue); background: rgba(3,169,244,0.08); color: var(--light-blue); }
.reports-app .config-label { font-weight: 600; color: var(--gray-800); margin-bottom: 5px; font-size: 0.875rem; }
/* Exports table */
.reports-app .reports-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.reports-app .reports-table thead th { background: var(--gray-100); color: var(--gray-600); font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; padding: 14px 16px; border-bottom: 1px solid var(--gray-200); }
.reports-app .reports-table tbody td { padding: 14px 16px; font-size: 0.875rem; color: var(--gray-800); border-bottom: 1px solid var(--gray-100); }
.reports-app .reports-table tbody tr:hover td { background: var(--gray-50); }
.reports-app .reports-table tbody tr:last-child td { border-bottom: none; }
/* Builder, templates, schedule */
.reports-app .report-builder { background: #fff; border: 1px solid var(--gray-200); border-radius: 16px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.reports-app .builder-section { margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid var(--gray-200); }
.reports-app .section-title { font-size: 1rem; font-weight: 700; color: var(--primary-green); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
.reports-app .component-card { border: 1.5px solid var(--gray-200); border-radius: 10px; padding: 14px; text-align: center; cursor: move; transition: all .2s; background: #fff; }
.reports-app .component-card:hover { border-color: var(--primary-green); background: rgba(46,125,50,0.06); }
.reports-app .report-preview { background: #fff; border: 1px solid var(--gray-200); border-radius: 14px; padding: 20px; min-height: 600px; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.reports-app .preview-header { text-align: center; padding-bottom: 20px; margin-bottom: 24px; border-bottom: 2px solid var(--primary-green); }
.reports-app .preview-placeholder { height: 300px; display: flex; align-items: center; justify-content: center; background: var(--gray-50); border-radius: 12px; border: 2px dashed var(--gray-200); color: var(--gray-600); }
.reports-app .template-card { background: #fff; border: 1px solid var(--gray-200); border-radius: 14px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.06); transition: all .2s; height: 100%; }
.reports-app .template-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,.08); }
.reports-app .template-header { padding: 18px 20px; background: var(--primary-green); color: #fff; }
.reports-app .template-body { padding: 20px; }
.reports-app .schedule-card { background: #fff; border: 1px solid var(--gray-200); border-radius: 14px; padding: 20px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.06); border-left: 4px solid var(--primary-blue); }
.reports-app .export-option { flex: 1; min-width: 100px; text-align: center; padding: 14px; border: 1.5px solid var(--gray-200); border-radius: 10px; cursor: pointer; transition: all .2s; background: #fff; }
.reports-app .export-option:hover { border-color: var(--primary-green); background: rgba(46,125,50,0.06); }
.reports-app .export-option.active { border-color: var(--primary-green); background: rgba(46,125,50,0.1); }
.reports-app .card { border: 1px solid var(--gray-200); border-radius: 16px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.reports-app .card-header { padding: 16px 20px; border-bottom: 1px solid var(--gray-200); background: linear-gradient(180deg, var(--gray-50) 0%, #fff 100%); font-weight: 700; color: var(--gray-800); }
</style>
@endpush

@section('title', 'Reports')
@section('page-title', 'Reports')

@section('content')
    <div id="content">
        @include('layouts.top-nav')

        <div class="reports-app container-fluid mt-4">
            <!-- Topbar -->
            <div class="topbar">
                <h2><span class="sb"><i class="fas fa-chart-pie"></i></span> Reports</h2>
                <p>Create, schedule, and export GHG reports. Use templates or build custom reports.</p>
            </div>

            <!-- Stats -->
            <div class="stats">
                <div class="sc">
                    <div class="si g"><i class="fas fa-file-pdf"></i></div>
                    <div style="flex:1;min-width:0;">
                        <div class="sv" id="totalReports">0</div>
                        <div class="sl">Total Reports</div>
                        <span class="stat-sub" id="reportsThisMonth">+0</span> <span class="stat-muted">this month</span>
                    </div>
                </div>
                <div class="sc">
                    <div class="si b"><i class="fas fa-calendar-alt"></i></div>
                    <div style="flex:1;min-width:0;">
                        <div class="sv" id="scheduledReports">0</div>
                        <div class="sl">Scheduled</div>
                        <span class="stat-sub" id="dueToday">0 due today</span>
                    </div>
                </div>
                <div class="sc">
                    <div class="si w"><i class="fas fa-users"></i></div>
                    <div style="flex:1;min-width:0;">
                        <div class="sv" id="sharedReports">0</div>
                        <div class="sl">Shared</div>
                        <span class="stat-sub"><i class="fas fa-eye me-1"></i><span id="viewsThisWeek">0 views this week</span></span>
                    </div>
                </div>
                <div class="sc">
                    <div class="si d"><i class="fas fa-clock"></i></div>
                    <div style="flex:1;min-width:0;">
                        <div class="sv" id="pendingReports">0</div>
                        <div class="sl">Pending</div>
                        <button type="button" class="btn-pending" onclick="showPendingReports()">
                            <i class="fas fa-external-link-alt me-1"></i>Review Now
                        </button>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="qa-grid">
                <div class="qa-card" onclick="startNewReport()">
                    <div class="qa-icon g"><i class="fas fa-plus-circle"></i></div>
                    <h5>New Report</h5>
                    <p>Create a custom report from scratch</p>
                </div>
                <div class="qa-card" onclick="useTemplateWizard()">
                    <div class="qa-icon suc"><i class="fas fa-clone"></i></div>
                    <h5>Use Template</h5>
                    <p>Start with a pre-designed template</p>
                </div>
                <div class="qa-card" onclick="scheduleNewReport()">
                    <div class="qa-icon w"><i class="fas fa-clock"></i></div>
                    <h5>Schedule Report</h5>
                    <p>Set up automated report generation</p>
                </div>
                <div class="qa-card" onclick="exportAllReports()">
                    <div class="qa-icon b"><i class="fas fa-download"></i></div>
                    <h5>Export All</h5>
                    <p>Batch export in multiple formats</p>
                </div>
                <a href="{{ route('reports.ghg_protocol') }}" class="qa-card qa-card-link">
                    <div class="qa-icon b"><i class="fas fa-file-contract"></i></div>
                    <h5>GHG Protocol Report</h5>
                    <p>Generate GHG Protocol compliance report</p>
                </a>
            </div>

            <!-- Reports Tabs -->
            <div class="reports-tabs tw">
            <ul class="nav nav-tabs nav-tabs-reports" id="reportsTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="library-tab" data-bs-toggle="tab" data-bs-target="#library" type="button">
                        <i class="fas fa-folder me-2"></i>Report Library
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="builder-tab" data-bs-toggle="tab" data-bs-target="#builder" type="button">
                        <i class="fas fa-tools me-2"></i>Report Builder
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="templates-tab" data-bs-toggle="tab" data-bs-target="#templates" type="button">
                        <i class="fas fa-clone me-2"></i>Templates
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="scheduled-tab" data-bs-toggle="tab" data-bs-target="#scheduled" type="button">
                        <i class="fas fa-calendar-alt me-2"></i>Scheduled
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="exports-tab" data-bs-toggle="tab" data-bs-target="#exports" type="button">
                        <i class="fas fa-download me-2"></i>Exports
                    </button>
                </li>
            </ul>
            
            <div class="tab-content tab-content-reports" id="reportsTabContent">
                
                <!-- Report Library Tab -->
                <div class="tab-pane fade show active" id="library" role="tabpanel">
                    <!-- Filter Panel -->
                    <div class="filter-panel">
                        <div class="row g-3">
                            <div class="col-lg-3 col-md-6">
                                <label class="form-label">Report Type</label>
                                <select class="form-select" id="reportTypeFilter">
                                    <option value="all">All Types</option>
                                    <option value="executive">Executive Summary</option>
                                    <option value="regulatory">Regulatory Compliance</option>
                                    <option value="internal">Internal Analysis</option>
                                    <option value="public">Public Disclosure</option>
                                </select>
                            </div>
                            
                            <div class="col-lg-3 col-md-6">
                                <label class="form-label">Date Range</label>
                                <select class="form-select" id="dateRangeFilter">
                                    <option value="all">All Time</option>
                                    <option value="today">Today</option>
                                    <option value="week">Last 7 Days</option>
                                    <option value="month" selected>Last 30 Days</option>
                                    <option value="quarter">Last Quarter</option>
                                </select>
                            </div>
                            
                            <div class="col-lg-3 col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" id="statusFilter">
                                    <option value="all">All Status</option>
                                    <option value="published">Published</option>
                                    <option value="draft">Draft</option>
                                    <option value="scheduled">Scheduled</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>
                            
                            <div class="col-lg-3 col-md-6">
                                <label class="form-label">Format</label>
                                <select class="form-select" id="formatFilter">
                                    <option value="all">All Formats</option>
                                    <option value="pdf">PDF</option>
                                    <option value="excel">Excel</option>
                                    <option value="pptx">PowerPoint</option>
                                    <option value="web">Web Dashboard</option>
                                </select>
                            </div>
                            
                            <div class="col-12 mt-3 d-flex justify-content-between flex-wrap gap-2">
                                <button type="button" class="btn btn-reset" id="resetFilters">
                                    <i class="fas fa-redo me-2"></i>Reset Filters
                                </button>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline" id="saveFilterBtn">
                                        <i class="fas fa-save me-2"></i>Save Filter
                                    </button>
                                    <button type="button" class="btn btn-apply" id="applyFilters" onclick="loadReports()">
                                        <i class="fas fa-filter me-2"></i>Apply Filters
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Reports Grid -->
                    <div class="row" id="reportsGrid">
                        <!-- Reports will be loaded here via AJAX -->
                    </div>
                    
                    <!-- Load More -->
                    <div class="text-center mt-4">
                        <button class="btn btn-outline-primary" onclick="loadMoreReports()">
                            <i class="fas fa-sync me-2"></i>Load More Reports
                        </button>
                    </div>
                </div>
                
                <!-- Report Builder Tab -->
                <div class="tab-pane fade" id="builder" role="tabpanel">
                    <div class="report-builder">
                        <div class="row">
                            <!-- Components Library -->
                            <div class="col-lg-3">
                                <div class="builder-section">
                                    <div class="section-title">
                                        <i class="fas fa-shapes"></i> Components
                                    </div>
                                    
                                    <div class="mb-3">
                                        <h6>Visualizations</h6>
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <div class="component-card" draggable="true" data-type="chart-line">
                                                    <i class="fas fa-chart-line mb-2"></i>
                                                    <div class="small">Line Chart</div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="component-card" draggable="true" data-type="chart-bar">
                                                    <i class="fas fa-chart-bar mb-2"></i>
                                                    <div class="small">Bar Chart</div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="component-card" draggable="true" data-type="chart-pie">
                                                    <i class="fas fa-chart-pie mb-2"></i>
                                                    <div class="small">Pie Chart</div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="component-card" draggable="true" data-type="chart-area">
                                                    <i class="fas fa-chart-area mb-2"></i>
                                                    <div class="small">Area Chart</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <h6>Data Tables</h6>
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <div class="component-card" draggable="true" data-type="table-summary">
                                                    <i class="fas fa-table mb-2"></i>
                                                    <div class="small">Summary Table</div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="component-card" draggable="true" data-type="table-detailed">
                                                    <i class="fas fa-list-alt mb-2"></i>
                                                    <div class="small">Detailed Table</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <h6>Text & Media</h6>
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <div class="component-card" draggable="true" data-type="text-header">
                                                    <i class="fas fa-heading mb-2"></i>
                                                    <div class="small">Header</div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="component-card" draggable="true" data-type="text-paragraph">
                                                    <i class="fas fa-paragraph mb-2"></i>
                                                    <div class="small">Paragraph</div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="component-card" draggable="true" data-type="media-image">
                                                    <i class="fas fa-image mb-2"></i>
                                                    <div class="small">Image</div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="component-card" draggable="true" data-type="text-keypoint">
                                                    <i class="fas fa-star mb-2"></i>
                                                    <div class="small">Key Point</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="builder-section">
                                    <div class="section-title">
                                        <i class="fas fa-sliders-h"></i> Settings
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Report Title</label>
                                        <input type="text" class="form-control" id="reportTitle" value="New Custom Report">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Report Type</label>
                                        <select class="form-select" id="reportType">
                                            <option value="internal">Internal Analysis</option>
                                            <option value="executive">Executive Summary</option>
                                            <option value="regulatory">Regulatory Compliance</option>
                                            <option value="public">Public Disclosure</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Data Range</label>
                                        <select class="form-select" id="dataRange">
                                            <option value="last-month">Last Month</option>
                                            <option value="last-quarter">Last Quarter</option>
                                            <option value="last-year">Last Year</option>
                                            <option value="year-to-date">Year to Date</option>
                                            <option value="custom">Custom Range</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Include Scopes</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="scope1" checked>
                                            <label class="form-check-label" for="scope1">Scope 1</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="scope2" checked>
                                            <label class="form-check-label" for="scope2">Scope 2</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="scope3">
                                            <label class="form-check-label" for="scope3">Scope 3</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Report Preview -->
                            <div class="col-lg-9">
                                <div class="report-preview">
                                    <div class="preview-header">
                                        <h3 id="previewTitle">New Custom Report</h3>
                                        <p class="text-muted mb-0">Report Preview - Drag and drop components from the left</p>
                                    </div>
                                    
                                    <div id="reportComponents">
                                        <div class="preview-placeholder">
                                            <div class="text-center">
                                                <i class="fas fa-mouse-pointer fa-3x mb-3 text-muted"></i>
                                                <h5>Drag components here</h5>
                                                <p class="text-muted">Start building your report by dragging components from the library</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Builder Actions -->
                                <div class="d-flex justify-content-between mt-4">
                                    <div>
                                        <button class="btn btn-outline-secondary" onclick="clearReport()">
                                            <i class="fas fa-trash me-2"></i>Clear Report
                                        </button>
                                        <button class="btn btn-outline-primary ms-2" onclick="saveAsDraft()">
                                            <i class="fas fa-save me-2"></i>Save as Draft
                                        </button>
                                    </div>
                                    
                                    <div class="export-options">
                                        <div class="export-option" onclick="selectExportFormat('pdf')">
                                            <i class="fas fa-file-pdf fa-2x mb-2 text-danger"></i>
                                            <div>PDF</div>
                                        </div>
                                        <div class="export-option active" onclick="selectExportFormat('excel')">
                                            <i class="fas fa-file-excel fa-2x mb-2 text-success"></i>
                                            <div>Excel</div>
                                        </div>
                                        <div class="export-option" onclick="selectExportFormat('pptx')">
                                            <i class="fas fa-file-powerpoint fa-2x mb-2 text-warning"></i>
                                            <div>PowerPoint</div>
                                        </div>
                                        <div class="export-option" onclick="selectExportFormat('web')">
                                            <i class="fas fa-globe fa-2x mb-2 text-primary"></i>
                                            <div>Web Dashboard</div>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <button class="btn btn-success" onclick="generateReport()">
                                            <i class="fas fa-magic me-2"></i>Generate Report
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Templates Tab -->
                <div class="tab-pane fade" id="templates" role="tabpanel">
                    <div class="row">
                        <!-- Template Categories -->
                        <div class="col-lg-3 mb-4">
                            <div class="card h-100">
                                <div class="card-header bg-white">
                                    <h6 class="mb-0">Template Categories</h6>
                                </div>
                                <div class="card-body">
                                    <div class="list-group list-group-flush" id="templateCategories">
                                        @php
                                            $categories = [
                                                'executive' => ['icon' => 'fa-chart-line', 'label' => 'Executive Reports'],
                                                'compliance' => ['icon' => 'fa-balance-scale', 'label' => 'Compliance Reports'],
                                                'facility' => ['icon' => 'fa-industry', 'label' => 'Facility Reports'],
                                                'stakeholder' => ['icon' => 'fa-users', 'label' => 'Stakeholder Reports'],
                                                'periodic' => ['icon' => 'fa-calendar-alt', 'label' => 'Periodic Reports'],
                                                'target-tracking' => ['icon' => 'fa-bullseye', 'label' => 'Target Tracking'],
                                            ];
                                            $activeCategory = 'executive';
                                        @endphp
                                        @foreach($categories as $key => $category)
                                            <a href="#" class="list-group-item list-group-item-action {{ $key === $activeCategory ? 'active' : '' }}" 
                                               onclick="filterTemplatesByCategory('{{ $key }}'); return false;">
                                                <i class="fas {{ $category['icon'] }} me-2"></i>{{ $category['label'] }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Templates Grid -->
                        <div class="col-lg-9">
                            <div class="row" id="templatesGrid">
                                @forelse($templates->flatten() as $template)
                                    @php
                                        $categoryColors = [
                                            'executive' => '',
                                            'compliance' => 'style="background-color: var(--warning-orange);"',
                                            'facility' => 'style="background-color: var(--light-green);"',
                                            'stakeholder' => 'style="background-color: var(--primary-blue);"',
                                            'periodic' => 'style="background-color: var(--purple);"',
                                            'target-tracking' => 'style="background-color: var(--danger-red);"',
                                        ];
                                        $color = $categoryColors[$template->category] ?? '';
                                        $formats = $template->formats ?? [];
                                    @endphp
                                    <div class="col-md-6 col-lg-4 mb-4 template-item" data-category="{{ $template->category }}">
                                        <div class="template-card">
                                            <div class="template-header" {!! $color !!}>
                                                <h5 class="mb-0">{{ $template->name }}</h5>
                                                <small>{{ ucfirst(str_replace('-', ' ', $template->category)) }}</small>
                                            </div>
                                            <div class="template-body">
                                                <p class="small text-muted">{{ $template->description ?? 'No description available' }}</p>
                                                <div class="mb-3">
                                                    @foreach($formats as $format)
                                                        <span class="badge bg-light text-dark me-1">{{ strtoupper($format) }}</span>
                                                    @endforeach
                                                    @if(empty($formats))
                                                        <span class="badge bg-light text-dark">PDF</span>
                                                    @endif
                                                </div>
                                                <button class="btn btn-primary w-100" onclick="useTemplate({{ $template->id }})">
                                                    <i class="fas fa-clone me-2"></i>Use Template
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>No templates available. Create your first template to get started.
                                        </div>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Scheduled Reports Tab -->
                <div class="tab-pane fade" id="scheduled" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="mb-0">Scheduled Reports</h5>
                            <p class="text-muted mb-0">Automated report generation and distribution</p>
                        </div>
                        <button class="btn btn-primary" onclick="scheduleNewReport()">
                            <i class="fas fa-plus me-2"></i>New Schedule
                        </button>
                    </div>
                    
                    <!-- Scheduled Reports List -->
                    <div id="scheduledReportsList">
                        @forelse($scheduledReports as $scheduled)
                            <div class="schedule-card">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">{{ $scheduled->name }}</h6>
                                        <div class="text-muted small">{{ $scheduled->description ?? 'No description' }}</div>
                                        <div class="mt-2">
                                            <span class="badge bg-primary me-2">{{ ucfirst($scheduled->frequency) }}</span>
                                            <span class="badge bg-{{ $scheduled->status === 'active' ? 'success' : ($scheduled->status === 'paused' ? 'warning' : 'secondary') }}">
                                                {{ ucfirst($scheduled->status) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold">Next: {{ $scheduled->next_run_date ? $scheduled->next_run_date->format('M d, Y') : 'Not scheduled' }}</div>
                                        <div class="text-muted small">{{ $scheduled->schedule_time ? \Carbon\Carbon::parse($scheduled->schedule_time)->format('g:i A') : 'N/A' }}</div>
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <div class="small">
                                            <i class="fas fa-user me-1"></i> Recipients: {{ $scheduled->recipients ? count($scheduled->recipients) : 0 }}
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="small">
                                            <i class="fas fa-history me-1"></i> Last run: {{ $scheduled->last_run_date ? $scheduled->last_run_date->format('M d, Y') : 'Never' }}
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="d-flex justify-content-end gap-2">
                                            <button class="btn btn-sm btn-outline-primary" onclick="runScheduleNow({{ $scheduled->id }})">
                                                <i class="fas fa-play"></i> Run Now
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary" onclick="editSchedule({{ $scheduled->id }})">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>No scheduled reports. Create one to automate report generation.
                            </div>
                        @endforelse
                    </div>
                </div>
                
                <!-- Exports Tab -->
                <div class="tab-pane fade" id="exports" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Export Manager</h5>
                                    <button class="btn btn-sm btn-primary" onclick="createNewExport()">
                                        <i class="fas fa-plus me-2"></i>New Export
                                    </button>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0 reports-table">
                                            <thead>
                                                <tr>
                                                    <th>Export Job</th>
                                                    <th>Format</th>
                                                    <th>Status</th>
                                                    <th>Created</th>
                                                    <th>Size</th>
                    <th>Actions</th>
                </tr>
                </thead>
                                            <tbody id="exportsTableBody">
                                                @forelse($exportJobs as $job)
                                                    <tr>
                                                        <td>
                                                            <strong>{{ $job->name }}</strong>
                                                            <div class="text-muted small">{{ $job->description ?? 'No description' }}</div>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-success">{{ strtoupper($job->format) }}</span>
                                                        </td>
                                                        <td>
                                                            @php
                                                                $statusColors = [
                                                                    'pending' => 'warning',
                                                                    'processing' => 'info',
                                                                    'completed' => 'success',
                                                                    'failed' => 'danger',
                                                                ];
                                                                $color = $statusColors[$job->status] ?? 'secondary';
                                                            @endphp
                                                            <span class="badge bg-{{ $color }}">{{ ucfirst($job->status) }}</span>
                                                        </td>
                                                        <td>{{ $job->created_at->format('M d, Y g:i A') }}</td>
                                                        <td>{{ $job->file_size ?? 'N/A' }}</td>
                                                        <td>
                                                            @if($job->status === 'completed' && $job->file_path)
                                                                <button class="btn btn-sm btn-outline-primary" onclick="downloadExport({{ $job->id }})">
                                                                    <i class="fas fa-download"></i>
                                                                </button>
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted">No export jobs yet</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
            </table>
                                    </div>
                                </div>
        </div>
    </div>

                        <div class="col-lg-4">
                            <div class="card h-100">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Export Settings</h5>
                        </div>
                                <div class="card-body">
                                    <h6 class="mb-3">Export Formats</h6>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="exportPdf" checked>
                                        <label class="form-check-label" for="exportPdf">
                                            PDF Documents
                                        </label>
                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="exportExcel" checked>
                                        <label class="form-check-label" for="exportExcel">
                                            Excel Spreadsheets
                                        </label>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="exportCsv">
                                        <label class="form-check-label" for="exportCsv">
                                            CSV Data Files
                                        </label>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="exportImages">
                                        <label class="form-check-label" for="exportImages">
                                            Chart Images (PNG)
                                        </label>
                                    </div>
                                    
                                    <hr class="my-4">
                                    
                                    <h6 class="mb-3">Export Options</h6>
                                    <div class="mb-3">
                                        <label class="form-label">Compression</label>
                                        <select class="form-select">
                                            <option value="none">No Compression</option>
                                            <option value="zip" selected>ZIP Archive</option>
                                            <option value="high">High Compression</option>
                                </select>
                            </div>
                                    <div class="mb-3">
                                        <label class="form-label">File Naming</label>
                                        <select class="form-select">
                                            <option value="default">Report Name + Date</option>
                                            <option value="simple">Report Name Only</option>
                                            <option value="detailed">Full Details</option>
                                </select>
                            </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="includeMetadata" checked>
                                        <label class="form-check-label" for="includeMetadata">
                                            Include Metadata
                                        </label>
                            </div>
                                    
                                    <button class="btn btn-success w-100 mt-3" onclick="saveExportSettings()">
                                        <i class="fas fa-save me-2"></i>Save Settings
                                    </button>
                            </div>
                            </div>
                        </div>
                    </div>
                    </div>
            </div>
        </div>
        </div>
    </div>

    <!-- Report Generation Modal -->
    <div class="modal fade" id="generateModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center p-5">
                    <div class="mb-4">
                        <div class="spinner-border text-primary" style="width: 4rem; height: 4rem;" role="status">
                            <span class="visually-hidden">Loading...</span>
                </div>
                    </div>
                    <h5 class="mb-3">Generating Report</h5>
                    <p class="text-muted">Your report is being prepared. This may take a few moments.</p>
                    <div class="progress mt-4">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center p-5">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h5 class="mb-3">Report Generated!</h5>
                    <p class="text-muted">Your report has been generated successfully.</p>
                    <button type="button" class="btn btn-success w-100" data-bs-dismiss="modal">
                        Download Now
                    </button>
                    <button type="button" class="btn btn-outline-secondary w-100 mt-2" data-bs-dismiss="modal">
                        View in Library
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- New/Edit Report Modal -->
    <div class="modal fade" id="newReportModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reportModalTitle">Create New Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="reportForm">
                        <input type="hidden" id="reportId" name="id">
                        
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Report Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="reportName" name="report_name" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Facility <span class="text-danger">*</span></label>
                                <select class="form-select" id="reportFacility" name="facility_id" required onchange="loadDepartments(this.value)">
                                    <option value="">Select Facility...</option>
                                    @foreach($facilities as $facility)
                                        <option value="{{ $facility->id }}">{{ $facility->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Department</label>
                                <select class="form-select" id="reportDepartment" name="department_id">
                                    <option value="">Select Department...</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}" data-facility-id="{{ $department->facility_id }}" style="display: none;">
                                            {{ $department->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Period <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="reportPeriod" name="period" placeholder="e.g., Q3 2023, January 2024" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Generated Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="reportGeneratedAt" name="generated_at" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Report Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="reportType" name="type" required>
                                    <option value="executive">Executive Summary</option>
                                    <option value="regulatory">Regulatory Compliance</option>
                                    <option value="internal" selected>Internal Analysis</option>
                                    <option value="public">Public Disclosure</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="reportStatus" name="status" required>
                                    <option value="draft" selected>Draft</option>
                                    <option value="published">Published</option>
                                    <option value="scheduled">Scheduled</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveReport()">
                        <i class="fas fa-save me-2"></i>Save Report
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script>
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

        // Load statistics on page load
        $(document).ready(function() {
            loadStatistics();
            loadReports();
        });
        
        function loadStatistics() {
            $.get("{{ route('reports.statistics') }}", function(data) {
                $('#totalReports').text(data.total || 0);
                $('#reportsThisMonth').text('+' + (data.this_month || 0));
                $('#scheduledReports').text(data.scheduled || 0);
                $('#dueToday').text((data.due_today || 0) + ' due today');
                $('#sharedReports').text(data.shared || 0);
                $('#viewsThisWeek').text((data.views_week || 0) + ' views this week');
                $('#pendingReports').text(data.pending || 0);
            }).fail(function() {
                // Default values if endpoint doesn't exist yet
                $('#totalReports').text('{{ $total ?? 0 }}');
                $('#reportsThisMonth').text('+{{ $thisMonth ?? 0 }}');
                $('#scheduledReports').text('0');
                $('#dueToday').text('0 due today');
                $('#sharedReports').text('{{ $published ?? 0 }}');
                $('#viewsThisWeek').text('0 views this week');
                $('#pendingReports').text('{{ $pending ?? 0 }}');
            });
        }
        
        function loadReports() {
            const filters = {
                type: $('#reportTypeFilter').val() || 'all',
                status: $('#statusFilter').val() || 'all',
                date_range: $('#dateRangeFilter').val() || 'all',
            };
            
            $.get("{{ route('reports.json') }}", filters, function(data) {
                renderReports(data.data || []);
            }).fail(function() {
                // Fallback: load via DataTables endpoint
                const table = $('#reportsTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('reports.data') }}",
                        data: function(d) {
                            d.type = filters.type;
                            d.status = filters.status;
                            d.date_range = filters.date_range;
                        }
                    },
                    columns: [
                        { data: 'report_name', name: 'report_name' },
                        { data: 'type_badge', name: 'type' },
                        { data: 'status_badge', name: 'status' },
                        { data: 'period', name: 'period' },
                        { data: 'generated_at', name: 'generated_at' },
                        { data: 'author_name', name: 'user.name' },
                        { data: 'actions', name: 'actions', orderable: false, searchable: false }
                    ]
                });
            });
        }
        
        function renderReports(reports) {
            const grid = $('#reportsGrid');
            grid.empty();
            
            if (reports.length === 0) {
                grid.html('<div class="col-12"><div class="alert alert-info">No reports found. Create your first report!</div></div>');
                return;
            }
            
            reports.forEach(function(report) {
                const card = createReportCard(report);
                grid.append(card);
            });
        }
        
        function createReportCard(report) {
            const statusClass = report.status === 'published' ? 'status-published' : 
                               report.status === 'draft' ? 'status-draft' :
                               report.status === 'scheduled' ? 'status-scheduled' : 'status-archived';
            
            const statusIcon = report.status === 'published' ? 'fa-check-circle' :
                              report.status === 'draft' ? 'fa-pencil-alt' :
                              report.status === 'scheduled' ? 'fa-clock' : 'fa-archive';
            
            const statusText = report.status ? report.status.charAt(0).toUpperCase() + report.status.slice(1) : 'Draft';
            
            return `
                <div class="col-xl-4 col-md-6">
                    <div class="report-card ${report.type || 'internal'}">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="mb-1">${report.report_name || 'Untitled Report'}</h5>
                                <span class="report-type type-pdf">
                                    <i class="fas fa-file-pdf me-1"></i>PDF
                                </span>
                            </div>
                            <span class="report-status ${statusClass}">
                                <i class="fas ${statusIcon}"></i>${statusText}
                            </span>
                        </div>
                        
                        <p class="text-muted small mb-3">${report.period || 'No period specified'}</p>
                        
                        <div class="mb-3">
                            <div class="config-label">Report Details</div>
                            <div class="small">
                                <i class="fas fa-building me-1"></i> Facility: ${report.facility?.name || 'N/A'}
                                <br>
                                <i class="fas fa-sitemap me-1"></i> Department: ${report.department?.name || 'N/A'}
                                <br>
                                <i class="fas fa-calendar me-1"></i> Generated: ${report.generated_at || 'N/A'}
                                <br>
                                <i class="fas fa-user me-1"></i> Author: ${report.user?.name || 'Unknown'}
                            </div>
                        </div>
                        
                        <div class="report-actions">
                            <a href="#" class="action-btn download-btn" onclick="downloadReport(${report.id})">
                                <i class="fas fa-download"></i> Download
                            </a>
                            <a href="#" class="action-btn edit-btn" onclick="editReport(${report.id})">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="#" class="action-btn delete-btn" onclick="deleteReport(${report.id})">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Drag and drop functionality for report builder
        document.addEventListener('DOMContentLoaded', function() {
            const components = document.querySelectorAll('.component-card');
            const reportArea = document.getElementById('reportComponents');
            
            components.forEach(component => {
                component.addEventListener('dragstart', function(e) {
                    e.dataTransfer.setData('text/plain', this.getAttribute('data-type'));
                });
            });
            
            reportArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.style.backgroundColor = 'rgba(46, 125, 50, 0.05)';
            });
            
            reportArea.addEventListener('dragleave', function(e) {
                this.style.backgroundColor = '';
            });
            
            reportArea.addEventListener('drop', function(e) {
                e.preventDefault();
                this.style.backgroundColor = '';
                
                const componentType = e.dataTransfer.getData('text/plain');
                addComponentToReport(componentType);
            });
            
            // Update preview title when input changes
            document.getElementById('reportTitle').addEventListener('input', function() {
                document.getElementById('previewTitle').textContent = this.value;
                });
            });

        // Report actions
        function downloadReport(reportId) {
            showToast(`Downloading report ${reportId}...`, 'info');
            window.location.href = `/reports/${reportId}/download`;
        }
        
        async function editReport(reportId) {
            try {
                const response = await $.get("{{ url('reports') }}/" + reportId);
                if (response) {
                    // Fill form
                    $('#reportId').val(response.id);
                    $('#reportName').val(response.report_name || '');
                    $('#reportFacility').val(response.facility_id || '');
                    
                    // Load departments for selected facility
                    if (response.facility_id) {
                        loadDepartments(response.facility_id, response.department_id);
                    }
                    
                    $('#reportPeriod').val(response.period || '');
                    $('#reportType').val(response.type || 'internal');
                    $('#reportStatus').val(response.status || 'draft');
                    $('#reportGeneratedAt').val(response.generated_at ? (response.generated_at.split(' ')[0] || response.generated_at) : '');
                    
                    // Update modal title
                    $('#reportModalTitle').text('Edit Report');
                    
                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('newReportModal'));
                    modal.show();
                }
            } catch (error) {
                showToast('Error loading report: ' + (error.responseJSON?.message || error.statusText), 'error');
            }
        }
        
        // Load departments based on selected facility
        function loadDepartments(facilityId, selectedDepartmentId = null) {
            const departmentSelect = $('#reportDepartment');
            departmentSelect.find('option').each(function() {
                const option = $(this);
                if (option.val() === '') {
                    option.show();
                } else {
                    const optionFacilityId = option.data('facility-id');
                    if (optionFacilityId == facilityId) {
                        option.show();
                    } else {
                        option.hide();
                    }
                }
            });
            
            // Reset selection
            departmentSelect.val('');
            
            // Set selected department if provided
            if (selectedDepartmentId) {
                departmentSelect.val(selectedDepartmentId);
            }
        }
        
        function shareReport(reportId) {
            showToast(`Sharing options for report ${reportId}`, 'info');
        }
        
        function deleteReport(reportId) {
            if (!confirm('Are you sure you want to delete this report?')) return;
            
            $.ajax({
                url: "{{ url('reports') }}/" + reportId,
                method: 'DELETE',
                success: function(response) {
                    showToast('Report deleted successfully', 'success');
                    loadReports();
                    loadStatistics();
                },
                error: function(xhr) {
                    showToast('Error deleting report: ' + (xhr.responseJSON?.message || 'Unknown error'), 'error');
                }
            });
        }
        
        // Quick actions
        function startNewReport() {
            // Clear form
            $('#reportForm')[0].reset();
            $('#reportId').val('');
            $('#reportGeneratedAt').val(new Date().toISOString().split('T')[0]);
            $('#reportModalTitle').text('Create New Report');
            
            // Reset department dropdown
            $('#reportDepartment').find('option').hide();
            $('#reportDepartment').find('option[value=""]').show();
            $('#reportDepartment').val('');
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('newReportModal'));
            modal.show();
        }
        
        // Save report
        function saveReport() {
            const formData = {
                id: $('#reportId').val() || null,
                facility_id: $('#reportFacility').val(),
                department_id: $('#reportDepartment').val() || null,
                report_name: $('#reportName').val(),
                period: $('#reportPeriod').val(),
                type: $('#reportType').val(),
                status: $('#reportStatus').val(),
                generated_at: $('#reportGeneratedAt').val(),
            };
            
            $.ajax({
                url: "{{ route('reports.storeOrUpdate') }}",
                method: 'POST',
                data: formData,
                success: function(response) {
                    showToast(response.message || 'Report saved successfully', 'success');
                    $('#newReportModal').modal('hide');
                    loadReports();
                    loadStatistics();
                },
                error: function(xhr) {
                    const errors = xhr.responseJSON?.errors || {};
                    let errorMsg = xhr.responseJSON?.message || 'Error saving report';
                    if (Object.keys(errors).length > 0) {
                        errorMsg = Object.values(errors).flat().join(', ');
                    }
                    showToast(errorMsg, 'error');
                }
            });
        }
        
        function useTemplateWizard() {
            const templatesTab = document.getElementById('templates-tab');
            templatesTab.click();
            showToast('Select a template to begin', 'info');
        }
        
        function scheduleNewReport() {
            const scheduledTab = document.getElementById('scheduled-tab');
            scheduledTab.click();
            showToast('Create a new scheduled report', 'info');
        }
        
        function exportAllReports() {
            const exportsTab = document.getElementById('exports-tab');
            exportsTab.click();
            showToast('Export all reports', 'info');
        }
        
        function showPendingReports() {
            document.getElementById('statusFilter').value = 'draft';
            document.getElementById('applyFilters').click();
            showToast('Showing pending reports', 'info');
        }
        
        // Report builder functions
        function addComponentToReport(type) {
            const reportArea = document.getElementById('reportComponents');
            
            const placeholder = reportArea.querySelector('.preview-placeholder');
            if (placeholder) {
                placeholder.remove();
            }
            
            let componentHTML = '';
            const componentId = `component-${Date.now()}`;
            
            switch(type) {
                case 'chart-line':
                    componentHTML = `
                        <div class="card mb-3" id="${componentId}">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Line Chart</h6>
                                <button class="btn btn-sm btn-outline-danger" onclick="removeComponent('${componentId}')">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="chart-placeholder text-center p-4">
                                    <i class="fas fa-chart-line fa-2x text-muted mb-3"></i>
                                    <p class="text-muted">Emissions Trend Chart</p>
                                </div>
                            </div>
                        </div>
                    `;
                    break;
                    
                case 'chart-bar':
                    componentHTML = `
                        <div class="card mb-3" id="${componentId}">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Bar Chart</h6>
                                <button class="btn btn-sm btn-outline-danger" onclick="removeComponent('${componentId}')">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="chart-placeholder text-center p-4">
                                    <i class="fas fa-chart-bar fa-2x text-muted mb-3"></i>
                                    <p class="text-muted">Emissions by Scope</p>
                                </div>
                            </div>
                        </div>
                    `;
                    break;
                    
                case 'table-summary':
                    componentHTML = `
                        <div class="card mb-3" id="${componentId}">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Summary Table</h6>
                                <button class="btn btn-sm btn-outline-danger" onclick="removeComponent('${componentId}')">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-placeholder text-center p-4">
                                    <i class="fas fa-table fa-2x text-muted mb-3"></i>
                                    <p class="text-muted">Emissions Summary Table</p>
                                </div>
                            </div>
                        </div>
                    `;
                    break;
                    
                case 'text-header':
                    componentHTML = `
                        <div class="card mb-3" id="${componentId}">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Header</h6>
                                <button class="btn btn-sm btn-outline-danger" onclick="removeComponent('${componentId}')">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="card-body">
                                <input type="text" class="form-control" value="Section Header" placeholder="Enter header text...">
                            </div>
                        </div>
                    `;
                    break;
            }
            
            reportArea.insertAdjacentHTML('beforeend', componentHTML);
            showToast('Component added to report', 'success');
        }
        
        function removeComponent(componentId) {
            document.getElementById(componentId).remove();
            showToast('Component removed', 'success');
            
            const reportArea = document.getElementById('reportComponents');
            if (reportArea.children.length === 0) {
                reportArea.innerHTML = `
                    <div class="preview-placeholder">
                        <div class="text-center">
                            <i class="fas fa-mouse-pointer fa-3x mb-3 text-muted"></i>
                            <h5>Drag components here</h5>
                            <p class="text-muted">Start building your report by dragging components from the library</p>
                        </div>
                    </div>
                `;
            }
        }
        
        function clearReport() {
            const reportArea = document.getElementById('reportComponents');
            reportArea.innerHTML = `
                <div class="preview-placeholder">
                    <div class="text-center">
                        <i class="fas fa-mouse-pointer fa-3x mb-3 text-muted"></i>
                        <h5>Drag components here</h5>
                        <p class="text-muted">Start building your report by dragging components from the library</p>
                    </div>
                </div>
            `;
            
            document.getElementById('reportTitle').value = 'New Custom Report';
            document.getElementById('previewTitle').textContent = 'New Custom Report';
            
            showToast('Report cleared', 'info');
        }
        
        function saveAsDraft() {
            showToast('Report saved as draft', 'success');
        }
        
        function selectExportFormat(format) {
            document.querySelectorAll('.export-option').forEach(option => {
                option.classList.remove('active');
            });
            event.target.closest('.export-option').classList.add('active');
            showToast(`${format.toUpperCase()} format selected`, 'info');
        }
        
        function generateReport() {
            const modal = new bootstrap.Modal(document.getElementById('generateModal'));
            modal.show();
            
            setTimeout(() => {
                modal.hide();
                const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                successModal.show();
            }, 3000);
        }
        
        // Template functions
        function filterTemplatesByCategory(category) {
            // Update active category
            document.querySelectorAll('#templateCategories .list-group-item').forEach(item => {
                item.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Filter templates
            document.querySelectorAll('.template-item').forEach(item => {
                if (item.dataset.category === category) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        function useTemplate(templateId) {
            const builderTab = document.getElementById('builder-tab');
            builderTab.click();
            
            clearReport();
            
            setTimeout(() => {
                addComponentToReport('text-header');
                addComponentToReport('chart-line');
                addComponentToReport('chart-bar');
                addComponentToReport('table-summary');
                
                // You can load template-specific data here if needed
                showToast(`Template loaded`, 'success');
            }, 500);
        }
        
        // Schedule functions
        function runScheduleNow(scheduleId) {
            showToast(`Running ${scheduleId} now...`, 'info');
        }
        
        function editSchedule(scheduleId) {
            showToast(`Editing ${scheduleId}`, 'info');
        }
        
        // Export functions
        function createNewExport() {
            showToast('Creating new export job', 'info');
        }
        
        function downloadExport(exportId) {
            showToast(`Downloading ${exportId}...`, 'info');
        }
        
        function saveExportSettings() {
            showToast('Export settings saved', 'success');
        }
        
        // Filter functionality
        document.getElementById('applyFilters').addEventListener('click', function() {
            loadReports();
            showToast('Filters applied successfully', 'success');
        });
        
        $('#resetFilters').on('click', function() {
            document.querySelectorAll('.filter-panel select').forEach(select => {
                select.value = 'all';
            });
            document.getElementById('dateRangeFilter').value = 'month';
            loadReports();
            showToast('Filters reset', 'info');
        });
        
        function loadMoreReports() {
            showToast('Loading more reports...', 'info');
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
    </script>
@endpush

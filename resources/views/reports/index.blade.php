@extends('layouts.app')

@push('styles')
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    
    <!-- ApexCharts CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.css">
    
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <style>
        :root {
            --primary-green: #2e7d32;
            --light-green: #4caf50;
            --dark-green: #1b5e20;
            --primary-blue: #0277bd;
            --light-blue: #03a9f4;
            --gray-50: #f8f9fa;
            --gray-100: #f1f3f4;
            --gray-200: #e8eaed;
            --gray-600: #5f6368;
            --gray-800: #3c4043;
            --warning-orange: #f57c00;
            --danger-red: #d32f2f;
            --purple: #7b1fa2;
        }
        
        /* Report Stats Cards */
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
        
        /* Report Cards */
        .report-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            transition: all 0.3s;
            border-left: 4px solid var(--primary-green);
            height: 100%;
        }
        
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .report-card.executive {
            border-left-color: var(--primary-blue);
        }
        
        .report-card.regulatory {
            border-left-color: var(--warning-orange);
        }
        
        .report-card.internal {
            border-left-color: var(--light-green);
        }
        
        .report-card.public {
            border-left-color: var(--purple);
        }
        
        /* Report Status Badges */
        .report-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .status-published {
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--light-green);
        }
        
        .status-draft {
            background-color: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }
        
        .status-scheduled {
            background-color: rgba(3, 169, 244, 0.1);
            color: var(--light-blue);
        }
        
        .status-archived {
            background-color: rgba(158, 158, 158, 0.1);
            color: #9e9e9e;
        }
        
        /* Report Type Badges */
        .report-type {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            background-color: var(--gray-100);
            color: var(--gray-800);
        }
        
        .type-pdf {
            background-color: rgba(211, 47, 47, 0.1);
            color: var(--danger-red);
        }
        
        .type-excel {
            background-color: rgba(46, 125, 50, 0.1);
            color: var(--primary-green);
        }
        
        .type-pptx {
            background-color: rgba(245, 124, 0, 0.1);
            color: var(--warning-orange);
        }
        
        .type-web {
            background-color: rgba(3, 169, 244, 0.1);
            color: var(--light-blue);
        }
        
        /* Report Actions */
        .report-actions {
            display: flex;
            gap: 8px;
            margin-top: 15px;
        }
        
        .action-btn {
            flex: 1;
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid var(--gray-200);
            background-color: white;
            color: var(--gray-600);
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: all 0.2s;
            text-decoration: none;
        }
        
        .action-btn:hover {
            background-color: var(--gray-50);
            color: var(--gray-800);
        }
        
        .download-btn:hover {
            background-color: rgba(46, 125, 50, 0.1);
            color: var(--primary-green);
            border-color: rgba(46, 125, 50, 0.3);
        }
        
        .edit-btn:hover {
            background-color: rgba(3, 169, 244, 0.1);
            color: var(--light-blue);
            border-color: rgba(3, 169, 244, 0.3);
        }
        
        .share-btn:hover {
            background-color: rgba(156, 39, 176, 0.1);
            color: #9c27b0;
            border-color: rgba(156, 39, 176, 0.3);
        }
        
        /* Tab Navigation */
        .reports-tabs {
            background: white;
            border-radius: 10px;
            padding: 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .nav-tabs-reports {
            border-bottom: none;
            background-color: var(--gray-50);
            padding: 0 20px;
        }
        
        .nav-tabs-reports .nav-link {
            border: none;
            color: var(--gray-600);
            font-weight: 500;
            padding: 15px 20px;
            position: relative;
        }
        
        .nav-tabs-reports .nav-link.active {
            color: var(--primary-green);
            background-color: white;
        }
        
        .nav-tabs-reports .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background-color: var(--primary-green);
        }
        
        .tab-content-reports {
            padding: 20px;
        }
        
        /* Quick Actions */
        .quick-action-card {
            text-align: center;
            padding: 30px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            cursor: pointer;
            transition: all 0.3s;
            height: 100%;
        }
        
        .quick-action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .quick-action-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        
        /* Report Builder */
        .report-builder {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        .builder-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-green);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 10px;
        }
        
        /* Component Library */
        .component-card {
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: move;
            transition: all 0.2s;
        }
        
        .component-card:hover {
            border-color: var(--primary-green);
            background-color: rgba(46, 125, 50, 0.05);
        }
        
        /* Report Preview */
        .report-preview {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--gray-200);
            min-height: 600px;
        }
        
        .preview-header {
            text-align: center;
            padding-bottom: 20px;
            margin-bottom: 30px;
            border-bottom: 2px solid var(--primary-green);
        }
        
        .preview-placeholder {
            height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--gray-50);
            border-radius: 8px;
            border: 2px dashed var(--gray-300);
            color: var(--gray-600);
        }
        
        /* Template Cards */
        .template-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            height: 100%;
        }
        
        .template-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .template-header {
            padding: 20px;
            background-color: var(--primary-green);
            color: white;
        }
        
        .template-body {
            padding: 20px;
        }
        
        /* Schedule Card */
        .schedule-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 15px;
            border-left: 4px solid var(--primary-blue);
        }
        
        /* Filter Panel */
        .filter-panel {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }
        
        /* Export Options */
        .export-options {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .export-option {
            flex: 1;
            min-width: 120px;
            text-align: center;
            padding: 15px;
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .export-option:hover {
            border-color: var(--primary-green);
            background-color: rgba(46, 125, 50, 0.05);
        }
        
        .export-option.active {
            border-color: var(--primary-green);
            background-color: rgba(46, 125, 50, 0.1);
        }
        
        .config-label {
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 5px;
            font-size: 0.875rem;
        }
    </style>
@endpush

@section('content')
    <div id="content">
        @include('layouts.top-nav')
        
        <!-- Statistics Cards -->
        <div class="row mt-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background-color: var(--primary-green);">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <h3 class="mb-2" id="totalReports">0</h3>
                    <p class="text-muted mb-2">Total Reports</p>
                    <div class="d-flex align-items-center">
                        <span class="text-success fw-bold" id="reportsThisMonth">+0</span>
                        <span class="text-muted ms-2">this month</span>
                    </div>
                </div>
        </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background-color: var(--light-blue);">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3 class="mb-2" id="scheduledReports">0</h3>
                    <p class="text-muted mb-2">Scheduled Reports</p>
                    <div class="connection-status">
                        <span class="text-primary fw-bold" id="dueToday">0 due today</span>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background-color: var(--warning-orange);">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="mb-2" id="sharedReports">0</h3>
                    <p class="text-muted mb-2">Shared Reports</p>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-eye text-warning me-2"></i>
                        <span class="small" id="viewsThisWeek">0 views this week</span>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background-color: var(--danger-red);">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 class="mb-2" id="pendingReports">0</h3>
                    <p class="text-muted mb-2">Reports Pending</p>
                    <button class="btn btn-sm btn-danger mt-2" onclick="showPendingReports()">
                        <i class="fas fa-external-link-alt me-1"></i>Review Now
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="quick-action-card" onclick="startNewReport()">
                    <div class="quick-action-icon text-primary">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <h5>New Report</h5>
                    <p class="text-muted small">Create a custom report from scratch</p>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="quick-action-card" onclick="useTemplateWizard()">
                    <div class="quick-action-icon text-success">
                        <i class="fas fa-clone"></i>
                    </div>
                    <h5>Use Template</h5>
                    <p class="text-muted small">Start with a pre-designed template</p>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="quick-action-card" onclick="scheduleNewReport()">
                    <div class="quick-action-icon text-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h5>Schedule Report</h5>
                    <p class="text-muted small">Set up automated report generation</p>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="quick-action-card" onclick="exportAllReports()">
                    <div class="quick-action-icon text-info">
                        <i class="fas fa-download"></i>
                    </div>
                    <h5>Export All</h5>
                    <p class="text-muted small">Batch export reports in multiple formats</p>
                </div>
            </div>
        </div>
        
        <!-- Reports Tabs -->
        <div class="reports-tabs">
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
                            
                            <div class="col-12 mt-3 d-flex justify-content-between">
                                <button class="btn btn-outline-secondary" id="resetFilters">
                                    <i class="fas fa-redo me-2"></i>Reset Filters
                                </button>
                                <div>
                                    <button class="btn btn-outline-primary me-2" id="saveFilterBtn">
                                        <i class="fas fa-save me-2"></i>Save Filter
                                    </button>
                                    <button class="btn btn-success" id="applyFilters">
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
                                    <div class="list-group list-group-flush">
                                        <a href="#" class="list-group-item list-group-item-action active">
                                            <i class="fas fa-chart-line me-2"></i>Executive Reports
                                        </a>
                                        <a href="#" class="list-group-item list-group-item-action">
                                            <i class="fas fa-balance-scale me-2"></i>Compliance Reports
                                        </a>
                                        <a href="#" class="list-group-item list-group-item-action">
                                            <i class="fas fa-industry me-2"></i>Facility Reports
                                        </a>
                                        <a href="#" class="list-group-item list-group-item-action">
                                            <i class="fas fa-users me-2"></i>Stakeholder Reports
                                        </a>
                                        <a href="#" class="list-group-item list-group-item-action">
                                            <i class="fas fa-calendar-alt me-2"></i>Periodic Reports
                                        </a>
                                        <a href="#" class="list-group-item list-group-item-action">
                                            <i class="fas fa-bullseye me-2"></i>Target Tracking
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Templates Grid -->
                        <div class="col-lg-9">
                            <div class="row">
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="template-card">
                                        <div class="template-header">
                                            <h5 class="mb-0">Executive Dashboard</h5>
                                            <small>For board and C-suite</small>
                                        </div>
                                        <div class="template-body">
                                            <p class="small text-muted">High-level summary with KPIs and trend analysis</p>
                                            <div class="mb-3">
                                                <span class="badge bg-light text-dark me-1">PDF</span>
                                                <span class="badge bg-light text-dark me-1">PPTX</span>
                                                <span class="badge bg-light text-dark">Web</span>
                                            </div>
                                            <button class="btn btn-primary w-100" onclick="useTemplate('executive-dashboard')">
                                                <i class="fas fa-clone me-2"></i>Use Template
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="template-card">
                                        <div class="template-header" style="background-color: var(--warning-orange);">
                                            <h5 class="mb-0">GHG Protocol Report</h5>
                                            <small>For regulatory compliance</small>
                                        </div>
                                        <div class="template-body">
                                            <p class="small text-muted">Complete GHG Protocol reporting template with all required sections</p>
                                            <div class="mb-3">
                                                <span class="badge bg-light text-dark me-1">Excel</span>
                                                <span class="badge bg-light text-dark me-1">PDF</span>
                                            </div>
                                            <button class="btn btn-primary w-100" onclick="useTemplate('ghg-protocol')">
                                                <i class="fas fa-clone me-2"></i>Use Template
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="template-card">
                                        <div class="template-header" style="background-color: var(--light-green);">
                                            <h5 class="mb-0">CDP Disclosure</h5>
                                            <small>For CDP reporting</small>
                                        </div>
                                        <div class="template-body">
                                            <p class="small text-muted">CDP climate change questionnaire response template</p>
                                            <div class="mb-3">
                                                <span class="badge bg-light text-dark me-1">Excel</span>
                                                <span class="badge bg-light text-dark">Web</span>
                                            </div>
                                            <button class="btn btn-primary w-100" onclick="useTemplate('cdp-disclosure')">
                                                <i class="fas fa-clone me-2"></i>Use Template
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="template-card">
                                        <div class="template-header" style="background-color: var(--primary-blue);">
                                            <h5 class="mb-0">Monthly Performance</h5>
                                            <small>For internal teams</small>
                                        </div>
                                        <div class="template-body">
                                            <p class="small text-muted">Monthly emissions performance tracking and analysis</p>
                                            <div class="mb-3">
                                                <span class="badge bg-light text-dark me-1">PDF</span>
                                                <span class="badge bg-light text-dark">Excel</span>
                                            </div>
                                            <button class="btn btn-primary w-100" onclick="useTemplate('monthly-performance')">
                                                <i class="fas fa-clone me-2"></i>Use Template
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="template-card">
                                        <div class="template-header" style="background-color: var(--purple);">
                                            <h5 class="mb-0">Sustainability Report</h5>
                                            <small>For public disclosure</small>
                                        </div>
                                        <div class="template-body">
                                            <p class="small text-muted">Annual sustainability report with ESG metrics</p>
                                            <div class="mb-3">
                                                <span class="badge bg-light text-dark me-1">PPTX</span>
                                                <span class="badge bg-light text-dark me-1">PDF</span>
                                                <span class="badge bg-light text-dark">Web</span>
                                            </div>
                                            <button class="btn btn-primary w-100" onclick="useTemplate('sustainability-report')">
                                                <i class="fas fa-clone me-2"></i>Use Template
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="template-card">
                                        <div class="template-header" style="background-color: var(--danger-red);">
                                            <h5 class="mb-0">Audit Report</h5>
                                            <small>For internal/external audit</small>
                                        </div>
                                        <div class="template-body">
                                            <p class="small text-muted">Detailed audit report with data validation and verification</p>
                                            <div class="mb-3">
                                                <span class="badge bg-light text-dark me-1">PDF</span>
                                                <span class="badge bg-light text-dark">Excel</span>
                                            </div>
                                            <button class="btn btn-primary w-100" onclick="useTemplate('audit-report')">
                                                <i class="fas fa-clone me-2"></i>Use Template
                                            </button>
                                        </div>
                                    </div>
                                </div>
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
                        <!-- Scheduled reports will be loaded here -->
                        <div class="schedule-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">Monthly Executive Summary</h6>
                                    <div class="text-muted small">PDF report emailed to executive team</div>
                                    <div class="mt-2">
                                        <span class="badge bg-primary me-2">Monthly</span>
                                        <span class="badge bg-success">Active</span>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold">Next: Nov 1, 2023</div>
                                    <div class="text-muted small">8:00 AM</div>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <div class="small">
                                        <i class="fas fa-user me-1"></i> Recipients: 12
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="small">
                                        <i class="fas fa-history me-1"></i> Last run: Oct 1, 2023
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex justify-content-end gap-2">
                                        <button class="btn btn-sm btn-outline-primary" onclick="runScheduleNow('monthly-exec')">
                                            <i class="fas fa-play"></i> Run Now
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="editSchedule('monthly-exec')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                                <div class="card-body">
        <div class="table-responsive">
                                        <table class="table table-hover">
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
                                                <tr>
                                                    <td>
                                                        <strong>Q3 2023 Data Export</strong>
                                                        <div class="text-muted small">All emissions data</div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success">Excel</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success">Completed</span>
                                                    </td>
                                                    <td>Today, 10:30 AM</td>
                                                    <td>24.5 MB</td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary" onclick="downloadExport('export-1')">
                                                            <i class="fas fa-download"></i>
                                                        </button>
                                                    </td>
                                                </tr>
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
            $.get("{{ route('reports.index') }}/statistics", function(data) {
                $('#totalReports').text(data.total || 0);
                $('#reportsThisMonth').text('+' + (data.this_month || 0));
                $('#scheduledReports').text(data.scheduled || 0);
                $('#dueToday').text((data.due_today || 0) + ' due today');
                $('#sharedReports').text(data.shared || 0);
                $('#viewsThisWeek').text((data.views_week || 0) + ' views this week');
                $('#pendingReports').text(data.pending || 0);
            }).fail(function() {
                // Default values if endpoint doesn't exist yet
                $('#totalReports').text('0');
                $('#reportsThisMonth').text('+0');
                $('#scheduledReports').text('0');
                $('#dueToday').text('0 due today');
                $('#sharedReports').text('0');
                $('#viewsThisWeek').text('0 views this week');
                $('#pendingReports').text('0');
            });
        }
        
        function loadReports() {
            $.get("{{ route('reports.json') }}", function(data) {
                renderReports(data.data || []);
            }).fail(function() {
                // Fallback to DataTables endpoint
                $.get("{{ route('reports.data') }}", function(data) {
                    renderReports(data.data || []);
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
                            <a href="#" class="action-btn share-btn" onclick="shareReport(${report.id})">
                                <i class="fas fa-share-alt"></i> Share
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
        
        function editReport(reportId) {
            window.location.href = `/reports/${reportId}/edit`;
        }
        
        function shareReport(reportId) {
            showToast(`Sharing options for report ${reportId}`, 'info');
        }
        
        // Quick actions
        function startNewReport() {
            const builderTab = document.getElementById('builder-tab');
            builderTab.click();
            clearReport();
            showToast('Starting new report', 'info');
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
        function useTemplate(templateName) {
            const builderTab = document.getElementById('builder-tab');
            builderTab.click();
            
            clearReport();
            
            setTimeout(() => {
                addComponentToReport('text-header');
                addComponentToReport('chart-line');
                addComponentToReport('chart-bar');
                addComponentToReport('table-summary');
                
                document.getElementById('reportTitle').value = `${templateName.replace('-', ' ').toUpperCase()} Report`;
                document.getElementById('previewTitle').textContent = `${templateName.replace('-', ' ').toUpperCase()} Report`;
                
                showToast(`Template "${templateName}" loaded`, 'success');
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
        
        document.getElementById('resetFilters').addEventListener('click', function() {
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

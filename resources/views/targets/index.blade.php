@extends('layouts.app')

@section('page-title', 'Targets & Goals Tracking')

@push('styles')
<style>
    /* Additional styles specific to Targets & Goals page */
    .config-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--gray-600);
        text-transform: uppercase;
        margin-bottom: 8px;
        letter-spacing: 0.5px;
    }
    
    /* Progress Indicators */
    .progress-circle {
        width: 80px;
        height: 80px;
        position: relative;
        flex-shrink: 0;
    }
    
    .progress-circle svg {
        width: 100%;
        height: 100%;
        transform: rotate(-90deg);
    }
    
    .progress-circle-bg {
        fill: none;
        stroke: var(--gray-200);
        stroke-width: 8;
    }
    
    .progress-circle-fill {
        fill: none;
        stroke-width: 8;
        stroke-linecap: round;
        stroke-dasharray: 226.2;
        stroke-dashoffset: calc(226.2 * (1 - var(--progress)));
        transition: stroke-dashoffset 0.5s ease;
    }
    
    .progress-circle-value {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-weight: 700;
        font-size: 1.2rem;
    }
    
    /* Target Cards */
    .target-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        margin-bottom: 20px;
        border-left: 4px solid var(--primary-green);
        transition: all 0.3s;
        height: 100%;
    }
    
    .target-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }
    
    .target-card.on-track {
        border-left-color: var(--light-green);
    }
    
    .target-card.at-risk {
        border-left-color: var(--warning-orange);
    }
    
    .target-card.off-track {
        border-left-color: var(--danger-red);
    }
    
    .target-card.completed {
        border-left-color: var(--success-teal);
    }
    
    /* Target Status Badges */
    .target-status {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    
    .status-on-track {
        background-color: rgba(76, 175, 80, 0.1);
        color: var(--light-green);
    }
    
    .status-at-risk {
        background-color: rgba(255, 193, 7, 0.1);
        color: #ffc107;
    }
    
    .status-off-track {
        background-color: rgba(211, 47, 47, 0.1);
        color: var(--danger-red);
    }
    
    .status-completed {
        background-color: rgba(0, 150, 136, 0.1);
        color: var(--success-teal);
    }
    
    /* Goal Type Badges */
    .goal-type {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        background-color: var(--gray-100);
        color: var(--gray-800);
    }
    
    .type-sbt {
        background-color: rgba(123, 31, 162, 0.1);
        color: var(--purple);
    }
    
    .type-net-zero {
        background-color: rgba(3, 169, 244, 0.1);
        color: var(--light-blue);
    }
    
    .type-carbon-neutral {
        background-color: rgba(46, 125, 50, 0.1);
        color: var(--primary-green);
    }
    
    .type-regulatory {
        background-color: rgba(245, 124, 0, 0.1);
        color: var(--warning-orange);
    }
    
    .type-internal {
        background-color: rgba(158, 158, 158, 0.1);
        color: #757575;
    }
    
    /* Progress Bar */
    .target-progress {
        height: 8px;
        border-radius: 4px;
        background-color: var(--gray-200);
        overflow: hidden;
        margin: 15px 0;
    }
    
    .target-progress-bar {
        height: 100%;
        border-radius: 4px;
        transition: width 0.5s ease;
    }
    
    /* Milestone Timeline */
    .milestone-timeline {
        position: relative;
        padding-left: 30px;
    }
    
    .milestone-timeline::before {
        content: '';
        position: absolute;
        left: 15px;
        top: 0;
        bottom: 0;
        width: 2px;
        background-color: var(--gray-200);
    }
    
    .milestone-item {
        position: relative;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 1px solid var(--gray-200);
    }
    
    .milestone-item:last-child {
        border-bottom: none;
    }
    
    .milestone-dot {
        position: absolute;
        left: -22px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background-color: white;
        border: 3px solid var(--gray-300);
    }
    
    .milestone-dot.completed {
        border-color: var(--light-green);
        background-color: var(--light-green);
    }
    
    .milestone-dot.current {
        border-color: var(--light-blue);
        background-color: white;
    }
    
    .milestone-dot.upcoming {
        border-color: var(--gray-400);
        background-color: white;
    }
    
    /* New Target Wizard */
    .target-wizard {
        background: white;
        border-radius: 10px;
        padding: 30px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        margin-bottom: 30px;
    }
    
    .wizard-step {
        padding: 20px 0;
        display: none;
    }
    
    .wizard-step.active {
        display: block;
    }
    
    .wizard-header {
        border-bottom: 1px solid var(--gray-200);
        padding-bottom: 20px;
        margin-bottom: 30px;
    }
    
    /* Target Actions */
    .target-actions {
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
        cursor: pointer;
    }
    
    .action-btn:hover {
        background-color: var(--gray-50);
        color: var(--gray-800);
    }
    
    .update-btn:hover {
        background-color: rgba(3, 169, 244, 0.1);
        color: var(--light-blue);
        border-color: rgba(3, 169, 244, 0.3);
    }
    
    .report-btn:hover {
        background-color: rgba(46, 125, 50, 0.1);
        color: var(--primary-green);
        border-color: rgba(46, 125, 50, 0.3);
    }
    
    .edit-btn:hover {
        background-color: rgba(255, 193, 7, 0.1);
        color: #ffc107;
        border-color: rgba(255, 193, 7, 0.3);
    }
    
    .delete-btn:hover {
        background-color: rgba(220, 53, 69, 0.1);
        color: #dc3545;
        border-color: rgba(220, 53, 69, 0.3);
    }
    
    /* Scenario Cards */
    .scenario-card {
        text-align: center;
        padding: 25px 15px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        cursor: pointer;
        transition: all 0.3s;
        height: 100%;
    }
    
    .scenario-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }
    
    .scenario-card.active {
        border: 2px solid var(--primary-green);
        background-color: rgba(46, 125, 50, 0.05);
    }
    
    .scenario-icon {
        font-size: 36px;
        margin-bottom: 15px;
    }
    
    /* Filter Panel */
    .filter-panel {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        margin-bottom: 20px;
    }
    
    /* Target Summary */
    .target-summary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 20px;
    }
    
    :root {
        --success-teal: #009688;
        --purple: #7b1fa2;
    }
</style>
@endpush

@section('content')
    <!-- Main Content -->
    <div id="content">
        @include('layouts.top-nav')
        
        <!-- Target Summary Banner -->
        <div class="target-summary">
            <div class="row">
                <div class="col-md-8">
                    <h3 class="mb-3">Emission Reduction Targets</h3>
                    <p class="mb-0">Track progress towards your climate goals and science-based targets. Set new targets, monitor progress, and adjust strategies.</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#newTargetModal">
                        <i class="fas fa-plus me-2"></i>Set New Target
                    </button>
                </div>
            </div>
        </div>
        
        <!-- KPI Cards -->
        <div class="row mt-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="kpi-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">ACTIVE TARGETS</div>
                            <div class="kpi-value">8</div>
                            <div class="d-flex align-items-center">
                                <span class="text-success fw-bold">+2</span>
                                <span class="text-muted ms-2">this quarter</span>
                            </div>
                        </div>
                        <div class="kpi-icon" style="background-color: var(--primary-green);">
                            <i class="fas fa-bullseye"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="kpi-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">ON TRACK</div>
                            <div class="kpi-value">5</div>
                            <div class="progress mt-2" style="height: 6px;">
                                <div class="progress-bar bg-success" style="width: 63%"></div>
                            </div>
                        </div>
                        <div class="kpi-icon" style="background-color: var(--light-green);">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="kpi-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">TOTAL REDUCTION</div>
                            <div class="kpi-value">2,450 <span class="fs-6">tCO₂e</span></div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-arrow-down text-success me-2"></i>
                                <span class="text-success fw-bold">18% achieved</span>
                            </div>
                        </div>
                        <div class="kpi-icon" style="background-color: var(--primary-blue);">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="kpi-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">AT RISK</div>
                            <div class="kpi-value">2</div>
                            <button class="btn btn-sm btn-warning mt-2" onclick="showAtRiskTargets()">
                                <i class="fas fa-external-link-alt me-1"></i>Review Now
                            </button>
                        </div>
                        <div class="kpi-icon" style="background-color: var(--warning-orange);">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filter Panel -->
        <div class="filter-panel">
            <div class="row g-3">
                <div class="col-lg-3 col-md-6">
                    <label class="form-label">Target Status</label>
                    <select class="form-select" id="statusFilter">
                        <option value="all">All Status</option>
                        <option value="on-track">On Track</option>
                        <option value="at-risk">At Risk</option>
                        <option value="off-track">Off Track</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <label class="form-label">Goal Type</label>
                    <select class="form-select" id="typeFilter">
                        <option value="all">All Types</option>
                        <option value="sbt">Science-Based Target</option>
                        <option value="net-zero">Net Zero</option>
                        <option value="carbon-neutral">Carbon Neutral</option>
                        <option value="regulatory">Regulatory</option>
                        <option value="internal">Internal</option>
                    </select>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <label class="form-label">Scope</label>
                    <select class="form-select" id="scopeFilter">
                        <option value="all">All Scopes</option>
                        <option value="1">Scope 1</option>
                        <option value="2">Scope 2</option>
                        <option value="3">Scope 3</option>
                        <option value="1-2">Scope 1+2</option>
                        <option value="all-scopes">All Scopes</option>
                    </select>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <label class="form-label">Timeframe</label>
                    <select class="form-select" id="timeframeFilter">
                        <option value="all">All Timeframes</option>
                        <option value="current-year">Current Year</option>
                        <option value="next-year">Next Year</option>
                        <option value="2025">2025 Targets</option>
                        <option value="2030">2030 Targets</option>
                        <option value="2050">2050 Targets</option>
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
        
        <!-- Charts Row -->
        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="chart-container">
                    <div class="chart-title">Target Progress Overview</div>
                    <div id="targetProgressChart"></div>
                </div>
            </div>
            
            <div class="col-lg-4 mb-4">
                <div class="chart-container">
                    <div class="chart-title">Target Status Distribution</div>
                    <div id="statusDistributionChart"></div>
                </div>
            </div>
        </div>
        
        <!-- Active Targets -->
        <div class="row">
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mb-0">Active Targets</h4>
                    <button class="btn btn-outline-primary" onclick="showAllTargets()">
                        <i class="fas fa-list me-2"></i>View All Targets
                    </button>
                </div>
                
                <!-- Target Cards -->
                <div class="row" id="targetsGrid">
                    <!-- Net Zero Target -->
                    <div class="col-md-6 mb-4">
                        <div class="target-card on-track">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="mb-1">Net Zero 2050</h5>
                                    <span class="goal-type type-net-zero">
                                        <i class="fas fa-globe-europe me-1"></i>Net Zero
                                    </span>
                                </div>
                                <span class="target-status status-on-track">
                                    <i class="fas fa-check-circle"></i>On Track
                                </span>
                            </div>
                            
                            <p class="text-muted small mb-3">Achieve net-zero emissions across all scopes by 2050</p>
                            
                            <div class="mb-3">
                                <div class="config-label">Target Details</div>
                                <div class="small">
                                    <i class="fas fa-calendar me-1"></i> Deadline: 2050
                                    <br>
                                    <i class="fas fa-bullseye me-1"></i> Baseline: 2020
                                    <br>
                                    <i class="fas fa-weight me-1"></i> Reduction: 100%
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="config-label">Progress</div>
                                <div class="d-flex align-items-center">
                                    <div class="progress-circle" style="--progress: 0.18">
                                        <svg>
                                            <circle class="progress-circle-bg" cx="40" cy="40" r="36"></circle>
                                            <circle class="progress-circle-fill" cx="40" cy="40" r="36" style="stroke: var(--light-green);"></circle>
                                        </svg>
                                        <div class="progress-circle-value">18%</div>
                                    </div>
                                    <div class="ms-3 flex-grow-1">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="small">Current: 12,450 tCO₂e</span>
                                            <span class="small fw-bold">2,450 tCO₂e reduced</span>
                                        </div>
                                        <div class="target-progress">
                                            <div class="target-progress-bar" style="width: 18%; background-color: var(--light-green);"></div>
                                        </div>
                                        <div class="small text-muted">Target: 0 tCO₂e by 2050</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="target-actions">
                                <a href="#" class="action-btn update-btn" onclick="updateProgress('net-zero-2050')">
                                    <i class="fas fa-chart-line"></i> Update
                                </a>
                                <a href="#" class="action-btn report-btn" onclick="viewTargetReport('net-zero-2050')">
                                    <i class="fas fa-file-alt"></i> Report
                                </a>
                                <a href="#" class="action-btn edit-btn" onclick="editTarget('net-zero-2050')">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SBTi Target -->
                    <div class="col-md-6 mb-4">
                        <div class="target-card on-track">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="mb-1">SBTi 1.5°C Aligned</h5>
                                    <span class="goal-type type-sbt">
                                        <i class="fas fa-temperature-low me-1"></i>Science-Based
                                    </span>
                                </div>
                                <span class="target-status status-on-track">
                                    <i class="fas fa-check-circle"></i>On Track
                                </span>
                            </div>
                            
                            <p class="text-muted small mb-3">Reduce Scope 1+2 emissions by 50% by 2030 (vs 2020 baseline)</p>
                            
                            <div class="mb-3">
                                <div class="config-label">Target Details</div>
                                <div class="small">
                                    <i class="fas fa-calendar me-1"></i> Deadline: 2030
                                    <br>
                                    <i class="fas fa-bullseye me-1"></i> Baseline: 2020
                                    <br>
                                    <i class="fas fa-weight me-1"></i> Reduction: 50%
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="config-label">Progress</div>
                                <div class="d-flex align-items-center">
                                    <div class="progress-circle" style="--progress: 0.32">
                                        <svg>
                                            <circle class="progress-circle-bg" cx="40" cy="40" r="36"></circle>
                                            <circle class="progress-circle-fill" cx="40" cy="40" r="36" style="stroke: var(--light-green);"></circle>
                                        </svg>
                                        <div class="progress-circle-value">32%</div>
                                    </div>
                                    <div class="ms-3 flex-grow-1">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="small">Current: 7,970 tCO₂e</span>
                                            <span class="small fw-bold">3,750 tCO₂e reduced</span>
                                        </div>
                                        <div class="target-progress">
                                            <div class="target-progress-bar" style="width: 32%; background-color: var(--light-green);"></div>
                                        </div>
                                        <div class="small text-muted">Target: 5,860 tCO₂e by 2030</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="target-actions">
                                <a href="#" class="action-btn update-btn" onclick="updateProgress('sbti-2030')">
                                    <i class="fas fa-chart-line"></i> Update
                                </a>
                                <a href="#" class="action-btn report-btn" onclick="viewTargetReport('sbti-2030')">
                                    <i class="fas fa-file-alt"></i> Report
                                </a>
                                <a href="#" class="action-btn edit-btn" onclick="editTarget('sbti-2030')">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- At Risk Target -->
                    <div class="col-md-6 mb-4">
                        <div class="target-card at-risk">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="mb-1">Scope 3 Reduction</h5>
                                    <span class="goal-type type-internal">
                                        <i class="fas fa-truck me-1"></i>Internal Target
                                    </span>
                                </div>
                                <span class="target-status status-at-risk">
                                    <i class="fas fa-exclamation-triangle"></i>At Risk
                                </span>
                            </div>
                            
                            <p class="text-muted small mb-3">Reduce Scope 3 emissions by 25% by 2025 (vs 2022 baseline)</p>
                            
                            <div class="mb-3">
                                <div class="config-label">Target Details</div>
                                <div class="small">
                                    <i class="fas fa-calendar me-1"></i> Deadline: 2025
                                    <br>
                                    <i class="fas fa-bullseye me-1"></i> Baseline: 2022
                                    <br>
                                    <i class="fas fa-weight me-1"></i> Reduction: 25%
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="config-label">Progress</div>
                                <div class="d-flex align-items-center">
                                    <div class="progress-circle" style="--progress: 0.15">
                                        <svg>
                                            <circle class="progress-circle-bg" cx="40" cy="40" r="36"></circle>
                                            <circle class="progress-circle-fill" cx="40" cy="40" r="36" style="stroke: #ffc107;"></circle>
                                        </svg>
                                        <div class="progress-circle-value">15%</div>
                                    </div>
                                    <div class="ms-3 flex-grow-1">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="small">Current: 4,480 tCO₂e</span>
                                            <span class="small fw-bold">790 tCO₂e reduced</span>
                                        </div>
                                        <div class="target-progress">
                                            <div class="target-progress-bar" style="width: 15%; background-color: #ffc107;"></div>
                                        </div>
                                        <div class="small text-muted">Target: 3,960 tCO₂e by 2025</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="target-actions">
                                <a href="#" class="action-btn update-btn" onclick="updateProgress('scope3-2025')">
                                    <i class="fas fa-chart-line"></i> Update
                                </a>
                                <a href="#" class="action-btn report-btn" onclick="viewTargetReport('scope3-2025')">
                                    <i class="fas fa-file-alt"></i> Report
                                </a>
                                <a href="#" class="action-btn edit-btn" onclick="editTarget('scope3-2025')">
                                    <i class="fas fa-edit"></i> Adjust
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Completed Target -->
                    <div class="col-md-6 mb-4">
                        <div class="target-card completed">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="mb-1">RE100 Commitment</h5>
                                    <span class="goal-type type-carbon-neutral">
                                        <i class="fas fa-sun me-1"></i>Carbon Neutral
                                    </span>
                                </div>
                                <span class="target-status status-completed">
                                    <i class="fas fa-trophy"></i>Completed
                                </span>
                            </div>
                            
                            <p class="text-muted small mb-3">Achieve 100% renewable electricity by 2023 for all facilities</p>
                            
                            <div class="mb-3">
                                <div class="config-label">Target Details</div>
                                <div class="small">
                                    <i class="fas fa-calendar me-1"></i> Completed: 2023
                                    <br>
                                    <i class="fas fa-bullseye me-1"></i> Baseline: 2020
                                    <br>
                                    <i class="fas fa-weight me-1"></i> Reduction: 100% Scope 2
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="config-label">Achievement</div>
                                <div class="d-flex align-items-center">
                                    <div class="progress-circle" style="--progress: 1">
                                        <svg>
                                            <circle class="progress-circle-bg" cx="40" cy="40" r="36"></circle>
                                            <circle class="progress-circle-fill" cx="40" cy="40" r="36" style="stroke: var(--success-teal);"></circle>
                                        </svg>
                                        <div class="progress-circle-value">100%</div>
                                    </div>
                                    <div class="ms-3 flex-grow-1">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="small">Achieved: Oct 2023</span>
                                            <span class="small fw-bold">4,120 tCO₂e eliminated</span>
                                        </div>
                                        <div class="target-progress">
                                            <div class="target-progress-bar" style="width: 100%; background-color: var(--success-teal);"></div>
                                        </div>
                                        <div class="small text-muted">Target: 0 tCO₂e from purchased electricity</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="target-actions">
                                <a href="#" class="action-btn report-btn" onclick="viewTargetReport('re100-2023')">
                                    <i class="fas fa-file-alt"></i> Report
                                </a>
                                <a href="#" class="action-btn edit-btn" onclick="celebrateAchievement('re100-2023')">
                                    <i class="fas fa-trophy"></i> Celebrate
                                </a>
                                <a href="#" class="action-btn" onclick="setNewTarget()">
                                    <i class="fas fa-plus"></i> New Goal
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Milestone Timeline -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Target Milestones</h5>
                        <button class="btn btn-sm btn-outline-primary" onclick="addMilestone()">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="milestone-timeline">
                            <div class="milestone-item">
                                <div class="milestone-dot completed"></div>
                                <div class="mb-2">
                                    <strong>RE100 Achievement</strong>
                                    <div class="small text-muted">100% renewable electricity achieved</div>
                                </div>
                                <div class="small text-success">
                                    <i class="fas fa-check-circle me-1"></i>Completed: Oct 2023
                                </div>
                            </div>
                            
                            <div class="milestone-item">
                                <div class="milestone-dot completed"></div>
                                <div class="mb-2">
                                    <strong>SBTi Validation</strong>
                                    <div class="small text-muted">Science-based target validated by SBTi</div>
                                </div>
                                <div class="small text-success">
                                    <i class="fas fa-check-circle me-1"></i>Completed: Jun 2023
                                </div>
                            </div>
                            
                            <div class="milestone-item">
                                <div class="milestone-dot current"></div>
                                <div class="mb-2">
                                    <strong>2024 Interim Target</strong>
                                    <div class="small text-muted">Achieve 30% reduction vs 2020 baseline</div>
                                </div>
                                <div class="small text-primary">
                                    <i class="fas fa-clock me-1"></i>Due: Dec 2024
                                </div>
                            </div>
                            
                            <div class="milestone-item">
                                <div class="milestone-dot upcoming"></div>
                                <div class="mb-2">
                                    <strong>Scope 3 Baseline</strong>
                                    <div class="small text-muted">Complete comprehensive Scope 3 assessment</div>
                                </div>
                                <div class="small text-muted">
                                    <i class="fas fa-calendar me-1"></i>Due: Mar 2024
                                </div>
                            </div>
                            
                            <div class="milestone-item">
                                <div class="milestone-dot upcoming"></div>
                                <div class="mb-2">
                                    <strong>2030 SBTi Target</strong>
                                    <div class="small text-muted">50% reduction across Scope 1+2</div>
                                </div>
                                <div class="small text-muted">
                                    <i class="fas fa-calendar me-1"></i>Due: Dec 2030
                                </div>
                            </div>
                            
                            <div class="milestone-item">
                                <div class="milestone-dot upcoming"></div>
                                <div class="mb-2">
                                    <strong>Net Zero 2050</strong>
                                    <div class="small text-muted">Achieve net-zero emissions</div>
                                </div>
                                <div class="small text-muted">
                                    <i class="fas fa-calendar me-1"></i>Due: Dec 2050
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Scenario Modeling -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Scenario Modeling</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4">Compare different pathways to achieve your emission reduction targets</p>
                        
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <div class="scenario-card active" onclick="selectScenario('baseline')">
                                    <div class="scenario-icon text-primary">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <h6>Baseline</h6>
                                    <p class="small text-muted">Current trajectory with existing initiatives</p>
                                    <div class="mt-2">
                                        <span class="badge bg-primary">2030: 8,240 tCO₂e</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <div class="scenario-card" onclick="selectScenario('accelerated')">
                                    <div class="scenario-icon text-success">
                                        <i class="fas fa-bolt"></i>
                                    </div>
                                    <h6>Accelerated</h6>
                                    <p class="small text-muted">Fast-track all planned projects</p>
                                    <div class="mt-2">
                                        <span class="badge bg-success">2030: 5,860 tCO₂e</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <div class="scenario-card" onclick="selectScenario('innovative')">
                                    <div class="scenario-icon text-warning">
                                        <i class="fas fa-lightbulb"></i>
                                    </div>
                                    <h6>Innovative</h6>
                                    <p class="small text-muted">New technologies and partnerships</p>
                                    <div class="mt-2">
                                        <span class="badge bg-warning text-dark">2030: 4,920 tCO₂e</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <div class="scenario-card" onclick="selectScenario('transformational')">
                                    <div class="scenario-icon text-info">
                                        <i class="fas fa-rocket"></i>
                                    </div>
                                    <h6>Transformational</h6>
                                    <p class="small text-muted">Business model transformation</p>
                                    <div class="mt-2">
                                        <span class="badge bg-info">2030: 3,150 tCO₂e</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <div class="chart-container">
                                <div class="chart-title">Scenario Comparison - Emissions Trajectory</div>
                                <div id="scenarioChart"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- New Target Modal -->
    <div class="modal fade" id="newTargetModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Set New Reduction Target</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="target-wizard">
                        <div class="wizard-header">
                            <h6 class="mb-3">Target Configuration</h6>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar" style="width: 33%" id="wizardProgress"></div>
                            </div>
                        </div>
                        
                        <!-- Step 1: Target Basics -->
                        <div class="wizard-step active" id="step1">
                            <h6 class="mb-3">Target Details</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Target Name</label>
                                    <input type="text" class="form-control" placeholder="e.g., Net Zero 2050">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Target Type</label>
                                    <select class="form-select">
                                        <option value="">Select type...</option>
                                        <option value="sbt">Science-Based Target (SBTi)</option>
                                        <option value="net-zero">Net Zero</option>
                                        <option value="carbon-neutral">Carbon Neutral</option>
                                        <option value="regulatory">Regulatory Compliance</option>
                                        <option value="internal">Internal Goal</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Target Scope</label>
                                    <select class="form-select">
                                        <option value="">Select scope...</option>
                                        <option value="1">Scope 1 Only</option>
                                        <option value="2">Scope 2 Only</option>
                                        <option value="3">Scope 3 Only</option>
                                        <option value="1-2">Scope 1+2</option>
                                        <option value="all">All Scopes (1+2+3)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Target Deadline</label>
                                    <select class="form-select">
                                        <option value="">Select deadline...</option>
                                        <option value="2025">2025</option>
                                        <option value="2030">2030</option>
                                        <option value="2040">2040</option>
                                        <option value="2050">2050</option>
                                        <option value="custom">Custom Year</option>
                                    </select>
                                </div>
                                
                                <div class="col-12">
                                    <label class="form-label">Target Description</label>
                                    <textarea class="form-control" rows="3" placeholder="Describe your target and its importance..."></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Step 2: Reduction Metrics -->
                        <div class="wizard-step" id="step2">
                            <h6 class="mb-3">Reduction Metrics</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Baseline Year</label>
                                    <select class="form-select">
                                        <option value="">Select baseline year...</option>
                                        <option value="2020">2020</option>
                                        <option value="2021">2021</option>
                                        <option value="2022">2022</option>
                                        <option value="2023">2023</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Baseline Emissions</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" placeholder="0.00">
                                        <span class="input-group-text">tCO₂e</span>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Reduction Percentage</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" placeholder="0.00" min="0" max="100" step="0.1">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Target Emissions</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" placeholder="0.00">
                                        <span class="input-group-text">tCO₂e</span>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <label class="form-label">Reduction Strategy</label>
                                    <select class="form-select">
                                        <option value="">Select primary strategy...</option>
                                        <option value="energy-efficiency">Energy Efficiency</option>
                                        <option value="renewables">Renewable Energy</option>
                                        <option value="fuel-switching">Fuel Switching</option>
                                        <option value="process-improvement">Process Improvement</option>
                                        <option value="carbon-capture">Carbon Capture</option>
                                        <option value="offsetting">Carbon Offsetting</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Step 3: Milestones & Monitoring -->
                        <div class="wizard-step" id="step3">
                            <h6 class="mb-3">Milestones & Monitoring</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Review Frequency</label>
                                    <select class="form-select">
                                        <option value="monthly">Monthly</option>
                                        <option value="quarterly" selected>Quarterly</option>
                                        <option value="biannual">Biannual</option>
                                        <option value="annual">Annual</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Responsible Person</label>
                                    <select class="form-select">
                                        <option value="">Select person...</option>
                                        <option value="sarah">Sarah Manager</option>
                                        <option value="john">John Analyst</option>
                                        <option value="emma">Emma Operator</option>
                                        <option value="team">Sustainability Team</option>
                                    </select>
                                </div>
                                
                                <div class="col-12">
                                    <label class="form-label">Interim Milestones</label>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="milestone2025">
                                        <label class="form-check-label" for="milestone2025">
                                            25% reduction by 2025
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="milestone2030">
                                        <label class="form-check-label" for="milestone2030">
                                            50% reduction by 2030
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="milestone2040">
                                        <label class="form-check-label" for="milestone2040">
                                            75% reduction by 2040
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="milestone2050">
                                        <label class="form-check-label" for="milestone2050">
                                            100% reduction by 2050
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <label class="form-label">Success Metrics</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="metricCarbon" checked>
                                        <label class="form-check-label" for="metricCarbon">
                                            Carbon emissions reduction
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="metricEnergy">
                                        <label class="form-check-label" for="metricEnergy">
                                            Energy consumption reduction
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="metricRenewable">
                                        <label class="form-check-label" for="metricRenewable">
                                            Renewable energy percentage
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="metricCost">
                                        <label class="form-check-label" for="metricCost">
                                            Cost savings
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="prevBtn" onclick="prevWizardStep()" style="display: none;">
                        <i class="fas fa-arrow-left me-2"></i>Previous
                    </button>
                    <button type="button" class="btn btn-primary" id="nextBtn" onclick="nextWizardStep()">
                        Next <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                    <button type="button" class="btn btn-success" id="saveBtn" onclick="saveTarget()" style="display: none;">
                        <i class="fas fa-save me-2"></i>Save Target
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Target Details Modal -->
    <div class="modal fade" id="targetDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Target Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="targetDetailsContent">
                        <!-- Content loaded dynamically -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="downloadTargetReport()">
                        <i class="fas fa-download me-2"></i>Download Report
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    let currentWizardStep = 1;
    let selectedScenario = 'baseline';
    
    // Initialize charts when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Target Progress Chart
        const progressOptions = {
            series: [
                {
                    name: 'Actual Emissions',
                    data: [15000, 14500, 14200, 13800, 13500, 13200, 12900, 12600, 12450, 12200, 12000, 11800]
                },
                {
                    name: 'Target Trajectory',
                    data: [15000, 14500, 14000, 13500, 13000, 12500, 12000, 11500, 11000, 10500, 10000, 9500]
                }
            ],
            chart: {
                height: 250,
                type: 'line',
                zoom: {
                    enabled: false
                },
                toolbar: {
                    show: false
                }
            },
            colors: ['#d32f2f', '#2e7d32'],
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            xaxis: {
                categories: ['2020', '2021', '2022', '2023', '2024', '2025', '2026', '2027', '2028', '2029', '2030', '2031'],
                title: {
                    text: 'Year'
                }
            },
            yaxis: {
                title: {
                    text: 'Emissions (tCO₂e)'
                },
                min: 8000,
                max: 16000
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right'
            },
            markers: {
                size: 5
            }
        };
        
        const progressChart = new ApexCharts(document.querySelector("#targetProgressChart"), progressOptions);
        progressChart.render();
        
        // Status Distribution Chart
        const distributionOptions = {
            series: [5, 2, 1, 1],
            chart: {
                height: 250,
                type: 'donut',
            },
            colors: ['#4caf50', '#ffc107', '#d32f2f', '#009688'],
            labels: ['On Track', 'At Risk', 'Off Track', 'Completed'],
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
            dataLabels: {
                enabled: false
            },
            legend: {
                position: 'bottom'
            }
        };
        
        const distributionChart = new ApexCharts(document.querySelector("#statusDistributionChart"), distributionOptions);
        distributionChart.render();
        
        // Scenario Chart
        const scenarioOptions = {
            series: [
                {
                    name: 'Baseline',
                    data: [12450, 12000, 11600, 11200, 10800, 10400, 10000, 9600, 9200, 8800, 8400, 8240]
                },
                {
                    name: 'Accelerated',
                    data: [12450, 11500, 10600, 9800, 9100, 8500, 8000, 7600, 7200, 6800, 6400, 5860]
                },
                {
                    name: 'Innovative',
                    data: [12450, 11000, 9800, 8700, 7800, 7100, 6500, 6000, 5600, 5300, 5000, 4920]
                },
                {
                    name: 'Transformational',
                    data: [12450, 10500, 8800, 7400, 6300, 5400, 4700, 4200, 3800, 3500, 3300, 3150]
                },
                {
                    name: 'SBTi Target',
                    data: [11720, 11720, 11720, 11720, 11720, 11720, 11720, 11720, 11720, 11720, 11720, 5860]
                }
            ],
            chart: {
                height: 300,
                type: 'line',
                zoom: {
                    enabled: false
                },
                toolbar: {
                    show: false
                }
            },
            colors: ['#d32f2f', '#4caf50', '#ffc107', '#03a9f4', '#2e7d32'],
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            xaxis: {
                categories: ['2023', '2024', '2025', '2026', '2027', '2028', '2029', '2030', '2031', '2032', '2033', '2034'],
                title: {
                    text: 'Year'
                }
            },
            yaxis: {
                title: {
                    text: 'Emissions (tCO₂e)'
                },
                min: 2000,
                max: 13000
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right'
            },
            markers: {
                size: 5
            }
        };
        
        const scenarioChart = new ApexCharts(document.querySelector("#scenarioChart"), scenarioOptions);
        scenarioChart.render();
        
        // Initialize progress circles
        updateProgressCircles();
    });
    
    // Progress circle updates
    function updateProgressCircles() {
        const circles = document.querySelectorAll('.progress-circle');
        circles.forEach(circle => {
            const fill = circle.querySelector('.progress-circle-fill');
            const value = circle.querySelector('.progress-circle-value');
            const progress = parseFloat(circle.style.getPropertyValue('--progress'));
            
            if (fill && value) {
                const percentage = Math.round(progress * 100);
                value.textContent = `${percentage}%`;
            }
        });
    }
    
    // Target actions
    function updateProgress(targetId) {
        showToast(`Updating progress for ${targetId}`, 'info');
    }
    
    function viewTargetReport(targetId) {
        const details = `
            <div class="row">
                <div class="col-md-6">
                    <h6>Target Information</h6>
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Target ID:</strong></td>
                            <td><code>${targetId}</code></td>
                        </tr>
                        <tr>
                            <td><strong>Name:</strong></td>
                            <td>Net Zero 2050</td>
                        </tr>
                        <tr>
                            <td><strong>Type:</strong></td>
                            <td>Net Zero Target</td>
                        </tr>
                        <tr>
                            <td><strong>Deadline:</strong></td>
                            <td>2050</td>
                        </tr>
                        <tr>
                            <td><strong>Status:</strong></td>
                            <td><span class="badge bg-success">On Track</span></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6>Progress Metrics</h6>
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Baseline (2020):</strong></td>
                            <td>14,900 tCO₂e</td>
                        </tr>
                        <tr>
                            <td><strong>Current (2023):</strong></td>
                            <td>12,450 tCO₂e</td>
                        </tr>
                        <tr>
                            <td><strong>Target (2050):</strong></td>
                            <td>0 tCO₂e</td>
                        </tr>
                        <tr>
                            <td><strong>Reduction Achieved:</strong></td>
                            <td>2,450 tCO₂e (16.4%)</td>
                        </tr>
                        <tr>
                            <td><strong>Annual Reduction Needed:</strong></td>
                            <td>461 tCO₂e/year</td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="mt-4">
                <h6>Progress Chart</h6>
                <div class="text-center p-4" style="background-color: #f8f9fa; border-radius: 8px;">
                    <i class="fas fa-chart-line fa-2x text-muted mb-3"></i>
                    <p class="text-muted">Detailed progress chart would appear here</p>
                </div>
            </div>
        `;
        
        document.getElementById('targetDetailsContent').innerHTML = details;
        const modal = new bootstrap.Modal(document.getElementById('targetDetailsModal'));
        modal.show();
    }
    
    function editTarget(targetId) {
        const newTargetModal = new bootstrap.Modal(document.getElementById('newTargetModal'));
        newTargetModal.show();
        showToast(`Editing ${targetId}`, 'info');
    }
    
    function celebrateAchievement(targetId) {
        showToast(`🎉 Celebrating achievement of ${targetId}!`, 'success');
    }
    
    function setNewTarget() {
        const newTargetModal = new bootstrap.Modal(document.getElementById('newTargetModal'));
        newTargetModal.show();
        showToast('Setting new target', 'info');
    }
    
    // Wizard functionality
    function nextWizardStep() {
        if (currentWizardStep < 3) {
            document.getElementById(`step${currentWizardStep}`).classList.remove('active');
            currentWizardStep++;
            document.getElementById(`step${currentWizardStep}`).classList.add('active');
            document.getElementById('wizardProgress').style.width = `${(currentWizardStep / 3) * 100}%`;
            
            // Update button visibility
            if (currentWizardStep === 3) {
                document.getElementById('nextBtn').style.display = 'none';
                document.getElementById('saveBtn').style.display = 'block';
            }
            document.getElementById('prevBtn').style.display = 'block';
        }
    }
    
    function prevWizardStep() {
        if (currentWizardStep > 1) {
            document.getElementById(`step${currentWizardStep}`).classList.remove('active');
            currentWizardStep--;
            document.getElementById(`step${currentWizardStep}`).classList.add('active');
            document.getElementById('wizardProgress').style.width = `${(currentWizardStep / 3) * 100}%`;
            
            // Update button visibility
            if (currentWizardStep === 1) {
                document.getElementById('prevBtn').style.display = 'none';
            }
            document.getElementById('nextBtn').style.display = 'block';
            document.getElementById('saveBtn').style.display = 'none';
        }
    }
    
    function saveTarget() {
        showToast('Target saved successfully!', 'success');
        document.getElementById('newTargetModal').querySelector('.btn-close').click();
        
        // Reset wizard
        currentWizardStep = 1;
        document.querySelectorAll('.wizard-step').forEach(step => {
            step.classList.remove('active');
        });
        document.getElementById('step1').classList.add('active');
        document.getElementById('wizardProgress').style.width = '33%';
        document.getElementById('prevBtn').style.display = 'none';
        document.getElementById('nextBtn').style.display = 'block';
        document.getElementById('saveBtn').style.display = 'none';
    }
    
    // Scenario selection
    function selectScenario(scenario) {
        selectedScenario = scenario;
        
        // Update active scenario card
        document.querySelectorAll('.scenario-card').forEach(card => {
            card.classList.remove('active');
        });
        event.target.closest('.scenario-card').classList.add('active');
        
        showToast(`Scenario "${scenario}" selected`, 'info');
    }
    
    // Milestone actions
    function addMilestone() {
        showToast('Add new milestone', 'info');
    }
    
    // Filter functionality
    document.getElementById('applyFilters').addEventListener('click', function() {
        showToast('Filters applied successfully', 'success');
    });
    
    document.getElementById('resetFilters').addEventListener('click', function() {
        document.querySelectorAll('.filter-panel select').forEach(select => {
            select.value = 'all';
        });
        showToast('Filters reset', 'info');
    });
    
    // Other functions
    function showAtRiskTargets() {
        document.getElementById('statusFilter').value = 'at-risk';
        document.getElementById('applyFilters').click();
        showToast('Showing targets at risk', 'info');
    }
    
    function showAllTargets() {
        document.getElementById('statusFilter').value = 'all';
        document.getElementById('applyFilters').click();
        showToast('Showing all targets', 'info');
    }
    
    function downloadTargetReport() {
        showToast('Downloading target report...', 'info');
        setTimeout(() => {
            showToast('Report downloaded successfully', 'success');
            document.getElementById('targetDetailsModal').querySelector('.btn-close').click();
        }, 1500);
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


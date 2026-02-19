@extends('layouts.app')

@section('title', 'Targets & Goals')
@section('page-title', 'Targets & Goals Tracking')

@push('styles')
<style>
.targets-app * { box-sizing: border-box; }
.targets-app { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
/* Topbar */
.targets-app .topbar { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; padding: 20px 24px; background: linear-gradient(135deg, #fff 0%, var(--gray-50) 100%); border: 1px solid var(--gray-200); border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.targets-app .topbar h2 { font-size: 1.35rem; font-weight: 700; letter-spacing: -0.02em; display: flex; align-items: center; gap: 10px; margin: 0; color: var(--gray-800); }
.targets-app .topbar h2 .sb { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%); color: #fff; font-size: 1rem; font-weight: 700; box-shadow: 0 2px 8px rgba(46,125,50,.25); }
.targets-app .topbar p { color: var(--gray-600); font-size: 0.875rem; flex: 1; min-width: 180px; margin: 0; line-height: 1.4; }
.targets-app .btn-add { margin-left: auto; padding: 10px 20px; border-radius: 10px; background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%); color: #fff; border: none; font-size: 0.875rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
.targets-app .btn-add:hover { background: linear-gradient(135deg, var(--dark-green) 0%, var(--primary-green) 100%); color: #fff; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(46,125,50,.35); }
/* Stats */
.targets-app .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
.targets-app .sc { background: #fff; border: 1px solid var(--gray-200); border-radius: 14px; padding: 18px 20px; display: flex; align-items: center; gap: 14px; box-shadow: 0 2px 8px rgba(0,0,0,.06); transition: transform .2s, box-shadow .2s; }
.targets-app .sc:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,.08); }
.targets-app .sc .si { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0; }
.targets-app .sc .si.g { background: linear-gradient(135deg, rgba(46,125,50,.15) 0%, rgba(76,175,80,.12) 100%); color: var(--primary-green); }
.targets-app .sc .si.suc { background: linear-gradient(135deg, rgba(76,175,80,.2) 0%, rgba(129,199,132,.15) 100%); color: var(--light-green); }
.targets-app .sc .si.b { background: linear-gradient(135deg, rgba(2,119,189,.12) 0%, rgba(3,169,244,.1) 100%); color: var(--primary-blue); }
.targets-app .sc .si.w { background: linear-gradient(135deg, rgba(245,124,0,.15) 0%, rgba(255,152,0,.1) 100%); color: var(--warning-orange); }
.targets-app .sc .sv { font-size: 1.5rem; font-weight: 700; letter-spacing: -0.02em; color: var(--gray-800); }
.targets-app .sc .sl { font-size: 0.75rem; color: var(--gray-600); margin-top: 2px; font-weight: 500; text-transform: uppercase; letter-spacing: .04em; }
.targets-app .sc .qm { height: 6px; border-radius: 3px; background: var(--gray-200); overflow: hidden; margin-top: 8px; flex: 1; min-width: 60px; }
.targets-app .sc .qm-fill { height: 100%; border-radius: 3px; background: var(--light-green); }
.targets-app .stat-sub { font-size: 0.8125rem; font-weight: 600; color: var(--light-green); }
.targets-app .btn-at-risk { margin-top: 8px; padding: 6px 12px; border-radius: 8px; font-size: 0.8125rem; font-weight: 600; border: none; background: rgba(245,124,0,0.15); color: var(--warning-orange); cursor: pointer; }
.targets-app .btn-at-risk:hover { background: rgba(245,124,0,0.25); }
/* Filter panel */
.targets-app .filter-panel { background: #fff; border: 1px solid var(--gray-200); border-radius: 16px; padding: 20px 24px; box-shadow: 0 2px 8px rgba(0,0,0,.06); margin-bottom: 20px; }
.targets-app .filter-panel .form-label { font-size: 13px; font-weight: 600; color: var(--gray-800); margin-bottom: 6px; }
.targets-app .filter-panel .form-select { padding: 10px 14px; font-size: 14px; border: 1.5px solid var(--gray-200); border-radius: 10px; background: #fff; }
.targets-app .filter-panel .form-select:focus { border-color: var(--primary-green); outline: none; }
.targets-app .filter-panel .btn-reset { background: var(--gray-100); border: 1.5px solid var(--gray-200); color: var(--gray-600); padding: 8px 16px; border-radius: 10px; font-weight: 600; }
.targets-app .filter-panel .btn-reset:hover { background: var(--gray-200); color: var(--gray-800); }
.targets-app .filter-panel .btn-outline { border: 1.5px solid var(--primary-green); color: var(--primary-green); background: #fff; padding: 8px 16px; border-radius: 10px; font-weight: 600; }
.targets-app .filter-panel .btn-apply { background: var(--primary-green); color: #fff; border: none; padding: 8px 16px; border-radius: 10px; font-weight: 600; }
.targets-app .filter-panel .btn-apply:hover { background: var(--dark-green); }
/* Chart container */
.targets-app .chart-container { background: #fff; border: 1px solid var(--gray-200); border-radius: 16px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,.06); margin-bottom: 20px; }
.targets-app .chart-title { font-size: 1rem; font-weight: 700; color: var(--gray-800); margin-bottom: 15px; }
/* Target card, progress, badges */
.targets-app .config-label { font-size: 0.75rem; font-weight: 600; color: var(--gray-600); text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px; }
.targets-app .progress-circle { width: 80px; height: 80px; position: relative; flex-shrink: 0; }
.targets-app .progress-circle svg { width: 100%; height: 100%; transform: rotate(-90deg); }
.targets-app .progress-circle-bg { fill: none; stroke: var(--gray-200); stroke-width: 8; }
.targets-app .progress-circle-fill { fill: none; stroke-width: 8; stroke-linecap: round; stroke-dasharray: 226.2; stroke-dashoffset: calc(226.2 * (1 - var(--progress))); transition: stroke-dashoffset 0.5s ease; }
.targets-app .progress-circle-value { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-weight: 700; font-size: 1.2rem; }
.targets-app .target-card { background: #fff; border: 1px solid var(--gray-200); border-radius: 14px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,.06); margin-bottom: 20px; border-left: 4px solid var(--primary-green); transition: all .2s; height: 100%; }
.targets-app .target-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,.08); }
.targets-app .target-card.on-track { border-left-color: var(--light-green); }
.targets-app .target-card.at-risk { border-left-color: var(--warning-orange); }
.targets-app .target-card.off-track { border-left-color: var(--danger-red); }
.targets-app .target-card.completed { border-left-color: #009688; }
.targets-app .target-status { padding: 4px 10px; border-radius: 100px; font-size: 0.75rem; font-weight: 600; }
.targets-app .status-on-track { background: rgba(76,175,80,0.12); color: var(--light-green); }
.targets-app .status-at-risk { background: rgba(255,193,7,0.12); color: #b38600; }
.targets-app .status-off-track { background: rgba(211,47,47,0.12); color: var(--danger-red); }
.targets-app .status-completed { background: rgba(0,150,136,0.12); color: #009688; }
.targets-app .goal-type { padding: 4px 10px; border-radius: 100px; font-size: 0.75rem; font-weight: 600; }
.targets-app .type-sbt { background: rgba(123,31,162,0.12); color: #7b1fa2; }
.targets-app .type-net-zero { background: rgba(3,169,244,0.12); color: var(--light-blue); }
.targets-app .type-carbon-neutral { background: rgba(46,125,50,0.12); color: var(--primary-green); }
.targets-app .type-regulatory { background: rgba(245,124,0,0.12); color: var(--warning-orange); }
.targets-app .type-internal { background: rgba(158,158,158,0.12); color: #757575; }
.targets-app .target-progress { height: 8px; border-radius: 4px; background: var(--gray-200); overflow: hidden; margin: 15px 0; }
.targets-app .target-progress-bar { height: 100%; border-radius: 4px; transition: width 0.5s ease; }
.targets-app .target-actions { display: flex; gap: 8px; margin-top: 15px; }
.targets-app .action-btn { flex: 1; padding: 8px 12px; border-radius: 8px; border: 1.5px solid var(--gray-200); background: #fff; color: var(--gray-600); font-size: 0.8125rem; display: flex; align-items: center; justify-content: center; gap: 5px; transition: all .2s; text-decoration: none; cursor: pointer; }
.targets-app .action-btn:hover { background: var(--gray-50); color: var(--gray-800); }
.targets-app .report-btn:hover { border-color: var(--primary-green); background: rgba(46,125,50,0.08); color: var(--primary-green); }
.targets-app .edit-btn:hover { border-color: var(--warning-orange); background: rgba(255,193,7,0.08); color: #b38600; }
.targets-app .delete-btn:hover { border-color: #dc3545; background: rgba(220,53,69,0.08); color: #dc3545; }
/* Milestone */
.targets-app .milestone-timeline { position: relative; padding-left: 30px; }
.targets-app .milestone-timeline::before { content: ''; position: absolute; left: 15px; top: 0; bottom: 0; width: 2px; background: var(--gray-200); }
.targets-app .milestone-item { position: relative; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid var(--gray-200); }
.targets-app .milestone-item:last-child { border-bottom: none; }
.targets-app .milestone-dot { position: absolute; left: -22px; width: 16px; height: 16px; border-radius: 50%; background: #fff; border: 3px solid var(--gray-300); }
.targets-app .milestone-dot.completed { border-color: var(--light-green); background: var(--light-green); }
.targets-app .milestone-dot.current { border-color: var(--light-blue); background: #fff; }
.targets-app .milestone-dot.upcoming { border-color: var(--gray-300); background: #fff; }
/* Cards */
.targets-app .card { border: 1px solid var(--gray-200); border-radius: 16px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.targets-app .card-header { padding: 16px 20px; border-bottom: 1px solid var(--gray-200); background: linear-gradient(180deg, var(--gray-50) 0%, #fff 100%); font-weight: 700; color: var(--gray-800); }
.targets-app .scenario-card { text-align: center; padding: 20px 16px; background: #fff; border: 1.5px solid var(--gray-200); border-radius: 14px; cursor: pointer; transition: all .2s; height: 100%; }
.targets-app .scenario-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,.08); }
.targets-app .scenario-card.active { border-color: var(--primary-green); background: rgba(46,125,50,0.06); }
.targets-app .scenario-icon { font-size: 2rem; margin-bottom: 12px; }
.targets-app .target-wizard { background: #fff; border-radius: 14px; padding: 24px; margin-bottom: 0; }
.targets-app .wizard-step { padding: 20px 0; display: none; }
.targets-app .wizard-step.active { display: block; }
.targets-app .wizard-header { border-bottom: 1px solid var(--gray-200); padding-bottom: 20px; margin-bottom: 24px; }
</style>
@endpush

@section('content')
    <div id="content">
        @include('layouts.top-nav')

        <div class="targets-app container-fluid mt-4">
            <!-- Topbar -->
            <div class="topbar">
                <h2><span class="sb"><i class="fas fa-bullseye"></i></span> Emission Reduction Targets</h2>
                <p>Track progress towards climate goals and science-based targets. Set new targets, monitor progress, and adjust strategies.</p>
                <button type="button" class="btn-add" id="btnSetNewTarget" data-bs-toggle="modal" data-bs-target="#newTargetModal">
                    <i class="fas fa-plus"></i> Set New Target
                </button>
            </div>

            <!-- Stats -->
            <div class="stats">
                <div class="sc">
                    <div class="si g"><i class="fas fa-bullseye"></i></div>
                    <div style="flex:1;min-width:0;">
                        <div class="sv">{{ $activeTargets ?? 0 }}</div>
                        <div class="sl">Active Targets</div>
                        <span class="stat-sub" style="font-size: 0.75rem; color: var(--gray-600);">Saved in database</span>
                    </div>
                </div>
                <div class="sc">
                    <div class="si suc"><i class="fas fa-check-circle"></i></div>
                    <div style="flex:1;min-width:0;">
                        <div class="sv">{{ $onTrackCount ?? 0 }}</div>
                        <div class="sl">On Track</div>
                        @php $totalTargets = isset($statusDistribution) ? array_sum($statusDistribution) : 0; $onTrackPct = $totalTargets > 0 ? round((($onTrackCount ?? 0) / $totalTargets) * 100) : 0; @endphp
                        <div class="qm mt-2"><div class="qm-fill" style="width: {{ $onTrackPct }}%;"></div></div>
                    </div>
                </div>
                <div class="sc">
                    <div class="si b"><i class="fas fa-chart-line"></i></div>
                    <div style="flex:1;min-width:0;">
                        <div class="sv">{{ number_format((float) ($totalReduction ?? 0), 2) }} <span style="font-size: 0.875rem;">tCO₂e</span></div>
                        <div class="sl">Total Reduction</div>
                        <span class="stat-sub"><i class="fas fa-arrow-down me-1"></i> vs baseline</span>
                    </div>
                </div>
                <div class="sc">
                    <div class="si w"><i class="fas fa-exclamation-triangle"></i></div>
                    <div style="flex:1;min-width:0;">
                        <div class="sv">{{ $atRiskCount ?? 0 }}</div>
                        <div class="sl">At Risk</div>
                        <button type="button" class="btn-at-risk" onclick="showAtRiskTargets()">
                            <i class="fas fa-external-link-alt me-1"></i>Review Now
                        </button>
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
                    <div class="col-12 mt-3 d-flex justify-content-between flex-wrap gap-2">
                        <button type="button" class="btn btn-reset" id="resetFilters">
                            <i class="fas fa-redo me-2"></i>Reset Filters
                        </button>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline" id="saveFilterBtn">
                                <i class="fas fa-save me-2"></i>Save Filter
                            </button>
                            <button type="button" class="btn btn-apply" id="applyFilters">
                                <i class="fas fa-filter me-2"></i>Apply Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        
        <!-- Charts Row -->
        <div class="row mt-4">
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
        <div class="row mt-4">
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mb-0">Active Targets</h4>
                    <button class="btn btn-outline-primary" onclick="showAllTargets()">
                        <i class="fas fa-list me-2"></i>View All Targets
                    </button>
                </div>
                
                <!-- Target Cards -->
                <div class="row" id="targetsGrid">
                    @php
                        $activeList = isset($targets) ? $targets->where('status', '!=', 'completed') : collect();
                    @endphp

                    @forelse($activeList as $t)
                        @php
                            $cardClass = match($t->status) {
                                'on-track' => 'on-track',
                                'at-risk' => 'at-risk',
                                'off-track' => 'off-track',
                                'completed' => 'completed',
                                default => 'on-track',
                            };

                            $statusClass = match($t->status) {
                                'on-track' => 'status-on-track',
                                'at-risk' => 'status-at-risk',
                                'off-track' => 'status-off-track',
                                'completed' => 'status-completed',
                                default => 'status-on-track',
                            };

                            $typeClass = match($t->type) {
                                'sbt' => 'type-sbt',
                                'net-zero' => 'type-net-zero',
                                'carbon-neutral' => 'type-carbon-neutral',
                                'regulatory' => 'type-regulatory',
                                'internal' => 'type-internal',
                                default => 'type-internal',
                            };

                            $typeLabel = match($t->type) {
                                'sbt' => 'Science-Based',
                                'net-zero' => 'Net Zero',
                                'carbon-neutral' => 'Carbon Neutral',
                                'regulatory' => 'Regulatory',
                                'internal' => 'Internal',
                                default => 'Internal',
                            };

                            $icon = match($t->type) {
                                'sbt' => 'fa-temperature-low',
                                'net-zero' => 'fa-globe-europe',
                                'carbon-neutral' => 'fa-leaf',
                                'regulatory' => 'fa-balance-scale',
                                'internal' => 'fa-bullseye',
                                default => 'fa-bullseye',
                            };

                            $progress = (float) ($t->progress_percent ?? 0);
                            $progress01 = max(0, min(1, $progress / 100));
                            $strokeColor = $t->status === 'at-risk' ? '#ffc107' : ($t->status === 'off-track' ? 'var(--danger-red)' : 'var(--light-green)');
                        @endphp

                        <div class="col-md-6 mb-4">
                            <div class="target-card {{ $cardClass }}">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h5 class="mb-1">{{ $t->name }}</h5>
                                        <span class="goal-type {{ $typeClass }}">
                                            <i class="fas {{ $icon }} me-1"></i>{{ $typeLabel }}
                                        </span>
                                    </div>
                                    <span class="target-status {{ $statusClass }}">
                                        <i class="fas {{ $t->status === 'on-track' ? 'fa-check-circle' : ($t->status === 'at-risk' ? 'fa-exclamation-triangle' : ($t->status === 'off-track' ? 'fa-times-circle' : 'fa-trophy')) }}"></i>
                                        {{ ucwords(str_replace('-', ' ', $t->status)) }}
                                    </span>
                                </div>

                                <p class="text-muted small mb-3">{{ $t->description ?: 'No description provided.' }}</p>

                                <div class="mb-3">
                                    <div class="config-label">Target Details</div>
                                    <div class="small">
                                        <i class="fas fa-calendar me-1"></i> Deadline: {{ $t->target_year }}
                                        <br>
                                        <i class="fas fa-bullseye me-1"></i> Baseline: {{ $t->baseline_year ?: 'N/A' }}
                                        <br>
                                        <i class="fas fa-weight me-1"></i> Reduction: {{ $t->reduction_percent !== null ? number_format((float)$t->reduction_percent, 1) . '%' : 'N/A' }}
                                        <br>
                                        <i class="fas fa-layer-group me-1"></i> Scope: {{ $t->scope }}
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="config-label">Progress (current year)</div>
                                    <div class="d-flex align-items-center">
                                        <div class="progress-circle" style="--progress: {{ $progress01 }}">
                                            <svg>
                                                <circle class="progress-circle-bg" cx="40" cy="40" r="36"></circle>
                                                <circle class="progress-circle-fill" cx="40" cy="40" r="36" style="stroke: {{ $strokeColor }};"></circle>
                                            </svg>
                                            <div class="progress-circle-value">{{ number_format($progress, 0) }}%</div>
                                        </div>
                                        <div class="ms-3 flex-grow-1">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="small">Current: {{ number_format((float) ($t->current_emissions ?? 0), 2) }} tCO₂e</span>
                                                <span class="small fw-bold">
                                                    Baseline: {{ $t->baseline_emissions !== null ? number_format((float) $t->baseline_emissions, 2) : 'N/A' }} tCO₂e
                                                </span>
                                            </div>
                                            <div class="target-progress">
                                                <div class="target-progress-bar" style="width: {{ $progress }}%; background-color: {{ $strokeColor }};"></div>
                                            </div>
                                            <div class="small text-muted">Target: {{ $t->target_emissions !== null ? number_format((float) $t->target_emissions, 2) : 'N/A' }} tCO₂e by {{ $t->target_year }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="target-actions">
                                    <a href="#" class="action-btn report-btn" onclick="viewTargetReport({{ $t->id }}); return false;">
                                        <i class="fas fa-file-alt"></i> Report
                                    </a>
                                    <a href="#" class="action-btn edit-btn" onclick="editTarget({{ $t->id }}); return false;">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="#" class="action-btn delete-btn" onclick="deleteTarget({{ $t->id }}); return false;">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="alert alert-info mb-0">
                                No targets yet. Click <strong>Set New Target</strong> to create your first one.
                            </div>
                        </div>
                    @endforelse
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
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Scenario Modeling</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4">Compare different pathways to achieve your emission reduction targets</p>
                        
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <div class="scenario-card active" onclick="selectScenario('baseline', event)">
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
                                <div class="scenario-card" onclick="selectScenario('accelerated', event)">
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
                                <div class="scenario-card" onclick="selectScenario('innovative', event)">
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
                                <div class="scenario-card" onclick="selectScenario('transformational', event)">
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
        
        <!-- New Target Modal -->
        <div class="modal fade" id="newTargetModal" tabindex="-1" aria-labelledby="newTargetModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="newTargetModalLabel">Set New Reduction Target</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                    <input type="hidden" id="targetId" value="">
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
                                    <input type="text" class="form-control" id="targetName" placeholder="e.g., Net Zero 2050">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Target Type</label>
                                    <select class="form-select" id="targetType">
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
                                    <select class="form-select" id="targetScope">
                                        <option value="">Select scope...</option>
                                        <option value="1">Scope 1 Only</option>
                                        <option value="2">Scope 2 Only</option>
                                        <option value="3">Scope 3 Only</option>
                                        <option value="1-2">Scope 1+2</option>
                                        <option value="all-scopes">All Scopes (1+2+3)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Target Deadline</label>
                                    <input type="number" class="form-control" id="targetDeadline" placeholder="e.g., 2030" min="2000" max="2100">
                                </div>
                                
                                <div class="col-12">
                                    <label class="form-label">Target Description</label>
                                    <textarea class="form-control" rows="3" id="targetDescription" placeholder="Describe your target and its importance..."></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Step 2: Reduction Metrics -->
                        <div class="wizard-step" id="step2">
                            <h6 class="mb-3">Reduction Metrics</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Baseline Year</label>
                                    <input type="number" class="form-control" id="baselineYear" placeholder="e.g., 2020" min="2000" max="2100">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Baseline Emissions</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="baselineEmissions" placeholder="0.00" min="0" step="0.01">
                                        <span class="input-group-text">tCO₂e</span>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Reduction Percentage</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="reductionPercent" placeholder="0.00" min="0" max="100" step="0.1">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Target Emissions</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="targetEmissions" placeholder="0.00" min="0" step="0.01">
                                        <span class="input-group-text">tCO₂e</span>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <label class="form-label">Reduction Strategy</label>
                                    <select class="form-select" id="reductionStrategy">
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
                                    <select class="form-select" id="reviewFrequency">
                                        <option value="monthly">Monthly</option>
                                        <option value="quarterly" selected>Quarterly</option>
                                        <option value="biannual">Biannual</option>
                                        <option value="annual">Annual</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Responsible Person</label>
                                    <input type="text" class="form-control" id="responsiblePerson" placeholder="e.g., Sustainability Team">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" id="targetStatus">
                                        <option value="on-track" selected>On Track</option>
                                        <option value="at-risk">At Risk</option>
                                        <option value="off-track">Off Track</option>
                                        <option value="completed">Completed</option>
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
        <div class="modal fade" id="targetDetailsModal" tabindex="-1" aria-labelledby="targetDetailsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="targetDetailsModalLabel">Target Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="targetDetailsContent" class="min-vh-25">
                            <p class="text-muted text-center py-4 mb-0">Loading...</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="btnDownloadTargetReport" onclick="downloadTargetReport()">
                            <i class="fas fa-download me-2"></i>Download Report
                        </button>
                    </div>
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
    const targetsStoreUrl = "{{ route('targets.storeOrUpdate') }}";
    const targetsBaseUrl = "{{ url('targets') }}";
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    // Chart data from backend
    const progressChartData = @json($progressChartData);
    const scenarioChartData = @json($scenarioChartData);
    
    // Initialize charts when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Reset New Target form when opening modal via "Set New Target" button
        document.getElementById('btnSetNewTarget').addEventListener('click', resetNewTargetForm);
        document.getElementById('newTargetModal').addEventListener('show.bs.modal', function(e) {
            if (!document.getElementById('targetId').value) resetNewTargetForm();
        });

        // Target Progress Chart
        const progressOptions = {
            series: [
                {
                    name: 'Actual Emissions',
                    data: progressChartData.actual || []
                },
                {
                    name: 'Target Trajectory',
                    data: progressChartData.target || []
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
                categories: progressChartData.years || [],
                title: {
                    text: 'Year'
                }
            },
            yaxis: {
                title: {
                    text: 'Emissions (tCO₂e)'
                },
                min: 0
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
        
        // Status Distribution Chart (dynamic)
        const distributionOptions = {
            series: [
                {{ (int)($statusDistribution['on-track'] ?? 0) }},
                {{ (int)($statusDistribution['at-risk'] ?? 0) }},
                {{ (int)($statusDistribution['off-track'] ?? 0) }},
                {{ (int)($statusDistribution['completed'] ?? 0) }},
            ],
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
                    data: scenarioChartData.baseline || []
                },
                {
                    name: 'Accelerated',
                    data: scenarioChartData.accelerated || []
                },
                {
                    name: 'Innovative',
                    data: scenarioChartData.innovative || []
                },
                {
                    name: 'Transformational',
                    data: scenarioChartData.transformational || []
                },
                {
                    name: 'SBTi Target',
                    data: scenarioChartData.sbtiTarget || []
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
                categories: scenarioChartData.years || [],
                title: {
                    text: 'Year'
                }
            },
            yaxis: {
                title: {
                    text: 'Emissions (tCO₂e)'
                },
                min: 0
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
    
    async function viewTargetReport(targetId) {
        try {
            const res = await fetch(`${targetsBaseUrl}/${targetId}`, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (!res.ok) throw new Error(data?.message || 'Failed to load target');

            const statusBadge = data.status === 'on-track' ? 'bg-success' :
                                data.status === 'at-risk' ? 'bg-warning text-dark' :
                                data.status === 'off-track' ? 'bg-danger' : 'bg-info';

            const details = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Target Information</h6>
                        <table class="table table-sm">
                            <tr><td><strong>ID:</strong></td><td><code>${data.id}</code></td></tr>
                            <tr><td><strong>Name:</strong></td><td>${data.name}</td></tr>
                            <tr><td><strong>Type:</strong></td><td>${data.type}</td></tr>
                            <tr><td><strong>Scope:</strong></td><td>${data.scope}</td></tr>
                            <tr><td><strong>Deadline:</strong></td><td>${data.target_year}</td></tr>
                            <tr><td><strong>Status:</strong></td><td><span class="badge ${statusBadge}">${data.status}</span></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Metrics</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Baseline Year:</strong></td><td>${data.baseline_year ?? '-'}</td></tr>
                            <tr><td><strong>Baseline Emissions:</strong></td><td>${data.baseline_emissions ?? '-'} tCO₂e</td></tr>
                            <tr><td><strong>Target Emissions:</strong></td><td>${data.target_emissions ?? '-'} tCO₂e</td></tr>
                            <tr><td><strong>Reduction %:</strong></td><td>${data.reduction_percent ?? '-'}%</td></tr>
                        </table>
                    </div>
                </div>
                <div class="mt-3">
                    <h6>Description</h6>
                    <div class="p-3 rounded" style="background-color:#f8f9fa;">${data.description ?? '—'}</div>
                </div>
            `;

            document.getElementById('targetDetailsContent').innerHTML = details;
            const modal = new bootstrap.Modal(document.getElementById('targetDetailsModal'));
            modal.show();
        } catch (e) {
            showToast(e.message || 'Error loading target', 'error');
        }
    }
    
    async function editTarget(targetId) {
        try {
            const res = await fetch(`${targetsBaseUrl}/${targetId}`, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (!res.ok) throw new Error(data?.message || 'Failed to load target');

            // Fill form fields
            document.getElementById('targetId').value = data.id;
            document.getElementById('targetName').value = data.name ?? '';
            document.getElementById('targetType').value = data.type ?? '';
            const scopeVal = (data.scope === 'all' ? 'all-scopes' : data.scope) ?? '';
            document.getElementById('targetScope').value = scopeVal;
            document.getElementById('targetDeadline').value = data.target_year ?? '';
            document.getElementById('targetDescription').value = data.description ?? '';

            document.getElementById('baselineYear').value = data.baseline_year ?? '';
            document.getElementById('baselineEmissions').value = data.baseline_emissions ?? '';
            document.getElementById('reductionPercent').value = data.reduction_percent ?? '';
            document.getElementById('targetEmissions').value = data.target_emissions ?? '';
            document.getElementById('reductionStrategy').value = data.strategy ?? '';

            document.getElementById('reviewFrequency').value = data.review_frequency ?? 'quarterly';
            document.getElementById('responsiblePerson').value = data.responsible_person ?? '';
            document.getElementById('targetStatus').value = data.status ?? 'on-track';

            // Reset wizard to step 1
            currentWizardStep = 1;
            document.querySelectorAll('.wizard-step').forEach(step => step.classList.remove('active'));
            document.getElementById('step1').classList.add('active');
            document.getElementById('wizardProgress').style.width = '33%';
            document.getElementById('prevBtn').style.display = 'none';
            document.getElementById('nextBtn').style.display = 'block';
            document.getElementById('saveBtn').style.display = 'none';

            const newTargetModal = new bootstrap.Modal(document.getElementById('newTargetModal'));
            newTargetModal.show();
        } catch (e) {
            showToast(e.message || 'Error loading target', 'error');
        }
    }

    async function deleteTarget(targetId) {
        if (!confirm('Delete this target?')) return;
        try {
            const res = await fetch(`${targetsBaseUrl}/${targetId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data?.message || 'Delete failed');
            showToast('Target deleted', 'success');
            window.location.reload();
        } catch (e) {
            showToast(e.message || 'Error deleting target', 'error');
        }
    }
    
    function celebrateAchievement(targetId) {
        showToast(`🎉 Celebrating achievement of ${targetId}!`, 'success');
    }
    
    function setNewTarget() {
        resetNewTargetForm();
        const newTargetModal = new bootstrap.Modal(document.getElementById('newTargetModal'));
        newTargetModal.show();
        showToast('Setting new target', 'info');
    }

    function resetNewTargetForm() {
        document.getElementById('targetId').value = '';
        document.getElementById('targetName').value = '';
        document.getElementById('targetType').value = '';
        document.getElementById('targetScope').value = '';
        document.getElementById('targetDeadline').value = '';
        document.getElementById('targetDescription').value = '';
        document.getElementById('baselineYear').value = '';
        document.getElementById('baselineEmissions').value = '';
        document.getElementById('reductionPercent').value = '';
        document.getElementById('targetEmissions').value = '';
        document.getElementById('reductionStrategy').value = '';
        document.getElementById('reviewFrequency').value = 'quarterly';
        document.getElementById('responsiblePerson').value = '';
        document.getElementById('targetStatus').value = 'on-track';
        document.getElementById('milestone2025').checked = false;
        document.getElementById('milestone2030').checked = false;
        document.getElementById('milestone2040').checked = false;
        document.getElementById('milestone2050').checked = false;
        document.getElementById('metricCarbon').checked = true;
        document.getElementById('metricEnergy').checked = false;
        document.getElementById('metricRenewable').checked = false;
        document.getElementById('metricCost').checked = false;
        currentWizardStep = 1;
        document.querySelectorAll('.wizard-step').forEach(function(s) { s.classList.remove('active'); });
        var step1 = document.getElementById('step1');
        if (step1) step1.classList.add('active');
        document.getElementById('wizardProgress').style.width = '33%';
        document.getElementById('prevBtn').style.display = 'none';
        document.getElementById('nextBtn').style.display = 'block';
        document.getElementById('saveBtn').style.display = 'none';
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
        // Basic required checks
        const payload = {
            id: document.getElementById('targetId').value || null,
            name: document.getElementById('targetName').value,
            type: document.getElementById('targetType').value,
            scope: document.getElementById('targetScope').value,
            target_year: document.getElementById('targetDeadline').value,
            description: document.getElementById('targetDescription').value,
            baseline_year: document.getElementById('baselineYear').value || null,
            baseline_emissions: document.getElementById('baselineEmissions').value || null,
            reduction_percent: document.getElementById('reductionPercent').value || null,
            target_emissions: document.getElementById('targetEmissions').value || null,
            strategy: document.getElementById('reductionStrategy').value || null,
            review_frequency: document.getElementById('reviewFrequency').value,
            responsible_person: document.getElementById('responsiblePerson').value || null,
            status: document.getElementById('targetStatus').value,
        };

        // Convert blank strings to null for numeric fields
        ['baseline_year','baseline_emissions','reduction_percent','target_emissions'].forEach(k => {
            if (payload[k] === '') payload[k] = null;
        });

        fetch(targetsStoreUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload)
        })
        .then(async res => {
            const data = await res.json();
            if (!res.ok) throw new Error(data?.message || 'Validation error');
            return data;
        })
        .then(() => {
            showToast('Target saved successfully!', 'success');
            window.location.reload();
        })
        .catch(err => {
            showToast(err.message || 'Failed to save target', 'error');
        });
    }
    
    // Scenario selection
    function selectScenario(scenario, ev) {
        selectedScenario = scenario;
        var card = (ev && ev.target) ? ev.target.closest('.scenario-card') : document.querySelector('.scenario-card[onclick*="' + scenario + '"]');
        document.querySelectorAll('.scenario-card').forEach(function(c) { c.classList.remove('active'); });
        if (card) card.classList.add('active');
        showToast('Scenario "' + scenario + '" selected', 'info');
    }
    
    // Milestone actions
    function addMilestone() {
        showToast('Add new milestone', 'info');
    }
    
    // Filter functionality
    document.getElementById('applyFilters').addEventListener('click', function() {
        const status = document.getElementById('statusFilter').value;
        const type = document.getElementById('typeFilter').value;
        const scope = document.getElementById('scopeFilter').value;

        const params = new URLSearchParams();
        if (status && status !== 'all') params.set('status', status);
        if (type && type !== 'all') params.set('type', type);
        if (scope && scope !== 'all') params.set('scope', scope);

        window.location.href = `${window.location.pathname}?${params.toString()}`;
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
    }
    
    function showAllTargets() {
        document.getElementById('statusFilter').value = 'all';
        document.getElementById('applyFilters').click();
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


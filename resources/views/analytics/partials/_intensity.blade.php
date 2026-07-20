{{-- Emissions Intensity Metrics Tab --}}
<div class="tab-pane fade" id="intensity" role="tabpanel">

    {{-- Warning banner if company data incomplete --}}
    <div id="intensityWarning" class="alert alert-warning d-flex align-items-center mb-3" style="display:none !important;">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <span id="intensityWarningText">Update your company profile with employee count and annual revenue to see all intensity metrics.</span>
        <a href="{{ route('companies.index') }}" class="btn btn-sm btn-warning ms-auto">Update Profile</a>
    </div>

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card analytics-kpi accent-dark">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="kpi-icon-sm icon-grad-dark"><i class="fas fa-smog"></i></div>
                    <div>
                        <div class="kpi-cap">Total Emissions</div>
                        <div class="kpi-num grad-dark" id="intensityTotal">-</div>
                        <div class="small text-muted">tCO2e</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card analytics-kpi accent-green">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="kpi-icon-sm icon-grad-green"><i class="fas fa-user-friends"></i></div>
                    <div>
                        <div class="kpi-cap">Per Employee</div>
                        <div class="kpi-num grad-green" id="intensityPerEmployee">-</div>
                        <div class="small text-muted">tCO2e / employee</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card analytics-kpi accent-blue">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="kpi-icon-sm icon-grad-blue"><i class="fas fa-coins"></i></div>
                    <div>
                        <div class="kpi-cap">Per Revenue</div>
                        <div class="kpi-num grad-blue" id="intensityPerRevenue">-</div>
                        <div class="small text-muted" id="intensityRevenueUnit">tCO2e / $M revenue</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card analytics-kpi accent-orange">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="kpi-icon-sm icon-grad-orange"><i class="fas fa-users"></i></div>
                    <div>
                        <div class="kpi-cap">Employees</div>
                        <div class="kpi-num grad-orange" id="intensityEmployeeCount">-</div>
                        <div class="small text-muted" id="intensityRevenueInfo">-</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Scope Intensity Breakdown --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 fw-semibold"><i class="fas fa-chart-bar me-2 text-success"></i>Intensity by Scope</h6>
                </div>
                <div class="card-body">
                    <div id="intensityScopeChart" style="min-height: 320px;"></div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 fw-semibold"><i class="fas fa-chart-line me-2 text-primary"></i>Monthly Intensity Trend</h6>
                </div>
                <div class="card-body">
                    <div id="intensityTrendChart" style="min-height: 320px;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Scope Intensity Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
            <h6 class="mb-0 fw-semibold"><i class="fas fa-table me-2 text-success"></i>Intensity Breakdown</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Scope</th>
                            <th class="text-end">Total (tCO2e)</th>
                            <th class="text-end">Per Employee</th>
                            <th class="text-end">Per $M Revenue</th>
                        </tr>
                    </thead>
                    <tbody id="intensityTableBody">
                        {{-- Populated by JS --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

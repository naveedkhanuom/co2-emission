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
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="small text-muted fw-semibold mb-1">TOTAL EMISSIONS</div>
                    <div class="h3 fw-bold text-dark mb-0" id="intensityTotal">-</div>
                    <div class="small text-muted">tCO2e</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="small text-muted fw-semibold mb-1">PER EMPLOYEE</div>
                    <div class="h3 fw-bold mb-0" style="color: var(--primary-green);" id="intensityPerEmployee">-</div>
                    <div class="small text-muted">tCO2e / employee</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="small text-muted fw-semibold mb-1">PER REVENUE</div>
                    <div class="h3 fw-bold mb-0" style="color: var(--primary-blue);" id="intensityPerRevenue">-</div>
                    <div class="small text-muted" id="intensityRevenueUnit">tCO2e / $M revenue</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="small text-muted fw-semibold mb-1">EMPLOYEES</div>
                    <div class="h3 fw-bold text-dark mb-0" id="intensityEmployeeCount">-</div>
                    <div class="small text-muted" id="intensityRevenueInfo">-</div>
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

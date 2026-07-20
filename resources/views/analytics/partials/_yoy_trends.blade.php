{{-- Year-over-Year Trends Tab --}}
<div class="tab-pane fade" id="trends" role="tabpanel">

    {{-- Period selector + Summary cards --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="btn-group" role="group" id="periodSelector">
            <button type="button" class="btn btn-sm btn-success active" data-period="monthly">Monthly</button>
            <button type="button" class="btn btn-sm btn-outline-success" data-period="quarterly">Quarterly</button>
            <button type="button" class="btn btn-sm btn-outline-success" data-period="annual">Annual</button>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card analytics-kpi accent-orange">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="kpi-icon-sm icon-grad-orange"><i class="fas fa-exchange-alt"></i></div>
                    <div>
                        <div class="kpi-cap">Total Change</div>
                        <div class="kpi-num mb-0" id="yoyTotalChange">-</div>
                        <div class="small fw-semibold" id="yoyTotalChangePct">-</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card analytics-kpi accent-green">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="kpi-icon-sm icon-grad-green"><i class="fas fa-calendar-check"></i></div>
                    <div>
                        <div class="kpi-cap">Current Period</div>
                        <div class="kpi-num grad-green" id="yoyCurrentTotal">-</div>
                        <div class="small text-muted" id="yoyCurrentRange">-</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card analytics-kpi accent-dark">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="kpi-icon-sm icon-grad-dark"><i class="fas fa-calendar-minus"></i></div>
                    <div>
                        <div class="kpi-cap">Previous Period</div>
                        <div class="kpi-num grad-dark" id="yoyPreviousTotal">-</div>
                        <div class="small text-muted" id="yoyPreviousRange">-</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="row g-3 mb-4">
        <div class="col-md-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 fw-semibold"><i class="fas fa-chart-line me-2 text-success"></i>Period Comparison</h6>
                </div>
                <div class="card-body">
                    <div id="yoyOverlayChart" style="min-height: 380px;"></div>
                    <div id="yoyNoData" class="text-center text-muted py-5" style="display:none;">
                        <i class="fas fa-chart-line fa-3x mb-3 opacity-25"></i>
                        <p>No trend data available for the selected period.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 fw-semibold"><i class="fas fa-water me-2 text-primary"></i>Change Contributors (Waterfall)</h6>
                </div>
                <div class="card-body">
                    <div id="waterfallChart" style="min-height: 380px;"></div>
                    <div id="waterfallNoData" class="text-center text-muted py-5" style="display:none;">
                        <i class="fas fa-water fa-3x mb-3 opacity-25"></i>
                        <p>No comparison data available.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Changes Detail Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
            <h6 class="mb-0 fw-semibold"><i class="fas fa-exchange-alt me-2 text-success"></i>Top Changes by Source</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Source</th>
                            <th class="text-end">Previous (tCO2e)</th>
                            <th class="text-end">Current (tCO2e)</th>
                            <th class="text-end">Change (tCO2e)</th>
                            <th class="text-center">Direction</th>
                        </tr>
                    </thead>
                    <tbody id="waterfallTableBody">
                        {{-- Populated by JS --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

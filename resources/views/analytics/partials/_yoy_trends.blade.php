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
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="small text-muted fw-semibold mb-1">TOTAL CHANGE</div>
                    <div class="h3 fw-bold mb-0" id="yoyTotalChange">-</div>
                    <div class="small" id="yoyTotalChangePct">-</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="small text-muted fw-semibold mb-1">CURRENT PERIOD</div>
                    <div class="h3 fw-bold text-dark mb-0" id="yoyCurrentTotal">-</div>
                    <div class="small text-muted" id="yoyCurrentRange">-</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="small text-muted fw-semibold mb-1">PREVIOUS PERIOD</div>
                    <div class="h3 fw-bold text-dark mb-0" id="yoyPreviousTotal">-</div>
                    <div class="small text-muted" id="yoyPreviousRange">-</div>
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

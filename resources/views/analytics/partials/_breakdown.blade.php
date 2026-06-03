{{-- Emissions Breakdown / Drill-down Tab --}}
<div class="tab-pane fade show active" id="breakdown" role="tabpanel">

    {{-- Dimension Selector + Breadcrumb --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="btn-group" role="group" id="dimensionSelector">
            <button type="button" class="btn btn-sm btn-success active" data-dimension="scope">By Scope</button>
            <button type="button" class="btn btn-sm btn-outline-success" data-dimension="source">By Source</button>
            <button type="button" class="btn btn-sm btn-outline-success" data-dimension="facility">By Facility</button>
            <button type="button" class="btn btn-sm btn-outline-success" data-dimension="department">By Department</button>
            <button type="button" class="btn btn-sm btn-outline-success" data-dimension="month">By Month</button>
        </div>
        <nav aria-label="breadcrumb" id="drilldownBreadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item active">All Emissions</li>
            </ol>
        </nav>
    </div>

    {{-- Chart --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div id="breakdownChart" style="min-height: 400px;"></div>
            <div id="breakdownNoData" class="text-center text-muted py-5" style="display:none;">
                <i class="fas fa-chart-bar fa-3x mb-3 opacity-25"></i>
                <p>No emission data available for the selected filters.</p>
            </div>
        </div>
    </div>

    {{-- Detail Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
            <h6 class="mb-0 fw-semibold"><i class="fas fa-table me-2 text-success"></i>Breakdown Details</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="breakdownTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">#</th>
                            <th>Category</th>
                            <th class="text-end">Emissions (tCO2e)</th>
                            <th class="text-end">Records</th>
                            <th class="text-end">% of Total</th>
                            <th style="width: 200px;">Distribution</th>
                        </tr>
                    </thead>
                    <tbody id="breakdownTableBody">
                        {{-- Populated by JS --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

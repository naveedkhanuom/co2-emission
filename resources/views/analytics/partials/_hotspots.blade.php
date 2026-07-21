{{-- Carbon Hotspot Analysis Tab --}}
<div class="tab-pane fade" id="hotspots" role="tabpanel">

    {{-- Grouping Selector --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="btn-group" role="group" id="hotspotGroupSelector">
            <button type="button" class="btn btn-sm btn-success active" data-group="source">By Source</button>
            <button type="button" class="btn btn-sm btn-outline-success" data-group="facility">By Facility</button>
            <button type="button" class="btn btn-sm btn-outline-success" data-group="department">By Department</button>
            <button type="button" class="btn btn-sm btn-outline-success" data-group="supplier">By Supplier</button>
        </div>
    </div>

    {{-- Treemap Chart --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 py-3">
            <h6 class="mb-0 fw-semibold"><i class="fas fa-th-large me-2 text-success"></i>Emissions Treemap (Scope &rarr; Source)</h6>
        </div>
        <div class="card-body">
            <div id="treemapChart" style="min-height: 420px;"></div>
            <div id="treemapNoData" class="text-center text-muted py-5" style="display:none;">
                <i class="fas fa-th-large fa-3x mb-3 opacity-25"></i>
                <p>No emission data available for treemap visualization.</p>
            </div>
        </div>
    </div>

    {{-- Top Contributors Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold"><i class="fas fa-fire-alt me-2 text-danger"></i>Top Emission Contributors</h6>
            <span class="badge bg-light text-dark" id="hotspotCount">0 sources</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="hotspotsTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width:50px;">Rank</th>
                            <th>Source</th>
                            <th>Scope</th>
                            <th class="text-end">Emissions (tCO2e)</th>
                            <th class="text-end">% of Total</th>
                            <th class="text-end">Cumulative %</th>
                            <th style="width: 150px;">Impact</th>
                        </tr>
                    </thead>
                    <tbody id="hotspotsTableBody">
                        {{-- Populated by JS --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

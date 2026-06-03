{{-- Analytics Filters --}}
<div class="filters-section mb-4">
    <div class="card border-0 shadow-sm">
        <div class="card-body py-3">
            <form id="analyticsFilterForm" class="row g-3 align-items-end">
                {{-- Date Range --}}
                <div class="col-md-2">
                    <label class="form-label fw-semibold text-muted small mb-1">Date Range</label>
                    <select class="form-select form-select-sm" id="filterDateRange" name="date_range">
                        <option value="3" {{ ($filters['date_range'] ?? '12') == '3' ? 'selected' : '' }}>Last 3 Months</option>
                        <option value="12" {{ ($filters['date_range'] ?? '12') == '12' ? 'selected' : '' }}>Last 12 Months</option>
                        <option value="ytd" {{ ($filters['date_range'] ?? '') == 'ytd' ? 'selected' : '' }}>Year to Date</option>
                        <option value="custom" {{ ($filters['date_range'] ?? '') == 'custom' ? 'selected' : '' }}>Custom Range</option>
                    </select>
                </div>

                {{-- Custom Date Inputs --}}
                <div class="col-md-2 custom-date-fields" style="{{ ($filters['date_range'] ?? '') == 'custom' ? '' : 'display:none;' }}">
                    <label class="form-label fw-semibold text-muted small mb-1">Start Date</label>
                    <input type="date" class="form-control form-control-sm" id="filterStartDate" name="start_date" value="{{ $filters['start_date'] ?? '' }}">
                </div>
                <div class="col-md-2 custom-date-fields" style="{{ ($filters['date_range'] ?? '') == 'custom' ? '' : 'display:none;' }}">
                    <label class="form-label fw-semibold text-muted small mb-1">End Date</label>
                    <input type="date" class="form-control form-control-sm" id="filterEndDate" name="end_date" value="{{ $filters['end_date'] ?? '' }}">
                </div>

                {{-- Scope --}}
                <div class="col-md-2">
                    <label class="form-label fw-semibold text-muted small mb-1">Scope</label>
                    <select class="form-select form-select-sm" id="filterScope" name="scope">
                        <option value="">All Scopes</option>
                        <option value="1" {{ ($filters['scope'] ?? '') == '1' ? 'selected' : '' }}>Scope 1</option>
                        <option value="2" {{ ($filters['scope'] ?? '') == '2' ? 'selected' : '' }}>Scope 2</option>
                        <option value="3" {{ ($filters['scope'] ?? '') == '3' ? 'selected' : '' }}>Scope 3</option>
                    </select>
                </div>

                {{-- Facility --}}
                <div class="col-md-2">
                    <label class="form-label fw-semibold text-muted small mb-1">Facility</label>
                    <select class="form-select form-select-sm" id="filterFacility" name="facility">
                        <option value="">All Facilities</option>
                        @foreach($facilities as $facility)
                            <option value="{{ $facility->id }}" {{ ($filters['facility'] ?? '') == $facility->id ? 'selected' : '' }}>
                                {{ $facility->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Department --}}
                <div class="col-md-2">
                    <label class="form-label fw-semibold text-muted small mb-1">Department</label>
                    <select class="form-select form-select-sm" id="filterDepartment" name="department">
                        <option value="">All Departments</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ ($filters['department'] ?? '') == $department->id ? 'selected' : '' }}>
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Actions --}}
                <div class="col-md-2 d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-success px-3" id="btnApplyFilters">
                        <i class="fas fa-filter me-1"></i> Apply
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnResetFilters">
                        <i class="fas fa-undo me-1"></i> Reset
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

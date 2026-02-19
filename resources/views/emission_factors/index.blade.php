@extends('layouts.app')

@section('title', 'Emission Factors')
@section('page-title', 'Emission Factors')

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="emission-factors-app container-fluid mt-4">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Topbar -->
        <div class="topbar">
            <h2><span class="sb"><i class="fas fa-calculator"></i></span> Emission Factors</h2>
            <p>Manage factors used for auto-calculation (tCO₂e per unit).</p>
            <button type="button" class="btn-add" id="addFactorBtn">
                <i class="fas fa-plus"></i> Add Factor
            </button>
        </div>

        <!-- DataTable Card -->
        <div class="card factors-datatable-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                <h5 class="mb-0">Emission Factors List</h5>
                <div class="input-group" style="width: 280px;">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" id="searchInput" class="form-control" placeholder="Search factors...">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="factorsTable">
                        <thead>
                            <tr>
                                <th width="50">ID</th>
                                <th>Emission Source</th>
                                <th>Organization</th>
                                <th>Country</th>
                                <th>Unit</th>
                                <th>Factor Value</th>
                                <th>Region</th>
                                <th width="150" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="table-info-text">
                        Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of <span id="totalCount">0</span> factors
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="factorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div class="icon-circle">
                    <i class="fas fa-calculator"></i>
                </div>
                <h5 class="modal-title" id="factorModalTitle">Add Emission Factor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="factorForm">
                    @csrf
                    <input type="hidden" name="id" id="factor_id">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Emission Source *</label>
                            <select class="form-select" name="emission_source_id" id="emission_source_id" required>
                                <option value="">Select source...</option>
                                @foreach($sources as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Organization *</label>
                            <select class="form-select" name="organization_id" id="organization_id" required onchange="onFactorOrganizationChange()">
                                <option value="">Select organization...</option>
                                @foreach($factorOrganizations ?? [] as $org)
                                    <option value="{{ $org->id }}">{{ $org->code }} – {{ $org->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">e.g. DEFRA, EPA, IPCC, or Country-specific</small>
                        </div>
                        <div class="col-md-6" id="factorCountryWrapper" style="display: none;">
                            <label class="form-label">Country *</label>
                            <select class="form-select" name="country_id" id="country_id">
                                <option value="">Select country...</option>
                                @foreach(($countries ?? collect()) as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->code }})</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Required when Organization is Country-specific.</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Unit *</label>
                            <input type="text" class="form-control" name="unit" id="unit" required maxlength="255" placeholder="e.g., kWh, liters, m³">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Factor Value *</label>
                            <input type="number" class="form-control" name="factor_value" id="factor_value" required step="0.000001" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Region</label>
                            <input type="text" class="form-control" name="region" id="region" maxlength="255" placeholder="optional (e.g., UAE, EU, default)">
                        </div>
                    </div>
                </form>

                <div class="alert alert-danger mt-3 d-none" id="factorError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="saveFactorBtn">Save</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        /* System modal look (shared) */
        .modal-content { border-radius: 16px; }
        .modal-header { gap: 10px; }
        .icon-circle {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%);
            color: #fff;
            box-shadow: 0 4px 12px rgba(46,125,50,.25);
            flex: 0 0 auto;
        }

        .emission-factors-app * { box-sizing: border-box; }
        .emission-factors-app { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .emission-factors-app .topbar { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; padding: 20px 24px; background: linear-gradient(135deg, #fff 0%, var(--gray-50) 100%); border: 1px solid var(--gray-200); border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        .emission-factors-app .topbar h2 { font-size: 1.35rem; font-weight: 700; letter-spacing: -0.02em; display: flex; align-items: center; gap: 10px; margin: 0; color: var(--gray-800); }
        .emission-factors-app .topbar h2 .sb { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%); color: #fff; font-size: 1rem; font-weight: 700; box-shadow: 0 2px 8px rgba(46,125,50,.25); }
        .emission-factors-app .topbar p { color: var(--gray-600); font-size: 0.875rem; flex: 1; min-width: 180px; margin: 0; line-height: 1.4; }
        .emission-factors-app .btn-add { margin-left: auto; padding: 10px 20px; border-radius: 10px; background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%); color: #fff; border: none; font-size: 0.875rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
        .emission-factors-app .btn-add:hover { background: linear-gradient(135deg, var(--dark-green) 0%, var(--primary-green) 100%); color: #fff; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(46,125,50,.35); }
        .emission-factors-app .alert { border-radius: 12px; border: 1px solid transparent; }
        .emission-factors-app .alert-success { background: rgba(76,175,80,0.1); border-color: rgba(76,175,50,0.25); color: var(--dark-green); }
        .emission-factors-app .alert-danger { background: rgba(211,47,47,0.08); border-color: rgba(211,47,47,0.2); color: var(--danger-red); }
        .emission-factors-app .factors-datatable-card { background: #fff; border: 1px solid var(--gray-200); border-radius: 16px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        .emission-factors-app .factors-datatable-card .card-header { padding: 16px 20px; border-bottom: 1px solid var(--gray-200); background: linear-gradient(180deg, var(--gray-50) 0%, #fff 100%); }
        .emission-factors-app .factors-datatable-card .card-header h5 { font-size: 1.0625rem; font-weight: 700; color: var(--gray-800); }
        .emission-factors-app .factors-datatable-card .card-header .input-group { border-radius: 10px; overflow: hidden; border: 1px solid var(--gray-200); }
        .emission-factors-app .factors-datatable-card .card-header .input-group-text { background: var(--gray-50); border: none; color: var(--gray-600); padding: 10px 14px; }
        .emission-factors-app .factors-datatable-card .card-header .form-control { border: none; padding: 10px 14px; font-size: 0.875rem; }
        .emission-factors-app .factors-datatable-card #factorsTable { width: 100% !important; border-collapse: separate; border-spacing: 0; }
        .emission-factors-app .factors-datatable-card #factorsTable thead th { background: var(--gray-100); color: var(--gray-600); font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; padding: 14px 16px; border: none; border-bottom: 1px solid var(--gray-200); }
        .emission-factors-app .factors-datatable-card #factorsTable thead th:first-child { padding-left: 20px; }
        .emission-factors-app .factors-datatable-card #factorsTable tbody td { padding: 14px 16px; font-size: 0.875rem; color: var(--gray-800); border: none; border-bottom: 1px solid var(--gray-100); vertical-align: middle; }
        .emission-factors-app .factors-datatable-card #factorsTable tbody td:first-child { padding-left: 20px; }
        .emission-factors-app .factors-datatable-card #factorsTable tbody tr:hover td { background: var(--gray-50); }
        .emission-factors-app .factors-datatable-card #factorsTable tbody tr:last-child td { border-bottom: none; }
        .emission-factors-app .factors-datatable-card .card-footer { padding: 12px 20px; border-top: 1px solid var(--gray-200); background: var(--gray-50); font-size: 0.8125rem; color: var(--gray-600); }
        .emission-factors-app .factors-datatable-card .dataTables_wrapper { padding: 0; }
        .emission-factors-app .factors-datatable-card .dataTables_wrapper .dataTables_length,
        .emission-factors-app .factors-datatable-card .dataTables_wrapper .dataTables_filter { display: none; }
        .emission-factors-app .factors-datatable-card .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 6px 12px; margin: 0 2px; border-radius: 8px; border: 1px solid var(--gray-200); background: #fff; color: var(--gray-700) !important; font-size: 0.8125rem; font-weight: 600; }
        .emission-factors-app .factors-datatable-card .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: var(--gray-100) !important; border-color: var(--gray-300) !important; color: var(--gray-800) !important; }
        .emission-factors-app .factors-datatable-card .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: var(--primary-green) !important; border-color: var(--primary-green) !important; color: #fff !important; }
        .emission-factors-app .factors-datatable-card .dataTables_wrapper .dataTables_processing { background: rgba(255,255,255,0.95); border-radius: 10px; padding: 14px 24px; font-weight: 600; font-size: 0.875rem; color: var(--gray-700); border: 1px solid var(--gray-200); box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        .emission-factors-app .factors-datatable-card .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    </style>
@endpush

@push('scripts')
<script>
const countrySpecificOrgId = {{ json_encode($countrySpecificOrgId ?? null) }};

function onFactorOrganizationChange() {
    const orgEl = document.getElementById('organization_id');
    const wrapper = document.getElementById('factorCountryWrapper');
    const countryEl = document.getElementById('country_id');
    const selectedId = orgEl ? orgEl.value : '';
    const isCountrySpecific = countrySpecificOrgId && String(selectedId) === String(countrySpecificOrgId);

    if (wrapper) wrapper.style.display = isCountrySpecific ? 'block' : 'none';
    if (countryEl) {
        if (isCountrySpecific) {
            countryEl.setAttribute('required', 'required');
        } else {
            countryEl.removeAttribute('required');
            countryEl.value = '';
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const table = $('#factorsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('emission_factors.data') }}",
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        dom: 'rt<"row mt-3"<"col-sm-12"p>>',
        drawCallback: function () {
            var api = this.api();
            var info = api.page.info();
            $('#showingFrom').text(info.recordsDisplay ? info.start + 1 : 0);
            $('#showingTo').text(info.end);
            $('#totalCount').text(info.recordsDisplay);
        },
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            emptyTable: 'No factors found',
            zeroRecords: 'No matching factors found',
            paginate: { first: '<i class="fas fa-angle-double-left"></i>', previous: '<i class="fas fa-angle-left"></i>', next: '<i class="fas fa-angle-right"></i>', last: '<i class="fas fa-angle-double-right"></i>' }
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'source_name', name: 'source_name' },
            { data: 'organization_name', name: 'organization_name', defaultContent: '—', orderable: false, searchable: false },
            { data: 'country_name', name: 'country_name', defaultContent: '' },
            { data: 'unit', name: 'unit' },
            { data: 'factor_value', name: 'factor_value' },
            { data: 'region', name: 'region', defaultContent: '' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false },
        ]
    });

    $('#searchInput').on('keyup', function () {
        table.search(this.value).draw();
    });

    const modalEl = document.getElementById('factorModal');
    const modal = new bootstrap.Modal(modalEl);

    function resetForm() {
        document.getElementById('factorForm').reset();
        document.getElementById('factor_id').value = '';
        document.getElementById('factorError').classList.add('d-none');
        document.getElementById('factorError').textContent = '';
        onFactorOrganizationChange();
    }

    document.getElementById('addFactorBtn').addEventListener('click', () => {
        resetForm();
        document.getElementById('factorModalTitle').textContent = 'Add Emission Factor';
        modal.show();
    });

    $('#factorsTable').on('click', '.editBtn, .viewBtn', function () {
        resetForm();
        const id = this.getAttribute('data-id');
        fetch(`{{ url('emission-factors') }}/${id}`, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                document.getElementById('factorModalTitle').textContent = this.classList.contains('viewBtn') ? 'View Emission Factor' : 'Edit Emission Factor';
                document.getElementById('factor_id').value = data.id;
                document.getElementById('emission_source_id').value = data.emission_source_id || '';
                document.getElementById('organization_id').value = data.organization_id || '';
                document.getElementById('country_id').value = data.country_id || '';
                document.getElementById('unit').value = data.unit || '';
                document.getElementById('factor_value').value = data.factor_value || '';
                document.getElementById('region').value = data.region || '';

                onFactorOrganizationChange();

                const isView = this.classList.contains('viewBtn');
                ['emission_source_id','organization_id','country_id','unit','factor_value','region'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.disabled = isView;
                });
                document.getElementById('saveFactorBtn').style.display = isView ? 'none' : 'inline-block';

                modal.show();
            });
    });

    $('#factorsTable').on('click', '.deleteBtn', function () {
        const id = this.getAttribute('data-id');
        if (!confirm('Delete this emission factor?')) return;
        fetch(`{{ url('emission-factors') }}/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(r => r.json())
        .then(() => table.ajax.reload(null, false));
    });

    document.getElementById('saveFactorBtn').addEventListener('click', () => {
        const form = document.getElementById('factorForm');
        const formData = new FormData(form);
        fetch("{{ route('emission_factors.storeOrUpdate') }}", {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: formData
        })
        .then(async r => {
            const data = await r.json();
            if (!r.ok) throw data;
            return data;
        })
        .then(() => {
            modal.hide();
            table.ajax.reload(null, false);
        })
        .catch(err => {
            const box = document.getElementById('factorError');
            box.classList.remove('d-none');
            box.textContent = err?.message || 'Validation error. Please check inputs.';
        });
    });

    modalEl.addEventListener('hidden.bs.modal', () => {
        ['emission_source_id','organization_id','country_id','unit','factor_value','region'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.disabled = false;
        });
        document.getElementById('saveFactorBtn').style.display = 'inline-block';
    });
});
</script>
@endpush


@extends('layouts.app')

@section('title', 'Energy Certificates')
@section('page-title', 'Energy Certificates')

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="emission-factors-app container-fluid mt-4">
        <!-- Topbar -->
        <div class="topbar">
            <h2><span class="sb"><i class="fas fa-certificate"></i></span> Energy Attribute Certificates</h2>
            <p>Market-based Scope 2 instruments — RECs, GOs, I-RECs, PPAs and supplier/residual-mix factors used for dual reporting.</p>
            <button type="button" class="btn-add" id="addCertBtn">
                <i class="fas fa-plus"></i> Add Certificate
            </button>
        </div>

        <div class="alert alert-info" style="border-radius:12px;">
            <i class="fas fa-info-circle me-2"></i>
            Under the GHG Protocol Scope 2 Guidance, purchased energy is reported two ways: <strong>location-based</strong>
            (grid-average factor) and <strong>market-based</strong> (the contractual instruments below). A certificate's
            emission factor is applied to matched Scope 2 consumption to produce the market-based figure (renewables backed by a valid certificate = 0 kgCO₂e/kWh).
        </div>

        <!-- DataTable Card -->
        <div class="card factors-datatable-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                <h5 class="mb-0">Certificates &amp; Contracts</h5>
                <div class="input-group" style="width: 280px;">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" id="searchInput" class="form-control" placeholder="Search certificates...">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="certTable">
                        <thead>
                            <tr>
                                <th width="50">ID</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Carrier</th>
                                <th>Volume</th>
                                <th>Market Factor</th>
                                <th>Validity</th>
                                <th>Status</th>
                                <th width="140" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <div class="table-info-text">
                    Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of <span id="totalCount">0</span> certificates
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="certModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div class="icon-circle"><i class="fas fa-certificate"></i></div>
                <h5 class="modal-title" id="certModalTitle">Add Certificate</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="certForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="id" id="cert_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name / Reference *</label>
                            <input type="text" class="form-control" name="name" id="name" required maxlength="255" placeholder="e.g., 2025 I-REC batch – Plant A">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Instrument Type *</label>
                            <select class="form-select" name="type" id="type" required>
                                @foreach($types as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Energy Carrier *</label>
                            <select class="form-select" name="energy_carrier" id="energy_carrier" required>
                                @foreach($carriers as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Market Factor (kgCO₂e/kWh) *</label>
                            <input type="number" class="form-control" name="emission_factor" id="emission_factor" required step="0.000001" min="0" value="0">
                            <small class="text-muted">0 for renewables backed by a valid certificate.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Contracted Volume (MWh)</label>
                            <input type="number" class="form-control" name="mwh_volume" id="mwh_volume" step="0.0001" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Certificate / Contract No.</label>
                            <input type="text" class="form-control" name="certificate_number" id="certificate_number" maxlength="255">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Supplier / Issuer</label>
                            <input type="text" class="form-control" name="supplier_name" id="supplier_name" maxlength="255">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Region / Market</label>
                            <input type="text" class="form-control" name="region" id="region" maxlength="255" placeholder="e.g., UAE, EU">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Vintage Year</label>
                            <input type="number" class="form-control" name="vintage_year" id="vintage_year" min="1990" max="2100">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Valid From</label>
                            <input type="date" class="form-control" name="valid_from" id="valid_from">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Valid To</label>
                            <input type="date" class="form-control" name="valid_to" id="valid_to">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status *</label>
                            <select class="form-select" name="status" id="status" required>
                                <option value="active">Active</option>
                                <option value="retired">Retired</option>
                                <option value="expired">Expired</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Supporting Document</label>
                            <input type="file" class="form-control" name="document" id="document" accept=".pdf,.jpg,.jpeg,.png,.webp,.xlsx,.xls,.csv">
                            <small class="text-muted" id="existingDoc"></small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" id="notes" rows="2" maxlength="1000"></textarea>
                        </div>
                    </div>
                </form>
                <div class="alert alert-danger mt-3 d-none" id="certError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="saveCertBtn">Save</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        .modal-content { border-radius: 16px; }
        .modal-header { gap: 10px; }
        .icon-circle { width: 42px; height: 42px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%); color: #fff; box-shadow: 0 4px 12px rgba(46,125,50,.25); flex: 0 0 auto; }
        .emission-factors-app * { box-sizing: border-box; }
        .emission-factors-app { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .emission-factors-app .topbar { display: flex; align-items: center; gap: 16px; margin-bottom: 16px; flex-wrap: wrap; padding: 20px 24px; background: linear-gradient(135deg, #fff 0%, var(--gray-50) 100%); border: 1px solid var(--gray-200); border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        .emission-factors-app .topbar h2 { font-size: 1.35rem; font-weight: 700; letter-spacing: -0.02em; display: flex; align-items: center; gap: 10px; margin: 0; color: var(--gray-800); }
        .emission-factors-app .topbar h2 .sb { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%); color: #fff; font-size: 1rem; font-weight: 700; box-shadow: 0 2px 8px rgba(46,125,50,.25); }
        .emission-factors-app .topbar p { color: var(--gray-600); font-size: 0.875rem; flex: 1; min-width: 180px; margin: 0; line-height: 1.4; }
        .emission-factors-app .btn-add { margin-left: auto; padding: 10px 20px; border-radius: 10px; background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%); color: #fff; border: none; font-size: 0.875rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
        .emission-factors-app .factors-datatable-card { background: #fff; border: 1px solid var(--gray-200); border-radius: 16px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        .emission-factors-app .factors-datatable-card .card-header { padding: 16px 20px; border-bottom: 1px solid var(--gray-200); background: linear-gradient(180deg, var(--gray-50) 0%, #fff 100%); }
        .emission-factors-app .factors-datatable-card .card-header h5 { font-size: 1.0625rem; font-weight: 700; color: var(--gray-800); }
        .emission-factors-app .factors-datatable-card .card-header .input-group { border-radius: 10px; overflow: hidden; border: 1px solid var(--gray-200); }
        .emission-factors-app .factors-datatable-card .card-header .input-group-text { background: var(--gray-50); border: none; color: var(--gray-600); padding: 10px 14px; }
        .emission-factors-app .factors-datatable-card .card-header .form-control { border: none; padding: 10px 14px; font-size: 0.875rem; }
        .emission-factors-app .factors-datatable-card #certTable { width: 100% !important; }
        .emission-factors-app .factors-datatable-card #certTable thead th { background: var(--gray-100); color: var(--gray-600); font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; padding: 14px 16px; border: none; border-bottom: 1px solid var(--gray-200); }
        .emission-factors-app .factors-datatable-card #certTable tbody td { padding: 14px 16px; font-size: 0.875rem; color: var(--gray-800); border: none; border-bottom: 1px solid var(--gray-100); vertical-align: middle; }
        .emission-factors-app .factors-datatable-card #certTable tbody tr:hover td { background: var(--gray-50); }
        .emission-factors-app .factors-datatable-card .card-footer { padding: 12px 20px; border-top: 1px solid var(--gray-200); background: var(--gray-50); font-size: 0.8125rem; color: var(--gray-600); }
        .emission-factors-app .factors-datatable-card .dataTables_wrapper .dataTables_length, .emission-factors-app .factors-datatable-card .dataTables_wrapper .dataTables_filter { display: none; }
        .emission-factors-app .factors-datatable-card .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 6px 12px; margin: 0 2px; border-radius: 8px; border: 1px solid var(--gray-200); background: #fff; color: var(--gray-700) !important; font-size: 0.8125rem; font-weight: 600; }
        .emission-factors-app .factors-datatable-card .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: var(--primary-green) !important; border-color: var(--primary-green) !important; color: #fff !important; }
    </style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const fields = ['name','type','energy_carrier','emission_factor','mwh_volume','certificate_number','supplier_name','region','vintage_year','valid_from','valid_to','status','notes'];

    const table = $('#certTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('energy_certificates.data') }}",
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        order: [[0, 'desc']],
        dom: 'rt<"row mt-3"<"col-sm-12"p>>',
        drawCallback: function () {
            var info = this.api().page.info();
            $('#showingFrom').text(info.recordsDisplay ? info.start + 1 : 0);
            $('#showingTo').text(info.end);
            $('#totalCount').text(info.recordsDisplay);
        },
        language: {
            processing: '<div class="spinner-border text-primary" role="status"></div>',
            emptyTable: 'No certificates yet — add your first market-based instrument.',
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'name', name: 'name' },
            { data: 'type_label', name: 'type', orderable: false },
            { data: 'energy_carrier', name: 'energy_carrier' },
            { data: 'volume_formatted', name: 'mwh_volume' },
            { data: 'factor_formatted', name: 'emission_factor' },
            { data: 'validity', name: 'valid_from', orderable: false, searchable: false },
            { data: 'status_badge', name: 'status' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false },
        ]
    });

    $('#searchInput').on('keyup', function () { table.search(this.value).draw(); });

    const modalEl = document.getElementById('certModal');
    const modal = new bootstrap.Modal(modalEl);

    function resetForm() {
        document.getElementById('certForm').reset();
        document.getElementById('cert_id').value = '';
        document.getElementById('existingDoc').textContent = '';
        const box = document.getElementById('certError');
        box.classList.add('d-none'); box.textContent = '';
    }

    document.getElementById('addCertBtn').addEventListener('click', () => {
        resetForm();
        document.getElementById('certModalTitle').textContent = 'Add Certificate';
        document.getElementById('saveCertBtn').style.display = 'inline-block';
        modal.show();
    });

    $('#certTable').on('click', '.editBtn, .viewBtn', function () {
        resetForm();
        const id = this.getAttribute('data-id');
        const isView = this.classList.contains('viewBtn');
        fetch(`{{ url('energy-certificates') }}/${id}`, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                document.getElementById('certModalTitle').textContent = isView ? 'View Certificate' : 'Edit Certificate';
                document.getElementById('cert_id').value = data.id;
                fields.forEach(f => { const el = document.getElementById(f); if (el) el.value = data[f] ?? ''; });
                if (data.document_path) {
                    document.getElementById('existingDoc').innerHTML = '<i class="fas fa-paperclip"></i> Current: ' + data.document_path.split('/').pop();
                }
                fields.concat(['document']).forEach(f => { const el = document.getElementById(f); if (el) el.disabled = isView; });
                document.getElementById('saveCertBtn').style.display = isView ? 'none' : 'inline-block';
                modal.show();
            });
    });

    $('#certTable').on('click', '.deleteBtn', function () {
        const id = this.getAttribute('data-id');
        if (!confirm('Delete this certificate?')) return;
        fetch(`{{ url('energy-certificates') }}/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Accept': 'application/json' }
        }).then(r => r.json()).then(() => table.ajax.reload(null, false));
    });

    document.getElementById('saveCertBtn').addEventListener('click', () => {
        const formData = new FormData(document.getElementById('certForm'));
        fetch("{{ route('energy_certificates.storeOrUpdate') }}", {
            method: 'POST', headers: { 'Accept': 'application/json' }, body: formData
        })
        .then(async r => { const data = await r.json(); if (!r.ok) throw data; return data; })
        .then(() => { modal.hide(); table.ajax.reload(null, false); })
        .catch(err => {
            const box = document.getElementById('certError');
            box.classList.remove('d-none');
            let msg = err?.message || 'Validation error. Please check inputs.';
            if (err?.errors) msg = Object.values(err.errors).flat().join(' ');
            box.textContent = msg;
        });
    });

    modalEl.addEventListener('hidden.bs.modal', () => {
        fields.concat(['document']).forEach(f => { const el = document.getElementById(f); if (el) el.disabled = false; });
        document.getElementById('saveCertBtn').style.display = 'inline-block';
    });
});
</script>
@endpush

@extends('layouts.app')

@section('title', 'Countries')
@section('page-title', 'Countries')

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="countries-app container-fluid mt-4">
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
            <h2><span class="sb"><i class="fas fa-flag"></i></span> Countries</h2>
            <p>Manage the list used for country-specific emission factors.</p>
            <button type="button" class="btn-add" id="addCountryBtn">
                <i class="fas fa-plus"></i> Add Country
            </button>
        </div>

        <!-- DataTable Card -->
        <div class="card countries-datatable-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                <h5 class="mb-0">Countries List</h5>
                <div class="input-group" style="width: 280px;">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" id="searchInput" class="form-control" placeholder="Search countries...">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="countriesTable">
                        <thead>
                            <tr>
                                <th width="50">ID</th>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Active</th>
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
                        Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of <span id="totalCount">0</span> countries
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="countryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div class="icon-circle">
                    <i class="fas fa-flag"></i>
                </div>
                <h5 class="modal-title" id="countryModalTitle">Add Country</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="countryForm">
                    @csrf
                    <input type="hidden" name="id" id="country_id_hidden">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Code *</label>
                            <input type="text" class="form-control" name="code" id="country_code" required maxlength="10" placeholder="e.g. UAE">
                            <small class="text-muted">Short code used in factors.</small>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Name *</label>
                            <input type="text" class="form-control" name="name" id="country_name" required maxlength="255" placeholder="e.g. United Arab Emirates">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="is_active" id="country_active">
                                <option value="1" selected>Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </form>

                <div class="alert alert-danger mt-3 d-none" id="countryError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="saveCountryBtn">Save</button>
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

        .countries-app * { box-sizing: border-box; }
        .countries-app { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .countries-app .topbar { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; padding: 20px 24px; background: linear-gradient(135deg, #fff 0%, var(--gray-50) 100%); border: 1px solid var(--gray-200); border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        .countries-app .topbar h2 { font-size: 1.35rem; font-weight: 700; letter-spacing: -0.02em; display: flex; align-items: center; gap: 10px; margin: 0; color: var(--gray-800); }
        .countries-app .topbar h2 .sb { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%); color: #fff; font-size: 1rem; font-weight: 700; box-shadow: 0 2px 8px rgba(46,125,50,.25); }
        .countries-app .topbar p { color: var(--gray-600); font-size: 0.875rem; flex: 1; min-width: 180px; margin: 0; line-height: 1.4; }
        .countries-app .btn-add { margin-left: auto; padding: 10px 20px; border-radius: 10px; background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%); color: #fff; border: none; font-size: 0.875rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
        .countries-app .btn-add:hover { background: linear-gradient(135deg, var(--dark-green) 0%, var(--primary-green) 100%); color: #fff; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(46,125,50,.35); }
        .countries-app .alert { border-radius: 12px; border: 1px solid transparent; }
        .countries-app .alert-success { background: rgba(76,175,80,0.1); border-color: rgba(76,175,50,0.25); color: var(--dark-green); }
        .countries-app .alert-danger { background: rgba(211,47,47,0.08); border-color: rgba(211,47,47,0.2); color: var(--danger-red); }
        .countries-app .countries-datatable-card { background: #fff; border: 1px solid var(--gray-200); border-radius: 16px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        .countries-app .countries-datatable-card .card-header { padding: 16px 20px; border-bottom: 1px solid var(--gray-200); background: linear-gradient(180deg, var(--gray-50) 0%, #fff 100%); }
        .countries-app .countries-datatable-card .card-header h5 { font-size: 1.0625rem; font-weight: 700; color: var(--gray-800); }
        .countries-app .countries-datatable-card .card-header .input-group { border-radius: 10px; overflow: hidden; border: 1px solid var(--gray-200); }
        .countries-app .countries-datatable-card .card-header .input-group-text { background: var(--gray-50); border: none; color: var(--gray-600); padding: 10px 14px; }
        .countries-app .countries-datatable-card .card-header .form-control { border: none; padding: 10px 14px; font-size: 0.875rem; }
        .countries-app .countries-datatable-card #countriesTable { width: 100% !important; border-collapse: separate; border-spacing: 0; }
        .countries-app .countries-datatable-card #countriesTable thead th { background: var(--gray-100); color: var(--gray-600); font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; padding: 14px 16px; border: none; border-bottom: 1px solid var(--gray-200); }
        .countries-app .countries-datatable-card #countriesTable thead th:first-child { padding-left: 20px; }
        .countries-app .countries-datatable-card #countriesTable tbody td { padding: 14px 16px; font-size: 0.875rem; color: var(--gray-800); border: none; border-bottom: 1px solid var(--gray-100); vertical-align: middle; }
        .countries-app .countries-datatable-card #countriesTable tbody td:first-child { padding-left: 20px; }
        .countries-app .countries-datatable-card #countriesTable tbody tr:hover td { background: var(--gray-50); }
        .countries-app .countries-datatable-card #countriesTable tbody tr:last-child td { border-bottom: none; }
        .countries-app .countries-datatable-card .card-footer { padding: 12px 20px; border-top: 1px solid var(--gray-200); background: var(--gray-50); font-size: 0.8125rem; color: var(--gray-600); }
        .countries-app .countries-datatable-card .dataTables_wrapper { padding: 0; }
        .countries-app .countries-datatable-card .dataTables_wrapper .dataTables_length,
        .countries-app .countries-datatable-card .dataTables_wrapper .dataTables_filter { display: none; }
        .countries-app .countries-datatable-card .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 6px 12px; margin: 0 2px; border-radius: 8px; border: 1px solid var(--gray-200); background: #fff; color: var(--gray-700) !important; font-size: 0.8125rem; font-weight: 600; }
        .countries-app .countries-datatable-card .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: var(--gray-100) !important; border-color: var(--gray-300) !important; color: var(--gray-800) !important; }
        .countries-app .countries-datatable-card .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: var(--primary-green) !important; border-color: var(--primary-green) !important; color: #fff !important; }
        .countries-app .countries-datatable-card .dataTables_wrapper .dataTables_processing { background: rgba(255,255,255,0.95); border-radius: 10px; padding: 14px 24px; font-weight: 600; font-size: 0.875rem; color: var(--gray-700); border: 1px solid var(--gray-200); box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        .countries-app .countries-datatable-card .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    </style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const table = $('#countriesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('countries.data') }}",
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
            emptyTable: 'No countries found',
            zeroRecords: 'No matching countries found',
            paginate: {
                first: '<i class="fas fa-angle-double-left"></i>',
                previous: '<i class="fas fa-angle-left"></i>',
                next: '<i class="fas fa-angle-right"></i>',
                last: '<i class="fas fa-angle-double-right"></i>'
            }
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'code', name: 'code' },
            { data: 'name', name: 'name' },
            { data: 'is_active', name: 'is_active', render: (v) => v ? 'Yes' : 'No' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false },
        ]
    });

    $('#searchInput').on('keyup', function () {
        table.search(this.value).draw();
    });

    const modalEl = document.getElementById('countryModal');
    const modal = new bootstrap.Modal(modalEl);

    function resetForm() {
        document.getElementById('countryForm').reset();
        document.getElementById('country_id_hidden').value = '';
        document.getElementById('countryError').classList.add('d-none');
        document.getElementById('countryError').textContent = '';
        document.getElementById('country_active').value = '1';
    }

    document.getElementById('addCountryBtn').addEventListener('click', () => {
        resetForm();
        document.getElementById('countryModalTitle').textContent = 'Add Country';
        modal.show();
    });

    $('#countriesTable').on('click', '.editBtn, .viewBtn', function () {
        resetForm();
        const id = this.getAttribute('data-id');
        fetch(`{{ url('countries') }}/${id}`, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                document.getElementById('countryModalTitle').textContent = this.classList.contains('viewBtn') ? 'View Country' : 'Edit Country';
                document.getElementById('country_id_hidden').value = data.id;
                document.getElementById('country_code').value = data.code || '';
                document.getElementById('country_name').value = data.name || '';
                document.getElementById('country_active').value = data.is_active ? '1' : '0';

                const isView = this.classList.contains('viewBtn');
                ['country_code','country_name','country_active'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.disabled = isView;
                });
                document.getElementById('saveCountryBtn').style.display = isView ? 'none' : 'inline-block';

                modal.show();
            });
    });

    $('#countriesTable').on('click', '.deleteBtn', function () {
        const id = this.getAttribute('data-id');
        if (!confirm('Delete this country?')) return;
        fetch(`{{ url('countries') }}/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(r => r.json())
        .then(() => table.ajax.reload(null, false));
    });

    document.getElementById('saveCountryBtn').addEventListener('click', () => {
        const form = document.getElementById('countryForm');
        const formData = new FormData(form);
        fetch("{{ route('countries.storeOrUpdate') }}", {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: formData
        })
        .then(async r => {
            const data = await r.json().catch(() => ({}));
            if (!r.ok) throw new Error(data.message || 'Validation error');
            return data;
        })
        .then(() => {
            modal.hide();
            table.ajax.reload(null, false);
        })
        .catch(err => {
            const el = document.getElementById('countryError');
            el.textContent = err.message;
            el.classList.remove('d-none');
        });
    });
});
</script>
@endpush


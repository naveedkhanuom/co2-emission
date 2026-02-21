@extends('layouts.app')

@section('title', 'Emission Sources')
@section('page-title', 'Emission Sources')

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="emission-sources-app container-fluid mt-4">
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
            <h2><span class="sb"><i class="fas fa-smog"></i></span> Emission Sources</h2>
            <p>Manage Scope 1/2/3 emission source list used in data entry.</p>
            <button type="button" class="btn-add" id="addBtn">
                <i class="fas fa-plus"></i> Add Source
            </button>
        </div>

        <!-- DataTable Card -->
        <div class="card sources-datatable-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                <h5 class="mb-0">Emission Sources List</h5>
                <div class="input-group" style="width: 280px;">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" id="searchInput" class="form-control" placeholder="Search sources...">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="sourcesTable">
                        <thead>
                            <tr>
                                <th width="50">ID</th>
                                <th>Name</th>
                                <th>Scope</th>
                                <th>Description</th>
                                <th>Created</th>
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
                        Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of <span id="totalCount">0</span> sources
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="sourceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div class="icon-circle">
                    <i class="fas fa-smog"></i>
                </div>
                <h5 class="modal-title" id="sourceModalTitle">Add Emission Source</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="sourceForm">
                    @csrf
                    <input type="hidden" name="emission_source_id" id="emission_source_id">

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Name *</label>
                            <input type="text" class="form-control" name="name" id="name" required maxlength="255">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Scope</label>
                            <select class="form-select" name="scope" id="scope">
                                <option value="">General</option>
                                <option value="1">Scope 1</option>
                                <option value="2">Scope 2</option>
                                <option value="3">Scope 3</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="description" rows="3"></textarea>
                        </div>
                    </div>
                </form>

                <div class="alert alert-danger mt-3 d-none" id="sourceError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="saveSourceBtn">Save</button>
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

        .emission-sources-app * { box-sizing: border-box; }
        .emission-sources-app { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .emission-sources-app .topbar { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; padding: 20px 24px; background: linear-gradient(135deg, #fff 0%, var(--gray-50) 100%); border: 1px solid var(--gray-200); border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        .emission-sources-app .topbar h2 { font-size: 1.35rem; font-weight: 700; letter-spacing: -0.02em; display: flex; align-items: center; gap: 10px; margin: 0; color: var(--gray-800); }
        .emission-sources-app .topbar h2 .sb { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%); color: #fff; font-size: 1rem; font-weight: 700; box-shadow: 0 2px 8px rgba(46,125,50,.25); }
        .emission-sources-app .topbar p { color: var(--gray-600); font-size: 0.875rem; flex: 1; min-width: 180px; margin: 0; line-height: 1.4; }
        .emission-sources-app .btn-add { margin-left: auto; padding: 10px 20px; border-radius: 10px; background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%); color: #fff; border: none; font-size: 0.875rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
        .emission-sources-app .btn-add:hover { background: linear-gradient(135deg, var(--dark-green) 0%, var(--primary-green) 100%); color: #fff; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(46,125,50,.35); }
        .emission-sources-app .alert { border-radius: 12px; border: 1px solid transparent; }
        .emission-sources-app .alert-success { background: rgba(76,175,80,0.1); border-color: rgba(76,175,50,0.25); color: var(--dark-green); }
        .emission-sources-app .alert-danger { background: rgba(211,47,47,0.08); border-color: rgba(211,47,47,0.2); color: var(--danger-red); }
        .emission-sources-app .sources-datatable-card { background: #fff; border: 1px solid var(--gray-200); border-radius: 16px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        .emission-sources-app .sources-datatable-card .card-header { padding: 16px 20px; border-bottom: 1px solid var(--gray-200); background: linear-gradient(180deg, var(--gray-50) 0%, #fff 100%); }
        .emission-sources-app .sources-datatable-card .card-header h5 { font-size: 1.0625rem; font-weight: 700; color: var(--gray-800); }
        .emission-sources-app .sources-datatable-card .card-header .input-group { border-radius: 10px; overflow: hidden; border: 1px solid var(--gray-200); }
        .emission-sources-app .sources-datatable-card .card-header .input-group-text { background: var(--gray-50); border: none; color: var(--gray-600); padding: 10px 14px; }
        .emission-sources-app .sources-datatable-card .card-header .form-control { border: none; padding: 10px 14px; font-size: 0.875rem; }
        .emission-sources-app .sources-datatable-card #sourcesTable { width: 100% !important; border-collapse: separate; border-spacing: 0; }
        .emission-sources-app .sources-datatable-card #sourcesTable thead th { background: var(--gray-100); color: var(--gray-600); font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; padding: 14px 16px; border: none; border-bottom: 1px solid var(--gray-200); }
        .emission-sources-app .sources-datatable-card #sourcesTable thead th:first-child { padding-left: 20px; }
        .emission-sources-app .sources-datatable-card #sourcesTable tbody td { padding: 14px 16px; font-size: 0.875rem; color: var(--gray-800); border: none; border-bottom: 1px solid var(--gray-100); vertical-align: middle; }
        .emission-sources-app .sources-datatable-card #sourcesTable tbody td:first-child { padding-left: 20px; }
        .emission-sources-app .sources-datatable-card #sourcesTable tbody tr:hover td { background: var(--gray-50); }
        .emission-sources-app .sources-datatable-card #sourcesTable tbody tr:last-child td { border-bottom: none; }
        .emission-sources-app .sources-datatable-card .card-footer { padding: 12px 20px; border-top: 1px solid var(--gray-200); background: var(--gray-50); font-size: 0.8125rem; color: var(--gray-600); }
        .emission-sources-app .sources-datatable-card .dataTables_wrapper { padding: 0; }
        .emission-sources-app .sources-datatable-card .dataTables_wrapper .dataTables_length,
        .emission-sources-app .sources-datatable-card .dataTables_wrapper .dataTables_filter { display: none; }
        .emission-sources-app .sources-datatable-card .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 6px 12px; margin: 0 2px; border-radius: 8px; border: 1px solid var(--gray-200); background: #fff; color: var(--gray-700) !important; font-size: 0.8125rem; font-weight: 600; }
        .emission-sources-app .sources-datatable-card .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: var(--gray-100) !important; border-color: var(--gray-300) !important; color: var(--gray-800) !important; }
        .emission-sources-app .sources-datatable-card .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: var(--primary-green) !important; border-color: var(--primary-green) !important; color: #fff !important; }
        .emission-sources-app .sources-datatable-card .dataTables_wrapper .dataTables_processing { background: rgba(255,255,255,0.95); border-radius: 10px; padding: 14px 24px; font-weight: 600; font-size: 0.875rem; color: var(--gray-700); border: 1px solid var(--gray-200); box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        .emission-sources-app .sources-datatable-card .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    </style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const table = $('#sourcesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('emission_sources.data') }}",
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
            emptyTable: 'No sources found',
            zeroRecords: 'No matching sources found',
            paginate: { first: '<i class="fas fa-angle-double-left"></i>', previous: '<i class="fas fa-angle-left"></i>', next: '<i class="fas fa-angle-right"></i>', last: '<i class="fas fa-angle-double-right"></i>' }
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'name', name: 'name' },
            { data: 'scope', name: 'scope', render: (v) => v ? `Scope ${v}` : 'General' },
            { data: 'description', name: 'description', defaultContent: '' },
            { data: 'created_at', name: 'created_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ]
    });

    $('#searchInput').on('keyup', function () {
        table.search(this.value).draw();
    });

    const modalEl = document.getElementById('sourceModal');
    const modal = new bootstrap.Modal(modalEl);

    function resetForm() {
        document.getElementById('sourceForm').reset();
        document.getElementById('emission_source_id').value = '';
        document.getElementById('sourceError').classList.add('d-none');
        document.getElementById('sourceError').textContent = '';
    }

    document.getElementById('addBtn').addEventListener('click', () => {
        resetForm();
        document.getElementById('sourceModalTitle').textContent = 'Add Emission Source';
        modal.show();
    });

    $('#sourcesTable').on('click', '.editBtn, .viewBtn', function () {
        resetForm();
        const id = this.getAttribute('data-id');
        fetch(`{{ url('emission-sources') }}/${id}`, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                document.getElementById('sourceModalTitle').textContent = this.classList.contains('viewBtn') ? 'View Emission Source' : 'Edit Emission Source';
                document.getElementById('emission_source_id').value = data.id;
                document.getElementById('name').value = data.name || '';
                document.getElementById('scope').value = data.scope || '';
                document.getElementById('description').value = data.description || '';

                const isView = this.classList.contains('viewBtn');
                ['name','scope','description'].forEach(id => {
                    document.getElementById(id).disabled = isView;
                });
                document.getElementById('saveSourceBtn').style.display = isView ? 'none' : 'inline-block';

                modal.show();
            });
    });

    $('#sourcesTable').on('click', '.deleteBtn', function () {
        const id = this.getAttribute('data-id');
        if (!confirm('Delete this emission source?')) return;
        fetch(`{{ url('emission-sources') }}/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(r => r.json())
        .then(() => table.ajax.reload(null, false));
    });

    document.getElementById('saveSourceBtn').addEventListener('click', () => {
        const form = document.getElementById('sourceForm');
        const formData = new FormData(form);

        fetch("{{ route('emission_sources.storeOrUpdate') }}", {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: formData
        })
        .then(async r => {
            const data = await r.json();
            if (!r.ok) {
                throw data;
            }
            return data;
        })
        .then(() => {
            modal.hide();
            table.ajax.reload(null, false);
        })
        .catch(err => {
            const box = document.getElementById('sourceError');
            box.classList.remove('d-none');
            box.textContent = err?.message || 'Validation error. Please check inputs.';
        });
    });

    modalEl.addEventListener('hidden.bs.modal', () => {
        ['name','scope','description'].forEach(id => document.getElementById(id).disabled = false);
        document.getElementById('saveSourceBtn').style.display = 'inline-block';
    });
});
</script>
@endpush


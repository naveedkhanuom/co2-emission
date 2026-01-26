@extends('layouts.app')

@section('title', 'Emission Sources')
@section('page-title', 'Emission Sources')

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="container-fluid mt-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">Emission Sources</h4>
                <div class="text-muted small">Manage Scope 1/2/3 emission source list used in data entry.</div>
            </div>
            <button class="btn btn-success" id="addBtn">
                <i class="fas fa-plus me-2"></i>Add Source
            </button>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle w-100" id="sourcesTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Scope</th>
                                <th>Description</th>
                                <th>Created</th>
                                <th style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="sourceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const table = $('#sourcesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('emission_sources.data') }}",
        columns: [
            { data: 'id', name: 'id' },
            { data: 'name', name: 'name' },
            { data: 'scope', name: 'scope', render: (v) => v ? `Scope ${v}` : 'General' },
            { data: 'description', name: 'description', defaultContent: '' },
            { data: 'created_at', name: 'created_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ]
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


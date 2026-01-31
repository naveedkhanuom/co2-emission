@extends('layouts.app')

@section('title', 'Countries')
@section('page-title', 'Countries')

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="container-fluid mt-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">Countries</h4>
                <div class="text-muted small">Manage the list used for country-specific emission factors.</div>
            </div>
            <button class="btn btn-success" id="addCountryBtn">
                <i class="fas fa-plus me-2"></i>Add Country
            </button>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle w-100" id="countriesTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Active</th>
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
<div class="modal fade" id="countryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const table = $('#countriesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('countries.data') }}",
        columns: [
            { data: 'id', name: 'id' },
            { data: 'code', name: 'code' },
            { data: 'name', name: 'name' },
            { data: 'is_active', name: 'is_active', render: (v) => v ? 'Yes' : 'No' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false },
        ]
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


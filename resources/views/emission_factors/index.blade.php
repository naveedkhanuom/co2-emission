@extends('layouts.app')

@section('title', 'Emission Factors')
@section('page-title', 'Emission Factors')

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="container-fluid mt-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">Emission Factors</h4>
                <div class="text-muted small">Manage factors used for auto-calculation (tCO₂e per unit).</div>
            </div>
            <button class="btn btn-success" id="addFactorBtn">
                <i class="fas fa-plus me-2"></i>Add Factor
            </button>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle w-100" id="factorsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Emission Source</th>
                                <th>Unit</th>
                                <th>Factor Value</th>
                                <th>Region</th>
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
<div class="modal fade" id="factorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const table = $('#factorsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('emission_factors.data') }}",
        columns: [
            { data: 'id', name: 'id' },
            { data: 'source_name', name: 'source_name' },
            { data: 'unit', name: 'unit' },
            { data: 'factor_value', name: 'factor_value' },
            { data: 'region', name: 'region', defaultContent: '' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false },
        ]
    });

    const modalEl = document.getElementById('factorModal');
    const modal = new bootstrap.Modal(modalEl);

    function resetForm() {
        document.getElementById('factorForm').reset();
        document.getElementById('factor_id').value = '';
        document.getElementById('factorError').classList.add('d-none');
        document.getElementById('factorError').textContent = '';
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
                document.getElementById('unit').value = data.unit || '';
                document.getElementById('factor_value').value = data.factor_value || '';
                document.getElementById('region').value = data.region || '';

                const isView = this.classList.contains('viewBtn');
                ['emission_source_id','unit','factor_value','region'].forEach(id => {
                    document.getElementById(id).disabled = isView;
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
        ['emission_source_id','unit','factor_value','region'].forEach(id => document.getElementById(id).disabled = false);
        document.getElementById('saveFactorBtn').style.display = 'inline-block';
    });
});
</script>
@endpush


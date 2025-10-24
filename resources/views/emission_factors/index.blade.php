@extends('layouts.app')

@section('title', 'Emission Factors')
@section('page-title', 'Emission Factors')

@section('content')
    <div class="container-fluid py-4">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 mb-0">Emission Factors List</h2>
            <button class="btn btn-primary" id="addEmissionFactorBtn">Add Emission Factor</button>
        </div>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered" id="emissionFactorsTable">
                <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Emission Source</th>
                    <th>Unit</th>
                    <th>Factor Value</th>
                    <th>Region</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <!-- Modal for Add/Edit -->
    <div class="modal fade" id="emissionFactorModal" tabindex="-1" aria-labelledby="emissionFactorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form id="emissionFactorForm">
                    <input type="hidden" name="id" id="emissionFactorId">
                    <div class="modal-header">
                        <div class="icon-circle">
                            <i class="bi bi-cloud"></i>
                        </div>
                        <h5 class="modal-title" id="modalTitle">Add Emission Factor</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="formErrors" class="alert alert-danger d-none"></div>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label">Emission Source <span class="text-danger">*</span></label>
                                <select name="emission_source_id" id="emission_source_id" class="form-select" required>
                                    <option value="">Select Source</option>
                                    @foreach($sources as $source)
                                        <option value="{{ $source->id }}">{{ $source->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Unit <span class="text-danger">*</span></label>
                                <input type="text" name="unit" id="unit" class="form-control" placeholder="e.g., kg CO2e/Litre" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Factor Value <span class="text-danger">*</span></label>
                                <input type="number" step="0.000001" name="factor_value" id="factor_value" class="form-control" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Region</label>
                                <input type="text" name="region" id="region" class="form-control" placeholder="Optional">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i> Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save-fill me-1"></i> Save Factor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Modal -->
    <div class="modal fade" id="viewEmissionFactorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">Emission Factor Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-2"><strong>Emission Source:</strong> <span id="viewSource">-</span></div>
                        <div class="col-md-6 mb-2"><strong>Unit:</strong> <span id="viewUnit">-</span></div>
                        <div class="col-md-6 mb-2"><strong>Factor Value:</strong> <span id="viewValue">-</span></div>
                        <div class="col-md-6 mb-2"><strong>Region:</strong> <span id="viewRegion">-</span></div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-info" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.min.css" rel="stylesheet">
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });

        $(function () {
            var modal = new bootstrap.Modal(document.getElementById('emissionFactorModal'));
            var viewModal = new bootstrap.Modal(document.getElementById('viewEmissionFactorModal'));

            var table = $('#emissionFactorsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('emission_factors.data') }}",
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'source_name', name: 'emissionSource.name' },
                    { data: 'unit', name: 'unit' },
                    { data: 'factor_value', name: 'factor_value' },
                    { data: 'region', name: 'region' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                ]
            });

            // Add
            $('#addEmissionFactorBtn').click(function () {
                $('#emissionFactorForm')[0].reset();
                $('#emissionFactorId').val('');
                $('#modalTitle').text('Add Emission Factor');
                $('#formErrors').html('').addClass('d-none');
                modal.show();
            });

            // Submit Add/Edit
            $('#emissionFactorForm').submit(function (e) {
                e.preventDefault();
                var formData = $(this).serialize();
                $.ajax({
                    url: "{{ route('emission_factors.storeOrUpdate') }}",
                    method: 'POST',
                    data: formData,
                    success: function (res) {
                        table.ajax.reload();
                        modal.hide();
                        Swal.fire('Success', res.message, 'success');
                    },
                    error: function (err) {
                        if (err.status === 422) {
                            let errors = err.responseJSON.errors;
                            let errorHtml = '<ul>';
                            $.each(errors, function (key, value) {
                                errorHtml += '<li>' + value[0] + '</li>';
                            });
                            errorHtml += '</ul>';
                            $('#formErrors').html(errorHtml).removeClass('d-none');
                        } else {
                            Swal.fire('Error', 'Something went wrong!', 'error');
                        }
                    }
                });
            });

            // Edit
            $('#emissionFactorsTable').on('click', '.editBtn', function () {
                var id = $(this).data('id');
                $.get("{{ url('emission-factors') }}/" + id, function (data) {
                    $('#emissionFactorId').val(data.id);
                    $('#emission_source_id').val(data.emission_source_id);
                    $('#unit').val(data.unit);
                    $('#factor_value').val(data.factor_value);
                    $('#region').val(data.region);
                    $('#modalTitle').text('Edit Emission Factor');
                    $('#formErrors').html('').addClass('d-none');
                    modal.show();
                });
            });

            // View
            $('#emissionFactorsTable').on('click', '.viewBtn', function () {
                var id = $(this).data('id');
                $.get("{{ url('emission-factors') }}/" + id, function (data) {
                    $('#viewSource').text(data.emission_source.name);
                    $('#viewUnit').text(data.unit);
                    $('#viewValue').text(data.factor_value);
                    $('#viewRegion').text(data.region ?? '-');
                    viewModal.show();
                });
            });

            // Delete
            $('#emissionFactorsTable').on('click', '.deleteBtn', function () {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ url('emission-factors') }}/" + id,
                            method: 'DELETE',
                            data: { _token: "{{ csrf_token() }}" },
                            success: function (res) {
                                table.ajax.reload();
                                Swal.fire('Deleted!', res.message, 'success');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush

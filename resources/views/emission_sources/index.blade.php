@extends('layouts.app')

@section('title', 'Emission Sources')
@section('page-title', 'Emission Sources')

@section('content')
    <div class="container-fluid py-4">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 mb-0">Emission Sources List</h2>
            <button class="btn btn-primary" id="addEmissionSourceBtn">Add Emission Source</button>
        </div>

        <!-- DataTable -->
        <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered" id="emissionSourcesTable">
                <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Scope</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <!-- Modal for Add/Edit -->
    <div class="modal fade" id="emissionSourceModal" tabindex="-1" aria-labelledby="emissionSourceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form id="emissionSourceForm">
                    <input type="hidden" name="id" id="emissionSourceId">
                    <div class="modal-header">
                        <div class="icon-circle">
                            <i class="bi bi-cloud"></i>
                        </div>
                        <h5 class="modal-title" id="modalTitle">Add Emission Source</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="formErrors" class="alert alert-danger d-none"></div>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label">Source Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control" placeholder="e.g., Diesel Generator" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Scope <span class="text-danger">*</span></label>
                                <select name="scope" id="scope" class="form-select" required>
                                    <option value="">Select Scope</option>
                                    <option value="Scope 1">Scope 1 - Direct Emissions</option>
                                    <option value="Scope 2">Scope 2 - Indirect Energy Emissions</option>
                                    <option value="Scope 3">Scope 3 - Other Indirect Emissions</option>
                                </select>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" id="description" class="form-control" rows="3" placeholder="Enter details about this emission source"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg me-1"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save-fill me-1"></i> Save Source
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Modal -->
    <div class="modal fade" id="viewEmissionSourceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">Emission Source Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-2"><strong>Name:</strong> <span id="viewName">-</span></div>
                        <div class="col-md-6 mb-2"><strong>Scope:</strong> <span id="viewScope">-</span></div>
                        <div class="col-12 mb-2"><strong>Description:</strong> <p id="viewDescription">-</p></div>
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

        // ✅ Add CSRF token to all AJAX requests (MUST be before any AJAX calls)
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $(function () {
            var modal = new bootstrap.Modal(document.getElementById('emissionSourceModal'));
            var viewModal = new bootstrap.Modal(document.getElementById('viewEmissionSourceModal'));

            var table = $('#emissionSourcesTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('emission_sources.data') }}",
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'name', name: 'name' },
                    { data: 'scope', name: 'scope' },
                    { data: 'description', name: 'description' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                ]
            });

            // Open Add Modal
            $('#addEmissionSourceBtn').click(function () {
                $('#emissionSourceForm')[0].reset();
                $('#emissionSourceId').val('');
                $('#modalTitle').text('Add Emission Source');
                $('#formErrors').html('').addClass('d-none');
                modal.show();
            });

            // Submit Form
            $('#emissionSourceForm').submit(function (e) {
                e.preventDefault();
                var formData = $(this).serialize();
                $.ajax({
                    url: "{{ route('emission_sources.storeOrUpdate') }}",
                    method: "POST",
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
            $('#emissionSourcesTable').on('click', '.editBtn', function () {
                var id = $(this).data('id');
                $.get("{{ url('emission-sources') }}/" + id, function (data) {
                    $('#emissionSourceId').val(data.id);
                    $('#name').val(data.name);
                    $('#scope').val(data.scope);
                    $('#description').val(data.description);
                    $('#modalTitle').text('Edit Emission Source');
                    $('#formErrors').html('').addClass('d-none');
                    modal.show();
                });
            });

            // View
            $('#emissionSourcesTable').on('click', '.viewBtn', function () {
                var id = $(this).data('id');
                $.get("{{ url('emission-sources') }}/" + id, function (data) {
                    $('#viewName').text(data.name);
                    $('#viewScope').text(data.scope);
                    $('#viewDescription').text(data.description ?? '-');
                    viewModal.show();
                });
            });

            // Delete
            $('#emissionSourcesTable').on('click', '.deleteBtn', function () {
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
                            url: "{{ url('emission-sources') }}/" + id,
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

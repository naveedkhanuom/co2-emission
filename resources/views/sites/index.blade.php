@extends('layouts.app')

@section('title', 'Sites')
@section('page-title', 'Sites')

@section('content')
    <div class="container-fluid py-4">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 mb-0">Sites List</h2>
            <button class="btn btn-primary" id="addSiteBtn">Add Site</button>
        </div>

        <!-- Sites Table -->
        <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered" id="sitesTable">
                <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Company</th>
                    <th>Name</th>
                    <th>Location</th>
                    <th>Latitude</th>
                    <th>Longitude</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <!-- Modal for Add/Edit -->
    <!-- --- POPUP (MODAL) STRUCTURE --- -->
    <div class="modal fade" id="siteModal" tabindex="-1" aria-labelledby="siteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form id="siteForm" onsubmit="event.preventDefault(); submitSiteForm();">
                    <input type="hidden" name="site_id" id="siteId">
                    <div class="modal-header">
                        <!-- Floating Icon Circle -->
                        <div class="icon-circle">
                            <i class="bi bi-building"></i>
                        </div>
                        <h5 class="modal-title" id="modalTitle">Register New Company Site</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="formErrors" class="alert alert-danger d-none"></div>
                        <div class="row g-4">

                            <div class="col-md-6">
                                <label class="form-label">Company <span class="text-danger">*</span></label>
                                <select name="company_id" id="company_id" class="form-control" required>
                                    <option value="">Select Company</option>
                                    @foreach($companies as $company)
                                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Site Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="siteName" class="form-control" required placeholder="e.g., Central Warehouse">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Location</label>
                                <input type="text" name="location" id="location" class="form-control" placeholder="City, State/Country">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Latitude</label>
                                <input type="text" name="latitude" id="latitude" class="form-control" placeholder="e.g., 34.0522">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Longitude</label>
                                <input type="text" name="longitude" id="longitude" class="form-control" placeholder="e.g., -118.2437">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" id="description" class="form-control" rows="3" placeholder="Detailed notes about the site or its purpose"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i> Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save-fill me-1"></i> Save Site</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- --- END POPUP STRUCTURE --- -->

    <!-- View Site Modal -->
    <div class="modal fade" id="viewSiteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">Site Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-2"><strong>Company:</strong> <span id="viewCompany">-</span></div>
                        <div class="col-md-6 mb-2"><strong>Site Name:</strong> <span id="viewName">-</span></div>
                        <div class="col-md-6 mb-2"><strong>Location:</strong> <span id="viewLocation">-</span></div>
                        <div class="col-md-6 mb-2"><strong>Latitude:</strong> <span id="viewLatitude">-</span></div>
                        <div class="col-md-6 mb-2"><strong>Longitude:</strong> <span id="viewLongitude">-</span></div>
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
        $(document).ready(function() {

            var siteModal = new bootstrap.Modal(document.getElementById('siteModal'));
            var viewModal = new bootstrap.Modal(document.getElementById('viewSiteModal'));

            var table = $('#sitesTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('sites.getSites') }}",
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'company', name: 'company.name' },
                    { data: 'name', name: 'name' },
                    { data: 'location', name: 'location' },
                    { data: 'latitude', name: 'latitude' },
                    { data: 'longitude', name: 'longitude' },
                    { data: 'description', name: 'description' },
                    { data: 'actions', name: 'actions', orderable:false, searchable:false }
                ]
            });

            // Add Site
            $('#addSiteBtn').click(function() {
                $('#siteForm')[0].reset();
                $('#siteId').val('');
                $('#modalTitle').text('Add Site');
                $('#formErrors').html('').addClass('d-none');
                siteModal.show();
            });

            // Submit Add/Edit
            $('#siteForm').submit(function(e){
                e.preventDefault();
                var formData = $(this).serialize();
                $.ajax({
                    url: "{{ route('sites.storeOrUpdate') }}",
                    method: 'POST',
                    data: formData,
                    success: function(res){
                        table.ajax.reload();
                        siteModal.hide();
                        Swal.fire('Success', res.message, 'success');
                    },
                    error: function(err){
                        if(err.status === 422){
                            let errors = err.responseJSON.errors;
                            let errorHtml = '<ul>';
                            $.each(errors, function(key, value){
                                errorHtml += '<li>'+value[0]+'</li>';
                            });
                            errorHtml += '</ul>';
                            $('#formErrors').html(errorHtml).removeClass('d-none');
                        } else {
                            Swal.fire('Error', 'Something went wrong!', 'error');
                        }
                    }
                });
            });

            // Edit Site
            $('#sitesTable').on('click', '.editBtn', function(){
                var id = $(this).data('id');
                $.get("{{ url('sites') }}/"+id, function(data){
                    $('#siteId').val(data.id);
                    $('#company_id').val(data.company_id);
                    $('#siteName').val(data.name);
                    $('#location').val(data.location);
                    $('#latitude').val(data.latitude);
                    $('#longitude').val(data.longitude);
                    $('#description').val(data.description);
                    $('#modalTitle').text('Edit Site');
                    $('#formErrors').html('').addClass('d-none');
                    siteModal.show();
                });
            });

            // View Site
          // View Site
            $('#sitesTable').on('click', '.viewBtn', function(){
                var id = $(this).data('id');
                $.get("{{ url('sites') }}/"+id, function(data){
                    $('#viewCompany').text(data.company.name);
                    $('#viewName').text(data.name);
                    $('#viewLocation').text(data.location ?? '-');
                    $('#viewLatitude').text(data.latitude ?? '-');
                    $('#viewLongitude').text(data.longitude ?? '-');
                    $('#viewDescription').text(data.description ?? '-');
                    viewModal.show();
                });
            });


            // Delete Site
            $('#sitesTable').on('click', '.deleteBtn', function(){
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
                    if(result.isConfirmed){
                        $.ajax({
                            url: "{{ url('sites') }}/"+id,
                            method: 'DELETE',
                            data: { _token: "{{ csrf_token() }}" },
                            success: function(res){
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

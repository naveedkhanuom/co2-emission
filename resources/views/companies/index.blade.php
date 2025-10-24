@extends('layouts.app')

@section('title', 'Companies')
@section('page-title', 'Companies')

@section('content')
    <div class="container-fluid py-4">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 mb-0">Companies List</h2>
            <button class="btn btn-primary" id="addCompanyBtn">Add Company</button>
        </div>

        <!-- Companies Table -->
        <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered" id="companiesTable">
                <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Industry</th>
                    <th>Country</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="companyModal" tabindex="-1" aria-labelledby="companyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="companyForm">
                    @csrf
                    <input type="hidden" name="id" id="companyId">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add Company</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="companyName" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Industry Type</label>
                                <input type="text" name="industry_type" id="industryType" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Country</label>
                                <input type="text" name="country" id="country" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contact Person</label>
                                <input type="text" name="contact_person" id="contactPerson" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" id="email" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" id="phone" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Company</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {

            var companyModal = new bootstrap.Modal(document.getElementById('companyModal'));

            // Initialize DataTable
            var table = $('#companiesTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('companies.index') }}",
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'name', name: 'name' },
                    { data: 'industry_type', name: 'industry_type' },
                    { data: 'country', name: 'country' },
                    { data: 'email', name: 'email' },
                    { data: 'phone', name: 'phone' },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                lengthMenu: [5,10,25,50],
                pageLength: 10
            });

            // Open modal for add
            $('#addCompanyBtn').click(function() {
                $('#companyForm')[0].reset();
                $('#companyId').val('');
                $('#modalTitle').text('Add Company');
                companyModal.show();
            });

            // Submit form
            $('#companyForm').submit(function(e){
                e.preventDefault();
                $.ajax({
                    url: "{{ route('companies.store') }}",
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(res){
                        table.ajax.reload();
                        companyModal.hide();
                        alert(res.message);
                    },
                    error: function(err){
                        console.log(err);
                        alert('Error saving company.');
                    }
                });
            });

            // Edit
            $('#companiesTable').on('click', '.editBtn', function(){
                var id = $(this).data('id');
                $.get("{{ url('companies') }}/"+id+"/edit", function(data){
                    $('#companyId').val(data.id);
                    $('#companyName').val(data.name);
                    $('#industryType').val(data.industry_type);
                    $('#country').val(data.country);
                    $('#contactPerson').val(data.contact_person);
                    $('#email').val(data.email);
                    $('#phone').val(data.phone);
                    $('#modalTitle').text('Edit Company');
                    companyModal.show();
                });
            });

            // Delete
            $('#companiesTable').on('click', '.deleteBtn', function(){
                if(confirm('Are you sure you want to delete this company?')){
                    var id = $(this).data('id');
                    $.ajax({
                        url: "{{ url('companies') }}/"+id,
                        method: 'DELETE',
                        data: { _token: "{{ csrf_token() }}" },
                        success: function(res){
                            table.ajax.reload();
                            alert(res.message);
                        }
                    });
                }
            });

            // View
            $('#companiesTable').on('click', '.viewBtn', function(){
                var id = $(this).data('id');
                $.get("{{ url('companies') }}/"+id, function(data){
                    alert(`
Name: ${data.name}
Industry: ${data.industry_type}
Country: ${data.country}
Contact Person: ${data.contact_person}
Email: ${data.email}
Phone: ${data.phone}
            `);
                });
            });

        });
    </script>
@endpush

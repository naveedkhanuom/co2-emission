@extends('layouts.app')

@section('title', 'Suppliers')
@section('page-title', 'Suppliers')

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="suppliers-app container-fluid mt-4">
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
            <h2><span class="sb"><i class="fas fa-truck"></i></span> Suppliers</h2>
            <p>View and manage your suppliers for Scope 3 emissions tracking.</p>
            <button type="button" class="btn-add" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fas fa-plus"></i> Add New Supplier
            </button>
        </div>

        <!-- DataTable Card -->
        <div class="card suppliers-datatable-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                <h5 class="mb-0">Suppliers List</h5>
                <div class="input-group" style="width: 280px;">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" id="searchInput" class="form-control" placeholder="Search suppliers...">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="suppliersTable">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Contact Person</th>
                                <th>Country</th>
                                <th>Total Emissions</th>
                                <th>Data Quality</th>
                                <th>Status</th>
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
                        Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of <span id="totalCount">0</span> suppliers
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================= ADD MODAL ================= -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="addForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Supplier</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Supplier Name *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Contact Person</label>
                            <input type="text" name="contact_person" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Country</label>
                            <input type="text" name="country" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Industry</label>
                            <input type="text" name="industry" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">State</label>
                            <input type="text" name="state" class="form-control">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="pending">Pending</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Supplier</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ================= EDIT MODAL ================= -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="editForm">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Supplier</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Supplier Name *</label>
                            <input type="text" name="name" id="editName" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="editEmail" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Contact Person</label>
                            <input type="text" name="contact_person" id="editContactPerson" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" id="editPhone" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Country</label>
                            <input type="text" name="country" id="editCountry" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Industry</label>
                            <input type="text" name="industry" id="editIndustry" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">City</label>
                            <input type="text" name="city" id="editCity" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">State</label>
                            <input type="text" name="state" id="editState" class="form-control">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" id="editAddress" class="form-control">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Status</label>
                            <select name="status" id="editStatus" class="form-select">
                                <option value="pending">Pending</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" id="editNotes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Update Supplier</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ================= VIEW MODAL ================= -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Supplier Details</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body" id="viewModalBody">
                <!-- Content will be loaded via AJAX -->
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.suppliers-app * { box-sizing: border-box; }
.suppliers-app { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
/* Topbar */
.suppliers-app .topbar { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; padding: 20px 24px; background: linear-gradient(135deg, #fff 0%, var(--gray-50) 100%); border: 1px solid var(--gray-200); border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.suppliers-app .topbar h2 { font-size: 1.35rem; font-weight: 700; letter-spacing: -0.02em; display: flex; align-items: center; gap: 10px; margin: 0; color: var(--gray-800); }
.suppliers-app .topbar h2 .sb { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%); color: #fff; font-size: 1rem; font-weight: 700; box-shadow: 0 2px 8px rgba(46,125,50,.25); }
.suppliers-app .topbar p { color: var(--gray-600); font-size: 0.875rem; flex: 1; min-width: 180px; margin: 0; line-height: 1.4; }
.suppliers-app .btn-add { margin-left: auto; padding: 10px 20px; border-radius: 10px; background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%); color: #fff; border: none; font-size: 0.875rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
.suppliers-app .btn-add:hover { background: linear-gradient(135deg, var(--dark-green) 0%, var(--primary-green) 100%); color: #fff; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(46,125,50,.35); }
/* Alerts */
.suppliers-app .alert { border-radius: 12px; border: 1px solid transparent; }
.suppliers-app .alert-success { background: rgba(76,175,80,0.1); border-color: rgba(76,175,50,0.25); color: var(--dark-green); }
.suppliers-app .alert-danger { background: rgba(211,47,47,0.08); border-color: rgba(211,47,47,0.2); color: var(--danger-red); }
/* DataTable card */
.suppliers-app .suppliers-datatable-card { background: #fff; border: 1px solid var(--gray-200); border-radius: 16px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.suppliers-app .suppliers-datatable-card .card-header { padding: 16px 20px; border-bottom: 1px solid var(--gray-200); background: linear-gradient(180deg, var(--gray-50) 0%, #fff 100%); }
.suppliers-app .suppliers-datatable-card .card-header h5 { font-size: 1.0625rem; font-weight: 700; color: var(--gray-800); }
.suppliers-app .suppliers-datatable-card .card-header .input-group { border-radius: 10px; overflow: hidden; border: 1px solid var(--gray-200); }
.suppliers-app .suppliers-datatable-card .card-header .input-group-text { background: var(--gray-50); border: none; color: var(--gray-600); padding: 10px 14px; }
.suppliers-app .suppliers-datatable-card .card-header .form-control { border: none; padding: 10px 14px; font-size: 0.875rem; }
.suppliers-app .suppliers-datatable-card #suppliersTable { width: 100% !important; border-collapse: separate; border-spacing: 0; }
.suppliers-app .suppliers-datatable-card #suppliersTable thead th { background: var(--gray-100); color: var(--gray-600); font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; padding: 14px 16px; border: none; border-bottom: 1px solid var(--gray-200); }
.suppliers-app .suppliers-datatable-card #suppliersTable thead th:first-child { padding-left: 20px; }
.suppliers-app .suppliers-datatable-card #suppliersTable tbody td { padding: 14px 16px; font-size: 0.875rem; color: var(--gray-800); border: none; border-bottom: 1px solid var(--gray-100); vertical-align: middle; }
.suppliers-app .suppliers-datatable-card #suppliersTable tbody td:first-child { padding-left: 20px; }
.suppliers-app .suppliers-datatable-card #suppliersTable tbody tr:hover td { background: var(--gray-50); }
.suppliers-app .suppliers-datatable-card #suppliersTable tbody tr:last-child td { border-bottom: none; }
.suppliers-app .suppliers-datatable-card .card-footer { padding: 12px 20px; border-top: 1px solid var(--gray-200); background: var(--gray-50); font-size: 0.8125rem; color: var(--gray-600); }
.suppliers-app .suppliers-datatable-card .dataTables_wrapper { padding: 0; }
.suppliers-app .suppliers-datatable-card .dataTables_wrapper .dataTables_length,
.suppliers-app .suppliers-datatable-card .dataTables_wrapper .dataTables_filter { display: none; }
.suppliers-app .suppliers-datatable-card .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 6px 12px; margin: 0 2px; border-radius: 8px; border: 1px solid var(--gray-200); background: #fff; color: var(--gray-700) !important; font-size: 0.8125rem; font-weight: 600; }
.suppliers-app .suppliers-datatable-card .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: var(--gray-100) !important; border-color: var(--gray-300) !important; color: var(--gray-800) !important; }
.suppliers-app .suppliers-datatable-card .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: var(--primary-green) !important; border-color: var(--primary-green) !important; color: #fff !important; }
.suppliers-app .suppliers-datatable-card .dataTables_wrapper .dataTables_processing { background: rgba(255,255,255,0.95); border-radius: 10px; padding: 14px 24px; font-weight: 600; font-size: 0.875rem; color: var(--gray-700); border: 1px solid var(--gray-200); box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.suppliers-app .suppliers-datatable-card .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
</style>
@endpush

@push('scripts')
<!-- jQuery and DataTables JS -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialize DataTable
        var table = $('#suppliersTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("suppliers.data") }}',
                error: function(xhr, error, thrown) {
                    console.error('DataTables error:', error);
                    alert('Error loading suppliers data. Please refresh the page.');
                }
            },
            columns: [
                { 
                    data: 'id', 
                    name: 'id',
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                { 
                    data: 'name', 
                    name: 'name',
                    orderable: true,
                    searchable: true
                },
                { 
                    data: 'email', 
                    name: 'email',
                    orderable: true,
                    searchable: true
                },
                { 
                    data: 'contact_person', 
                    name: 'contact_person',
                    orderable: true,
                    searchable: true
                },
                { 
                    data: 'country', 
                    name: 'country',
                    orderable: true,
                    searchable: true
                },
                { 
                    data: 'total_emissions', 
                    name: 'total_emissions',
                    orderable: false,
                    searchable: false
                },
                { 
                    data: 'data_quality_badge', 
                    name: 'data_quality',
                    orderable: false,
                    searchable: false
                },
                { 
                    data: 'status_badge', 
                    name: 'status',
                    orderable: true,
                    searchable: false
                },
                { 
                    data: 'actions', 
                    name: 'actions',
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                }
            ],
            order: [[0, 'asc']],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            drawCallback: function() {
                var info = table.page.info();
                $('#showingFrom').text(info.recordsDisplay ? info.start + 1 : 0);
                $('#showingTo').text(info.end);
                $('#totalCount').text(info.recordsDisplay);
            },
            language: {
                processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
                emptyTable: 'No suppliers found',
                zeroRecords: 'No matching suppliers found',
                info: 'Showing _START_ to _END_ of _TOTAL_ suppliers',
                infoEmpty: 'Showing 0 to 0 of 0 suppliers',
                infoFiltered: '(filtered from _MAX_ total suppliers)',
                search: '',
                searchPlaceholder: 'Search...',
                lengthMenu: 'Show _MENU_ suppliers',
                paginate: {
                    first: '<i class="fas fa-angle-double-left"></i>',
                    previous: '<i class="fas fa-angle-left"></i>',
                    next: '<i class="fas fa-angle-right"></i>',
                    last: '<i class="fas fa-angle-double-right"></i>'
                }
            },
            dom: 'rt<"row mt-3"<"col-sm-12"p>>',
        });
        
        // Custom search input
        $('#searchInput').on('keyup', function() {
            table.search(this.value).draw();
        });

        // Add Supplier Form
        $('#addForm').on('submit', function(e) {
            e.preventDefault();
            var formData = $(this).serialize();
            
            $.ajax({
                url: '{{ route("suppliers.store") }}',
                method: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        $('#addModal').modal('hide');
                        $('#addForm')[0].reset();
                        table.ajax.reload();
                        $('.alert-success').remove();
                        $('.suppliers-app').prepend('<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i>' + response.message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
                        setTimeout(function() {
                            $('.alert-success').fadeOut('slow');
                        }, 5000);
                    }
                },
                error: function(xhr) {
                    var errors = xhr.responseJSON?.errors || {};
                    var errorMsg = xhr.responseJSON?.message || 'Error creating supplier';
                    alert(errorMsg);
                }
            });
        });

        // Edit Supplier - Load data
        $(document).on('click', '.editBtn', function() {
            var id = $(this).data('id');
            $('#editForm').attr('action', '/suppliers/' + id);
            
            // Load supplier data
            $.ajax({
                url: '/suppliers/' + id,
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        var supplier = response.supplier;
                        $('#editName').val(supplier.name);
                        $('#editEmail').val(supplier.email);
                        $('#editContactPerson').val(supplier.contact_person);
                        $('#editPhone').val(supplier.phone);
                        $('#editCountry').val(supplier.country);
                        $('#editIndustry').val(supplier.industry);
                        $('#editCity').val(supplier.city);
                        $('#editState').val(supplier.state);
                        $('#editAddress').val(supplier.address);
                        $('#editStatus').val(supplier.status);
                        $('#editNotes').val(supplier.notes);
                    }
                }
            });
        });

        // Edit Supplier Form
        $('#editForm').on('submit', function(e) {
            e.preventDefault();
            var formData = $(this).serialize();
            var url = $(this).attr('action');
            
            $.ajax({
                url: url,
                method: 'PUT',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        $('#editModal').modal('hide');
                        table.ajax.reload();
                        $('.alert-success').remove();
                        $('.suppliers-app').prepend('<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i>' + response.message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
                        setTimeout(function() {
                            $('.alert-success').fadeOut('slow');
                        }, 5000);
                    }
                },
                error: function(xhr) {
                    var errorMsg = xhr.responseJSON?.message || 'Error updating supplier';
                    alert(errorMsg);
                }
            });
        });

        // View Supplier
        $(document).on('click', '.viewBtn', function() {
            var id = $(this).data('id');
            
            $.ajax({
                url: '/suppliers/' + id,
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        var supplier = response.supplier;
                        var html = '<div class="row g-3">';
                        html += '<div class="col-md-6"><strong>Name:</strong> ' + (supplier.name || 'N/A') + '</div>';
                        html += '<div class="col-md-6"><strong>Email:</strong> ' + (supplier.email || 'N/A') + '</div>';
                        html += '<div class="col-md-6"><strong>Contact Person:</strong> ' + (supplier.contact_person || 'N/A') + '</div>';
                        html += '<div class="col-md-6"><strong>Phone:</strong> ' + (supplier.phone || 'N/A') + '</div>';
                        html += '<div class="col-md-6"><strong>Country:</strong> ' + (supplier.country || 'N/A') + '</div>';
                        html += '<div class="col-md-6"><strong>Industry:</strong> ' + (supplier.industry || 'N/A') + '</div>';
                        html += '<div class="col-md-12"><strong>Total Emissions (Current Year):</strong> ' + (response.total_emissions || 0) + ' tCO2e</div>';
                        html += '<div class="col-md-12"><strong>Notes:</strong> ' + (supplier.notes || 'N/A') + '</div>';
                        html += '</div>';
                        $('#viewModalBody').html(html);
                        $('#viewModal').modal('show');
                    }
                }
            });
        });

        // Delete Supplier
        $(document).on('click', '.deleteBtn', function() {
            var id = $(this).data('id');
            
            if (confirm('Are you sure you want to delete this supplier?')) {
                $.ajax({
                    url: '/suppliers/' + id,
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            table.ajax.reload();
                            $('.alert-success').remove();
                            $('.suppliers-app').prepend('<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i>' + response.message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
                            setTimeout(function() {
                                $('.alert-success').fadeOut('slow');
                            }, 5000);
                        }
                    },
                    error: function(xhr) {
                        var errorMsg = xhr.responseJSON?.message || 'Error deleting supplier';
                        alert(errorMsg);
                    }
                });
            }
        });

        // Auto-hide success message after 5 seconds
        setTimeout(function() {
            $('.alert-success').fadeOut('slow');
        }, 5000);
    });
</script>
@endpush

@extends('layouts.app')

@section('title', 'Users')
@section('page-title', 'Users')

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="users-app container-fluid mt-4">

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
            <h2><span class="sb"><i class="fas fa-users"></i></span> Users</h2>
            <p>View and manage system users.</p>
            @can('create-user')
            <a href="{{ route('users.create') }}" class="btn-add">
                <i class="fas fa-plus"></i> Add New User
            </a>
            @endcan
        </div>

        <!-- DataTable Card -->
        <div class="card users-datatable-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                <h5 class="mb-0">Users List</h5>
                <div class="input-group" style="width: 280px;">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" id="searchInput" class="form-control" placeholder="Search users...">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="usersTable">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Roles</th>
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
                        Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of <span id="totalCount">0</span> users
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        .users-app * { box-sizing: border-box; }
        .users-app { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

        .users-app .topbar { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; padding: 20px 24px; background: linear-gradient(135deg, #fff 0%, var(--gray-50) 100%); border: 1px solid var(--gray-200); border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        .users-app .topbar h2 { font-size: 1.35rem; font-weight: 700; letter-spacing: -0.02em; display: flex; align-items: center; gap: 10px; margin: 0; color: var(--gray-800); }
        .users-app .topbar h2 .sb { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%); color: #fff; font-size: 1rem; font-weight: 700; box-shadow: 0 2px 8px rgba(46,125,50,.25); }
        .users-app .topbar p { color: var(--gray-600); font-size: 0.875rem; flex: 1; min-width: 180px; margin: 0; line-height: 1.4; }
        .users-app .btn-add { margin-left: auto; padding: 10px 20px; border-radius: 10px; background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%); color: #fff !important; border: none; font-size: 0.875rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .users-app .btn-add:hover { background: linear-gradient(135deg, var(--dark-green) 0%, var(--primary-green) 100%); color: #fff !important; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(46,125,50,.35); }

        .users-app .alert { border-radius: 12px; border: 1px solid transparent; }
        .users-app .alert-success { background: rgba(76,175,80,0.1); border-color: rgba(76,175,50,0.25); color: var(--dark-green); }
        .users-app .alert-danger { background: rgba(211,47,47,0.08); border-color: rgba(211,47,47,0.2); color: var(--danger-red); }

        .users-app .users-datatable-card { background: #fff; border: 1px solid var(--gray-200); border-radius: 16px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        .users-app .users-datatable-card .card-header { padding: 16px 20px; border-bottom: 1px solid var(--gray-200); background: linear-gradient(180deg, var(--gray-50) 0%, #fff 100%); }
        .users-app .users-datatable-card .card-header h5 { font-size: 1.0625rem; font-weight: 700; color: var(--gray-800); }
        .users-app .users-datatable-card .card-header .input-group { border-radius: 10px; overflow: hidden; border: 1px solid var(--gray-200); }
        .users-app .users-datatable-card .card-header .input-group-text { background: var(--gray-50); border: none; color: var(--gray-600); padding: 10px 14px; }
        .users-app .users-datatable-card .card-header .form-control { border: none; padding: 10px 14px; font-size: 0.875rem; }

        .users-app .users-datatable-card #usersTable { width: 100% !important; border-collapse: separate; border-spacing: 0; }
        .users-app .users-datatable-card #usersTable thead th { background: var(--gray-100); color: var(--gray-600); font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; padding: 14px 16px; border: none; border-bottom: 1px solid var(--gray-200); }
        .users-app .users-datatable-card #usersTable thead th:first-child { padding-left: 20px; }
        .users-app .users-datatable-card #usersTable tbody td { padding: 14px 16px; font-size: 0.875rem; color: var(--gray-800); border: none; border-bottom: 1px solid var(--gray-100); vertical-align: middle; }
        .users-app .users-datatable-card #usersTable tbody td:first-child { padding-left: 20px; }
        .users-app .users-datatable-card #usersTable tbody tr:hover td { background: var(--gray-50); }
        .users-app .users-datatable-card #usersTable tbody tr:last-child td { border-bottom: none; }

        .users-app .users-datatable-card .card-footer { padding: 12px 20px; border-top: 1px solid var(--gray-200); background: var(--gray-50); font-size: 0.8125rem; color: var(--gray-600); }

        .users-app .users-datatable-card .dataTables_wrapper { padding: 0; }
        .users-app .users-datatable-card .dataTables_wrapper .dataTables_length,
        .users-app .users-datatable-card .dataTables_wrapper .dataTables_filter { display: none; }
        .users-app .users-datatable-card .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 6px 12px; margin: 0 2px; border-radius: 8px; border: 1px solid var(--gray-200); background: #fff; color: var(--gray-700) !important; font-size: 0.8125rem; font-weight: 600; }
        .users-app .users-datatable-card .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: var(--gray-100) !important; border-color: var(--gray-300) !important; color: var(--gray-800) !important; }
        .users-app .users-datatable-card .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: var(--primary-green) !important; border-color: var(--primary-green) !important; color: #fff !important; }
        .users-app .users-datatable-card .dataTables_wrapper .dataTables_processing { background: rgba(255,255,255,0.95) !important; border-radius: 10px !important; padding: 14px 24px !important; font-weight: 600 !important; font-size: 0.875rem !important; color: var(--gray-700) !important; border: 1px solid var(--gray-200) !important; box-shadow: 0 2px 8px rgba(0,0,0,.06) !important; }
        .users-app .users-datatable-card .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
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
        var table = $('#usersTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("users.data") }}',
                error: function(xhr, error, thrown) {
                    console.error('DataTables error:', error);
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
                    data: 'name_with_badge', 
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
                    data: 'roles_badge', 
                    name: 'roles',
                    orderable: false,
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
                emptyTable: 'No users found',
                zeroRecords: 'No matching users found',
                paginate: {
                    first: '<i class="fas fa-angle-double-left"></i>',
                    previous: '<i class="fas fa-angle-left"></i>',
                    next: '<i class="fas fa-angle-right"></i>',
                    last: '<i class="fas fa-angle-double-right"></i>'
                }
            }
        });
        
        // Custom search input
        $('#searchInput').on('keyup', function() {
            table.search(this.value).draw();
        });
        
        // Auto-hide success message after 5 seconds
        setTimeout(function() {
            $('.alert-success').fadeOut('slow');
        }, 5000);
    });
</script>
@endpush

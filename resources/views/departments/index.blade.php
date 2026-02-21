@extends('layouts.app')

@section('title', 'Departments')
@section('page-title', 'Departments')

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="departments-app container-fluid mt-4">

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
            <h2><span class="sb"><i class="fas fa-sitemap"></i></span> Departments</h2>
            <p>View and manage departments under each facility for data entry and reporting.</p>
            <button type="button" class="btn-add" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fas fa-plus"></i> Add Department
            </button>
        </div>

        <!-- DataTable Card -->
        <div class="card departments-datatable-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                <h5 class="mb-0">Departments List</h5>
                <div class="input-group" style="width: 280px;">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" id="searchInput" class="form-control" placeholder="Search departments...">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="departmentsTable">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>Facility</th>
                                <th>Department Name</th>
                                <th>Description</th>
                                <th width="160" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($departments as $department)
                                <tr>
                                    <td></td>
                                    <td>{{ $department->facility->name ?? '-' }}</td>
                                    <td>{{ $department->name }}</td>
                                    <td>{{ $department->description }}</td>
                                    <td class="text-center">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-warning editBtn"
                                            data-id="{{ $department->id }}"
                                            data-name="{{ $department->name }}"
                                            data-description="{{ $department->description }}"
                                            data-facility="{{ $department->facility_id }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editModal">
                                            <i class="fas fa-edit me-1"></i>Edit
                                        </button>

                                        <form method="POST"
                                              action="{{ route('departments.destroy', $department->id) }}"
                                              class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Delete this department?')">
                                                <i class="fas fa-trash me-1"></i>Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="table-info-text">
                        Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of <span id="totalCount">0</span> departments
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================= ADD MODAL ================= -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <form method="POST" action="{{ route('departments.store') }}">
            @csrf
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <div class="icon-circle">
                        <i class="fas fa-plus"></i>
                    </div>
                    <h5 class="modal-title">Add Department</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Facility *</label>
                            <select name="facility_id" class="form-select" required>
                                <option value="">Select Facility</option>
                                @foreach($facilities as $facility)
                                    <option value="{{ $facility->id }}">
                                        {{ $facility->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Department Name *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Department</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ================= EDIT MODAL ================= -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <form method="POST" id="editForm">
            @csrf
            @method('PUT')
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <div class="icon-circle">
                        <i class="fas fa-edit"></i>
                    </div>
                    <h5 class="modal-title">Edit Department</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Facility *</label>
                            <select name="facility_id" id="editFacility" class="form-select" required>
                                @foreach($facilities as $facility)
                                    <option value="{{ $facility->id }}">
                                        {{ $facility->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Department Name *</label>
                            <input type="text" name="name" id="editName" class="form-control" required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="editDescription"
                                      class="form-control" rows="3"></textarea>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Update Department</button>
                </div>
            </div>
        </form>
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

        .departments-app * { box-sizing: border-box; }
        .departments-app { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

        /* Topbar */
        .departments-app .topbar {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
            flex-wrap: wrap;
            padding: 20px 24px;
            background: linear-gradient(135deg, #fff 0%, var(--gray-50) 100%);
            border: 1px solid var(--gray-200);
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,.06);
        }
        .departments-app .topbar h2 {
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
            color: var(--gray-800);
        }
        .departments-app .topbar h2 .sb {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%);
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(46,125,50,.25);
        }
        .departments-app .topbar p {
            color: var(--gray-600);
            font-size: 0.875rem;
            flex: 1;
            min-width: 180px;
            margin: 0;
            line-height: 1.4;
        }
        .departments-app .btn-add {
            margin-left: auto;
            padding: 10px 20px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%);
            color: #fff;
            border: none;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .departments-app .btn-add:hover {
            background: linear-gradient(135deg, var(--dark-green) 0%, var(--primary-green) 100%);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(46,125,50,.35);
        }

        /* Alerts */
        .departments-app .alert { border-radius: 12px; border: 1px solid transparent; }
        .departments-app .alert-success { background: rgba(76,175,80,0.1); border-color: rgba(76,175,50,0.25); color: var(--dark-green); }
        .departments-app .alert-danger { background: rgba(211,47,47,0.08); border-color: rgba(211,47,47,0.2); color: var(--danger-red); }

        /* DataTable card (match system look) */
        .departments-app .departments-datatable-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,.06);
        }
        .departments-app .departments-datatable-card .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--gray-200);
            background: linear-gradient(180deg, var(--gray-50) 0%, #fff 100%);
        }
        .departments-app .departments-datatable-card .card-header h5 {
            font-size: 1.0625rem;
            font-weight: 700;
            color: var(--gray-800);
        }
        .departments-app .departments-datatable-card .card-header .input-group {
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid var(--gray-200);
        }
        .departments-app .departments-datatable-card .card-header .input-group-text {
            background: var(--gray-50);
            border: none;
            color: var(--gray-600);
            padding: 10px 14px;
        }
        .departments-app .departments-datatable-card .card-header .form-control {
            border: none;
            padding: 10px 14px;
            font-size: 0.875rem;
        }

        .departments-app .departments-datatable-card #departmentsTable { width: 100% !important; border-collapse: separate; border-spacing: 0; }
        .departments-app .departments-datatable-card #departmentsTable thead th {
            background: var(--gray-100);
            color: var(--gray-600);
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 14px 16px;
            border: none;
            border-bottom: 1px solid var(--gray-200);
        }
        .departments-app .departments-datatable-card #departmentsTable thead th:first-child { padding-left: 20px; }
        .departments-app .departments-datatable-card #departmentsTable tbody td {
            padding: 14px 16px;
            font-size: 0.875rem;
            color: var(--gray-800);
            border: none;
            border-bottom: 1px solid var(--gray-100);
            vertical-align: middle;
        }
        .departments-app .departments-datatable-card #departmentsTable tbody td:first-child { padding-left: 20px; }
        .departments-app .departments-datatable-card #departmentsTable tbody tr:hover td { background: var(--gray-50); }
        .departments-app .departments-datatable-card #departmentsTable tbody tr:last-child td { border-bottom: none; }

        .departments-app .departments-datatable-card .card-footer {
            padding: 12px 20px;
            border-top: 1px solid var(--gray-200);
            background: var(--gray-50);
            font-size: 0.8125rem;
            color: var(--gray-600);
        }

        .departments-app .departments-datatable-card .dataTables_wrapper { padding: 0; }
        .departments-app .departments-datatable-card .dataTables_wrapper .dataTables_length,
        .departments-app .departments-datatable-card .dataTables_wrapper .dataTables_filter { display: none; }
        .departments-app .departments-datatable-card .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 6px 12px;
            margin: 0 2px;
            border-radius: 8px;
            border: 1px solid var(--gray-200);
            background: #fff;
            color: var(--gray-700) !important;
            font-size: 0.8125rem;
            font-weight: 600;
        }
        .departments-app .departments-datatable-card .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: var(--gray-100) !important;
            border-color: var(--gray-300) !important;
            color: var(--gray-800) !important;
        }
        .departments-app .departments-datatable-card .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--primary-green) !important;
            border-color: var(--primary-green) !important;
            color: #fff !important;
        }
        .departments-app .departments-datatable-card .dataTables_wrapper .dataTables_processing {
            background: rgba(255,255,255,0.95);
            border-radius: 10px;
            padding: 14px 24px;
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--gray-700);
            border: 1px solid var(--gray-200);
            box-shadow: 0 2px 8px rgba(0,0,0,.06);
        }
        .departments-app .departments-datatable-card .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    </style>
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function () {
            var table = $('#departmentsTable').DataTable({
                processing: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                order: [[2, 'asc']],
                columnDefs: [
                    {
                        targets: 0,
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    { targets: -1, orderable: false, searchable: false }
                ],
                drawCallback: function () {
                    var api = this.api();
                    var info = api.page.info();
                    $('#showingFrom').text(info.recordsDisplay ? info.start + 1 : 0);
                    $('#showingTo').text(info.end);
                    $('#totalCount').text(info.recordsDisplay);
                },
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
                    emptyTable: 'No departments found',
                    zeroRecords: 'No matching departments found',
                    info: 'Showing _START_ to _END_ of _TOTAL_ departments',
                    infoEmpty: 'Showing 0 to 0 of 0 departments',
                    infoFiltered: '(filtered from _MAX_ total departments)',
                    search: '',
                    searchPlaceholder: 'Search...',
                    lengthMenu: 'Show _MENU_ departments',
                    paginate: {
                        first: '<i class="fas fa-angle-double-left"></i>',
                        previous: '<i class="fas fa-angle-left"></i>',
                        next: '<i class="fas fa-angle-right"></i>',
                        last: '<i class="fas fa-angle-double-right"></i>'
                    }
                },
                dom: 'rt<"row mt-3"<"col-sm-12"p>>'
            });

            $('#searchInput').on('keyup', function () {
                table.search(this.value).draw();
            });

            // Edit Department (delegated so it works after DataTables redraw)
            $(document).on('click', '#departmentsTable .editBtn', function () {
                var id = $(this).data('id');
                $('#editForm').attr('action', "{{ url('departments') }}/" + id);

                $('#editName').val($(this).data('name'));
                $('#editDescription').val($(this).data('description'));
                $('#editFacility').val($(this).data('facility'));
            });

            setTimeout(function () {
                $('.alert-success').fadeOut('slow');
            }, 5000);
        });
    </script>
@endpush

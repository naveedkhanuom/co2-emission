@extends('layouts.app')

@section('title', 'Facilities')
@section('page-title', 'Facilities')

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="facilities-app container-fluid mt-4">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button class="btn-close" data-bs-dismiss="alert"></button>
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
            <h2><span class="sb"><i class="fas fa-warehouse"></i></span> Facilities</h2>
            <p>View and manage your company facilities used across data entry and reporting.</p>
            <button type="button" class="btn-add" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fas fa-plus"></i> Add Facility
            </button>
        </div>

        <!-- DataTable Card -->
        <div class="card facilities-datatable-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                <h5 class="mb-0">Facilities List</h5>
                <div class="input-group" style="width: 280px;">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" id="searchInput" class="form-control" placeholder="Search facilities...">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="facilitiesTable">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>Name</th>
                                <th>City</th>
                                <th>State</th>
                                <th>Country</th>
                                <th width="150" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($facilities as $facility)
                                <tr>
                                    <td></td>
                                    <td>{{ $facility->name }}</td>
                                    <td>{{ $facility->city }}</td>
                                    <td>{{ $facility->state }}</td>
                                    <td>{{ $facility->country }}</td>
                                    <td class="text-center">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-warning editBtn"
                                            data-id="{{ $facility->id }}"
                                            data-name="{{ $facility->name }}"
                                            data-description="{{ $facility->description }}"
                                            data-address="{{ $facility->address }}"
                                            data-city="{{ $facility->city }}"
                                            data-state="{{ $facility->state }}"
                                            data-country="{{ $facility->country }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editModal">
                                            <i class="fas fa-edit me-1"></i>Edit
                                        </button>

                                        <form method="POST"
                                              action="{{ route('facilities.destroy', $facility->id) }}"
                                              class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Delete this facility?')">
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
                        Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of <span id="totalCount">0</span> facilities
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================= ADD MODAL ================= -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('facilities.store') }}">
                @csrf

                <div class="modal-header">
                    <div class="icon-circle">
                        <i class="bi bi-geo-alt"></i>
                    </div>
                    <h5 class="modal-title">Register New Facility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-danger @if(!$errors->any()) d-none @endif" id="addFormErrors">
                        @if($errors->any())
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Facility Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g., Head Office">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Country</label>
                            <input type="text" name="country" class="form-control" placeholder="e.g., Pakistan">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">State</label>
                            <input type="text" name="state" class="form-control" placeholder="e.g., Punjab">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control" placeholder="e.g., Lahore">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control" placeholder="Street / Area / Zip">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Optional notes about this facility"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save-fill me-1"></i> Save Facility
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= EDIT MODAL ================= -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" id="editForm">
                @csrf
                @method('PUT')

                <div class="modal-header">
                    <div class="icon-circle">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                    <h5 class="modal-title">Edit Facility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="editFormErrors" class="alert alert-danger d-none"></div>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Facility Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="editName" class="form-control" required placeholder="e.g., Head Office">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Country</label>
                            <input type="text" name="country" id="editCountry" class="form-control" placeholder="e.g., Pakistan">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">State</label>
                            <input type="text" name="state" id="editState" class="form-control" placeholder="e.g., Punjab">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">City</label>
                            <input type="text" name="city" id="editCity" class="form-control" placeholder="e.g., Lahore">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" id="editAddress" class="form-control" placeholder="Street / Area / Zip">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="editDescription" class="form-control" rows="3" placeholder="Optional notes about this facility"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check2-circle me-1"></i> Update Facility
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        .facilities-app * { box-sizing: border-box; }
        .facilities-app { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

        /* Topbar */
        .facilities-app .topbar {
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
        .facilities-app .topbar h2 {
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
            color: var(--gray-800);
        }
        .facilities-app .topbar h2 .sb {
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
        .facilities-app .topbar p {
            color: var(--gray-600);
            font-size: 0.875rem;
            flex: 1;
            min-width: 180px;
            margin: 0;
            line-height: 1.4;
        }
        .facilities-app .btn-add {
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
        .facilities-app .btn-add:hover {
            background: linear-gradient(135deg, var(--dark-green) 0%, var(--primary-green) 100%);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(46,125,50,.35);
        }

        /* Alerts */
        .facilities-app .alert { border-radius: 12px; border: 1px solid transparent; }
        .facilities-app .alert-success { background: rgba(76,175,80,0.1); border-color: rgba(76,175,50,0.25); color: var(--dark-green); }
        .facilities-app .alert-danger { background: rgba(211,47,47,0.08); border-color: rgba(211,47,47,0.2); color: var(--danger-red); }

        /* DataTable card (match system look) */
        .facilities-app .facilities-datatable-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,.06);
        }
        .facilities-app .facilities-datatable-card .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--gray-200);
            background: linear-gradient(180deg, var(--gray-50) 0%, #fff 100%);
        }
        .facilities-app .facilities-datatable-card .card-header h5 {
            font-size: 1.0625rem;
            font-weight: 700;
            color: var(--gray-800);
        }
        .facilities-app .facilities-datatable-card .card-header .input-group {
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid var(--gray-200);
        }
        .facilities-app .facilities-datatable-card .card-header .input-group-text {
            background: var(--gray-50);
            border: none;
            color: var(--gray-600);
            padding: 10px 14px;
        }
        .facilities-app .facilities-datatable-card .card-header .form-control {
            border: none;
            padding: 10px 14px;
            font-size: 0.875rem;
        }

        .facilities-app .facilities-datatable-card #facilitiesTable { width: 100% !important; border-collapse: separate; border-spacing: 0; }
        .facilities-app .facilities-datatable-card #facilitiesTable thead th {
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
        .facilities-app .facilities-datatable-card #facilitiesTable thead th:first-child { padding-left: 20px; }
        .facilities-app .facilities-datatable-card #facilitiesTable tbody td {
            padding: 14px 16px;
            font-size: 0.875rem;
            color: var(--gray-800);
            border: none;
            border-bottom: 1px solid var(--gray-100);
            vertical-align: middle;
        }
        .facilities-app .facilities-datatable-card #facilitiesTable tbody td:first-child { padding-left: 20px; }
        .facilities-app .facilities-datatable-card #facilitiesTable tbody tr:hover td { background: var(--gray-50); }
        .facilities-app .facilities-datatable-card #facilitiesTable tbody tr:last-child td { border-bottom: none; }

        .facilities-app .facilities-datatable-card .card-footer {
            padding: 12px 20px;
            border-top: 1px solid var(--gray-200);
            background: var(--gray-50);
            font-size: 0.8125rem;
            color: var(--gray-600);
        }

        .facilities-app .facilities-datatable-card .dataTables_wrapper { padding: 0; }
        .facilities-app .facilities-datatable-card .dataTables_wrapper .dataTables_length,
        .facilities-app .facilities-datatable-card .dataTables_wrapper .dataTables_filter { display: none; }
        .facilities-app .facilities-datatable-card .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 6px 12px;
            margin: 0 2px;
            border-radius: 8px;
            border: 1px solid var(--gray-200);
            background: #fff;
            color: var(--gray-700) !important;
            font-size: 0.8125rem;
            font-weight: 600;
        }
        .facilities-app .facilities-datatable-card .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: var(--gray-100) !important;
            border-color: var(--gray-300) !important;
            color: var(--gray-800) !important;
        }
        .facilities-app .facilities-datatable-card .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--primary-green) !important;
            border-color: var(--primary-green) !important;
            color: #fff !important;
        }
        .facilities-app .facilities-datatable-card .dataTables_wrapper .dataTables_processing {
            background: rgba(255,255,255,0.95);
            border-radius: 10px;
            padding: 14px 24px;
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--gray-700);
            border: 1px solid var(--gray-200);
            box-shadow: 0 2px 8px rgba(0,0,0,.06);
        }
        .facilities-app .facilities-datatable-card .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }

        /* Modal icon circle (match system popup style) */
        .facilities-app .modal-header { position: relative; }
        .facilities-app .icon-circle {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%);
            color: #fff;
            box-shadow: 0 4px 12px rgba(46,125,50,.25);
            flex: 0 0 auto;
        }
        .facilities-app .modal-title { font-weight: 700; color: var(--gray-800); }
    </style>
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function () {
        var table = $('#facilitiesTable').DataTable({
            processing: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            order: [[1, 'asc']],
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
                emptyTable: 'No facilities found',
                zeroRecords: 'No matching facilities found',
                info: 'Showing _START_ to _END_ of _TOTAL_ facilities',
                infoEmpty: 'Showing 0 to 0 of 0 facilities',
                infoFiltered: '(filtered from _MAX_ total facilities)',
                search: '',
                searchPlaceholder: 'Search...',
                lengthMenu: 'Show _MENU_ facilities',
                paginate: {
                    first: '<i class="fas fa-angle-double-left"></i>',
                    previous: '<i class="fas fa-angle-left"></i>',
                    next: '<i class="fas fa-angle-right"></i>',
                    last: '<i class="fas fa-angle-double-right"></i>'
                }
            },
            dom: 'rt<"row mt-3"<"col-sm-12"p>>',
        });

        $('#searchInput').on('keyup', function () {
            table.search(this.value).draw();
        });

        // Edit Facility (delegated so it works after DataTables redraw)
        $(document).on('click', '#facilitiesTable .editBtn', function () {
            var id = $(this).data('id');
            $('#editForm').attr('action', "{{ url('facilities') }}/" + id);

            $('#editName').val($(this).data('name'));
            $('#editDescription').val($(this).data('description'));
            $('#editAddress').val($(this).data('address'));
            $('#editCity').val($(this).data('city'));
            $('#editState').val($(this).data('state'));
            $('#editCountry').val($(this).data('country'));
        });

        setTimeout(function () {
            $('.alert-success').fadeOut('slow');
        }, 5000);
    });
</script>
@endpush

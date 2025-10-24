@extends('layouts.app')

@section('title', 'Emission Records')
@section('page-title', 'Emission Records')

@section('content')
    <div class="container-fluid py-4">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 mb-0">Emission Records List</h2>
            <button class="btn btn-primary" id="addRecordBtn">Add Emission Record</button>
        </div>

        <!-- DataTable -->
        <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered" id="emissionRecordsTable">
                <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Company</th>
                    <th>Site</th>
                    <th>User</th>
                    <th>Source</th>
                    <th>Factor</th>
                    <th>Record Date</th>
                    <th>Activity Data</th>
                    <th>Emission Value</th>
                    <th>Unit</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <!-- Modal for Add/Edit -->
    <div class="modal fade" id="recordModal" tabindex="-1" aria-labelledby="recordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form id="recordForm">
                    <input type="hidden" name="id" id="recordId">
                    <div class="modal-header">
                        <div class="icon-circle">
                            <i class="bi bi-bar-chart-line"></i>
                        </div>
                        <h5 class="modal-title" id="modalTitle">Add Emission Record</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="formErrors" class="alert alert-danger d-none"></div>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label">Company <span class="text-danger">*</span></label>
                                <select name="company_id" id="company_id" class="form-select" required>
                                    <option value="">Select Company</option>
                                    @foreach($companies as $company)
                                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Site</label>
                                <select name="site_id" id="site_id" class="form-select">
                                    <option value="">Select Site</option>
                                    @foreach($sites as $site)
                                        <option value="{{ $site->id }}">{{ $site->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">User</label>
                                <select name="user_id" id="user_id" class="form-select">
                                    <option value="">Select User</option>
                                    @foreach(\App\Models\User::all() as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>

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
                                <label class="form-label">Emission Factor</label>
                                <select name="emission_factor_id" id="emission_factor_id" class="form-select">
                                    <option value="">Select Factor</option>
                                    @foreach($factors as $factor)
                                        <option value="{{ $factor->id }}">{{ $factor->name }} ({{ $factor->factor_value }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Record Date <span class="text-danger">*</span></label>
                                <input type="date" name="record_date" id="record_date" class="form-control" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Activity Data <span class="text-danger">*</span></label>
                                <input type="number" name="activity_data" id="activity_data" class="form-control" step="0.0001" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Unit</label>
                                <input type="text" name="unit" id="unit" class="form-control" placeholder="e.g., liters, kWh">
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i> Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save-fill me-1"></i> Save Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Modal -->
    <div class="modal fade" id="viewRecordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">Emission Record Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-2"><strong>Company:</strong> <span id="viewCompany">-</span></div>
                        <div class="col-md-6 mb-2"><strong>Site:</strong> <span id="viewSite">-</span></div>
                        <div class="col-md-6 mb-2"><strong>User:</strong> <span id="viewUser">-</span></div>
                        <div class="col-md-6 mb-2"><strong>Source:</strong> <span id="viewSource">-</span></div>
                        <div class="col-md-6 mb-2"><strong>Factor:</strong> <span id="viewFactor">-</span></div>
                        <div class="col-md-6 mb-2"><strong>Date:</strong> <span id="viewDate">-</span></div>
                        <div class="col-md-6 mb-2"><strong>Activity:</strong> <span id="viewActivity">-</span></div>
                        <div class="col-md-6 mb-2"><strong>Emission Value:</strong> <span id="viewEmission">-</span></div>
                        <div class="col-md-6 mb-2"><strong>Unit:</strong> <span id="viewUnit">-</span></div>
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
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
        });

        $(function () {
            var modal = new bootstrap.Modal(document.getElementById('recordModal'));
            var viewModal = new bootstrap.Modal(document.getElementById('viewRecordModal'));

            var table = $('#emissionRecordsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('emission_records.data') }}",
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'company.name', name: 'company.name' },
                    { data: 'site.name', name: 'site.name' },
                    { data: 'user.name', name: 'user.name' },
                    { data: 'emission_source.name', name: 'emission_source.name' },
                    { data: 'emission_factor.name', name: 'emission_factor.name' },
                    { data: 'record_date', name: 'record_date' },
                    { data: 'activity_data', name: 'activity_data' },
                    { data: 'emission_value', name: 'emission_value' },
                    { data: 'unit', name: 'unit' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                ]
            });

            // Add Record
            $('#addRecordBtn').click(function(){
                $('#recordForm')[0].reset();
                $('#recordId').val('');
                $('#modalTitle').text('Add Emission Record');
                $('#formErrors').html('').addClass('d-none');
                modal.show();
            });

            // Submit Add/Edit
            $('#recordForm').submit(function(e){
                e.preventDefault();
                $.post("{{ route('emission_records.storeOrUpdate') }}", $(this).serialize(), function(res){
                    table.ajax.reload();
                    modal.hide();
                    Swal.fire('Success', res.message, 'success');
                }).fail(function(err){
                    if(err.status===422){
                        let errors = err.responseJSON.errors;
                        let html = '<ul>';
                        $.each(errors, function(k,v){ html += '<li>'+v[0]+'</li>'; });
                        html += '</ul>';
                        $('#formErrors').html(html).removeClass('d-none');
                    } else {
                        Swal.fire('Error','Something went wrong!','error');
                    }
                });
            });

            // Edit Record
            $('#emissionRecordsTable').on('click', '.editBtn', function(){
                var id = $(this).data('id');
                $.get("{{ url('emission-records') }}/"+id, function(data){
                    $('#recordId').val(data.id);
                    $('#company_id').val(data.company_id);
                    $('#site_id').val(data.site_id);
                    $('#user_id').val(data.user_id);
                    $('#emission_source_id').val(data.emission_source_id);
                    $('#emission_factor_id').val(data.emission_factor_id);
                    $('#record_date').val(data.record_date);
                    $('#activity_data').val(data.activity_data);
                    $('#unit').val(data.unit);
                    $('#modalTitle').text('Edit Emission Record');
                    $('#formErrors').html('').addClass('d-none');
                    modal.show();
                });
            });

            // View Record
            $('#emissionRecordsTable').on('click', '.viewBtn', function(){
                var id = $(this).data('id');
                $.get("{{ url('emission-records') }}/"+id, function(data){
                    $('#viewCompany').text(data.company?.name ?? '-');
                    $('#viewSite').text(data.site?.name ?? '-');
                    $('#viewUser').text(data.user?.name ?? '-');
                    $('#viewSource').text(data.emission_source?.name ?? '-');
                    $('#viewFactor').text(data.emission_factor?.name ?? '-');
                    $('#viewDate').text(data.record_date);
                    $('#viewActivity').text(data.activity_data);
                    $('#viewEmission').text(data.emission_value ?? '-');
                    $('#viewUnit').text(data.unit ?? '-');
                    viewModal.show();
                });
            });

            // Delete Record
            $('#emissionRecordsTable').on('click', '.deleteBtn', function(){
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton:true,
                    confirmButtonColor:'#3085d6',
                    cancelButtonColor:'#d33',
                    confirmButtonText:'Yes, delete it!'
                }).then((result)=>{
                    if(result.isConfirmed){
                        $.ajax({
                            url:"{{ url('emission-records') }}/"+id,
                            type:'DELETE',
                            data:{_token:"{{ csrf_token() }}"},
                            success:function(res){
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

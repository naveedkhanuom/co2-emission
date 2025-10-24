@extends('layouts.app')

@section('title', 'Reports')
@section('page-title', 'Reports')

@section('content')
    <div class="container-fluid py-4">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 mb-0">Reports List</h2>
            <button class="btn btn-primary" id="addReportBtn">Add Report</button>
        </div>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered" id="reportsTable">
                <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Company</th>
                    <th>Site</th>
                    <th>Report Name</th>
                    <th>Period</th>
                    <th>Generated At</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <!-- Modal for Add/Edit -->
    <div class="modal fade" id="reportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form id="reportForm">
                    <input type="hidden" name="id" id="reportId">
                    <div class="modal-header">
                        <div class="icon-circle">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <h5 class="modal-title" id="modalTitle">Add Report</h5>
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
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Report Name <span class="text-danger">*</span></label>
                                <input type="text" name="report_name" id="report_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Period <span class="text-danger">*</span></label>
                                <input type="text" name="period" id="period" class="form-control" placeholder="e.g., 2025-Q1" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Generated At <span class="text-danger">*</span></label>
                                <input type="date" name="generated_at" id="generated_at" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i> Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save-fill me-1"></i> Save Report</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Modal -->
    <div class="modal fade" id="viewReportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">Report Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-2"><strong>Company:</strong> <span id="viewCompany">-</span></div>
                        <div class="col-md-6 mb-2"><strong>Site:</strong> <span id="viewSite">-</span></div>
                        <div class="col-md-6 mb-2"><strong>Report Name:</strong> <span id="viewReportName">-</span></div>
                        <div class="col-md-6 mb-2"><strong>Period:</strong> <span id="viewPeriod">-</span></div>
                        <div class="col-md-6 mb-2"><strong>Generated At:</strong> <span id="viewGeneratedAt">-</span></div>
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
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

        $(function () {
            var modal = new bootstrap.Modal(document.getElementById('reportModal'));
            var viewModal = new bootstrap.Modal(document.getElementById('viewReportModal'));

            // Load Sites dynamically when Company changes
            $('#company_id').change(function() {
                var companyId = $(this).val();
                if(companyId) {
                    $.get('/api/companies/' + companyId + '/sites', function(data) {
                        var options = '<option value="">Select Site</option>';
                        $.each(data, function(i, site){ options += '<option value="'+site.id+'">'+site.name+'</option>'; });
                        $('#site_id').html(options);
                    });
                } else { $('#site_id').html('<option value="">Select Site</option>'); }
            });

            var table = $('#reportsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('reports.data') }}",
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'company.name', name: 'company.name' },
                    { data: 'site.name', name: 'site.name' },
                    { data: 'report_name', name: 'report_name' },
                    { data: 'period', name: 'period' },
                    { data: 'generated_at', name: 'generated_at' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                ]
            });

            // Add Report
            $('#addReportBtn').click(function() {
                $('#reportForm')[0].reset();
                $('#reportId').val('');
                $('#modalTitle').text('Add Report');
                $('#formErrors').html('').addClass('d-none');
                modal.show();
            });

            // Submit Add/Edit
            $('#reportForm').submit(function(e){
                e.preventDefault();
                $.post("{{ route('reports.storeOrUpdate') }}", $(this).serialize())
                    .done(function(res){ table.ajax.reload(); modal.hide(); Swal.fire('Success', res.message, 'success'); })
                    .fail(function(err){
                        if(err.status === 422) {
                            let html = '<ul>';
                            $.each(err.responseJSON.errors, function(key, val){ html += '<li>'+val[0]+'</li>'; });
                            html += '</ul>';
                            $('#formErrors').html(html).removeClass('d-none');
                        } else { Swal.fire('Error','Something went wrong','error'); }
                    });
            });

            // Edit
            $('#reportsTable').on('click','.editBtn',function(){
                var id = $(this).data('id');
                $.get("/reports/" + id, function(data){
                    $('#reportId').val(data.id);
                    $('#company_id').val(data.company_id).trigger('change');
                    setTimeout(function(){ $('#site_id').val(data.site_id); }, 200);
                    $('#report_name').val(data.report_name);
                    $('#period').val(data.period);
                    $('#generated_at').val(data.generated_at);
                    $('#modalTitle').text('Edit Report');
                    $('#formErrors').html('').addClass('d-none');
                    modal.show();
                });
            });

            // View
            $('#reportsTable').on('click','.viewBtn',function(){
                var id = $(this).data('id');
                $.get("/reports/" + id, function(data){
                    $('#viewCompany').text(data.company.name);
                    $('#viewSite').text(data.site?.name ?? '-');
                    $('#viewReportName').text(data.report_name);
                    $('#viewPeriod').text(data.period);
                    $('#viewGeneratedAt').text(data.generated_at);
                    viewModal.show();
                });
            });

            // Delete
            $('#reportsTable').on('click','.deleteBtn',function(){
                var id = $(this).data('id');
                Swal.fire({
                    title:'Are you sure?',
                    text:"You won't be able to revert this!",
                    icon:'warning',
                    showCancelButton:true,
                    confirmButtonColor:'#3085d6',
                    cancelButtonColor:'#d33',
                    confirmButtonText:'Yes, delete it!'
                }).then((result)=>{
                    if(result.isConfirmed){
                        $.ajax({
                            url:'/reports/'+id,
                            type:'DELETE',
                            data:{ _token: "{{ csrf_token() }}" },
                            success:function(res){ table.ajax.reload(); Swal.fire('Deleted!', res.message, 'success'); }
                        });
                    }
                });
            });
        });
    </script>
@endpush

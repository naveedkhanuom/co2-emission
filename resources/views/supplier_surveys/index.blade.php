@extends('layouts.app')

@section('title', 'Supplier Surveys')
@section('page-title', 'Supplier Surveys')

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="container mt-4">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                {{ session('error') }}
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Header Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0">
                            <i class="fas fa-clipboard-list me-2 text-primary"></i>Supplier Surveys
                        </h4>
                        <p class="text-muted mb-0 mt-1">Create and manage surveys to collect emission data from suppliers</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fas fa-plus me-1"></i>Create New Survey
                    </button>
                </div>
            </div>
        </div>

        <!-- DataTable Card -->
        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-table me-2"></i>Surveys List
                    </h5>
                    <div class="d-flex gap-2">
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" id="searchInput" class="form-control" placeholder="Search surveys...">
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="surveysTable">
                        <thead class="table-light">
                            <tr>
                                <th width="50">#</th>
                                <th>Title</th>
                                <th>Supplier</th>
                                <th>Status</th>
                                <th>Due Date</th>
                                <th>Completion</th>
                                <th>Reminders</th>
                                <th width="180" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be loaded via DataTables -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================= ADD MODAL ================= -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <form id="addForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Survey</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Survey Title *</label>
                            <input type="text" name="title" class="form-control" required placeholder="e.g., 2024 Emissions Data Request">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Supplier *</label>
                            <select name="supplier_id" id="supplierSelect" class="form-select" required>
                                <option value="">Select Supplier</option>
                                <!-- Will be populated via AJAX -->
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Survey Type *</label>
                            <select name="survey_type" class="form-select" required>
                                <option value="emissions_data">Emissions Data</option>
                                <option value="general">General Information</option>
                                <option value="specific_category">Specific Category</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Due Date *</label>
                            <input type="date" name="due_date" class="form-control" required min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Survey description and instructions..."></textarea>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Questions *</label>
                            <div id="questionsContainer">
                                <div class="question-item mb-3 p-3 border rounded">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <strong>Question 1</strong>
                                        <button type="button" class="btn btn-sm btn-danger removeQuestion" style="display:none;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <input type="text" name="questions[]" class="form-control mb-2" placeholder="Enter question text" required>
                                    <select name="question_types[]" class="form-select">
                                        <option value="text">Text</option>
                                        <option value="number">Number</option>
                                        <option value="date">Date</option>
                                        <option value="yes_no">Yes/No</option>
                                        <option value="multiple_choice">Multiple Choice</option>
                                    </select>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-secondary" id="addQuestionBtn">
                                <i class="fas fa-plus"></i> Add Question
                            </button>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Survey</button>
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
                <h5 class="modal-title">Survey Details</h5>
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
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<style>
    #surveysTable_wrapper .dataTables_filter {
        display: none;
    }
    
    #surveysTable thead th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #dee2e6;
    }
    
    #surveysTable tbody tr {
        transition: all 0.2s ease;
    }
    
    #surveysTable tbody tr:hover {
        background-color: #f8f9fa;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .question-item {
        background-color: #f8f9fa;
    }
</style>
@endpush

@push('scripts')
<!-- jQuery and DataTables JS -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        // Load suppliers for dropdown
        $.ajax({
            url: '{{ route("suppliers.list") }}',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    response.data.forEach(function(supplier) {
                        $('#supplierSelect').append('<option value="' + supplier.id + '">' + supplier.name + '</option>');
                    });
                }
            }
        });

        // Add Question
        let questionCount = 1;
        $('#addQuestionBtn').on('click', function() {
            questionCount++;
            var questionHtml = `
                <div class="question-item mb-3 p-3 border rounded">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>Question ${questionCount}</strong>
                        <button type="button" class="btn btn-sm btn-danger removeQuestion">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <input type="text" name="questions[]" class="form-control mb-2" placeholder="Enter question text" required>
                    <select name="question_types[]" class="form-select">
                        <option value="text">Text</option>
                        <option value="number">Number</option>
                        <option value="date">Date</option>
                        <option value="yes_no">Yes/No</option>
                        <option value="multiple_choice">Multiple Choice</option>
                    </select>
                </div>
            `;
            $('#questionsContainer').append(questionHtml);
        });

        // Remove Question
        $(document).on('click', '.removeQuestion', function() {
            if ($('.question-item').length > 1) {
                $(this).closest('.question-item').remove();
                // Renumber questions
                $('.question-item').each(function(index) {
                    $(this).find('strong').text('Question ' + (index + 1));
                });
                questionCount = $('.question-item').length;
            }
        });

        // Show remove button if more than one question
        if ($('.question-item').length > 1) {
            $('.removeQuestion').show();
        }

        // Initialize DataTable
        var table = $('#surveysTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("supplier_surveys.data") }}',
                error: function(xhr, error, thrown) {
                    console.error('DataTables error:', error);
                    alert('Error loading surveys data. Please refresh the page.');
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
                    data: 'title', 
                    name: 'title',
                    orderable: true,
                    searchable: true
                },
                { 
                    data: 'supplier_name', 
                    name: 'supplier_name',
                    orderable: true,
                    searchable: true
                },
                { 
                    data: 'status_badge', 
                    name: 'status',
                    orderable: true,
                    searchable: false
                },
                { 
                    data: 'due_date_formatted', 
                    name: 'due_date',
                    orderable: true,
                    searchable: false
                },
                { 
                    data: 'completion_percentage', 
                    name: 'completion_percentage',
                    orderable: false,
                    searchable: false
                },
                { 
                    data: 'reminder_count', 
                    name: 'reminder_count',
                    orderable: true,
                    searchable: false,
                    render: function(data) {
                        return data || 0;
                    }
                },
                { 
                    data: 'actions', 
                    name: 'actions',
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                }
            ],
            order: [[0, 'desc']],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            language: {
                processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
                emptyTable: 'No surveys found',
                zeroRecords: 'No matching surveys found',
                info: 'Showing _START_ to _END_ of _TOTAL_ surveys',
                infoEmpty: 'Showing 0 to 0 of 0 surveys',
                infoFiltered: '(filtered from _MAX_ total surveys)',
                search: '',
                searchPlaceholder: 'Search...',
                lengthMenu: 'Show _MENU_ surveys',
                paginate: {
                    first: '<i class="fas fa-angle-double-left"></i>',
                    previous: '<i class="fas fa-angle-left"></i>',
                    next: '<i class="fas fa-angle-right"></i>',
                    last: '<i class="fas fa-angle-double-right"></i>'
                }
            },
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        });
        
        // Custom search input
        $('#searchInput').on('keyup', function() {
            table.search(this.value).draw();
        });

        // Add Survey Form
        $('#addForm').on('submit', function(e) {
            e.preventDefault();
            
            // Collect questions
            var questions = [];
            $('input[name="questions[]"]').each(function(index) {
                var questionText = $(this).val();
                var questionType = $('select[name="question_types[]"]').eq(index).val();
                if (questionText) {
                    questions.push({
                        question: questionText,
                        type: questionType
                    });
                }
            });

            if (questions.length === 0) {
                alert('Please add at least one question');
                return;
            }

            var formData = {
                supplier_id: $('#supplierSelect').val(),
                title: $('input[name="title"]').val(),
                description: $('textarea[name="description"]').val(),
                survey_type: $('select[name="survey_type"]').val(),
                due_date: $('input[name="due_date"]').val(),
                questions: questions,
                _token: $('input[name="_token"]').val()
            };
            
            $.ajax({
                url: '{{ route("supplier_surveys.store") }}',
                method: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        $('#addModal').modal('hide');
                        $('#addForm')[0].reset();
                        $('#questionsContainer').html(`
                            <div class="question-item mb-3 p-3 border rounded">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong>Question 1</strong>
                                    <button type="button" class="btn btn-sm btn-danger removeQuestion" style="display:none;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <input type="text" name="questions[]" class="form-control mb-2" placeholder="Enter question text" required>
                                <select name="question_types[]" class="form-select">
                                    <option value="text">Text</option>
                                    <option value="number">Number</option>
                                    <option value="date">Date</option>
                                    <option value="yes_no">Yes/No</option>
                                    <option value="multiple_choice">Multiple Choice</option>
                                </select>
                            </div>
                        `);
                        questionCount = 1;
                        table.ajax.reload();
                        $('.alert-success').remove();
                        $('.container').prepend('<div class="alert alert-success alert-dismissible fade show">' + response.message + '<button class="btn-close" data-bs-dismiss="alert"></button></div>');
                        setTimeout(function() {
                            $('.alert-success').fadeOut('slow');
                        }, 5000);
                    }
                },
                error: function(xhr) {
                    var errors = xhr.responseJSON?.errors || {};
                    var errorMsg = xhr.responseJSON?.message || 'Error creating survey';
                    alert(errorMsg);
                }
            });
        });

        // View Survey
        $(document).on('click', '.viewBtn', function() {
            var id = $(this).data('id');
            
            $.ajax({
                url: '/supplier-surveys/' + id,
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        var survey = response.survey;
                        var html = '<div class="row g-3">';
                        html += '<div class="col-md-6"><strong>Title:</strong> ' + (survey.title || 'N/A') + '</div>';
                        html += '<div class="col-md-6"><strong>Supplier:</strong> ' + (survey.supplier?.name || 'N/A') + '</div>';
                        html += '<div class="col-md-6"><strong>Status:</strong> <span class="badge bg-info">' + survey.status + '</span></div>';
                        html += '<div class="col-md-6"><strong>Due Date:</strong> ' + (survey.due_date ? new Date(survey.due_date).toLocaleDateString() : 'N/A') + '</div>';
                        html += '<div class="col-md-12"><strong>Description:</strong> ' + (survey.description || 'N/A') + '</div>';
                        
                        if (survey.questions && survey.questions.length > 0) {
                            html += '<div class="col-md-12"><strong>Questions:</strong><ul class="mt-2">';
                            survey.questions.forEach(function(q, index) {
                                html += '<li>' + (q.question || q) + '</li>';
                            });
                            html += '</ul></div>';
                        }
                        
                        if (survey.responses && Object.keys(survey.responses).length > 0) {
                            html += '<div class="col-md-12"><strong>Responses:</strong><ul class="mt-2">';
                            Object.keys(survey.responses).forEach(function(key) {
                                html += '<li><strong>' + key + ':</strong> ' + survey.responses[key] + '</li>';
                            });
                            html += '</ul></div>';
                        }
                        
                        html += '</div>';
                        $('#viewModalBody').html(html);
                        $('#viewModal').modal('show');
                    }
                }
            });
        });

        // Send Survey
        $(document).on('click', '.sendBtn', function() {
            var id = $(this).data('id');
            
            if (confirm('Send this survey to the supplier?')) {
                $.ajax({
                    url: '/supplier-surveys/' + id + '/send',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            table.ajax.reload();
                            $('.alert-success').remove();
                            $('.container').prepend('<div class="alert alert-success alert-dismissible fade show">' + response.message + '<button class="btn-close" data-bs-dismiss="alert"></button></div>');
                            setTimeout(function() {
                                $('.alert-success').fadeOut('slow');
                            }, 5000);
                        }
                    },
                    error: function(xhr) {
                        var errorMsg = xhr.responseJSON?.message || 'Error sending survey';
                        alert(errorMsg);
                    }
                });
            }
        });

        // Send Reminder
        $(document).on('click', '.reminderBtn', function() {
            var id = $(this).data('id');
            
            if (confirm('Send a reminder to the supplier?')) {
                $.ajax({
                    url: '/supplier-surveys/' + id + '/reminder',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            table.ajax.reload();
                            $('.alert-success').remove();
                            $('.container').prepend('<div class="alert alert-success alert-dismissible fade show">' + response.message + '<button class="btn-close" data-bs-dismiss="alert"></button></div>');
                            setTimeout(function() {
                                $('.alert-success').fadeOut('slow');
                            }, 5000);
                        }
                    },
                    error: function(xhr) {
                        var errorMsg = xhr.responseJSON?.message || 'Error sending reminder';
                        alert(errorMsg);
                    }
                });
            }
        });

        // Delete Survey
        $(document).on('click', '.deleteBtn', function() {
            var id = $(this).data('id');
            
            if (confirm('Are you sure you want to delete this survey?')) {
                $.ajax({
                    url: '/supplier-surveys/' + id,
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            table.ajax.reload();
                            $('.alert-success').remove();
                            $('.container').prepend('<div class="alert alert-success alert-dismissible fade show">' + response.message + '<button class="btn-close" data-bs-dismiss="alert"></button></div>');
                            setTimeout(function() {
                                $('.alert-success').fadeOut('slow');
                            }, 5000);
                        }
                    },
                    error: function(xhr) {
                        var errorMsg = xhr.responseJSON?.message || 'Error deleting survey';
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

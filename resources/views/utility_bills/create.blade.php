@extends('layouts.app')

@section('content')
<div id="content">
    @include('layouts.top-nav')
    
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom">
                        <h4 class="mb-0">
                            <i class="fas fa-file-upload me-2 text-primary"></i>
                            Upload Utility Bill for Emission Data Extraction
                        </h4>
                        <p class="text-muted mb-0 small">Upload electricity or fuel bills to automatically extract emission data</p>
                    </div>
                    <div class="card-body">
                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <form action="{{ route('utility.upload') }}" method="POST" enctype="multipart/form-data" id="billUploadForm">
                            @csrf
                            
                            <div class="row g-4">
                                <!-- Bill Type Selection -->
                                <div class="col-md-6">
                                    <label class="form-label fw-bold required-label">
                                        <i class="fas fa-tag me-2"></i>Bill Type
                                    </label>
                                    <select class="form-select form-select-lg" name="bill_type" id="billType" required>
                                        <option value="">Select Bill Type</option>
                                        <option value="electricity">Electricity Bill</option>
                                        <option value="fuel">Fuel Bill (Diesel/Gasoline)</option>
                                    </select>
                                    <div class="form-text">Select the type of utility bill you're uploading</div>
                                </div>

                                <!-- Facility Selection -->
                                <div class="col-md-6">
                                    <label class="form-label fw-bold required-label">
                                        <i class="fas fa-building me-2"></i>Facility
                                    </label>
                                    <select class="form-select form-select-lg" name="facility_id" id="facilitySelect" required>
                                        <option value="">Select Facility</option>
                                        @foreach($facilities as $facility)
                                            <option value="{{ $facility->id }}">{{ $facility->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Select the facility this bill belongs to</div>
                                </div>

                                <!-- Department Selection -->
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-sitemap me-2"></i>Department (Optional)
                                    </label>
                                    <select class="form-select form-select-lg" name="department_id" id="departmentSelect">
                                        <option value="">Select Department</option>
                                        @foreach($departments as $department)
                                            <option value="{{ $department->id }}" data-facility-id="{{ $department->facility_id }}">
                                                {{ $department->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Optional: Select the department</div>
                                </div>

                                <!-- Emission Source -->
                                <div class="col-md-6">
                                    <label class="form-label fw-bold required-label">
                                        <i class="fas fa-industry me-2"></i>Emission Source
                                    </label>
                                    <input type="text" 
                                           class="form-control form-control-lg" 
                                           name="emission_source" 
                                           id="emissionSource"
                                           list="emissionSourceList"
                                           placeholder="e.g., Purchased Electricity, Diesel, Gasoline"
                                           required>
                                    <datalist id="emissionSourceList">
                                        @foreach($emissionSources as $source)
                                            <option value="{{ $source->name }}">
                                        @endforeach
                                        <option value="Purchased Electricity">
                                        <option value="Diesel">
                                        <option value="Gasoline">
                                        <option value="Natural Gas">
                                    </datalist>
                                    <div class="form-text">Enter or select the emission source</div>
                                </div>

                                <!-- File Upload -->
                                <div class="col-12">
                                    <label class="form-label fw-bold required-label">
                                        <i class="fas fa-file-pdf me-2"></i>Upload Bill File
                                    </label>
                                    <div class="file-upload-area border rounded p-4 text-center" 
                                         style="background-color: #f8f9fa; border-style: dashed !important; border-width: 2px !important;"
                                         id="fileUploadArea">
                                        <input type="file" 
                                               class="form-control d-none" 
                                               name="bill_file" 
                                               id="billFile" 
                                               accept=".pdf,.jpg,.jpeg,.png"
                                               required>
                                        <div id="fileUploadContent">
                                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                            <p class="mb-2">
                                                <strong>Click to upload</strong> or drag and drop
                                            </p>
                                            <p class="text-muted small mb-0">
                                                PDF, JPG, PNG (Max 10MB)
                                            </p>
                                        </div>
                                        <div id="filePreview" class="d-none mt-3">
                                            <i class="fas fa-file fa-2x text-primary mb-2"></i>
                                            <p class="mb-0" id="fileName"></p>
                                            <button type="button" class="btn btn-sm btn-outline-danger mt-2" id="removeFile">
                                                <i class="fas fa-times me-1"></i>Remove
                                            </button>
                                        </div>
                                    </div>
                                    <div class="form-text mt-2">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Supported formats: PDF, JPG, PNG. Ensure the bill is clear and readable for best OCR results.
                                    </div>
                                </div>

                                <!-- Info Box -->
                                <div class="col-12">
                                    <div class="alert alert-info mb-0">
                                        <h6 class="alert-heading">
                                            <i class="fas fa-lightbulb me-2"></i>How it works:
                                        </h6>
                                        <ul class="mb-0 small">
                                            <li>The system will use OCR to extract text from your bill</li>
                                            <li>Key data (date, consumption, cost) will be automatically extracted</li>
                                            <li>Emission records will be created with calculated CO₂e values</li>
                                            <li>Records will be set as "draft" for your review before activation</li>
                                        </ul>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <a href="{{ route('utility.index') }}" class="btn btn-outline-secondary">
                                            <i class="fas fa-arrow-left me-2"></i>Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary btn-lg px-5" id="submitBtn">
                                            <i class="fas fa-upload me-2"></i>Upload & Process Bill
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .required-label::after {
        content: " *";
        color: #dc3545;
    }

    .file-upload-area {
        cursor: pointer;
        transition: all 0.3s;
    }

    .file-upload-area:hover {
        background-color: #e9ecef !important;
        border-color: #0277bd !important;
    }

    .file-upload-area.dragover {
        background-color: #e3f2fd !important;
        border-color: #0277bd !important;
    }

    #submitBtn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileUploadArea = document.getElementById('fileUploadArea');
    const billFile = document.getElementById('billFile');
    const fileUploadContent = document.getElementById('fileUploadContent');
    const filePreview = document.getElementById('filePreview');
    const fileName = document.getElementById('fileName');
    const removeFile = document.getElementById('removeFile');
    const submitBtn = document.getElementById('submitBtn');
    const form = document.getElementById('billUploadForm');
    const departmentSelect = document.getElementById('departmentSelect');
    const facilitySelect = document.getElementById('facilitySelect');

    // Filter departments based on selected facility
    facilitySelect.addEventListener('change', function() {
        const facilityId = this.value;
        const options = departmentSelect.querySelectorAll('option[data-facility-id]');
        
        options.forEach(option => {
            if (!facilityId || option.getAttribute('data-facility-id') === facilityId) {
                option.style.display = '';
            } else {
                option.style.display = 'none';
            }
        });
        
        if (facilityId) {
            departmentSelect.value = '';
        }
    });

    // Auto-fill emission source based on bill type
    document.getElementById('billType').addEventListener('change', function() {
        const emissionSource = document.getElementById('emissionSource');
        if (this.value === 'electricity') {
            emissionSource.value = 'Purchased Electricity';
        } else if (this.value === 'fuel') {
            emissionSource.value = 'Diesel';
        }
    });

    // File upload area click
    fileUploadArea.addEventListener('click', function(e) {
        if (e.target !== removeFile && e.target !== removeFile.querySelector('i')) {
            billFile.click();
        }
    });

    // Drag and drop
    fileUploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('dragover');
    });

    fileUploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
    });

    fileUploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            billFile.files = files;
            handleFileSelect(files[0]);
        }
    });

    // File input change
    billFile.addEventListener('change', function(e) {
        if (this.files.length > 0) {
            handleFileSelect(this.files[0]);
        }
    });

    // Remove file
    removeFile.addEventListener('click', function(e) {
        e.stopPropagation();
        billFile.value = '';
        fileUploadContent.classList.remove('d-none');
        filePreview.classList.add('d-none');
    });

    function handleFileSelect(file) {
        // Validate file type
        const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        if (!allowedTypes.includes(file.type)) {
            alert('Invalid file type. Please upload PDF, JPG, or PNG files only.');
            billFile.value = '';
            return;
        }

        // Validate file size (10MB)
        if (file.size > 10 * 1024 * 1024) {
            alert('File size exceeds 10MB limit.');
            billFile.value = '';
            return;
        }

        fileName.textContent = file.name;
        fileUploadContent.classList.add('d-none');
        filePreview.classList.remove('d-none');
    }

    // Form submission
    form.addEventListener('submit', function(e) {
        if (!billFile.files.length) {
            e.preventDefault();
            alert('Please select a file to upload.');
            return;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
    });
});
</script>
@endpush
@endsection


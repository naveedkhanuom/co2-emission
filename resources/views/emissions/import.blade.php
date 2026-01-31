@extends('layouts.app')

@section('title', 'Emissions Import')
@section('page-title', 'Import Emissions Data')

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="container mt-4">
        <!-- Header Card -->
        <div class="import-header-card mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-2"><i class="fas fa-file-import me-2 text-primary"></i>Import Emissions Data</h3>
                    <p class="text-muted mb-0">Upload and import emission records from Excel or CSV files</p>
                </div>
                <a href="{{ route('emissions.sample') }}" class="btn btn-info btn-lg">
                    <i class="fas fa-download me-2"></i>Download Sample Template
                </a>
            </div>
        </div>

        <!-- Wizard Steps -->
        <div class="wizard-container mb-4">
            <div class="wizard-steps">
                <div class="wizard-step active" id="wizard-step-1">
                    <div class="wizard-step-icon">
                        <i class="fas fa-upload"></i>
                    </div>
                    <div class="wizard-step-content">
                        <div class="wizard-step-title">Upload File</div>
                        <div class="wizard-step-desc">Select your file</div>
                    </div>
                </div>
                <div class="wizard-step-line"></div>
                <div class="wizard-step" id="wizard-step-2">
                    <div class="wizard-step-icon">
                        <i class="fas fa-columns"></i>
                    </div>
                    <div class="wizard-step-content">
                        <div class="wizard-step-title">Map Columns</div>
                        <div class="wizard-step-desc">Match your columns</div>
                    </div>
                </div>
                <div class="wizard-step-line"></div>
                <div class="wizard-step" id="wizard-step-3">
                    <div class="wizard-step-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="wizard-step-content">
                        <div class="wizard-step-title">Review & Import</div>
                        <div class="wizard-step-desc">Confirm and import</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 1: Upload -->
        <div class="import-card" id="step1">
            <div class="card-header-section">
                <h5 class="mb-0"><i class="fas fa-cloud-upload-alt me-2 text-primary"></i>Upload Your File</h5>
            </div>
            <div class="card-body-section">
                <div id="dropzone-area" class="dropzone-area">
                    <div class="dropzone-content">
                        <i class="fas fa-cloud-upload-alt dropzone-icon"></i>
                        <h5 class="dropzone-title">Drop your file here or click to browse</h5>
                        <p class="dropzone-subtitle">Supports CSV, XLS, and XLSX files up to 10MB</p>
                        <div class="dropzone-file-types">
                            <span class="badge bg-light text-dark me-2"><i class="fas fa-file-csv me-1"></i>CSV</span>
                            <span class="badge bg-light text-dark me-2"><i class="fas fa-file-excel me-1"></i>XLS</span>
                            <span class="badge bg-light text-dark"><i class="fas fa-file-excel me-1"></i>XLSX</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 2: Map Columns -->
        <div class="import-card d-none" id="step2">
            <div class="card-header-section">
                <h5 class="mb-0"><i class="fas fa-columns me-2 text-primary"></i>Map Your File Columns</h5>
                <p class="text-muted mb-0 small">Match your file columns to the system fields</p>
            </div>
            <div class="card-body-section">
                <form id="mappingForm">
                    <div class="row g-3">
                        @foreach(['entry_date','facility_id','department_id','scope','emission_source','activity_data','emission_factor','co2e_value','confidence_level','notes'] as $field)
                            <div class="col-md-6">
                                <label class="form-label mb-2">
                                    <i class="fas fa-tag me-1 text-muted"></i>
                                    {{ ucfirst(str_replace('_',' ',$field)) }}
                                    @if(in_array($field, ['entry_date','facility_id','department_id']))
                                        <span class="text-danger">*</span>
                                    @endif
                                </label>
                                <select class="form-select form-select-lg" name="{{ $field }}">
                                    <option value="">-- Select Column --</option>
                                </select>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4 pt-3 border-top">
                        <button type="button" class="btn btn-secondary btn-lg me-2" id="backToStep1">
                            <i class="fas fa-arrow-left me-2"></i>Back
                        </button>
                        <button type="button" class="btn btn-primary btn-lg" id="toStep3">
                            Next: Review <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Step 3: Review & Import -->
        <div class="import-card d-none" id="step3">
            <div class="card-header-section">
                <h5 class="mb-0"><i class="fas fa-eye me-2 text-primary"></i>Preview & Import</h5>
                <p class="text-muted mb-0 small">Review your data before importing</p>
            </div>
            <div class="card-body-section">
                <div id="previewTable" class="preview-table-wrapper"></div>
                <div class="mt-4 pt-3 border-top">
                    <div class="form-check form-check-lg mb-4">
                        <input class="form-check-input" type="checkbox" id="overwriteData">
                        <label class="form-check-label" for="overwriteData">
                            <strong>Overwrite existing data</strong>
                            <span class="d-block small text-muted">If checked, existing records with matching criteria will be updated</span>
                        </label>
                    </div>
                    <div>
                        <button type="button" class="btn btn-secondary btn-lg me-2" id="backToStep2">
                            <i class="fas fa-arrow-left me-2"></i>Back
                        </button>
                        <button type="button" class="btn btn-success btn-lg" id="importDataBtn">
                            <i class="fas fa-upload me-2"></i>Import Data
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    :root {
        --primary-green: #2e7d32;
        --light-green: #4caf50;
        --dark-green: #1b5e20;
        --primary-blue: #0277bd;
        --gray-50: #f8f9fa;
        --gray-100: #f1f3f4;
        --gray-200: #e8eaed;
        --gray-300: #dadce0;
        --gray-600: #5f6368;
        --gray-800: #3c4043;
    }

    /* Header Card */
    .import-header-card {
        background: white;
        border-radius: 12px;
        padding: 25px 30px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    /* Wizard Steps */
    .wizard-container {
        background: white;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .wizard-steps {
        display: flex;
        align-items: center;
        justify-content: space-between;
        max-width: 800px;
        margin: 0 auto;
    }

    .wizard-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        flex: 1;
        position: relative;
    }

    .wizard-step-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: var(--gray-200);
        color: var(--gray-600);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-bottom: 12px;
        transition: all 0.3s;
        border: 3px solid var(--gray-200);
    }

    .wizard-step.active .wizard-step-icon {
        background: var(--primary-green);
        color: white;
        border-color: var(--primary-green);
        box-shadow: 0 4px 12px rgba(46, 125, 50, 0.3);
    }

    .wizard-step.completed .wizard-step-icon {
        background: var(--light-green);
        color: white;
        border-color: var(--light-green);
    }

    .wizard-step-content {
        text-align: center;
    }

    .wizard-step-title {
        font-weight: 600;
        color: var(--gray-800);
        margin-bottom: 4px;
        font-size: 14px;
    }

    .wizard-step.active .wizard-step-title {
        color: var(--primary-green);
    }

    .wizard-step-desc {
        font-size: 12px;
        color: var(--gray-600);
    }

    .wizard-step-line {
        flex: 1;
        height: 3px;
        background: var(--gray-200);
        margin: 0 15px;
        margin-top: -40px;
        position: relative;
        z-index: 0;
    }

    .wizard-step.active ~ .wizard-step-line,
    .wizard-step.completed ~ .wizard-step-line {
        background: var(--light-green);
    }

    /* Import Card */
    .import-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }

    .card-header-section {
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        padding: 20px 30px;
        border-bottom: 1px solid var(--gray-200);
    }

    .card-body-section {
        padding: 30px;
    }

    /* Dropzone */
    .dropzone-area {
        border: 3px dashed var(--gray-300);
        border-radius: 12px;
        padding: 60px 20px;
        text-align: center;
        background: var(--gray-50);
        transition: all 0.3s;
        cursor: pointer;
    }

    .dropzone-area:hover,
    .dropzone-area.dz-drag-hover {
        border-color: var(--primary-green);
        background: rgba(46, 125, 50, 0.05);
    }

    .dropzone-content {
        pointer-events: none;
    }

    .dropzone-icon {
        font-size: 64px;
        color: var(--primary-green);
        margin-bottom: 20px;
        display: block;
    }

    .dropzone-title {
        color: var(--gray-800);
        font-weight: 600;
        margin-bottom: 10px;
    }

    .dropzone-subtitle {
        color: var(--gray-600);
        margin-bottom: 20px;
    }

    .dropzone-file-types {
        display: flex;
        justify-content: center;
        gap: 8px;
    }

    /* Form Styles */
    .form-label {
        font-weight: 600;
        color: var(--gray-800);
        font-size: 14px;
    }

    .form-select-lg {
        border: 1px solid var(--gray-300);
        border-radius: 8px;
        padding: 10px 15px;
        transition: all 0.3s;
    }

    .form-select-lg:focus {
        border-color: var(--primary-green);
        box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.1);
    }

    /* Preview Table */
    .preview-table-wrapper {
        background: var(--gray-50);
        border-radius: 8px;
        padding: 20px;
        max-height: 500px;
        overflow-y: auto;
    }

    .preview-table-wrapper table {
        background: white;
        border-radius: 8px;
        overflow: hidden;
    }

    .preview-table-wrapper table thead {
        background: var(--primary-green);
        color: white;
    }

    .preview-table-wrapper table thead th {
        padding: 12px 15px;
        font-weight: 600;
        border: none;
    }

    .preview-table-wrapper table tbody td {
        padding: 10px 15px;
        border-bottom: 1px solid var(--gray-200);
    }

    .preview-table-wrapper table tbody tr:hover {
        background: var(--gray-50);
    }

    /* Buttons */
    .btn-lg {
        padding: 12px 24px;
        font-weight: 600;
        border-radius: 8px;
    }

    .btn-info {
        background: var(--primary-blue);
        border-color: var(--primary-blue);
    }

    .btn-success {
        background: var(--primary-green);
        border-color: var(--primary-green);
    }

    .btn-success:hover {
        background: var(--dark-green);
        border-color: var(--dark-green);
    }

    /* Checkbox */
    .form-check-input:checked {
        background-color: var(--primary-green);
        border-color: var(--primary-green);
    }

    .form-check-lg .form-check-input {
        width: 1.25rem;
        height: 1.25rem;
        margin-top: 0.25rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .wizard-steps {
            flex-direction: column;
            gap: 20px;
        }

        .wizard-step-line {
            display: none;
        }

        .import-header-card .d-flex {
            flex-direction: column;
            gap: 15px;
            align-items: flex-start !important;
        }

        .card-body-section {
            padding: 20px;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
Dropzone.autoDiscover = false;
let uploadedFile = null;
let parsedData = [];
let headers = [];

const myDropzone = new Dropzone("#dropzone-area", {
    url: "#",
    autoProcessQueue: false,
    maxFiles: 1,
    acceptedFiles: ".csv,.xls,.xlsx",
    init: function() {
        this.on("addedfile", function(file) {
            uploadedFile = file;
            parseFile(file);
        });
        this.on("dragenter", function() {
            document.getElementById('dropzone-area').classList.add('dz-drag-hover');
        });
        this.on("dragleave", function() {
            document.getElementById('dropzone-area').classList.remove('dz-drag-hover');
        });
    }
});

function parseFile(file) {
    const reader = new FileReader();
    reader.onload = function(e) {
        const data = e.target.result;
        if(file.name.endsWith('.csv')) {
            const rows = data.split("\n").map(r => r.split(","));
            headers = rows[0];
            parsedData = rows.slice(1);
        } else {
            const workbook = XLSX.read(data, {type: 'binary'});
            const sheet = workbook.Sheets[workbook.SheetNames[0]];
            parsedData = XLSX.utils.sheet_to_json(sheet, {header:1});
            headers = parsedData[0];
            parsedData = parsedData.slice(1);
        }
        populateMappingOptions();
        goToStep(2);
    };
    if(file.name.endsWith('.csv')) reader.readAsText(file);
    else reader.readAsBinaryString(file);
}

function populateMappingOptions() {
    document.querySelectorAll('#mappingForm select').forEach(select => {
        const currentValue = select.value;
        select.innerHTML = '<option value="">-- Select Column --</option>' + 
            headers.map(h => `<option value="${h}">${h}</option>`).join('');
        if (currentValue) select.value = currentValue;
    });
}

function goToStep(step) {
    // Hide all steps
    document.getElementById('step1').classList.add('d-none');
    document.getElementById('step2').classList.add('d-none');
    document.getElementById('step3').classList.add('d-none');
    
    // Remove all active/completed classes
    document.querySelectorAll('.wizard-step').forEach(el => {
        el.classList.remove('active', 'completed');
    });
    
    // Show current step
    if(step === 1) {
        document.getElementById('step1').classList.remove('d-none');
        document.getElementById('wizard-step-1').classList.add('active');
    }
    if(step === 2) {
        document.getElementById('step2').classList.remove('d-none');
        document.getElementById('wizard-step-1').classList.add('completed');
        document.getElementById('wizard-step-2').classList.add('active');
    }
    if(step === 3) {
        document.getElementById('step3').classList.remove('d-none');
        document.getElementById('wizard-step-1').classList.add('completed');
        document.getElementById('wizard-step-2').classList.add('completed');
        document.getElementById('wizard-step-3').classList.add('active');
    }
}

document.getElementById('backToStep1').onclick = () => goToStep(1);
document.getElementById('toStep3').onclick = () => { generatePreview(); goToStep(3); };
document.getElementById('backToStep2').onclick = () => goToStep(2);

function generatePreview() {
    const mapping = Object.fromEntries([...document.querySelectorAll('#mappingForm select')].map(s => [s.name, s.value]));
    let html = '<table class="table table-striped mb-0"><thead><tr>';
    Object.values(mapping).forEach(h => html += `<th>${h}</th>`);
    html += '</tr></thead><tbody>';
    parsedData.slice(0, 10).forEach(row => {
        html += '<tr>';
        Object.values(mapping).forEach(col => {
            const idx = headers.indexOf(col);
            html += `<td>${row[idx] || ''}</td>`;
        });
        html += '</tr>';
    });
    html += '</tbody></table>';
    if (parsedData.length > 10) {
        html += `<div class="mt-3 text-center text-muted"><small>Showing first 10 rows of ${parsedData.length} total rows</small></div>`;
    }
    document.getElementById('previewTable').innerHTML = html;
}

// Toast notification function
function showToast(message, type) {
    const toast = document.createElement('div');
    toast.className = 'position-fixed bottom-0 end-0 p-3';
    toast.style.zIndex = '11';
    
    const bgColor = type === 'success' ? 'bg-success' : 
                   type === 'error' ? 'bg-danger' : 
                   type === 'warning' ? 'bg-warning' : 'bg-info';
    
    toast.innerHTML = `
        <div class="toast show" role="alert">
            <div class="toast-header ${bgColor} text-white">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 
                                type === 'error' ? 'exclamation-circle' : 
                                type === 'warning' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                <strong class="me-auto">${type.charAt(0).toUpperCase() + type.slice(1)}</strong>
                <button type="button" class="btn-close btn-close-white" onclick="this.parentElement.parentElement.remove()"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        if (toast.parentElement) {
            toast.remove();
        }
    }, 5000);
}

document.getElementById('importDataBtn').onclick = function(){
    const mapping = Object.fromEntries([...document.querySelectorAll('#mappingForm select')].map(s => [s.name, s.value]));
    const formData = new FormData();
    formData.append('file', uploadedFile);
    formData.append('overwrite', document.getElementById('overwriteData').checked ? 1 : 0);
    formData.append('mapping', JSON.stringify(mapping));

    const btn = document.getElementById('importDataBtn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Importing...';

    fetch("{{ route('emissions.import') }}", {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: formData
    })
    .then(res => {
        if (!res.ok) {
            return res.json().then(err => Promise.reject(err));
        }
        return res.json();
    })
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        if (data.status === 'success') {
            showToast(data.message, 'success');
            // Reload page after 1.5 seconds to reset the form and prevent duplicate imports
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showToast(data.message || 'Import failed', 'error');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        const errorMsg = err.message || err.error || 'Import failed. Please try again.';
        showToast(errorMsg, 'error');
        console.error('Import error:', err);
    });
};
</script>
@endpush
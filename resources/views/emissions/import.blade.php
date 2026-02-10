@extends('layouts.app')

@section('title', 'Emissions Import')
@section('page-title', 'Import Emissions Data')

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="container-fluid px-4 py-4 import-page">
        <!-- Header -->
        <div class="import-hero mb-4">
            <div class="import-hero-inner">
                <div class="import-hero-content">
                    <h1 class="import-hero-title">
                        <i class="fas fa-file-import import-hero-icon"></i>
                        Import Emissions Data
                    </h1>
                    <p class="import-hero-subtitle">Upload Excel or CSV files to bulk import emission records. Map your columns, preview the data, and import in a few clicks.</p>
                    <div class="import-hero-actions">
                        <a href="{{ route('emissions.sample') }}" class="btn btn-outline-light btn-lg rounded-pill">
                            <i class="fas fa-download me-2"></i>Download Sample Template
                        </a>
                        @if(\Illuminate\Support\Facades\Route::has('import_history.index'))
                        <a href="{{ route('import_history.index') }}" class="btn btn-light btn-lg rounded-pill ms-2">
                            <i class="fas fa-history me-2"></i>Import History
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Wizard -->
        <div class="import-wizard mb-4">
            <div class="import-wizard-steps">
                <div class="import-wizard-step active" id="wizard-step-1" data-step="1">
                    <div class="import-wizard-step-circle">
                        <span class="import-wizard-step-num">1</span>
                    </div>
                    <span class="import-wizard-step-label">Upload</span>
                </div>
                <div class="import-wizard-step-line"></div>
                <div class="import-wizard-step" id="wizard-step-2" data-step="2">
                    <div class="import-wizard-step-circle">
                        <span class="import-wizard-step-num">2</span>
                    </div>
                    <span class="import-wizard-step-label">Map Columns</span>
                </div>
                <div class="import-wizard-step-line"></div>
                <div class="import-wizard-step" id="wizard-step-3" data-step="3">
                    <div class="import-wizard-step-circle">
                        <span class="import-wizard-step-num">3</span>
                    </div>
                    <span class="import-wizard-step-label">Review & Import</span>
                </div>
            </div>
        </div>

        <!-- Step 1: Upload -->
        <div class="import-card card-shadow" id="step1">
            <div class="import-card-body">
                <div class="import-upload-zone" id="dropzone-area">
                    <input type="file" id="fileInput" accept=".csv,.xls,.xlsx" class="d-none">
                    <div class="import-upload-content">
                        <div class="import-upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <h4 class="import-upload-title">Drop your file here or click to browse</h4>
                        <p class="import-upload-subtitle">Supports CSV, XLS, and XLSX files (max 10MB)</p>
                        <div class="import-upload-badges">
                            <span class="badge">CSV</span>
                            <span class="badge">XLS</span>
                            <span class="badge">XLSX</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 2: Map Columns -->
        <div class="import-card card-shadow d-none" id="step2">
            <div class="import-card-header">
                <h5 class="mb-0"><i class="fas fa-columns me-2 text-primary"></i>Map Your Columns</h5>
                <p class="text-muted mb-0 small">Match your file columns to the system fields. Required fields are marked with <span class="text-danger">*</span>.</p>
            </div>
            <div class="import-card-body">
                <form id="mappingForm">
                    <div class="row g-4">
                        @php
                            $fields = [
                                'entry_date' => ['label' => 'Entry Date', 'required' => true, 'alt' => 'date'],
                                'facility_id' => ['label' => 'Facility', 'required' => true, 'alt' => 'facility'],
                                'department_id' => ['label' => 'Department', 'required' => true, 'alt' => 'department'],
                                'scope' => ['label' => 'Scope', 'required' => false],
                                'emission_source' => ['label' => 'Emission Source', 'required' => false],
                                'activity_data' => ['label' => 'Activity Data', 'required' => false],
                                'emission_factor' => ['label' => 'Emission Factor', 'required' => false],
                                'co2e_value' => ['label' => 'CO2e Value', 'required' => false],
                                'confidence_level' => ['label' => 'Confidence Level', 'required' => false],
                                'notes' => ['label' => 'Notes', 'required' => false],
                            ];
                        @endphp
                        @foreach($fields as $field => $opts)
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label fw-semibold">
                                    {{ $opts['label'] }}
                                    @if($opts['required'])
                                        <span class="text-danger">*</span>
                                    @endif
                                </label>
                                <select class="form-select mapping-select" name="{{ $field }}" data-field="{{ $field }}">
                                    <option value="">— Select column —</option>
                                </select>
                            </div>
                        @endforeach
                    </div>
                    <div class="import-form-actions mt-4 pt-4">
                        <button type="button" class="btn btn-outline-secondary btn-lg px-4" id="backToStep1">
                            <i class="fas fa-arrow-left me-2"></i>Back
                        </button>
                        <button type="button" class="btn btn-primary btn-lg px-4" id="toStep3">
                            Next: Review <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Step 3: Review & Import -->
        <div class="import-card card-shadow d-none" id="step3">
            <div class="import-card-header">
                <h5 class="mb-0"><i class="fas fa-eye me-2 text-primary"></i>Preview & Import</h5>
                <p class="text-muted mb-0 small">Review the first rows before importing. Unmapped columns are excluded.</p>
            </div>
            <div class="import-card-body">
                <div id="previewTable" class="import-preview-table"></div>
                <div class="form-check form-switch mt-4 py-3 px-3 rounded" style="background: var(--gray-50);">
                    <input class="form-check-input" type="checkbox" id="overwriteData">
                    <label class="form-check-label fw-semibold" for="overwriteData">
                        Overwrite existing data
                    </label>
                    <p class="small text-muted mb-0 mt-1">When enabled, matching records (same date, facility, department, emission source) will be updated instead of duplicated.</p>
                </div>
                <div class="import-form-actions mt-4">
                    <button type="button" class="btn btn-outline-secondary btn-lg px-4" id="backToStep2">
                        <i class="fas fa-arrow-left me-2"></i>Back
                    </button>
                    <button type="button" class="btn btn-success btn-lg px-5" id="importDataBtn">
                        <i class="fas fa-upload me-2"></i>Import Data
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="importSuccessModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-body text-center py-5">
                <div class="import-success-icon mb-3">
                    <i class="fas fa-check-circle" style="color: var(--primary-green);"></i>
                </div>
                <h4 class="mb-2">Import Completed</h4>
                <p class="text-muted mb-4" id="importSuccessMessage">Your data has been imported successfully.</p>
                <div class="d-flex justify-content-center gap-2 flex-wrap" id="importSuccessStats"></div>
                <div class="mt-4">
                    <a href="{{ route('emission_records.index') }}" class="btn btn-primary me-2">View Emission Records</a>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" onclick="location.reload()">Import More</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Import page – uses system color scheme */
.import-page {
    max-width: 1200px;
    margin: 0 auto;
}

.import-hero {
    background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 50%, var(--dark-green) 100%);
    border-radius: var(--radius-lg, 16px);
    padding: 2rem 2.5rem;
    color: white;
    box-shadow: 0 10px 40px rgba(46, 125, 50, 0.25);
}

.import-hero-title {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.import-hero-icon {
    font-size: 1.5rem;
    margin-right: 0.5rem;
    opacity: 0.95;
}

.import-hero-subtitle {
    opacity: 0.9;
    font-size: 1rem;
    margin-bottom: 1.5rem;
}

.import-hero-actions .btn-outline-light:hover {
    background: rgba(255,255,255,0.2);
    border-color: white;
    color: white;
}

.import-wizard {
    background: white;
    border-radius: var(--radius-md, 12px);
    padding: 1.5rem 2rem;
    box-shadow: var(--shadow-sm, 0 1px 3px rgba(0,0,0,0.06));
}

.import-wizard-steps {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0;
    flex-wrap: wrap;
}

.import-wizard-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.import-wizard-step-circle {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: var(--gray-200);
    color: var(--gray-600);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1rem;
    transition: all 0.3s;
}

.import-wizard-step.active .import-wizard-step-circle {
    background: var(--primary-green);
    color: white;
    box-shadow: 0 4px 14px rgba(46, 125, 50, 0.4);
}

.import-wizard-step.completed .import-wizard-step-circle {
    background: var(--light-green);
    color: white;
}

.import-wizard-step.completed .import-wizard-step-circle .import-wizard-step-num {
    display: none;
}

.import-wizard-step.completed .import-wizard-step-circle::after {
    content: "\f00c";
    font-family: "Font Awesome 6 Free";
    font-weight: 900;
    font-size: 1rem;
}

.import-wizard-step-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--gray-600);
}

.import-wizard-step.active .import-wizard-step-label {
    color: var(--primary-green);
}

.import-wizard-step-line {
    width: 60px;
    height: 3px;
    background: var(--gray-200);
    margin: 0 0.5rem;
    margin-top: -28px;
    border-radius: 2px;
}

.import-wizard-step.completed + .import-wizard-step-line {
    background: var(--light-green);
}

.import-card {
    background: white;
    border-radius: var(--radius-md, 12px);
    overflow: hidden;
    border: 1px solid var(--gray-200);
}

.card-shadow {
    box-shadow: var(--shadow-sm, 0 1px 3px rgba(0,0,0,0.06));
}

.import-card-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--gray-200);
    background: var(--gray-50);
}

.import-card-header h5 i.text-primary {
    color: var(--primary-green) !important;
}

.import-card-body {
    padding: 2rem 1.5rem;
}

.import-upload-zone {
    border: 2px dashed var(--gray-300);
    border-radius: var(--radius-md, 12px);
    padding: 4rem 2rem;
    text-align: center;
    background: var(--gray-50);
    cursor: pointer;
    transition: all 0.25s;
}

.import-upload-zone:hover,
.import-upload-zone.dz-drag-hover {
    border-color: var(--primary-green);
    background: rgba(46, 125, 50, 0.04);
}

.import-upload-icon {
    font-size: 3.5rem;
    color: var(--primary-green);
    margin-bottom: 1rem;
}

.import-upload-title {
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: 0.5rem;
}

.import-upload-subtitle {
    color: var(--gray-600);
    margin-bottom: 1rem;
}

.import-upload-badges .badge {
    background: var(--gray-200);
    color: var(--gray-800);
    padding: 0.35rem 0.75rem;
    font-weight: 500;
    margin: 0 0.25rem;
}

.mapping-select {
    border-radius: var(--radius-sm, 8px);
    border: 1px solid var(--gray-300);
}

.mapping-select:focus {
    border-color: var(--primary-green);
    box-shadow: 0 0 0 0.2rem rgba(46, 125, 50, 0.15);
}

.import-form-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.import-preview-table {
    background: var(--gray-50);
    border-radius: var(--radius-sm, 8px);
    padding: 1rem;
    max-height: 400px;
    overflow: auto;
}

.import-preview-table table {
    background: white;
    border-radius: 8px;
    overflow: hidden;
}

.import-preview-table thead th {
    background: var(--primary-green);
    color: white;
    padding: 0.75rem 1rem;
    font-weight: 600;
    font-size: 0.875rem;
}

.import-preview-table tbody td {
    padding: 0.6rem 1rem;
    border-bottom: 1px solid var(--gray-200);
    font-size: 0.875rem;
}

.import-preview-table tbody tr:hover {
    background: var(--gray-50);
}

.import-success-icon {
    font-size: 4rem;
}

.import-form-actions .btn-primary {
    background: var(--primary-green);
    border-color: var(--primary-green);
}

.import-form-actions .btn-primary:hover {
    background: var(--dark-green);
    border-color: var(--dark-green);
}

.import-form-actions .btn-success {
    background: var(--primary-green);
    border-color: var(--primary-green);
}

.import-form-actions .btn-success:hover {
    background: var(--dark-green);
    border-color: var(--dark-green);
}

.form-check-input:checked {
    background-color: var(--primary-green);
    border-color: var(--primary-green);
}

#importSuccessModal .btn-primary {
    background: var(--primary-green);
    border-color: var(--primary-green);
}

#importSuccessModal .btn-primary:hover {
    background: var(--dark-green);
    border-color: var(--dark-green);
}

@media (max-width: 768px) {
    .import-wizard-step-line { display: none; }
    .import-hero { padding: 1.5rem; }
    .import-hero-actions { flex-direction: column; }
}
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
(function() {
    let uploadedFile = null;
    let parsedData = [];
    let headers = [];
    const FIELD_LABELS = {
        entry_date: 'Entry Date', facility_id: 'Facility', department_id: 'Department',
        scope: 'Scope', emission_source: 'Emission Source', activity_data: 'Activity Data',
        emission_factor: 'Emission Factor', co2e_value: 'CO2e Value',
        confidence_level: 'Confidence Level', notes: 'Notes'
    };
    const AUTO_MAP = {
        'entry_date': ['entry_date', 'date', 'entry date'],
        'facility_id': ['facility', 'facility_id', 'facility name'],
        'department_id': ['department', 'department_id', 'department name'],
        'scope': ['scope', 'scope category'],
        'emission_source': ['emission_source', 'emission source', 'source'],
        'activity_data': ['activity_data', 'activity data', 'activity'],
        'emission_factor': ['emission_factor', 'emission factor', 'factor'],
        'co2e_value': ['co2e_value', 'co2e value', 'co2e'],
        'confidence_level': ['confidence_level', 'confidence level', 'confidence'],
        'notes': ['notes', 'note', 'comments']
    };

    function parseFile(file) {
        uploadedFile = file;
        const reader = new FileReader();
        reader.onload = function(e) {
            const data = e.target.result;
            let rows;
            if (file.name.toLowerCase().endsWith('.csv')) {
                try {
                    const wb = XLSX.read(data, { type: 'string', raw: true });
                    const ws = wb.Sheets[wb.SheetNames[0]];
                    rows = XLSX.utils.sheet_to_json(ws, { header: 1, defval: '' });
                } catch (err) {
                    const lines = data.split(/\r?\n/).filter(Boolean);
                    if (lines.length === 0) {
                        showToast('File is empty or invalid', 'error');
                        return;
                    }
                    rows = lines.map(line => {
                        const result = [];
                        let current = '';
                        let inQuotes = false;
                        for (let i = 0; i < line.length; i++) {
                            const c = line[i];
                            if (c === '"') inQuotes = !inQuotes;
                            else if ((c === ',' || c === ';') && !inQuotes) {
                                result.push(current.trim());
                                current = '';
                            } else current += c;
                        }
                        result.push(current.trim());
                        return result;
                    });
                }
            } else {
                const wb = XLSX.read(data, { type: 'binary' });
                const ws = wb.Sheets[wb.SheetNames[0]];
                rows = XLSX.utils.sheet_to_json(ws, { header: 1, defval: '' });
            }
            if (!rows || rows.length < 2) {
                showToast('File must have a header row and at least one data row', 'error');
                return;
            }
            headers = rows[0].map(h => String(h || '').trim()).filter(Boolean);
            parsedData = rows.slice(1).map(row => {
                const arr = [];
                for (let i = 0; i < headers.length; i++) arr[i] = row[i] != null ? row[i] : '';
                return arr;
            });
            if (headers.length === 0) {
                showToast('Could not detect column headers', 'error');
                return;
            }
            populateMappingOptions();
            autoMapColumns();
            goToStep(2);
        };
        if (file.name.toLowerCase().endsWith('.csv')) {
            reader.readAsText(file, 'UTF-8');
        } else {
            reader.readAsBinaryString(file);
        }
    }

    function autoMapColumns() {
        document.querySelectorAll('#mappingForm select').forEach(select => {
            const field = select.dataset.field || select.name;
            const targets = AUTO_MAP[field];
            if (!targets) return;
            const normalizedHeaders = headers.map(h => h.toLowerCase().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, ''));
            for (const t of targets) {
                const norm = t.toLowerCase().replace(/\s+/g, '_');
                const idx = headers.findIndex((h, i) => {
                    const nh = normalizedHeaders[i] || '';
                    return nh === norm || nh === t.toLowerCase() || (h && h.toLowerCase() === t.toLowerCase());
                });
                if (idx >= 0) {
                    select.value = headers[idx];
                    break;
                }
            }
        });
    }

    function populateMappingOptions() {
        document.querySelectorAll('#mappingForm select').forEach(select => {
            const cur = select.value;
            select.innerHTML = '<option value="">— Select column —</option>' +
                headers.map(h => `<option value="${escapeHtml(h)}">${escapeHtml(h)}</option>`).join('');
            if (cur && headers.includes(cur)) select.value = cur;
        });
    }

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function goToStep(step) {
        document.querySelectorAll('[id^="step"]').forEach(el => el.classList.add('d-none'));
        document.getElementById('step' + step).classList.remove('d-none');
        document.querySelectorAll('.import-wizard-step').forEach(el => {
            el.classList.remove('active', 'completed');
        });
        for (let i = 1; i < step; i++) {
            const s = document.getElementById('wizard-step-' + i);
            if (s) s.classList.add('completed');
        }
        const active = document.getElementById('wizard-step-' + step);
        if (active) active.classList.add('active');
    }

    function validateMapping() {
        const mapping = getMapping();
        const hasDate = !!(mapping.entry_date || mapping.date);
        const hasFacility = !!(mapping.facility_id || mapping.facility);
        const hasDepartment = !!(mapping.department_id || mapping.department);
        return { valid: hasDate && hasFacility && hasDepartment, hasDate, hasFacility, hasDepartment };
    }

    function getMapping() {
        return Object.fromEntries(
            [...document.querySelectorAll('#mappingForm select')]
                .filter(s => s.value)
                .map(s => [s.name, s.value])
        );
    }

    function generatePreview() {
        const mapping = getMapping();
        const v = validateMapping();
        if (!v.valid) {
            showToast('Please map required columns: Entry Date, Facility, and Department', 'error');
            return false;
        }
        const mappedCols = Object.entries(mapping).filter(([k,v]) => v);
        if (mappedCols.length === 0) return false;
        let html = '<table class="table table-sm mb-0"><thead><tr>';
        mappedCols.forEach(([k, v]) => html += `<th>${escapeHtml(FIELD_LABELS[k] || k)}</th>`);
        html += '</tr></thead><tbody>';
        const previewRows = parsedData.slice(0, 10);
        previewRows.forEach(row => {
            html += '<tr>';
            mappedCols.forEach(([k, col]) => {
                const idx = headers.indexOf(col);
                const val = idx >= 0 ? (row[idx] ?? '') : '';
                html += `<td>${escapeHtml(String(val))}</td>`;
            });
            html += '</tr>';
        });
        html += '</tbody></table>';
        if (parsedData.length > 10) {
            html += `<p class="mt-2 mb-0 small text-muted text-center">Showing first 10 of ${parsedData.length} rows</p>`;
        }
        document.getElementById('previewTable').innerHTML = html;
        return true;
    }

    function showToast(message, type) {
        const container = document.createElement('div');
        container.className = 'position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        const bg = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-warning';
        container.innerHTML = `
            <div class="toast show" role="alert">
                <div class="toast-header ${bg} text-white">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'exclamation-triangle'} me-2"></i>
                    <strong class="me-auto">${type === 'success' ? 'Success' : type === 'error' ? 'Error' : 'Warning'}</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">${escapeHtml(message)}</div>
            </div>`;
        document.body.appendChild(container);
        container.querySelector('[data-bs-dismiss="toast"]').onclick = () => container.remove();
        setTimeout(() => container.remove(), 5000);
    }

    // Upload zone
    const zone = document.getElementById('dropzone-area');
    const fileInput = document.getElementById('fileInput');
    zone.addEventListener('click', () => fileInput.click());
    zone.addEventListener('dragover', (e) => { e.preventDefault(); zone.classList.add('dz-drag-hover'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('dz-drag-hover'));
    zone.addEventListener('drop', (e) => {
        e.preventDefault();
        zone.classList.remove('dz-drag-hover');
        const files = e.dataTransfer?.files;
        if (files && files.length) parseFile(files[0]);
    });
    fileInput.addEventListener('change', (e) => {
        const f = e.target.files?.[0];
        if (f) parseFile(f);
        e.target.value = '';
    });

    document.getElementById('backToStep1').onclick = () => goToStep(1);
    document.getElementById('toStep3').onclick = () => {
        if (generatePreview()) goToStep(3);
    };
    document.getElementById('backToStep2').onclick = () => goToStep(2);

    document.getElementById('importDataBtn').onclick = function() {
        const v = validateMapping();
        if (!v.valid) {
            showToast('Please map required columns: Entry Date, Facility, and Department', 'error');
            return;
        }
        const mapping = getMapping();
        const formData = new FormData();
        formData.append('file', uploadedFile);
        formData.append('overwrite', document.getElementById('overwriteData').checked ? '1' : '0');
        formData.append('mapping', JSON.stringify(mapping));
        formData.append('_token', '{{ csrf_token() }}');

        const btn = document.getElementById('importDataBtn');
        const orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Importing...';

        fetch("{{ route('emissions.import') }}", {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = orig;
            if (data.status === 'success') {
                document.getElementById('importSuccessMessage').textContent = data.message || 'Import completed.';
                const stats = document.getElementById('importSuccessStats');
                stats.innerHTML = `
                    ${data.successful != null ? `<span class="badge bg-success">${data.successful} imported</span>` : ''}
                    ${data.skipped > 0 ? `<span class="badge bg-warning text-dark">${data.skipped} skipped</span>` : ''}
                    ${data.total != null ? `<span class="badge bg-secondary">${data.total} total rows</span>` : ''}
                `;
                const modal = new bootstrap.Modal(document.getElementById('importSuccessModal'));
                modal.show();
            } else {
                showToast(data.message || data.error || 'Import failed', 'error');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = orig;
            showToast(err.message || err.error || 'Import failed. Please try again.', 'error');
        });
    };

})();
</script>
@endpush

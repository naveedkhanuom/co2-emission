@extends('layouts.app')

@section('title', 'Emissions Import')
@section('page-title', 'Import Emissions Data')

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="emissions-import-app container-fluid mt-4">
        <!-- Topbar -->
        <div class="topbar">
            <h2><span class="sb"><i class="fas fa-file-import"></i></span> Import Emissions Data</h2>
            <p>Upload Excel or CSV files to bulk import emission records. Map columns, preview, and import in a few clicks.</p>
            <div class="topbar-actions">
                <a href="{{ route('emissions.sample') }}" class="btn-outline">
                    <i class="fas fa-download"></i> Download Sample
                </a>
                @if(\Illuminate\Support\Facades\Route::has('import_history.index'))
                <a href="{{ route('import_history.index') }}" class="btn-secondary">
                    <i class="fas fa-history"></i> Import History
                </a>
                @endif
            </div>
        </div>

        <!-- Wizard -->
        <div class="wizard-card">
            <div class="wizard-steps">
                <div class="wizard-step active" id="wizard-step-1" data-step="1">
                    <div class="wizard-step-circle"><span class="wizard-step-num">1</span></div>
                    <span class="wizard-step-label">Upload</span>
                </div>
                <div class="wizard-step-line"></div>
                <div class="wizard-step" id="wizard-step-2" data-step="2">
                    <div class="wizard-step-circle"><span class="wizard-step-num">2</span></div>
                    <span class="wizard-step-label">Map Columns</span>
                </div>
                <div class="wizard-step-line"></div>
                <div class="wizard-step" id="wizard-step-3" data-step="3">
                    <div class="wizard-step-circle"><span class="wizard-step-num">3</span></div>
                    <span class="wizard-step-label">Review & Import</span>
                </div>
            </div>
        </div>

        <!-- Step 1: Upload -->
        <div class="tw step-card" id="step1">
            <div class="step-body">
                <div class="fup-zone" id="dropzone-area">
                    <input type="file" id="fileInput" accept=".csv,.xls,.xlsx" class="d-none">
                    <div class="fup-content">
                        <div class="fup-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                        <div class="fup-title">Drop your file here or click to browse</div>
                        <div class="fup-sub">Supports CSV, XLS, and XLSX (max 10MB)</div>
                        <div class="fup-badges">
                            <span class="fup-badge">CSV</span>
                            <span class="fup-badge">XLS</span>
                            <span class="fup-badge">XLSX</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 2: Map Columns -->
        <div class="tw step-card d-none" id="step2">
            <div class="step-head">
                <h5 class="mb-1"><i class="fas fa-columns me-2"></i>Map Your Columns</h5>
                <p class="step-sub">Match file columns to system fields. Required fields are marked with <span class="rq">*</span>.</p>
            </div>
            <div class="step-body">
                <form id="mappingForm">
                    <div class="row g-3">
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
                                <div class="fg">
                                    <label>{{ $opts['label'] }}@if($opts['required'])<span class="rq">*</span>@endif</label>
                                    <select class="fsl mapping-select" name="{{ $field }}" data-field="{{ $field }}">
                                        <option value="">— Select column —</option>
                                    </select>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="step-actions mt-4 pt-4">
                        <button type="button" class="btn btn-bs" id="backToStep1">
                            <i class="fas fa-arrow-left me-2"></i>Back
                        </button>
                        <button type="button" class="btn btn-bp" id="toStep3">
                            Next: Review <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Step 3: Review & Import -->
        <div class="tw step-card d-none" id="step3">
            <div class="step-head">
                <h5 class="mb-1"><i class="fas fa-eye me-2"></i>Preview & Import</h5>
                <p class="step-sub">Review the first rows before importing. Unmapped columns are excluded.</p>
            </div>
            <div class="step-body">
                <div id="previewTable" class="preview-table-wrap"></div>
                <div class="overwrite-wrap mt-4">
                    <div class="form-check form-switch py-3 px-3 rounded">
                        <input class="form-check-input" type="checkbox" id="overwriteData">
                        <label class="form-check-label fw-semibold" for="overwriteData">Overwrite existing data</label>
                        <p class="small text-muted mb-0 mt-1">Matching records (date, facility, department, source) will be updated instead of duplicated.</p>
                    </div>
                </div>
                <div class="step-actions mt-4">
                    <button type="button" class="btn btn-bs" id="backToStep2">
                        <i class="fas fa-arrow-left me-2"></i>Back
                    </button>
                    <button type="button" class="btn btn-bp" id="importDataBtn">
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
.emissions-import-app * { box-sizing: border-box; }
.emissions-import-app { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 1200px; margin: 0 auto; }
/* Topbar - same as other modules */
.emissions-import-app .topbar { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; padding: 20px 24px; background: linear-gradient(135deg, #fff 0%, var(--gray-50) 100%); border: 1px solid var(--gray-200); border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.emissions-import-app .topbar h2 { font-size: 1.35rem; font-weight: 700; letter-spacing: -0.02em; display: flex; align-items: center; gap: 10px; margin: 0; color: var(--gray-800); }
.emissions-import-app .topbar h2 .sb { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%); color: #fff; font-size: 1rem; font-weight: 700; box-shadow: 0 2px 8px rgba(46,125,50,.25); }
.emissions-import-app .topbar p { color: var(--gray-600); font-size: 0.875rem; flex: 1; min-width: 180px; margin: 0; line-height: 1.4; }
.emissions-import-app .topbar-actions { margin-left: auto; display: flex; gap: 10px; flex-wrap: wrap; }
.emissions-import-app .btn-outline { padding: 10px 20px; border-radius: 10px; border: 1.5px solid var(--primary-green); background: #fff; color: var(--primary-green); font-size: 0.875rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
.emissions-import-app .btn-outline:hover { background: rgba(46,125,50,0.08); color: var(--primary-green); }
.emissions-import-app .btn-secondary { padding: 10px 20px; border-radius: 10px; border: 1.5px solid var(--gray-200); background: #fff; color: var(--gray-700); font-size: 0.875rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
.emissions-import-app .btn-secondary:hover { background: var(--gray-50); color: var(--gray-800); }
/* Wizard */
.emissions-import-app .wizard-card { background: #fff; border: 1px solid var(--gray-200); border-radius: 16px; padding: 1.5rem 2rem; box-shadow: 0 2px 8px rgba(0,0,0,.06); margin-bottom: 24px; }
.emissions-import-app .wizard-steps { display: flex; align-items: center; justify-content: center; gap: 0; flex-wrap: wrap; }
.emissions-import-app .wizard-step { display: flex; flex-direction: column; align-items: center; gap: 0.5rem; }
.emissions-import-app .wizard-step-circle { width: 44px; height: 44px; border-radius: 50%; background: var(--gray-200); color: var(--gray-600); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9375rem; transition: all 0.25s; }
.emissions-import-app .wizard-step.active .wizard-step-circle { background: var(--primary-green); color: #fff; box-shadow: 0 4px 12px rgba(46,125,50,.35); }
.emissions-import-app .wizard-step.completed .wizard-step-circle { background: var(--light-green); color: #fff; }
.emissions-import-app .wizard-step.completed .wizard-step-circle .wizard-step-num { display: none; }
.emissions-import-app .wizard-step.completed .wizard-step-circle::after { content: "\f00c"; font-family: "Font Awesome 6 Free"; font-weight: 900; font-size: 0.875rem; }
.emissions-import-app .wizard-step-label { font-size: 0.8125rem; font-weight: 600; color: var(--gray-600); }
.emissions-import-app .wizard-step.active .wizard-step-label { color: var(--primary-green); }
.emissions-import-app .wizard-step-line { width: 56px; height: 3px; background: var(--gray-200); margin: 0 0.5rem; margin-top: -26px; border-radius: 2px; }
.emissions-import-app .wizard-step.completed + .wizard-step-line { background: var(--light-green); }
/* Step cards */
.emissions-import-app .tw.step-card { background: #fff; border: 1px solid var(--gray-200); border-radius: 16px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.06); margin-bottom: 24px; }
.emissions-import-app .step-head { padding: 18px 24px; border-bottom: 1px solid var(--gray-200); background: linear-gradient(180deg, var(--gray-50) 0%, #fff 100%); }
.emissions-import-app .step-head h5 { font-size: 1rem; font-weight: 700; color: var(--gray-800); margin: 0; }
.emissions-import-app .step-head h5 i { color: var(--primary-green); }
.emissions-import-app .step-sub { font-size: 0.8125rem; color: var(--gray-600); margin: 0; }
.emissions-import-app .step-body { padding: 24px; }
.emissions-import-app .step-body .rq { color: var(--danger-red); }
/* Upload zone */
.emissions-import-app .fup-zone { border: 2px dashed var(--gray-200); border-radius: 14px; padding: 3rem 2rem; text-align: center; background: var(--gray-50); cursor: pointer; transition: all 0.25s; }
.emissions-import-app .fup-zone:hover,
.emissions-import-app .fup-zone.dz-drag-hover { border-color: var(--primary-green); background: rgba(46,125,50,0.06); }
.emissions-import-app .fup-icon { font-size: 3rem; color: var(--primary-green); margin-bottom: 1rem; }
.emissions-import-app .fup-title { font-weight: 600; font-size: 1.0625rem; color: var(--gray-800); margin-bottom: 0.5rem; }
.emissions-import-app .fup-sub { font-size: 0.875rem; color: var(--gray-600); margin-bottom: 1rem; }
.emissions-import-app .fup-badges { display: flex; justify-content: center; gap: 8px; flex-wrap: wrap; }
.emissions-import-app .fup-badge { background: var(--gray-200); color: var(--gray-800); padding: 4px 12px; border-radius: 100px; font-size: 0.75rem; font-weight: 600; }
/* Form - fg, fsl */
.emissions-import-app .fg { margin-bottom: 0; }
.emissions-import-app .fg label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--gray-800); }
.emissions-import-app .fg .fsl { width: 100%; padding: 10px 14px; font-size: 14px; border: 1.5px solid var(--gray-200); border-radius: 10px; background: #fff; outline: none; }
.emissions-import-app .fg .fsl:focus { border-color: var(--primary-green); }
.emissions-import-app .mapping-select { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%235f6368' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; padding-right: 36px; }
/* Step actions */
.emissions-import-app .step-actions { display: flex; gap: 12px; flex-wrap: wrap; }
.emissions-import-app .btn-bs { padding: 10px 20px; border-radius: 10px; font-size: 0.9375rem; font-weight: 600; background: var(--gray-100); color: var(--gray-600); border: 1.5px solid var(--gray-200); }
.emissions-import-app .btn-bs:hover { background: var(--gray-200); color: var(--gray-800); }
.emissions-import-app .btn-bp { padding: 10px 20px; border-radius: 10px; font-size: 0.9375rem; font-weight: 600; background: var(--primary-green); color: #fff; border: none; }
.emissions-import-app .btn-bp:hover { background: var(--dark-green); color: #fff; }
/* Preview table - system table style */
.emissions-import-app .preview-table-wrap { background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: 12px; padding: 1rem; max-height: 400px; overflow: auto; }
.emissions-import-app .preview-table-wrap table { width: 100%; background: #fff; border-collapse: separate; border-spacing: 0; border-radius: 8px; overflow: hidden; }
.emissions-import-app .preview-table-wrap thead th { background: var(--gray-100); color: var(--gray-600); font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; padding: 12px 14px; border-bottom: 1px solid var(--gray-200); }
.emissions-import-app .preview-table-wrap tbody td { padding: 12px 14px; font-size: 0.875rem; color: var(--gray-800); border-bottom: 1px solid var(--gray-100); }
.emissions-import-app .preview-table-wrap tbody tr:hover td { background: var(--gray-50); }
.emissions-import-app .preview-table-wrap tbody tr:last-child td { border-bottom: none; }
.emissions-import-app .overwrite-wrap .rounded { background: var(--gray-50); border: 1px solid var(--gray-200); }
.emissions-import-app .form-check-input:checked { background-color: var(--primary-green); border-color: var(--primary-green); }
/* Success modal */
.emissions-import-app .import-success-icon { font-size: 3.5rem; color: var(--primary-green); }
#importSuccessModal .btn-primary { background: var(--primary-green); border-color: var(--primary-green); }
#importSuccessModal .btn-primary:hover { background: var(--dark-green); border-color: var(--dark-green); }
@media (max-width: 768px) {
    .emissions-import-app .wizard-step-line { display: none; }
    .emissions-import-app .topbar-actions { margin-left: 0; width: 100%; }
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
        document.querySelectorAll('.wizard-step').forEach(el => {
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
        let html = '<table class="table table-sm mb-0 w-100"><thead><tr>';
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

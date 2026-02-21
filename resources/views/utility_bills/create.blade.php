@extends('layouts.app')

@section('title', 'Upload Utility Bill')
@section('page-title', 'Upload Utility Bill')

@push('styles')
<style>
.utility-bills-app * { box-sizing: border-box; }
.utility-bills-app { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
/* Topbar */
.utility-bills-app .topbar { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; padding: 20px 24px; background: linear-gradient(135deg, #fff 0%, var(--gray-50) 100%); border: 1px solid var(--gray-200); border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.utility-bills-app .topbar h2 { font-size: 1.35rem; font-weight: 700; letter-spacing: -0.02em; display: flex; align-items: center; gap: 10px; margin: 0; color: var(--gray-800); }
.utility-bills-app .topbar h2 .sb { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%); color: #fff; font-size: 1rem; font-weight: 700; box-shadow: 0 2px 8px rgba(46,125,50,.25); }
.utility-bills-app .topbar p { color: var(--gray-600); font-size: 0.875rem; flex: 1; min-width: 180px; margin: 0; line-height: 1.4; }
.utility-bills-app .btn-back { padding: 10px 18px; border-radius: 10px; background: var(--gray-100); color: var(--gray-600); border: 1.5px solid var(--gray-200); font-size: 0.875rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all .15s; }
.utility-bills-app .btn-back:hover { background: var(--gray-200); color: var(--gray-800); }
/* Form card */
.utility-bills-app .tw { background: #fff; border: 1px solid var(--gray-200); border-radius: 16px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.utility-bills-app .card-head { padding: 18px 24px; border-bottom: 1px solid var(--gray-200); background: linear-gradient(180deg, var(--gray-50) 0%, #fff 100%); }
.utility-bills-app .card-head h3 { font-size: 1rem; font-weight: 700; color: var(--gray-800); margin: 0 0 4px 0; }
.utility-bills-app .card-head .ch-sub { font-size: 0.8125rem; color: var(--gray-600); margin: 0; }
.utility-bills-app .card-body { padding: 24px; }
/* Alerts */
.utility-bills-app .alert { border-radius: 12px; border: 1px solid transparent; }
.utility-bills-app .alert-danger { background: rgba(211,47,47,0.08); border-color: rgba(211,47,47,0.2); color: var(--danger-red); }
/* Form groups - same as Scope 1/3 */
.utility-bills-app .fg { margin-bottom: 18px; }
.utility-bills-app .fg label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--gray-800); }
.utility-bills-app .fg label .rq { color: var(--danger-red); }
.utility-bills-app .fg .fh { font-size: 11px; color: var(--gray-600); margin-top: 4px; }
.utility-bills-app .fi, .utility-bills-app .fsl { width: 100%; padding: 10px 14px; font-size: 14px; border: 1.5px solid var(--gray-200); border-radius: 10px; background: #fff; outline: none; color: var(--gray-800); font-family: inherit; }
.utility-bills-app .fi:focus, .utility-bills-app .fsl:focus { border-color: var(--primary-green); }
.utility-bills-app .fsl { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%235f6368' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; padding-right: 36px; cursor: pointer; }
/* File upload area */
.utility-bills-app .fup { border: 2px dashed var(--gray-200); border-radius: 12px; padding: 24px; text-align: center; cursor: pointer; background: var(--gray-50); transition: all .2s; }
.utility-bills-app .fup:hover { border-color: var(--gray-300); background: #fff; }
.utility-bills-app .fup.dragover { border-color: var(--primary-green); background: rgba(46,125,50,0.06); }
.utility-bills-app .fup input { display: none; }
.utility-bills-app .fup .fut { font-size: 15px; font-weight: 600; color: var(--gray-800); margin-bottom: 4px; }
.utility-bills-app .fup .fuh { font-size: 12px; color: var(--gray-600); }
.utility-bills-app .fup .fprev { display: none; margin-top: 12px; }
.utility-bills-app .fup.has-file .fprev { display: block; }
.utility-bills-app .fup.has-file .fdrop { display: none; }
.utility-bills-app .fup .btn-remove { margin-top: 8px; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; border: 1.5px solid var(--gray-200); background: #fff; color: var(--gray-600); cursor: pointer; transition: all .15s; }
.utility-bills-app .fup .btn-remove:hover { border-color: var(--danger-red); background: rgba(211,47,47,0.06); color: var(--danger-red); }
/* Info box */
.utility-bills-app .info-box { padding: 14px 18px; border-radius: 12px; background: rgba(46,125,50,0.06); border: 1px solid rgba(46,125,50,0.15); margin-bottom: 20px; }
.utility-bills-app .info-box h6 { font-size: 13px; font-weight: 700; color: var(--gray-800); margin: 0 0 8px 0; display: flex; align-items: center; gap: 8px; }
.utility-bills-app .info-box ul { margin: 0; padding-left: 20px; font-size: 13px; color: var(--gray-600); line-height: 1.6; }
/* Buttons */
.utility-bills-app .fn { display: flex; gap: 12px; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--gray-200); flex-wrap: wrap; }
.utility-bills-app .btn { padding: 10px 20px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all .15s; border: none; font-family: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
.utility-bills-app .btn-bs { background: var(--gray-100); color: var(--gray-600); border: 1.5px solid var(--gray-200); }
.utility-bills-app .btn-bs:hover { background: var(--gray-200); color: var(--gray-800); }
.utility-bills-app .btn-bp { flex: 1; background: var(--primary-green); color: #fff; min-width: 180px; justify-content: center; }
.utility-bills-app .btn-bp:hover { background: var(--dark-green); color: #fff; }
.utility-bills-app .btn-bp:disabled { opacity: 0.7; cursor: not-allowed; }
</style>
@endpush

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="utility-bills-app container-fluid mt-4">
        <div class="topbar">
            <h2><span class="sb"><i class="fas fa-file-upload"></i></span> Upload Utility Bill</h2>
            <p>Upload electricity or fuel bills to automatically extract emission data</p>
            <a href="{{ route('utility.index') }}" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to Bills
            </a>
        </div>

        <div class="tw">
            <div class="card-head">
                <h3>Emission data extraction</h3>
                <p class="ch-sub">Select bill type, facility, and upload your file. OCR will extract key data and create draft emission records.</p>
            </div>
            <div class="card-body">
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form action="{{ route('utility.upload') }}" method="POST" enctype="multipart/form-data" id="billUploadForm">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="fg">
                                <label>Bill Type <span class="rq">*</span></label>
                                <select class="fsl" name="bill_type" id="billType" required>
                                    <option value="">Select Bill Type</option>
                                    <option value="electricity">Electricity Bill</option>
                                    <option value="fuel">Fuel Bill (Diesel/Gasoline)</option>
                                </select>
                                <div class="fh">Type of utility bill you're uploading</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="fg">
                                <label>Facility <span class="rq">*</span></label>
                                <select class="fsl" name="facility_id" id="facilitySelect" required>
                                    <option value="">Select Facility</option>
                                    @foreach($facilities as $facility)
                                        <option value="{{ $facility->id }}">{{ $facility->name }}</option>
                                    @endforeach
                                </select>
                                <div class="fh">Facility this bill belongs to</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="fg">
                                <label>Department (optional)</label>
                                <select class="fsl" name="department_id" id="departmentSelect">
                                    <option value="">Select Department</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}" data-facility-id="{{ $department->facility_id }}">{{ $department->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="fg">
                                <label>Emission Source <span class="rq">*</span></label>
                                <input type="text" class="fi" name="emission_source" id="emissionSource" list="emissionSourceList" placeholder="e.g. Purchased Electricity, Diesel" required>
                                <datalist id="emissionSourceList">
                                    @foreach($emissionSources as $source)
                                        <option value="{{ $source->name }}">
                                    @endforeach
                                    <option value="Purchased Electricity">
                                    <option value="Diesel">
                                    <option value="Gasoline">
                                    <option value="Natural Gas">
                                </datalist>
                                <div class="fh">Enter or select the emission source</div>
                            </div>
                        </div>
                    </div>

                    <div class="fg" style="margin-top: 8px;">
                        <label>Upload Bill File <span class="rq">*</span></label>
                        <div class="fup" id="fileUploadArea">
                            <input type="file" name="bill_file" id="billFile" accept=".pdf,.jpg,.jpeg,.png" required>
                            <div class="fdrop" id="fileUploadContent">
                                <i class="fas fa-cloud-upload-alt fa-3x" style="color: var(--gray-400); margin-bottom: 12px;"></i>
                                <div class="fut">Click to upload or drag and drop</div>
                                <div class="fuh">PDF, JPG, PNG (max 10MB)</div>
                            </div>
                            <div class="fprev" id="filePreview">
                                <i class="fas fa-file-alt fa-2x" style="color: var(--primary-green); margin-bottom: 8px;"></i>
                                <div class="fut" id="fileName"></div>
                                <button type="button" class="btn-remove" id="removeFile"><i class="fas fa-times me-1"></i> Remove</button>
                            </div>
                        </div>
                        <div class="fh">Supported: PDF, JPG, PNG. Clear, readable bills give better OCR results.</div>
                    </div>

                    <div class="info-box">
                        <h6><i class="fas fa-lightbulb"></i> How it works</h6>
                        <ul>
                            <li>OCR extracts text from your bill</li>
                            <li>Date, consumption, and cost are auto-extracted</li>
                            <li>Emission records are created with calculated CO₂e</li>
                            <li>Records are saved as draft for your review</li>
                        </ul>
                    </div>

                    <div class="fn">
                        <a href="{{ route('utility.index') }}" class="btn btn-bs"><i class="fas fa-arrow-left"></i> Cancel</a>
                        <button type="submit" class="btn btn-bp" id="submitBtn">
                            <i class="fas fa-upload"></i> Upload & Process Bill
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

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

    facilitySelect.addEventListener('change', function() {
        const facilityId = this.value;
        departmentSelect.querySelectorAll('option[data-facility-id]').forEach(function(opt) {
            opt.style.display = (!facilityId || opt.getAttribute('data-facility-id') === facilityId) ? '' : 'none';
        });
        if (facilityId) departmentSelect.value = '';
    });

    document.getElementById('billType').addEventListener('change', function() {
        const el = document.getElementById('emissionSource');
        if (this.value === 'electricity') el.value = 'Purchased Electricity';
        else if (this.value === 'fuel') el.value = 'Diesel';
    });

    fileUploadArea.addEventListener('click', function(e) {
        if (!removeFile.contains(e.target)) billFile.click();
    });

    fileUploadArea.addEventListener('dragover', function(e) { e.preventDefault(); this.classList.add('dragover'); });
    fileUploadArea.addEventListener('dragleave', function(e) { e.preventDefault(); this.classList.remove('dragover'); });
    fileUploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
        if (e.dataTransfer.files.length) { billFile.files = e.dataTransfer.files; handleFileSelect(e.dataTransfer.files[0]); }
    });

    billFile.addEventListener('change', function() { if (this.files.length) handleFileSelect(this.files[0]); });

    removeFile.addEventListener('click', function(e) {
        e.stopPropagation();
        billFile.value = '';
        fileUploadArea.classList.remove('has-file');
    });

    function handleFileSelect(file) {
        var allowed = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        if (!allowed.includes(file.type)) { alert('Invalid file type. Use PDF, JPG, or PNG.'); billFile.value = ''; return; }
        if (file.size > 10 * 1024 * 1024) { alert('File size must be under 10MB.'); billFile.value = ''; return; }
        fileName.textContent = file.name;
        fileUploadArea.classList.add('has-file');
    }

    form.addEventListener('submit', function(e) {
        if (!billFile.files.length) { e.preventDefault(); alert('Please select a file.'); return; }
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Processing...';
    });
});
</script>
@endpush

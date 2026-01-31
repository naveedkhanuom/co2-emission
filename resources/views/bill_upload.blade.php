@extends('layouts.app')

@section('content')
    <!-- Main Content -->
    <div id="content">

       @include('layouts.top-nav') 
<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h4 class="mb-0">
                <i class="fas fa-file-upload me-2 text-primary"></i>
                Upload Utility Bill
            </h4>
            <p class="text-muted mb-0 small">Upload fuel or electricity bills to extract consumption data</p>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>

                @if(session('bill'))
                    <div class="alert alert-info">
                        <h5 class="alert-heading">
                            <i class="fas fa-file-invoice me-2"></i>Parsed Bill Data
                        </h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-2">
                                    <strong><i class="fas fa-tag me-2"></i>Bill Type:</strong> 
                                    <span class="badge bg-primary">{{ ucfirst(session('bill')->bill_type ?? 'electricity') }}</span>
                                </p>
                                @if(session('bill')->supplier_name)
                                <p class="mb-2">
                                    <strong><i class="fas fa-building me-2"></i>Supplier:</strong> 
                                    {{ session('bill')->supplier_name }}
                                </p>
                                @endif
                                @if(session('bill')->bill_date)
                                <p class="mb-2">
                                    <strong><i class="fas fa-calendar me-2"></i>Bill Date:</strong> 
                                    {{ \Carbon\Carbon::parse(session('bill')->bill_date)->format('Y-m-d') }}
                                </p>
                                @endif
                            </div>
                            <div class="col-md-6">
                                @if(session('bill')->consumption)
                                <p class="mb-2">
                                    <strong>
                                        <i class="fas fa-bolt me-2"></i>
                                        {{ session('bill')->bill_type == 'fuel' ? 'Fuel Quantity' : 'Consumption' }}:
                                    </strong> 
                                    <span class="badge bg-success fs-6">
                                        {{ number_format(session('bill')->consumption, 2) }} {{ session('bill')->consumption_unit ?? (session('bill')->bill_type == 'fuel' ? 'L' : 'kWh') }}
                                    </span>
                                </p>
                                @endif
                                @if(session('bill')->cost)
                                <p class="mb-2">
                                    <strong><i class="fas fa-dollar-sign me-2"></i>Cost:</strong> 
                                    {{ number_format(session('bill')->cost, 2) }}
                                </p>
                                @endif
                                @if(session('extracted_data') && isset(session('extracted_data')['co2e_value']))
                                <p class="mb-2">
                                    <strong><i class="fas fa-leaf me-2"></i>CO₂e:</strong> 
                                    <span class="badge bg-warning text-dark">
                                        {{ number_format(session('extracted_data')['co2e_value'], 4) }} tCO₂e
                                    </span>
                                </p>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    @if(session('bill')->raw_text)
                    <div class="mb-3">
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#rawTextCollapse">
                            <i class="fas fa-code me-2"></i>View Raw Extracted Text
                        </button>
                        <div class="collapse mt-2" id="rawTextCollapse">
                            <div class="card card-body bg-light">
                                <pre class="mb-0 small" style="max-height: 200px; overflow-y: auto;">{{ session('bill')->raw_text }}</pre>
                            </div>
                        </div>
                    </div>
                    @endif
                @endif
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form action="{{ route('bill.upload.post') }}" method="POST" enctype="multipart/form-data" id="billUploadForm">
                @csrf
                
                <div class="mb-3">
                    <label for="bill_type" class="form-label fw-bold">
                        <i class="fas fa-tag me-2"></i>Bill Type <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" name="bill_type" id="bill_type" required>
                        <option value="">Select Bill Type</option>
                        <option value="electricity" {{ old('bill_type') == 'electricity' ? 'selected' : '' }}>Electricity Bill</option>
                        <option value="fuel" {{ old('bill_type') == 'fuel' ? 'selected' : '' }}>Fuel Bill (Diesel/Gasoline)</option>
                    </select>
                    <div class="form-text">Select whether you're uploading an electricity or fuel bill</div>
                </div>

                <div class="mb-3">
                    <label for="bill_file" class="form-label fw-bold">
                        <i class="fas fa-file-pdf me-2"></i>Choose Bill File <span class="text-danger">*</span>
                    </label>
                    <input type="file" 
                           class="form-control" 
                           name="bill_file" 
                           id="bill_file" 
                           accept=".pdf,.jpg,.jpeg,.png"
                           required>
                    <div class="form-text">
                        Supported formats: JPG, PNG, PDF (Max 10MB). The system will extract:
                        <ul class="mb-0 mt-1">
                            <li id="extractionInfo">Select bill type to see what will be extracted</li>
                        </ul>
                    </div>
                </div>

                <div class="alert alert-info">
                    <h6 class="alert-heading">
                        <i class="fas fa-info-circle me-2"></i>What will be extracted:
                    </h6>
                    <ul class="mb-0 small" id="extractionList">
                        <li>Bill date</li>
                        <li>Supplier name</li>
                        <li>Consumption data (kWh for electricity, Liters for fuel)</li>
                        <li>Cost/Amount</li>
                    </ul>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back
                    </a>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-upload me-2"></i>Upload & Extract Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
   @include('emission_records.css')
   <style>
       .badge {
           padding: 0.35em 0.65em;
       }
   </style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const billTypeSelect = document.getElementById('bill_type');
    const extractionInfo = document.getElementById('extractionInfo');
    const extractionList = document.getElementById('extractionList');
    const submitBtn = document.getElementById('submitBtn');
    const form = document.getElementById('billUploadForm');

    // Update extraction info based on bill type
    billTypeSelect.addEventListener('change', function() {
        const billType = this.value;
        
        if (billType === 'electricity') {
            extractionInfo.innerHTML = '<strong>Electricity:</strong> kWh (kilowatt-hours) consumption will be extracted';
            extractionList.innerHTML = `
                <li>Bill date</li>
                <li>Supplier name</li>
                <li><strong>Electricity Consumption: kWh (kilowatt-hours)</strong></li>
                <li>Cost/Amount</li>
            `;
        } else if (billType === 'fuel') {
            extractionInfo.innerHTML = '<strong>Fuel:</strong> Liters (L) or Gallons quantity will be extracted and converted to Liters';
            extractionList.innerHTML = `
                <li>Bill date</li>
                <li>Supplier name</li>
                <li><strong>Fuel Quantity: Liters (L) - gallons will be converted to liters</strong></li>
                <li>Cost/Amount</li>
            `;
        } else {
            extractionInfo.innerHTML = 'Select bill type to see what will be extracted';
            extractionList.innerHTML = `
                <li>Bill date</li>
                <li>Supplier name</li>
                <li>Consumption data (kWh for electricity, Liters for fuel)</li>
                <li>Cost/Amount</li>
            `;
        }
    });

    // Form submission handler
    form.addEventListener('submit', function(e) {
        if (!billTypeSelect.value) {
            e.preventDefault();
            alert('Please select a bill type (Electricity or Fuel)');
            billTypeSelect.focus();
            return false;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
    });
});
</script>
@endpush

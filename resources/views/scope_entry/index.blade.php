@extends('layouts.app')

@section('title', 'Scope-Based Entry')
@section('page-title', 'Scope-Based Emission Entry')

@section('content')
    <!-- Main Content -->
    <div id="content">
        @include('layouts.top-nav') 
        
        <div class="container mt-4">
            <!-- Scope Selection Cards -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="scope-card" data-scope="1" onclick="openScopeForm(1)">
                        <div class="scope-card-header scope-1-header">
                            <i class="fas fa-fire"></i>
                            <h3>Scope 1</h3>
                        </div>
                        <div class="scope-card-body">
                            <p class="scope-description">Direct Emissions</p>
                            <p class="scope-details">Emissions from sources owned or controlled by your organization</p>
                            <div class="scope-examples">
                                <small><strong>Examples:</strong> Fuel combustion, company vehicles, refrigerants</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <div class="scope-card" data-scope="2" onclick="openScopeForm(2)">
                        <div class="scope-card-header scope-2-header">
                            <i class="fas fa-bolt"></i>
                            <h3>Scope 2</h3>
                        </div>
                        <div class="scope-card-body">
                            <p class="scope-description">Indirect Emissions</p>
                            <p class="scope-details">Emissions from purchased energy (electricity, steam, heating, cooling)</p>
                            <div class="scope-examples">
                                <small><strong>Examples:</strong> Purchased electricity, district heating/cooling</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <div class="scope-card" data-scope="3" onclick="openScopeForm(3)">
                        <div class="scope-card-header scope-3-header">
                            <i class="fas fa-network-wired"></i>
                            <h3>Scope 3</h3>
                        </div>
                        <div class="scope-card-body">
                            <p class="scope-description">Other Indirect Emissions</p>
                            <p class="scope-details">Emissions from activities in your value chain</p>
                            <div class="scope-examples">
                                <small><strong>Examples:</strong> Business travel, employee commuting, waste disposal</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scope Forms (Hidden by default) -->
            @for($scope = 1; $scope <= 3; $scope++)
                <div class="scope-form-container" id="scopeForm{{ $scope }}" style="display: none;">
                    <div class="form-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-0">
                                    <span class="scope-badge scope-{{ $scope }}">Scope {{ $scope }}</span>
                                    <span class="ms-2">Emission Entry Form</span>
                                </h4>
                                <p class="text-muted mb-0 mt-1">Enter emission data for Scope {{ $scope }}</p>
                            </div>
                            <button type="button" class="btn btn-outline-secondary" onclick="closeScopeForm({{ $scope }})">
                                <i class="fas fa-times me-2"></i>Close
                            </button>
                        </div>
                    </div>

                    <form class="emission-form" id="scope{{ $scope }}Form" method="POST" action="{{ route('emission-records.store') }}">
                        @csrf
                        <input type="hidden" name="scopeSelect" value="{{ $scope }}">
                        
                        <!-- Basic Information Section -->
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-info-circle"></i> Basic Information
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label required-label">Date</label>
                                    <input type="date" class="form-control date-picker" name="entryDate" required>
                                    <div class="help-text">Select the date when emissions occurred</div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label required-label">Facility / Location</label>
                                    <select class="form-select facility-select" name="facilitySelect" required>
                                        <option value="">Select facility...</option>
                                        @foreach(facilities() as $facility)
                                            <option value="{{ $facility->name }}">{{ $facility->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="help-text">Choose the facility where emissions occurred</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Emission Source Section -->
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-industry"></i> Emission Source
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label required-label">Source Type</label>
                                    <select class="form-select" id="scope{{ $scope }}SourceType" onchange="toggleSourceInput({{ $scope }})">
                                        <option value="existing">Select from Existing Sources</option>
                                        <option value="custom">Enter Custom Source</option>
                                    </select>
                                    <div class="help-text">Choose to select from existing sources or enter a custom one</div>
                                </div>
                                
                                <div class="col-md-6" id="scope{{ $scope }}ExistingSourceDiv">
                                    <label class="form-label required-label">Emission Source</label>
                                    <select class="form-select emission-source-select" name="emissionSourceSelect" id="scope{{ $scope }}EmissionSource" required onchange="updateEmissionFactor({{ $scope }})">
                                        <option value="">Select emission source...</option>
                                        @if($scope == 1)
                                            @foreach($scope1Sources as $source)
                                                <option value="{{ $source->name }}" data-unit="" data-factor="">{{ $source->name }}</option>
                                            @endforeach
                                            {{-- Fallback options if no database sources --}}
                                            @if($scope1Sources->isEmpty())
                                                <option value="natural-gas" data-unit="m³" data-factor="">Natural Gas Combustion</option>
                                                <option value="diesel" data-unit="L" data-factor="">Diesel Fuel</option>
                                                <option value="gasoline" data-unit="L" data-factor="">Gasoline (Company Vehicles)</option>
                                                <option value="lpg" data-unit="L" data-factor="">LPG (Liquefied Petroleum Gas)</option>
                                                <option value="refrigerants" data-unit="kg" data-factor="">Refrigerants (F-gases)</option>
                                                <option value="process-emissions" data-unit="kg" data-factor="">Process Emissions</option>
                                            @endif
                                        @elseif($scope == 2)
                                            @foreach($scope2Sources as $source)
                                                <option value="{{ $source->name }}" data-unit="" data-factor="">{{ $source->name }}</option>
                                            @endforeach
                                            {{-- Fallback options if no database sources --}}
                                            @if($scope2Sources->isEmpty())
                                                <option value="electricity" data-unit="kWh" data-factor="">Purchased Electricity</option>
                                                <option value="steam" data-unit="MJ" data-factor="">Purchased Steam</option>
                                                <option value="heating" data-unit="kWh" data-factor="">District Heating</option>
                                                <option value="cooling" data-unit="kWh" data-factor="">District Cooling</option>
                                            @endif
                                        @elseif($scope == 3)
                                            @foreach($scope3Sources as $source)
                                                <option value="{{ $source->name }}" data-unit="" data-factor="">{{ $source->name }}</option>
                                            @endforeach
                                            {{-- Fallback options if no database sources --}}
                                            @if($scope3Sources->isEmpty())
                                                <option value="business-travel" data-unit="km" data-factor="">Business Travel (Air)</option>
                                                <option value="business-travel-road" data-unit="km" data-factor="">Business Travel (Road)</option>
                                                <option value="employee-commute" data-unit="km" data-factor="">Employee Commuting</option>
                                                <option value="waste" data-unit="kg" data-factor="">Waste Disposal</option>
                                                <option value="purchased-goods" data-unit="kg" data-factor="">Purchased Goods & Services</option>
                                                <option value="transportation" data-unit="km" data-factor="">Transportation & Distribution</option>
                                                <option value="water" data-unit="m³" data-factor="">Water Consumption</option>
                                            @endif
                                        @endif
                                    </select>
                                    <div class="help-text">Select from existing emission sources</div>
                                </div>
                                
                                <div class="col-md-6" id="scope{{ $scope }}CustomSourceDiv" style="display: none;">
                                    <label class="form-label required-label">Custom Emission Source</label>
                                    <input type="text" class="form-control" name="customEmissionSource" id="scope{{ $scope }}CustomEmissionSource" placeholder="Enter emission source name..." maxlength="100" oninput="updateEmissionFactor({{ $scope }})">
                                    <div class="help-text">Enter a custom emission source name</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Activity Data & Calculation Section -->
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-calculator"></i> Activity Data & Calculation
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label required-label">Activity Data</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="activityData" id="scope{{ $scope }}ActivityData" step="0.01" min="0" placeholder="0.00" required oninput="calculateCO2e({{ $scope }})">
                                        <span class="input-group-text" id="scope{{ $scope }}ActivityUnit">-</span>
                                    </div>
                                    <div class="help-text">Enter the activity amount</div>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label required-label">Emission Factor</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="emissionFactor" id="scope{{ $scope }}EmissionFactor" step="0.000001" min="0" placeholder="0.000000" required oninput="calculateCO2e({{ $scope }})">
                                        <span class="input-group-text">tCO₂e/unit</span>
                                    </div>
                                    <div class="help-text">Pre-filled based on emission source</div>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label required-label">CO₂e Value</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="co2eValue" id="scope{{ $scope }}CO2eValue" step="0.0001" min="0" placeholder="0.0000" required readonly>
                                        <span class="input-group-text">tCO₂e</span>
                                    </div>
                                    <div class="help-text">Automatically calculated</div>
                                </div>
                            </div>
                            
                            <!-- Calculation Display -->
                            <div class="row g-3 mt-2">
                                <div class="col-md-12">
                                    <div class="calculation-display">
                                        <div class="calculation-formula" id="scope{{ $scope }}CalculationFormula">
                                            Activity Data × Emission Factor = CO₂e Value
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Additional Details Section -->
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-file-alt"></i> Additional Details
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Department / Cost Center</label>
                                    <select class="form-select" name="departmentSelect">
                                        <option value="">Select department...</option>
                                        @foreach(departments() as $department)
                                            <option value="{{ $department->name }}">{{ $department->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Confidence Level</label>
                                    <select class="form-select" name="confidenceLevel">
                                        <option value="high">High Confidence</option>
                                        <option value="medium" selected>Medium Confidence</option>
                                        <option value="low">Low Confidence</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Data Source</label>
                                    <select class="form-select" name="dataSource">
                                        <option value="manual" selected>Manual Entry</option>
                                        <option value="meter">Meter Reading</option>
                                        <option value="invoice">Utility Invoice</option>
                                        <option value="estimate">Estimate</option>
                                    </select>
                                </div>
                                
                                <div class="col-12">
                                    <label class="form-label">Notes / Comments</label>
                                    <textarea class="form-control" name="entryNotes" rows="3" placeholder="Add any additional information about this emission record..."></textarea>
                                    <div class="help-text">Optional: Add context, assumptions, or specific details</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Form Actions -->
                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                            <div>
                                <button type="submit" name="status" value="draft" class="btn btn-outline-secondary">
                                    <i class="fas fa-save me-2"></i>Save as Draft
                                </button>
                            </div>
                            
                            <div class="d-flex gap-3">
                                <button type="button" class="btn btn-outline-primary" onclick="clearScopeForm({{ $scope }})">
                                    <i class="fas fa-redo me-2"></i>Clear Form
                                </button>
                                <button type="submit" name="status" value="active" class="btn btn-success">
                                    <i class="fas fa-check-circle me-2"></i>Submit Entry
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            @endfor
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center p-5">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h5 class="mb-3">Entry Saved!</h5>
                    <p class="text-muted">Your emission record has been successfully saved.</p>
                    <button type="button" class="btn btn-success w-100" data-bs-dismiss="modal">
                        Continue
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
   @include('emission_records.css')
   <style>
        /* Scope Cards */
        .scope-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            cursor: pointer;
            transition: all 0.3s ease;
            overflow: hidden;
            height: 100%;
        }
        
        .scope-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .scope-card-header {
            padding: 30px 20px;
            text-align: center;
            color: white;
        }
        
        .scope-1-header {
            background: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%);
        }
        
        .scope-2-header {
            background: linear-gradient(135deg, #0277bd 0%, #03a9f4 100%);
        }
        
        .scope-3-header {
            background: linear-gradient(135deg, #795548 0%, #a1887f 100%);
        }
        
        .scope-card-header i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.9;
        }
        
        .scope-card-header h3 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
        }
        
        .scope-card-body {
            padding: 25px 20px;
        }
        
        .scope-description {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        
        .scope-details {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .scope-examples {
            padding-top: 15px;
            border-top: 1px solid #eee;
            color: #888;
        }
        
        /* Scope Form Container */
        .scope-form-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            padding: 30px;
            margin-top: 30px;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .form-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .emission-form {
            margin-top: 20px;
        }
        
        .scope-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .scope-1 {
            background-color: rgba(46, 125, 50, 0.1);
            color: #2e7d32;
        }
        
        .scope-2 {
            background-color: rgba(2, 119, 189, 0.1);
            color: #0277bd;
        }
        
        .scope-3 {
            background-color: rgba(121, 85, 72, 0.1);
            color: #795548;
        }
        
        .calculation-display {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            border-radius: 8px;
            padding: 20px;
            border-left: 4px solid #0277bd;
        }
        
        .calculation-formula {
            font-family: 'Courier New', monospace;
            font-size: 1.1rem;
            color: #333;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .scope-card-header i {
                font-size: 2rem;
            }
            
            .scope-card-header h3 {
                font-size: 1.5rem;
            }
        }
   </style>
@endpush

@push('scripts')
<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    // Pre-defined emission factors (will be updated with your values)
    const emissionFactors = {
        // Scope 1
        'natural-gas': { unit: 'm³', factor: 0.00196 }, // tCO2e per m³
        'diesel': { unit: 'L', factor: 0.00268 }, // tCO2e per liter
        'gasoline': { unit: 'L', factor: 0.00231 }, // tCO2e per liter
        'lpg': { unit: 'L', factor: 0.00151 }, // tCO2e per liter
        'refrigerants': { unit: 'kg', factor: 0.0 }, // Will be provided
        'process-emissions': { unit: 'kg', factor: 0.0 }, // Will be provided
        
        // Scope 2
        'electricity': { unit: 'kWh', factor: 0.000527 }, // tCO2e per kWh
        'steam': { unit: 'MJ', factor: 0.0 }, // Will be provided
        'heating': { unit: 'kWh', factor: 0.0 }, // Will be provided
        'cooling': { unit: 'kWh', factor: 0.0 }, // Will be provided
        
        // Scope 3
        'business-travel': { unit: 'km', factor: 0.000255 }, // tCO2e per km (air travel)
        'business-travel-road': { unit: 'km', factor: 0.00015 }, // tCO2e per km (road)
        'employee-commute': { unit: 'km', factor: 0.00012 }, // tCO2e per km
        'waste': { unit: 'kg', factor: 0.0 }, // Will be provided
        'purchased-goods': { unit: 'kg', factor: 0.0 }, // Will be provided
        'transportation': { unit: 'km', factor: 0.0 }, // Will be provided
        'water': { unit: 'm³', factor: 0.0 }, // Will be provided
    };
    
    // Open scope form
    function openScopeForm(scope) {
        // Hide all forms
        document.querySelectorAll('.scope-form-container').forEach(form => {
            form.style.display = 'none';
        });
        
        // Show selected form
        const form = document.getElementById(`scopeForm${scope}`);
        if (form) {
            form.style.display = 'block';
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            
            // Initialize date picker
            const dateInput = form.querySelector('.date-picker');
            if (dateInput && !dateInput._flatpickr) {
                flatpickr(dateInput, {
                    dateFormat: "Y-m-d",
                    defaultDate: new Date(),
                    maxDate: new Date()
                });
            }
            
            // Initialize Select2
            if (typeof $ !== 'undefined') {
                $(form).find('.facility-select').select2({
                    placeholder: "Select facility...",
                    allowClear: true,
                    width: '100%'
                });
                
                $(form).find('.emission-source-select').select2({
                    placeholder: "Select emission source...",
                    allowClear: true,
                    width: '100%'
                });
            }
        }
    }
    
    // Close scope form
    function closeScopeForm(scope) {
        const form = document.getElementById(`scopeForm${scope}`);
        if (form) {
            form.style.display = 'none';
        }
    }
    
    // Toggle between existing and custom source input
    function toggleSourceInput(scope) {
        const sourceType = document.getElementById(`scope${scope}SourceType`).value;
        const existingDiv = document.getElementById(`scope${scope}ExistingSourceDiv`);
        const customDiv = document.getElementById(`scope${scope}CustomSourceDiv`);
        const existingSelect = document.getElementById(`scope${scope}EmissionSource`);
        const customInput = document.getElementById(`scope${scope}CustomEmissionSource`);
        
        if (sourceType === 'existing') {
            existingDiv.style.display = 'block';
            customDiv.style.display = 'none';
            existingSelect.required = true;
            customInput.required = false;
            customInput.value = '';
        } else {
            existingDiv.style.display = 'none';
            customDiv.style.display = 'block';
            existingSelect.required = false;
            customInput.required = true;
            existingSelect.value = '';
        }
        
        // Reset emission factor when switching
        const factorInput = document.getElementById(`scope${scope}EmissionFactor`);
        const activityUnit = document.getElementById(`scope${scope}ActivityUnit`);
        factorInput.value = '';
        activityUnit.textContent = '-';
    }
    
    // Update emission factor when source is selected
    function updateEmissionFactor(scope) {
        const sourceType = document.getElementById(`scope${scope}SourceType`).value;
        const activityUnit = document.getElementById(`scope${scope}ActivityUnit`);
        const factorInput = document.getElementById(`scope${scope}EmissionFactor`);
        
        let source = '';
        if (sourceType === 'existing') {
            const sourceSelect = document.getElementById(`scope${scope}EmissionSource`);
            source = sourceSelect.value;
        } else {
            const customInput = document.getElementById(`scope${scope}CustomEmissionSource`);
            source = customInput.value;
        }
        
        // Try to find emission factor from predefined list or database
        if (source && emissionFactors[source.toLowerCase()]) {
            const factorData = emissionFactors[source.toLowerCase()];
            activityUnit.textContent = factorData.unit;
            factorInput.value = factorData.factor || '';
            
            // Trigger calculation
            calculateCO2e(scope);
        } else {
            // If not found in predefined list, user needs to enter manually
            activityUnit.textContent = '-';
            if (!factorInput.value) {
                factorInput.value = '';
            }
        }
    }
    
    // Calculate CO2e value
    function calculateCO2e(scope) {
        const activityData = parseFloat(document.getElementById(`scope${scope}ActivityData`).value) || 0;
        const emissionFactor = parseFloat(document.getElementById(`scope${scope}EmissionFactor`).value) || 0;
        const co2eValue = activityData * emissionFactor;
        
        const co2eInput = document.getElementById(`scope${scope}CO2eValue`);
        co2eInput.value = co2eValue.toFixed(4);
        
        const activityUnit = document.getElementById(`scope${scope}ActivityUnit`).textContent;
        const formula = document.getElementById(`scope${scope}CalculationFormula`);
        formula.textContent = `${activityData.toFixed(2)} ${activityUnit} × ${emissionFactor.toFixed(6)} tCO₂e/${activityUnit} = ${co2eValue.toFixed(4)} tCO₂e`;
    }
    
    // Clear scope form
    function clearScopeForm(scope) {
        const form = document.getElementById(`scope${scope}Form`);
        if (form) {
            form.reset();
            
            // Reset source type toggle
            document.getElementById(`scope${scope}SourceType`).value = 'existing';
            toggleSourceInput(scope);
            
            // Reset Select2
            if (typeof $ !== 'undefined') {
                $(form).find('.facility-select').val(null).trigger('change');
                $(form).find('.emission-source-select').val(null).trigger('change');
            }
            
            // Reset calculation
            document.getElementById(`scope${scope}ActivityUnit`).textContent = '-';
            document.getElementById(`scope${scope}CalculationFormula`).textContent = 'Activity Data × Emission Factor = CO₂e Value';
            
            showToast('Form cleared', 'info');
        }
    }
    
    // Toast notification
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
    
    // Handle form submission
    document.addEventListener('DOMContentLoaded', function() {
        // Handle form submissions
        document.querySelectorAll('.emission-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const scope = parseInt(this.querySelector('[name="scopeSelect"]').value);
                const sourceType = document.getElementById(`scope${scope}SourceType`).value;
                const formData = new FormData(this);
                
                // Get the correct emission source value based on source type
                let emissionSource = '';
                if (sourceType === 'existing') {
                    const existingSelect = document.getElementById(`scope${scope}EmissionSource`);
                    emissionSource = existingSelect.value;
                    // Remove custom source if it exists
                    formData.delete('customEmissionSource');
                } else {
                    const customInput = document.getElementById(`scope${scope}CustomEmissionSource`);
                    emissionSource = customInput.value;
                    // Remove existing source select value
                    formData.delete('emissionSourceSelect');
                }
                
                // Set the emission source value
                formData.set('emissionSourceSelect', emissionSource);
                
                fetch(this.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || formData.get('_token'),
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(async res => {
                    const data = await res.json();
                    if (!res.ok) {
                        throw new Error(data.message || 'Validation error');
                    }
                    return data;
                })
                .then(data => {
                    const modal = new bootstrap.Modal(document.getElementById('successModal'));
                    modal.show();
                    this.reset();
                    clearScopeForm(scope);
                })
                .catch(err => {
                    showToast(err.message || 'An error occurred', 'error');
                });
            });
        });
    });
</script>
@endpush


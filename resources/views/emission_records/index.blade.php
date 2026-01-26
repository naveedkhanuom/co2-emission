@extends('layouts.app')

@section('title', 'Emission Records')
@section('page-title', 'Emission Records')

@section('content')
    <!-- Main Content -->
    <div id="content">
        <!-- Top Navigation Bar -->
     @include('layouts.top-nav') 
        
        <!-- Entry Mode Selection -->
        <div class="row mt-3 mb-3">
            <div class="col-md-4 mb-3">
                <div class="entry-mode-card active" id="singleEntryCard" onclick="setEntryMode('single')">
                    <div class="entry-mode-icon">
                        <i class="fas fa-file-signature"></i>
                    </div>
                    <h4>Single Entry</h4>
                    <p class="text-muted mb-2">Enter one emission record at a time</p>
                    <span class="badge bg-success">Recommended</span>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="entry-mode-card" id="quickEntryCard" onclick="setEntryMode('quick')">
                    <div class="entry-mode-icon">
                        <i class="fas fa-table"></i>
                    </div>
                    <h4>Quick Entry</h4>
                    <p class="text-muted mb-2">Enter multiple records quickly</p>
                    <span class="badge bg-primary">Fast bulk entry</span>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="entry-mode-card" id="templateEntryCard" onclick="setEntryMode('template')">
                    <div class="entry-mode-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h4>Template Based</h4>
                    <p class="text-muted mb-2">Use pre-defined templates</p>
                    <span class="badge bg-info">Consistent</span>
                </div>
            </div>
        </div>
        
        <!-- Single Entry Form -->
        <div class="form-container" id="singleEntryForm">
            <div class="form-header mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1">
                            <i class="fas fa-leaf text-success me-2"></i>
                            Enter Emission Data
                        </h4>
                        <p class="text-muted mb-0 small">Fill in the details below to record your greenhouse gas emissions</p>
                    </div>
                    <div class="form-steps-indicator">
                        <span class="step active" title="Step 1: Basic Information">
                            <i class="fas fa-check-circle"></i>
                        </span>
                        <span class="step" title="Step 2: Calculation">
                            <i class="fas fa-circle"></i>
                        </span>
                        <span class="step" title="Step 3: Additional Details">
                            <i class="fas fa-circle"></i>
                        </span>
                    </div>
                </div>
            </div>

        <form id="emissionRecordForm"
                  method="POST"
                  action="{{ route('emission-records.store') }}"
                  onsubmit="handleFormSubmit(event);">
                @csrf
            
            <!-- Basic Information Section -->
            <div class="form-section">
                <div class="form-section-header">
                    <div class="section-number">1</div>
                    <div class="section-title-content">
                        <h5 class="form-section-title mb-1">
                            <i class="fas fa-info-circle text-primary me-2"></i>Basic Information
                        </h5>
                        <p class="section-description">Start by providing the essential details about your emission record</p>
                    </div>
                </div>
                
                <div class="form-section-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="form-field-wrapper">
                                <label class="form-label required-label">
                                    <i class="fas fa-calendar-alt me-1"></i>Date
                                </label>
                                <input type="date" 
                                       class="form-control form-control-lg date-picker" 
                                       id="entryDate" 
                                       name="entryDate" 
                                       placeholder="Select date" 
                                       required>
                                <div class="field-help">
                                    <i class="fas fa-lightbulb me-1"></i>
                                    Select the date when emissions occurred
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-field-wrapper">
                                <label class="form-label required-label">
                                    <i class="fas fa-building me-1"></i>Facility / Location
                                </label>
                                <select class="form-select form-select-lg facility-select" 
                                        id="facilitySelect" 
                                        name="facilitySelect" 
                                        required>
                                    <option value="">Choose a facility...</option>
                                    @foreach(facilities() as $facility)
                                        <option value="{{ $facility->id }}">{{ $facility->name }}</option>
                                    @endforeach
                                </select>
                                <div class="field-help">
                                    <i class="fas fa-lightbulb me-1"></i>
                                    Choose the facility where emissions occurred
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-field-wrapper">
                                <label class="form-label">
                                    <i class="fas fa-map-marker-alt me-1"></i>Site
                                </label>
                                <select class="form-select form-select-lg site-select" 
                                        id="siteSelect" 
                                        name="siteSelect">
                                    <option value="">Select site (optional)...</option>
                                    @foreach(sites() as $site)
                                        <option value="{{ $site->id }}">{{ $site->name }}@if($site->location) - {{ $site->location }}@endif</option>
                                    @endforeach
                                </select>
                                <div class="field-help">
                                    <i class="fas fa-lightbulb me-1"></i>
                                    Optional: Select a specific site within the facility
                                </div>
                            </div>
                        </div>
                    
                        <div class="col-md-6">
                            <div class="form-field-wrapper">
                                <label class="form-label required-label">
                                    <i class="fas fa-layer-group me-1"></i>Scope Category
                                </label>
                                <select class="form-select form-select-lg" 
                                        id="scopeSelect" 
                                        name="scopeSelect" 
                                        required
                                        onchange="toggleScope3Fields()">
                                    <option value="">Select scope category...</option>
                                    <option value="1">
                                        <span class="scope-option">Scope 1</span> - Direct Emissions
                                    </option>
                                    <option value="2">
                                        <span class="scope-option">Scope 2</span> - Indirect Emissions (Purchased Energy)
                                    </option>
                                    <option value="3">
                                        <span class="scope-option">Scope 3</span> - Other Indirect Emissions
                                    </option>
                                </select>
                                <div class="field-help">
                                    <i class="fas fa-lightbulb me-1"></i>
                                    Select GHG Protocol scope category
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-field-wrapper">
                                <label class="form-label required-label">
                                    <i class="fas fa-smog me-1"></i>Emission Source
                                </label>
                                <select class="form-select form-select-lg emission-source-select" 
                                        id="emissionSourceSelect" 
                                        name="emissionSourceSelect" 
                                        required>
                                    <option value="">Select emission source...</option>
                                    @if(($scope1Sources ?? collect())->isEmpty() && ($scope2Sources ?? collect())->isEmpty() && ($scope3Sources ?? collect())->isEmpty())
                                        <option value="" disabled>No emission sources found. Please run: php artisan migrate:fresh --seed</option>
                                    @endif
                                    <optgroup label="Scope 1 Sources">
                                        @foreach(($scope1Sources ?? collect()) as $source)
                                            @php
                                                $factor = $source->emissionFactors->first();
                                                $unit = $factor ? $factor->unit : '';
                                                $factorValue = $factor ? $factor->factor_value : '';
                                            @endphp
                                            <option value="{{ $source->name }}" data-unit="{{ $unit }}" data-factor="{{ $factorValue }}">{{ $source->name }}</option>
                                        @endforeach
                                    </optgroup>
                                    <optgroup label="Scope 2 Sources">
                                        @foreach(($scope2Sources ?? collect()) as $source)
                                            @php
                                                $factor = $source->emissionFactors->first();
                                                $unit = $factor ? $factor->unit : '';
                                                $factorValue = $factor ? $factor->factor_value : '';
                                            @endphp
                                            <option value="{{ $source->name }}" data-unit="{{ $unit }}" data-factor="{{ $factorValue }}">{{ $source->name }}</option>
                                        @endforeach
                                    </optgroup>
                                    <optgroup label="Scope 3 Sources">
                                        @foreach(($scope3Sources ?? collect()) as $source)
                                            @php
                                                $factor = $source->emissionFactors->first();
                                                $unit = $factor ? $factor->unit : '';
                                                $factorValue = $factor ? $factor->factor_value : '';
                                            @endphp
                                            <option value="{{ $source->name }}" data-unit="{{ $unit }}" data-factor="{{ $factorValue }}">{{ $source->name }}</option>
                                        @endforeach
                                    </optgroup>
                                </select>
                                <div class="field-help">
                                    <i class="fas fa-lightbulb me-1"></i>
                                    Select the specific source of emissions
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Calculation Method Section -->
            <div class="form-section">
                <div class="form-section-header">
                    <div class="section-number">2</div>
                    <div class="section-title-content">
                        <h5 class="form-section-title mb-1">
                            <i class="fas fa-calculator text-primary me-2"></i>Calculation Method
                        </h5>
                        <p class="section-description">Choose how you want to enter the emission data</p>
                    </div>
                </div>
                
                <div class="form-section-body">
                    <div class="calculation-mode-selector">
                        <div class="calc-option" onclick="setCalculationMode('direct')">
                            <div class="calc-option-icon">
                                <i class="fas fa-keyboard"></i>
                            </div>
                            <div class="calc-option-content">
                                <h6 class="mb-1">Direct Entry</h6>
                                <p class="small text-muted mb-0">Enter CO₂e value directly if you already know it</p>
                            </div>
                            <div class="calc-option-badge">
                                <span class="badge bg-light text-dark">Quick</span>
                            </div>
                        </div>
                        <div class="calc-option active" onclick="setCalculationMode('activity', this)">
                            <div class="calc-option-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="calc-option-content">
                                <h6 class="mb-1">Activity-Based</h6>
                                <p class="small text-muted mb-0">Calculate from activity data and emission factor</p>
                            </div>
                            <div class="calc-option-badge">
                                <span class="badge bg-success">Recommended</span>
                            </div>
                        </div>
                    </div>
                
                    <!-- Direct Entry Section -->
                    <div id="directEntrySection" style="display: none;">
                        <div class="row g-4 mt-2">
                            <div class="col-md-6">
                                <div class="form-field-wrapper">
                                    <label class="form-label required-label">
                                        <i class="fas fa-weight me-1"></i>CO₂e Value
                                    </label>
                                    <div class="input-group input-group-lg">
                                        <input type="number" 
                                               class="form-control" 
                                               id="co2eValue" 
                                               name="co2eValue" 
                                               step="0.01" 
                                               min="0" 
                                               placeholder="0.00" 
                                               required>
                                        <span class="input-group-text bg-light">
                                            <strong>tCO₂e</strong>
                                        </span>
                                    </div>
                                    <div class="field-help">
                                        <i class="fas fa-lightbulb me-1"></i>
                                        Enter the total CO₂ equivalent value
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-field-wrapper">
                                    <label class="form-label">
                                        <i class="fas fa-certificate me-1"></i>Confidence Level
                                    </label>
                                    <select class="form-select form-select-lg" 
                                            id="confidenceLevel" 
                                            name="confidenceLevel">
                                        <option value="high">
                                            <i class="fas fa-check-circle"></i> High Confidence
                                        </option>
                                        <option value="medium" selected>Medium Confidence</option>
                                        <option value="low">Low Confidence</option>
                                        <option value="estimated">Estimated</option>
                                    </select>
                                    <div class="field-help">
                                        <i class="fas fa-lightbulb me-1"></i>
                                        How confident are you in this data?
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                
                    <!-- Activity-Based Calculation Section -->
                    <div id="activityBasedSection" class="mt-4">
                        <!-- Hidden input for calculated CO₂e value (always available for form submission) -->
                        <input type="hidden" id="co2eValueHidden" name="co2eValue" value="0.00">
                        
                        <div class="row g-4">
                            <!-- Activity Data -->
                            <div class="col-md-4">
                                <div class="form-field-wrapper">
                                    <label class="form-label required-label">
                                        <i class="fas fa-tachometer-alt me-1"></i>Activity Data
                                    </label>
                                    <div class="input-group input-group-lg">
                                        <input type="number" 
                                               class="form-control" 
                                               id="activityData" 
                                               name="activityData" 
                                               step="0.01" 
                                               min="0" 
                                               placeholder="0.00">
                                        <select class="form-select" id="activityUnitSelect">
                                            <option value="kWh">kWh</option>
                                            <option value="liters">Liters</option>
                                            <option value="m³">m³</option>
                                            <option value="km">km</option>
                                            <option value="kg">kg</option>
                                        </select>
                                    </div>
                                    <div class="field-help">
                                        <i class="fas fa-lightbulb me-1"></i>
                                        Enter the activity amount (e.g., energy consumed, distance traveled)
                                    </div>
                                </div>
                            </div>

                            <!-- Emission Factor -->
                            <div class="col-md-4">
                                <div class="form-field-wrapper">
                                    <label class="form-label required-label">
                                        <i class="fas fa-exchange-alt me-1"></i>Emission Factor
                                    </label>
                                    <div class="input-group input-group-lg">
                                        <input type="number" 
                                               class="form-control" 
                                               id="emissionFactor" 
                                               name="emissionFactor" 
                                               step="0.00001" 
                                               min="0" 
                                               placeholder="0.0000">
                                        <span class="input-group-text bg-light">
                                            <small id="factorUnitLabel">tCO₂e/unit</small>
                                        </span>
                                    </div>
                                    <div class="field-help">
                                        <i class="fas fa-lightbulb me-1"></i>
                                        Standard emission factor for this activity type
                                    </div>
                                </div>
                            </div>

                            <!-- Calculated Result Display -->
                            <div class="col-md-4">
                                <label class="form-label">
                                    <i class="fas fa-chart-bar me-1"></i>Calculated Result
                                </label>
                                <div class="calculation-display-enhanced">
                                    <div class="calculation-formula-small mb-2" id="calculationFormula">
                                        <span class="text-muted">Formula:</span> 0.00 × 0.0000 = 
                                    </div>
                                    <div class="calculated-result-large">
                                        <span id="calculatedResult">0.00</span>
                                        <span class="result-unit">tCO₂e</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Scope 3 Specific Fields (Only visible when Scope 3 is selected) -->
            <div class="form-section scope3-specific-section" id="scope3SpecificSection" style="display: none;">
                <div class="form-section-header">
                    <div class="section-number">2.5</div>
                    <div class="section-title-content">
                        <h5 class="form-section-title mb-1">
                            <i class="fas fa-network-wired text-primary me-2"></i>Scope 3 Specific Information
                        </h5>
                        <p class="section-description">Additional fields required for Scope 3 emissions tracking</p>
                    </div>
                </div>
                
                <div class="form-section-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="form-field-wrapper">
                                <label class="form-label">
                                    <i class="fas fa-tags me-1"></i>Scope 3 Category
                                </label>
                                <select class="form-select form-select-lg scope3-category-select" 
                                        id="scope3Category" 
                                        name="scope3_category_id">
                                    <option value="">Select category (optional)...</option>
                                    @foreach(scope3_categories() as $category)
                                        <option value="{{ $category->id }}" data-type="{{ $category->category_type }}">
                                            {{ $category->code }} - {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="field-help">
                                    <i class="fas fa-lightbulb me-1"></i>
                                    Select the GHG Protocol Scope 3 category (1-15)
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-field-wrapper">
                                <label class="form-label">
                                    <i class="fas fa-truck me-1"></i>Supplier
                                </label>
                                <select class="form-select form-select-lg supplier-select" 
                                        id="supplierId" 
                                        name="supplier_id">
                                    <option value="">Select supplier (optional)...</option>
                                    @foreach(suppliers() as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                                <div class="field-help">
                                    <i class="fas fa-lightbulb me-1"></i>
                                    Select the supplier if applicable
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-field-wrapper">
                                <label class="form-label">
                                    <i class="fas fa-calculator me-1"></i>Calculation Method
                                </label>
                                <select class="form-select form-select-lg calculation-method-select" 
                                        id="calculationMethod" 
                                        name="calculation_method"
                                        onchange="toggleScope3CalculationMethod()">
                                    <option value="activity-based" selected>Activity-Based</option>
                                    <option value="spend-based">Spend-Based</option>
                                    <option value="hybrid">Hybrid</option>
                                </select>
                                <div class="field-help">
                                    <i class="fas fa-lightbulb me-1"></i>
                                    Choose how emissions are calculated
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-field-wrapper">
                                <label class="form-label">
                                    <i class="fas fa-certificate me-1"></i>Data Quality
                                </label>
                                <select class="form-select form-select-lg data-quality-select" 
                                        id="dataQuality" 
                                        name="data_quality">
                                    <option value="primary">Primary Data</option>
                                    <option value="secondary">Secondary Data</option>
                                    <option value="estimated" selected>Estimated</option>
                                </select>
                                <div class="field-help">
                                    <i class="fas fa-lightbulb me-1"></i>
                                    Quality rating of the data source
                                </div>
                            </div>
                        </div>
                        
                        <!-- Spend-Based Fields (Hidden by default) -->
                        <div class="col-12 spend-based-fields" id="spendBasedFields" style="display: none;">
                            <div class="alert alert-info mb-3">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Spend-Based Calculation:</strong> Enter the spend amount and select sector to automatically calculate emissions using EIO factors.
                            </div>
                            
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <div class="form-field-wrapper">
                                        <label class="form-label">
                                            <i class="fas fa-dollar-sign me-1"></i>Spend Amount
                                        </label>
                                        <div class="input-group input-group-lg">
                                            <input type="number" 
                                                   class="form-control" 
                                                   id="spendAmount" 
                                                   name="spend_amount" 
                                                   step="0.01" 
                                                   min="0" 
                                                   placeholder="0.00"
                                                   oninput="calculateScope3SpendBased()">
                                            <select class="form-select" name="spend_currency" id="spendCurrency" style="max-width: 120px;" onchange="calculateScope3SpendBased()">
                                                <option value="USD" selected>USD</option>
                                                <option value="EUR">EUR</option>
                                                <option value="GBP">GBP</option>
                                                <option value="JPY">JPY</option>
                                                <option value="CAD">CAD</option>
                                                <option value="AUD">AUD</option>
                                            </select>
                                        </div>
                                        <div class="field-help">
                                            <i class="fas fa-lightbulb me-1"></i>
                                            Enter the monetary spend amount
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-field-wrapper">
                                        <label class="form-label">
                                            <i class="fas fa-industry me-1"></i>Sector Code
                                        </label>
                                        <input type="text" 
                                               class="form-control form-control-lg" 
                                               id="sectorCode" 
                                               name="sector_code" 
                                               placeholder="e.g., 31-33"
                                               oninput="calculateScope3SpendBased()">
                                        <div class="field-help">
                                            <i class="fas fa-lightbulb me-1"></i>
                                            NAICS or similar sector code for EIO factor lookup
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-field-wrapper">
                                        <label class="form-label">
                                            <i class="fas fa-globe me-1"></i>Country
                                        </label>
                                        <select class="form-select form-select-lg" 
                                                id="country" 
                                                name="country"
                                                onchange="calculateScope3SpendBased()">
                                            <option value="USA" selected>United States</option>
                                            <option value="CAN">Canada</option>
                                            <option value="GBR">United Kingdom</option>
                                            <option value="DEU">Germany</option>
                                            <option value="FRA">France</option>
                                            <option value="JPN">Japan</option>
                                            <option value="CHN">China</option>
                                            <option value="AUS">Australia</option>
                                        </select>
                                        <div class="field-help">
                                            <i class="fas fa-lightbulb me-1"></i>
                                            Country for EIO factor lookup
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-12">
                                    <div class="alert alert-warning" id="spendCalculationResult" style="display: none;">
                                        <i class="fas fa-calculator me-2"></i>
                                        <span id="spendCalculationText">Enter spend amount and sector code to calculate emissions</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Additional Details Section -->
            <div class="form-section">
                <div class="form-section-header">
                    <div class="section-number">3</div>
                    <div class="section-title-content">
                        <h5 class="form-section-title mb-1">
                            <i class="fas fa-file-alt text-primary me-2"></i>Additional Details
                        </h5>
                        <p class="section-description">Add optional information to improve data tracking and reporting</p>
                    </div>
                </div>
                
                <div class="form-section-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="form-field-wrapper">
                                <label class="form-label">
                                    <i class="fas fa-sitemap me-1"></i>Department / Cost Center
                                </label>
                                <select class="form-select form-select-lg" 
                                        id="departmentSelect" 
                                        name="departmentSelect" 
                                        required>
                                    <option value="">Select department...</option>
                                    @foreach(departments() as $department)
                                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                                    @endforeach
                                </select>
                                <div class="field-help">
                                    <i class="fas fa-lightbulb me-1"></i>
                                    Organize emissions by department or cost center
                                </div>
                            </div>
                        </div>
                    
                        <div class="col-md-6">
                            <div class="form-field-wrapper">
                                <label class="form-label">
                                    <i class="fas fa-database me-1"></i>Data Source
                                </label>
                                <select class="form-select form-select-lg" 
                                        id="dataSource" 
                                        name="dataSource">
                                    <option value="manual" selected>
                                        <i class="fas fa-keyboard"></i> Manual Entry
                                    </option>
                                    <option value="meter">Meter Reading</option>
                                    <option value="invoice">Utility Invoice</option>
                                    <option value="estimate">Estimate</option>
                                </select>
                                <div class="field-help">
                                    <i class="fas fa-lightbulb me-1"></i>
                                    How was this data obtained?
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="form-field-wrapper">
                                <label class="form-label">
                                    <i class="fas fa-sticky-note me-1"></i>Notes / Comments
                                </label>
                                <textarea class="form-control form-control-lg" 
                                          id="entryNotes" 
                                          name="entryNotes" 
                                          rows="4" 
                                          placeholder="Add any additional information, context, assumptions, or specific details about this emission record..."></textarea>
                                <div class="field-help">
                                    <i class="fas fa-lightbulb me-1"></i>
                                    Optional: Add context, assumptions, or specific details
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-field-wrapper">
                                <label class="form-label">
                                    <i class="fas fa-paperclip me-1"></i>Supporting Documents
                                </label>
                                <input
                                    type="file"
                                    class="form-control form-control-lg"
                                    id="supportingDocuments"
                                    name="supporting_documents[]"
                                    multiple
                                    accept=".pdf,.jpg,.jpeg,.png,.webp,.xlsx,.xls,.csv"
                                >
                                <div class="field-help">
                                    <i class="fas fa-lightbulb me-1"></i>
                                    Optional: Upload invoices, meter screenshots, spreadsheets, or other evidence (max 10MB per file)
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="form-actions-wrapper">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <button type="submit" 
                            name="status" 
                            value="draft" 
                            class="btn btn-lg btn-outline-secondary">
                        <i class="fas fa-save me-2"></i>Save as Draft
                    </button>
                    
                    <div class="d-flex gap-2">
                        <button type="button" 
                                class="btn btn-lg btn-outline-primary" 
                                onclick="clearForm()">
                            <i class="fas fa-redo me-2"></i>Clear Form
                        </button>
                        <button type="submit" 
                                name="status" 
                                value="active" 
                                class="btn btn-lg btn-success">
                            <i class="fas fa-check-circle me-2"></i>Submit Entry
                        </button>
                    </div>
                </div>
            </div>
          </form>
        </div>


        <!-- Quick Entry Table (Hidden by default) -->
        <div class="form-container" id="quickEntryForm" style="display: none;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">Quick Entry Table</h4>
                <div>
                    <button class="btn btn-outline-primary me-2" onclick="addQuickEntryRow()">
                        <i class="fas fa-plus me-2"></i>Add Row
                    </button>
                    <button class="btn btn-success" onclick="saveAllQuickEntries()">
                        <i class="fas fa-save me-2"></i>Save All
                    </button>
                </div>
            </div>
            
            <div class="alert alert-info mb-4">
                <i class="fas fa-info-circle me-2"></i>
                Enter multiple emission records quickly. All required fields are marked with an asterisk (*).
            </div>
            
            <div class="quick-entry-table">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>Date *</th>
                                <th>Facility *</th>
                                <th>Scope *</th>
                                <th>Source *</th>
                                <th>CO₂e (t) *</th>
                                <th>Notes</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="quickEntryTableBody">
                            <!-- Rows will be added dynamically -->
                        </tbody>
                    </table>
                </div>
                <div class="table-controls">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            <span id="rowCount">0</span> rows entered
                        </div>
                        <div>
                            <button class="btn btn-sm btn-outline-secondary me-2" onclick="addQuickEntryRow()">
                                <i class="fas fa-plus"></i> Add Row
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="clearQuickEntryTable()">
                                <i class="fas fa-trash"></i> Clear All
                            </button>
                        </div>
                    </div>
                </div>
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
</div>
@endsection

@push('styles')
   @include('emission_records.css')

@endpush

@push('scripts')

<script>
    // Convert PHP helper result to JS array
    const facilitiesData = @json(Facilities());
    // Dynamic emission factors loaded from database (keyed by emission source name)
    const emissionFactors = @json($emissionFactorsMap ?? []);
    const allEmissionSourceNames = @json($allEmissionSourceNames ?? []);
    const emissionSourceNamesByScope = @json($emissionSourceNamesByScope ?? []);
</script>

<script>
        // Entry mode state
        let currentEntryMode = 'single';
        let calculationMode = 'activity';
        let quickEntryRows = 0;

// Build unit/factor helpers from DB-driven emissionFactors map
const allowedUnits = {};
const emissionUnits = { default: { unit: 'unit', factorUnit: 'tCO₂e/unit' } };
Object.keys(emissionFactors || {}).forEach((name) => {
    const unit = emissionFactors[name]?.unit || 'unit';
    allowedUnits[name] = [unit];
    emissionUnits[name] = { unit, factorUnit: `tCO₂e/${unit}` };
});

// Update activity unit options based on selected emission source
function updateUnitOptions() {
    const source = document.getElementById('emissionSourceSelect').value;
    const unitSelect = document.getElementById('activityUnitSelect');
    const validUnits = allowedUnits[source] || [];

    Array.from(unitSelect.options).forEach(option => {
        option.disabled = !validUnits.includes(option.value);
    });

    // Auto-select the first valid unit
    if (validUnits.length > 0) {
        // Ensure the unit option exists, otherwise create it
        const u = validUnits[0];
        if (![...unitSelect.options].some(o => o.value === u)) {
            const opt = document.createElement('option');
            opt.value = u;
            opt.textContent = u;
            unitSelect.appendChild(opt);
        }
        unitSelect.value = u;
    } else {
        unitSelect.value = '';
    }

    // Auto-fill emission factor + unit label (prefer option dataset; fallback to map)
    const option = document.getElementById('emissionSourceSelect').selectedOptions?.[0];
    const unitFromOpt = option?.dataset?.unit;
    const factorFromOpt = option?.dataset?.factor;

    const unitResolved = unitFromOpt || emissionFactors[source]?.unit || validUnits[0] || 'unit';
    const factorResolved = (factorFromOpt !== undefined && factorFromOpt !== null && factorFromOpt !== '')
        ? factorFromOpt
        : emissionFactors[source]?.factor;

    const factorLabel = document.getElementById('factorUnitLabel');
    if (factorLabel) {
        factorLabel.textContent = `tCO₂e/${unitResolved || 'unit'}`;
    }

    // Lock unit dropdown to the resolved unit (create option if needed)
    if (unitResolved) {
        if (![...unitSelect.options].some(o => o.value === unitResolved)) {
            const opt = document.createElement('option');
            opt.value = unitResolved;
            opt.textContent = unitResolved;
            unitSelect.appendChild(opt);
        }
        unitSelect.value = unitResolved;
    }

    if (typeof factorResolved !== 'undefined' && factorResolved !== null && factorResolved !== '') {
        const factorInput = document.getElementById('emissionFactor');
        if (factorInput) factorInput.value = parseFloat(factorResolved) || 0;
    }

    updateCalculation();
}

function rebuildEmissionSourceOptionsForScope(scopeValue) {
    const select = document.getElementById('emissionSourceSelect');
    if (!select) return;

    // Keep the initial full list so user can clear scope and see everything again
    if (!select.dataset.fullOptionsHtml) {
        select.dataset.fullOptionsHtml = select.innerHTML;
    }

    // If no scope selected, restore original options
    if (!scopeValue) {
        select.innerHTML = select.dataset.fullOptionsHtml;
        if (typeof $ !== 'undefined' && $.fn.select2) {
            $(select).trigger('change.select2');
        }
        return;
    }

    const names = emissionSourceNamesByScope?.[scopeValue] || [];
    const current = select.value;

    // Rebuild with only the selected scope names
    select.innerHTML = '<option value="">Select emission source...</option>';
    if (!names.length) {
        const opt = document.createElement('option');
        opt.value = '';
        opt.disabled = true;
        opt.textContent = 'No sources found for this scope (check Settings → Emission Sources)';
        select.appendChild(opt);
    } else {
        names.forEach((name) => {
            const opt = document.createElement('option');
            opt.value = name;
            opt.textContent = name;

            // Preserve metadata for auto-fill
            const meta = emissionFactors?.[name];
            if (meta?.unit) opt.dataset.unit = meta.unit;
            if (typeof meta?.factor !== 'undefined' && meta?.factor !== null) opt.dataset.factor = meta.factor;

            select.appendChild(opt);
        });
    }

    // If previous selection no longer valid, clear it
    if (current && !names.includes(current)) {
        select.value = '';
    } else if (current) {
        select.value = current;
    }

    if (typeof $ !== 'undefined' && $.fn.select2) {
        $(select).trigger('change.select2');
    }
    updateUnitOptions();
}

// Update calculation formula & result
function updateCalculation() {
    const activity = parseFloat(document.getElementById('activityData').value) || 0;
    const factor = parseFloat(document.getElementById('emissionFactor').value) || 0;
    const result = activity * factor;

    const unit = document.getElementById('activityUnitSelect').value || 'unit';

    document.getElementById('calculationFormula').textContent =
        `${activity.toFixed(2)} ${unit} × ${factor.toFixed(6)} tCO₂e/${unit} = ${result.toFixed(2)} tCO₂e`;

    document.getElementById('calculatedResult').textContent =
        `${result.toFixed(2)} tCO₂e`;

    // Update both visible and hidden CO₂e inputs
    const co2eValueInput = document.getElementById('co2eValue');
    const co2eValueHidden = document.getElementById('co2eValueHidden');
    const value = result.toFixed(2);
    
    if (co2eValueInput) {
        co2eValueInput.value = value;
    }
    if (co2eValueHidden) {
        co2eValueHidden.value = value;
    }
}

// Event listeners
document.getElementById('emissionSourceSelect').addEventListener('change', updateUnitOptions);
document.getElementById('activityData').addEventListener('input', updateCalculation);
document.getElementById('emissionFactor').addEventListener('input', updateCalculation);
document.getElementById('activityUnitSelect').addEventListener('change', updateCalculation);

        
        // Initialize components
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize date picker
            flatpickr(".date-picker", {
                dateFormat: "Y-m-d",
                defaultDate: new Date(),
                maxDate: new Date()
            });
            
            // Initialize Select2 for dropdowns (only if jQuery and Select2 are available)
            if (typeof $ !== 'undefined' && $.fn.select2) {
                $('.facility-select').select2({
                    placeholder: "Select facility...",
                    allowClear: true,
                    width: '100%'
                });
                
                $('.site-select').select2({
                    placeholder: "Select site (optional)...",
                    allowClear: true,
                    width: '100%'
                });
                
                $('.emission-source-select').select2({
                    placeholder: "Select emission source...",
                    allowClear: true,
                    width: '100%'
                });
                
                $('.scope3-category-select').select2({
                    placeholder: "Select category (optional)...",
                    allowClear: true,
                    width: '100%'
                });
                
                $('.supplier-select').select2({
                    placeholder: "Select supplier (optional)...",
                    allowClear: true,
                    width: '100%'
                });
            }
            
            // Initialize Scope 3 fields visibility
            toggleScope3Fields();
            // Filter emission sources by selected scope
            rebuildEmissionSourceOptionsForScope(document.getElementById('scopeSelect')?.value || '');
            
            // Set Activity-Based as default calculation mode
            setCalculationMode('activity');
            
            // Update calculation when activity data changes
            document.getElementById('activityData')?.addEventListener('input', updateCalculation);
            document.getElementById('emissionFactor')?.addEventListener('input', updateCalculation);
            document.getElementById('emissionSourceSelect')?.addEventListener('change', updateUnitOptions);
            
            // Add first quick entry row
            addQuickEntryRow();
        });
        
        // Set entry mode
        function setEntryMode(mode) {
            currentEntryMode = mode;
            
            // Update card highlights
            document.getElementById('singleEntryCard').classList.remove('active');
            document.getElementById('quickEntryCard').classList.remove('active');
            document.getElementById('templateEntryCard').classList.remove('active');
            
            document.getElementById(`${mode}EntryCard`).classList.add('active');
            
            // Show/hide forms
            document.getElementById('singleEntryForm').style.display = mode === 'single' ? 'block' : 'none';
            document.getElementById('quickEntryForm').style.display = mode === 'quick' ? 'block' : 'none';
            document.getElementById('templateEntryForm').style.display = mode === 'template' ? 'block' : 'none';
        }

        // Set calculation mode
        function setCalculationMode(mode, el = null) {
    calculationMode = mode;

    document.querySelectorAll('.calc-option').forEach(option => {
        option.classList.remove('active');
    });

    // Find and activate the correct option
    const options = document.querySelectorAll('.calc-option');
    if (mode === 'direct') {
        options[0].classList.add('active');
    } else if (mode === 'activity') {
        options[1].classList.add('active');
    }

    if (el) {
        el.classList.add('active');
    }

    document.getElementById('directEntrySection').style.display =
        mode === 'direct' ? 'block' : 'none';

    document.getElementById('activityBasedSection').style.display =
        mode === 'activity' ? 'block' : 'none';

    // Handle form input names based on mode
    const co2eValueInput = document.getElementById('co2eValue');
    const co2eValueHidden = document.getElementById('co2eValueHidden');
    
    if (mode === 'direct') {
        // In direct mode, use visible input
        if (co2eValueInput) {
            co2eValueInput.setAttribute('name', 'co2eValue');
            co2eValueInput.setAttribute('required', 'required');
        }
        if (co2eValueHidden) {
            co2eValueHidden.removeAttribute('name');
        }
    } else {
        // In activity mode, use hidden input
        if (co2eValueInput) {
            co2eValueInput.removeAttribute('name');
            co2eValueInput.removeAttribute('required');
        }
        if (co2eValueHidden) {
            co2eValueHidden.setAttribute('name', 'co2eValue');
        }
        updateCalculation();
    }
}

        
// NOTE: updateCalculation/updateUnitOptions are defined earlier (single source of truth).

        
function addQuickEntryRow() {
    if (!facilitiesData.length) {
        alert('No facilities available!');
        return;
    }

    quickEntryRows++;
    const rowId = `row-${quickEntryRows}`;

    const row = document.createElement('tr');
    row.className = 'entry-row';
    row.id = rowId;

    // Build facility options dynamically from helper
    let facilityOptions = `<option value="">Select facility...</option>`;
    facilitiesData.forEach(f => {
        // IMPORTANT: backend stores facility as string name (not ID)
        facilityOptions += `<option value="${String(f.name).replace(/"/g, '&quot;')}">${f.name}</option>`;
    });

    function buildSourceOptionsForScope(scopeValue) {
        const names = emissionSourceNamesByScope?.[scopeValue] || allEmissionSourceNames || [];
        let html = `<option value=\"\">Select source...</option>`;
        (names || []).forEach(name => {
            html += `<option value=\"${String(name).replace(/\"/g, '&quot;')}\">${name}</option>`;
        });
        return html;
    }

    row.innerHTML = `
        <td>${quickEntryRows}</td>
        <td>
            <input type="date" class="form-control form-control-sm quick-entry-date" required>
        </td>
        <td>
            <select class="form-select form-select-sm quick-entry-facility" required>
                ${facilityOptions}
            </select>
        </td>
        <td>
            <select class="form-select form-select-sm quick-entry-scope" required>
                <option value="">Select scope...</option>
                <option value="1">Scope 1</option>
                <option value="2">Scope 2</option>
                <option value="3">Scope 3</option>
            </select>
        </td>
        <td>
            <select class="form-select form-select-sm quick-entry-source" required>
                ${buildSourceOptionsForScope('')}
            </select>
        </td>
        <td>
            <input type="number" class="form-control form-control-sm quick-entry-co2e" step="0.01" min="0" placeholder="0.00" required>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm quick-entry-notes" placeholder="Optional notes">
        </td>
        <td>
            <div class="action-buttons">
                <button class="action-btn save-btn" onclick="saveQuickEntry('${rowId}')">
                    <i class="fas fa-check"></i>
                </button>
                <button class="action-btn delete-btn" onclick="deleteQuickEntry('${rowId}')">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </td>
    `;

    document.getElementById('quickEntryTableBody').appendChild(row);

    // Filter sources when scope changes (per-row)
    const scopeEl = row.querySelector('.quick-entry-scope');
    const sourceEl = row.querySelector('.quick-entry-source');
    if (scopeEl && sourceEl) {
        scopeEl.addEventListener('change', () => {
            const selected = sourceEl.value;
            sourceEl.innerHTML = buildSourceOptionsForScope(scopeEl.value);
            if (selected) {
                // try keep selection if still valid
                const names = emissionSourceNamesByScope?.[scopeEl.value] || [];
                if (names.includes(selected)) {
                    sourceEl.value = selected;
                }
            }
        });
    }

    // Initialize date picker
    flatpickr(row.querySelector('.quick-entry-date'), {
        dateFormat: "Y-m-d",
        defaultDate: new Date(),
        maxDate: new Date()
    });
}

        
        // Save quick entry row
        function saveQuickEntry(rowId) {
            const row = document.getElementById(rowId);
            row.classList.add('editing');
            
            setTimeout(() => {
                row.classList.remove('editing');
            }, 1000);
            
            showToast('Row saved successfully', 'success');
        }
        
        // Delete quick entry row
        function deleteQuickEntry(rowId) {
            if (confirm('Are you sure you want to delete this row?')) {
                const row = document.getElementById(rowId);
                row.style.opacity = '0.5';
                
                setTimeout(() => {
                    row.remove();
                    updateRowCount();
                    renumberRows();
                    showToast('Row deleted', 'success');
                }, 300);
            }
        }
        
        // Renumber table rows
        function renumberRows() {
            const rows = document.querySelectorAll('#quickEntryTableBody tr');
            rows.forEach((row, index) => {
                row.cells[0].textContent = index + 1;
            });
            quickEntryRows = rows.length;
        }
        
        // Update row count
        function updateRowCount() {
            const rows = document.querySelectorAll('#quickEntryTableBody tr').length;
            document.getElementById('rowCount').textContent = rows;
        }
        
        // Clear quick entry table
        function clearQuickEntryTable() {
            if (confirm('Are you sure you want to clear all rows?')) {
                document.getElementById('quickEntryTableBody').innerHTML = '';
                quickEntryRows = 0;
                updateRowCount();
                showToast('All rows cleared', 'success');
            }
        }
        
        // Save all quick entries
        function saveAllQuickEntries(status = 'active') {
            const rows = document.querySelectorAll('#quickEntryTableBody tr');
            let entries = [];
            let valid = true;

            rows.forEach(row => {
        // Clear previous invalid markers for this row
        row.querySelectorAll('input, select').forEach(i => i.classList.remove('is-invalid'));

        const dateEl = row.querySelector('.quick-entry-date');
        const facilityEl = row.querySelector('.quick-entry-facility');
        const scopeEl = row.querySelector('.quick-entry-scope');
        const sourceEl = row.querySelector('.quick-entry-source');
        const co2eEl = row.querySelector('.quick-entry-co2e');

        // Validate required fields (row-level)
        const required = [
            { el: dateEl, name: 'Date' },
            { el: facilityEl, name: 'Facility' },
            { el: scopeEl, name: 'Scope' },
            { el: sourceEl, name: 'Emission Source' },
            { el: co2eEl, name: 'CO₂e' },
        ];

        const missing = required.filter(r => !r.el || !r.el.value);
        if (missing.length) {
            valid = false;
            missing.forEach(r => r.el && r.el.classList.add('is-invalid'));
        }

                const entry = {
            entryDate: dateEl?.value,
            facilitySelect: facilityEl?.value,
            scopeSelect: scopeEl?.value,
            emissionSourceSelect: sourceEl?.value,
            co2eValue: co2eEl?.value,
                    confidenceLevel: 'medium', // default for quick entry
                    entryNotes: row.querySelector('.quick-entry-notes').value,
                    dataSource: 'manual',
                    departmentSelect: null
                };

                entries.push(entry);
            });

            if (!valid) {
        showToast('Please fix the highlighted fields in the table (missing required values).', 'error');
                return;
            }

            // Send all entries to the same store route as single entry
            fetch("{{ route('emission-records.store') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ entries, status })
            })
            .then(async res => {
                if (!res.ok) {
                    const error = await res.json();
                    // Show row-level server validation if present
                    if (error?.errors?.row && error?.errors?.messages) {
                        const rowNum = error.errors.row;
                        const messages = error.errors.messages;
                        const firstKey = Object.keys(messages)[0];
                        const firstMsg = messages[firstKey]?.[0] || 'Validation error';
                        throw new Error(`Row ${rowNum}: ${firstMsg}`);
                    }
                    throw new Error(error.message || 'Server error');
                }
                return res.json();
            })
            .then(data => {
                showToast(`${rows.length} records saved successfully`, 'success');
                clearQuickEntryTable();
                addQuickEntryRow(); // Add a new blank row
            })
            .catch(err => showToast(err.message, 'error'));
        }



        
        // Use template
        function useTemplate(templateName) {
            setEntryMode('single');
            
            // Pre-fill based on template
            switch(templateName) {
                case 'electricity':
                    document.getElementById('scopeSelect').value = '2';
                    selectEmissionSourceByText(/electric/i);
                    setCalculationMode('activity');
                    break;
                case 'fleet':
                    document.getElementById('scopeSelect').value = '1';
                    selectEmissionSourceByText(/gasoline|diesel|vehicle/i);
                    setCalculationMode('activity');
                    break;
                case 'travel':
                    document.getElementById('scopeSelect').value = '3';
                    selectEmissionSourceByText(/travel/i);
                    break;
                case 'waste':
                    document.getElementById('scopeSelect').value = '3';
                    selectEmissionSourceByText(/waste/i);
                    setCalculationMode('activity');
                    break;
            }
            
            // Update Select2 values if jQuery is available
            if (typeof $ !== 'undefined' && $.fn.select2) {
                $('.emission-source-select').trigger('change');
            }
            
            showToast(`Template "${templateName}" applied`, 'success');
        }

        function selectEmissionSourceByText(regex) {
            const select = document.getElementById('emissionSourceSelect');
            if (!select) return;
            const opt = [...select.options].find(o => o.value && regex.test(o.textContent || ''));
            if (opt) {
                select.value = opt.value;
                updateUnitOptions();
            }
        }
        
        // Save as draft
        function saveAsDraft() {
            // Validate required fields
            const requiredFields = document.querySelectorAll('#singleEntryForm [required]');
            let valid = true;
            
            requiredFields.forEach(field => {
                if (!field.value) {
                    field.classList.add('is-invalid');
                    valid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!valid) {
                showToast('Please fill all required fields', 'error');
                return;
            }
            
            showToast('Entry saved as draft', 'success');
        }
        
        // Handle form submission with AJAX
        function handleFormSubmit(event) {
            event.preventDefault(); // Prevent default form submission
            
            const form = event.target; // Get the form from the event
            const formData = new FormData(form);
            const submitButton = document.activeElement; // Get the clicked button
            
            // Determine status from the button clicked
            const status = submitButton.name === 'status' ? submitButton.value : 'active';
            formData.set('status', status);
            
            // Show loading state
            const originalButtonText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
            
            // Submit via AJAX
            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(async response => {
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.message || data.error || 'Validation error');
                }
                
                return data;
            })
            .then(data => {
                // Success - Show toast notification
                const message = data.message || 'Emission record saved successfully!';
                showToast(message, 'success');
                
                // Reset form
                form.reset();
                // Reset Select2 dropdowns if jQuery is available
                if (typeof $ !== 'undefined' && $.fn.select2) {
                    $('.facility-select').val(null).trigger('change');
                    $('.emission-source-select').val(null).trigger('change');
                } else {
                    // Fallback to vanilla JS
                    const facilitySelect = document.querySelector('.facility-select');
                    const sourceSelect = document.querySelector('.emission-source-select');
                    if (facilitySelect) facilitySelect.value = '';
                    if (sourceSelect) sourceSelect.value = '';
                }
                
                // Reset calculation display
                if (document.getElementById('calculatedResult')) {
                    document.getElementById('calculatedResult').textContent = '0.00';
                    document.getElementById('calculationFormula').textContent = '0.00 × 0.0000 = 0.00 tCO₂e';
                }
                
                // Remove validation classes
                document.querySelectorAll('.is-invalid').forEach(el => {
                    el.classList.remove('is-invalid');
                });
                
                // Reset button state after a short delay
                setTimeout(() => {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;
                }, 1000);
            })
            .catch(error => {
                // Error - Show toast notification
                const errorMessage = error.message || 'An error occurred while saving. Please try again.';
                showToast(errorMessage, 'error');
                
                // Reset button state
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            });
        }
        
        // Legacy function - kept for compatibility
        function submitEntry() {
            handleFormSubmit(event);
        }

        
        // Clear form
        function clearForm() {
            const form = document.getElementById('emissionRecordForm');
            if (form) {
                form.reset();
            }
            
            // Reset Select2 dropdowns if jQuery is available
            if (typeof $ !== 'undefined' && $.fn.select2) {
                $('.facility-select').val(null).trigger('change');
                $('.site-select').val(null).trigger('change');
                $('.emission-source-select').val(null).trigger('change');
                $('.scope3-category-select').val(null).trigger('change');
                $('.supplier-select').val(null).trigger('change');
            } else {
                // Fallback to vanilla JS
                const facilitySelect = document.querySelector('.facility-select');
                const siteSelect = document.querySelector('.site-select');
                const sourceSelect = document.querySelector('.emission-source-select');
                const scope3Category = document.getElementById('scope3Category');
                const supplier = document.getElementById('supplierId');
                if (facilitySelect) facilitySelect.value = '';
                if (siteSelect) siteSelect.value = '';
                if (sourceSelect) sourceSelect.value = '';
                if (scope3Category) scope3Category.value = '';
                if (supplier) supplier.value = '';
            }
            
            // Hide Scope 3 section
            const scope3Section = document.getElementById('scope3SpecificSection');
            if (scope3Section) scope3Section.style.display = 'none';
            
            // Reset calculation method
            const calcMethod = document.getElementById('calculationMethod');
            if (calcMethod) {
                calcMethod.value = 'activity-based';
                toggleScope3CalculationMethod();
            }
            
            // Hide spend calculation result
            const spendResult = document.getElementById('spendCalculationResult');
            if (spendResult) spendResult.style.display = 'none';
            
            // Remove validation classes
            document.querySelectorAll('.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });
            
            // Reset calculation display
            if (document.getElementById('calculatedResult')) {
                document.getElementById('calculatedResult').textContent = '0.00';
                if (document.getElementById('calculationFormula')) {
                    document.getElementById('calculationFormula').textContent = '0.00 × 0.0000 = 0.00 tCO₂e';
                }
            }
            
            showToast('Form cleared', 'info');
        }
        
        // Enhanced Toast notification with better styling
        function showToast(message, type) {
            // Remove existing toasts to prevent stacking
            const existingToasts = document.querySelectorAll('.custom-toast-container');
            existingToasts.forEach(toast => toast.remove());
            
            const toastContainer = document.createElement('div');
            toastContainer.className = 'custom-toast-container position-fixed';
            toastContainer.style.cssText = 'bottom: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            
            const iconClass = type === 'success' ? 'fa-check-circle' : 
                            type === 'error' ? 'fa-exclamation-circle' : 
                            type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle';
            
            const bgColor = type === 'success' ? '#28a745' : 
                           type === 'error' ? '#dc3545' : 
                           type === 'warning' ? '#ffc107' : '#17a2b8';
            
            const iconBg = type === 'success' ? '#1e7e34' : 
                          type === 'error' ? '#c82333' : 
                          type === 'warning' ? '#e0a800' : '#138496';
            
            toastContainer.innerHTML = `
                <div class="toast show align-items-center text-white border-0" 
                     role="alert" 
                     style="background-color: ${bgColor} !important; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);"
                     data-bs-autohide="true" 
                     data-bs-delay="4000">
                    <div class="d-flex align-items-center p-3">
                        <div class="toast-icon me-3" style="width: 40px; height: 40px; background-color: ${iconBg}; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="fas ${iconClass} fa-lg"></i>
                        </div>
                        <div class="toast-body flex-grow-1">
                            <strong style="font-size: 1rem;">${type.charAt(0).toUpperCase() + type.slice(1)}</strong>
                            <div style="font-size: 0.9rem; margin-top: 4px;">${message}</div>
                        </div>
                        <button type="button" 
                                class="btn-close btn-close-white ms-2" 
                                data-bs-dismiss="toast" 
                                aria-label="Close"
                                onclick="this.closest('.custom-toast-container').remove()"></button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(toastContainer);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (toastContainer.parentElement) {
                    toastContainer.style.opacity = '0';
                    toastContainer.style.transition = 'opacity 0.3s ease';
                    setTimeout(() => {
                        toastContainer.remove();
                    }, 300);
                }
            }, 5000);
        }
        
        // Toggle Scope 3 fields visibility based on scope selection
        function toggleScope3Fields() {
            const scopeSelect = document.getElementById('scopeSelect');
            const scope3Section = document.getElementById('scope3SpecificSection');
            
            if (scopeSelect && scope3Section) {
                if (scopeSelect.value === '3') {
                    scope3Section.style.display = 'block';
                } else {
                    scope3Section.style.display = 'none';
                }
            }

            // Also filter emission sources list based on selected scope
            rebuildEmissionSourceOptionsForScope(scopeSelect?.value || '');
        }
        
        // Toggle calculation method for Scope 3
        function toggleScope3CalculationMethod() {
            const method = document.getElementById('calculationMethod').value;
            const spendFields = document.getElementById('spendBasedFields');
            
            if (method === 'spend-based' || method === 'hybrid') {
                spendFields.style.display = 'block';
            } else {
                spendFields.style.display = 'none';
            }
        }
        
        // Calculate emissions from spend amount (Scope 3 spend-based)
        function calculateScope3SpendBased() {
            const spendAmount = parseFloat(document.getElementById('spendAmount').value) || 0;
            const sectorCode = document.getElementById('sectorCode').value;
            const country = document.getElementById('country').value;
            const currency = document.getElementById('spendCurrency').value;
            const resultDiv = document.getElementById('spendCalculationResult');
            const resultText = document.getElementById('spendCalculationText');
            
            if (!spendAmount || !sectorCode) {
                resultDiv.style.display = 'none';
                return;
            }
            
            // Call API to calculate emissions
            fetch('{{ route("eio_factors.calculate") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    spend_amount: spendAmount,
                    sector_code: sectorCode,
                    country: country,
                    currency: currency
                })
            })
            .then(async res => {
                const data = await res.json();
                if (res.ok && data.success) {
                    const emissionsT = data.emissions_t_co2e || 0;
                    resultText.innerHTML = `<strong>Calculated Emissions:</strong> ${emissionsT.toFixed(4)} tCO₂e from ${spendAmount.toFixed(2)} ${currency}`;
                    resultDiv.className = 'alert alert-success';
                    resultDiv.style.display = 'block';
                    
                    // Auto-fill CO2e value if spend-based
                    const method = document.getElementById('calculationMethod').value;
                    if (method === 'spend-based') {
                        const co2eValueInput = document.getElementById('co2eValue');
                        const co2eValueHidden = document.getElementById('co2eValueHidden');
                        if (co2eValueInput) {
                            co2eValueInput.value = emissionsT.toFixed(4);
                        }
                        if (co2eValueHidden) {
                            co2eValueHidden.value = emissionsT.toFixed(4);
                        }
                    }
                } else {
                    resultText.innerHTML = `<strong>Error:</strong> ${data.message || 'Could not calculate emissions. Please check sector code.'}`;
                    resultDiv.className = 'alert alert-warning';
                    resultDiv.style.display = 'block';
                }
            })
            .catch(err => {
                resultText.innerHTML = `<strong>Error:</strong> Could not calculate emissions. Please try again.`;
                resultDiv.className = 'alert alert-warning';
                resultDiv.style.display = 'block';
            });
        }
        
        // Sidebar toggle for mobile
        document.getElementById('sidebarCollapse').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('content').classList.toggle('active');
        });
    </script>
@endpush

@extends('layouts.app')

@section('title', 'Emission Records')
@section('page-title', 'Emission Records')

@section('content')
    <!-- Main Content -->
    <div id="content">
        @include('layouts.top-nav')

        <!-- Page header -->
        <div class="mb-4">
            <h1 class="emission-records-page-title">Manual Entry</h1>
            <p class="emission-records-page-subtitle">Record greenhouse gas emissions by scope. Choose how you want to enter data below.</p>
        </div>

        <!-- Entry mode selection -->
        <div class="entry-mode-strip">
            <p class="strip-label">How would you like to enter data?</p>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="entry-mode-card active" id="singleEntryCard" onclick="setEntryMode('single')" role="button" tabindex="0" onkeydown="if(event.key==='Enter') setEntryMode('single')">
                        <div class="entry-mode-icon">
                            <i class="fas fa-file-signature"></i>
                        </div>
                        <h4>Single Entry</h4>
                        <p class="mb-2">One record at a time with full calculation options</p>
                        <span class="badge bg-success">Recommended</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="entry-mode-card" id="quickEntryCard" onclick="setEntryMode('quick')" role="button" tabindex="0" onkeydown="if(event.key==='Enter') setEntryMode('quick')">
                        <div class="entry-mode-icon">
                            <i class="fas fa-table"></i>
                        </div>
                        <h4>Quick Entry</h4>
                        <p class="mb-2">Add many rows and save in one go</p>
                        <span class="badge bg-primary">Bulk</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="entry-mode-card" id="templateEntryCard" onclick="setEntryMode('template')" role="button" tabindex="0" onkeydown="if(event.key==='Enter') setEntryMode('template')">
                        <div class="entry-mode-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <h4>Template Based</h4>
                        <p class="mb-2">Start from Electricity, Fleet, Travel, or Waste</p>
                        <span class="badge bg-info">Quick start</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Single Entry Form -->
        <div class="form-container" id="singleEntryForm">
            <div class="form-header">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h4><i class="fas fa-leaf text-success me-2"></i>Enter emission data</h4>
                        <p class="text-muted mb-0 small">Basic info → calculation → optional details</p>
                    </div>
                    <div class="form-steps-indicator" aria-hidden="true">
                        <span class="step active" title="Step 1: Basic Information"><i class="fas fa-check-circle"></i></span>
                        <span class="step" title="Step 2: Calculation"><i class="fas fa-circle"></i></span>
                        <span class="step" title="Step 3: Additional Details"><i class="fas fa-circle"></i></span>
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
                                        <option value="{{ e($facility->name) }}">{{ $facility->name }}</option>
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
                                    <option value="1">Scope 1 - Direct Emissions</option>
                                    <option value="2">Scope 2 - Indirect Emissions (Purchased Energy)</option>
                                    <option value="3">Scope 3 - Other Indirect Emissions</option>
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
                                    <option value="__other__">Other (specify below)</option>
                                </select>
                                <div id="emissionSourceOtherWrapper" class="mt-2" style="display: none;">
                                    <input type="text"
                                           class="form-control form-control-lg"
                                           id="emissionSourceOther"
                                           name="emission_source_other"
                                           placeholder="e.g. Hydrogen, Biogas, Custom fuel"
                                           maxlength="255"
                                           autocomplete="off">
                                    <div class="field-help mt-1">
                                        <i class="fas fa-lightbulb me-1"></i>
                                        Type the emission source name if it is not in the list
                                    </div>
                                </div>
                                <div class="field-help d-flex justify-content-between align-items-center flex-wrap gap-2 mt-2">
                                    <span><i class="fas fa-lightbulb me-1"></i>Pick the emission source from the list</span>
                                    <a href="#" class="small text-primary fw-semibold" id="addCustomSourceLink" onclick="openCustomSourceModal(event); return false;">
                                        <i class="fas fa-plus-circle me-1"></i>Add custom source
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-field-wrapper">
                                <label class="form-label required-label">
                                    <i class="fas fa-book me-1"></i>Factor Organization
                                </label>
                                <select class="form-select form-select-lg"
                                        id="factorOrganizationSelect"
                                        name="factor_organization_id"
                                        required>
                                    @foreach(($factorOrganizations ?? collect()) as $org)
                                        @if(($org->code ?? '') === 'COUNTRY')
                                            <option value="{{ $org->id }}" data-factor-mode="country">{{ $org->code }} - {{ $org->name }}</option>
                                        @else
                                            <option value="{{ $org->id }}" @if(($defaultOrganizationId ?? null) == $org->id) selected @endif>
                                                {{ $org->code }} - {{ $org->name }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                                <div class="field-help">
                                    <i class="fas fa-lightbulb me-1"></i>
                                    Select which standard to use (e.g., DEFRA / EPA / IPCC)
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6" id="factorCountryWrapper" style="display:none;">
                            <div class="form-field-wrapper">
                                <label class="form-label required-label">
                                    <i class="fas fa-flag me-1"></i>Country
                                </label>
                                <select class="form-select form-select-lg"
                                        id="factorCountrySelect"
                                        onchange="updateUnitOptions();">
                                    <option value="">Select country…</option>
                                    @foreach(($countries ?? collect()) as $c)
                                        <option value="{{ $c->code }}">{{ $c->name }} ({{ $c->code }})</option>
                                    @endforeach
                                </select>
                                <div class="field-help">
                                    <i class="fas fa-lightbulb me-1"></i>
                                    Select the country used for country-specific emission factors.
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
                                        <option value="high">High Confidence</option>
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
                                        <select class="form-select" id="activityUnitSelect" name="activity_unit">
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
                                               placeholder="e.g. MANUF, SERV, OFFICE"
                                               oninput="calculateScope3SpendBased()">
                                        <div class="field-help">
                                            <i class="fas fa-lightbulb me-1"></i>
                                            Sector code for EIO lookup (e.g. MANUF, SERV, OFFICE, IT, TRANS — see Settings → EIO Factors)
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
                                        name="departmentSelect">
                                    <option value="">Select department (optional)...</option>
                                    @foreach(departments() as $department)
                                        <option value="{{ e($department->name) }}">{{ $department->name }}</option>
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
                                    <option value="manual" selected>Manual Entry</option>
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
                                <div id="supportingDocumentsPreview" class="mt-2 small text-muted"></div>
                                <div class="field-help">
                                    <i class="fas fa-lightbulb me-1"></i>
                                    Optional: Upload invoices, meter screenshots, spreadsheets, or other evidence (max 10MB per file). Click × to remove before submit.
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

        <!-- Template Based Entry (Hidden by default) -->
        <div class="form-container" id="templateEntryForm" style="display: none;">
            <div class="form-header mb-4">
                <h4 class="mb-1"><i class="fas fa-clipboard-list text-info me-2"></i>Choose a template</h4>
                <p class="text-muted mb-0 small">We’ll open the single entry form and pre-fill scope and source for you</p>
            </div>
            <div class="row g-3">
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 template-card" onclick="useTemplate('electricity')" role="button" tabindex="0" onkeydown="if(event.key==='Enter') useTemplate('electricity')">
                        <div class="card-body text-center">
                            <i class="fas fa-bolt text-warning fa-2x mb-2"></i>
                            <h6 class="card-title">Electricity</h6>
                            <p class="card-text small mb-0">Scope 2 – Purchased electricity</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 template-card" onclick="useTemplate('fleet')" role="button" tabindex="0" onkeydown="if(event.key==='Enter') useTemplate('fleet')">
                        <div class="card-body text-center">
                            <i class="fas fa-truck text-primary fa-2x mb-2"></i>
                            <h6 class="card-title">Fleet / Vehicles</h6>
                            <p class="card-text small mb-0">Scope 1 – Gasoline, diesel, CNG</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 template-card" onclick="useTemplate('travel')" role="button" tabindex="0" onkeydown="if(event.key==='Enter') useTemplate('travel')">
                        <div class="card-body text-center">
                            <i class="fas fa-plane text-info fa-2x mb-2"></i>
                            <h6 class="card-title">Business Travel</h6>
                            <p class="card-text small mb-0">Scope 3 – Travel & commuting</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 template-card" onclick="useTemplate('waste')" role="button" tabindex="0" onkeydown="if(event.key==='Enter') useTemplate('waste')">
                        <div class="card-body text-center">
                            <i class="fas fa-recycle text-success fa-2x mb-2"></i>
                            <h6 class="card-title">Waste</h6>
                            <p class="card-text small mb-0">Scope 3 – Waste generated</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Entry Table (Hidden by default) -->
        <div class="form-container" id="quickEntryForm" style="display: none;">
            <div class="quick-entry-header d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <h4 class="mb-1"><i class="fas fa-table text-primary me-2"></i>Quick entry table</h4>
                    <p class="text-muted small mb-0">Add rows, fill required fields (*), then save each row or save all at once.</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary" onclick="addQuickEntryRow()">
                        <i class="fas fa-plus me-1"></i>Add row
                    </button>
                    <button type="button" class="btn btn-success" onclick="saveAllQuickEntries()">
                        <i class="fas fa-save me-1"></i>Save all
                    </button>
                </div>
            </div>

            <div class="quick-entry-tip mb-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span><i class="fas fa-info-circle me-2 text-primary"></i>Required fields are marked with *. You can save one row with the checkmark or save every row with “Save all”.</span>
                <a href="#" class="small text-primary text-nowrap fw-semibold" onclick="openCustomSourceModal(event); return false;">
                    <i class="fas fa-plus-circle me-1"></i>Add custom source
                </a>
            </div>

            <div class="quick-entry-table">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>Date *</th>
                                <th>Facility *</th>
                                <th>Site</th>
                                <th>Scope *</th>
                                <th>Source *</th>
                                <th>Org *</th>
                                <th>CO₂e (t) *</th>
                                <th>Dept</th>
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

    <!-- Add Custom Source Modal -->
    <div class="modal fade" id="customSourceModal" tabindex="-1" aria-labelledby="customSourceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="customSourceModalLabel">
                        <i class="fas fa-plus-circle me-2"></i>Add custom emission source
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">Add a source not in the list. You can add an emission factor for it in <strong>Settings → Emission Factors</strong> afterwards.</p>
                    <form id="customSourceForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Name *</label>
                            <input type="text" class="form-control" name="name" id="customSourceName" required maxlength="255" placeholder="e.g. Hydrogen Combustion, Biogas">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Scope *</label>
                            <select class="form-select" name="scope" id="customSourceScope" required>
                                <option value="1">Scope 1</option>
                                <option value="2">Scope 2</option>
                                <option value="3">Scope 3</option>
                            </select>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="customSourceDescription" rows="2" placeholder="Optional"></textarea>
                        </div>
                    </form>
                    <div class="alert alert-danger mt-3 d-none" id="customSourceError"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="saveCustomSourceBtn">
                        <i class="fas fa-plus me-1"></i>Add source
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
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    // Convert PHP helper result to JS array
    const facilitiesData = @json(Facilities());
    const sitesData = @json(sites());
    const departmentsData = @json(departments());
    window.selectedSupportingDocuments = [];

    function renderSupportingDocumentsPreview() {
        const el = document.getElementById('supportingDocumentsPreview');
        if (!el) return;
        const arr = window.selectedSupportingDocuments || [];
        if (arr.length === 0) {
            el.innerHTML = '';
            return;
        }
        let html = '<div class="d-flex flex-wrap gap-2 align-items-center mt-1">';
        arr.forEach(function(file, i) {
            html += '<span class="badge bg-light text-dark d-inline-flex align-items-center gap-1">' +
                (file.name || 'File ' + (i + 1)) +
                ' <button type="button" class="btn btn-link btn-sm p-0 text-danger" onclick="removeSupportingDocument(' + i + ')" aria-label="Remove">×</button></span>';
        });
        html += '</div>';
        el.innerHTML = html;
    }

    function removeSupportingDocument(index) {
        (window.selectedSupportingDocuments || []).splice(index, 1);
        renderSupportingDocumentsPreview();
    }

    // Dynamic emission factors loaded from database (keyed by emission source name)
    const emissionFactors = @json($emissionFactorsMap ?? []);
    const allEmissionSourceNames = @json($allEmissionSourceNames ?? []);
    const emissionSourceNamesByScope = @json($emissionSourceNamesByScope ?? []);
    const defaultOrganizationId = @json($defaultOrganizationId ?? null);
    const companyCountryCode = @json($companyCountryCode ?? 'default');
    const companyCountryDisplay = @json($companyCountryDisplay ?? null);
</script>

<script>
        // Entry mode state
        let currentEntryMode = 'single';
        let calculationMode = 'activity';
        let quickEntryRows = 0;

function isCountryModeEnabled() {
    const orgEl = document.getElementById('factorOrganizationSelect');
    const opt = orgEl ? orgEl.options[orgEl.selectedIndex] : null;
    return !!(opt && opt.dataset && opt.dataset.factorMode === 'country');
}

function onFactorOrganizationChange() {
    const wrapper = document.getElementById('factorCountryWrapper');
    const countryEl = document.getElementById('factorCountrySelect');
    const enabled = isCountryModeEnabled();

    if (wrapper) wrapper.style.display = enabled ? 'block' : 'none';
    if (countryEl) {
        if (enabled) {
            countryEl.setAttribute('required', 'required');
        } else {
            countryEl.removeAttribute('required');
            countryEl.value = '';
        }
    }
    updateUnitOptions();
}

function getEffectiveFactorRegion() {
    if (isCountryModeEnabled()) {
        const cEl = document.getElementById('factorCountrySelect');
        const v = cEl ? cEl.value : '';
        return v ? String(v) : ''; // no country selected = don't auto-pick a factor
    }
    return 'default';
}

// Build unit/factor helpers from DB-driven emissionFactors map
const allowedUnits = {};
const emissionUnits = { default: { unit: 'unit', factorUnit: 'tCO₂e/unit' } };
Object.keys(emissionFactors || {}).forEach((name) => {
    const bySource = emissionFactors[name];
    const orgId = String(defaultOrganizationId || '');
    const list = bySource && orgId && bySource[orgId]
        ? (Array.isArray(bySource[orgId]) ? bySource[orgId] : [bySource[orgId]])
        : null;
    const unit = (list && list.length && list[0]?.unit) ? list[0].unit : 'unit';
    allowedUnits[name] = [unit];
    emissionUnits[name] = { unit, factorUnit: `tCO₂e/${unit}` };
});

function getSelectedOrganizationId() {
    const el = document.getElementById('factorOrganizationSelect');
    const v = el ? el.value : (defaultOrganizationId || '');
    return v ? String(v) : '';
}

function resolveFactorMeta(sourceName) {
    const orgId = getSelectedOrganizationId();
    const bySource = emissionFactors?.[sourceName];
    const effectiveRegion = getEffectiveFactorRegion();

    // New structure: emissionFactors[source][orgId] => [ { region, unit, factor }, ... ]
    if (bySource && typeof bySource === 'object' && orgId && bySource[orgId]) {
        const list = Array.isArray(bySource[orgId]) ? bySource[orgId] : [bySource[orgId]];
        if (list.length && typeof list[0]?.region !== 'undefined') {
            let chosen = list.find((f) => (f.region || 'default') === effectiveRegion);
            if (!chosen && effectiveRegion) chosen = list.find((f) => (f.region || 'default') === 'default');
            if (!chosen && effectiveRegion) chosen = list[0];
            return chosen || null;
        }
        return list[0] || null;
    }

    // Fallback: try default orgId
    const def = defaultOrganizationId ? String(defaultOrganizationId) : '';
    if (bySource && typeof bySource === 'object' && def && bySource[def]) {
        const list = Array.isArray(bySource[def]) ? bySource[def] : [bySource[def]];
        if (list.length && typeof list[0]?.region !== 'undefined') {
            let chosen = list.find((f) => (f.region || 'default') === effectiveRegion);
            if (!chosen && effectiveRegion) chosen = list.find((f) => (f.region || 'default') === 'default');
            if (!chosen && effectiveRegion) chosen = list[0];
            return chosen || null;
        }
        return list[0] || null;
    }

    // Backward compatibility: old structure { unit, factor }
    if (bySource && typeof bySource === 'object' && (bySource.unit || bySource.factor)) {
        return bySource;
    }

    // If nested but org missing, pick first available org
    if (bySource && typeof bySource === 'object') {
        const keys = Object.keys(bySource);
        if (keys.length) return bySource[keys[0]];
    }

    return null;
}

// Update activity unit options based on selected emission source
function updateUnitOptions() {
    const sourceSelect = document.getElementById('emissionSourceSelect');
    const source = sourceSelect ? sourceSelect.value : '';
    const otherWrapper = document.getElementById('emissionSourceOtherWrapper');
    const otherInput = document.getElementById('emissionSourceOther');
    if (source === '__other__') {
        if (otherWrapper) otherWrapper.style.display = 'block';
        if (otherInput) { otherInput.setAttribute('required', 'required'); otherInput.removeAttribute('disabled'); }
    } else {
        if (otherWrapper) otherWrapper.style.display = 'none';
        if (otherInput) { otherInput.removeAttribute('required'); otherInput.value = ''; }
    }
    const unitSelect = document.getElementById('activityUnitSelect');
    const validUnits = source === '__other__' ? [] : (allowedUnits[source] || []);

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

    // Auto-fill emission factor + unit label based on selected organization
    const meta = resolveFactorMeta(source);
    const unitResolved = meta?.unit || validUnits[0] || 'unit';
    const factorResolved = meta?.factor;

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

    if (source === '__other__') {
        // User types custom source; factor/unit can be entered manually or use Direct Entry
        const factorInput = document.getElementById('emissionFactor');
        if (factorInput && !factorInput.value) factorInput.value = '';
    } else if (typeof factorResolved !== 'undefined' && factorResolved !== null && factorResolved !== '') {
        const factorInput = document.getElementById('emissionFactor');
        if (factorInput) factorInput.value = parseFloat(factorResolved) || 0;
    } else {
        // No factor found for selected org; keep current value but alert user
        showToast('No emission factor found for the selected organization and source.', 'warning');
    }

    updateCalculation();
}

function rebuildEmissionSourceOptionsForScope(scopeValue) {
    const select = document.getElementById('emissionSourceSelect');
    if (!select) return;

    // Destroy Select2 before mutating options so the widget and factor update keep working
    if (typeof $ !== 'undefined' && $.fn.select2) {
        try {
            if ($(select).data('select2')) $(select).select2('destroy');
        } catch (e) { /* ignore */ }
    }

    // Keep the initial full list so user can clear scope and see everything again
    if (!select.dataset.fullOptionsHtml) {
        select.dataset.fullOptionsHtml = select.innerHTML;
    }

    // If no scope selected, restore original options
    if (!scopeValue) {
        select.innerHTML = select.dataset.fullOptionsHtml;
        initEmissionSourceSelect2();
        updateUnitOptions();
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

            // Preserve metadata for auto-fill (uses selected org via resolveFactorMeta)
            const meta = resolveFactorMeta(name);
            if (meta?.unit) opt.dataset.unit = meta.unit;
            if (typeof meta?.factor !== 'undefined' && meta?.factor !== null) opt.dataset.factor = meta.factor;

            select.appendChild(opt);
        });
    }
    // Always append "Other (specify below)" so user can type a free-form source
    const otherOpt = document.createElement('option');
    otherOpt.value = '__other__';
    otherOpt.textContent = 'Other (specify below)';
    select.appendChild(otherOpt);

    // If previous selection no longer valid, clear it (except __other__)
    if (current && current !== '__other__' && !names.includes(current)) {
        select.value = '';
    } else if (current) {
        select.value = current;
    }

    initEmissionSourceSelect2();
    updateUnitOptions();
}

function initEmissionSourceSelect2() {
    if (typeof $ === 'undefined' || !$.fn.select2) return;
    const sel = document.getElementById('emissionSourceSelect');
    if (!sel) return;
    $('.emission-source-select').off('change.emissionSourceFactor');
    $('.emission-source-select').select2({
        placeholder: "Select emission source...",
        allowClear: true,
        width: '100%',
        minimumResultsForSearch: 0
    });
    $('.emission-source-select').on('change.emissionSourceFactor', function() {
        if (typeof updateUnitOptions === 'function') updateUnitOptions();
    });
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
document.getElementById('factorOrganizationSelect')?.addEventListener('change', onFactorOrganizationChange);
document.getElementById('activityData').addEventListener('input', updateCalculation);
document.getElementById('emissionFactor').addEventListener('input', updateCalculation);
document.getElementById('activityUnitSelect').addEventListener('change', updateCalculation);

        // ----- Add custom source -----
        const customSourceModalEl = document.getElementById('customSourceModal');
        const customSourceModal = customSourceModalEl ? new bootstrap.Modal(customSourceModalEl) : null;
        const urlEmissionSourcesStore = "{{ route('emission_sources.storeOrUpdate') }}";

        function openCustomSourceModal(e) {
            if (e) e.preventDefault();
            const scopeSelect = document.getElementById('scopeSelect');
            const scopeOpt = document.getElementById('customSourceScope');
            if (scopeOpt && scopeSelect && scopeSelect.value) scopeOpt.value = scopeSelect.value;
            document.getElementById('customSourceForm')?.reset();
            const errEl = document.getElementById('customSourceError');
            if (errEl) { errEl.classList.add('d-none'); errEl.textContent = ''; }
            if (customSourceModal) customSourceModal.show();
        }

        function addCustomSourceThenUpdateDropdown() {
            const name = document.getElementById('customSourceName')?.value?.trim();
            const scope = document.getElementById('customSourceScope')?.value;
            const description = document.getElementById('customSourceDescription')?.value?.trim() || '';
            const errEl = document.getElementById('customSourceError');
            const btn = document.getElementById('saveCustomSourceBtn');

            if (!name) {
                if (errEl) { errEl.textContent = 'Name is required.'; errEl.classList.remove('d-none'); }
                return;
            }
            if (errEl) { errEl.classList.add('d-none'); errEl.textContent = ''; }

            const form = document.getElementById('customSourceForm');
            const formData = new FormData(form);
            formData.set('name', name);
            formData.set('scope', scope || '1');
            formData.set('description', description);

            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Adding...'; }

            fetch(urlEmissionSourcesStore, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]')?.value || '' },
                body: formData
            })
            .then(async (r) => {
                const data = await r.json().catch(() => ({}));
                if (!r.ok) throw new Error(data?.message || data?.errors?.name?.[0] || 'Could not add source.');
                return data;
            })
            .then((data) => {
                const created = data?.data;
                const sourceName = created?.name || name;
                const sourceScope = String(created?.scope || scope || '1');

                if (!emissionSourceNamesByScope[sourceScope]) emissionSourceNamesByScope[sourceScope] = [];
                if (!emissionSourceNamesByScope[sourceScope].includes(sourceName)) emissionSourceNamesByScope[sourceScope].push(sourceName);
                if (!Array.isArray(allEmissionSourceNames)) allEmissionSourceNames = [];
                if (!allEmissionSourceNames.includes(sourceName)) allEmissionSourceNames.push(sourceName);

                rebuildEmissionSourceOptionsForScope(document.getElementById('scopeSelect')?.value || '');
                const sel = document.getElementById('emissionSourceSelect');
                if (sel) sel.value = sourceName;
                if (typeof $ !== 'undefined' && $.fn.select2) $(sel).trigger('change.select2');
                updateUnitOptions();
                if (typeof refreshQuickEntrySourceDropdowns === 'function') refreshQuickEntrySourceDropdowns();

                if (customSourceModal) customSourceModal.hide();
                showToast('Source added. Add an emission factor in Settings → Emission Factors to use it for calculations.', 'info');
            })
            .catch((err) => {
                if (errEl) { errEl.textContent = err.message || 'Could not add source.'; errEl.classList.remove('d-none'); }
            })
            .finally(() => {
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-plus me-1"></i>Add source'; }
            });
        }

        
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
                
                // Emission source Select2 init is in rebuildEmissionSourceOptionsForScope (searchable + factor refresh)
                
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

            // Ensure country dropdown visibility matches the selected option
            onFactorOrganizationChange();

            // Default organization select (if not already selected)
            const orgSelect = document.getElementById('factorOrganizationSelect');
            if (orgSelect && !orgSelect.value && defaultOrganizationId) {
                orgSelect.value = String(defaultOrganizationId);
            }
            
            // Set Activity-Based as default calculation mode
            setCalculationMode('activity');
            
            // Update calculation when activity data changes
            document.getElementById('activityData')?.addEventListener('input', updateCalculation);
            document.getElementById('emissionFactor')?.addEventListener('input', updateCalculation);
            document.getElementById('emissionSourceSelect')?.addEventListener('change', updateUnitOptions);
            document.getElementById('factorOrganizationSelect')?.addEventListener('change', updateUnitOptions);
            
            // Add first quick entry row
            addQuickEntryRow();

            // Custom source modal save
            document.getElementById('saveCustomSourceBtn')?.addEventListener('click', addCustomSourceThenUpdateDropdown);

            // Supporting documents: add selected files to array and show preview
            document.getElementById('supportingDocuments')?.addEventListener('change', function() {
                const input = this;
                if (input.files && input.files.length) {
                    for (let i = 0; i < input.files.length; i++) {
                        window.selectedSupportingDocuments.push(input.files[i]);
                    }
                    input.value = '';
                    renderSupportingDocumentsPreview();
                }
            });
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
            const templateForm = document.getElementById('templateEntryForm');
            if (templateForm) templateForm.style.display = mode === 'template' ? 'block' : 'none';
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

        
function buildQuickEntrySourceOptionsHtml(scopeValue) {
    const names = emissionSourceNamesByScope?.[scopeValue] || allEmissionSourceNames || [];
    let html = `<option value="">Select source...</option>`;
    (names || []).forEach(name => {
        html += `<option value="${String(name).replace(/"/g, '&quot;')}">${name}</option>`;
    });
    html += `<option value="__other__">Other (specify)</option>`;
    return html;
}

function refreshQuickEntrySourceDropdowns() {
    document.querySelectorAll('#quickEntryTableBody tr .quick-entry-source').forEach(sourceEl => {
        const row = sourceEl.closest('tr');
        const scopeEl = row?.querySelector('.quick-entry-scope');
        const scope = scopeEl?.value || '';
        const selected = sourceEl.value;
        sourceEl.innerHTML = buildQuickEntrySourceOptionsHtml(scope);
        if (selected) {
            const names = emissionSourceNamesByScope?.[scope] || allEmissionSourceNames || [];
            if (names.includes(selected)) sourceEl.value = selected;
        }
    });
}

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

    let siteOptions = `<option value="">—</option>`;
    (sitesData || []).forEach(s => {
        siteOptions += `<option value="${s.id || ''}">${(s.name || '').replace(/"/g, '&quot;')}</option>`;
    });
    let departmentOptions = `<option value="">—</option>`;
    (departmentsData || []).forEach(d => {
        const name = d.name || '';
        departmentOptions += `<option value="${String(name).replace(/"/g, '&quot;')}">${name}</option>`;
    });

    function buildOrgOptions() {
        // Build from server-rendered select options to avoid duplicating org list in JS
        const orgSelect = document.getElementById('factorOrganizationSelect');
        if (!orgSelect) return `<option value=\"\">Select org...</option>`;
        return [...orgSelect.options].map(o => {
            const selected = (o.value && String(o.value) === String(orgSelect.value)) ? 'selected' : '';
            return `<option value=\"${String(o.value).replace(/\"/g, '&quot;')}\" ${selected}>${o.textContent}</option>`;
        }).join('');
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
            <select class="form-select form-select-sm quick-entry-site">
                ${siteOptions}
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
                ${buildQuickEntrySourceOptionsHtml('')}
            </select>
            <input type="text" class="form-control form-control-sm quick-entry-source-other mt-1" placeholder="Type source name" style="display: none;" maxlength="255">
        </td>
        <td>
            <select class="form-select form-select-sm quick-entry-org" required>
                ${buildOrgOptions()}
            </select>
        </td>
        <td>
            <input type="number" class="form-control form-control-sm quick-entry-co2e" step="0.01" min="0" placeholder="0.00" required>
        </td>
        <td>
            <select class="form-select form-select-sm quick-entry-department">
                ${departmentOptions}
            </select>
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
    const sourceOtherEl = row.querySelector('.quick-entry-source-other');
    function toggleQuickEntrySourceOther() {
        if (sourceOtherEl) {
            if (sourceEl && sourceEl.value === '__other__') {
                sourceOtherEl.style.display = 'block';
                sourceOtherEl.setAttribute('required', 'required');
            } else {
                sourceOtherEl.style.display = 'none';
                sourceOtherEl.removeAttribute('required');
                sourceOtherEl.value = '';
            }
        }
    }
    if (scopeEl && sourceEl) {
        scopeEl.addEventListener('change', () => {
            const selected = sourceEl.value;
            sourceEl.innerHTML = buildQuickEntrySourceOptionsHtml(scopeEl.value);
            if (selected) {
                const names = emissionSourceNamesByScope?.[scopeEl.value] || allEmissionSourceNames || [];
                if (names.includes(selected)) sourceEl.value = selected;
            }
            toggleQuickEntrySourceOther();
        });
    }
    if (sourceEl) sourceEl.addEventListener('change', toggleQuickEntrySourceOther);

    // Initialize date picker
    flatpickr(row.querySelector('.quick-entry-date'), {
        dateFormat: "Y-m-d",
        defaultDate: new Date(),
        maxDate: new Date()
    });

    updateRowCount();
}

        
        // Save quick entry row (persists to server)
        function saveQuickEntry(rowId) {
            const row = document.getElementById(rowId);
            if (!row) return;

            const dateEl = row.querySelector('.quick-entry-date');
            const facilityEl = row.querySelector('.quick-entry-facility');
            const siteEl = row.querySelector('.quick-entry-site');
            const scopeEl = row.querySelector('.quick-entry-scope');
            const sourceEl = row.querySelector('.quick-entry-source');
            const sourceOtherEl = row.querySelector('.quick-entry-source-other');
            const orgEl = row.querySelector('.quick-entry-org');
            const co2eEl = row.querySelector('.quick-entry-co2e');
            const departmentEl = row.querySelector('.quick-entry-department');

            const isOtherSource = sourceEl?.value === '__other__';
            const otherSourceName = sourceOtherEl?.value?.trim() || '';
            if (isOtherSource && !otherSourceName) {
                showToast('Please enter the emission source name when "Other" is selected.', 'error');
                if (sourceOtherEl) sourceOtherEl.classList.add('is-invalid');
                return;
            }

            const required = [
                { el: dateEl, name: 'Date' },
                { el: facilityEl, name: 'Facility' },
                { el: scopeEl, name: 'Scope' },
                { el: sourceEl, name: 'Emission Source' },
                { el: orgEl, name: 'Organization' },
                { el: co2eEl, name: 'CO₂e' },
            ];
            const missing = required.filter(r => !r.el || !r.el.value);
            if (missing.length) {
                missing.forEach(r => r.el && r.el.classList.add('is-invalid'));
                showToast('Please fill all required fields in this row.', 'error');
                return;
            }
            row.querySelectorAll('input, select').forEach(i => i.classList.remove('is-invalid'));

            const entry = {
                entryDate: dateEl?.value,
                facilitySelect: facilityEl?.value,
                siteSelect: siteEl?.value || null,
                scopeSelect: scopeEl?.value,
                emissionSourceSelect: isOtherSource ? '__other__' : sourceEl?.value,
                emission_source_other: isOtherSource ? otherSourceName : null,
                factor_organization_id: orgEl?.value || null,
                co2eValue: co2eEl?.value,
                confidenceLevel: 'medium',
                entryNotes: row.querySelector('.quick-entry-notes')?.value || '',
                dataSource: 'manual',
                departmentSelect: departmentEl?.value || null,
            };

            row.classList.add('editing');
            fetch("{{ route('emission-records.store') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ entries: [entry], status: 'active' })
            })
            .then(async res => {
                const data = await res.json();
                if (!res.ok) throw new Error(data?.errors?.messages ? Object.values(data.errors.messages).flat().join(' ') : (data.message || 'Failed to save'));
                return data;
            })
            .then(() => {
                row.classList.remove('editing');
                showToast('Row saved successfully', 'success');
                row.remove();
                updateRowCount();
                renumberRows();
            })
            .catch(err => {
                row.classList.remove('editing');
                showToast(err.message || 'Failed to save row', 'error');
            });
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
        const siteEl = row.querySelector('.quick-entry-site');
        const scopeEl = row.querySelector('.quick-entry-scope');
        const sourceEl = row.querySelector('.quick-entry-source');
        const sourceOtherEl = row.querySelector('.quick-entry-source-other');
        const orgEl = row.querySelector('.quick-entry-org');
        const co2eEl = row.querySelector('.quick-entry-co2e');
        const departmentEl = row.querySelector('.quick-entry-department');

        const isOtherSource = sourceEl?.value === '__other__';
        const otherSourceName = sourceOtherEl?.value?.trim() || '';
        if (isOtherSource && !otherSourceName) {
            valid = false;
            if (sourceOtherEl) sourceOtherEl.classList.add('is-invalid');
        } else if (sourceOtherEl) sourceOtherEl.classList.remove('is-invalid');

        // Validate required fields (row-level)
        const required = [
            { el: dateEl, name: 'Date' },
            { el: facilityEl, name: 'Facility' },
            { el: scopeEl, name: 'Scope' },
            { el: sourceEl, name: 'Emission Source' },
            { el: orgEl, name: 'Organization' },
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
            siteSelect: siteEl?.value || null,
            scopeSelect: scopeEl?.value,
            emissionSourceSelect: isOtherSource ? '__other__' : sourceEl?.value,
            emission_source_other: isOtherSource ? otherSourceName : null,
            factor_organization_id: orgEl?.value || null,
            co2eValue: co2eEl?.value,
            confidenceLevel: 'medium',
            entryNotes: row.querySelector('.quick-entry-notes').value,
            dataSource: 'manual',
            departmentSelect: departmentEl?.value || null
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
            const sourceSelect = document.getElementById('emissionSourceSelect');
            const otherInput = document.getElementById('emissionSourceOther');
            if (sourceSelect && sourceSelect.value === '__other__') {
                const otherName = otherInput ? otherInput.value.trim() : '';
                if (!otherName) {
                    showToast('Please specify the emission source name when "Other" is selected.', 'error');
                    if (otherInput) { otherInput.classList.add('is-invalid'); otherInput.focus(); }
                    return;
                }
                if (otherInput) otherInput.classList.remove('is-invalid');
            }
            
            const formData = new FormData(form);
            const submitButton = document.activeElement; // Get the clicked button

            // Append supporting documents from our array (so remove-before-submit works)
            const docInput = document.getElementById('supportingDocuments');
            if (docInput && docInput.name) {
                formData.delete(docInput.name);
                docInput.removeAttribute('name');
            }
            (window.selectedSupportingDocuments || []).forEach(function(f) {
                formData.append('supporting_documents[]', f);
            });
            if (docInput) docInput.setAttribute('name', 'supporting_documents[]');

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
                window.selectedSupportingDocuments = [];
                renderSupportingDocumentsPreview();
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
            window.selectedSupportingDocuments = [];
            renderSupportingDocumentsPreview();
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
            
            // Hide "Other" emission source wrapper and clear value
            const otherWrapper = document.getElementById('emissionSourceOtherWrapper');
            const otherInput = document.getElementById('emissionSourceOther');
            if (otherWrapper) otherWrapper.style.display = 'none';
            if (otherInput) { otherInput.value = ''; otherInput.classList.remove('is-invalid'); }
            
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

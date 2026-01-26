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
                                <div class="col-md-4">
                                    <label class="form-label required-label">Date</label>
                                    <input type="date" class="form-control date-picker" name="entryDate" required>
                                    <div class="help-text">Select the date when emissions occurred</div>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label required-label">Facility / Location</label>
                                    <select class="form-select facility-select" name="facilitySelect" required>
                                        <option value="">Select facility...</option>
                                        @foreach(facilities() as $facility)
                                            <option value="{{ $facility->name }}">{{ $facility->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="help-text">Choose the facility where emissions occurred</div>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">Site</label>
                                    <select class="form-select site-select" name="siteSelect" id="scope{{ $scope }}SiteSelect">
                                        <option value="">Select site (optional)...</option>
                                        @foreach(sites() as $site)
                                            <option value="{{ $site->id }}">{{ $site->name }}@if($site->location) - {{ $site->location }}@endif</option>
                                        @endforeach
                                    </select>
                                    <div class="help-text">Optional: Select a specific site within the facility</div>
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
                                                @php
                                                    $factor = $source->emissionFactors->first();
                                                    $unit = $factor ? $factor->unit : '';
                                                    $factorValue = $factor ? $factor->factor_value : '';
                                                @endphp
                                                <option value="{{ $source->name }}" data-unit="{{ $unit }}" data-factor="{{ $factorValue }}">{{ $source->name }}</option>
                                            @endforeach
                                        @elseif($scope == 2)
                                            @foreach($scope2Sources as $source)
                                                @php
                                                    $factor = $source->emissionFactors->first();
                                                    $unit = $factor ? $factor->unit : '';
                                                    $factorValue = $factor ? $factor->factor_value : '';
                                                @endphp
                                                <option value="{{ $source->name }}" data-unit="{{ $unit }}" data-factor="{{ $factorValue }}">{{ $source->name }}</option>
                                            @endforeach
                                                <option value="steam" data-unit="MJ" data-factor="">Purchased Steam</option>
                                                <option value="heating" data-unit="kWh" data-factor="">District Heating</option>
                                                <option value="cooling" data-unit="kWh" data-factor="">District Cooling</option>
                                        @elseif($scope == 3)
                                            @foreach($scope3Sources as $source)
                                                @php
                                                    $factor = $source->emissionFactors->first();
                                                    $unit = $factor ? $factor->unit : '';
                                                    $factorValue = $factor ? $factor->factor_value : '';
                                                @endphp
                                                <option value="{{ $source->name }}" data-unit="{{ $unit }}" data-factor="{{ $factorValue }}">{{ $source->name }}</option>
                                            @endforeach
                                            {{-- All 15 Scope 3 Categories (GHG Protocol) --}}
                                            <optgroup label="GHG Protocol Categories">
                                                <option value="1. Purchased Goods & Services" data-unit="kg" data-factor="">1. Purchased Goods & Services</option>
                                                <option value="2. Capital Goods" data-unit="kg" data-factor="">2. Capital Goods</option>
                                                <option value="3. Fuel & Energy Related Activities" data-unit="kWh" data-factor="">3. Fuel & Energy Related Activities</option>
                                                <option value="4. Upstream Transportation & Distribution" data-unit="km" data-factor="">4. Upstream Transportation & Distribution</option>
                                                <option value="5. Waste Generated in Operations" data-unit="kg" data-factor="">5. Waste Generated in Operations</option>
                                                <option value="6. Business Travel" data-unit="km" data-factor="">6. Business Travel</option>
                                                <option value="7. Employee Commuting" data-unit="km" data-factor="">7. Employee Commuting</option>
                                                <option value="8. Upstream Leased Assets" data-unit="m²" data-factor="">8. Upstream Leased Assets</option>
                                                <option value="9. Downstream Transportation & Distribution" data-unit="km" data-factor="">9. Downstream Transportation & Distribution</option>
                                                <option value="10. Processing of Sold Products" data-unit="kg" data-factor="">10. Processing of Sold Products</option>
                                                <option value="11. Use of Sold Products" data-unit="unit" data-factor="">11. Use of Sold Products</option>
                                                <option value="12. End-of-Life Treatment of Sold Products" data-unit="kg" data-factor="">12. End-of-Life Treatment of Sold Products</option>
                                                <option value="13. Downstream Leased Assets" data-unit="m²" data-factor="">13. Downstream Leased Assets</option>
                                                <option value="14. Franchises" data-unit="unit" data-factor="">14. Franchises</option>
                                                <option value="15. Investments" data-unit="unit" data-factor="">15. Investments</option>
                                            </optgroup>
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
                                        <span class="input-group-text" id="scope{{ $scope }}FactorUnitLabel">tCO₂e/unit</span>
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
                        
                        <!-- Scope 3 Specific Fields (Only for Scope 3) -->
                        @if($scope == 3)
                        <div class="form-section scope3-specific-section" id="scope{{ $scope }}Scope3Section">
                            <div class="form-section-title">
                                <i class="fas fa-network-wired"></i> Scope 3 Specific Information
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Scope 3 Category</label>
                                    <select class="form-select scope3-category-select" name="scope3_category_id" id="scope{{ $scope }}Scope3Category">
                                        <option value="">Select category (optional)...</option>
                                        @foreach(scope3_categories() as $category)
                                            <option value="{{ $category->id }}" data-type="{{ $category->category_type }}">
                                                {{ $category->code }} - {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="help-text">Select the GHG Protocol Scope 3 category</div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Supplier</label>
                                    <select class="form-select supplier-select" name="supplier_id" id="scope{{ $scope }}Supplier">
                                        <option value="">Select supplier (optional)...</option>
                                        @foreach(suppliers() as $supplier)
                                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="help-text">Select the supplier if applicable</div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Calculation Method</label>
                                    <select class="form-select calculation-method-select" name="calculation_method" id="scope{{ $scope }}CalculationMethod" onchange="toggleCalculationMethod({{ $scope }})">
                                        <option value="activity-based" selected>Activity-Based</option>
                                        <option value="spend-based">Spend-Based</option>
                                        <option value="hybrid">Hybrid</option>
                                    </select>
                                    <div class="help-text">Choose how emissions are calculated</div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Data Quality</label>
                                    <select class="form-select data-quality-select" name="data_quality" id="scope{{ $scope }}DataQuality">
                                        <option value="primary">Primary Data</option>
                                        <option value="secondary">Secondary Data</option>
                                        <option value="estimated" selected>Estimated</option>
                                    </select>
                                    <div class="help-text">Quality rating of the data source</div>
                                </div>
                                
                                <!-- Spend-Based Fields (Hidden by default) -->
                                <div class="col-md-12 spend-based-fields" id="scope{{ $scope }}SpendBasedFields" style="display: none;">
                                    <div class="alert alert-info mb-3">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Spend-Based Calculation:</strong> Enter the spend amount and select sector to automatically calculate emissions using EIO factors.
                                    </div>
                                    
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Spend Amount</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="spend_amount" id="scope{{ $scope }}SpendAmount" step="0.01" min="0" placeholder="0.00" oninput="calculateSpendBased({{ $scope }})">
                                                <select class="form-select" name="spend_currency" id="scope{{ $scope }}SpendCurrency" style="max-width: 100px;">
                                                    <option value="USD" selected>USD</option>
                                                    <option value="EUR">EUR</option>
                                                    <option value="GBP">GBP</option>
                                                    <option value="JPY">JPY</option>
                                                    <option value="CAD">CAD</option>
                                                    <option value="AUD">AUD</option>
                                                </select>
                                            </div>
                                            <div class="help-text">Enter the monetary spend amount</div>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <label class="form-label">Sector Code</label>
                                            <input type="text" class="form-control" name="sector_code" id="scope{{ $scope }}SectorCode" placeholder="e.g., 31-33" oninput="calculateSpendBased({{ $scope }})">
                                            <div class="help-text">NAICS or similar sector code for EIO factor lookup</div>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <label class="form-label">Country</label>
                                            <select class="form-select" name="country" id="scope{{ $scope }}Country" onchange="calculateSpendBased({{ $scope }})">
                                                <option value="USA" selected>United States</option>
                                                <option value="CAN">Canada</option>
                                                <option value="GBR">United Kingdom</option>
                                                <option value="DEU">Germany</option>
                                                <option value="FRA">France</option>
                                                <option value="JPN">Japan</option>
                                                <option value="CHN">China</option>
                                                <option value="AUS">Australia</option>
                                            </select>
                                            <div class="help-text">Country for EIO factor lookup</div>
                                        </div>
                                        
                                        <div class="col-md-12">
                                            <div class="alert alert-warning" id="scope{{ $scope }}SpendCalculationResult" style="display: none;">
                                                <i class="fas fa-calculator me-2"></i>
                                                <span id="scope{{ $scope }}SpendCalculationText">Enter spend amount and sector code to calculate emissions</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        
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

                                <div class="col-12">
                                    <label class="form-label">Supporting Documents</label>
                                    <input
                                        type="file"
                                        class="form-control"
                                        name="supporting_documents[]"
                                        multiple
                                        accept=".pdf,.jpg,.jpeg,.png,.webp,.xlsx,.xls,.csv"
                                    >
                                    <div class="help-text">
                                        Optional: Upload invoices, meter screenshots, spreadsheets, or other evidence (max 10MB per file)
                                    </div>
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
    // Dynamic emission factors loaded from database
    const emissionFactors = @json($emissionFactorsMap);
    
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
                
                $(form).find('.site-select').select2({
                    placeholder: "Select site (optional)...",
                    allowClear: true,
                    width: '100%'
                });
                
                $(form).find('.emission-source-select').select2({
                    placeholder: "Select emission source...",
                    allowClear: true,
                    width: '100%'
                });
                
                // Initialize Scope 3 specific Select2 dropdowns
                if (scope === 3) {
                    $(form).find('.scope3-category-select').select2({
                        placeholder: "Select category (optional)...",
                        allowClear: true,
                        width: '100%'
                    });
                    
                    $(form).find('.supplier-select').select2({
                        placeholder: "Select supplier (optional)...",
                        allowClear: true,
                        width: '100%'
                    });
                }
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
        const factorUnitLabel = document.getElementById(`scope${scope}FactorUnitLabel`);
        
        let source = '';
        let unit = '';
        let factor = '';
        
        if (sourceType === 'existing') {
            const sourceSelect = document.getElementById(`scope${scope}EmissionSource`);
            const selectedOption = sourceSelect.options[sourceSelect.selectedIndex];
            source = sourceSelect.value;
            unit = selectedOption ? selectedOption.getAttribute('data-unit') || '' : '';
            factor = selectedOption ? selectedOption.getAttribute('data-factor') || '' : '';
        } else {
            const customInput = document.getElementById(`scope${scope}CustomEmissionSource`);
            source = customInput.value;
            // For custom sources, try to find in emissionFactors map
            if (source && emissionFactors[source]) {
                unit = emissionFactors[source].unit || '';
                factor = emissionFactors[source].factor || '';
            }
        }
        
        // Use data from select option first, then fallback to emissionFactors map
        if (unit && factor) {
            activityUnit.textContent = unit;
            factorInput.value = factor;
            if (factorUnitLabel) factorUnitLabel.textContent = `tCO₂e/${unit || 'unit'}`;
            calculateCO2e(scope);
        } else if (source && emissionFactors[source]) {
            const factorData = emissionFactors[source];
            activityUnit.textContent = factorData.unit || '-';
            factorInput.value = factorData.factor || '';
            if (factorUnitLabel) factorUnitLabel.textContent = `tCO₂e/${(factorData.unit || 'unit')}`;
            calculateCO2e(scope);
        } else {
            // If not found, user needs to enter manually
            activityUnit.textContent = '-';
            if (!factorInput.value) {
                factorInput.value = '';
            }
            if (factorUnitLabel) factorUnitLabel.textContent = 'tCO₂e/unit';
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
    
    // Toggle calculation method for Scope 3
    function toggleCalculationMethod(scope) {
        const method = document.getElementById(`scope${scope}CalculationMethod`).value;
        const spendFields = document.getElementById(`scope${scope}SpendBasedFields`);
        
        if (method === 'spend-based' || method === 'hybrid') {
            spendFields.style.display = 'block';
        } else {
            spendFields.style.display = 'none';
        }
    }
    
    // Calculate emissions from spend amount (Scope 3 spend-based)
    function calculateSpendBased(scope) {
        const spendAmount = parseFloat(document.getElementById(`scope${scope}SpendAmount`).value) || 0;
        const sectorCode = document.getElementById(`scope${scope}SectorCode`).value;
        const country = document.getElementById(`scope${scope}Country`).value;
        const currency = document.getElementById(`scope${scope}SpendCurrency`).value;
        const resultDiv = document.getElementById(`scope${scope}SpendCalculationResult`);
        const resultText = document.getElementById(`scope${scope}SpendCalculationText`);
        
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
                const method = document.getElementById(`scope${scope}CalculationMethod`).value;
                if (method === 'spend-based') {
                    const co2eInput = document.getElementById(`scope${scope}CO2eValue`);
                    if (co2eInput) {
                        co2eInput.value = emissionsT.toFixed(4);
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
    
    // Clear scope form
    function clearScopeForm(scope) {
        const form = document.getElementById(`scope${scope}Form`);
        if (form) {
            form.reset();
            
            // Reset source type toggle
            document.getElementById(`scope${scope}SourceType`).value = 'existing';
            toggleSourceInput(scope);
            
            // Reset calculation method toggle for Scope 3
            if (scope === 3) {
                document.getElementById(`scope${scope}CalculationMethod`).value = 'activity-based';
                toggleCalculationMethod(scope);
            }
            
            // Reset Select2
            if (typeof $ !== 'undefined') {
                $(form).find('.facility-select').val(null).trigger('change');
                $(form).find('.site-select').val(null).trigger('change');
                $(form).find('.emission-source-select').val(null).trigger('change');
                
                if (scope === 3) {
                    $(form).find('.scope3-category-select').val(null).trigger('change');
                    $(form).find('.supplier-select').val(null).trigger('change');
                }
            }
            
            // Reset calculation
            document.getElementById(`scope${scope}ActivityUnit`).textContent = '-';
            document.getElementById(`scope${scope}CalculationFormula`).textContent = 'Activity Data × Emission Factor = CO₂e Value';
            
            // Hide spend calculation result
            if (scope === 3) {
                const resultDiv = document.getElementById(`scope${scope}SpendCalculationResult`);
                if (resultDiv) resultDiv.style.display = 'none';
            }
            
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
                
                // Add Scope 3 specific fields if scope is 3
                if (scope === 3) {
                    const scope3Category = document.getElementById(`scope${scope}Scope3Category`);
                    const supplier = document.getElementById(`scope${scope}Supplier`);
                    const calculationMethod = document.getElementById(`scope${scope}CalculationMethod`);
                    const dataQuality = document.getElementById(`scope${scope}DataQuality`);
                    const spendAmount = document.getElementById(`scope${scope}SpendAmount`);
                    const spendCurrency = document.getElementById(`scope${scope}SpendCurrency`);
                    const sectorCode = document.getElementById(`scope${scope}SectorCode`);
                    const country = document.getElementById(`scope${scope}Country`);
                    
                    if (scope3Category && scope3Category.value) {
                        formData.set('scope3_category_id', scope3Category.value);
                    }
                    if (supplier && supplier.value) {
                        formData.set('supplier_id', supplier.value);
                    }
                    if (calculationMethod) {
                        formData.set('calculation_method', calculationMethod.value);
                    }
                    if (dataQuality) {
                        formData.set('data_quality', dataQuality.value);
                    }
                    if (spendAmount && spendAmount.value) {
                        formData.set('spend_amount', spendAmount.value);
                    }
                    if (spendCurrency) {
                        formData.set('spend_currency', spendCurrency.value);
                    }
                    if (sectorCode && sectorCode.value) {
                        formData.set('sector_code', sectorCode.value);
                    }
                    if (country) {
                        formData.set('country', country.value);
                    }
                }
                
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


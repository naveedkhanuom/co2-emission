@extends('layouts.app')

@section('title', 'Emission Records')
@section('page-title', 'Emission Records')

@section('content')
    <!-- Main Content -->
    <div id="content">
        <!-- Top Navigation Bar -->
     @include('layouts.top-nav') 
        
        <!-- Entry Mode Selection -->
        <div class="row mt-4">
            <div class="col-md-4 mb-4">
                <div class="entry-mode-card active" id="singleEntryCard" onclick="setEntryMode('single')">
                    <div class="entry-mode-icon">
                        <i class="fas fa-file-signature"></i>
                    </div>
                    <h4>Single Entry</h4>
                    <p class="text-muted">Enter one emission record at a time with detailed information</p>
                    <div class="mt-3">
                        <span class="badge bg-success">Recommended for accuracy</span>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="entry-mode-card" id="quickEntryCard" onclick="setEntryMode('quick')">
                    <div class="entry-mode-icon">
                        <i class="fas fa-table"></i>
                    </div>
                    <h4>Quick Entry</h4>
                    <p class="text-muted">Enter multiple records in a table format for efficiency</p>
                    <div class="mt-3">
                        <span class="badge bg-primary">Fast bulk entry</span>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="entry-mode-card" id="templateEntryCard" onclick="setEntryMode('template')">
                    <div class="entry-mode-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h4>Template Based</h4>
                    <p class="text-muted">Use pre-defined templates for common emission sources</p>
                    <div class="mt-3">
                        <span class="badge bg-info">Consistent formatting</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Single Entry Form -->
        <div class="form-container" id="singleEntryForm">
            <h4 class="mb-4">Enter Emission Data</h4>

        <!-- Single Entry Form -->
        <form class="form-container"
                  id="singleEntryForm"
                  method="POST"
                  action="{{ route('emission-records.store') }}">
                @csrf
            <h4 class="mb-4">Enter Emission Data</h4>
            
            <!-- Basic Information Section -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-info-circle"></i> Basic Information
                </div>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label required-label">Date</label>
                        <input type="date" class="form-control date-picker" id="entryDate" name="entryDate" placeholder="Select date" required>
                        <div class="help-text">Select the date when emissions occurred</div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label required-label">Facility / Location</label>
                        <select class="form-select facility-select" id="facilitySelect" name="facilitySelect" required>
                            <option value="">Select facility...</option>
                            @foreach(facilities() as $facility)
                                <option value="{{ $facility->id }}">{{ $facility->name }}</option>
                            @endforeach
                        </select>
                        <div class="help-text">Choose the facility where emissions occurred</div>
                    </div>

                    
                    <div class="col-md-6">
                        <label class="form-label required-label">Scope</label>
                        <select class="form-select" id="scopeSelect" name="scopeSelect" required>
                            <option value="">Select scope...</option>
                            <option value="1">Scope 1 - Direct Emissions</option>
                            <option value="2">Scope 2 - Indirect Emissions (Purchased Energy)</option>
                            <option value="3">Scope 3 - Other Indirect Emissions</option>
                        </select>
                        <div class="help-text">Select GHG Protocol scope category</div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label required-label">Emission Source</label>
                        <select class="form-select emission-source-select" id="emissionSourceSelect" name="emissionSourceSelect" required>
                            <option value="">Select source...</option>
                            <optgroup label="Scope 1 Sources">
                                <option value="natural-gas">Natural Gas Combustion</option>
                                <option value="diesel">Diesel Fuel</option>
                                <option value="gasoline">Gasoline (Company Vehicles)</option>
                                <option value="refrigerants">Refrigerants (F-gases)</option>
                                <option value="process">Process Emissions</option>
                            </optgroup>
                            <optgroup label="Scope 2 Sources">
                                <option value="electricity">Purchased Electricity</option>
                                <option value="steam">Purchased Steam</option>
                                <option value="heating">District Heating</option>
                                <option value="cooling">District Cooling</option>
                            </optgroup>
                            <optgroup label="Scope 3 Sources">
                                <option value="business-travel">Business Travel</option>
                                <option value="employee-commute">Employee Commuting</option>
                                <option value="waste">Waste Disposal</option>
                                <option value="purchased-goods">Purchased Goods & Services</option>
                                <option value="transportation">Transportation & Distribution</option>
                            </optgroup>
                        </select>
                        <div class="help-text">Select the specific source of emissions</div>
                    </div>
                </div>
            </div>
            
            <!-- Calculation Mode -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-calculator"></i> Calculation Method
                </div>
                
                <div class="calculation-mode">
                    <div class="calc-option active" onclick="setCalculationMode('direct')">
                        <h6>Direct Entry</h6>
                        <p class="small text-muted mb-0">Enter CO₂e value directly</p>
                    </div>
                    <div class="calc-option" onclick="setCalculationMode('activity', this)">
                        <h6>Activity-Based</h6>
                        <p class="small text-muted mb-0">Calculate from activity data</p>
                    </div>
                </div>
                
                <!-- Direct Entry -->
                <div id="directEntrySection">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required-label">CO₂e Value</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="co2eValue" name="co2eValue" step="0.01" min="0" placeholder="0.00" required>
                                <span class="input-group-text">tCO₂e</span>
                            </div>
                            <div class="help-text">Enter the total CO₂ equivalent value</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Confidence Level</label>
                            <select class="form-select" id="confidenceLevel" name="confidenceLevel">
                                <option value="high">High Confidence</option>
                                <option value="medium" selected>Medium Confidence</option>
                                <option value="low">Low Confidence</option>
                                <option value="estimated">Estimated</option>
                            </select>
                            <div class="help-text">How confident are you in this data?</div>
                        </div>
                    </div>
                </div>
                
                <!-- Activity-Based Calculation Section -->
                <div class="form-section" id="activityBasedSection" style="display: none;">
                    <div class="row g-3">
                        <!-- Emission Source -->
                        <div class="col-md-4">
                            <label class="form-label required-label">Emission Source</label>
                            <select class="form-select" id="emissionSourceSelect" name="emissionSourceSelect" required>
                                <option value="">Select source...</option>
                                <optgroup label="Scope 1 Sources">
                                    <option value="natural-gas">Natural Gas Combustion</option>
                                    <option value="diesel">Diesel Fuel</option>
                                    <option value="gasoline">Gasoline (Company Vehicles)</option>
                                </optgroup>
                                <optgroup label="Scope 2 Sources">
                                    <option value="electricity">Purchased Electricity</option>
                                </optgroup>
                                <optgroup label="Scope 3 Sources">
                                    <option value="business-travel">Business Travel</option>
                                    <option value="waste">Waste Disposal</option>
                                </optgroup>
                            </select>
                            <div class="help-text">Select the specific source of emissions</div>
                        </div>

                        <!-- Activity Data -->
                        <div class="col-md-4">
                            <label class="form-label required-label">Activity Data</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="activityData" name="activityData" step="0.01" min="0" placeholder="0.00">
                                <select class="form-select" id="activityUnitSelect">
                                    <option value="kWh">kWh</option>
                                    <option value="liters">Liters</option>
                                    <option value="m³">m³</option>
                                    <option value="km">km</option>
                                    <option value="kg">kg</option>
                                </select>
                            </div>
                            <div class="help-text">Select the unit for activity data</div>
                        </div>

                        <!-- Emission Factor -->
                        <div class="col-md-4">
                            <label class="form-label required-label">Emission Factor</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="emissionFactor" name="emissionFactor" step="0.00001" min="0" placeholder="0.0000">
                                <span class="input-group-text">tCO₂e/unit</span>
                            </div>
                            <div class="help-text">Emission factor for this activity</div>
                        </div>
                    </div>

                    <!-- Calculated Result -->
                    <div class="row g-3 mt-3">
                        <div class="col-md-12">
                            <label class="form-label">Calculated CO₂e</label>
                            <div class="calculation-display">
                                <div class="calculation-formula" id="calculationFormula">0.00 × 0.0000 = 0.00 tCO₂e</div>
                                <div class="mt-2">
                                    <strong id="calculatedResult">0.00 tCO₂e</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            
            <!-- Additional Details -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-file-alt"></i> Additional Details
                </div>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Department / Cost Center</label>
                        <select class="form-select" id="departmentSelect" name="departmentSelect" required>
                            <option value="">Select department...</option>
                            @foreach(departments() as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    
                    <div class="col-md-6">
                        <label class="form-label">Data Source</label>
                        <select class="form-select" id="dataSource" name="dataSource">
                            <option value="manual" selected>Manual Entry</option>
                            <option value="meter">Meter Reading</option>
                            <option value="invoice">Utility Invoice</option>
                            <option value="estimate">Estimate</option>
                        </select>
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label">Notes / Comments</label>
                        <textarea class="form-control" id="entryNotes" name="entryNotes" rows="3" placeholder="Add any additional information about this emission record..."></textarea>
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
                    <button type="button" class="btn btn-outline-primary" onclick="clearForm()">
                        <i class="fas fa-redo me-2"></i>Clear Form
                    </button>
                    <button type="submit" name="status" value="active" class="btn btn-success">
                        <i class="fas fa-check-circle me-2"></i>Submit Entry
                    </button>
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
</script>

<script>
        // Entry mode state
        let currentEntryMode = 'single';
        let calculationMode = 'direct';
        let quickEntryRows = 0;

// Allowed units for each emission source
const allowedUnits = {
    "electricity": ["kWh"],
    "natural-gas": ["m³"],
    "diesel": ["liters"],
    "gasoline": ["liters"],
    "business-travel": ["km"],
    "waste": ["kg"],
};

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
        unitSelect.value = validUnits[0];
    } else {
        unitSelect.value = '';
    }

    updateCalculation();
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

    // Auto-fill CO₂e input
    document.getElementById('co2eValue').value = result.toFixed(2);
}

// Event listeners
document.getElementById('emissionSourceSelect').addEventListener('change', updateUnitOptions);
document.getElementById('activityData').addEventListener('input', updateCalculation);
document.getElementById('emissionFactor').addEventListener('input', updateCalculation);
document.getElementById('activityUnitSelect').addEventListener('change', updateCalculation);


// Update Activity Unit & Factor Unit dynamically
function updateUnits() {
    const source = document.getElementById('emissionSourceSelect').value;
    const config = emissionUnits[source] || emissionUnits["default"];

    // Update the activity unit
    document.getElementById('activityUnit').textContent = config.unit;

    // Update the factor unit next to emissionFactor input
    document.getElementById('factorUnit').textContent = config.factorUnit;

    // Recalculate instantly
    updateCalculation();
}

// Listen to changes on emission source
document.getElementById('emissionSourceSelect')
    .addEventListener('change', updateUnits);

// Also trigger calculation on activity/factor input
document.getElementById('activityData')
    .addEventListener('input', updateCalculation);

document.getElementById('emissionFactor')
    .addEventListener('input', updateCalculation);



        
        // Initialize components
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize date picker
            flatpickr(".date-picker", {
                dateFormat: "Y-m-d",
                defaultDate: new Date(),
                maxDate: new Date()
            });
            
            // Initialize Select2 for dropdowns
            $('.facility-select').select2({
                placeholder: "Select facility...",
                allowClear: true,
                width: '100%'
            });
            
            $('.emission-source-select').select2({
                placeholder: "Select emission source...",
                allowClear: true,
                width: '100%'
            });
            
            // Update calculation when activity data changes
            document.getElementById('activityData')?.addEventListener('input', updateCalculation);
            document.getElementById('emissionFactor')?.addEventListener('input', updateCalculation);
            
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

        document.getElementById('activityData')
    .addEventListener('input', updateCalculation);

document.getElementById('emissionFactor')
    .addEventListener('input', updateCalculation);

    document.getElementById('emissionSourceSelect')
    .addEventListener('change', updateUnits);


        
        // Set calculation mode
        function setCalculationMode(mode, el = null) {
    calculationMode = mode;

    document.querySelectorAll('.calc-option').forEach(option => {
        option.classList.remove('active');
    });

    if (el) {
        el.classList.add('active');
    }

    document.getElementById('directEntrySection').style.display =
        mode === 'direct' ? 'block' : 'none';

    document.getElementById('activityBasedSection').style.display =
        mode === 'activity' ? 'block' : 'none';

    if (mode === 'activity') {
        updateCalculation();
    }
}

        
// Update calculation display
function updateCalculation() {
    const activity = parseFloat(document.getElementById('activityData').value) || 0;
    const factor = parseFloat(document.getElementById('emissionFactor').value) || 0;
    const result = activity * factor;

    document.getElementById('calculationFormula').textContent =
        `${activity.toFixed(2)} × ${factor.toFixed(6)} = ${result.toFixed(2)} tCO₂e`;

    document.getElementById('calculatedResult').textContent =
        `${result.toFixed(2)} tCO₂e`;

    document.getElementById('co2eValue').value = result.toFixed(2);
}


function updateUnits() {
    const source = document.getElementById('emissionSourceSelect').value;
    const config = emissionUnits[source] || emissionUnits.default;

    // Update unit labels
    document.getElementById('activityUnit').textContent = config.unit;

    document.querySelector('#emissionFactor')
        .nextElementSibling.textContent = config.factorUnit;

    // Recalculate after unit change
    updateCalculation();
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
        facilityOptions += `<option value="${f.id}">${f.name}</option>`;
    });

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
                <option value="">Select source...</option>
                <option value="natural-gas">Natural Gas</option>
                <option value="electricity">Electricity</option>
                <option value="vehicle">Company Vehicles</option>
                <option value="travel">Business Travel</option>
                <option value="waste">Waste</option>
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
                const entry = {
                    entryDate: row.querySelector('.quick-entry-date').value,
                    facilitySelect: row.querySelector('.quick-entry-facility').value,
                    scopeSelect: row.querySelector('.quick-entry-scope').value,
                    emissionSourceSelect: row.querySelector('.quick-entry-source').value,
                    co2eValue: row.querySelector('.quick-entry-co2e').value,
                    confidenceLevel: 'medium', // default for quick entry
                    entryNotes: row.querySelector('.quick-entry-notes').value,
                    dataSource: 'manual',
                    departmentSelect: null
                };

                // Validate required fields
                Object.keys(entry).forEach(key => {
                    if ((entry[key] === null || entry[key] === '') && key !== 'entryNotes' && key !== 'departmentSelect') {
                        valid = false;
                        row.querySelectorAll('input, select').forEach(i => i.classList.add('is-invalid'));
                    } else {
                        row.querySelectorAll('input, select').forEach(i => i.classList.remove('is-invalid'));
                    }
                });

                entries.push(entry);
            });

            if (!valid) {
                showToast('Please fill all required fields', 'error');
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
                    document.getElementById('emissionSourceSelect').value = 'electricity';
                    document.getElementById('activityUnit').textContent = 'kWh';
                    setCalculationMode('activity');
                    break;
                case 'fleet':
                    document.getElementById('scopeSelect').value = '1';
                    document.getElementById('emissionSourceSelect').value = 'gasoline';
                    document.getElementById('activityUnit').textContent = 'liters';
                    setCalculationMode('activity');
                    break;
                case 'travel':
                    document.getElementById('scopeSelect').value = '3';
                    document.getElementById('emissionSourceSelect').value = 'business-travel';
                    break;
                case 'waste':
                    document.getElementById('scopeSelect').value = '3';
                    document.getElementById('emissionSourceSelect').value = 'waste';
                    document.getElementById('activityUnit').textContent = 'kg';
                    setCalculationMode('activity');
                    break;
            }
            
            // Update Select2 values
            $('.emission-source-select').trigger('change');
            
            showToast(`Template "${templateName}" applied`, 'success');
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
        
        // Submit entry
        function submitEntry() {
            const form = document.getElementById('singleEntryForm');

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json'
                },
                body: new FormData(form)
            })
            .then(res => {
                if (!res.ok) throw res;
                return res.json();
            })
            .then(data => {
                const modal = new bootstrap.Modal(
                    document.getElementById('successModal')
                );
                modal.show();
                form.reset();
            })
            .catch(async err => {
                const error = await err.json();
                showToast(error.message || 'Validation error', 'error');
            });
        }

        
        // Clear form
        function clearForm() {
            document.getElementById('singleEntryForm').reset();
            $('.facility-select').val(null).trigger('change');
            $('.emission-source-select').val(null).trigger('change');
            
            // Remove validation classes
            document.querySelectorAll('.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });
            
            showToast('Form cleared', 'info');
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
        
        // Sidebar toggle for mobile
        document.getElementById('sidebarCollapse').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('content').classList.toggle('active');
        });
    </script>
@endpush

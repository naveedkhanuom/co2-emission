@extends('layouts.app')

@section('title', 'Companies')
@section('page-title', 'Companies')

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="companies-app container-fluid mt-4">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Topbar -->
        <div class="topbar">
            <h2><span class="sb"><i class="fas fa-building"></i></span> Companies</h2>
            <p>Register and manage companies for GHG emissions tracking.</p>
            <button type="button" class="btn-add" id="addCompanyBtn">
                <i class="fas fa-plus"></i> Register New Company
            </button>
        </div>

        <!-- DataTable Card -->
        <div class="card companies-datatable-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                <h5 class="mb-0">Companies List</h5>
                <div class="input-group" style="width: 280px;">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" id="searchInput" class="form-control" placeholder="Search companies...">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="companiesTable">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>Name</th>
                                <th>Code</th>
                                <th>Industry</th>
                                <th>Size</th>
                                <th>Country</th>
                                <th width="100" class="text-center">Status</th>
                                <th width="150" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="table-info-text">
                        Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of <span id="totalCount">0</span> companies
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Add/Edit Company -->
<div class="modal fade" id="companyModal" tabindex="-1" aria-labelledby="companyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <form id="companyForm">
                <input type="hidden" name="id" id="companyId">
                <div class="modal-header">
                    <div class="icon-circle">
                        <i class="fas fa-building"></i>
                    </div>
                    <h5 class="modal-title" id="modalTitle">Register New Company</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <div id="formErrors" class="alert alert-danger d-none"></div>
                    
                    <!-- Basic Information -->
                    <div class="mb-4">
                        <h6 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-info-circle me-2 text-primary"></i>Basic Information
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Company Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="companyName" class="form-control" required placeholder="e.g., ABC Manufacturing Ltd">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Company Code</label>
                                <input type="text" name="code" id="companyCode" class="form-control" placeholder="e.g., ABC-MFG">
                                <small class="text-muted">Unique identifier for the company</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Industry Type <span class="text-danger">*</span></label>
                                <select name="industry_type" id="industryType" class="form-select" required>
                                    <option value="">Select Industry</option>
                                    <option value="manufacturing">Manufacturing</option>
                                    <option value="energy">Energy</option>
                                    <option value="transportation">Transportation</option>
                                    <option value="agriculture">Agriculture</option>
                                    <option value="construction">Construction</option>
                                    <option value="retail">Retail</option>
                                    <option value="healthcare">Healthcare</option>
                                    <option value="education">Education</option>
                                    <option value="technology">Technology</option>
                                    <option value="finance">Finance</option>
                                    <option value="hospitality">Hospitality</option>
                                    <option value="mining">Mining</option>
                                    <option value="chemical">Chemical</option>
                                    <option value="textile">Textile</option>
                                    <option value="food_beverage">Food & Beverage</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Company Size</label>
                                <select name="size" id="companySize" class="form-select">
                                    <option value="">Select Size</option>
                                    <option value="small">Small (1-50 employees)</option>
                                    <option value="medium">Medium (51-250 employees)</option>
                                    <option value="large">Large (251-1000 employees)</option>
                                    <option value="enterprise">Enterprise (1000+ employees)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Employee Count</label>
                                <input type="number" name="employee_count" id="employeeCount" class="form-control" min="0" placeholder="e.g., 500">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Annual Revenue</label>
                                <div class="input-group">
                                    <select name="currency" id="currency" class="form-select" style="max-width: 100px;">
                                        <option value="USD">USD</option>
                                        <option value="EUR">EUR</option>
                                        <option value="GBP">GBP</option>
                                        <option value="JPY">JPY</option>
                                        <option value="CNY">CNY</option>
                                    </select>
                                    <input type="number" name="annual_revenue" id="annualRevenue" class="form-control" min="0" step="0.01" placeholder="e.g., 1000000">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="mb-4">
                        <h6 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-address-book me-2 text-primary"></i>Contact Information
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Contact Person</label>
                                <input type="text" name="contact_person" id="contactPerson" class="form-control" placeholder="e.g., John Doe">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" id="companyEmail" class="form-control" placeholder="contact@company.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" id="companyPhone" class="form-control" placeholder="+1 234 567 8900">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Website</label>
                                <input type="url" name="website" id="companyWebsite" class="form-control" placeholder="https://www.company.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Country</label>
                                <input type="text" name="country" id="companyCountry" class="form-control" placeholder="e.g., United States">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Address</label>
                                <input type="text" name="address" id="companyAddress" class="form-control" placeholder="Street address, City, State">
                            </div>
                        </div>
                    </div>

                    <!-- Legal Information -->
                    <div class="mb-4">
                        <h6 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-gavel me-2 text-primary"></i>Legal Information
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Tax ID</label>
                                <input type="text" name="tax_id" id="taxId" class="form-control" placeholder="Tax identification number">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Registration Number</label>
                                <input type="text" name="registration_number" id="registrationNumber" class="form-control" placeholder="Company registration number">
                            </div>
                        </div>
                    </div>

                    <!-- Reporting Configuration -->
                    <div class="mb-4">
                        <h6 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-chart-line me-2 text-primary"></i>Reporting Configuration
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Timezone</label>
                                <select name="timezone" id="timezone" class="form-select">
                                    <option value="UTC">UTC</option>
                                    <option value="America/New_York">America/New_York (EST)</option>
                                    <option value="America/Chicago">America/Chicago (CST)</option>
                                    <option value="America/Denver">America/Denver (MST)</option>
                                    <option value="America/Los_Angeles">America/Los_Angeles (PST)</option>
                                    <option value="Europe/London">Europe/London (GMT)</option>
                                    <option value="Europe/Paris">Europe/Paris (CET)</option>
                                    <option value="Asia/Dubai">Asia/Dubai (GST)</option>
                                    <option value="Asia/Tokyo">Asia/Tokyo (JST)</option>
                                    <option value="Asia/Shanghai">Asia/Shanghai (CST)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fiscal Year Start</label>
                                <input type="text" name="fiscal_year_start" id="fiscalYearStart" class="form-control" placeholder="MM-DD (e.g., 01-01)">
                                <small class="text-muted">Format: MM-DD</small>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Reporting Standards</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="reporting_standards[]" value="GHG Protocol" id="ghgProtocol">
                                    <label class="form-check-label" for="ghgProtocol">GHG Protocol</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="reporting_standards[]" value="ISO 14064" id="iso14064">
                                    <label class="form-check-label" for="iso14064">ISO 14064</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="reporting_standards[]" value="CDP" id="cdp">
                                    <label class="form-check-label" for="cdp">CDP (Carbon Disclosure Project)</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Scopes Enabled</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="scopes_enabled[]" value="1" id="scope1" checked>
                                    <label class="form-check-label" for="scope1">Scope 1 (Direct Emissions)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="scopes_enabled[]" value="2" id="scope2" checked>
                                    <label class="form-check-label" for="scope2">Scope 2 (Indirect Emissions - Energy)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="scopes_enabled[]" value="3" id="scope3" checked>
                                    <label class="form-check-label" for="scope3">Scope 3 (Other Indirect Emissions)</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="isActive" checked>
                                    <label class="form-check-label" for="isActive">Company is Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Company
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.min.css" rel="stylesheet">
<style>
    /* Modal scrolling fix */
    #companyModal .modal-dialog {
        max-height: 90vh;
        margin: 1.75rem auto;
    }
    
    #companyModal .modal-content {
        display: flex;
        flex-direction: column;
        max-height: 90vh;
        overflow: hidden;
    }
    
    #companyModal .modal-header {
        flex-shrink: 0;
        border-bottom: 1px solid rgba(0,0,0,.125);
    }
    
    #companyModal .modal-body {
        flex: 1 1 auto;
        overflow-y: auto;
        padding: 1.5rem;
        min-height: 0;
    }
    
    #companyModal .modal-footer {
        flex-shrink: 0;
        border-top: 1px solid rgba(0,0,0,.125);
        padding: 1rem;
        background: white;
        position: relative;
        z-index: 10;
    }
    
    /* Ensure modal is visible on all screen sizes */
    @media (max-height: 600px) {
        #companyModal .modal-dialog {
            max-height: 95vh;
        }
        #companyModal .modal-content {
            max-height: 95vh;
        }
    }

    /* New design (match other modules) */
    .companies-app * { box-sizing: border-box; }
    .companies-app { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    .companies-app .topbar { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; padding: 20px 24px; background: linear-gradient(135deg, #fff 0%, var(--gray-50) 100%); border: 1px solid var(--gray-200); border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
    .companies-app .topbar h2 { font-size: 1.35rem; font-weight: 700; letter-spacing: -0.02em; display: flex; align-items: center; gap: 10px; margin: 0; color: var(--gray-800); }
    .companies-app .topbar h2 .sb { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%); color: #fff; font-size: 1rem; font-weight: 700; box-shadow: 0 2px 8px rgba(46,125,50,.25); }
    .companies-app .topbar p { color: var(--gray-600); font-size: 0.875rem; flex: 1; min-width: 180px; margin: 0; line-height: 1.4; }
    .companies-app .btn-add { margin-left: auto; padding: 10px 20px; border-radius: 10px; background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%); color: #fff; border: none; font-size: 0.875rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
    .companies-app .btn-add:hover { background: linear-gradient(135deg, var(--dark-green) 0%, var(--primary-green) 100%); color: #fff; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(46,125,50,.35); }
    .companies-app .alert { border-radius: 12px; border: 1px solid transparent; }
    .companies-app .alert-success { background: rgba(76,175,80,0.1); border-color: rgba(76,175,50,0.25); color: var(--dark-green); }
    .companies-app .alert-danger { background: rgba(211,47,47,0.08); border-color: rgba(211,47,47,0.2); color: var(--danger-red); }

    .companies-app .companies-datatable-card { background: #fff; border: 1px solid var(--gray-200); border-radius: 16px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
    .companies-app .companies-datatable-card .card-header { padding: 16px 20px; border-bottom: 1px solid var(--gray-200); background: linear-gradient(180deg, var(--gray-50) 0%, #fff 100%); }
    .companies-app .companies-datatable-card .card-header h5 { font-size: 1.0625rem; font-weight: 700; color: var(--gray-800); }
    .companies-app .companies-datatable-card .card-header .input-group { border-radius: 10px; overflow: hidden; border: 1px solid var(--gray-200); }
    .companies-app .companies-datatable-card .card-header .input-group-text { background: var(--gray-50); border: none; color: var(--gray-600); padding: 10px 14px; }
    .companies-app .companies-datatable-card .card-header .form-control { border: none; padding: 10px 14px; font-size: 0.875rem; }

    .companies-app .companies-datatable-card #companiesTable { width: 100% !important; border-collapse: separate; border-spacing: 0; }
    .companies-app .companies-datatable-card #companiesTable thead th { background: var(--gray-100); color: var(--gray-600); font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; padding: 14px 16px; border: none; border-bottom: 1px solid var(--gray-200); }
    .companies-app .companies-datatable-card #companiesTable thead th:first-child { padding-left: 20px; }
    .companies-app .companies-datatable-card #companiesTable tbody td { padding: 14px 16px; font-size: 0.875rem; color: var(--gray-800); border: none; border-bottom: 1px solid var(--gray-100); vertical-align: middle; }
    .companies-app .companies-datatable-card #companiesTable tbody td:first-child { padding-left: 20px; }
    .companies-app .companies-datatable-card #companiesTable tbody tr:hover td { background: var(--gray-50); }
    .companies-app .companies-datatable-card #companiesTable tbody tr:last-child td { border-bottom: none; }

    .companies-app .companies-datatable-card .card-footer { padding: 12px 20px; border-top: 1px solid var(--gray-200); background: var(--gray-50); font-size: 0.8125rem; color: var(--gray-600); }

    .companies-app .companies-datatable-card .dataTables_wrapper { padding: 0; }
    .companies-app .companies-datatable-card .dataTables_wrapper .dataTables_length,
    .companies-app .companies-datatable-card .dataTables_wrapper .dataTables_filter { display: none; }
    .companies-app .companies-datatable-card .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 6px 12px; margin: 0 2px; border-radius: 8px; border: 1px solid var(--gray-200); background: #fff; color: var(--gray-700) !important; font-size: 0.8125rem; font-weight: 600; }
    .companies-app .companies-datatable-card .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: var(--gray-100) !important; border-color: var(--gray-300) !important; color: var(--gray-800) !important; }
    .companies-app .companies-datatable-card .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: var(--primary-green) !important; border-color: var(--primary-green) !important; color: #fff !important; }
    .companies-app .companies-datatable-card .dataTables_wrapper .dataTables_processing { background: rgba(255,255,255,0.95) !important; border-radius: 10px !important; padding: 14px 24px !important; font-weight: 600 !important; font-size: 0.875rem !important; color: var(--gray-700) !important; border: 1px solid var(--gray-200) !important; box-shadow: 0 2px 8px rgba(0,0,0,.06) !important; }
    .companies-app .companies-datatable-card .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }

    /* System modal look (shared) */
    .modal-content { border-radius: 16px; }
    .modal-header { gap: 10px; }
    .icon-circle {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%);
        color: #fff;
        box-shadow: 0 4px 12px rgba(46,125,50,.25);
        flex: 0 0 auto;
    }
</style>
@endpush

@push('scripts')
<!-- jQuery and DataTables JS -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    var companyModal = new bootstrap.Modal(document.getElementById('companyModal'));
    
    // Load companies data
    function loadCompanies() {
        $.ajax({
            url: '{{ route("companies.index") }}',
            method: 'GET',
            dataType: 'json',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(companies) {
                if (Array.isArray(companies)) {
                    table.clear().rows.add(companies).draw();
                } else {
                    // If response is not array, try to get data property
                    const data = companies.data || companies;
                    if (Array.isArray(data)) {
                        table.clear().rows.add(data).draw();
                    } else {
                        console.error('Invalid data format:', companies);
                        table.clear().draw();
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading companies:', error);
                console.error('Response:', xhr.responseText);
                // Don't show error if it's just a view being returned
                if (xhr.status !== 200) {
                    Swal.fire('Error', 'Failed to load companies. Please refresh the page.', 'error');
                }
            }
        });
    }

    // Initialize DataTable
    var table = $('#companiesTable').DataTable({
        processing: true,
        serverSide: false,
        data: [],
        ajax: {
            url: '{{ route("companies.index") }}',
            type: 'GET',
            dataType: 'json',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            dataSrc: function(json) {
                if (Array.isArray(json)) {
                    return json;
                } else if (json && Array.isArray(json.data)) {
                    return json.data;
                } else {
                    console.error('Unexpected data format:', json);
                    return [];
                }
            },
            error: function(xhr, error, thrown) {
                console.error('DataTables AJAX error:', error);
                console.error('Status:', xhr.status);
                console.error('Response:', xhr.responseText);
                if (xhr.status === 0) {
                    // Fallback: try loading directly
                    loadCompanies();
                }
            }
        },
        columns: [
            { 
                data: 'id', 
                name: 'id',
                render: function(data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            { 
                data: 'name', 
                name: 'name',
                orderable: true,
                searchable: true
            },
            { 
                data: 'code', 
                name: 'code',
                defaultContent: '-',
                orderable: true,
                searchable: true
            },
            { 
                data: 'industry_type',
                name: 'industry_type',
                render: function(data) {
                    if (!data) return '-';
                    return data.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                },
                orderable: true,
                searchable: true
            },
            { 
                data: 'size',
                name: 'size',
                render: function(data) {
                    if (!data) return '-';
                    const sizes = {
                        'small': '<span class="badge bg-info">Small</span>',
                        'medium': '<span class="badge bg-primary">Medium</span>',
                        'large': '<span class="badge bg-warning">Large</span>',
                        'enterprise': '<span class="badge bg-success">Enterprise</span>'
                    };
                    return sizes[data] || data;
                },
                orderable: true,
                searchable: false
            },
            { 
                data: 'country', 
                name: 'country',
                defaultContent: '-',
                orderable: true,
                searchable: true
            },
            {
                data: 'is_active',
                name: 'is_active',
                render: function(data) {
                    return data 
                        ? '<span class="badge bg-success">Active</span>'
                        : '<span class="badge bg-secondary">Inactive</span>';
                },
                orderable: true,
                searchable: false,
                className: 'text-center'
            },
            { 
                data: null, 
                name: 'actions',
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function(data, type, row) {
                    return `
                        <button class="btn btn-sm btn-info viewBtn" data-id="${row.id}" title="View">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-primary editBtn" data-id="${row.id}" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger deleteBtn" data-id="${row.id}" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                }
            }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            emptyTable: 'No companies found',
            zeroRecords: 'No matching companies found',
            search: '',
            searchPlaceholder: 'Search...',
            paginate: {
                first: '<i class="fas fa-angle-double-left"></i>',
                previous: '<i class="fas fa-angle-left"></i>',
                next: '<i class="fas fa-angle-right"></i>',
                last: '<i class="fas fa-angle-double-right"></i>'
            }
        },
        dom: 'rt<"row mt-3"<"col-sm-12"p>>',
        drawCallback: function () {
            var api = this.api();
            var info = api.page.info();
            $('#showingFrom').text(info.recordsDisplay ? info.start + 1 : 0);
            $('#showingTo').text(info.end);
            $('#totalCount').text(info.recordsDisplay);
        }
    });
    
    // Load companies on page load (fallback if AJAX doesn't work)
    setTimeout(function() {
        if (table.data().count() === 0) {
            loadCompanies();
        }
    }, 500);
    
    // Custom search input
    $('#searchInput').on('keyup', function() {
        table.search(this.value).draw();
    });
    
    // Add Company Button
    $('#addCompanyBtn').click(function() {
        $('#companyForm')[0].reset();
        $('#companyId').val('');
        $('#modalTitle').text('Register New Company');
        $('#formErrors').html('').addClass('d-none');
        $('#scope1, #scope2, #scope3').prop('checked', true);
        $('#isActive').prop('checked', true);
        companyModal.show();
    });

    // Submit Company Form
    $('#companyForm').submit(function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = {};
        formData.forEach((value, key) => {
            if (key.includes('[]')) {
                const baseKey = key.replace('[]', '');
                if (!data[baseKey]) data[baseKey] = [];
                data[baseKey].push(value);
            } else {
                data[key] = value;
            }
        });

        // Convert checkboxes
        if (!data['reporting_standards']) data['reporting_standards'] = [];
        if (!data['scopes_enabled']) data['scopes_enabled'] = [];
        
        // Convert is_active to proper boolean
        data['is_active'] = $('#isActive').is(':checked') ? true : false;

        const url = $('#companyId').val() 
            ? `{{ url('companies') }}/${$('#companyId').val()}`
            : '{{ route("companies.store") }}';
        const method = $('#companyId').val() ? 'PUT' : 'POST';

        // Ensure is_active is sent as boolean
        data['is_active'] = data['is_active'] === true || data['is_active'] === 'true' || data['is_active'] === 1 || data['is_active'] === '1';

        $.ajax({
            url: url,
            method: method,
            data: data,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            success: function(response) {
                if (response.success) {
                    companyModal.hide();
                    loadCompanies(); // Reload companies
                    Swal.fire('Success', response.message || 'Company saved successfully!', 'success');
                } else {
                    Swal.fire('Error', response.message || 'Failed to save company', 'error');
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors || {};
                    let errorHtml = '<ul class="mb-0">';
                    Object.keys(errors).forEach(key => {
                        errors[key].forEach(error => {
                            errorHtml += `<li>${error}</li>`;
                        });
                    });
                    errorHtml += '</ul>';
                    $('#formErrors').html(errorHtml).removeClass('d-none');
                } else {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Something went wrong!', 'error');
                }
            }
        });
    });

    // Edit Company
    $(document).on('click', '.editBtn', function() {
        const id = $(this).data('id');
        $.ajax({
            url: `{{ url('companies') }}/${id}`,
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            },
            success: function(company) {
                $('#companyId').val(company.id);
                $('#companyName').val(company.name);
                $('#companyCode').val(company.code);
                $('#industryType').val(company.industry_type);
                $('#companySize').val(company.size);
                $('#employeeCount').val(company.employee_count);
                $('#annualRevenue').val(company.annual_revenue);
                $('#currency').val(company.currency || 'USD');
                $('#contactPerson').val(company.contact_person);
                $('#companyEmail').val(company.email);
                $('#companyPhone').val(company.phone);
                $('#companyWebsite').val(company.website);
                $('#companyCountry').val(company.country);
                $('#companyAddress').val(company.address);
                $('#taxId').val(company.tax_id);
                $('#registrationNumber').val(company.registration_number);
                $('#timezone').val(company.timezone || 'UTC');
                $('#fiscalYearStart').val(company.fiscal_year_start);
                $('#isActive').prop('checked', company.is_active !== false);

                // Set reporting standards
                $('input[name="reporting_standards[]"]').prop('checked', false);
                if (company.reporting_standards) {
                    company.reporting_standards.forEach(standard => {
                        $(`input[value="${standard}"]`).prop('checked', true);
                    });
                }

                // Set scopes
                $('input[name="scopes_enabled[]"]').prop('checked', false);
                if (company.scopes_enabled) {
                    company.scopes_enabled.forEach(scope => {
                        $(`input[value="${scope}"]`).prop('checked', true);
                    });
                }

                $('#modalTitle').text('Edit Company');
                $('#formErrors').html('').addClass('d-none');
                companyModal.show();
            }
        });
    });

    // Delete Company
    $(document).on('click', '.deleteBtn', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('companies') }}/${id}`,
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            loadCompanies(); // Reload companies
                            Swal.fire('Deleted!', response.message || 'Company deleted successfully!', 'success');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Failed to delete company', 'error');
                    }
                });
            }
        });
    });

    // View Company
    $(document).on('click', '.viewBtn', function() {
        const id = $(this).data('id');
        window.location.href = `{{ url('companies') }}/${id}`;
    });

    // Auto-hide success message after 5 seconds
    setTimeout(function() {
        $('.alert-success').fadeOut('slow');
    }, 5000);
});
</script>
@endpush

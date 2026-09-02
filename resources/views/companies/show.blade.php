@extends('layouts.app')

@section('title', $company->name)
@section('page-title', 'Company')

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="container-fluid py-4">

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
            <div>
                <a href="{{ route('companies.index') }}" class="btn btn-outline-secondary btn-sm mb-2">
                    <i class="fas fa-arrow-left me-1"></i> Back to companies
                </a>
                <h1 class="h4 mb-1">{{ $company->name }}</h1>
                <p class="text-muted small mb-0">
                    {{ $company->code ?? '—' }}
                    @if($company->industry_type)
                        · {{ ucfirst(str_replace('_', ' ', $company->industry_type)) }}
                    @endif
                </p>
            </div>

            <div class="d-flex align-items-center gap-2">
                @if($company->is_active)
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">Active</span>
                @else
                    <span class="badge bg-secondary-subtle text-secondary border px-3 py-2">Inactive</span>
                @endif

                {{-- stored_file_url() rather than a '/storage/' prefix: under tenancy an
                     uploaded logo lives in the client's own storage, which the
                     public/storage symlink does not reach. --}}
                @php $logoUrl = stored_file_url($company->logo); @endphp
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $company->name }}"
                         style="height:44px;width:auto;max-width:160px;object-fit:contain;">
                @endif
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><i class="fas fa-building text-success me-2"></i>Organisation</h5>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4 text-muted">Name</dt>
                            <dd class="col-sm-8">{{ $company->name }}</dd>

                            <dt class="col-sm-4 text-muted">Code</dt>
                            <dd class="col-sm-8">{{ $company->code ?? '—' }}</dd>

                            <dt class="col-sm-4 text-muted">Industry</dt>
                            <dd class="col-sm-8">{{ $company->industry_type ? ucfirst(str_replace('_', ' ', $company->industry_type)) : '—' }}</dd>

                            <dt class="col-sm-4 text-muted">Sub-industry</dt>
                            <dd class="col-sm-8">{{ $company->sub_industry ?? '—' }}</dd>

                            <dt class="col-sm-4 text-muted">ISIC code</dt>
                            <dd class="col-sm-8">{{ $company->isic_code ?? '—' }}</dd>

                            <dt class="col-sm-4 text-muted">Description</dt>
                            <dd class="col-sm-8">{{ $company->business_description ?? '—' }}</dd>

                            <dt class="col-sm-4 text-muted">Size</dt>
                            <dd class="col-sm-8">{{ $company->size ? ucfirst($company->size) : '—' }}</dd>

                            <dt class="col-sm-4 text-muted">Employees</dt>
                            <dd class="col-sm-8">{{ $company->employee_count !== null ? number_format($company->employee_count) : '—' }}</dd>

                            <dt class="col-sm-4 text-muted">Annual revenue</dt>
                            <dd class="col-sm-8">
                                @if($company->annual_revenue !== null)
                                    {{ number_format((float) $company->annual_revenue, 2) }} {{ $company->currency }}
                                @else
                                    —
                                @endif
                            </dd>
                        </dl>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><i class="fas fa-address-book text-success me-2"></i>Contact</h5>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4 text-muted">Contact person</dt>
                            <dd class="col-sm-8">{{ $company->contact_person ?? '—' }}</dd>

                            <dt class="col-sm-4 text-muted">Email</dt>
                            <dd class="col-sm-8">
                                @if($company->email)
                                    <a href="mailto:{{ $company->email }}">{{ $company->email }}</a>
                                @else — @endif
                            </dd>

                            <dt class="col-sm-4 text-muted">Phone</dt>
                            <dd class="col-sm-8">{{ $company->phone ?? '—' }}</dd>

                            <dt class="col-sm-4 text-muted">Website</dt>
                            <dd class="col-sm-8">
                                @if($company->website)
                                    <a href="{{ $company->website }}" target="_blank" rel="noopener noreferrer">{{ $company->website }}</a>
                                @else — @endif
                            </dd>

                            <dt class="col-sm-4 text-muted">Country</dt>
                            <dd class="col-sm-8">{{ $company->country ?? '—' }}</dd>

                            <dt class="col-sm-4 text-muted">Address</dt>
                            <dd class="col-sm-8">{{ $company->address ?? '—' }}</dd>
                        </dl>
                    </div>
                </div>

            </div>

            <div class="col-lg-4">

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><i class="fas fa-clipboard-check text-success me-2"></i>Reporting</h5>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-6 text-muted">Fiscal year start</dt>
                            <dd class="col-sm-6">{{ $company->fiscal_year_start ?? '—' }}</dd>

                            <dt class="col-sm-6 text-muted">Timezone</dt>
                            <dd class="col-sm-6">{{ $company->timezone ?? '—' }}</dd>

                            <dt class="col-sm-6 text-muted">Scopes enabled</dt>
                            <dd class="col-sm-6">
                                @forelse($company->scopes_enabled ?? [] as $scope)
                                    <span class="badge bg-light text-dark border me-1">Scope {{ $scope }}</span>
                                @empty
                                    —
                                @endforelse
                            </dd>

                            <dt class="col-sm-6 text-muted">Standards</dt>
                            <dd class="col-sm-6">
                                @forelse($company->reporting_standards ?? [] as $standard)
                                    <span class="badge bg-light text-dark border me-1 mb-1">{{ $standard }}</span>
                                @empty
                                    —
                                @endforelse
                            </dd>
                        </dl>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><i class="fas fa-file-contract text-success me-2"></i>Registration</h5>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-6 text-muted">Tax ID</dt>
                            <dd class="col-sm-6">{{ $company->tax_id ?? '—' }}</dd>

                            <dt class="col-sm-6 text-muted">Registration no.</dt>
                            <dd class="col-sm-6">{{ $company->registration_number ?? '—' }}</dd>

                            <dt class="col-sm-6 text-muted">Created</dt>
                            <dd class="col-sm-6">{{ $company->created_at?->format('d M Y') ?? '—' }}</dd>

                            <dt class="col-sm-6 text-muted">Updated</dt>
                            <dd class="col-sm-6">{{ $company->updated_at?->format('d M Y') ?? '—' }}</dd>
                        </dl>
                    </div>
                </div>

                @if($company->notes)
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0"><i class="fas fa-sticky-note text-success me-2"></i>Notes</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-0 small">{{ $company->notes }}</p>
                        </div>
                    </div>
                @endif

            </div>
        </div>

    </div>
</div>
@endsection

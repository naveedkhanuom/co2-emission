@extends('layouts.app')

@section('title', 'Scope 3 Calculator')
@section('page-title', 'Scope 3 Calculator')

@push('styles')
<style>
@include('scope3.calculator-css')
</style>
@endpush

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="container mt-4 mb-4">
        <div class="mb-4">
            <h1 class="mb-1">Scope 3 Calculator</h1>
            <p class="text-muted mb-2">Estimate Scope 3 emissions in 3 simple steps:</p>
            <ol class="scope3-steps-list mb-0">
                <li><strong>Set once</strong> — Choose date and facility in the green box below (we’ll remember them).</li>
                <li><strong>Pick a category</strong> — Click a category on the left, then add your data in the rows.</li>
                <li><strong>Save &amp; Calculate</strong> — One click saves that category to the database. No need to save everything at the end.</li>
            </ol>
        </div>

        <div class="row g-4">
            <!-- Category navigation (matches sidebar style) -->
            <div class="col-lg-3 col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 text-primary">
                            <i class="fas fa-layer-group me-2"></i>Categories
                        </h6>
                    </div>
                    <div class="card-body p-2">
                        <nav class="scope3-cat-nav" id="sidebarNav" aria-label="Scope 3 categories"></nav>
                        <hr class="my-2">
                        <button type="button" class="btn btn-success w-100 mt-2" onclick="scope3ShowSummary()">
                            <i class="fas fa-chart-bar me-2"></i>View Summary & Totals
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main content area -->
            <div class="col-lg-9 col-md-8">
                <!-- Set once: date & facility (remembered for next time) -->
                <div class="card shadow-sm border-0 mb-3 scope3-save-options-card" id="scope3SaveOptionsCard">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="scope3-step-badge">1</span>
                            <span class="fw-semibold text-success">Set once — then save any category</span>
                        </div>
                        <p class="small text-muted mb-2">Your choices here are used whenever you click Save &amp; Calculate. They’re saved for your next visit.</p>
                        <div class="row g-3 align-items-end flex-wrap">
                            <div class="col-auto">
                                <label class="form-label small mb-0">Entry date</label>
                                <input type="date" class="form-control form-control-sm" id="scope3SaveEntryDate" value="{{ date('Y-m-d') }}" title="Date for the emission record">
                            </div>
                            <div class="col-auto">
                                <label class="form-label small mb-0">Facility <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" id="scope3SaveFacility" required title="Required to save to database">
                                    <option value="">Choose facility...</option>
                                    @foreach(($facilities ?? collect()) as $f)
                                        <option value="{{ $f->name ?? '' }}">{{ $f->name ?? '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-auto">
                                <label class="form-label small mb-0">Site <span class="text-muted">(optional)</span></label>
                                <select class="form-select form-select-sm" id="scope3SaveSite" title="Optional">
                                    <option value="">—</option>
                                    @foreach(($sites ?? collect()) as $s)
                                        <option value="{{ $s->id ?? '' }}">{{ $s->name ?? '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col flex-grow-1 text-end align-self-center">
                                <span class="small" id="scope3SaveCategoryStatus">Ready. Click Save &amp; Calculate on a category to save.</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="mainContent"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.scope3StoreUrl = @json(route('emission-records.store'));
window.scope3CategoryMap = @json($categoryMap ?? []);
window.scope3Facilities = @json($facilities ?? []);
window.scope3Sites = @json($sites ?? []);
window.scope3CsrfToken = @json(csrf_token());
</script>
<script>
@include('scope3.calculator-js')
</script>
@endpush

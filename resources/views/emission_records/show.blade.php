@extends('layouts.app')

@section('title', 'Emission Record')
@section('page-title', 'Emission Record')

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="container-fluid py-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
            <div>
                <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm mb-2">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
                <h1 class="h4 mb-1">Emission Record #{{ $emissionRecord->id }}</h1>
                <p class="text-muted small mb-0">Scope {{ $emissionRecord->scope }} · {{ $emissionRecord->emission_source }}</p>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><i class="fas fa-leaf text-success me-2"></i>Details</h5>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4 text-muted">Emission source</dt>
                            <dd class="col-sm-8">{{ $emissionRecord->emission_source }}</dd>

                            <dt class="col-sm-4 text-muted">Scope</dt>
                            <dd class="col-sm-8">Scope {{ $emissionRecord->scope }}</dd>

                            <dt class="col-sm-4 text-muted">Facility</dt>
                            <dd class="col-sm-8">{{ $emissionRecord->facility ?? '—' }}</dd>

                            <dt class="col-sm-4 text-muted">Entry date</dt>
                            <dd class="col-sm-8">{{ $emissionRecord->entry_date ? $emissionRecord->entry_date->format('d M Y') : '—' }}</dd>

                            <dt class="col-sm-4 text-muted">Activity data</dt>
                            <dd class="col-sm-8">{{ $emissionRecord->activity_data !== null ? number_format((float) $emissionRecord->activity_data, 4) : '—' }}</dd>

                            <dt class="col-sm-4 text-muted">CO2e (tCO₂e)</dt>
                            <dd class="col-sm-8"><strong class="text-success">{{ $emissionRecord->co2e_value !== null ? number_format((float) $emissionRecord->co2e_value, 4) : '—' }}</strong></dd>

                            @if($emissionRecord->department)
                            <dt class="col-sm-4 text-muted">Department</dt>
                            <dd class="col-sm-8">{{ $emissionRecord->department }}</dd>
                            @endif

                            <dt class="col-sm-4 text-muted">Data source</dt>
                            <dd class="col-sm-8">{{ $emissionRecord->data_source ?? '—' }}</dd>

                            <dt class="col-sm-4 text-muted">Status</dt>
                            <dd class="col-sm-8">
                                <span class="badge {{ $emissionRecord->status === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ $emissionRecord->status ?? 'draft' }}</span>
                            </dd>

                            @if($emissionRecord->notes)
                            <dt class="col-sm-4 text-muted">Notes</dt>
                            <dd class="col-sm-8">{{ nl2br(e($emissionRecord->notes)) }}</dd>
                            @endif
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><i class="fas fa-paperclip text-primary me-2"></i>Attachments</h5>
                    </div>
                    <div class="card-body">
                        @php
                            $docs = $emissionRecord->supporting_documents ?? [];
                            $docs = is_array($docs) ? $docs : [];
                        @endphp
                        @if(count($docs) > 0)
                            <ul class="list-group list-group-flush">
                                @foreach(array_values($docs) as $i => $path)
                                    @php $name = basename($path); $url = route('emission_records.document', ['emissionRecord' => $emissionRecord->id, 'index' => $i]); @endphp
                                    <li class="list-group-item px-0 d-flex align-items-center">
                                        <i class="fas fa-file me-2 text-muted"></i>
                                        <a href="{{ $url }}" target="_blank" rel="noopener" class="flex-grow-1 text-break">{{ $name }}</a>
                                        <a href="{{ $url }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary" title="Open in new tab"><i class="fas fa-external-link-alt"></i></a>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted mb-0 small">No attachments for this record.</p>
                        @endif
                    </div>
                </div>

                @if($emissionRecord->company)
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><i class="fas fa-building text-muted me-2"></i>Company</h5>
                    </div>
                    <div class="card-body py-2">
                        <p class="mb-0">{{ $emissionRecord->company->name ?? '—' }}</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

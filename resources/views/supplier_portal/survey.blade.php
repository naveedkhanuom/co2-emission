@extends('layouts.app')

@section('title', 'Supplier Survey')
@section('page-title', 'Supplier Survey')

@section('content')
<div id="content">
    <div class="container mt-5" style="max-width: 900px;">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h4 class="mb-1">{{ $survey->title ?? 'Supplier Survey' }}</h4>
                <div class="text-muted">
                    Supplier: <strong>{{ $survey->supplier?->name ?? 'N/A' }}</strong>
                    @if($survey->due_date)
                        · Due: <strong>{{ $survey->due_date->format('Y-m-d') }}</strong>
                    @endif
                </div>
            </div>

            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') }}
                        <button class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show">
                        {{ session('error') }}
                        <button class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(!empty($survey->description))
                    <p class="text-muted">{{ $survey->description }}</p>
                @endif

                <form method="POST" action="{{ route('supplier_portal.survey.submit', $survey->public_token) }}">
                    @csrf

                    @php
                        $questions = $survey->questions ?? [];
                        $responses = $survey->responses ?? [];
                    @endphp

                    @forelse($questions as $index => $q)
                        @php
                            $qText = is_array($q) ? ($q['question'] ?? ('Question ' . ($index + 1))) : $q;
                            $qType = is_array($q) ? ($q['type'] ?? 'text') : 'text';
                            $value = $responses[$index] ?? '';
                        @endphp

                        <div class="mb-4">
                            <label class="form-label fw-semibold">{{ $index + 1 }}. {{ $qText }}</label>

                            @if($qType === 'number')
                                <input type="number" step="any" class="form-control" name="responses[{{ $index }}]" value="{{ $value }}">
                            @elseif($qType === 'date')
                                <input type="date" class="form-control" name="responses[{{ $index }}]" value="{{ $value }}">
                            @elseif($qType === 'yes_no')
                                <select class="form-select" name="responses[{{ $index }}]">
                                    <option value="" {{ $value === '' ? 'selected' : '' }}>Select</option>
                                    <option value="yes" {{ $value === 'yes' ? 'selected' : '' }}>Yes</option>
                                    <option value="no" {{ $value === 'no' ? 'selected' : '' }}>No</option>
                                </select>
                            @else
                                <input type="text" class="form-control" name="responses[{{ $index }}]" value="{{ $value }}">
                            @endif

                            <small class="text-muted">You can save and return later using the same link.</small>
                        </div>
                    @empty
                        <div class="alert alert-warning">
                            No questions configured for this survey.
                        </div>
                    @endforelse

                    <div class="d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-1"></i> Submit / Save
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="text-center text-muted mt-3">
            If you have questions, contact your company representative.
        </div>
    </div>
</div>
@endsection


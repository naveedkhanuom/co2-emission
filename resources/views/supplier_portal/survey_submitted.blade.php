@extends('layouts.portal')

@section('title', 'Survey Submitted')

@section('content')
<div id="content">
    <div class="container my-5" style="max-width: 700px;">
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <div class="mb-3">
                    <i class="fas fa-circle-check fa-3x text-success"></i>
                </div>
                <h4 class="mb-2">Thank you — your survey has been submitted</h4>
                <p class="text-muted mb-1">
                    {{ $survey->supplier?->name ? $survey->supplier->name . ', your' : 'Your' }} response to
                    <strong>{{ $survey->title ?: 'the emissions survey' }}</strong> was received successfully.
                </p>
                @if($survey->completed_at)
                    <p class="text-muted small mb-0">
                        Submitted on {{ $survey->completed_at->format('F j, Y \a\t H:i') }}.
                    </p>
                @endif
                <hr class="my-4">
                <p class="text-muted small mb-0">
                    This was a one-time link and has now been used. If you need to update your response,
                    please contact {{ $survey->company?->name ?? 'the company' }} for a new link.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

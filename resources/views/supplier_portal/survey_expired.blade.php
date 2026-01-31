@extends('layouts.app')

@section('title', 'Survey Link Expired')
@section('page-title', 'Survey Link Expired')

@section('content')
<div id="content">
    <div class="container mt-5" style="max-width: 700px;">
        <div class="card shadow-sm">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning"></i>
                </div>
                <h4 class="mb-2">This survey link has expired</h4>
                <p class="text-muted mb-0">
                    Please contact the company administrator to request a new link.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection


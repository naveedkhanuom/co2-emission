@extends('layouts.app')

@section('content')
<div class="container-fluid py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card border-0 shadow-sm text-center py-5 px-4">
                <div class="card-body">
                    <div class="mb-4">
                        <i class="fas fa-lock text-warning" style="font-size: 4rem;"></i>
                    </div>
                    <h2 class="h4 text-dark mb-3">You don't have permission</h2>
                    <p class="text-muted mb-4">
                        This feature is not available in demo mode. Contact your administrator for full access.
                    </p>
                    <a href="{{ route('home') }}" class="btn btn-primary">
                        <i class="fas fa-tachometer-alt me-2"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

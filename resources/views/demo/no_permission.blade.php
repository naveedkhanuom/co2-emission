@extends('layouts.app')

@section('content')
<div class="no-permission-page">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-11 col-sm-10 col-md-8 col-lg-6 col-xl-5">
            <div class="card border-0 no-permission-card shadow">
                <div class="card-body text-center p-4 p-md-5">
                    <div class="no-permission-icon-wrap mb-4">
                        <div class="no-permission-icon-bg">
                            <i class="fas fa-lock no-permission-icon"></i>
                        </div>
                    </div>
                    <h1 class="h4 fw-semibold text-dark mb-2 no-permission-title">
                        Access restricted
                    </h1>
                    <p class="text-muted mb-4 no-permission-desc">
                        This feature is not available for demo accounts. You can see it in the menu but cannot access it. Contact your administrator for full access.
                    </p>
                    <a href="{{ route('home') }}" class="btn no-permission-btn btn-primary px-4 py-2 rounded-pill">
                        <i class="fas fa-tachometer-alt me-2"></i> Back to Dashboard
                    </a>
                </div>
            </div>
            </div>
        </div>
    </div>
</div>

<style>
.no-permission-page {
    background: linear-gradient(160deg, var(--gray-50, #f8f9fa) 0%, var(--gray-100, #f1f3f4) 50%, rgba(46, 125, 50, 0.06) 100%);
    min-height: calc(100vh - 120px);
    display: flex;
    align-items: center;
    justify-content: center;
}
.no-permission-card {
    border-radius: 20px;
    overflow: hidden;
    background: #fff;
}
.no-permission-icon-wrap {
    display: flex;
    justify-content: center;
}
.no-permission-icon-bg {
    width: 88px;
    height: 88px;
    border-radius: 50%;
    background: linear-gradient(145deg, #fff8e6 0%, #ffecb3 100%);
    border: 2px solid rgba(245, 124, 0, 0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 14px rgba(245, 124, 0, 0.2);
}
.no-permission-icon {
    font-size: 2.25rem;
    color: var(--warning-orange, #f57c00);
}
.no-permission-title {
    letter-spacing: -0.02em;
    color: var(--gray-800, #3c4043);
}
.no-permission-desc {
    font-size: 0.95rem;
    line-height: 1.6;
    max-width: 420px;
    margin-left: auto;
    margin-right: auto;
}
.no-permission-btn {
    background: linear-gradient(135deg, var(--primary-green, #2e7d32) 0%, var(--dark-green, #1b5e20) 100%);
    border: none;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(46, 125, 50, 0.35);
    transition: transform 0.2s, box-shadow 0.2s;
}
.no-permission-btn:hover {
    background: linear-gradient(135deg, var(--light-green, #4caf50) 0%, var(--primary-green, #2e7d32) 100%);
    border: none;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(46, 125, 50, 0.4);
}
</style>
@endsection

@extends('layouts.app')

@section('content')
    <div id="content">
        @include('layouts.top-nav')
        
        <div class="container-fluid mt-4">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-md-10">
                    <div class="card shadow-sm border-0" style="border-radius: 15px;">
                        <div class="card-body text-center p-5">
                            <div class="mb-4">
                                <i class="fas fa-database" style="font-size: 80px; color: var(--primary-green); opacity: 0.3;"></i>
                            </div>
                            
                            <h2 class="mb-3" style="color: var(--gray-800); font-weight: 600;">Data Source</h2>
                            
                            <div class="mb-4">
                                <span class="badge bg-secondary px-3 py-2" style="font-size: 1rem;">
                                    <i class="fas fa-clock me-2"></i>Coming Soon
                                </span>
                            </div>
                            
                            <p class="text-muted mb-4" style="font-size: 1.1rem; line-height: 1.8;">
                                We're working on integrating real-time GHG gas monitoring devices. 
                                This feature will allow you to connect and monitor emissions data directly from your monitoring equipment.
                            </p>
                            
                            <div class="row mt-5">
                                <div class="col-md-4 mb-3">
                                    <div class="p-3">
                                        <i class="fas fa-satellite-dish fa-2x mb-3" style="color: var(--primary-blue);"></i>
                                        <h5 class="mb-2">Device Integration</h5>
                                        <p class="text-muted small">Connect monitoring devices for real-time data collection</p>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <div class="p-3">
                                        <i class="fas fa-chart-line fa-2x mb-3" style="color: var(--primary-green);"></i>
                                        <h5 class="mb-2">Real-Time Monitoring</h5>
                                        <p class="text-muted small">Track emissions continuously with live data feeds</p>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <div class="p-3">
                                        <i class="fas fa-bell fa-2x mb-3" style="color: var(--warning-orange);"></i>
                                        <h5 class="mb-2">Automated Alerts</h5>
                                        <p class="text-muted small">Get notified when emissions exceed thresholds</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-5 pt-4 border-top">
                                <p class="text-muted mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    For now, please use <a href="{{ route('emission_records.index') }}" class="text-decoration-none">Manual Entry</a> or 
                                    <a href="{{ route('emissions.import.form') }}" class="text-decoration-none">Import Data</a> to record your emissions.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

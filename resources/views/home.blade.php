@extends('layouts.app')

@section('title', 'GHG Dashboard')
@section('page-title', 'Dashboard')

@push('styles')
    <style>
        /* Metric Card Borders */
        .metric-card {
            border-left-width: 6px !important;
            border-top: 1px solid #dee2e6;
            border-right: 1px solid #dee2e6;
            border-bottom: 1px solid #dee2e6;
        }
    </style>
@endpush

@section('content')
    <section id="dashboard">
        <div class="container-fluid">
            <!-- Top Row: Left Doughnut + Right Metrics -->
            <div class="row g-4 align-items-stretch">

                <!-- Left Card: Total Emissions -->
                <div class="col-lg-6 d-flex">
                    <div class="card shadow-sm rounded-xl flex-fill">
                        <div class="card-body d-flex flex-column p-4">
                            <h3 class="fw-semibold text-dark mb-4 fs-5">Total GHG Emissions (Kg CO₂e)</h3>
                            <div class="d-flex justify-content-center align-items-center flex-grow-1">
                                <canvas id="totalEmissionsDoughnut" class="w-100" style="max-height:250px;"></canvas>
                            </div>
                            <div class="text-center border-top pt-3 mt-3">
                                <p class="fw-bold mb-0 fs-2">40.27 MT</p>
                                <p class="text-muted mt-1 small">Total CO₂e YTD - Last 12 Months</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Cards: Metrics -->
                <div class="col-lg-6 d-flex flex-column">
                    <div class="row g-3 flex-fill">

                        <!-- Scope 1 -->
                        <div class="col-sm-6 d-flex">
                            <div class="card shadow-sm rounded-xl flex-fill metric-card" style="border-left-color:#dc3545;">
                                <div class="card-body d-flex flex-column justify-content-between p-3">
                                    <div>
                                        <p class="text-muted mb-1 small">Scope 1 (Direct)</p>
                                        <p class="fw-bold mb-2 fs-4">23.83 MT</p>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center text-muted small">
                                        <span>59.2% of Total</span>
                                        <span class="text-danger d-flex align-items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" class="me-1" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7 7 7M12 3v18"/>
                                        </svg>
                                        +1.2% MoM
                                    </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Scope 2 -->
                        <div class="col-sm-6 d-flex">
                            <div class="card shadow-sm rounded-xl flex-fill metric-card" style="border-left-color:#0d6efd;">
                                <div class="card-body d-flex flex-column justify-content-between p-3">
                                    <div>
                                        <p class="text-muted mb-1 small">Scope 2 (Energy)</p>
                                        <p class="fw-bold mb-2 fs-4">9.94 MT</p>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center text-muted small">
                                        <span>24.7% of Total</span>
                                        <span class="text-primary d-flex align-items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" class="me-1" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7 7 7M12 3v18"/>
                                        </svg>
                                        +0.8% MoM
                                    </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Scope 3 -->
                        <div class="col-sm-6 d-flex">
                            <div class="card shadow-sm rounded-xl flex-fill metric-card" style="border-left-color:#198754;">
                                <div class="card-body d-flex flex-column justify-content-between p-3">
                                    <div>
                                        <p class="text-muted mb-1 small">Scope 3 (Indirect)</p>
                                        <p class="fw-bold mb-2 fs-4">6.50 MT</p>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center text-muted small">
                                        <span>16.1% of Total</span>
                                        <span class="text-success d-flex align-items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" class="me-1" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7-7-7M12 21V3"/>
                                        </svg>
                                        -0.5% MoM
                                    </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Renewable Energy Savings -->
                        <div class="col-sm-6 d-flex">
                            <div class="card shadow-sm rounded-xl flex-fill metric-card" style="border-left-color:#8b5cf6;">
                                <div class="card-body d-flex flex-column justify-content-between p-3">
                                    <div>
                                        <p class="text-muted mb-1 small">Renewable Energy Savings</p>
                                        <p class="fw-bold mb-2 fs-4">5.00 MT</p>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center text-muted small">
                                        <span>12.4% of Total</span>
                                        <span class="d-flex align-items-center" style="color:#8b5cf6;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" class="me-1" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7 7 7M12 3v18"/>
                                        </svg>
                                        +2.0% MoM
                                    </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

            <!-- Charts Row -->
            <div class="row g-4 mt-3">
                <div class="col-12 col-lg-6">
                    <div class="dashboard-card p-4 h-100">
                        <h3 class="h6 mb-3">Monthly CO₂e Emissions Trend</h3>
                        <canvas id="emissionsTrendChart" style="max-height:320px; width:100%;"></canvas>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="dashboard-card p-4 h-100">
                        <h3 class="h6 mb-3">Emission Source Distribution</h3>
                        <canvas id="sourceDistributionChart" style="max-height:320px; width:100%;"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        const chartTextColor = '#334155';
        const gridColor = '#e6eef8';

        // Doughnut: Total Emissions
        new Chart(document.getElementById('totalEmissionsDoughnut'), {
            type: 'doughnut',
            data: {
                labels: ['Scope 1', 'Scope 2', 'Scope 3'],
                datasets: [{ data: [23.83, 9.94, 6.50], backgroundColor: ['#ef4444','#3b82f6','#10b981'] }]
            },
            options: {
                plugins: { legend: { position: 'bottom', labels: { color: chartTextColor } } },
                maintainAspectRatio: false
            }
        });

        // Line: Monthly trend
        new Chart(document.getElementById('emissionsTrendChart'), {
            type: 'line',
            data: {
                labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
                datasets: [{
                    label: 'Total CO₂e (MT)',
                    data: [3.1,3.5,3.8,3.6,3.9,4.0,3.8,3.7,4.1,4.2,4.3,4.4],
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59,130,246,0.18)',
                    fill: true,
                    tension: 0.3,
                }]
            },
            options: {
                plugins: { legend: { labels: { color: chartTextColor } } },
                scales: {
                    x: { ticks: { color: chartTextColor }, grid: { color: gridColor } },
                    y: { ticks: { color: chartTextColor }, grid: { color: gridColor } }
                },
                maintainAspectRatio: false
            }
        });

        // Pie: Source Distribution
        new Chart(document.getElementById('sourceDistributionChart'), {
            type: 'pie',
            data: {
                labels: ['Transportation','Energy','Waste','Materials'],
                datasets: [{ data: [40,30,20,10], backgroundColor: ['#ef4444','#3b82f6','#10b981','#8b5cf6'] }]
            },
            options: {
                plugins: { legend: { position: 'bottom', labels: { color: chartTextColor } } },
                maintainAspectRatio: false
            }
        });
    </script>
@endpush

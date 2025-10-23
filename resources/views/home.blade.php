@extends('layouts.app')

@section('title', 'GHG Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    <section id="dashboard" class="view-content">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Emissions -->
            <div class="lg:col-span-2 dashboard-card rounded-xl p-6 flex flex-col h-full">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Total GHG Emissions (Kg CO₂e)</h3>
                <div class="flex-grow w-full flex justify-center items-center">
                    <canvas id="totalEmissionsDoughnut" class="max-h-64"></canvas>
                </div>
                <div class="mt-6 text-center border-t border-gray-200 pt-4">
                    <p class="text-4xl font-extrabold text-gray-800">40.27 MT</p>
                    <p class="text-sm text-gray-500 mt-1">Total CO₂e YTD - Last 12 Months</p>
                </div>
            </div>

            <!-- Metric Cards -->
            <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Metric Cards -->
                <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <!-- Scope 1 -->
                    <div class="dashboard-card rounded-xl p-5 border-l-4 border-red-500">
                        <p class="text-sm font-medium text-gray-600">Scope 1 (Direct)</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">23.83 MT</p>
                        <div class="flex justify-between items-center text-xs mt-2 text-gray-500">
                            <span>59.2% of Total</span>
                            <span class="flex items-center text-red-500">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                </svg>+1.2% MoM
            </span>
                        </div>
                    </div>

                    <!-- Scope 2 -->
                    <div class="dashboard-card rounded-xl p-5 border-l-4 border-blue-500">
                        <p class="text-sm font-medium text-gray-600">Scope 2 (Energy)</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">9.94 MT</p>
                        <div class="flex justify-between items-center text-xs mt-2 text-gray-500">
                            <span>24.7% of Total</span>
                            <span class="flex items-center text-blue-500">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                </svg>+0.8% MoM
            </span>
                        </div>
                    </div>

                    <!-- Scope 3 -->
                    <div class="dashboard-card rounded-xl p-5 border-l-4 border-green-500">
                        <p class="text-sm font-medium text-gray-600">Scope 3 (Indirect)</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">6.50 MT</p>
                        <div class="flex justify-between items-center text-xs mt-2 text-gray-500">
                            <span>16.1% of Total</span>
                            <span class="flex items-center text-green-500">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                </svg>-0.5% MoM
            </span>
                        </div>
                    </div>

                    <!-- Renewable Energy Savings -->
                    <div class="dashboard-card rounded-xl p-5 border-l-4 border-purple-500">
                        <p class="text-sm font-medium text-gray-600">Renewable Energy Savings</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">5.00 MT</p>
                        <div class="flex justify-between items-center text-xs mt-2 text-gray-500">
                            <span>12.4% of Total</span>
                            <span class="flex items-center text-purple-500">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                </svg>+2.0% MoM
            </span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="dashboard-card rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Monthly CO₂e Emissions Trend</h3>
                <canvas id="emissionsTrendChart" class="max-h-64"></canvas>
            </div>
            <div class="dashboard-card rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Emission Source Distribution</h3>
                <canvas id="sourceDistributionChart" class="max-h-64"></canvas>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        const chartTextColor = '#334155';
        const gridColor = '#e2e8f0';

        new Chart(document.getElementById('totalEmissionsDoughnut'), {
            type: 'doughnut',
            data: { labels: ['Scope 1', 'Scope 2', 'Scope 3'], datasets: [{ data: [23.83, 9.94, 6.50], backgroundColor: ['#ef4444','#3b82f6','#10b981'] }] },
            options: { plugins: { legend: { position: 'bottom', labels: { color: chartTextColor } } } }
        });

        new Chart(document.getElementById('emissionsTrendChart'), {
            type: 'line',
            data: {
                labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
                datasets: [{ label: 'Total CO₂e (MT)', data: [3.1,3.5,3.8,3.6,3.9,4.0,3.8,3.7,4.1,4.2,4.3,4.4], borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.2)', fill: true, tension: 0.3 }]
            },
            options: { plugins: { legend: { labels: { color: chartTextColor } } }, scales: { x: { ticks: { color: chartTextColor }, grid: { color: gridColor } }, y: { ticks: { color: chartTextColor }, grid: { color: gridColor } } } }
        });

        new Chart(document.getElementById('sourceDistributionChart'), {
            type: 'pie',
            data: { labels: ['Transportation','Energy','Waste','Materials'], datasets: [{ data: [40,30,20,10], backgroundColor: ['#ef4444','#3b82f6','#10b981','#8b5cf6'] }] },
            options: { plugins: { legend: { position: 'bottom', labels: { color: chartTextColor } } } }
        });
    </script>
@endpush

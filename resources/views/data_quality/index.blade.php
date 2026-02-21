@extends('layouts.app')

@section('title', 'Data Quality')
@section('page-title', 'Data Quality')

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="data-quality-app container-fluid mt-4">
        @if(session('error'))
            <div class="alert alert-warning alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Topbar -->
        <div class="topbar">
            <h2><span class="sb"><i class="fas fa-chart-pie"></i></span> Data Quality</h2>
            <p>Overall quality score, breakdown by type, and supplier-level metrics for {{ now()->year }}.</p>
        </div>

        <!-- Stats -->
        <div class="stats row g-3 mb-4">
            <div class="col-md-4">
                <div class="sc">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <span class="sl">Overall Quality Score</span>
                            <div class="score-circle {{ $overallScore >= 70 ? 'score-high' : ($overallScore >= 40 ? 'score-medium' : 'score-low') }}">
                                {{ number_format($overallScore, 1) }}
                            </div>
                            <span class="sl small">/ 100</span>
                        </div>
                        <div class="si g"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="sc">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <span class="sl">Total Records ({{ now()->year }})</span>
                            <div class="sv">{{ number_format($totalRecords) }}</div>
                            <span class="sl small">emission records</span>
                        </div>
                        <div class="si b"><i class="fas fa-database"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="sc">
                    <span class="sl d-block mb-3">Quality Breakdown</span>
                    @forelse($qualityBreakdown as $row)
                        @php
                            $label = $row->data_quality ?: 'not set';
                            $class = match($label) {
                                'primary' => 'quality-primary',
                                'secondary' => 'quality-secondary',
                                'estimated' => 'quality-estimated',
                                default => 'quality-estimated'
                            };
                        @endphp
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="quality-badge {{ $class }}">{{ ucfirst($label) }}</span>
                            <span class="sl"><strong>{{ number_format($row->count) }}</strong> records · {{ number_format((float) $row->total, 2) }} tCO2e</span>
                        </div>
                    @empty
                        <p class="sl small mb-0">No data for current year.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Scope 3 Quality & Trend -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card tw">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-layer-group me-2"></i>Scope 3 Data Quality</h5>
                    </div>
                    <div class="card-body">
                        @if($scope3Quality->isNotEmpty())
                            <ul class="list-group list-group-flush scope3-list">
                                @foreach($scope3Quality as $row)
                                    @php
                                        $label = $row->data_quality ?: 'not set';
                                        $class = in_array($label, ['primary','secondary','estimated']) ? 'quality-' . $label : 'quality-estimated';
                                    @endphp
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span class="quality-badge {{ $class }}">{{ ucfirst($label) }}</span>
                                        <span>{{ number_format($row->count) }} records · {{ number_format((float) $row->total, 2) }} tCO2e</span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="sl mb-0">No Scope 3 records for current year.</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card tw">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Quality Trend (Last 12 Months)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="qualityTrendChart" height="220"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Supplier Data Quality -->
        <div class="card suppliers-quality-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-truck me-2"></i>Supplier Data Quality Scores</h5>
            </div>
            <div class="card-body {{ $suppliers->isNotEmpty() ? 'p-0' : '' }}">
                @if($suppliers->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 suppliers-quality-table">
                            <thead>
                                <tr>
                                    <th>Supplier</th>
                                    <th>Quality Score</th>
                                    <th>Records</th>
                                    <th>Total Emissions ({{ now()->year }})</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($suppliers as $supplier)
                                    <tr>
                                        <td>{{ $supplier->name }}</td>
                                        <td>
                                            @php
                                                $score = $supplier->quality_score ?? 0;
                                                $scoreClass = $score >= 70 ? 'score-high' : ($score >= 40 ? 'score-medium' : 'score-low');
                                            @endphp
                                            <span class="score-circle score-sm {{ $scoreClass }}">{{ number_format($score, 0) }}</span>
                                        </td>
                                        <td>{{ $supplier->emission_records_count ?? 0 }}</td>
                                        <td>{{ number_format($supplier->total_emissions ?? 0, 2) }} tCO2e</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="sl mb-0">No suppliers with emission data.</p>
                @endif
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.data-quality-app * { box-sizing: border-box; }
.data-quality-app { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
/* Topbar */
.data-quality-app .topbar { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; padding: 20px 24px; background: linear-gradient(135deg, #fff 0%, var(--gray-50) 100%); border: 1px solid var(--gray-200); border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.data-quality-app .topbar h2 { font-size: 1.35rem; font-weight: 700; letter-spacing: -0.02em; display: flex; align-items: center; gap: 10px; margin: 0; color: var(--gray-800); }
.data-quality-app .topbar h2 .sb { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%); color: #fff; font-size: 1rem; font-weight: 700; box-shadow: 0 2px 8px rgba(46,125,50,.25); }
.data-quality-app .topbar p { color: var(--gray-600); font-size: 0.875rem; flex: 1; min-width: 180px; margin: 0; line-height: 1.4; }
/* Alerts */
.data-quality-app .alert { border-radius: 12px; border: 1px solid transparent; }
.data-quality-app .alert-success { background: rgba(76,175,80,0.1); border-color: rgba(76,175,50,0.25); color: var(--dark-green); }
.data-quality-app .alert-warning { background: rgba(255,152,0,0.1); border-color: rgba(255,152,0,0.25); color: var(--warning-orange); }
.data-quality-app .alert-danger { background: rgba(211,47,47,0.08); border-color: rgba(211,47,47,0.2); color: var(--danger-red); }
/* Stats */
.data-quality-app .stats .sc { background: #fff; border: 1px solid var(--gray-200); border-radius: 14px; padding: 20px; height: 100%; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.data-quality-app .stats .sl { color: var(--gray-600); font-size: 0.875rem; display: block; }
.data-quality-app .stats .sv { font-size: 1.75rem; font-weight: 700; color: var(--gray-800); margin: 4px 0 2px; }
.data-quality-app .stats .si { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0; }
.data-quality-app .stats .si.g { background: rgba(46,125,50,0.12); color: var(--primary-green); }
.data-quality-app .stats .si.b { background: rgba(3,169,244,0.12); color: var(--primary-blue); }
.data-quality-app .stats .si.w { background: rgba(255,152,0,0.12); color: var(--warning-orange); }
/* Quality badges & score circles */
.data-quality-app .quality-badge { padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
.data-quality-app .quality-primary { background: rgba(46,125,50,0.15); color: var(--primary-green); }
.data-quality-app .quality-secondary { background: rgba(3,169,244,0.15); color: var(--light-blue); }
.data-quality-app .quality-estimated { background: rgba(158,158,158,0.2); color: var(--gray-600); }
.data-quality-app .score-circle { border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; margin: 0; }
.data-quality-app .score-circle:not(.score-sm) { width: 100px; height: 100px; font-size: 1.75rem; }
.data-quality-app .score-circle.score-sm { width: 50px; height: 50px; font-size: 1rem; }
.data-quality-app .score-high { background: rgba(46,125,50,0.15); color: var(--primary-green); }
.data-quality-app .score-medium { background: rgba(255,152,0,0.15); color: var(--warning-orange); }
.data-quality-app .score-low { background: rgba(211,47,47,0.15); color: var(--danger-red); }
/* Cards */
.data-quality-app .card.tw { background: #fff; border: 1px solid var(--gray-200); border-radius: 16px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.data-quality-app .card.tw .card-header { padding: 16px 20px; border-bottom: 1px solid var(--gray-200); background: linear-gradient(180deg, var(--gray-50) 0%, #fff 100%); }
.data-quality-app .card.tw .card-header h5 { font-size: 1.0625rem; font-weight: 700; color: var(--gray-800); }
.data-quality-app .card.tw .card-header i { color: var(--primary-green); }
.data-quality-app .card.tw .card-body { padding: 20px; }
.data-quality-app .scope3-list .list-group-item { border-color: var(--gray-100); padding: 12px 0; }
/* Supplier table card */
.data-quality-app .suppliers-quality-card { background: #fff; border: 1px solid var(--gray-200); border-radius: 16px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.data-quality-app .suppliers-quality-card .card-header { padding: 16px 20px; border-bottom: 1px solid var(--gray-200); background: linear-gradient(180deg, var(--gray-50) 0%, #fff 100%); }
.data-quality-app .suppliers-quality-card .card-header h5 { font-size: 1.0625rem; font-weight: 700; color: var(--gray-800); }
.data-quality-app .suppliers-quality-card .card-header i { color: var(--primary-green); }
.data-quality-app .suppliers-quality-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.data-quality-app .suppliers-quality-table thead th { background: var(--gray-100); color: var(--gray-600); font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; padding: 14px 16px; border: none; border-bottom: 1px solid var(--gray-200); }
.data-quality-app .suppliers-quality-table thead th:first-child { padding-left: 20px; }
.data-quality-app .suppliers-quality-table tbody td { padding: 14px 16px; font-size: 0.875rem; color: var(--gray-800); border: none; border-bottom: 1px solid var(--gray-100); vertical-align: middle; }
.data-quality-app .suppliers-quality-table tbody td:first-child { padding-left: 20px; }
.data-quality-app .suppliers-quality-table tbody tr:hover td { background: var(--gray-50); }
.data-quality-app .suppliers-quality-table tbody tr:last-child td { border-bottom: none; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('qualityTrendChart');
    if (ctx) {
        var trendData = @json($trends);
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: trendData.map(function(t) { return t.month; }),
                datasets: [
                    { label: 'Primary', data: trendData.map(function(t) { return t.primary; }), backgroundColor: 'rgba(46, 125, 50, 0.7)' },
                    { label: 'Secondary', data: trendData.map(function(t) { return t.secondary; }), backgroundColor: 'rgba(3, 169, 244, 0.7)' },
                    { label: 'Estimated', data: trendData.map(function(t) { return t.estimated; }), backgroundColor: 'rgba(158, 158, 158, 0.6)' }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { stacked: true },
                    y: { stacked: true, beginAtZero: true }
                },
                plugins: {
                    legend: { position: 'top' }
                }
            }
        });
    }
});
</script>
@endpush
@endsection

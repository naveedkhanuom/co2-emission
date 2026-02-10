@extends('layouts.app')

@section('title', 'Data Quality')
@section('page-title', 'Data Quality')

@push('styles')
<style>
    .data-quality-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        margin-bottom: 20px;
        border-left: 4px solid var(--primary-green);
        height: 100%;
    }
    .data-quality-card:hover {
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
    }
    .quality-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    .quality-primary { background-color: rgba(46, 125, 50, 0.15); color: var(--primary-green); }
    .quality-secondary { background-color: rgba(3, 169, 244, 0.15); color: var(--light-blue); }
    .quality-estimated { background-color: rgba(158, 158, 158, 0.2); color: var(--gray-600); }
    .score-circle {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        font-weight: 700;
        margin: 0 auto;
    }
    .score-high { background: rgba(46, 125, 50, 0.15); color: var(--primary-green); }
    .score-medium { background: rgba(255, 152, 0, 0.15); color: #f57c00; }
    .score-low { background: rgba(211, 47, 47, 0.15); color: var(--danger-red); }
</style>
@endpush

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="container mt-4">

        @if(session('error'))
            <div class="alert alert-warning alert-dismissible fade show">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="data-quality-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Overall Quality Score</h6>
                            <div class="score-circle {{ $overallScore >= 70 ? 'score-high' : ($overallScore >= 40 ? 'score-medium' : 'score-low') }}">
                                {{ number_format($overallScore, 1) }}
                            </div>
                            <p class="text-center text-muted small mb-0 mt-2">/ 100</p>
                        </div>
                        <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-check-circle fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="data-quality-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Records ({{ now()->year }})</h6>
                            <h3 class="mb-0">{{ number_format($totalRecords) }}</h3>
                            <small class="text-muted">emission records</small>
                        </div>
                        <div class="bg-info bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-database fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="data-quality-card">
                    <h6 class="text-muted mb-3">Quality Breakdown</h6>
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
                            <span><strong>{{ number_format($row->count) }}</strong> records · {{ number_format((float) $row->total, 2) }} tCO2e</span>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">No data for current year.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Scope 3 Quality & Trends -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-0 pt-3">
                        <h5 class="mb-0"><i class="fas fa-layer-group text-primary me-2"></i>Scope 3 Data Quality</h5>
                    </div>
                    <div class="card-body">
                        @if($scope3Quality->isNotEmpty())
                            <ul class="list-group list-group-flush">
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
                            <p class="text-muted mb-0">No Scope 3 records for current year.</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-0 pt-3">
                        <h5 class="mb-0"><i class="fas fa-chart-line text-primary me-2"></i>Quality Trend (Last 12 Months)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="qualityTrendChart" height="220"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Supplier Data Quality -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-3">
                <h5 class="mb-0"><i class="fas fa-truck text-primary me-2"></i>Supplier Data Quality Scores</h5>
            </div>
            <div class="card-body">
                @if($suppliers->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
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
                                            <span class="score-circle {{ $scoreClass }}" style="width:50px;height:50px;font-size:1rem;">{{ number_format($score, 0) }}</span>
                                        </td>
                                        <td>{{ $supplier->emission_records_count ?? 0 }}</td>
                                        <td>{{ number_format($supplier->total_emissions ?? 0, 2) }} tCO2e</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted mb-0">No suppliers with emission data.</p>
                @endif
            </div>
        </div>

    </div>
</div>

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

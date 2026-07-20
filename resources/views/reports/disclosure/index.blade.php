@extends('layouts.app')

@section('title', 'Disclosure Reports')
@section('page-title', 'Disclosure Reports')

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="container-fluid mt-4" style="font-family:'Segoe UI',Tahoma,sans-serif;">
        <div class="card border-0 shadow-sm" style="border-radius:16px;">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span style="width:40px;height:40px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--primary-green),var(--light-green));color:#fff;"><i class="fas fa-clipboard-check"></i></span>
                    <h4 class="mb-0 fw-bold">Regulatory Disclosure Reports</h4>
                </div>
                <p class="text-muted mb-3">Map your GHG inventory to the datapoints required by CSRD/ESRS E1, CDP and GRI 305 — all from one consistent set of figures, with dual Scope 2 reporting and your selected GWP basis.</p>

                <form method="GET" action="{{ route('disclosure.index') }}" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Framework</label>
                        <select name="framework" class="form-select" onchange="this.form.submit()">
                            @foreach($frameworks as $key => $label)
                                <option value="{{ $key }}" @selected($framework === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Reporting Year</label>
                        <select name="year" class="form-select" onchange="this.form.submit()">
                            @foreach($years as $y)
                                <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">GWP Basis</label>
                        <select name="gwp_version" class="form-select" onchange="this.form.submit()">
                            @foreach($gwpOptions as $val => $label)
                                <option value="{{ $val }}" @selected($gwpVersion === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted d-block">Records: {{ number_format($data['meta']['record_count']) }}</small>
                        <small class="text-muted d-block">Boundary: {{ $data['meta']['consolidation'] }}</small>
                    </div>
                </form>
            </div>
        </div>

        <!-- Inventory summary cards -->
        <div class="row g-3 mt-1">
            @php
                $cards = [
                    ['Scope 1', $data['totals']['scope1'], 'fa-industry'],
                    ['Scope 2 (location)', $data['totals']['scope2_location'], 'fa-bolt'],
                    ['Scope 2 (market)', $data['totals']['scope2_market'], 'fa-plug'],
                    ['Scope 3', $data['totals']['scope3'], 'fa-truck'],
                ];
            @endphp
            @foreach($cards as [$label, $val, $icon])
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100" style="border-radius:14px;">
                    <div class="card-body">
                        <div class="text-muted small"><i class="fas {{ $icon }} me-1"></i>{{ $label }}</div>
                        <div class="fw-bold fs-4">{{ number_format($val, 2) }}</div>
                        <div class="text-muted" style="font-size:.7rem;">tCO₂e</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <div class="row g-3 mt-0">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm" style="border-radius:14px;background:#eef2ff;">
                    <div class="card-body"><div class="text-muted small">Total (location-based)</div><div class="fw-bold fs-5">{{ number_format($data['totals']['total_location'], 2) }} tCO₂e</div></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm" style="border-radius:14px;background:#ecfdf5;">
                    <div class="card-body"><div class="text-muted small">Total (market-based)</div><div class="fw-bold fs-5">{{ number_format($data['totals']['total_market'], 2) }} tCO₂e</div></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm" style="border-radius:14px;background:#fffbeb;">
                    <div class="card-body"><div class="text-muted small">Biogenic CO₂ (separate)</div><div class="fw-bold fs-5">{{ number_format($data['totals']['biogenic_co2'], 2) }} tCO₂</div></div>
                </div>
            </div>
        </div>

        <!-- Datapoint table -->
        <div class="card border-0 shadow-sm mt-3" style="border-radius:16px;">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2" style="border-radius:16px 16px 0 0;">
                <h5 class="mb-0 fw-bold">{{ $frameworkLabel }} — {{ $year }}</h5>
                <div class="btn-group">
                    <a class="btn btn-sm btn-danger" href="{{ route('disclosure.export', ['framework' => $framework, 'year' => $year, 'gwp_version' => $gwpVersion, 'format' => 'pdf']) }}"><i class="fas fa-file-pdf me-1"></i>PDF</a>
                    <a class="btn btn-sm btn-success" href="{{ route('disclosure.export', ['framework' => $framework, 'year' => $year, 'gwp_version' => $gwpVersion, 'format' => 'excel']) }}"><i class="fas fa-file-excel me-1"></i>Excel</a>
                    <a class="btn btn-sm btn-secondary" href="{{ route('disclosure.export', ['framework' => $framework, 'year' => $year, 'gwp_version' => $gwpVersion, 'format' => 'csv']) }}"><i class="fas fa-file-csv me-1"></i>CSV</a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:18%;">Reference</th>
                                <th>Datapoint</th>
                                <th class="text-end" style="width:18%;">Value</th>
                                <th style="width:14%;">Unit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($datapoints as $dp)
                            <tr>
                                <td><span class="badge bg-light text-dark border">{{ $dp['ref'] }}</span></td>
                                <td>{{ $dp['label'] }}</td>
                                <td class="text-end fw-semibold">{{ is_numeric($dp['value']) ? number_format($dp['value'], 2) : $dp['value'] }}</td>
                                <td class="text-muted">{{ $dp['unit'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white text-muted small">
                GWP basis: <strong>{{ $data['meta']['gwp_label'] }}</strong> · Generated {{ $data['meta']['generated_on'] }} ·
                Figures derived from {{ number_format($data['meta']['record_count']) }} active emission records for {{ $year }}.
            </div>
        </div>

        @if($framework === 'gri_305' || $framework === 'cdp')
        <!-- Scope 3 category detail -->
        <div class="card border-0 shadow-sm mt-3" style="border-radius:16px;">
            <div class="card-header bg-white fw-bold" style="border-radius:16px 16px 0 0;">Scope 3 by GHG Protocol Category</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th style="width:8%;">#</th><th>Category</th><th class="text-end" style="width:20%;">tCO₂e</th></tr></thead>
                        <tbody>
                            @foreach($data['scope3_by_category'] as $cat)
                            <tr>
                                <td>{{ $cat['number'] ?: '—' }}</td>
                                <td>{{ $cat['name'] }}</td>
                                <td class="text-end">{{ number_format($cat['co2e'], 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <p class="text-muted small mt-3">
            <i class="fas fa-info-circle me-1"></i>
            This export is a preparation aid that maps your inventory to each framework's emission datapoints. It does not
            constitute a filed disclosure or assurance; figures should be reviewed before submission.
        </p>
    </div>
</div>
@endsection

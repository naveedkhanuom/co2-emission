@extends('layouts.app')

@section('title', 'Base Year Comparison')
@section('page-title', 'Base Year Comparison')

@push('styles')
<style>
.byc-app { font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; }
.byc-app .intro { background:linear-gradient(135deg,rgba(46,125,50,.05),rgba(3,169,244,.05)); border:1px solid var(--gray-200); border-radius:16px; padding:18px 22px; margin-bottom:22px; }
.byc-app .intro h2 { font-size:1.15rem; font-weight:700; margin:0 0 4px; color:var(--gray-800); display:flex; align-items:center; gap:10px; }
.byc-app .intro p { margin:0; font-size:.875rem; color:var(--gray-600); line-height:1.5; }
.byc-app .card { background:#fff; border:1px solid var(--gray-200); border-radius:16px; box-shadow:0 2px 8px rgba(0,0,0,.06); overflow:hidden; }
.byc-app table { width:100%; margin:0; }
.byc-app thead th { background:var(--gray-100); color:var(--gray-600); font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; padding:12px 16px; text-align:right; }
.byc-app thead th:first-child { text-align:left; }
.byc-app tbody td { padding:14px 16px; border-bottom:1px solid var(--gray-100); font-size:.9rem; color:var(--gray-800); text-align:right; }
.byc-app tbody td:first-child { text-align:left; font-weight:700; }
.byc-app tbody tr:last-child td { border-bottom:none; }
.byc-app tr.base-row { background:rgba(2,119,189,.05); }
.byc-app .pill-base { display:inline-block; font-size:.68rem; font-weight:700; padding:3px 10px; border-radius:100px; background:rgba(2,119,189,.12); color:var(--primary-blue); margin-left:8px; }
.byc-app .chg { display:inline-flex; align-items:center; gap:5px; font-weight:700; font-size:.85rem; }
.byc-app .chg.down { color:var(--primary-green); }
.byc-app .chg.up { color:var(--danger-red); }
.byc-app .chg.flat { color:var(--gray-500); }
.byc-app .empty { padding:44px; text-align:center; color:var(--gray-600); }
.byc-app .empty a { color:var(--primary-green); font-weight:600; }
</style>
@endpush

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="container-fluid py-4 byc-app">
        <div class="intro">
            <h2><i class="fas fa-chart-line" style="color:var(--primary-green)"></i> Base Year Comparison</h2>
            <p>Each inventory year measured against your <strong>base year</strong> — the fixed baseline for tracking reductions. A <span style="color:var(--primary-green);font-weight:600">green</span> change is a reduction; <span style="color:var(--danger-red);font-weight:600">red</span> is an increase.</p>
        </div>

        @if(!$baseYear)
            <div class="card"><div class="empty">
                <i class="fas fa-flag fa-2x d-block mb-3" style="opacity:.3"></i>
                No base year is set yet. Choose one on the
                <a href="{{ route('reporting_periods.index') }}">Reporting Periods</a> page to enable comparisons.
            </div></div>
        @elseif(!$hasBaseData)
            <div class="card"><div class="empty">
                <i class="fas fa-triangle-exclamation fa-2x d-block mb-3" style="opacity:.3"></i>
                Your base year is <strong>{{ $baseYear }}</strong>, but there are no finalised (validated) records for it yet.
            </div></div>
        @else
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>Year</th>
                            <th>Scope 1</th>
                            <th>Scope 2</th>
                            <th>Scope 3</th>
                            <th>Total (tCO₂e)</th>
                            <th>vs Base Year</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($years as $y)
                            <tr class="{{ $y['is_base'] ? 'base-row' : '' }}">
                                <td>{{ $y['year'] }}@if($y['is_base'])<span class="pill-base">Base year</span>@endif</td>
                                <td>{{ number_format($y['scope1'], 2) }}</td>
                                <td>{{ number_format($y['scope2'], 2) }}</td>
                                <td>{{ number_format($y['scope3'], 2) }}</td>
                                <td><strong>{{ number_format($y['total'], 2) }}</strong></td>
                                <td>
                                    @if($y['is_base'])
                                        <span class="chg flat">— baseline —</span>
                                    @elseif($y['change'] === null)
                                        <span class="chg flat">—</span>
                                    @else
                                        @php $down = $y['change'] < 0; @endphp
                                        <span class="chg {{ $down ? 'down' : ($y['change'] > 0 ? 'up' : 'flat') }}">
                                            <i class="fas fa-arrow-{{ $down ? 'down' : ($y['change'] > 0 ? 'up' : 'right') }}"></i>
                                            {{ $y['change'] > 0 ? '+' : '' }}{{ number_format($y['change'], 1) }}%
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection

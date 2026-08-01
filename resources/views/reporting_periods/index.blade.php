@extends('layouts.app')

@section('title', 'Reporting Periods')
@section('page-title', 'Reporting Periods')

@push('styles')
<style>
.rp-app { font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; }
.rp-app .intro { background:linear-gradient(135deg,rgba(46,125,50,.05),rgba(3,169,244,.05)); border:1px solid var(--gray-200); border-radius:16px; padding:18px 22px; margin-bottom:22px; }
.rp-app .intro h2 { font-size:1.15rem; font-weight:700; margin:0 0 4px; color:var(--gray-800); display:flex; align-items:center; gap:10px; }
.rp-app .intro p { margin:0; font-size:.875rem; color:var(--gray-600); line-height:1.5; }
.rp-app .card { background:#fff; border:1px solid var(--gray-200); border-radius:16px; box-shadow:0 2px 8px rgba(0,0,0,.06); overflow:hidden; }
.rp-app table { width:100%; margin:0; }
.rp-app thead th { background:var(--gray-100); color:var(--gray-600); font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; padding:12px 16px; text-align:left; }
.rp-app tbody td { padding:14px 16px; border-bottom:1px solid var(--gray-100); font-size:.9rem; color:var(--gray-800); vertical-align:middle; }
.rp-app tbody tr:last-child td { border-bottom:none; }
.rp-app .yr { font-size:1.05rem; font-weight:700; }
.rp-app .pill { display:inline-flex; align-items:center; gap:6px; font-size:.72rem; font-weight:700; padding:4px 12px; border-radius:100px; }
.rp-app .pill.open { background:rgba(46,125,50,.12); color:var(--primary-green); }
.rp-app .pill.locked { background:rgba(211,47,47,.1); color:var(--danger-red); }
.rp-app .pill.base { background:rgba(2,119,189,.12); color:var(--primary-blue); }
.rp-app .rp-btn { font-size:.78rem; font-weight:600; padding:7px 14px; border-radius:9px; border:1px solid var(--gray-300); background:#fff; color:var(--gray-700); cursor:pointer; display:inline-flex; align-items:center; gap:6px; }
.rp-app .rp-btn:hover { background:var(--gray-50); }
.rp-app .rp-btn.lock { border-color:rgba(211,47,47,.4); color:var(--danger-red); }
.rp-app .rp-btn.lock:hover { background:rgba(211,47,47,.06); }
.rp-app .rp-btn.unlock { border-color:rgba(46,125,50,.4); color:var(--primary-green); }
.rp-app .rp-btn.unlock:hover { background:rgba(46,125,50,.06); }
.rp-app .lockmeta { font-size:.72rem; color:var(--gray-500); margin-top:3px; }
.rp-app .empty { padding:40px; text-align:center; color:var(--gray-500); }
</style>
@endpush

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="container-fluid py-4 rp-app">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <div class="intro">
            <h2><i class="fas fa-lock" style="color:var(--primary-green)"></i> Reporting Periods & Governance</h2>
            <p><strong>Lock</strong> a year after its report is filed to freeze its data — no records in a locked year can be added, edited, or deleted. Mark one year as the <strong>base year</strong>: the fixed baseline that targets and year-over-year comparisons are measured against.</p>
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Year</th>
                        <th>Records</th>
                        <th>Total (tCO₂e)</th>
                        <th>Status</th>
                        <th style="text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($periods as $p)
                        <tr>
                            <td>
                                <span class="yr">{{ $p['year'] }}</span>
                                @if($p['is_base_year'])<span class="pill base ms-2"><i class="fas fa-flag"></i> Base year</span>@endif
                            </td>
                            <td>{{ number_format($p['records']) }}</td>
                            <td>{{ number_format($p['total'], 2) }}</td>
                            <td>
                                @if($p['status'] === 'locked')
                                    <span class="pill locked"><i class="fas fa-lock"></i> Locked</span>
                                    @if($p['locked_by'])<div class="lockmeta">by {{ $p['locked_by'] }}{{ $p['locked_at'] ? ' · '.\Carbon\Carbon::parse($p['locked_at'])->format('d M Y') : '' }}</div>@endif
                                @else
                                    <span class="pill open"><i class="fas fa-lock-open"></i> Open</span>
                                @endif
                            </td>
                            <td style="text-align:right; white-space:nowrap">
                                @if(auth()->user()->can('edit-review-data'))
                                    @if(!$p['is_base_year'])
                                        <form method="POST" action="{{ route('reporting_periods.base_year', $p['year']) }}" class="d-inline">@csrf
                                            <button class="rp-btn" title="Set as base year"><i class="fas fa-flag"></i> Set base year</button>
                                        </form>
                                    @endif
                                    @if($p['status'] === 'locked')
                                        <form method="POST" action="{{ route('reporting_periods.unlock', $p['year']) }}" class="d-inline"
                                              onsubmit="return confirm('Unlock {{ $p['year'] }}? Its data will become editable again.');">@csrf
                                            <button class="rp-btn unlock"><i class="fas fa-lock-open"></i> Unlock</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('reporting_periods.lock', $p['year']) }}" class="d-inline"
                                              onsubmit="return confirm('Lock {{ $p['year'] }}? Its {{ number_format($p['records']) }} record(s) will be frozen (no add/edit/delete).');">@csrf
                                            <button class="rp-btn lock"><i class="fas fa-lock"></i> Lock</button>
                                        </form>
                                    @endif
                                @else
                                    <span class="text-muted" style="font-size:.78rem">View only</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty"><i class="fas fa-calendar fa-2x d-block mb-2" style="opacity:.3"></i>No reporting periods yet — they appear here once you have emission data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

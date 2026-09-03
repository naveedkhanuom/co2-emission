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
/* Boundary gate */
.rp-app .rp-gap { display:flex; gap:12px; align-items:flex-start; background:rgba(255,152,0,.08); border:1px solid rgba(255,152,0,.35); border-radius:14px; padding:14px 18px; margin-bottom:20px; font-size:.875rem; color:var(--gray-700); line-height:1.55; }
.rp-app .rp-gap > i { color:#e08600; font-size:1.05rem; margin-top:2px; }
.rp-app .rp-gap-link { display:inline-block; margin-top:6px; font-weight:700; font-size:.82rem; color:var(--primary-green); text-decoration:none; }
.rp-app .rp-gap-link:hover { text-decoration:underline; }
.rp-app .ack-form { background:var(--gray-50); border:1px solid var(--gray-200); border-radius:12px; padding:14px; margin-top:10px; text-align:left; max-width:420px; margin-left:auto; }
.rp-app .ack-form label { font-size:.78rem; font-weight:700; color:var(--gray-700); display:block; margin-bottom:6px; }
.rp-app .ack-form textarea { width:100%; font-size:.82rem; border:1px solid var(--gray-300); border-radius:8px; padding:8px 10px; resize:vertical; }
.rp-app .ack-form .hint { font-size:.72rem; color:var(--gray-500); margin:6px 0 10px; }
.rp-app .caveat { display:inline-flex; align-items:center; gap:5px; font-size:.7rem; font-weight:700; padding:3px 9px; border-radius:100px; background:rgba(255,152,0,.14); color:#b36b00; margin-top:4px; }
</style>
@endpush

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="container-fluid py-4 rp-app">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        {{-- The blade rendered only `success`, so the lock gate's refusal had nowhere to appear. --}}
        @if(session('error'))
            <div class="alert alert-warning alert-dismissible fade show"><i class="fas fa-triangle-exclamation me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-circle-exclamation me-2"></i>{{ $errors->first() }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        {{--
            Standing notice rather than a surprise at the moment of locking: a
            client should learn their boundary is incomplete while there is
            still time to fix it, not when they are trying to file.
        --}}
        @if($boundaryGap)
            <div class="rp-gap">
                <i class="fas fa-compass-drafting"></i>
                <div>
                    <b>Your inventory boundary is not complete</b> — {{ $boundaryGap }}
                    A year locked in this state will be permanently marked as finalised without one.
                    <a href="{{ route('boundary.index') }}" class="rp-gap-link">Scope my boundary <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
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
                                    {{-- A caveat nobody can see is not a caveat. --}}
                                    @if($p['ack_gap'])
                                        <div><span class="caveat" title="{{ $p['ack_gap'] }}&#10;&#10;{{ $p['ack_reason'] }}"><i class="fas fa-triangle-exclamation"></i> Boundary incomplete at lock</span></div>
                                    @endif
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
                                    @elseif(! $boundaryGap)
                                        <form method="POST" action="{{ route('reporting_periods.lock', $p['year']) }}" class="d-inline"
                                              onsubmit="return confirm('Lock {{ $p['year'] }}? Its {{ number_format($p['records']) }} record(s) will be frozen (no add/edit/delete).');">@csrf
                                            <button class="rp-btn lock"><i class="fas fa-lock"></i> Lock</button>
                                        </form>
                                    @else
                                        {{--
                                            Boundary incomplete: locking is still possible, but only
                                            with a written reason. The reason is what an assurer reads,
                                            so it is asked for here rather than bounced back as an error.
                                        --}}
                                        <button type="button" class="rp-btn lock" data-ack-toggle="{{ $p['year'] }}">
                                            <i class="fas fa-lock"></i> Lock…
                                        </button>
                                        <form method="POST" action="{{ route('reporting_periods.lock', $p['year']) }}"
                                              class="ack-form" id="ack-{{ $p['year'] }}" hidden
                                              onsubmit="return confirm('Lock {{ $p['year'] }} without a complete boundary? Its {{ number_format($p['records']) }} record(s) will be frozen and the year will be permanently marked.');">@csrf
                                            <label for="ackReason{{ $p['year'] }}">Why can {{ $p['year'] }} be finalised without a complete boundary?</label>
                                            <textarea id="ackReason{{ $p['year'] }}" name="boundary_ack_reason" rows="3" minlength="20" maxlength="500" required
                                                      placeholder="e.g. Scope 3 screening was completed in the 2025 consultant report filed outside this system; boundary to be entered before the next cycle."></textarea>
                                            <div class="hint">Recorded permanently against this year and shown to anyone reviewing the inventory.</div>
                                            <button class="rp-btn lock" type="submit"><i class="fas fa-lock"></i> Lock {{ $p['year'] }} anyway</button>
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

@push('scripts')
<script>
    // Reveal the acknowledgement form for one year at a time. Plain toggling —
    // the form posts to the same route the one-click button does, so a client
    // with a complete boundary never sees any of this.
    document.querySelectorAll('[data-ack-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var form = document.getElementById('ack-' + btn.dataset.ackToggle);
            if (! form) return;
            form.hidden = ! form.hidden;
            if (! form.hidden) form.querySelector('textarea').focus();
        });
    });
</script>
@endpush
@endsection

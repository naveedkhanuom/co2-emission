@extends('layouts.app')

@section('title', 'Data Health')
@section('page-title', 'Data Health')

@push('styles')
<style>
.health-app{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif}
.health-app .topbar{display:flex;align-items:center;gap:16px;margin-bottom:24px;flex-wrap:wrap;padding:20px 24px;background:linear-gradient(135deg,#fff,var(--gray-50));border:1px solid var(--gray-200);border-radius:16px;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.health-app .topbar h2{font-size:1.35rem;font-weight:700;letter-spacing:-.02em;display:flex;align-items:center;gap:10px;margin:0;color:var(--gray-800)}
.health-app .topbar h2 .sb{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,var(--primary-green),var(--light-green));color:#fff;font-size:1rem;box-shadow:0 2px 8px rgba(46,125,50,.25)}
.health-app .topbar p{color:var(--gray-600);font-size:.875rem;flex:1;min-width:180px;margin:0;line-height:1.4}
.health-app .grid2{display:grid;grid-template-columns:320px 1fr;gap:20px;margin-bottom:20px}
@media(max-width:900px){.health-app .grid2{grid-template-columns:1fr}}
.health-app .card{background:#fff;border:1px solid var(--gray-200);border-radius:16px;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.health-app .card .hd{padding:16px 20px;border-bottom:1px solid var(--gray-200);font-weight:700;color:var(--gray-800);font-size:1rem;background:linear-gradient(180deg,var(--gray-50),#fff);border-radius:16px 16px 0 0}
.health-app .card .bd{padding:20px}
/* Score ring */
.health-app .ringwrap{display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;height:100%;padding:24px 20px}
.health-app .ring{width:170px;height:170px;border-radius:50%;display:flex;align-items:center;justify-content:center;position:relative}
.health-app .ring::before{content:'';position:absolute;inset:14px;background:#fff;border-radius:50%}
.health-app .ring .rv{position:relative;z-index:1;font-size:2.6rem;font-weight:800;letter-spacing:-.03em;color:var(--gray-800);line-height:1}
.health-app .ring .rl{position:relative;z-index:1;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--gray-500);margin-top:2px}
.health-app .score-cap{margin-top:14px;font-size:.9rem;color:var(--gray-600);max-width:240px}
.health-app .score-badge{display:inline-block;margin-top:10px;padding:5px 14px;border-radius:100px;font-size:.8rem;font-weight:700}
/* Dimension bars */
.health-app .dim{margin-bottom:16px}
.health-app .dim:last-child{margin-bottom:0}
.health-app .dim .dt{display:flex;justify-content:space-between;align-items:baseline;margin-bottom:6px}
.health-app .dim .dt b{font-size:.9rem;color:var(--gray-800)}
.health-app .dim .dt .dh{font-size:.78rem;color:var(--gray-500)}
.health-app .dim .track{height:9px;border-radius:100px;background:var(--gray-100);overflow:hidden}
.health-app .dim .fill{height:100%;border-radius:100px;transition:width .5s}
.health-app .dim .na{font-size:.78rem;color:var(--gray-400);font-style:italic}
/* Scope coverage */
.health-app .scopes{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;margin-bottom:20px}
.health-app .scard{background:#fff;border:1px solid var(--gray-200);border-radius:14px;padding:18px 20px;box-shadow:0 2px 8px rgba(0,0,0,.06);display:flex;flex-direction:column;gap:6px;position:relative;overflow:hidden}
.health-app .scard .si{width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:1.15rem;color:#fff;background:linear-gradient(135deg,var(--primary-green),var(--light-green))}
.health-app .scard h4{margin:8px 0 0;font-size:1rem;font-weight:700;color:var(--gray-800);display:flex;align-items:center;gap:8px}
.health-app .scard .desc{font-size:.8rem;color:var(--gray-600);line-height:1.35}
.health-app .scard .st{font-size:.75rem;font-weight:700;padding:3px 10px;border-radius:100px;display:inline-flex;align-items:center;gap:5px;align-self:flex-start;margin-top:4px}
.health-app .scard .metric{font-size:1.5rem;font-weight:800;color:var(--gray-800);letter-spacing:-.02em}
.health-app .scard .metric small{font-size:.75rem;font-weight:600;color:var(--gray-500)}
.health-app .scard .lastd{font-size:.72rem;color:var(--gray-500)}
.health-app .scard.off{opacity:.6}
.health-app .badge-ok{background:rgba(46,125,50,.12);color:var(--primary-green)}
.health-app .badge-warn{background:rgba(245,124,0,.14);color:#e65100}
.health-app .badge-off{background:var(--gray-100);color:var(--gray-500)}
.health-app .scard .go{margin-top:6px;font-size:.8rem;font-weight:600;color:var(--primary-green);text-decoration:none;display:inline-flex;align-items:center;gap:6px}
/* Next steps */
.health-app .step{display:flex;align-items:flex-start;gap:14px;padding:14px 0;border-bottom:1px solid var(--gray-100)}
.health-app .step:last-child{border-bottom:none}
.health-app .step .sic{width:40px;height:40px;flex:none;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:1rem}
.health-app .step .sbody{flex:1;min-width:0}
.health-app .step .sbody b{display:block;font-size:.92rem;color:var(--gray-800)}
.health-app .step .sbody span{font-size:.8rem;color:var(--gray-600);line-height:1.4}
.health-app .step .scta{flex:none;font-size:.8rem;font-weight:600;padding:8px 14px;border-radius:9px;text-decoration:none;white-space:nowrap;background:linear-gradient(135deg,var(--primary-green),var(--light-green));color:#fff}
.health-app .step .scta:hover{background:linear-gradient(135deg,var(--dark-green),var(--primary-green))}
.health-app .sev-high .sic{background:rgba(211,47,47,.12);color:#d32f2f}
.health-app .sev-medium .sic{background:rgba(245,124,0,.14);color:#e65100}
.health-app .sev-low .sic{background:rgba(2,119,189,.12);color:#0277bd}
.health-app .sev-done .sic{background:rgba(46,125,50,.12);color:var(--primary-green)}
.health-app .mini{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px}
.health-app .mtile{background:var(--gray-50);border:1px solid var(--gray-200);border-radius:12px;padding:14px 16px}
.health-app .mtile .mv{font-size:1.35rem;font-weight:800;color:var(--gray-800);letter-spacing:-.02em}
.health-app .mtile .ml{font-size:.75rem;color:var(--gray-600);margin-top:2px}
</style>
@endpush

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="container-fluid py-4 health-app">
        @php
            $ringColor = $healthScore >= 80 ? 'var(--primary-green)' : ($healthScore >= 50 ? '#f57c00' : '#d32f2f');
            $ringDeg   = $healthScore * 3.6;
            $verdict   = $healthScore >= 80 ? ['Looking great', 'badge-ok'] : ($healthScore >= 50 ? ['Getting there', 'badge-warn'] : ['Needs attention', 'badge-warn']);
            $barColor  = fn($s) => $s >= 80 ? 'var(--primary-green)' : ($s >= 50 ? '#f57c00' : '#d32f2f');
        @endphp

        <div class="topbar">
            <h2><span class="sb"><i class="fas fa-heart-pulse"></i></span> Data Health</h2>
            <p>A quick read on how complete your carbon inventory is for {{ $year }}, and exactly what to do next.</p>
        </div>

        <div class="grid2">
            <!-- Health score -->
            <div class="card">
                <div class="ringwrap">
                    <div class="ring" style="background:conic-gradient({{ $ringColor }} {{ $ringDeg }}deg, var(--gray-200) {{ $ringDeg }}deg)">
                        <div style="position:relative;z-index:1;text-align:center">
                            <div class="rv">{{ $healthScore }}</div>
                            <div class="rl">/ 100</div>
                        </div>
                    </div>
                    <span class="score-badge {{ $verdict[1] }}">{{ $verdict[0] }}</span>
                    <p class="score-cap">This blends boundary coverage, scope coverage, Scope 3 breadth, monthly completeness, review status and data quality.</p>
                </div>
            </div>

            <!-- Dimension breakdown -->
            <div class="card">
                <div class="hd">What makes up your score</div>
                <div class="bd">
                    @foreach($dimensions as $d)
                        <div class="dim">
                            <div class="dt">
                                <b>{{ $d['label'] }}</b>
                                <span class="dh">{{ $d['hint'] }}</span>
                            </div>
                            @if($d['score'] === null)
                                <div class="na">Not applicable</div>
                            @else
                                <div class="track"><div class="fill" style="width:{{ $d['score'] }}%;background:{{ $barColor($d['score']) }}"></div></div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Scope coverage -->
        <div class="scopes">
            @foreach([1,2,3] as $s)
                @php $sc = $scopes[$s]; @endphp
                <div class="scard {{ $sc['enabled'] ? '' : 'off' }}">
                    <div class="si"><i class="fas {{ $sc['icon'] }}"></i></div>
                    <h4>{{ $sc['label'] }}</h4>
                    <div class="desc">{{ $sc['desc'] }}</div>
                    @if(!$sc['enabled'])
                        <span class="st badge-off"><i class="fas fa-minus"></i> Not enabled</span>
                    @elseif($sc['has_data'])
                        <span class="st badge-ok"><i class="fas fa-check"></i> Has data</span>
                        <div class="metric">{{ number_format($sc['count']) }} <small>entries · {{ number_format($sc['total'], 1) }} tCO₂e</small></div>
                        @if($sc['last_date'])<div class="lastd">Last entry {{ \Carbon\Carbon::parse($sc['last_date'])->format('d M Y') }}</div>@endif
                    @else
                        <span class="st badge-warn"><i class="fas fa-exclamation"></i> No data yet</span>
                        <a href="{{ route($sc['route']) }}" class="go">Add {{ $sc['label'] }} data <i class="fas fa-arrow-right"></i></a>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="grid2" style="grid-template-columns:1fr 320px">
            <!-- Next steps -->
            <div class="card">
                <div class="hd"><i class="fas fa-list-check me-2" style="color:var(--primary-green)"></i>Your next steps</div>
                <div class="bd" style="padding-top:6px;padding-bottom:6px">
                    @foreach($steps as $step)
                        <div class="step sev-{{ $step['sev'] }}">
                            <div class="sic"><i class="fas {{ $step['icon'] }}"></i></div>
                            <div class="sbody">
                                <b>{{ $step['title'] }}</b>
                                <span>{{ $step['sub'] }}</span>
                            </div>
                            @if(!empty($step['route']) && \Illuminate\Support\Facades\Route::has($step['route']))
                                <a href="{{ route($step['route']) }}" class="scta">{{ $step['cta'] }}</a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- At a glance -->
            <div class="card">
                <div class="hd">At a glance</div>
                <div class="bd">
                    <div class="mini">
                        <div class="mtile">
                            <div class="mv">{{ $boundary['has_boundary'] && $boundary['total'] ? $boundary['covered'].'/'.$boundary['total'] : '—' }}</div>
                            <div class="ml">Boundary items with data</div>
                        </div>
                        <div class="mtile"><div class="mv">{{ $scope3['enabled'] ? $scope3['covered'].'/'.$scope3['total'] : '—' }}</div><div class="ml">Scope 3 categories</div></div>
                        <div class="mtile"><div class="mv">{{ $monthsCovered }}/{{ $monthsElapsed }}</div><div class="ml">Months covered ({{ $year }})</div></div>
                        <div class="mtile"><div class="mv">{{ number_format($pendingReview) }}</div><div class="ml">Awaiting review</div></div>
                        <div class="mtile"><div class="mv">{{ $dqTotal ? round($dqPrimary / $dqTotal * 100) : 0 }}%</div><div class="ml">Measured data</div></div>
                    </div>

                    @if(!$boundary['has_boundary'])
                        <div style="margin-top:16px;font-size:.8rem;color:var(--gray-600);line-height:1.5">
                            <b style="color:var(--gray-700)">No boundary yet.</b>
                            You haven’t defined what your company needs to measure.
                            <a href="{{ route('boundary.index') }}" style="color:var(--primary-green);font-weight:700">Scope it now</a>
                            and the rest of this page becomes specific to your business.
                        </div>
                    @elseif($boundary['top_gaps']->isNotEmpty())
                        <div style="margin-top:16px;font-size:.8rem;color:var(--gray-600);line-height:1.5">
                            <b style="color:var(--gray-700)">Boundary gaps:</b>
                            {{ $boundary['top_gaps']->pluck('suggested_name')->implode(', ') }}{{ $boundary['gaps']->count() > 3 ? ' +'.($boundary['gaps']->count() - 3).' more' : '' }}
                        </div>
                    @endif

                    @if($scope3['enabled'] && !empty($scope3['missing_list']))
                        <div style="margin-top:16px;font-size:.8rem;color:var(--gray-600)">
                            <b style="color:var(--gray-700)">Scope 3 gaps:</b>
                            {{ implode(', ', $scope3['missing_list']) }}{{ $scope3['missing_more'] ? ' +'.$scope3['missing_more'].' more' : '' }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

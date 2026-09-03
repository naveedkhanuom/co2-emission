@extends('layouts.app')

@section('title', 'Boundary Advisor')
@section('page-title', 'Boundary Advisor')

@push('styles')
<style>
.boundary-page{
  position: relative;
  background:
    radial-gradient(1200px 500px at 20% -10%, rgba(46,125,50,.12), transparent 60%),
    radial-gradient(900px 450px at 95% 10%, rgba(2,119,189,.10), transparent 55%),
    linear-gradient(180deg, #fbfcfd 0%, #ffffff 60%);
}
.boundary-page .bd-container{ max-width: 1040px; margin: 0 auto; padding: 24px 20px 80px; }
.boundary-page .bd-hero{
  background: linear-gradient(135deg, rgba(255,255,255,.92) 0%, rgba(248,250,252,.92) 100%);
  border: 1px solid var(--gray-200); border-radius: 18px;
  box-shadow: 0 10px 30px rgba(0,0,0,.06);
  padding: 22px; margin-bottom: 18px;
}
.boundary-page .bd-title-row{ display:flex; align-items:center; gap: 12px; margin-bottom: 8px; }
.boundary-page .bd-title-icon{
  width: 44px; height: 44px; border-radius: 14px; flex:0 0 auto;
  display:flex; align-items:center; justify-content:center; color:#fff; font-size: 18px;
  background: linear-gradient(145deg, #2e7d32 0%, #0277bd 100%);
  box-shadow: 0 6px 16px rgba(2,119,189,.25);
}
.boundary-page h1{ font-size: 22px; font-weight: 800; color: var(--gray-800); margin:0; letter-spacing:-.02em; }
.boundary-page .bd-sub{ color: var(--gray-600); font-size: 13.5px; line-height:1.55; margin-top:4px; }
.boundary-page .bd-steps{ display:flex; gap:8px; flex-wrap:wrap; margin-top:16px; }
.boundary-page .bd-step{
  display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border-radius:999px;
  background: rgba(255,255,255,.9); border:1px solid var(--gray-200); font-size:12px; color: var(--gray-600);
}
.boundary-page .bd-step.done{ background: rgba(46,125,50,.08); border-color: rgba(46,125,50,.25); color: var(--primary-green); font-weight:700; }
.boundary-page .bd-step.current{ background:#fff; border-color: var(--primary-blue); color: var(--primary-blue); font-weight:800; box-shadow:0 2px 8px rgba(2,119,189,.15); }
.boundary-page .bd-step b{ font-weight:800; }

.boundary-page .bd-card{ background:#fff; border:1px solid var(--gray-200); border-radius:16px; padding:20px; margin-bottom:16px; box-shadow:0 2px 10px rgba(0,0,0,.04); }
.boundary-page .bd-card h2{ font-size:16px; font-weight:800; color:var(--gray-800); margin:0 0 4px; }
.boundary-page .bd-card .bd-card-hint{ font-size:12.5px; color:var(--gray-600); margin-bottom:16px; line-height:1.5; }
.boundary-page label.bd-label{ display:block; font-size:12px; font-weight:800; color:var(--gray-700); margin-bottom:6px; text-transform:uppercase; letter-spacing:.4px; }
.boundary-page .bd-help{ font-size:12px; color:var(--gray-500); margin-top:5px; line-height:1.45; }
.boundary-page .form-control, .boundary-page .form-select{ border-radius:12px; border:1px solid var(--gray-200); padding:11px 14px; font-size:14px; }
.boundary-page .form-control:focus, .boundary-page .form-select:focus{ border-color:var(--primary-green); box-shadow:0 0 0 3px rgba(46,125,50,.1); }

.boundary-page .bd-btn{
  border:none; cursor:pointer; color:#fff; font-weight:800; font-size:14px;
  padding:12px 22px; border-radius:12px; display:inline-flex; align-items:center; gap:8px;
  background: linear-gradient(180deg, #2e7d32 0%, #1b5e20 100%);
  box-shadow:0 4px 14px rgba(46,125,50,.25); transition: transform .2s, box-shadow .2s, opacity .2s;
}
.boundary-page .bd-btn:hover{ transform:translateY(-1px); box-shadow:0 6px 18px rgba(46,125,50,.35); color:#fff; }
.boundary-page .bd-btn:disabled{ opacity:.6; cursor:not-allowed; transform:none; }
.boundary-page .bd-btn-ghost{ background:#fff; color:var(--gray-700); border:2px solid var(--gray-200); box-shadow:none; }
.boundary-page .bd-btn-ghost:hover{ background:var(--gray-50); color:var(--gray-800); }

.boundary-page .bd-q{ border:1px solid var(--gray-200); border-radius:14px; padding:16px; margin-bottom:12px; background:#fff; }
.boundary-page .bd-q-title{ font-size:14.5px; font-weight:700; color:var(--gray-800); margin-bottom:4px; }
.boundary-page .bd-q-help{ font-size:12px; color:var(--gray-600); margin-bottom:12px; line-height:1.5; }
.boundary-page .bd-opts{ display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:8px; }
.boundary-page .bd-opt{
  display:flex; align-items:center; gap:9px; padding:11px 13px; border-radius:11px;
  border:2px solid var(--gray-200); background:#fff; cursor:pointer; font-size:13px; color:var(--gray-700);
  transition:all .18s;
}
.boundary-page .bd-opt:hover{ border-color:var(--gray-300); background:var(--gray-50); }
.boundary-page .bd-opt.selected{ border-color:var(--primary-green); background:rgba(46,125,50,.07); color:var(--gray-800); font-weight:700; }
.boundary-page .bd-opt input{ accent-color: var(--primary-green); flex:0 0 auto; }
.boundary-page .bd-ai-tag{ font-size:10px; font-weight:800; letter-spacing:.6px; text-transform:uppercase; color:var(--primary-blue); background:rgba(2,119,189,.08); border-radius:999px; padding:3px 8px; margin-left:8px; }

.boundary-page .bd-summary{ background:linear-gradient(135deg, rgba(46,125,50,.06) 0%, rgba(2,119,189,.06) 100%); border:1px solid var(--gray-200); border-radius:16px; padding:18px; margin-bottom:16px; }
.boundary-page .bd-summary-text{ font-size:14px; line-height:1.6; color:var(--gray-700); }
.boundary-page .bd-meta{ display:flex; gap:8px; flex-wrap:wrap; margin-top:12px; }
.boundary-page .bd-chip{ font-size:11px; font-weight:700; color:var(--gray-600); background:#fff; border:1px solid var(--gray-200); padding:5px 11px; border-radius:999px; }

.boundary-page .bd-progress-wrap{ display:flex; align-items:center; gap:14px; flex-wrap:wrap; margin-bottom:16px; }
.boundary-page .bd-progress{ flex:1; min-width:200px; height:8px; border-radius:999px; background:var(--gray-200); overflow:hidden; }
.boundary-page .bd-progress-fill{ height:100%; border-radius:999px; background:linear-gradient(90deg,#f57c00,#2e7d32); transition:width .4s; }
.boundary-page .bd-progress-label{ font-size:12.5px; font-weight:700; color:var(--gray-700); }

.boundary-page .bd-scope-head{ display:flex; align-items:center; gap:10px; margin:22px 0 10px; }
.boundary-page .bd-scope-badge{ width:34px; height:34px; border-radius:10px; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:800; font-size:15px; background:linear-gradient(145deg,#2e7d32,#1b5e20); }
.boundary-page .bd-scope-badge[data-scope="2"]{ background:linear-gradient(145deg,#0277bd,#01579b); }
.boundary-page .bd-scope-badge[data-scope="3"]{ background:linear-gradient(145deg,#1b5e20,#0d3d0d); }
.boundary-page .bd-scope-title{ font-size:15px; font-weight:800; color:var(--gray-800); }
.boundary-page .bd-scope-sub{ font-size:12px; color:var(--gray-600); }

.boundary-page .bd-item{ border:1px solid var(--gray-200); border-radius:14px; padding:15px 16px; margin-bottom:10px; background:#fff; transition:border-color .2s, box-shadow .2s; }
.boundary-page .bd-item:hover{ box-shadow:0 4px 14px rgba(0,0,0,.06); }
.boundary-page .bd-item.is-included{ border-left:4px solid var(--primary-green); background:rgba(46,125,50,.03); }
.boundary-page .bd-item.is-excluded{ border-left:4px solid var(--gray-300); opacity:.72; }
.boundary-page .bd-item.is-deferred{ border-left:4px solid #f57c00; }
.boundary-page .bd-item-top{ display:flex; align-items:flex-start; gap:12px; flex-wrap:wrap; }
.boundary-page .bd-item-main{ flex:1; min-width:220px; }
.boundary-page .bd-item-name{ font-size:14.5px; font-weight:700; color:var(--gray-800); }
.boundary-page .bd-item-cat{ font-size:11.5px; color:var(--gray-500); margin-top:2px; }
.boundary-page .bd-item-why{ font-size:13px; color:var(--gray-700); margin-top:8px; line-height:1.55; }
.boundary-page .bd-item-hint{ font-size:12.5px; color:var(--gray-600); margin-top:8px; background:var(--gray-50); border-radius:9px; padding:9px 11px; line-height:1.5; }
.boundary-page .bd-item-hint i{ color:var(--primary-blue); margin-right:6px; }
.boundary-page .bd-tags{ display:flex; gap:6px; flex-wrap:wrap; align-items:center; }
.boundary-page .bd-tag{ font-size:10px; font-weight:800; letter-spacing:.5px; text-transform:uppercase; padding:4px 9px; border-radius:999px; }
.boundary-page .bd-tag-high{ background:rgba(211,47,47,.09); color:#c62828; }
.boundary-page .bd-tag-medium{ background:rgba(245,124,0,.10); color:#e65100; }
.boundary-page .bd-tag-low{ background:var(--gray-100); color:var(--gray-600); }
.boundary-page .bd-tag-unknown{ background:rgba(2,119,189,.09); color:#01579b; }
.boundary-page .bd-actions{ display:flex; gap:8px; flex-wrap:wrap; margin-top:12px; align-items:center; }
.boundary-page .bd-mini{
  border:2px solid var(--gray-200); background:#fff; color:var(--gray-700);
  font-size:12.5px; font-weight:700; padding:8px 14px; border-radius:10px; cursor:pointer; transition:all .18s;
}
.boundary-page .bd-mini:hover{ border-color:var(--gray-300); background:var(--gray-50); }
.boundary-page .bd-mini.active-in{ border-color:var(--primary-green); background:rgba(46,125,50,.09); color:var(--primary-green); }
.boundary-page .bd-mini.active-ex{ border-color:var(--gray-400); background:var(--gray-100); color:var(--gray-700); }
.boundary-page .bd-mini.active-df{ border-color:#f57c00; background:rgba(245,124,0,.09); color:#e65100; }
.boundary-page .bd-do{
  text-decoration:none; font-size:12.5px; font-weight:700; padding:8px 14px; border-radius:10px;
  background:rgba(2,119,189,.08); color:#01579b; border:1px solid rgba(2,119,189,.2);
  display:inline-flex; align-items:center; gap:7px; margin-left:auto; transition:all .18s;
}
.boundary-page .bd-do:hover{ background:rgba(2,119,189,.14); color:#01579b; }
.boundary-page .bd-excluded-note{ font-size:12px; color:var(--gray-600); margin-top:9px; font-style:italic; border-left:3px solid var(--gray-300); padding-left:10px; }

.boundary-page .bd-coverage{ background:#fff; border:1px solid var(--gray-200); border-radius:16px; padding:18px; margin-bottom:16px; }
.boundary-page .bd-cov-num{ font-size:30px; font-weight:800; color:var(--gray-800); line-height:1; }
.boundary-page .bd-cov-label{ font-size:12.5px; color:var(--gray-600); margin-top:4px; }

.boundary-page .bd-sticky{
  position:sticky; bottom:0; background:rgba(255,255,255,.96); backdrop-filter:blur(8px);
  border-top:1px solid var(--gray-200); padding:14px 0; margin-top:20px;
  display:flex; align-items:center; gap:12px; flex-wrap:wrap;
}
.boundary-page .bd-error{ color:#d32f2f; font-size:13px; margin-top:10px; display:none; }
.boundary-page .bd-error.show{ display:block; }
.boundary-page .bd-disclaimer{ font-size:11.5px; color:var(--gray-500); line-height:1.5; margin-top:14px; }
@media (max-width:640px){
  .boundary-page .bd-container{ padding:16px 14px 60px; }
  .boundary-page .bd-do{ margin-left:0; }
}
</style>
@endpush

@section('content')
<div id="content">
    @include('layouts.top-nav')

    @php
        $hasItems = $assessment && $items->count() > 0;
        $step = !$assessment ? 1 : ($hasItems ? 3 : 2);
        $decided = $items->where('decision', '!=', 'pending')->count();
        $includedCount = $items->where('decision', 'included')->count();
        $actionRoutes = [
            'bill'     => ['route' => 'utility.create',              'label' => 'Upload a bill',      'icon' => 'fa-file-invoice'],
            'supplier' => ['route' => 'supplier_surveys.index',      'label' => 'Ask the supplier',   'icon' => 'fa-envelope'],
            'import'   => ['route' => 'emissions.import.form',       'label' => 'Import a file',      'icon' => 'fa-file-csv'],
            'spend'    => ['route' => 'emission_records.scope_entry','label' => 'Estimate from spend','icon' => 'fa-coins'],
            'manual'   => ['route' => 'emission_records.scope_entry','label' => 'Enter the data',     'icon' => 'fa-pen'],
        ];
        $scopeTitles = [
            1 => ['Scope 1 — what you burn and leak', 'Fuel and gases from things you own and operate'],
            2 => ['Scope 2 — the energy you buy',      'Electricity, cooling and heat you purchase'],
            3 => ['Scope 3 — your value chain',        'Everything upstream and downstream of your own operations'],
        ];
    @endphp

    <div class="boundary-page">
        <div class="bd-container">

            <div class="bd-hero">
                <div class="bd-title-row">
                    <div class="bd-title-icon"><i class="fas fa-compass-drafting"></i></div>
                    <div>
                        <h1>What does your company need to measure?</h1>
                        <div class="bd-sub">Tell us what your business actually does. We work out which emissions belong in your inventory, why, and where to find each number.</div>
                    </div>
                </div>
                <div class="bd-steps">
                    <div class="bd-step {{ $step > 1 ? 'done' : ($step === 1 ? 'current' : '') }}"><b>1</b> Your business</div>
                    <div class="bd-step {{ $step > 2 ? 'done' : ($step === 2 ? 'current' : '') }}"><b>2</b> A few questions</div>
                    <div class="bd-step {{ $step === 3 ? 'current' : '' }}"><b>3</b> Your boundary</div>
                    <div class="bd-step {{ $assessment && $assessment->status === 'active' ? 'done' : '' }}"><b>4</b> Close the gaps</div>
                </div>
            </div>

            {{--
                Continuation of the setup wizard, not a fresh visit. Shown only
                while onboarding_stage is 'boundary' — BoundaryController::
                activate() clears it, so this cannot outlive its usefulness.
            --}}
            @if(($fromOnboarding ?? false) && $step === 1)
                <div class="alert alert-success d-flex align-items-start gap-2">
                    <i class="fas fa-circle-check mt-1"></i>
                    <div>
                        <b>Setup saved.</b> Last step — we've carried over what you just told us, so check it reads
                        right and continue. What comes out of this decides which numbers you need to collect, so it is
                        worth the two minutes.
                    </div>
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-warning">{{ session('error') }}</div>
            @endif

            {{-- ============ STEP 1 — PROFILE ============ --}}
            <div class="bd-card" id="stepProfile" @if($step !== 1) hidden @endif>
                <h2>Tell us about your business</h2>
                <div class="bd-card-hint">Plain language is best. "We rent cars and vans to consumers from 12 branches" tells us far more than an industry category alone.</div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="bd-label" for="industryType">Industry</label>
                        <select class="form-select" id="industryType">
                            <option value="">Choose one…</option>
                            @foreach($industries as $value => $label)
                                <option value="{{ $value }}" @selected($company->industry_type === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="bd-label" for="subIndustry">More specifically <span class="text-muted" style="text-transform:none;font-weight:500;">(optional)</span></label>
                        <select class="form-select" id="subIndustry">
                            <option value="">Not listed / general</option>
                            @foreach($subIndustries as $sub)
                                <option value="{{ $sub }}" @selected($company->sub_industry === $sub)>{{ ucwords(str_replace('_', ' ', $sub)) }}</option>
                            @endforeach
                        </select>
                        <div class="bd-help">Picking this sharpens the result — a car-rental firm and a shipping line need very different boundaries.</div>
                    </div>
                    <div class="col-12">
                        <label class="bd-label" for="businessDescription">What does your company do?</label>
                        <textarea class="form-control" id="businessDescription" rows="3" maxlength="1000"
                                  placeholder="e.g. We are a general contractor building residential towers in Abu Dhabi. We own our site plant but subcontract concrete and steel work.">{{ $company->business_description }}</textarea>
                        <div class="bd-help">Mention what you own, what you outsource, and who your customers are. Those three things decide most of your boundary.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="bd-label" for="reportingYear">Reporting year</label>
                        <select class="form-select" id="reportingYear">
                            @for($y = $currentYear + 1; $y >= $currentYear - 3; $y--)
                                <option value="{{ $y }}" @selected($y === ($defaultReportingYear ?? $currentYear))>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <div class="bd-error" id="profileError"></div>

                <div class="bd-sticky">
                    <button type="button" class="bd-btn" id="startBtn">
                        <i class="fas fa-arrow-right"></i> <span>Continue</span>
                    </button>
                    @unless($aiEnabled)
                        <span class="bd-chip">Using industry templates — no AI provider configured</span>
                    @endunless
                </div>
            </div>

            {{-- ============ STEP 2 — QUESTIONS ============ --}}
            <div class="bd-card" id="stepQuestions" @if($step !== 2) hidden @endif data-assessment="{{ $assessment?->id }}">
                <h2>A few questions</h2>
                <div class="bd-card-hint">These are the questions whose answers change what you have to measure. If you are not sure, say so — we will flag it rather than guess.</div>

                <div id="questionList"></div>

                <div class="bd-error" id="questionError"></div>

                <div class="bd-sticky">
                    <button type="button" class="bd-btn" id="generateBtn">
                        <i class="fas fa-wand-magic-sparkles"></i> <span>Build my boundary</span>
                    </button>
                    <button type="button" class="bd-btn bd-btn-ghost" id="backBtn">Back</button>
                </div>
            </div>

            {{-- ============ STEP 3 — CHECKLIST ============ --}}
            @if($hasItems)
                <div id="stepChecklist">

                    @if($coverage && $coverage['has_boundary'] && $coverage['total'] > 0)
                        <div class="bd-coverage">
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <div>
                                    <div class="bd-cov-num">{{ $coverage['covered'] }} / {{ $coverage['total'] }}</div>
                                    <div class="bd-cov-label">boundary items with data for {{ $coverage['year'] }}</div>
                                </div>
                                <div class="bd-progress" style="max-width:320px;">
                                    <div class="bd-progress-fill" style="width: {{ $coverage['percent'] }}%"></div>
                                </div>
                                <div class="bd-progress-label">{{ $coverage['percent'] }}% complete</div>
                            </div>
                            @if($coverage['gaps']->isNotEmpty())
                                <div class="bd-item-hint mt-3">
                                    <i class="fas fa-circle-exclamation"></i>
                                    Next gap to close: <strong>{{ $coverage['gaps']->first()->suggested_name }}</strong> — {{ $coverage['gaps']->first()->data_hint }}
                                </div>
                            @endif
                        </div>
                    @endif

                    @if(!empty($diff) && $diff['has_changes'])
                        <div class="bd-card" style="border-left:4px solid {{ $diff['requires_recalculation'] ? '#e65100' : 'var(--primary-blue)' }}">
                            <h2>
                                <i class="fas fa-code-compare" style="color:{{ $diff['requires_recalculation'] ? '#e65100' : 'var(--primary-blue)' }}"></i>
                                What changed since {{ $diff['previous']['year'] }}
                            </h2>
                            <div class="bd-card-hint">
                                Your boundary went from {{ $diff['previous']['included'] }} to {{ $diff['current']['included'] }} measured sources.
                            </div>

                            @if($diff['added']->isNotEmpty())
                                <div class="bd-item-hint" style="background:rgba(46,125,50,.07)">
                                    <i class="fas fa-plus" style="color:var(--primary-green)"></i>
                                    <b>Added ({{ $diff['added']->count() }}):</b>
                                    {{ $diff['added']->pluck('suggested_name')->take(6)->implode(', ') }}{{ $diff['added']->count() > 6 ? ' +'.($diff['added']->count() - 6).' more' : '' }}
                                </div>
                            @endif

                            @if($diff['removed']->isNotEmpty())
                                <div class="bd-item-hint" style="background:var(--gray-100)">
                                    <i class="fas fa-minus" style="color:var(--gray-600)"></i>
                                    <b>No longer measured ({{ $diff['removed']->count() }}):</b>
                                    {{ $diff['removed']->pluck('suggested_name')->take(6)->implode(', ') }}{{ $diff['removed']->count() > 6 ? ' +'.($diff['removed']->count() - 6).' more' : '' }}
                                </div>
                            @endif

                            @if($diff['changed']->isNotEmpty())
                                <div class="bd-item-hint">
                                    <i class="fas fa-arrow-up-right-dots"></i>
                                    <b>Impact reassessed ({{ $diff['changed']->count() }}):</b>
                                    @foreach($diff['changed']->take(4) as $c)
                                        {{ $c['item']->suggested_name }} ({{ $c['from'] }} → {{ $c['to'] }}){{ !$loop->last ? ';' : '' }}
                                    @endforeach
                                </div>
                            @endif

                            @if($diff['requires_recalculation'])
                                <div class="bd-item-hint" style="background:#fff8e1;border:1px solid #ffe8a3">
                                    <i class="fas fa-triangle-exclamation" style="color:#e65100"></i>
                                    <b>Your base year may need recalculating.</b>
                                    <ul style="margin:8px 0 0 18px;padding:0">
                                        @foreach($diff['reasons'] as $reason)
                                            <li style="margin-bottom:4px">{{ $reason }}</li>
                                        @endforeach
                                    </ul>
                                    <div style="margin-top:8px;font-size:12px;color:var(--gray-600)">
                                        The GHG Protocol asks you to recalculate the base year when the boundary changes significantly, so
                                        year-on-year comparisons stay like-for-like. Whether these changes cross your significance threshold
                                        is your call to record.
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="bd-summary">
                        <div class="bd-summary-text">{{ $assessment->summary }}</div>
                        <div class="bd-meta">
                            <span class="bd-chip">{{ $assessment->reporting_year }}</span>
                            <span class="bd-chip">v{{ $assessment->version }}</span>
                            <span class="bd-chip">{{ ucfirst($assessment->status) }}</span>
                            <span class="bd-chip">
                                {{ $assessment->isAiGenerated() ? 'AI-assisted' : 'Industry templates' }}
                                @if($assessment->confidence) · {{ (int) round($assessment->confidence * 100) }}% confidence @endif
                            </span>
                            <span class="bd-chip">{{ $items->count() }} items</span>
                        </div>
                    </div>

                    <div class="bd-progress-wrap">
                        <span class="bd-progress-label" id="progressLabel">{{ $decided }} of {{ $items->count() }} decided</span>
                        <div class="bd-progress"><div class="bd-progress-fill" id="progressFill" style="width: {{ $items->count() ? round($decided / $items->count() * 100) : 0 }}%"></div></div>
                        @if($assessment->status !== 'active')
                            <button type="button" class="bd-mini" id="acceptAllBtn" data-assessment="{{ $assessment->id }}">
                                <i class="fas fa-check-double"></i> Accept all recommended
                            </button>
                        @endif
                    </div>

                    @foreach([1, 2, 3] as $scope)
                        @php $scopeItems = $items->where('scope', $scope); @endphp
                        @continue($scopeItems->isEmpty())

                        <div class="bd-scope-head">
                            <div class="bd-scope-badge" data-scope="{{ $scope }}">{{ $scope }}</div>
                            <div>
                                <div class="bd-scope-title">{{ $scopeTitles[$scope][0] }}</div>
                                <div class="bd-scope-sub">{{ $scopeTitles[$scope][1] }} · {{ $scopeItems->count() }} items</div>
                            </div>
                        </div>

                        @foreach($scopeItems as $item)
                            @php $action = $actionRoutes[$item->action_type] ?? $actionRoutes['manual']; @endphp
                            <div class="bd-item {{ $item->decision === 'included' ? 'is-included' : ($item->decision === 'excluded' ? 'is-excluded' : ($item->decision === 'deferred' ? 'is-deferred' : '')) }}"
                                 data-item="{{ $item->id }}">
                                <div class="bd-item-top">
                                    <div class="bd-item-main">
                                        <div class="bd-item-name">{{ $item->suggested_name }}</div>
                                        @if($item->scope3Category)
                                            <div class="bd-item-cat">Category {{ $item->scope3Category->sort_order }}: {{ $item->scope3Category->name }}</div>
                                        @elseif($item->suggested_unit)
                                            <div class="bd-item-cat">Measured in {{ $item->suggested_unit }}</div>
                                        @endif
                                    </div>
                                    <div class="bd-tags">
                                        @if($item->relevance === 'unknown')
                                            <span class="bd-tag bd-tag-unknown">Needs your call</span>
                                        @endif
                                        <span class="bd-tag bd-tag-{{ $item->materiality }}">{{ $item->materiality }} impact</span>
                                        @if($item->typical_share_pct)
                                            <span class="bd-tag bd-tag-low">~{{ (int) $item->typical_share_pct }}% of total</span>
                                        @endif
                                    </div>
                                </div>

                                @if($item->rationale)
                                    <div class="bd-item-why">{{ $item->rationale }}</div>
                                @endif

                                @if($item->data_hint)
                                    <div class="bd-item-hint"><i class="fas fa-location-dot"></i>{{ $item->data_hint }}</div>
                                @endif

                                @if($item->decision === 'excluded' && $item->exclusion_reason)
                                    <div class="bd-excluded-note">Excluded: {{ $item->exclusion_reason }}</div>
                                @endif

                                <div class="bd-actions">
                                    <button type="button" class="bd-mini decide-btn {{ $item->decision === 'included' ? 'active-in' : '' }}" data-decision="included">
                                        <i class="fas fa-check"></i> We measure this
                                    </button>
                                    <button type="button" class="bd-mini decide-btn {{ $item->decision === 'excluded' ? 'active-ex' : '' }}" data-decision="excluded">
                                        <i class="fas fa-xmark"></i> Does not apply
                                    </button>
                                    <button type="button" class="bd-mini decide-btn {{ $item->decision === 'deferred' ? 'active-df' : '' }}" data-decision="deferred">
                                        <i class="fas fa-clock"></i> Later
                                    </button>
                                    @if($item->decision === 'included')
                                        <a class="bd-do" href="{{ route($action['route']) }}">
                                            <i class="fas {{ $action['icon'] }}"></i> {{ $action['label'] }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endforeach

                    <div class="bd-sticky">
                        @if($assessment->status !== 'active')
                            <form method="POST" action="{{ route('boundary.activate', $assessment) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="bd-btn">
                                    <i class="fas fa-flag-checkered"></i> Set this as my boundary
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('boundary.statement', $assessment) }}" class="bd-btn bd-btn-ghost">
                            <i class="fas fa-file-pdf"></i> Boundary statement
                        </a>
                        <a href="{{ route('boundary.statement', ['assessment' => $assessment, 'format' => 'excel']) }}" class="bd-btn bd-btn-ghost">
                            <i class="fas fa-file-excel"></i> Scope 3 screening
                        </a>
                        <form method="POST" action="{{ route('boundary.restart') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="bd-btn bd-btn-ghost">Start over</button>
                        </form>
                        <span class="bd-progress-label" id="pendingNote">
                            {{ $includedCount }} in your boundary
                        </span>
                    </div>

                    <div class="bd-disclaimer">
                        This is a starting point for your review, not an assurance opinion. Every line is a suggestion until you accept it, and exclusions are recorded with your reason for audit purposes.
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>

{{-- Exclusion reason modal — a reason is required, it is the audit record --}}
<div class="modal fade" id="excludeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;">
            <div class="modal-header border-0">
                <h5 class="modal-title" style="font-weight:800;font-size:16px;">Why does this not apply?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-0">
                <p style="font-size:13px;color:var(--gray-600);line-height:1.55;">
                    Your reason is kept on record. An auditor reviewing your inventory will ask why this was left out.
                </p>
                <textarea class="form-control" id="exclusionReason" rows="3" maxlength="1000"
                          placeholder="e.g. We do not sell any physical products, so there is nothing for customers to dispose of."></textarea>
                <div class="bd-error" id="excludeError"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="bd-mini" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="bd-btn" id="confirmExclude">Save reason</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  var csrf = document.querySelector('meta[name="csrf-token"]');
  csrf = csrf ? csrf.getAttribute('content') : '';

  function post(url, body, method){
    return fetch(url, {
      method: method || 'POST',
      headers: { 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN': csrf },
      body: JSON.stringify(body || {})
    }).then(async function(r){
      var j = await r.json().catch(function(){ return {}; });
      if (!r.ok) throw j;
      return j;
    });
  }

  function firstError(e, fallback){
    if (e && e.errors) {
      for (var k in e.errors) { if (e.errors[k] && e.errors[k][0]) return e.errors[k][0]; }
    }
    return (e && e.message) ? e.message : fallback;
  }

  function showErr(el, msg){ if(!el) return; el.textContent = msg; el.classList.add('show'); }
  function clearErr(el){ if(!el) return; el.textContent = ''; el.classList.remove('show'); }

  /* ---------- Step 1: profile ---------- */
  var startBtn = document.getElementById('startBtn');
  if (startBtn) {
    startBtn.addEventListener('click', function(){
      var err = document.getElementById('profileError');
      clearErr(err);

      var payload = {
        industry_type: document.getElementById('industryType').value,
        sub_industry: document.getElementById('subIndustry').value || null,
        business_description: document.getElementById('businessDescription').value.trim(),
        reporting_year: document.getElementById('reportingYear').value
      };

      if (!payload.industry_type) { showErr(err, 'Choose your industry to continue.'); return; }
      if (payload.business_description.length < 10) { showErr(err, 'Tell us in a sentence or two what your company does.'); return; }

      startBtn.disabled = true;
      startBtn.querySelector('span').textContent = 'Thinking…';

      post("{{ route('boundary.start') }}", payload)
        .then(function(j){
          renderQuestions(j.questions, j.assessment_id);
          document.getElementById('stepProfile').hidden = true;
          document.getElementById('stepQuestions').hidden = false;
          window.scrollTo({ top: 0, behavior: 'smooth' });
        })
        .catch(function(e){ showErr(err, firstError(e, 'Could not start. Please try again.')); })
        .finally(function(){
          startBtn.disabled = false;
          startBtn.querySelector('span').textContent = 'Continue';
        });
    });
  }

  /* ---------- Step 2: questions ---------- */
  var answers = {};

  function renderQuestions(questions, assessmentId){
    var list = document.getElementById('questionList');
    var wrap = document.getElementById('stepQuestions');
    wrap.setAttribute('data-assessment', assessmentId);
    list.innerHTML = '';
    answers = {};

    questions.forEach(function(q){
      var box = document.createElement('div');
      box.className = 'bd-q';

      var title = document.createElement('div');
      title.className = 'bd-q-title';
      title.textContent = q.question;
      if (q.source === 'ai') {
        var tag = document.createElement('span');
        tag.className = 'bd-ai-tag';
        tag.textContent = 'about you';
        title.appendChild(tag);
      }
      box.appendChild(title);

      if (q.help) {
        var help = document.createElement('div');
        help.className = 'bd-q-help';
        help.textContent = q.help;
        box.appendChild(help);
      }

      var opts = document.createElement('div');
      opts.className = 'bd-opts';

      q.options.forEach(function(o){
        var label = document.createElement('label');
        label.className = 'bd-opt';

        var input = document.createElement('input');
        input.type = 'radio';
        input.name = 'q_' + q.key;
        input.value = o.value;
        input.addEventListener('change', function(){
          answers[q.key] = o.value;
          opts.querySelectorAll('.bd-opt').forEach(function(el){ el.classList.remove('selected'); });
          label.classList.add('selected');
        });

        var text = document.createElement('span');
        text.textContent = o.label;

        label.appendChild(input);
        label.appendChild(text);
        opts.appendChild(label);
      });

      box.appendChild(opts);
      list.appendChild(box);
    });
  }

  var generateBtn = document.getElementById('generateBtn');
  if (generateBtn) {
    generateBtn.addEventListener('click', function(){
      var err = document.getElementById('questionError');
      clearErr(err);

      if (Object.keys(answers).length === 0) {
        showErr(err, 'Answer at least one question so we can tailor your boundary.');
        return;
      }

      var id = document.getElementById('stepQuestions').getAttribute('data-assessment');
      generateBtn.disabled = true;
      generateBtn.querySelector('span').textContent = 'Working it out…';

      post("{{ url('boundary') }}/" + id + "/generate", { answers: answers })
        .then(function(j){ window.location.href = j.redirect; })
        .catch(function(e){
          showErr(err, firstError(e, 'Could not build your boundary. Please try again.'));
          generateBtn.disabled = false;
          generateBtn.querySelector('span').textContent = 'Build my boundary';
        });
    });
  }

  var backBtn = document.getElementById('backBtn');
  if (backBtn) {
    backBtn.addEventListener('click', function(){
      document.getElementById('stepQuestions').hidden = true;
      document.getElementById('stepProfile').hidden = false;
    });
  }

  /* ---------- Step 3: decisions ---------- */
  var pendingExclude = null;
  var excludeModal = document.getElementById('excludeModal')
    ? new bootstrap.Modal(document.getElementById('excludeModal'))
    : null;

  function applyDecision(card, decision, reason){
    var id = card.getAttribute('data-item');

    return post("{{ url('boundary/items') }}/" + id, {
      decision: decision,
      exclusion_reason: reason || null
    }, 'PATCH').then(function(j){
      card.classList.remove('is-included','is-excluded','is-deferred');
      if (decision === 'included') card.classList.add('is-included');
      if (decision === 'excluded') card.classList.add('is-excluded');
      if (decision === 'deferred') card.classList.add('is-deferred');

      card.querySelectorAll('.decide-btn').forEach(function(b){
        b.classList.remove('active-in','active-ex','active-df');
        if (b.getAttribute('data-decision') === decision) {
          b.classList.add(decision === 'included' ? 'active-in' : (decision === 'excluded' ? 'active-ex' : 'active-df'));
        }
      });

      updateProgress(j.progress);
      return j;
    });
  }

  function updateProgress(p){
    if (!p || !p.total) return;
    var fill = document.getElementById('progressFill');
    var label = document.getElementById('progressLabel');
    var note = document.getElementById('pendingNote');
    if (fill) fill.style.width = Math.round(p.decided / p.total * 100) + '%';
    if (label) label.textContent = p.decided + ' of ' + p.total + ' decided';
    if (note) note.textContent = p.included + ' in your boundary';
  }

  document.querySelectorAll('.decide-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
      var card = btn.closest('.bd-item');
      var decision = btn.getAttribute('data-decision');

      if (decision === 'excluded') {
        pendingExclude = card;
        document.getElementById('exclusionReason').value = '';
        clearErr(document.getElementById('excludeError'));
        if (excludeModal) excludeModal.show();
        return;
      }

      applyDecision(card, decision).catch(function(e){
        alert(firstError(e, 'Could not save that. Please try again.'));
      });
    });
  });

  var confirmExclude = document.getElementById('confirmExclude');
  if (confirmExclude) {
    confirmExclude.addEventListener('click', function(){
      var err = document.getElementById('excludeError');
      var reason = document.getElementById('exclusionReason').value.trim();
      clearErr(err);

      if (reason.length < 5) { showErr(err, 'Give a short reason — it goes on the audit record.'); return; }
      if (!pendingExclude) return;

      applyDecision(pendingExclude, 'excluded', reason)
        .then(function(){
          var note = pendingExclude.querySelector('.bd-excluded-note');
          if (!note) {
            note = document.createElement('div');
            note.className = 'bd-excluded-note';
            pendingExclude.querySelector('.bd-actions').before(note);
          }
          note.textContent = 'Excluded: ' + reason;
          if (excludeModal) excludeModal.hide();
          pendingExclude = null;
        })
        .catch(function(e){ showErr(err, firstError(e, 'Could not save that reason.')); });
    });
  }

  var acceptAllBtn = document.getElementById('acceptAllBtn');
  if (acceptAllBtn) {
    acceptAllBtn.addEventListener('click', function(){
      var id = acceptAllBtn.getAttribute('data-assessment');
      acceptAllBtn.disabled = true;

      post("{{ url('boundary') }}/" + id + "/accept-recommended")
        .then(function(){ window.location.reload(); })
        .catch(function(e){
          alert(firstError(e, 'Could not accept the recommendations.'));
          acceptAllBtn.disabled = false;
        });
    });
  }
})();
</script>
@endpush

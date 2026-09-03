<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Welcome — Let's set up {{ $company->name }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root{
            --primary-green:#2e7d32; --light-green:#4caf50; --dark-green:#1b5e20;
            --gray-50:#f9fafb; --gray-100:#f3f4f6; --gray-200:#e5e7eb; --gray-300:#d1d5db;
            --gray-500:#6b7280; --gray-600:#4b5563; --gray-700:#374151; --gray-800:#1f2937;
        }
        *{box-sizing:border-box}
        body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:linear-gradient(135deg,#f0f7f1 0%,#e8f1ff 100%);min-height:100vh;margin:0;color:var(--gray-800)}
        .wiz-wrap{max-width:760px;margin:0 auto;padding:32px 16px 64px}
        .brand{display:flex;align-items:center;gap:12px;justify-content:center;margin-bottom:24px}
        .brand .logo{width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,var(--primary-green),var(--light-green));color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.2rem;box-shadow:0 4px 12px rgba(46,125,50,.3)}
        .brand h1{font-size:1.15rem;font-weight:700;margin:0}
        /* Progress */
        .steps{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;gap:6px}
        .steps .dot{flex:1;height:6px;border-radius:99px;background:var(--gray-200);transition:.3s}
        .steps .dot.active{background:linear-gradient(90deg,var(--primary-green),var(--light-green))}
        /* Card */
        .card-wiz{background:#fff;border:1px solid var(--gray-200);border-radius:20px;box-shadow:0 8px 30px rgba(0,0,0,.07);overflow:hidden}
        .card-wiz .body{padding:36px 36px 28px}
        .step-eyebrow{font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--primary-green);margin-bottom:8px}
        .step-title{font-size:1.5rem;font-weight:700;letter-spacing:-.02em;margin:0 0 8px}
        .step-sub{color:var(--gray-600);font-size:.95rem;margin-bottom:24px;line-height:1.5}
        .form-label{font-weight:600;font-size:.875rem;color:var(--gray-700)}
        .form-control,.form-select{border-radius:10px;border:1px solid var(--gray-300);padding:10px 14px;font-size:.925rem}
        .form-control:focus,.form-select:focus{border-color:var(--light-green);box-shadow:0 0 0 .2rem rgba(76,175,80,.15)}
        .hint{font-size:.8rem;color:var(--gray-500)}
        /* Benefit list */
        .benefit{display:flex;gap:14px;align-items:flex-start;padding:14px 0;border-bottom:1px solid var(--gray-100)}
        .benefit:last-child{border-bottom:none}
        .benefit .ic{width:40px;height:40px;flex:none;border-radius:11px;background:rgba(76,175,80,.12);color:var(--primary-green);display:flex;align-items:center;justify-content:center;font-size:1.05rem}
        .benefit b{display:block;font-size:.95rem}
        .benefit span{font-size:.85rem;color:var(--gray-600)}
        /* Activity tiles */
        .activities{display:grid;grid-template-columns:1fr 1fr;gap:12px}
        @media(max-width:560px){.activities{grid-template-columns:1fr}}
        .act{position:relative;border:1.5px solid var(--gray-200);border-radius:14px;padding:14px 14px 14px 48px;cursor:pointer;transition:.15s;background:#fff}
        .act:hover{border-color:var(--light-green);background:var(--gray-50)}
        .act input{position:absolute;opacity:0}
        .act .ic{position:absolute;left:14px;top:15px;color:var(--gray-500);font-size:1rem;transition:.15s}
        .act b{display:block;font-size:.9rem;font-weight:600}
        .act small{color:var(--gray-500);font-size:.78rem;line-height:1.3;display:block;margin-top:2px}
        .act.selected,.act:has(input:checked){border-color:var(--primary-green);background:rgba(76,175,80,.07)}
        .act:has(input:checked) .ic{color:var(--primary-green)}
        .act:has(input:checked) .check{background:var(--primary-green)}
        .act.selected .ic{color:var(--primary-green)}
        .act .check{position:absolute;right:12px;top:12px;width:20px;height:20px;border-radius:50%;background:var(--gray-200);color:#fff;display:flex;align-items:center;justify-content:center;font-size:.6rem;transition:.15s}
        .act.selected .check{background:var(--primary-green)}
        /* Site rows */
        .site-row{display:flex;gap:10px;margin-bottom:10px;align-items:center}
        .site-row .form-control{flex:1}
        .btn-remove{border:none;background:rgba(211,47,47,.1);color:#d32f2f;width:40px;height:40px;border-radius:10px;flex:none}
        /* Footer */
        .foot{display:flex;align-items:center;justify-content:space-between;padding:18px 36px;background:var(--gray-50);border-top:1px solid var(--gray-200)}
        .btn-green{background:linear-gradient(135deg,var(--primary-green),var(--light-green));border:none;color:#fff;font-weight:600;padding:11px 26px;border-radius:11px}
        .btn-green:hover{background:linear-gradient(135deg,var(--dark-green),var(--primary-green));color:#fff}
        .btn-ghost{background:none;border:none;color:var(--gray-600);font-weight:600;font-size:.875rem}
        .btn-ghost:hover{color:var(--gray-800)}
        .skip-link{display:block;text-align:center;margin-top:18px;font-size:.82rem;color:var(--gray-500)}
        .skip-link a{color:var(--gray-600);text-decoration:underline}
        .summary-li{display:flex;gap:10px;align-items:center;padding:10px 0;font-size:.92rem}
        .summary-li i{color:var(--light-green)}
        .invalid-msg{color:#d32f2f;font-size:.82rem;margin-top:6px;display:none}
    </style>
</head>
<body>
<div class="wiz-wrap">
    <div class="brand">
        <div class="logo"><i class="fas fa-leaf"></i></div>
        <h1>Carbon Setup</h1>
    </div>

    <div class="steps">
        <div class="dot active" data-dot="1"></div>
        <div class="dot" data-dot="2"></div>
        <div class="dot" data-dot="3"></div>
        <div class="dot" data-dot="4"></div>
        <div class="dot" data-dot="5"></div>
        <div class="dot" data-dot="6"></div>
    </div>

    <form id="wizForm" class="card-wiz" autocomplete="off">
        <!-- ============ STEP 1 — WELCOME ============ -->
        <section class="body step" data-step="1">
            <div class="step-eyebrow">Step 1 of 6</div>
            <h2 class="step-title">Welcome, {{ $company->name }} 👋</h2>
            <p class="step-sub">We'll measure your company's carbon footprint for you. You don't need to know anything about "emission factors" or "scopes" — just tell us about your business and we'll handle the science. It takes about 2 minutes.</p>

            <div class="benefit">
                <div class="ic"><i class="fas fa-circle-check"></i></div>
                <div><b>No carbon expertise needed</b><span>Enter ordinary data you already have — bills, fuel, travel. We pick the right calculations.</span></div>
            </div>
            <div class="benefit">
                <div class="ic"><i class="fas fa-file-shield"></i></div>
                <div><b>Audit-ready reports</b><span>Produce the figures regulators (CSRD, CDP, GRI) ask for — without hiring a consultant.</span></div>
            </div>
            <div class="benefit">
                <div class="ic"><i class="fas fa-arrow-trend-down"></i></div>
                <div><b>See how to cut emissions</b><span>We highlight your biggest sources and what to do about them.</span></div>
            </div>
        </section>

        <!-- ============ STEP 2 — BUSINESS ============ -->
        <section class="body step" data-step="2" hidden>
            <div class="step-eyebrow">Step 2 of 6</div>
            <h2 class="step-title">Tell us about your business</h2>
            <p class="step-sub">This helps us pre-load the right starting figures for your industry.</p>

            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label">Company name</label>
                    <input type="text" name="name" class="form-control" value="{{ $company->name }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">What industry are you in?</label>
                    <select name="industry_type" class="form-select" required>
                        <option value="">Choose one…</option>
                        @foreach($industries as $val => $label)
                            <option value="{{ $val }}" @selected($company->industry_type === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Country</label>
                    <input type="text" name="country" class="form-control" value="{{ $company->country }}" placeholder="e.g. United Arab Emirates">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Roughly how many employees? <span class="hint">(optional)</span></label>
                    <input type="number" name="employee_count" class="form-control" min="0" value="{{ $company->employee_count }}" placeholder="e.g. 120">
                </div>
                <div class="col-md-6">
                    <label class="form-label">When does your financial year start? <span class="hint">(optional)</span></label>
                    <input type="text" name="fiscal_year_start" class="form-control" value="{{ $company->fiscal_year_start }}" placeholder="e.g. 01-01 (Jan) or 04-01 (Apr)">
                </div>
                {{--
                    Asked here so the Boundary Advisor does not have to ask it
                    again on the very next screen. It is the field its interview
                    is built on, and a client who has just described their
                    business twice has learned that this product does not listen.
                --}}
                <div class="col-12">
                    <label class="form-label">In your own words, what does your company do?</label>
                    <textarea name="business_description" class="form-control" rows="3" maxlength="1000"
                              placeholder="e.g. We are a general contractor building residential towers in Abu Dhabi. We own our site plant but subcontract concrete and steel work.">{{ $company->business_description }}</textarea>
                    <div class="hint mt-1">Mention what you own, what you outsource, and who your customers are — those three things decide most of your footprint. A sentence or two is plenty.</div>
                </div>
            </div>
            <div class="invalid-msg" id="err2">Please fill in your company name, industry, and a sentence about what you do.</div>
        </section>

        <!-- ============ STEP 3 — REPORTING BASIS ============ -->
        <section class="body step" data-step="3" hidden>
            <div class="step-eyebrow">Step 3 of 6</div>
            <h2 class="step-title">How should we report your footprint?</h2>
            <p class="step-sub">Just three quick choices. We've picked the most common answer for each — if you're not sure, the defaults are fine and you can change them later.</p>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Which year do you want to measure first?</label>
                    <input type="number" name="base_year" class="form-control" min="2000" max="{{ $currentYear + 1 }}" value="{{ $defaultBaseYear }}">
                    <div class="hint mt-1">Your “base year” — the baseline we compare future progress against.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Which standard for greenhouse-gas values?</label>
                    <select name="gwp_version" class="form-select">
                        @foreach($gwpOptions as $val => $label)
                            <option value="{{ $val }}" @selected($defaultGwp === $val)>{{ $label }}@if($loop->first) — recommended @endif</option>
                        @endforeach
                    </select>
                    <div class="hint mt-1">The IPCC values used to convert gases to CO₂e. The latest is best unless a regulator requires otherwise.</div>
                </div>
                <div class="col-12">
                    <label class="form-label">Which operations should we count?</label>
                    <div class="mt-1">
                        @foreach($boundaries as $val => $label)
                            <label class="act" style="padding:12px 14px 12px 44px;margin-bottom:8px;display:block">
                                <input type="radio" name="consolidation_approach" value="{{ $val }}" @checked($defaultBoundary === $val)>
                                <i class="fas fa-sitemap ic" style="top:13px"></i>
                                <span class="check"><i class="fas fa-check"></i></span>
                                <b>{{ $label }}@if($loop->first) <span class="hint">(recommended)</span>@endif</b>
                                <small>{{ config("boundary.help.$val") }}</small>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <!-- ============ STEP 4 — SITES ============ -->
        <section class="body step" data-step="4" hidden>
            <div class="step-eyebrow">Step 4 of 6</div>
            <h2 class="step-title">Where do you operate?</h2>
            <p class="step-sub">Add your offices, factories, stores or sites. You can add more later — one is enough to start.</p>

            <div id="sitesContainer">
                <div class="site-row">
                    <input type="text" class="form-control site-name" placeholder="Site name (e.g. Head Office)" required>
                    <input type="text" class="form-control site-loc" placeholder="Location (e.g. Dubai) — optional">
                    <button type="button" class="btn-remove removeSite" style="display:none"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-ghost mt-1" id="addSite"><i class="fas fa-plus me-1"></i> Add another location</button>
            <div class="invalid-msg" id="err3">Please give each location a name.</div>
        </section>

        <!-- ============ STEP 5 — ACTIVITIES ============ -->
        <section class="body step" data-step="5" hidden>
            <div class="step-eyebrow">Step 5 of 6</div>
            <h2 class="step-title">What does your business do?</h2>
            <p class="step-sub">Tick everything that applies. We'll use this to set up the right categories — you don't need to worry about which "scope" anything is.</p>

            <div class="activities">
                @foreach($activities as $key => $a)
                    <label class="act">
                        <input type="checkbox" class="act-check" value="{{ $key }}">
                        <i class="fas {{ $a['icon'] }} ic"></i>
                        <span class="check"><i class="fas fa-check"></i></span>
                        <b>{{ $a['label'] }}</b>
                        <small>{{ $a['help'] }}</small>
                    </label>
                @endforeach
            </div>
            <div class="invalid-msg" id="err4">Please pick at least one.</div>
        </section>

        <!-- ============ STEP 6 — DONE ============ -->
        <section class="body step" data-step="6" hidden>
            <div class="step-eyebrow">Step 6 of 6</div>
            <h2 class="step-title">Your account is ready 🎉</h2>
            <p class="step-sub">Based on what you told us, here's what we'll be measuring:</p>

            <div id="checklist"></div>

            <p class="step-sub mt-3 mb-0">
                <i class="fas fa-compass-drafting text-success me-1"></i>
                <b>One thing left.</b> Before entering any numbers, we'll spend two minutes agreeing exactly which
                emissions belong in your footprint and which don't — that's the part auditors ask about, and it decides
                what data you actually need to collect. We've already filled in what you just told us.
            </p>
        </section>

        <!-- ============ FOOTER ============ -->
        <div class="foot">
            <button type="button" class="btn-ghost" id="backBtn" style="visibility:hidden"><i class="fas fa-arrow-left me-1"></i> Back</button>
            <button type="button" class="btn-green" id="nextBtn">Get started <i class="fas fa-arrow-right ms-1"></i></button>
        </div>
    </form>

    <div class="skip-link">
        <a href="#" id="skipLink">Skip setup for now</a>
    </div>
</div>

<form id="skipForm" action="{{ route('onboarding.skip') }}" method="POST" class="d-none">@csrf</form>

<script>
(function(){
    const TOTAL = 6;
    let step = 1;
    const ACTIVITY_SCOPES = @json(collect($activities)->map(fn($a,$k)=>$a['label'])->toArray());

    const form = document.getElementById('wizForm');
    const nextBtn = document.getElementById('nextBtn');
    const backBtn = document.getElementById('backBtn');
    const dots = [...document.querySelectorAll('.dot')];

    function show(n){
        step = n;
        document.querySelectorAll('.step').forEach(s=>{
            s.hidden = (+s.dataset.step !== n);
        });
        dots.forEach(d=> d.classList.toggle('active', +d.dataset.dot <= n));
        backBtn.style.visibility = n === 1 ? 'hidden' : 'visible';
        nextBtn.innerHTML = n === 1 ? 'Get started <i class="fas fa-arrow-right ms-1"></i>'
                          : n === TOTAL ? 'Scope what I measure <i class="fas fa-arrow-right ms-1"></i>'
                          : 'Continue <i class="fas fa-arrow-right ms-1"></i>';
        document.querySelectorAll('.invalid-msg').forEach(m=>m.style.display='none');
        window.scrollTo({top:0,behavior:'smooth'});
    }

    // ---- Activity tiles: visual selected state ----
    document.querySelectorAll('.act-check').forEach(cb=>{
        cb.addEventListener('change',()=> cb.closest('.act').classList.toggle('selected', cb.checked));
    });

    // ---- Sites: add / remove ----
    const sitesContainer = document.getElementById('sitesContainer');
    document.getElementById('addSite').addEventListener('click',()=>{
        const row = sitesContainer.firstElementChild.cloneNode(true);
        row.querySelectorAll('input').forEach(i=>i.value='');
        row.querySelector('.removeSite').style.display='';
        sitesContainer.appendChild(row);
        refreshRemoveButtons();
    });
    sitesContainer.addEventListener('click',e=>{
        if(e.target.closest('.removeSite') && sitesContainer.children.length>1){
            e.target.closest('.site-row').remove();
            refreshRemoveButtons();
        }
    });
    function refreshRemoveButtons(){
        const rows=[...sitesContainer.children];
        rows.forEach(r=> r.querySelector('.removeSite').style.display = rows.length>1 ? '' : 'none');
    }

    function collectSites(){
        return [...sitesContainer.children].map(r=>({
            name: r.querySelector('.site-name').value.trim(),
            location: r.querySelector('.site-loc').value.trim()
        })).filter(s=>s.name);
    }
    function selectedActivities(){
        return [...document.querySelectorAll('.act-check:checked')].map(c=>c.value);
    }

    // ---- Validation per step ----
    function validate(n){
        if(n===2){
            const name=form.name.value.trim(), ind=form.industry_type.value;
            // The description is optional to the API (so the skip path and any
            // older client still work) but insisted on here: it is what the
            // Boundary Advisor on the next screen runs its interview from, and
            // a blank one costs the client the whole benefit of the handoff.
            const desc=form.business_description.value.trim();
            if(!name || !ind || desc.length < 10){ document.getElementById('err2').style.display='block'; return false; }
        }
        if(n===4){
            if(collectSites().length===0){ document.getElementById('err3').style.display='block'; return false; }
        }
        if(n===5){
            if(selectedActivities().length===0){ document.getElementById('err4').style.display='block'; return false; }
        }
        return true;
    }

    function buildChecklist(){
        const acts = selectedActivities();
        const labelMap = @json(collect($activities)->map(fn($a)=>$a['label'])->toArray());
        const box = document.getElementById('checklist');
        let html = '';
        const items = acts.length ? acts.map(a=>labelMap[a]) : ['Add your first activity'];
        items.forEach(t=>{
            html += '<div class="summary-li"><i class="far fa-square"></i> Enter your '+ t.replace(/^We |^Our staff /,'').toLowerCase() +' data</div>';
        });
        box.innerHTML = html;
    }

    nextBtn.addEventListener('click', async ()=>{
        if(step < TOTAL){
            if(!validate(step)) return;
            if(step===5) buildChecklist();
            show(step+1);
            return;
        }
        // Final step → submit everything
        await submit();
    });
    backBtn.addEventListener('click',()=> step>1 && show(step-1));

    document.getElementById('skipLink').addEventListener('click',e=>{
        e.preventDefault();
        document.getElementById('skipForm').submit();
    });

    async function submit(){
        nextBtn.disabled = true;
        nextBtn.innerHTML = 'Setting up… <i class="fas fa-spinner fa-spin ms-1"></i>';
        const payload = {
            name: form.name.value.trim(),
            industry_type: form.industry_type.value,
            business_description: form.business_description.value.trim() || null,
            country: form.country.value.trim(),
            employee_count: form.employee_count.value || null,
            fiscal_year_start: form.fiscal_year_start.value.trim() || null,
            base_year: form.base_year.value || null,
            gwp_version: form.gwp_version.value || null,
            consolidation_approach: (form.querySelector('input[name=consolidation_approach]:checked') || {}).value || null,
            sites: collectSites(),
            activities: selectedActivities()
        };
        try{
            const res = await fetch('{{ route('onboarding.save') }}',{
                method:'POST',
                headers:{
                    'Content-Type':'application/json',
                    'Accept':'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if(res.ok && data.success){
                window.location = data.redirect || '{{ route('home') }}';
            }else{
                alert(data.message || 'Something went wrong. Please review your answers.');
                nextBtn.disabled=false;
                nextBtn.innerHTML='Scope what I measure <i class="fas fa-arrow-right ms-1"></i>';
            }
        }catch(err){
            alert('Network error — please try again.');
            nextBtn.disabled=false;
            nextBtn.innerHTML='Scope what I measure <i class="fas fa-arrow-right ms-1"></i>';
        }
    }

    show(1);
})();
</script>
</body>
</html>

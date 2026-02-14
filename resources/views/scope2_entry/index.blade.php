@extends('layouts.app')

@section('title', 'Scope 2 Entry')
@section('page-title', 'Scope 2 - Purchased Energy')

@push('styles')
<style>
/* Scope 2 Entry - same color scheme and style as Scope 1 (primary-green, grays) */
.scope2-app * { box-sizing: border-box; }
.scope2-app { font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; }
.scope2-app .topbar { display:flex; align-items:center; gap:16px; margin-bottom:24px; flex-wrap:wrap; padding:20px 24px; background:linear-gradient(135deg,#fff 0%,var(--gray-50) 100%); border:1px solid var(--gray-200); border-radius:16px; box-shadow:0 2px 8px rgba(0,0,0,.06); }
.scope2-app .topbar h2 { font-size:1.35rem; font-weight:700; letter-spacing:-.02em; display:flex; align-items:center; gap:10px; margin:0; color:var(--gray-800); }
.scope2-app .topbar h2 .sb { display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:10px; background:linear-gradient(135deg,var(--primary-green) 0%,var(--light-green) 100%); color:#fff; font-size:1rem; font-weight:700; box-shadow:0 2px 8px rgba(46,125,50,.25); }
.scope2-app .topbar p { color:var(--gray-600); font-size:0.875rem; flex:1; min-width:180px; margin:0; line-height:1.4; }
.scope2-app .btn-add { margin-left:auto; padding:10px 20px; border-radius:10px; background:linear-gradient(135deg,var(--primary-green) 0%,var(--light-green) 100%); color:#fff; border:none; font-size:0.875rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:8px; white-space:nowrap; box-shadow:0 2px 8px rgba(46,125,50,.25); transition:transform .2s,box-shadow .2s; }
.scope2-app .btn-add:hover { background:linear-gradient(135deg,var(--dark-green) 0%,var(--primary-green) 100%); color:#fff; transform:translateY(-1px); box-shadow:0 4px 12px rgba(46,125,50,.35); }
.scope2-app .stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px; margin-bottom:24px; }
.scope2-app .sc { background:#fff; border:1px solid var(--gray-200); border-radius:14px; padding:18px 20px; display:flex; align-items:center; gap:14px; box-shadow:0 2px 8px rgba(0,0,0,.06); transition:transform .2s,box-shadow .2s; }
.scope2-app .sc:hover { transform:translateY(-2px); box-shadow:0 4px 16px rgba(0,0,0,.08); }
.scope2-app .sc .si { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.25rem; flex-shrink:0; }
.scope2-app .sc .si.a { background:linear-gradient(135deg,rgba(46,125,50,.15) 0%,rgba(76,175,80,.12) 100%); }
.scope2-app .sc .si.b { background:linear-gradient(135deg,rgba(76,175,80,.2) 0%,rgba(129,199,132,.15) 100%); }
.scope2-app .sc .si.r { background:linear-gradient(135deg,rgba(245,124,0,.15) 0%,rgba(255,152,0,.1) 100%); }
.scope2-app .sc .si.g { background:linear-gradient(135deg,rgba(2,119,189,.12) 0%,rgba(3,169,244,.1) 100%); }
.scope2-app .sc .sv { font-size:1.5rem; font-weight:700; letter-spacing:-.02em; color:var(--gray-800); }
.scope2-app .sc .sl { font-size:0.75rem; color:var(--gray-600); margin-top:2px; font-weight:500; text-transform:uppercase; letter-spacing:.04em; }
.scope2-app .ftabs { display:flex; gap:8px; margin-bottom:20px; flex-wrap:wrap; }
.scope2-app .ftab { padding:8px 16px; border-radius:100px; font-size:0.8125rem; font-weight:600; cursor:pointer; border:1.5px solid var(--gray-200); background:#fff; color:var(--gray-600); transition:all .2s; display:flex; align-items:center; gap:6px; box-shadow:0 1px 3px rgba(0,0,0,.04); }
.scope2-app .ftab:hover { border-color:var(--gray-300); background:var(--gray-50); color:var(--gray-800); }
.scope2-app .ftab.on { background:rgba(46,125,50,0.1); border-color:rgba(46,125,50,0.35); color:var(--primary-green); box-shadow:0 2px 6px rgba(46,125,50,.12); }
.scope2-app .ftab .cn { font-size:0.625rem; background:var(--gray-100); padding:2px 6px; border-radius:100px; font-weight:700; }
.scope2-app .ftab.on .cn { background:rgba(46,125,50,0.2); color:var(--dark-green); }
.scope2-app .tw { background:#fff; border:1px solid var(--gray-200); border-radius:16px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.06); }
.scope2-app .th2 { display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid var(--gray-200); flex-wrap:wrap; gap:10px; background:linear-gradient(180deg,var(--gray-50) 0%,#fff 100%); }
.scope2-app .th2 h3 { font-size:1rem; font-weight:700; color:var(--gray-800); margin:0; }
.scope2-app .tw .p-3 { padding:20px; }
.scope2-app #scope2Table_wrapper { margin:0 -4px; }
.scope2-app #scope2Table thead th { background:var(--gray-100); color:var(--gray-600); font-size:0.6875rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; padding:12px 14px; border-bottom:1px solid var(--gray-200); }
.scope2-app #scope2Table tbody td { padding:12px 14px; font-size:0.875rem; border-bottom:1px solid var(--gray-100); vertical-align:middle; }
.scope2-app #scope2Table tbody tr:hover td { background:var(--gray-50); }
.scope2-app #scope2Table tbody tr:last-child td { border-bottom:none; }
/* Modal */
#scope2Ov { position:fixed; inset:0; background:rgba(12,18,30,.5); backdrop-filter:blur(3px); z-index:1050; display:none; align-items:flex-start; justify-content:center; padding:20px 12px; overflow-y:auto; opacity:0; transition:opacity .2s; }
#scope2Ov.open { display:flex !important; opacity:1; }
#scope2Ov .mdl { background:#fff; border-radius:16px; width:100%; max-width:920px; min-height:520px; box-shadow:0 20px 60px rgba(0,0,0,.12); margin:auto; overflow:hidden; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; }
#scope2Ov .mh { padding:18px 20px 14px; display:flex; align-items:flex-start; gap:12px; flex-wrap:wrap; border-bottom:1px solid var(--gray-200); }
#scope2Ov .mh .mi { width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; background:rgba(46,125,50,0.12); }
#scope2Ov .mh .mt { flex:1; min-width:0; }
#scope2Ov .mh .mt h2 { font-size:17px; font-weight:700; margin:0 0 2px 0; color:var(--gray-800); }
#scope2Ov .mh .mt span { font-size:12px; color:var(--gray-600); display:block; }
#scope2Ov .mh .mx { width:36px; height:36px; border-radius:8px; border:1px solid var(--gray-200); background:#fff; cursor:pointer; display:flex; align-items:center; justify-content:center; color:var(--gray-600); font-size:18px; flex-shrink:0; margin-left:auto; }
#scope2Ov .mh .mx:hover { background:var(--gray-100); border-color:var(--gray-300); color:var(--gray-800); }
#scope2Ov .stp { display:flex; padding:14px 20px 0; gap:0; border-bottom:1px solid var(--gray-200); }
#scope2Ov .st { flex:1; text-align:center; padding-bottom:12px; position:relative; }
#scope2Ov .st .sb3 { position:absolute; bottom:0; left:0; right:0; height:3px; background:var(--gray-200); border-radius:2px; }
#scope2Ov .st.dn .sb3 { background:var(--light-green); }
#scope2Ov .st.ac2 .sb3 { background:var(--primary-green); }
#scope2Ov .st .sn2 { width:24px; height:24px; border-radius:50%; border:2px solid var(--gray-200); display:inline-flex; align-items:center; justify-content:center; font-size:10px; font-weight:700; color:var(--gray-600); margin-bottom:4px; }
#scope2Ov .st.ac2 .sn2 { border-color:var(--primary-green); background:var(--primary-green); color:#fff; }
#scope2Ov .st.dn .sn2 { border-color:var(--light-green); background:var(--light-green); color:#fff; }
#scope2Ov .st .stx { font-size:11px; font-weight:600; color:var(--gray-600); }
#scope2Ov .st.ac2 .stx { color:var(--primary-green); }
#scope2Ov .st.dn .stx { color:var(--light-green); }
#scope2Ov .fp { padding:16px 20px 20px; display:none !important; }
#scope2Ov .fp.show { display:block !important; }
#scope2Ov .suc { display:none !important; padding:0; margin:0; }
#scope2Ov .suc.on { display:block !important; padding:32px 20px 24px; }
#scope2Ov .fg { margin-bottom:14px; }
#scope2Ov .fg label { display:block; font-size:13px; font-weight:600; margin-bottom:4px; color:var(--gray-800); }
#scope2Ov .fg label .rq { color:var(--danger-red); }
#scope2Ov .scb { display:flex; gap:8px; margin-bottom:12px; flex-wrap:wrap; }
#scope2Ov .scbt { padding:8px 16px; border-radius:100px; font-size:13px; font-weight:600; cursor:pointer; border:2px solid var(--gray-200); background:#fff; color:var(--gray-600); transition:all .15s; }
#scope2Ov .scbt:hover { border-color:var(--gray-300); background:var(--gray-50); }
#scope2Ov .scbt.on { background:rgba(46,125,50,0.1); border-color:rgba(46,125,50,0.3); color:var(--primary-green); }
#scope2Ov .sg { display:grid; grid-template-columns:1fr 1fr; gap:8px; max-height:220px; overflow-y:auto; padding-right:4px; }
#scope2Ov .sc2 { padding:12px; border:2px solid var(--gray-200); border-radius:10px; cursor:pointer; transition:all .12s; display:flex; align-items:flex-start; gap:8px; background:#fff; }
#scope2Ov .sc2:hover { border-color:var(--gray-300); background:var(--gray-50); }
#scope2Ov .sc2.pk { border-color:var(--primary-green); background:rgba(46,125,50,0.08); }
#scope2Ov .sc2 .sd2 { width:8px; height:8px; border-radius:50%; background:var(--gray-200); flex-shrink:0; margin-top:2px; }
#scope2Ov .sc2.pk .sd2 { background:var(--primary-green); }
#scope2Ov .sc2 .sn3 { font-size:13px; font-weight:600; line-height:1.3; color:var(--gray-800); }
#scope2Ov .sc2 .sd3 { font-size:11px; color:var(--gray-600); margin-top:2px; line-height:1.3; }
#scope2Ov .region-box { padding:12px; border-radius:10px; background:rgba(2,119,189,0.06); border:1px solid rgba(2,119,189,0.2); margin-bottom:12px; }
#scope2Ov .region-box .ef-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--primary-blue); margin-bottom:6px; }
#scope2Ov .ef-box { margin:12px 0; padding:12px; border-radius:10px; background:rgba(2,119,189,0.08); border:1px solid rgba(2,119,189,0.2); }
#scope2Ov .ef-box .ef-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--primary-blue); margin-bottom:6px; }
#scope2Ov .ef-row { display:flex; gap:8px; flex-wrap:wrap; }
#scope2Ov .ef-item { flex:1; min-width:80px; background:#fff; border:1px solid rgba(2,119,189,0.25); border-radius:8px; padding:8px; text-align:center; }
#scope2Ov .ef-item .ef-val { font-size:15px; font-weight:800; color:var(--primary-blue); }
#scope2Ov .ef-item .ef-lbl { font-size:9.5px; color:var(--gray-600); margin-top:2px; text-transform:uppercase; }
#scope2Ov .fi, #scope2Ov .fsl, #scope2Ov .fta { width:100%; padding:10px 12px; font-size:14px; border:1.5px solid var(--gray-200); border-radius:10px; background:#fff; outline:none; color:var(--gray-800); font-family:inherit; box-sizing:border-box; }
#scope2Ov .fi:focus, #scope2Ov .fsl:focus, #scope2Ov .fta:focus { border-color:var(--primary-green); }
#scope2Ov .fsl { appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%235f6368' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 10px center; padding-right:32px; cursor:pointer; }
#scope2Ov .fta { resize:vertical; min-height:60px; }
#scope2Ov .fr { display:flex; gap:12px; }
#scope2Ov .fr .fg { flex:1; }
#scope2Ov .fn { display:flex; gap:10px; margin-top:16px; padding-top:16px; border-top:1px solid var(--gray-200); }
#scope2Ov .btn { padding:10px 18px; border-radius:10px; font-size:14px; font-weight:600; cursor:pointer; transition:all .12s; border:none; font-family:inherit; }
#scope2Ov .bp { flex:1; background:var(--primary-green); color:#fff; }
#scope2Ov .bp:hover { background:var(--dark-green); color:#fff; }
#scope2Ov .bs { background:var(--gray-100); color:var(--gray-600); border:1.5px solid var(--gray-200); }
#scope2Ov .bs:hover { background:var(--gray-200); color:var(--gray-800); }
#scope2Ov .bg { background:var(--light-green); color:#fff; flex:1; }
#scope2Ov .bg:hover { background:var(--primary-green); color:#fff; }
#scope2Ov .ferr .fi, #scope2Ov .ferr .fsl { border-color:var(--danger-red); }
#scope2Ov .fem { font-size:11px; color:var(--danger-red); margin-top:2px; display:none; }
#scope2Ov .ferr .fem { display:block; }
#scope2Ov .co2box { padding:14px; border-radius:10px; text-align:center; margin-bottom:12px; background:rgba(46,125,50,0.08); border:1.5px solid rgba(46,125,50,0.2); }
#scope2Ov .co2box .co2v { font-size:26px; font-weight:800; color:var(--primary-green); }
#scope2Ov .co2box .co2l { font-size:12px; color:var(--gray-600); margin-top:2px; }
#scope2Ov .co2box .co2f { font-size:11px; color:var(--gray-600); margin-top:4px; font-style:italic; }
#scope2Ov .fup { border:2px dashed var(--gray-200); border-radius:10px; padding:16px; text-align:center; cursor:pointer; background:var(--gray-50); transition:all .2s; }
#scope2Ov .fup:hover { border-color:var(--gray-300); }
#scope2Ov .fup input { display:none; }
#scope2Ov .fup .fut { font-size:13px; color:var(--gray-600); }
#scope2Ov .fup .fuh { font-size:11px; color:var(--gray-600); margin-top:2px; }
#scope2Ov .ffl { margin-top:8px; display:flex; flex-wrap:wrap; gap:4px; }
#scope2Ov .ffi { display:inline-flex; align-items:center; gap:4px; padding:4px 8px; border-radius:6px; background:var(--gray-100); border:1px solid var(--gray-200); font-size:12px; color:var(--gray-600); }
#scope2Ov .suc .sk { width:52px; height:52px; border-radius:50%; background:rgba(76,175,80,0.2); border:2px solid rgba(76,175,80,0.4); display:inline-flex; align-items:center; justify-content:center; font-size:24px; margin-bottom:10px; }
#scope2Ov .suc h3 { font-size:18px; font-weight:700; color:var(--primary-green); margin-bottom:4px; }
#scope2Ov .suc p { font-size:13px; color:var(--gray-600); line-height:1.5; margin-bottom:14px; }
#scope2Ov .suc .sr { display:flex; gap:10px; justify-content:center; flex-wrap:wrap; max-width:100%; }
#scope2Ov .suc .sr .btn { min-width:100px; flex-shrink:0; }
#scope2Ov .ef-override { margin-top:8px; padding-top:8px; border-top:1px solid rgba(2,119,189,0.2); }
</style>
@endpush

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="container-fluid py-4 scope2-app">
        <div class="topbar">
            <h2><span class="sb">2</span> Scope 2 - Purchased Energy</h2>
            <p>Electricity, heat, steam &amp; cooling from third parties</p>
            <button type="button" class="btn-add" id="scope2BtnAdd"><i class="fas fa-plus me-1"></i> Add Entry</button>
        </div>
        <div class="stats">
            <div class="sc"><div class="si a">&#9889;</div><div><div class="sv" id="scope2StatTotal">0</div><div class="sl">Total Entries</div></div></div>
            <div class="sc"><div class="si b">&#9889;</div><div><div class="sv" id="scope2StatElectricity">0</div><div class="sl">Electricity</div></div></div>
            <div class="sc"><div class="si r">&#127777;</div><div><div class="sv" id="scope2StatHeating">0</div><div class="sl">Heat / Steam</div></div></div>
            <div class="sc"><div class="si g">&#10052;</div><div><div class="sv" id="scope2StatCooling">0</div><div class="sl">Cooling</div></div></div>
        </div>
        <div class="ftabs" id="scope2FTabs"></div>
        <div class="tw">
            <div class="th2"><h3>Entries</h3></div>
            <div class="p-3">
                <table class="table table-sm table-hover mb-0" id="scope2Table" width="100%">
                    <thead><tr><th>Source</th><th>Quantity</th><th>tCO2e</th><th>Facility</th><th>Date</th><th>Actions</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="ov" id="scope2Ov">
        <div class="mdl">
            <div class="mh">
                <div class="mi">&#9889;</div>
                <div class="mt"><h2>New Scope 2 Entry</h2><span>Purchased Energy</span></div>
                <button type="button" class="mx" id="scope2Mx" title="Close" aria-label="Close">&times;</button>
            </div>
            <div class="stp" role="tablist" aria-label="Form steps">
                <div class="st ac2" id="scope2Ms1"><span class="sn2">1</span><div class="stx">Source</div><div class="sb3"></div></div>
                <div class="st" id="scope2Ms2"><span class="sn2">2</span><div class="stx">Data</div><div class="sb3"></div></div>
                <div class="st" id="scope2Ms3"><span class="sn2">3</span><div class="stx">Review</div><div class="sb3"></div></div>
            </div>
            <div class="fp show" id="scope2P1">
                <div class="fg"><label>Sub-Category <span class="rq">*</span></label><div class="scb" id="scope2Scb"></div></div>
                <div class="fg" id="scope2FgSrc"><label>Emission Source <span class="rq">*</span></label><div class="fh">Select energy type</div><div class="sg" id="scope2SrcG"></div><div class="fem">Please select a source</div></div>
                <div class="fn"><button type="button" class="btn bs" id="scope2N1c">Cancel</button><button type="button" class="btn bp" id="scope2N1n">Next &rarr;</button></div>
            </div>
            <div class="fp" id="scope2P2">
                <div class="region-box" id="scope2RegionBox" style="display:none">
                    <div class="ef-title">&#127758; Grid Region / Country</div>
                    <div class="fg" style="margin:0"><select class="fsl" id="scope2Fregion"></select></div>
                    <div class="fg" id="scope2FgCef" style="margin:8px 0 0;display:none">
                        <label style="font-size:11px;font-weight:600">Custom Emission Factor (kgCO2e/kWh) <span class="rq">*</span></label>
                        <input class="fi" type="number" id="scope2Fcef" placeholder="e.g. 0.450" step="any" min="0" style="margin-top:3px">
                        <div style="font-size:9px;color:var(--gray-600);margin-top:2px">Enter the grid emission factor from your utility provider or local registry</div>
                    </div>
                </div>
                <div class="ef-box" id="scope2EfBox" style="display:none">
                    <div class="ef-title">&#128218; Emission Factors (auto-applied)</div>
                    <div class="ef-row" id="scope2EfRow"></div>
                    <div id="scope2EfOverride" class="ef-override" style="display:none">
                        <label style="display:flex;align-items:center;gap:6px;font-size:10.5px;font-weight:600;color:var(--primary-blue);cursor:pointer">
                            <input type="checkbox" id="scope2ChkEfOvr" style="accent-color:var(--primary-blue)"> Override with custom EF (kgCO2e/kWh)
                        </label>
                        <input class="fi" type="number" id="scope2FefOvr" placeholder="e.g. 0.250" step="any" min="0" style="margin-top:4px;display:none;font-size:11px">
                    </div>
                </div>
                <div class="fr"><div class="fg" id="scope2FgQty"><label>Quantity <span class="rq">*</span></label><input class="fi" type="number" id="scope2Fqty" placeholder="e.g. 5000" step="any" min="0"><div class="fem">Enter quantity</div></div><div class="fg" id="scope2FgUnit"><label>Unit <span class="rq">*</span></label><select class="fsl" id="scope2Funit"><option value="">Select...</option></select><div class="fem">Select unit</div></div></div>
                <div class="co2box" id="scope2Co2box" style="display:none"><div class="co2v" id="scope2Co2v">0.00</div><div class="co2l">tonnes CO2e</div><div class="co2f" id="scope2Co2f"></div></div>
                <div class="fr"><div class="fg" id="scope2FgPer"><label>Period <span class="rq">*</span></label><select class="fsl" id="scope2Fper"><option value="">Select...</option><option>Monthly</option><option>Quarterly</option><option>Annually</option></select><div class="fem">Select period</div></div><div class="fg" id="scope2FgDt"><label>Date <span class="rq">*</span></label><input class="fi" type="date" id="scope2Fdt"><div class="fem">Select date</div></div></div>
                <div class="fg" id="scope2FgFac"><label>Facility / Location <span class="rq">*</span></label><select class="fsl" id="scope2Ffac" required><option value="">Select facility...</option>@foreach(facilities() as $facility)<option value="{{ $facility->name }}">{{ $facility->name }}</option>@endforeach</select><div class="fem">Select facility</div></div>
                <div class="fg"><label>Notes</label><textarea class="fta" id="scope2Fdsc" placeholder="Optional..."></textarea></div>
                <div class="fn"><button type="button" class="btn bs" id="scope2N2b">&larr; Back</button><button type="button" class="btn bp" id="scope2N2n">Next &rarr;</button></div>
            </div>
            <div class="fp" id="scope2P3">
                <div class="fg"><label>Upload Evidence</label><div class="fup" id="scope2Fup"><span style="font-size:18px">&#128206;</span><div class="fut">Click to upload</div><div class="fuh">PDF, PNG, JPG, XLSX</div><input type="file" id="scope2Fupi" multiple accept=".pdf,.png,.jpg,.jpeg,.xlsx,.xls"></div><div class="ffl" id="scope2Ffl"></div></div>
                <div class="fg"><label>Summary</label><div id="scope2Rvw" style="padding:10px;border-radius:9px;background:var(--gray-50);border:1px solid var(--gray-200);font-size:13px;line-height:1.8;color:var(--gray-600)"></div></div>
                <div class="fn"><button type="button" class="btn bs" id="scope2N3b">&larr; Back</button><button type="button" class="btn bg" id="scope2N3s">&#10003; Submit</button></div>
            </div>
            <div class="suc" id="scope2Suc"><div class="sk">&#10003;</div><h3>Saved!</h3><p id="scope2SucM"></p><div class="sr"><button type="button" class="btn bs" id="scope2SucC">Close</button><button type="button" class="btn bp" id="scope2SucA">+ Another</button></div></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.scope2Sources = {!! $sourcesJson !!};
window.scope2GridEf = {!! $gridEfJson !!};
window.scope2StoreUrl = @json($storeUrl);
window.scope2DataUrl = @json(route('scope2_entry.data'));
window.scope2StatsUrl = @json(route('scope2_entry.stats'));
window.scope2Csrf = @json(csrf_token());
</script>
@include('scope2_entry.script')
@endpush

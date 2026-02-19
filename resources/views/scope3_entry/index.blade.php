@extends('layouts.app')

@section('title', 'Scope 3 Entry')
@section('page-title', 'Scope 3 - Indirect Value Chain')

@push('styles')
<style>
/* Scope 3 Entry - same color scheme and style as Scope 1 & 2 */
.scope3-app * { box-sizing: border-box; }
.scope3-app { font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; }
.scope3-app .topbar { display:flex; align-items:center; gap:16px; margin-bottom:24px; flex-wrap:wrap; padding:20px 24px; background:linear-gradient(135deg,#fff 0%,var(--gray-50) 100%); border:1px solid var(--gray-200); border-radius:16px; box-shadow:0 2px 8px rgba(0,0,0,.06); }
.scope3-app .topbar h2 { font-size:1.35rem; font-weight:700; letter-spacing:-.02em; display:flex; align-items:center; gap:10px; margin:0; color:var(--gray-800); }
.scope3-app .topbar h2 .sb { display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:10px; background:linear-gradient(135deg,var(--primary-green) 0%,var(--light-green) 100%); color:#fff; font-size:1rem; font-weight:700; box-shadow:0 2px 8px rgba(46,125,50,.25); }
.scope3-app .topbar p { color:var(--gray-600); font-size:0.875rem; flex:1; min-width:180px; margin:0; line-height:1.4; }
.scope3-app .btn-add { margin-left:auto; padding:10px 20px; border-radius:10px; background:linear-gradient(135deg,var(--primary-green) 0%,var(--light-green) 100%); color:#fff; border:none; font-size:0.875rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:8px; white-space:nowrap; box-shadow:0 2px 8px rgba(46,125,50,.25); transition:transform .2s,box-shadow .2s; }
.scope3-app .btn-add:hover { background:linear-gradient(135deg,var(--dark-green) 0%,var(--primary-green) 100%); color:#fff; transform:translateY(-1px); box-shadow:0 4px 12px rgba(46,125,50,.35); }
.scope3-app .stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px; margin-bottom:24px; }
.scope3-app .sc { background:#fff; border:1px solid var(--gray-200); border-radius:14px; padding:18px 20px; display:flex; align-items:center; gap:14px; box-shadow:0 2px 8px rgba(0,0,0,.06); transition:transform .2s,box-shadow .2s; }
.scope3-app .sc:hover { transform:translateY(-2px); box-shadow:0 4px 16px rgba(0,0,0,.08); }
.scope3-app .sc .si { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.25rem; flex-shrink:0; }
.scope3-app .sc .si.a { background:linear-gradient(135deg,rgba(46,125,50,.15) 0%,rgba(76,175,80,.12) 100%); }
.scope3-app .sc .si.b { background:linear-gradient(135deg,rgba(76,175,80,.2) 0%,rgba(129,199,132,.15) 100%); }
.scope3-app .sc .si.r { background:linear-gradient(135deg,rgba(245,124,0,.15) 0%,rgba(255,152,0,.1) 100%); }
.scope3-app .sc .sv { font-size:1.5rem; font-weight:700; letter-spacing:-.02em; color:var(--gray-800); }
.scope3-app .sc .sl { font-size:0.75rem; color:var(--gray-600); margin-top:2px; font-weight:500; text-transform:uppercase; letter-spacing:.04em; }
.scope3-app .ftabs { display:flex; gap:8px; margin-bottom:20px; flex-wrap:wrap; }
.scope3-app .ftab { padding:8px 16px; border-radius:100px; font-size:0.8125rem; font-weight:600; cursor:pointer; border:1.5px solid var(--gray-200); background:#fff; color:var(--gray-600); transition:all .2s; display:flex; align-items:center; gap:6px; box-shadow:0 1px 3px rgba(0,0,0,.04); }
.scope3-app .ftab:hover { border-color:var(--gray-300); background:var(--gray-50); color:var(--gray-800); }
.scope3-app .ftab.on { background:rgba(46,125,50,0.1); border-color:rgba(46,125,50,0.35); color:var(--primary-green); box-shadow:0 2px 6px rgba(46,125,50,.12); }
.scope3-app .ftab .cn { font-size:0.625rem; background:var(--gray-100); padding:2px 6px; border-radius:100px; font-weight:700; }
.scope3-app .ftab.on .cn { background:rgba(46,125,50,0.2); color:var(--dark-green); }
.scope3-app .tw { background:#fff; border:1px solid var(--gray-200); border-radius:16px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.06); }
.scope3-app .th2 { display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid var(--gray-200); flex-wrap:wrap; gap:10px; background:linear-gradient(180deg,var(--gray-50) 0%,#fff 100%); }
.scope3-app .th2 h3 { font-size:1rem; font-weight:700; color:var(--gray-800); margin:0; }
.scope3-app .tw .p-3 { padding:20px; }
.scope3-app #scope3Table_wrapper { margin:0 -4px; }
.scope3-app #scope3Table thead th { background:var(--gray-100); color:var(--gray-600); font-size:0.6875rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; padding:12px 14px; border-bottom:1px solid var(--gray-200); }
.scope3-app #scope3Table tbody td { padding:12px 14px; font-size:0.875rem; border-bottom:1px solid var(--gray-100); vertical-align:middle; }
.scope3-app #scope3Table tbody tr:hover td { background:var(--gray-50); }
.scope3-app #scope3Table tbody tr:last-child td { border-bottom:none; }
/* Modal */
#scope3Ov { position:fixed; inset:0; background:rgba(12,18,30,.5); backdrop-filter:blur(3px); z-index:1050; display:none; align-items:flex-start; justify-content:center; padding:20px 12px; overflow-y:auto; opacity:0; transition:opacity .2s; }
#scope3Ov.open { display:flex !important; opacity:1; }
#scope3Ov .mdl { background:#fff; border-radius:16px; width:100%; max-width:920px; min-height:520px; box-shadow:0 20px 60px rgba(0,0,0,.12); margin:auto; overflow:hidden; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; }
#scope3Ov .mh { padding:18px 20px 14px; display:flex; align-items:flex-start; gap:12px; flex-wrap:wrap; border-bottom:1px solid var(--gray-200); }
#scope3Ov .mh .mi { width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; background:rgba(46,125,50,0.12); }
#scope3Ov .mh .mt { flex:1; min-width:0; }
#scope3Ov .mh .mt h2 { font-size:17px; font-weight:700; margin:0 0 2px 0; color:var(--gray-800); }
#scope3Ov .mh .mt span { font-size:12px; color:var(--gray-600); display:block; }
#scope3Ov .mh .mx { width:36px; height:36px; border-radius:8px; border:1px solid var(--gray-200); background:#fff; cursor:pointer; display:flex; align-items:center; justify-content:center; color:var(--gray-600); font-size:18px; flex-shrink:0; margin-left:auto; }
#scope3Ov .mh .mx:hover { background:var(--gray-100); border-color:var(--gray-300); color:var(--gray-800); }
#scope3Ov .stp { display:flex; padding:14px 20px 0; gap:0; border-bottom:1px solid var(--gray-200); }
#scope3Ov .st { flex:1; text-align:center; padding-bottom:12px; position:relative; }
#scope3Ov .st .sb3 { position:absolute; bottom:0; left:0; right:0; height:3px; background:var(--gray-200); border-radius:2px; }
#scope3Ov .st.dn .sb3 { background:var(--light-green); }
#scope3Ov .st.ac2 .sb3 { background:var(--primary-green); }
#scope3Ov .st .sn2 { width:24px; height:24px; border-radius:50%; border:2px solid var(--gray-200); display:inline-flex; align-items:center; justify-content:center; font-size:10px; font-weight:700; color:var(--gray-600); margin-bottom:4px; }
#scope3Ov .st.ac2 .sn2 { border-color:var(--primary-green); background:var(--primary-green); color:#fff; }
#scope3Ov .st.dn .sn2 { border-color:var(--light-green); background:var(--light-green); color:#fff; }
#scope3Ov .st .stx { font-size:11px; font-weight:600; color:var(--gray-600); }
#scope3Ov .st.ac2 .stx { color:var(--primary-green); }
#scope3Ov .st.dn .stx { color:var(--light-green); }
#scope3Ov .fp { padding:16px 20px 20px; display:none !important; }
#scope3Ov .fp.show { display:block !important; }
#scope3Ov .suc { display:none !important; padding:0; margin:0; }
#scope3Ov .suc.on { display:block !important; padding:32px 20px 24px; }
#scope3Ov .fg { margin-bottom:14px; }
#scope3Ov .fg label { display:block; font-size:13px; font-weight:600; margin-bottom:4px; color:var(--gray-800); }
#scope3Ov .fg label .rq { color:var(--danger-red); }
#scope3Ov .scb { display:flex; gap:8px; margin-bottom:12px; flex-wrap:wrap; }
#scope3Ov .scbt { padding:8px 16px; border-radius:100px; font-size:13px; font-weight:600; cursor:pointer; border:2px solid var(--gray-200); background:#fff; color:var(--gray-600); transition:all .15s; }
#scope3Ov .scbt:hover { border-color:var(--gray-300); background:var(--gray-50); }
#scope3Ov .scbt.on { background:rgba(46,125,50,0.1); border-color:rgba(46,125,50,0.3); color:var(--primary-green); }
#scope3Ov .sg { display:grid; grid-template-columns:1fr 1fr; gap:8px; max-height:260px; overflow-y:auto; padding-right:4px; }
#scope3Ov .sc2 { padding:12px; border:2px solid var(--gray-200); border-radius:10px; cursor:pointer; transition:all .12s; display:flex; align-items:flex-start; gap:8px; background:#fff; }
#scope3Ov .sc2:hover { border-color:var(--gray-300); background:var(--gray-50); }
#scope3Ov .sc2.pk { border-color:var(--primary-green); background:rgba(46,125,50,0.08); }
#scope3Ov .sc2 .sd2 { width:8px; height:8px; border-radius:50%; background:var(--gray-200); flex-shrink:0; margin-top:2px; }
#scope3Ov .sc2.pk .sd2 { background:var(--primary-green); }
#scope3Ov .sc2 .sn3 { font-size:13px; font-weight:600; line-height:1.3; color:var(--gray-800); }
#scope3Ov .sc2 .sd3 { font-size:11px; color:var(--gray-600); margin-top:2px; line-height:1.3; }
#scope3Ov .fi, #scope3Ov .fsl, #scope3Ov .fta { width:100%; padding:10px 12px; font-size:14px; border:1.5px solid var(--gray-200); border-radius:10px; background:#fff; outline:none; color:var(--gray-800); font-family:inherit; box-sizing:border-box; }
#scope3Ov .fi:focus, #scope3Ov .fsl:focus, #scope3Ov .fta:focus { border-color:var(--primary-green); }
#scope3Ov .fsl { appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%235f6368' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 10px center; padding-right:32px; cursor:pointer; }
#scope3Ov .fta { resize:vertical; min-height:60px; }
#scope3Ov .fr { display:flex; gap:12px; }
#scope3Ov .fr .fg { flex:1; }
#scope3Ov .fn { display:flex; gap:10px; margin-top:16px; padding-top:16px; border-top:1px solid var(--gray-200); }
#scope3Ov .btn { padding:10px 18px; border-radius:10px; font-size:14px; font-weight:600; cursor:pointer; transition:all .12s; border:none; font-family:inherit; }
#scope3Ov .bp { flex:1; background:var(--primary-green); color:#fff; }
#scope3Ov .bp:hover { background:var(--dark-green); color:#fff; }
#scope3Ov .bs { background:var(--gray-100); color:var(--gray-600); border:1.5px solid var(--gray-200); }
#scope3Ov .bs:hover { background:var(--gray-200); color:var(--gray-800); }
#scope3Ov .bg { background:var(--light-green); color:#fff; flex:1; }
#scope3Ov .bg:hover { background:var(--primary-green); color:#fff; }
#scope3Ov .ferr .fi, #scope3Ov .ferr .fsl { border-color:var(--danger-red); }
#scope3Ov .fem { font-size:11px; color:var(--danger-red); margin-top:2px; display:none; }
#scope3Ov .ferr .fem { display:block; }
#scope3Ov .fup { border:2px dashed var(--gray-200); border-radius:10px; padding:16px; text-align:center; cursor:pointer; background:var(--gray-50); transition:all .2s; }
#scope3Ov .fup:hover { border-color:var(--gray-300); }
#scope3Ov .fup input { display:none; }
#scope3Ov .fup .fut { font-size:13px; color:var(--gray-600); }
#scope3Ov .fup .fuh { font-size:11px; color:var(--gray-600); margin-top:2px; }
#scope3Ov .ffl { margin-top:8px; display:flex; flex-wrap:wrap; gap:4px; }
#scope3Ov .ffi { display:inline-flex; align-items:center; gap:4px; padding:4px 8px; border-radius:6px; background:var(--gray-100); border:1px solid var(--gray-200); font-size:12px; color:var(--gray-600); }
#scope3Ov .suc .sk { width:52px; height:52px; border-radius:50%; background:rgba(76,175,80,0.2); border:2px solid rgba(76,175,80,0.4); display:inline-flex; align-items:center; justify-content:center; font-size:24px; margin-bottom:10px; }
#scope3Ov .suc h3 { font-size:18px; font-weight:700; color:var(--primary-green); margin-bottom:4px; }
#scope3Ov .suc p { font-size:13px; color:var(--gray-600); line-height:1.5; margin-bottom:14px; }
#scope3Ov .suc .sr { display:flex; gap:10px; justify-content:center; flex-wrap:wrap; max-width:100%; }
#scope3Ov .suc .sr .btn { min-width:100px; flex-shrink:0; }
.scope3-dyn-fields { display:grid; grid-template-columns:1fr 1fr; gap:10px 14px; }
.scope3-dyn-fields .fg-inline { margin-bottom:0; }
.scope3-dyn-fields .fg-inline label { font-size:12px; font-weight:600; color:var(--gray-700); margin-bottom:2px; display:block; }
.scope3-dyn-fields .fi, .scope3-dyn-fields .fsl { width:100%; padding:8px 10px; font-size:13px; }
@media (max-width:520px) { .scope3-dyn-fields { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="container-fluid py-4 scope3-app">
        <div class="topbar">
            <h2><span class="sb">3</span> Scope 3 - Indirect Value Chain</h2>
            <p>Purchased goods, business travel, waste, downstream &amp; other value chain emissions</p>
            <button type="button" class="btn-add" id="scope3BtnAdd"><i class="fas fa-plus me-1"></i> Add Entry</button>
        </div>
        <div class="stats">
            <div class="sc"><div class="si a">&#127760;</div><div><div class="sv" id="scope3StatTotal">0</div><div class="sl">Total Entries</div></div></div>
            <div class="sc"><div class="si b">&#8593;</div><div><div class="sv" id="scope3StatUpstream">0</div><div class="sl">Upstream</div></div></div>
            <div class="sc"><div class="si r">&#8595;</div><div><div class="sv" id="scope3StatDownstream">0</div><div class="sl">Downstream</div></div></div>
        </div>
        <div class="ftabs" id="scope3FTabs"></div>
        <div class="tw">
            <div class="th2"><h3>Entries</h3></div>
            <div class="p-3">
                <table class="table table-sm table-hover mb-0" id="scope3Table" width="100%">
                    <thead><tr><th>Category</th><th>Activity</th><th>tCO2e</th><th>Facility</th><th>Date</th><th>Attachments</th><th>Actions</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="ov" id="scope3Ov">
        <div class="mdl">
            <div class="mh">
                <div class="mi">&#127760;</div>
                <div class="mt"><h2>New Scope 3 Entry</h2><span>Indirect Value Chain</span></div>
                <button type="button" class="mx" id="scope3Mx" title="Close" aria-label="Close">&times;</button>
            </div>
            <div class="stp" role="tablist" aria-label="Form steps">
                <div class="st ac2" id="scope3Ms1"><span class="sn2">1</span><div class="stx">Category</div><div class="sb3"></div></div>
                <div class="st" id="scope3Ms2"><span class="sn2">2</span><div class="stx">Data</div><div class="sb3"></div></div>
                <div class="st" id="scope3Ms3"><span class="sn2">3</span><div class="stx">Review</div><div class="sb3"></div></div>
            </div>
            <div class="fp show" id="scope3P1">
                <div class="fg"><label>Type <span class="rq">*</span></label><div class="scb" id="scope3Scb"></div></div>
                <div class="fg" id="scope3FgCat"><label>Scope 3 Category <span class="rq">*</span></label><div class="fh">Select category</div><div class="sg" id="scope3CatG"></div><div class="fem">Please select a category</div></div>
                <div class="fn"><button type="button" class="btn bs" id="scope3N1c">Cancel</button><button type="button" class="btn bp" id="scope3N1n">Next &rarr;</button></div>
            </div>
            <div class="fp" id="scope3P2">
                <div class="fg" id="scope3FgDynamic">
                    <label id="scope3DynamicLabel">Category data</label>
                    <div class="scope3-form-info" id="scope3FormInfo" style="display:none;font-size:12px;color:var(--gray-600);margin-bottom:8px;"></div>
                    <div id="scope3DynamicFields" class="scope3-dyn-fields"></div>
                    <div class="fem" id="scope3DynamicErr">Fill in the required fields above</div>
                </div>
                <div class="scope3-co2-result" id="scope3Co2Result" style="display:none;padding:12px;border-radius:10px;background:rgba(46,125,50,0.08);border:1px solid rgba(46,125,50,0.2);margin-bottom:12px;">
                    <div style="font-size:12px;font-weight:600;color:var(--gray-600);margin-bottom:4px;">Calculated emissions</div>
                    <div style="font-size:24px;font-weight:700;color:var(--primary-green);" id="scope3Co2ResultVal">0.00 tCO2e</div>
                    <div class="fg" style="margin-top:10px;margin-bottom:0;"><label style="font-size:12px;">Override (tCO2e)</label><input class="fi" type="number" id="scope3Fco2Override" placeholder="Leave blank to use calculated" step="any" min="0"></div>
                </div>
                <div class="fg" id="scope3FgCo2Manual" style="display:none;"><label>Emissions (tCO2e) <span class="rq">*</span></label><input class="fi" type="number" id="scope3Fco2" placeholder="e.g. 12.5" step="any" min="0"><div class="fem">Enter tonnes CO2e</div></div>
                <div class="fr"><div class="fg" id="scope3FgPer"><label>Period <span class="rq">*</span></label><select class="fsl" id="scope3Fper"><option value="">Select...</option><option>Monthly</option><option>Quarterly</option><option>Annually</option></select><div class="fem">Select period</div></div><div class="fg" id="scope3FgDt"><label>Date <span class="rq">*</span></label><input class="fi" type="date" id="scope3Fdt"><div class="fem">Select date</div></div></div>
                <div class="fg" id="scope3FgFac"><label>Facility / Location <span class="rq">*</span></label><select class="fsl" id="scope3Ffac" required><option value="">Select facility...</option>@foreach(facilities() as $facility)<option value="{{ $facility->name }}">{{ $facility->name }}</option>@endforeach</select><div class="fem">Select facility</div></div>
                <div class="fr"><div class="fg" id="scope3FgMethod"><label>Calculation method</label><select class="fsl" id="scope3Fmethod"><option value="activity-based">Activity-based</option><option value="spend-based">Spend-based</option><option value="hybrid">Hybrid</option></select></div><div class="fg" id="scope3FgQuality"><label>Data quality</label><select class="fsl" id="scope3Fquality"><option value="estimated">Estimated</option><option value="secondary">Secondary</option><option value="primary">Primary</option></select></div></div>
                <div class="fg"><label>Notes</label><textarea class="fta" id="scope3Fdsc" placeholder="Optional..."></textarea></div>
                <div class="fn"><button type="button" class="btn bs" id="scope3N2b">&larr; Back</button><button type="button" class="btn bp" id="scope3N2n">Next &rarr;</button></div>
            </div>
            <div class="fp" id="scope3P3">
                <div class="fg"><label>Upload Evidence</label><div class="fup" id="scope3Fup"><span style="font-size:18px">&#128206;</span><div class="fut">Click to upload</div><div class="fuh">PDF, PNG, JPG, XLSX</div><input type="file" id="scope3Fupi" multiple accept=".pdf,.png,.jpg,.jpeg,.xlsx,.xls"></div><div class="ffl" id="scope3Ffl"></div></div>
                <div class="fg"><label>Summary</label><div id="scope3Rvw" style="padding:10px;border-radius:9px;background:var(--gray-50);border:1px solid var(--gray-200);font-size:13px;line-height:1.8;color:var(--gray-600)"></div></div>
                <div class="fn"><button type="button" class="btn bs" id="scope3N3b">&larr; Back</button><button type="button" class="btn bg" id="scope3N3s">&#10003; Submit</button></div>
            </div>
            <div class="suc" id="scope3Suc"><div class="sk">&#10003;</div><h3>Saved!</h3><p id="scope3SucM"></p><div class="sr"><button type="button" class="btn bs" id="scope3SucC">Close</button><button type="button" class="btn bp" id="scope3SucA">+ Another</button></div></div>
        </div>
    </div>

    <!-- Attachments modal -->
    <div class="modal fade" id="scope3AttachmentsModal" tabindex="-1" aria-labelledby="scope3AttachmentsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="scope3AttachmentsModalLabel"><i class="fas fa-paperclip me-2"></i>Attachments</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="scope3AttachmentsModalBody">
                    <p class="text-muted mb-0">No attachments.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- View Record modal (popup) -->
    <div class="modal fade" id="scope3ViewRecordModal" tabindex="-1" aria-labelledby="scope3ViewRecordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg scope3-view-modal-dialog">
            <div class="modal-content scope3-view-modal-content">
                <div class="modal-header scope3-view-modal-header">
                    <div class="d-flex align-items-center gap-3 flex-grow-1">
                        <div class="scope3-view-modal-icon"><i class="fas fa-globe-americas"></i></div>
                        <div>
                            <h5 class="modal-title mb-0" id="scope3ViewRecordModalLabel">Emission Record</h5>
                            <p class="scope3-view-modal-subtitle mb-0">Scope 3 · Value chain</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body scope3-view-modal-body" id="scope3ViewRecordModalBody">
                    <div class="text-center py-5 scope3-view-loading">
                        <div class="spinner-border text-success" role="status" style="width:2.5rem;height:2.5rem;"><span class="visually-hidden">Loading...</span></div>
                        <p class="text-muted mt-3 mb-0">Loading record...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.min.css" rel="stylesheet">
<style>
/* View Record modal — Scope 3 */
.scope3-view-modal-dialog { max-width: 560px; }
.scope3-view-modal-content { border: none; border-radius: 16px; overflow: hidden; box-shadow: 0 24px 48px rgba(0,0,0,.15); }
.scope3-view-modal-header {
  background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
  color: #fff; padding: 18px 20px; border-bottom: none;
}
.scope3-view-modal-icon {
  width: 48px; height: 48px; border-radius: 12px; background: rgba(255,255,255,.2);
  display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0;
}
.scope3-view-modal-subtitle { font-size: 0.8rem; opacity: .9; margin-top: 2px; }
.scope3-view-modal-body { padding: 24px; background: var(--gray-50); }
#scope3ViewRecordModal .scope3-view-details-card {
  background: #fff; border-radius: 12px; padding: 18px 20px; margin-bottom: 16px;
  border: 1px solid var(--gray-200); box-shadow: 0 2px 8px rgba(0,0,0,.04);
}
#scope3ViewRecordModal .scope3-view-co2e {
  background: linear-gradient(135deg, rgba(46,125,50,.1) 0%, rgba(76,175,80,.08) 100%);
  border: 1px solid rgba(46,125,50,.25); border-radius: 12px; padding: 14px 18px;
  text-align: center; margin-bottom: 18px;
}
#scope3ViewRecordModal .scope3-view-co2e .val { font-size: 1.75rem; font-weight: 800; color: var(--primary-green); letter-spacing: -0.02em; }
#scope3ViewRecordModal .scope3-view-co2e .lbl { font-size: 0.7rem; text-transform: uppercase; letter-spacing: .06em; color: var(--gray-600); margin-top: 2px; }
#scope3ViewRecordModal .scope3-view-row { display: flex; padding: 8px 0; border-bottom: 1px solid var(--gray-100); font-size: 0.9rem; }
#scope3ViewRecordModal .scope3-view-row:last-child { border-bottom: none; }
#scope3ViewRecordModal .scope3-view-row .k { color: var(--gray-600); min-width: 120px; flex-shrink: 0; }
#scope3ViewRecordModal .scope3-view-row .v { color: var(--gray-800); font-weight: 500; }
#scope3ViewRecordModal .scope3-view-attachments {
  background: #fff; border-radius: 12px; padding: 16px 18px; border: 1px solid var(--gray-200);
  box-shadow: 0 2px 8px rgba(0,0,0,.04);
}
#scope3ViewRecordModal .scope3-view-attachments .title { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--gray-600); margin-bottom: 10px; }
#scope3ViewRecordModal .scope3-view-attachment-item {
  display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 8px;
  background: var(--gray-50); border: 1px solid var(--gray-200); margin-bottom: 8px; font-size: 0.875rem;
  transition: background .15s, border-color .15s;
}
#scope3ViewRecordModal .scope3-view-attachment-item:last-child { margin-bottom: 0; }
#scope3ViewRecordModal .scope3-view-attachment-item:hover { background: #fff; border-color: var(--primary-green); }
#scope3ViewRecordModal .scope3-view-attachment-item a { color: var(--gray-800); text-decoration: none; flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
#scope3ViewRecordModal .scope3-view-attachment-item a:hover { color: var(--primary-green); }
#scope3ViewRecordModal .scope3-view-attachment-item .open-btn {
  flex-shrink: 0; padding: 6px 10px; border-radius: 8px; font-size: 0.75rem; font-weight: 600;
  background: var(--primary-green); color: #fff; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;
}
#scope3ViewRecordModal .scope3-view-attachment-item .open-btn:hover { background: var(--dark-green); color: #fff; }
#scope3ViewRecordModal .scope3-view-no-attach { color: var(--gray-600); font-size: 0.875rem; padding: 8px 0; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
window.scope3Categories = {!! $categoriesJson !!};
window.scope3EntryForms = {!! $entryFormsJson ?? '{}' !!};
window.scope3StoreUrl = @json($storeUrl);
window.scope3DataUrl = @json(route('scope3_entry.data'));
window.scope3StatsUrl = @json(route('scope3_entry.stats'));
window.scope3Csrf = @json(csrf_token());
</script>
@include('scope3_entry.script')
@endpush

@extends('layouts.app')

@section('title', 'Scope 1 Entry')
@section('page-title', 'Scope 1 - Direct Emissions')

@push('styles')
<style>
/* Scope 1 Entry - project color scheme, polished index UI */
.scope1-app * { box-sizing: border-box; }
.scope1-app { font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; }
/* Page header card */
.scope1-app .topbar { display:flex; align-items:center; gap:16px; margin-bottom:24px; flex-wrap:wrap; padding:20px 24px; background:linear-gradient(135deg,#fff 0%,var(--gray-50) 100%); border:1px solid var(--gray-200); border-radius:16px; box-shadow:0 2px 8px rgba(0,0,0,.06); }
.scope1-app .topbar h2 { font-size:1.35rem; font-weight:700; letter-spacing:-.02em; display:flex; align-items:center; gap:10px; margin:0; color:var(--gray-800); }
.scope1-app .topbar h2 .sb { display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:10px; background:linear-gradient(135deg,var(--primary-green) 0%,var(--light-green) 100%); color:#fff; font-size:1rem; font-weight:700; box-shadow:0 2px 8px rgba(46,125,50,.25); }
.scope1-app .topbar p { color:var(--gray-600); font-size:0.875rem; flex:1; min-width:180px; margin:0; line-height:1.4; }
.scope1-app .btn-add { margin-left:auto; padding:10px 20px; border-radius:10px; background:linear-gradient(135deg,var(--primary-green) 0%,var(--light-green) 100%); color:#fff; border:none; font-size:0.875rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:8px; white-space:nowrap; box-shadow:0 2px 8px rgba(46,125,50,.25); transition:transform .2s,box-shadow .2s; }
.scope1-app .btn-add:hover { background:linear-gradient(135deg,var(--dark-green) 0%,var(--primary-green) 100%); color:#fff; transform:translateY(-1px); box-shadow:0 4px 12px rgba(46,125,50,.35); }
/* Stats cards */
.scope1-app .stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px; margin-bottom:24px; }
.scope1-app .sc { background:#fff; border:1px solid var(--gray-200); border-radius:14px; padding:18px 20px; display:flex; align-items:center; gap:14px; box-shadow:0 2px 8px rgba(0,0,0,.06); transition:transform .2s,box-shadow .2s; }
.scope1-app .sc:hover { transform:translateY(-2px); box-shadow:0 4px 16px rgba(0,0,0,.08); }
.scope1-app .sc .si { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.25rem; flex-shrink:0; }
.scope1-app .sc .si.r { background:linear-gradient(135deg,rgba(46,125,50,.15) 0%,rgba(76,175,80,.12) 100%); }
.scope1-app .sc .si.g { background:linear-gradient(135deg,rgba(76,175,80,.2) 0%,rgba(129,199,132,.15) 100%); }
.scope1-app .sc .si.a { background:linear-gradient(135deg,rgba(245,124,0,.15) 0%,rgba(255,152,0,.1) 100%); }
.scope1-app .sc .si.p { background:linear-gradient(135deg,rgba(2,119,189,.12) 0%,rgba(3,169,244,.1) 100%); }
.scope1-app .sc .sv { font-size:1.5rem; font-weight:700; letter-spacing:-.02em; color:var(--gray-800); }
.scope1-app .sc .sl { font-size:0.75rem; color:var(--gray-600); margin-top:2px; font-weight:500; text-transform:uppercase; letter-spacing:.04em; }
/* Filter tabs */
.scope1-app .ftabs { display:flex; gap:8px; margin-bottom:20px; flex-wrap:wrap; }
.scope1-app .ftab { padding:8px 16px; border-radius:100px; font-size:0.8125rem; font-weight:600; cursor:pointer; border:1.5px solid var(--gray-200); background:#fff; color:var(--gray-600); transition:all .2s; display:flex; align-items:center; gap:6px; box-shadow:0 1px 3px rgba(0,0,0,.04); }
.scope1-app .ftab:hover { border-color:var(--gray-300); background:var(--gray-50); color:var(--gray-800); }
.scope1-app .ftab.on { background:rgba(46,125,50,0.1); border-color:rgba(46,125,50,0.35); color:var(--primary-green); box-shadow:0 2px 6px rgba(46,125,50,.12); }
.scope1-app .ftab .cn { font-size:0.625rem; background:var(--gray-100); padding:2px 6px; border-radius:100px; font-weight:700; }
.scope1-app .ftab.on .cn { background:rgba(46,125,50,0.2); color:var(--dark-green); }
/* Table card */
.scope1-app .tw { background:#fff; border:1px solid var(--gray-200); border-radius:16px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.06); }
.scope1-app .th2 { display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid var(--gray-200); flex-wrap:wrap; gap:10px; background:linear-gradient(180deg,var(--gray-50) 0%,#fff 100%); }
.scope1-app .th2 h3 { font-size:1rem; font-weight:700; color:var(--gray-800); margin:0; }
.scope1-app .tw .p-3 { padding:20px; }
.scope1-app #scope1Table_wrapper { margin:0 -4px; }
.scope1-app #scope1Table thead th { background:var(--gray-100); color:var(--gray-600); font-size:0.6875rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; padding:12px 14px; border-bottom:1px solid var(--gray-200); }
.scope1-app #scope1Table tbody td { padding:12px 14px; font-size:0.875rem; border-bottom:1px solid var(--gray-100); vertical-align:middle; }
.scope1-app #scope1Table tbody tr:hover td { background:var(--gray-50); }
.scope1-app #scope1Table tbody tr:last-child td { border-bottom:none; }
.scope1-app .ov { position:fixed; inset:0; background:rgba(12,18,30,.5); backdrop-filter:blur(3px); z-index:1050; display:none; align-items:flex-start; justify-content:center; padding:20px 12px; overflow-y:auto; opacity:0; transition:opacity .2s; }
.scope1-app .ov.open { display:flex; opacity:1; }
/* Modal overlay is outside .scope1-app — must target by ID */
#scope1Ov { position:fixed; inset:0; background:rgba(12,18,30,.5); backdrop-filter:blur(3px); z-index:1050; display:none; align-items:flex-start; justify-content:center; padding:20px 12px; overflow-y:auto; opacity:0; transition:opacity .2s; }
#scope1Ov.open { display:flex !important; opacity:1; }
.scope1-app .mdl { background:#fff; border-radius:16px; width:100%; max-width:920px; min-height:520px; box-shadow:0 20px 60px rgba(0,0,0,.18); margin:auto; overflow:hidden; }
#scope1Ov .mdl { background:#fff; border-radius:16px; width:100%; max-width:920px; min-height:520px; box-shadow:0 20px 60px rgba(0,0,0,.12); margin:auto; overflow:hidden; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; }
/* Modal — project colors (primary-green, grays, primary-blue, danger-red) */
#scope1Ov .mh { padding:18px 20px 14px; display:flex; align-items:flex-start; gap:12px; flex-wrap:wrap; border-bottom:1px solid var(--gray-200); }
#scope1Ov .mh .mi { width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; background:rgba(46,125,50,0.12); }
#scope1Ov .mh .mt { flex:1; min-width:0; }
#scope1Ov .mh .mt h2 { font-size:17px; font-weight:700; margin:0 0 2px 0; color:var(--gray-800); }
#scope1Ov .mh .mt span { font-size:12px; color:var(--gray-600); display:block; }
#scope1Ov .mh .mx { width:36px; height:36px; border-radius:8px; border:1px solid var(--gray-200); background:#fff; cursor:pointer; display:flex; align-items:center; justify-content:center; color:var(--gray-600); font-size:18px; flex-shrink:0; margin-left:auto; }
#scope1Ov .mh .mx:hover { background:var(--gray-100); border-color:var(--gray-300); color:var(--gray-800); }
#scope1Ov .stp { display:flex; padding:14px 20px 0; gap:0; border-bottom:1px solid var(--gray-200); }
#scope1Ov .st { flex:1; text-align:center; padding-bottom:12px; position:relative; }
#scope1Ov .st .sb3 { position:absolute; bottom:0; left:0; right:0; height:3px; background:var(--gray-200); border-radius:2px; }
#scope1Ov .st.dn .sb3 { background:var(--light-green); }
#scope1Ov .st.ac2 .sb3 { background:var(--primary-green); }
#scope1Ov .st .sn2 { width:24px; height:24px; border-radius:50%; border:2px solid var(--gray-200); display:inline-flex; align-items:center; justify-content:center; font-size:10px; font-weight:700; color:var(--gray-600); margin-bottom:4px; }
#scope1Ov .st.ac2 .sn2 { border-color:var(--primary-green); background:var(--primary-green); color:#fff; }
#scope1Ov .st.dn .sn2 { border-color:var(--light-green); background:var(--light-green); color:#fff; }
#scope1Ov .st .stx { font-size:11px; font-weight:600; color:var(--gray-600); }
#scope1Ov .st.ac2 .stx { color:var(--primary-green); }
#scope1Ov .st.dn .stx { color:var(--light-green); }
#scope1Ov .fp { padding:16px 20px 20px; display:none !important; }
#scope1Ov .fp.show { display:block !important; }
#scope1Ov .suc { display:none !important; padding:0; margin:0; }
#scope1Ov .suc.on { display:block !important; }
#scope1Ov .fg { margin-bottom:14px; }
#scope1Ov .fg label { display:block; font-size:13px; font-weight:600; margin-bottom:4px; color:var(--gray-800); }
#scope1Ov .fg label .rq { color:var(--danger-red); }
#scope1Ov .fg .fh { font-size:11px; color:var(--gray-600); margin-bottom:4px; }
#scope1Ov .scb { display:flex; gap:8px; margin-bottom:12px; flex-wrap:wrap; }
#scope1Ov .scbt { padding:8px 16px; border-radius:100px; font-size:13px; font-weight:600; cursor:pointer; border:2px solid var(--gray-200); background:#fff; color:var(--gray-600); transition:all .15s; }
#scope1Ov .scbt:hover { border-color:var(--gray-300); background:var(--gray-50); }
#scope1Ov .scbt.on { background:rgba(46,125,50,0.1); border-color:rgba(46,125,50,0.3); color:var(--primary-green); }
#scope1Ov .sg { display:grid; grid-template-columns:1fr 1fr; gap:8px; max-height:220px; overflow-y:auto; padding-right:4px; }
#scope1Ov .sc2 { padding:12px; border:2px solid var(--gray-200); border-radius:10px; cursor:pointer; transition:all .12s; display:flex; align-items:flex-start; gap:8px; background:#fff; }
#scope1Ov .sc2:hover { border-color:var(--gray-300); background:var(--gray-50); }
#scope1Ov .sc2.pk { border-color:var(--primary-green); background:rgba(46,125,50,0.08); }
#scope1Ov .sc2 .sd2 { width:8px; height:8px; border-radius:50%; background:var(--gray-200); flex-shrink:0; margin-top:2px; }
#scope1Ov .sc2.pk .sd2 { background:var(--primary-green); }
#scope1Ov .sc2 .sn3 { font-size:13px; font-weight:600; line-height:1.3; color:var(--gray-800); }
#scope1Ov .sc2 .sd3 { font-size:11px; color:var(--gray-600); margin-top:2px; line-height:1.3; }
#scope1Ov .fi, #scope1Ov .fsl, #scope1Ov .fta { width:100%; padding:10px 12px; font-size:14px; border:1.5px solid var(--gray-200); border-radius:10px; background:#fff; outline:none; color:var(--gray-800); font-family:inherit; box-sizing:border-box; }
#scope1Ov .fi:focus, #scope1Ov .fsl:focus, #scope1Ov .fta:focus { border-color:var(--primary-green); }
#scope1Ov .fsl { appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%235f6368' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 10px center; padding-right:32px; cursor:pointer; }
#scope1Ov .fta { resize:vertical; min-height:60px; }
#scope1Ov .fr { display:flex; gap:12px; }
#scope1Ov .fr .fg { flex:1; }
#scope1Ov .fn { display:flex; gap:10px; margin-top:16px; padding-top:16px; border-top:1px solid var(--gray-200); }
#scope1Ov .btn { padding:10px 18px; border-radius:10px; font-size:14px; font-weight:600; cursor:pointer; transition:all .12s; border:none; font-family:inherit; }
#scope1Ov .bp { flex:1; background:var(--primary-green); color:#fff; }
#scope1Ov .bp:hover { background:var(--dark-green); color:#fff; }
#scope1Ov .bs { background:var(--gray-100); color:var(--gray-600); border:1.5px solid var(--gray-200); }
#scope1Ov .bs:hover { background:var(--gray-200); color:var(--gray-800); }
#scope1Ov .bg { background:var(--light-green); color:#fff; flex:1; }
#scope1Ov .bg:hover { background:var(--primary-green); color:#fff; }
#scope1Ov .ferr .fi, #scope1Ov .ferr .fsl { border-color:var(--danger-red); }
#scope1Ov .fem { font-size:11px; color:var(--danger-red); margin-top:2px; display:none; }
#scope1Ov .ferr .fem { display:block; }
#scope1Ov .ef-box { margin:12px 0; padding:12px; border-radius:10px; background:rgba(2,119,189,0.08); border:1px solid rgba(2,119,189,0.2); }
#scope1Ov .ef-box .ef-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--primary-blue); margin-bottom:6px; }
#scope1Ov .ef-row { display:flex; gap:8px; flex-wrap:wrap; }
#scope1Ov .ef-item { flex:1; min-width:80px; background:#fff; border:1px solid rgba(2,119,189,0.25); border-radius:8px; padding:8px; text-align:center; }
#scope1Ov .ef-item .ef-val { font-size:14px; font-weight:800; color:var(--primary-blue); }
#scope1Ov .ef-item .ef-lbl { font-size:8.5px; color:var(--gray-600); margin-top:2px; text-transform:uppercase; }
#scope1Ov .co2box { padding:14px; border-radius:10px; text-align:center; margin-bottom:12px; background:rgba(46,125,50,0.08); border:1.5px solid rgba(46,125,50,0.2); }
#scope1Ov .co2box .co2v { font-size:24px; font-weight:800; color:var(--primary-green); }
#scope1Ov .co2box .co2l { font-size:11px; color:var(--gray-600); margin-top:2px; }
#scope1Ov .co2box .co2f { font-size:10px; color:var(--gray-600); margin-top:4px; font-style:italic; }
#scope1Ov .fup { border:2px dashed var(--gray-200); border-radius:10px; padding:16px; text-align:center; cursor:pointer; background:var(--gray-50); transition:all .2s; }
#scope1Ov .fup:hover { border-color:var(--gray-300); }
#scope1Ov .fup input { display:none; }
#scope1Ov .fup .fut { font-size:12px; color:var(--gray-600); }
#scope1Ov .fup .fuh { font-size:10px; color:var(--gray-600); margin-top:2px; }
#scope1Ov .ffl { margin-top:8px; display:flex; flex-wrap:wrap; gap:4px; }
#scope1Ov .ffi { display:inline-flex; align-items:center; gap:4px; padding:4px 8px; border-radius:6px; background:var(--gray-100); border:1px solid var(--gray-200); font-size:12px; color:var(--gray-600); }
#scope1Ov .suc.on { padding:32px 20px 24px; }
#scope1Ov .suc .sk { width:52px; height:52px; border-radius:50%; background:rgba(76,175,80,0.2); border:2px solid rgba(76,175,80,0.4); display:inline-flex; align-items:center; justify-content:center; font-size:24px; margin-bottom:10px; }
#scope1Ov .suc h3 { font-size:18px; font-weight:700; color:var(--primary-green); margin-bottom:4px; }
#scope1Ov .suc p { font-size:13px; color:var(--gray-600); line-height:1.5; margin-bottom:14px; }
#scope1Ov .suc .sr { display:flex; gap:10px; justify-content:center; flex-wrap:wrap; max-width:100%; }
#scope1Ov .suc .sr .btn { min-width:100px; flex-shrink:0; }
.scope1-app .mh { padding:18px 20px 0; display:flex; align-items:center; gap:11px; }
.scope1-app .mh .mi { width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; background:rgba(46,125,50,0.12); }
.scope1-app .mh .mt { flex:1; }
.scope1-app .mh .mt h2 { font-size:17px; font-weight:700; }
.scope1-app .mh .mt span { font-size:12px; color:var(--gray-600); }
.scope1-app .mh .mx { width:30px; height:30px; border-radius:7px; border:1px solid var(--gray-200); background:#fff; cursor:pointer; display:flex; align-items:center; justify-content:center; color:var(--gray-600); font-size:15px; flex-shrink:0; }
.scope1-app .stp { display:flex; padding:14px 20px 0; }
.scope1-app .st { flex:1; text-align:center; padding-bottom:12px; position:relative; }
.scope1-app .st .sb3 { position:absolute; bottom:0; left:0; right:0; height:3px; background:var(--gray-200); border-radius:2px; }
.scope1-app .st.dn .sb3 { background:var(--light-green); }
.scope1-app .st.ac2 .sb3 { background:var(--primary-green); }
.scope1-app .st .sn2 { width:22px; height:22px; border-radius:50%; border:2px solid var(--gray-200); display:inline-flex; align-items:center; justify-content:center; font-size:10px; font-weight:700; color:var(--gray-600); margin-bottom:2px; }
.scope1-app .st.ac2 .sn2 { border-color:var(--primary-green); background:var(--primary-green); color:#fff; }
.scope1-app .st.dn .sn2 { border-color:var(--light-green); background:var(--light-green); color:#fff; }
.scope1-app .st .stx { font-size:9.5px; font-weight:600; color:var(--gray-600); }
.scope1-app .st.ac2 .stx { color:var(--primary-green); }
.scope1-app .st.dn .stx { color:var(--light-green); }
.scope1-app .fp { padding:16px 20px 20px; display:none; }
.scope1-app .fp.show { display:block; }
.scope1-app .fg { margin-bottom:12px; }
.scope1-app .fg label { display:block; font-size:13px; font-weight:600; margin-bottom:3px; }
.scope1-app .fg label .rq { color:var(--danger-red); margin-left:1px; }
.scope1-app .fg .fh { font-size:11px; color:var(--gray-600); margin-bottom:3px; }
.scope1-app .fi, .scope1-app .fsl, .scope1-app .fta { width:100%; padding:8px 11px; font-size:13.5px; border:1.5px solid var(--gray-200); border-radius:10px; background:#fff; outline:none; color:var(--gray-800); }
.scope1-app .fi:focus, .scope1-app .fsl:focus, .scope1-app .fta:focus { border-color:var(--gray-300); }
.scope1-app .fsl { appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238b95a5' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 10px center; padding-right:28px; cursor:pointer; }
.scope1-app .fta { resize:vertical; min-height:52px; }
.scope1-app .fr { display:flex; gap:10px; }
.scope1-app .fr .fg { flex:1; }
.scope1-app .scb { display:flex; gap:4px; margin-bottom:10px; flex-wrap:wrap; }
.scope1-app .scbt { padding:5px 12px; border-radius:100px; font-size:12px; font-weight:600; cursor:pointer; border:1.5px solid var(--gray-200); background:#fff; color:var(--gray-600); transition:all .12s; }
.scope1-app .scbt:hover { border-color:var(--gray-300); }
.scope1-app .scbt.on { background:rgba(46,125,50,0.12); border-color:rgba(46,125,50,0.25); color:var(--primary-green); }
.scope1-app .sg { display:grid; grid-template-columns:1fr 1fr; gap:5px; max-height:240px; overflow-y:auto; padding-right:3px; }
.scope1-app .sc2 { padding:9px 10px; border:1.5px solid var(--gray-200); border-radius:10px; cursor:pointer; transition:all .1s; display:flex; align-items:center; gap:7px; }
.scope1-app .sc2:hover { border-color:var(--gray-300); background:#fafbfd; }
.scope1-app .sc2.pk { border-color:var(--primary-green); background:rgba(46,125,50,0.12); }
.scope1-app .sc2 .sd2 { width:6px; height:6px; border-radius:50%; background:var(--gray-200); flex-shrink:0; }
.scope1-app .sc2.pk .sd2 { background:var(--primary-green); }
.scope1-app .sc2 .sn3 { font-size:12px; font-weight:600; line-height:1.2; }
.scope1-app .sc2 .sd3 { font-size:10.5px; color:var(--gray-600); margin-top:1px; line-height:1.2; }
.scope1-app .ef-box { margin:10px 0 12px; padding:12px; border-radius:10px; background:var(--s1-bluebg); border:1px solid var(--s1-bluelt); }
.scope1-app .ef-box .ef-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--s1-blue); margin-bottom:6px; }
.scope1-app .ef-row { display:flex; gap:8px; flex-wrap:wrap; }
.scope1-app .ef-item { flex:1; min-width:80px; background:#fff; border:1px solid var(--s1-bluelt); border-radius:7px; padding:7px 8px; text-align:center; }
.scope1-app .ef-item .ef-val { font-size:15px; font-weight:800; color:var(--s1-blue); }
.scope1-app .ef-item .ef-lbl { font-size:9.5px; color:var(--gray-600); margin-top:1px; text-transform:uppercase; }
.scope1-app .co2box { padding:14px; border-radius:10px; text-align:center; margin-bottom:12px; background:rgba(46,125,50,0.12); border:1.5px solid rgba(46,125,50,0.25); }
.scope1-app .co2box .co2v { font-size:28px; font-weight:800; letter-spacing:-1px; color:var(--primary-green); }
.scope1-app .co2box .co2l { font-size:11.5px; color:var(--gray-600); margin-top:1px; }
.scope1-app .co2box .co2f { font-size:10.5px; color:var(--gray-600); margin-top:3px; font-style:italic; }
.scope1-app .fup { border:2px dashed var(--gray-200); border-radius:10px; padding:14px; text-align:center; cursor:pointer; transition:all .2s; background:var(--gray-50); }
.scope1-app .fup:hover { border-color:var(--gray-300); }
.scope1-app .fup input { display:none; }
.scope1-app .ffl { margin-top:5px; display:flex; flex-wrap:wrap; gap:3px; }
.scope1-app .ffi { display:inline-flex; align-items:center; gap:3px; padding:3px 7px; border-radius:5px; background:var(--gray-50); border:1px solid var(--gray-200); font-size:11px; color:var(--gray-600); }
.scope1-app .ferr .fi, .scope1-app .ferr .fsl { border-color:var(--danger-red); }
.scope1-app .fem { font-size:10.5px; color:var(--danger-red); margin-top:2px; display:none; }
.scope1-app .ferr .fem { display:block; }
.scope1-app .fn { display:flex; gap:8px; margin-top:6px; padding-top:12px; border-top:1px solid var(--gray-200); }
.scope1-app .btn { padding:8px 16px; border-radius:10px; font-size:13px; font-weight:600; cursor:pointer; transition:all .12s; border:none; }
.scope1-app .bp { flex:1; background:var(--primary-green); color:#fff; }
.scope1-app .bp:hover { background:var(--dark-green); color:#fff; }
.scope1-app .bs { background:var(--gray-50); color:var(--gray-600); border:1px solid var(--gray-200); }
.scope1-app .bs:hover { background:#e8eaf0; }
.scope1-app .bg { background:var(--light-green); color:#fff; flex:1; }
.scope1-app .bg:hover { background:#148c3e; color:#fff; }
.scope1-app .suc { display:none; text-align:center; padding:32px 20px 24px; }
.scope1-app .suc.on { display:block; }
.scope1-app .suc .sk { width:52px; height:52px; border-radius:50%; background:rgba(76,175,80,0.15); border:2px solid #bbf7d0; display:inline-flex; align-items:center; justify-content:center; font-size:24px; margin-bottom:8px; }
.scope1-app .suc h3 { font-size:17px; font-weight:700; color:var(--light-green); margin-bottom:3px; }
.scope1-app .suc p { font-size:13px; color:var(--gray-600); line-height:1.5; margin-bottom:12px; }
.scope1-app .suc .sr { display:flex; gap:8px; justify-content:center; flex-wrap:wrap; }
.scope1-app .suc .sr .btn { min-width:100px; }
.scope1-app #scope1Table_wrapper .dataTables_filter input { margin-left:6px; }

/* View Record modal — polished popup */
.scope1-view-modal-dialog { max-width: 560px; }
.scope1-view-modal-content { border: none; border-radius: 16px; overflow: hidden; box-shadow: 0 24px 48px rgba(0,0,0,.15); }
.scope1-view-modal-header {
  background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
  color: #fff; padding: 18px 20px; border-bottom: none;
}
.scope1-view-modal-icon {
  width: 48px; height: 48px; border-radius: 12px; background: rgba(255,255,255,.2);
  display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0;
}
.scope1-view-modal-subtitle { font-size: 0.8rem; opacity: .9; margin-top: 2px; }
.scope1-view-modal-body { padding: 24px; background: var(--gray-50); }
#scope1ViewRecordModal .scope1-view-details-card {
  background: #fff; border-radius: 12px; padding: 18px 20px; margin-bottom: 16px;
  border: 1px solid var(--gray-200); box-shadow: 0 2px 8px rgba(0,0,0,.04);
}
#scope1ViewRecordModal .scope1-view-co2e {
  background: linear-gradient(135deg, rgba(46,125,50,.1) 0%, rgba(76,175,80,.08) 100%);
  border: 1px solid rgba(46,125,50,.25); border-radius: 12px; padding: 14px 18px;
  text-align: center; margin-bottom: 18px;
}
#scope1ViewRecordModal .scope1-view-co2e .val { font-size: 1.75rem; font-weight: 800; color: var(--primary-green); letter-spacing: -0.02em; }
#scope1ViewRecordModal .scope1-view-co2e .lbl { font-size: 0.7rem; text-transform: uppercase; letter-spacing: .06em; color: var(--gray-600); margin-top: 2px; }
#scope1ViewRecordModal .scope1-view-row { display: flex; padding: 8px 0; border-bottom: 1px solid var(--gray-100); font-size: 0.9rem; }
#scope1ViewRecordModal .scope1-view-row:last-child { border-bottom: none; }
#scope1ViewRecordModal .scope1-view-row .k { color: var(--gray-600); min-width: 120px; flex-shrink: 0; }
#scope1ViewRecordModal .scope1-view-row .v { color: var(--gray-800); font-weight: 500; }
#scope1ViewRecordModal .scope1-view-attachments {
  background: #fff; border-radius: 12px; padding: 16px 18px; border: 1px solid var(--gray-200);
  box-shadow: 0 2px 8px rgba(0,0,0,.04);
}
#scope1ViewRecordModal .scope1-view-attachments .title { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--gray-600); margin-bottom: 10px; }
#scope1ViewRecordModal .scope1-view-attachment-item {
  display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 8px;
  background: var(--gray-50); border: 1px solid var(--gray-200); margin-bottom: 8px; font-size: 0.875rem;
  transition: background .15s, border-color .15s;
}
#scope1ViewRecordModal .scope1-view-attachment-item:last-child { margin-bottom: 0; }
#scope1ViewRecordModal .scope1-view-attachment-item:hover { background: #fff; border-color: var(--primary-green); }
#scope1ViewRecordModal .scope1-view-attachment-item a { color: var(--gray-800); text-decoration: none; flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
#scope1ViewRecordModal .scope1-view-attachment-item a:hover { color: var(--primary-green); }
#scope1ViewRecordModal .scope1-view-attachment-item .open-btn {
  flex-shrink: 0; padding: 6px 10px; border-radius: 8px; font-size: 0.75rem; font-weight: 600;
  background: var(--primary-green); color: #fff; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;
}
#scope1ViewRecordModal .scope1-view-attachment-item .open-btn:hover { background: var(--dark-green); color: #fff; }
#scope1ViewRecordModal .scope1-view-no-attach { color: var(--gray-600); font-size: 0.875rem; padding: 8px 0; }
</style>
@endpush

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="container-fluid py-4 scope1-app">
        <div class="topbar">
            <h2><span class="sb">1</span> Scope 1 - Direct Emissions</h2>
            <p>Fuel combustion, company vehicles, fugitive gas leaks</p>
            <button type="button" class="btn-add" id="scope1BtnAdd"><i class="fas fa-plus me-1"></i> Add Entry</button>
        </div>
        <div class="stats">
            <div class="sc"><div class="si r">&#128293;</div><div><div class="sv" id="scope1StatTotal">0</div><div class="sl">Total Entries</div></div></div>
            <div class="sc"><div class="si g">&#9981;</div><div><div class="sv" id="scope1StatStationary">0</div><div class="sl">Stationary</div></div></div>
            <div class="sc"><div class="si a">&#128663;</div><div><div class="sv" id="scope1StatMobile">0</div><div class="sl">Mobile</div></div></div>
            <div class="sc"><div class="si p">&#128168;</div><div><div class="sv" id="scope1StatFugitive">0</div><div class="sl">Fugitive</div></div></div>
        </div>
        <div class="ftabs" id="scope1FTabs"></div>
        <div class="tw">
            <div class="th2"><h3>Entries</h3></div>
            <div class="p-3">
                <table class="table table-sm table-hover mb-0" id="scope1Table" width="100%">
                    <thead><tr><th>Source</th><th>Quantity</th><th>tCO2e</th><th>Facility</th><th>Date</th><th>Attachments</th><th>Actions</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="ov" id="scope1Ov">
        <div class="mdl">
            <div class="mh">
                <div class="mi">&#128293;</div>
                <div class="mt"><h2>New Scope 1 Entry</h2><span>Direct Emissions</span></div>
                <button type="button" class="mx" id="scope1Mx" title="Close" aria-label="Close">&times;</button>
            </div>
            <div class="stp" role="tablist" aria-label="Form steps">
                <div class="st ac2" id="scope1Ms1"><span class="sn2">1</span><div class="stx">Source</div><div class="sb3"></div></div>
                <div class="st" id="scope1Ms2"><span class="sn2">2</span><div class="stx">Data</div><div class="sb3"></div></div>
                <div class="st" id="scope1Ms3"><span class="sn2">3</span><div class="stx">Review</div><div class="sb3"></div></div>
            </div>
            <div class="fp show" id="scope1P1">
                <div class="fg"><label>Sub-Category <span class="rq">*</span></label><div class="scb" id="scope1Scb"></div></div>
                <div class="fg" id="scope1FgSrc"><label>Emission Source <span class="rq">*</span></label><div class="fh">Select fuel type</div><div class="sg" id="scope1SrcG"></div><div class="fem">Please select a source</div></div>
                <div class="fn"><button type="button" class="btn bs" id="scope1N1c">Cancel</button><button type="button" class="btn bp" id="scope1N1n">Next &rarr;</button></div>
            </div>
            <div class="fp" id="scope1P2">
                <div class="ef-box" id="scope1EfBox" style="display:none"><div class="ef-title">&#128218; Emission Factors (auto-applied)</div><div class="ef-row" id="scope1EfRow"></div></div>
                <div class="fr"><div class="fg" id="scope1FgQty"><label>Quantity <span class="rq">*</span></label><input class="fi" type="number" id="scope1Fqty" placeholder="e.g. 5000" step="any" min="0"><div class="fem">Enter quantity</div></div><div class="fg" id="scope1FgUnit"><label>Unit <span class="rq">*</span></label><select class="fsl" id="scope1Funit"><option value="">Select...</option></select><div class="fem">Select unit</div></div></div>
                <div class="co2box" id="scope1Co2box" style="display:none"><div class="co2v" id="scope1Co2v">0.00</div><div class="co2l">tonnes CO2e</div><div class="co2f" id="scope1Co2f"></div></div>
                <div class="fr"><div class="fg" id="scope1FgPer"><label>Period <span class="rq">*</span></label><select class="fsl" id="scope1Fper"><option value="">Select...</option><option>Monthly</option><option>Quarterly</option><option>Annually</option></select><div class="fem">Select period</div></div><div class="fg" id="scope1FgDt"><label>Date <span class="rq">*</span></label><input class="fi" type="date" id="scope1Fdt"><div class="fem">Select date</div></div></div>
                <div class="fg" id="scope1FgFac"><label>Facility / Location <span class="rq">*</span></label><select class="fsl" id="scope1Ffac" required><option value="">Select facility...</option>@foreach(facilities() as $facility)<option value="{{ $facility->name }}">{{ $facility->name }}</option>@endforeach</select><div class="fem">Select facility</div></div>
                <div class="fg"><label>Notes</label><textarea class="fta" id="scope1Fdsc" placeholder="Optional..."></textarea></div>
                <div class="fn"><button type="button" class="btn bs" id="scope1N2b">&larr; Back</button><button type="button" class="btn bp" id="scope1N2n">Next &rarr;</button></div>
            </div>
            <div class="fp" id="scope1P3">
                <div class="fg"><label>Upload Evidence</label><div class="fup" id="scope1Fup"><span style="font-size:18px">&#128206;</span><div class="fut">Click to upload</div><div class="fuh">PDF, PNG, JPG, XLSX</div><input type="file" id="scope1Fupi" multiple accept=".pdf,.png,.jpg,.jpeg,.xlsx,.xls"></div><div class="ffl" id="scope1Ffl"></div></div>
                <div class="fg"><label>Summary</label><div id="scope1Rvw" style="padding:10px;border-radius:9px;background:var(--gray-50);border:1px solid var(--gray-200);font-size:13px;line-height:1.8;color:var(--gray-600)"></div></div>
                <div class="fn"><button type="button" class="btn bs" id="scope1N3b">&larr; Back</button><button type="button" class="btn bg" id="scope1N3s">&#10003; Submit</button></div>
            </div>
            <div class="suc" id="scope1Suc"><div class="sk">&#10003;</div><h3>Saved!</h3><p id="scope1SucM"></p><div class="sr"><button type="button" class="btn bs" id="scope1SucC">Close</button><button type="button" class="btn bp" id="scope1SucA">+ Another</button></div></div>
        </div>
    </div>

    <!-- Attachments modal -->
    <div class="modal fade" id="scope1AttachmentsModal" tabindex="-1" aria-labelledby="scope1AttachmentsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="scope1AttachmentsModalLabel"><i class="fas fa-paperclip me-2"></i>Attachments</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="scope1AttachmentsModalBody">
                    <p class="text-muted mb-0">No attachments.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- View Record modal (popup) -->
    <div class="modal fade" id="scope1ViewRecordModal" tabindex="-1" aria-labelledby="scope1ViewRecordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg scope1-view-modal-dialog">
            <div class="modal-content scope1-view-modal-content">
                <div class="modal-header scope1-view-modal-header">
                    <div class="d-flex align-items-center gap-3 flex-grow-1">
                        <div class="scope1-view-modal-icon"><i class="fas fa-leaf"></i></div>
                        <div>
                            <h5 class="modal-title mb-0" id="scope1ViewRecordModalLabel">Emission Record</h5>
                            <p class="scope1-view-modal-subtitle mb-0">Scope 1 · Direct emissions</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body scope1-view-modal-body" id="scope1ViewRecordModalBody">
                    <div class="text-center py-5 scope1-view-loading">
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
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
window.scope1Sources = {!! $sourcesJson !!};
window.scope1StoreUrl = @json($storeUrl);
window.scope1DataUrl = @json(route('scope1_entry.data'));
window.scope1StatsUrl = @json(route('scope1_entry.stats'));
window.scope1Csrf = @json(csrf_token());
</script>
@include('scope1_entry.script')
@endpush

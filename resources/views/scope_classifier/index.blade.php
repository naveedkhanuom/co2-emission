@extends('layouts.app')

@section('title', 'Scope Finder')
@section('page-title', 'Scope Finder')

@push('styles')
<style>
.scope-finder-page * { margin: 0; padding: 0; box-sizing: border-box; }
.scope-finder-page .sc-container { max-width: 900px; margin: 0 auto; padding: 32px 20px 80px; }
.scope-finder-page .sc-header { text-align: center; margin-bottom: 36px; }
.scope-finder-page .sc-header h1 { font-size: 26px; font-weight: 700; margin-bottom: 8px; color: var(--gray-800); }
.scope-finder-page .sc-header p { color: var(--gray-600); font-size: 15px; line-height: 1.5; }
.scope-finder-page .scope-tabs { display: flex; gap: 10px; margin-bottom: 28px; background: #fff; padding: 6px; border-radius: 12px; border: 1px solid var(--gray-200); box-shadow: 0 1px 3px rgba(0,0,0,.04); }
.scope-finder-page .scope-tab { flex: 1; padding: 12px 8px; border-radius: 10px; text-align: center; cursor: pointer; font-size: 13px; font-weight: 600; transition: all .25s; border: 2px solid transparent; }
.scope-finder-page .scope-tab .tab-num { font-size: 22px; font-weight: 700; display: block; margin-bottom: 2px; }
.scope-finder-page .scope-tab:hover { opacity: .9; background: var(--gray-100); }
.scope-finder-page .scope-tab[data-scope="1"] { color: var(--primary-green); }
.scope-finder-page .scope-tab[data-scope="2"] { color: var(--primary-blue); }
.scope-finder-page .scope-tab[data-scope="3"] { color: var(--dark-green); }
.scope-finder-page .scope-tab.active[data-scope="1"] { background: rgba(46, 125, 50, 0.08); border-color: rgba(46, 125, 50, 0.2); }
.scope-finder-page .scope-tab.active[data-scope="2"] { background: rgba(2, 119, 189, 0.08); border-color: rgba(2, 119, 189, 0.2); }
.scope-finder-page .scope-tab.active[data-scope="3"] { background: rgba(27, 94, 32, 0.08); border-color: rgba(27, 94, 32, 0.2); }
.scope-finder-page .scope-tab .tab-label { font-size: 11px; color: var(--gray-600); font-weight: 500; display: block; margin-top: 1px; }
.scope-finder-page .search-box { position: relative; margin-bottom: 24px; }
.scope-finder-page .search-box input { width: 100%; padding: 14px 16px 14px 44px; font-family: inherit; font-size: 15px; border: 1px solid var(--gray-200); border-radius: 12px; background: #fff; outline: none; color: var(--gray-800); transition: border .2s; }
.scope-finder-page .search-box input:focus { border-color: var(--primary-green); box-shadow: 0 0 0 3px rgba(46,125,50,.1); }
.scope-finder-page .search-box input::placeholder { color: var(--gray-600); }
.scope-finder-page .search-box .si { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); width: 20px; height: 20px; color: var(--gray-600); pointer-events: none; }
.scope-finder-page .cat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; }
.scope-finder-page .cat-tile { background: #fff; border: 2px solid var(--gray-200); border-radius: 12px; padding: 20px 16px 16px; text-align: center; cursor: pointer; transition: all .2s ease; position: relative; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.04); }
.scope-finder-page .cat-tile:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.08); border-color: var(--gray-300); }
.scope-finder-page .cat-tile.selected { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.08); }
.scope-finder-page .cat-tile.selected[data-scope="1"] { border-color: var(--primary-green); background: rgba(46, 125, 50, 0.08); }
.scope-finder-page .cat-tile.selected[data-scope="2"] { border-color: var(--primary-blue); background: rgba(2, 119, 189, 0.08); }
.scope-finder-page .cat-tile.selected[data-scope="3"] { border-color: var(--dark-green); background: rgba(27, 94, 32, 0.08); }
.scope-finder-page .cat-tile .tile-icon { font-size: 36px; display: block; margin-bottom: 10px; line-height: 1; }
.scope-finder-page .cat-tile .tile-name { font-size: 13px; font-weight: 600; line-height: 1.3; color: var(--gray-800); }
.scope-finder-page .cat-tile .tile-hint { font-size: 11px; color: var(--gray-600); margin-top: 4px; line-height: 1.3; }
.scope-finder-page .cat-tile .scope-dot { position: absolute; top: 10px; right: 10px; width: 10px; height: 10px; border-radius: 50%; opacity: 0; transition: opacity .25s; }
.scope-finder-page .cat-tile.selected .scope-dot { opacity: 1; }
.scope-finder-page .cat-tile[data-scope="1"] .scope-dot { background: var(--primary-green); }
.scope-finder-page .cat-tile[data-scope="2"] .scope-dot { background: var(--primary-blue); }
.scope-finder-page .cat-tile[data-scope="3"] .scope-dot { background: var(--dark-green); }
.scope-finder-page .cat-tile.hidden-tile { display: none; }
/* Modal popup - attractive design */
.scope-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.5); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); z-index: 1000; display: flex; align-items: center; justify-content: center; padding: 24px; opacity: 0; visibility: hidden; transition: opacity .3s ease, visibility .3s; }
.scope-modal-overlay.show { opacity: 1; visibility: visible; }
.scope-modal-overlay.show .scope-modal-dialog { transform: scale(1); opacity: 1; }
.scope-modal-dialog { background: #fff; border-radius: 24px; max-width: 460px; width: 100%; max-height: calc(100vh - 48px); overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 32px 64px rgba(0,0,0,.2), 0 0 0 1px rgba(0,0,0,.04); transform: scale(0.92); opacity: 0; transition: transform .35s cubic-bezier(0.34, 1.56, 0.64, 1), opacity .3s ease; }
.scope-modal-dialog:focus { outline: none; }
.scope-finder-page .result-panel { display: flex; flex-direction: column; overflow-y: auto; }
.scope-finder-page .result-panel.rs1 { border-top: 4px solid var(--primary-green); }
.scope-finder-page .result-panel.rs2 { border-top: 4px solid var(--primary-blue); }
.scope-finder-page .result-panel.rs3 { border-top: 4px solid var(--dark-green); }
.scope-finder-page .result-header { padding: 28px 28px 20px; display: flex; align-items: flex-start; gap: 18px; background: linear-gradient(180deg, var(--modal-header-bg) 0%, #fff 100%); }
.scope-finder-page .result-panel.rs1 .result-header { --modal-header-bg: rgba(46, 125, 50, 0.06); }
.scope-finder-page .result-panel.rs2 .result-header { --modal-header-bg: rgba(2, 119, 189, 0.06); }
.scope-finder-page .result-panel.rs3 .result-header { --modal-header-bg: rgba(27, 94, 32, 0.06); }
.scope-finder-page .result-header .result-badge-wrap { flex-shrink: 0; display: flex; align-items: center; justify-content: center; width: 56px; height: 56px; border-radius: 16px; position: relative; box-shadow: 0 4px 12px rgba(0,0,0,.12); }
.scope-finder-page .result-panel.rs1 .result-badge-wrap { background: linear-gradient(145deg, #2e7d32 0%, #1b5e20 100%); }
.scope-finder-page .result-panel.rs2 .result-badge-wrap { background: linear-gradient(145deg, #0277bd 0%, #01579b 100%); }
.scope-finder-page .result-panel.rs3 .result-badge-wrap { background: linear-gradient(145deg, #1b5e20 0%, #0d3d0d 100%); }
.scope-finder-page .result-header .result-badge { font-size: 22px; font-weight: 800; color: #fff; text-shadow: 0 1px 2px rgba(0,0,0,.2); }
.scope-finder-page .result-header .modal-cat-icon { font-size: 22px; position: absolute; right: -6px; top: -6px; line-height: 1; filter: drop-shadow(0 1px 2px rgba(0,0,0,.2)); background: #fff; border-radius: 50%; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 6px rgba(0,0,0,.12); }
.scope-finder-page .result-header .result-info { flex: 1; min-width: 0; }
.scope-finder-page .result-header .result-info .result-category { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
.scope-finder-page .result-panel.rs1 .result-header .result-info .result-category { color: var(--primary-green); }
.scope-finder-page .result-panel.rs2 .result-header .result-info .result-category { color: var(--primary-blue); }
.scope-finder-page .result-panel.rs3 .result-header .result-info .result-category { color: var(--dark-green); }
.scope-finder-page .result-header .result-info h3 { font-size: 18px; font-weight: 700; margin-bottom: 4px; line-height: 1.3; letter-spacing: -0.02em; }
.scope-finder-page .result-panel.rs1 .result-header .result-info h3 { color: #1b5e20; }
.scope-finder-page .result-panel.rs2 .result-header .result-info h3 { color: #01579b; }
.scope-finder-page .result-panel.rs3 .result-header .result-info h3 { color: #0d3d0d; }
.scope-finder-page .result-header .result-info p { font-size: 13px; line-height: 1.5; color: var(--gray-600); }
.scope-finder-page .result-header .close-result { width: 40px; height: 40px; border-radius: 12px; border: none; background: var(--gray-100); font-size: 22px; line-height: 1; cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--gray-500); flex-shrink: 0; transition: all .2s; }
.scope-finder-page .result-header .close-result:hover { background: var(--gray-200); color: var(--gray-800); transform: scale(1.05); }
.scope-finder-page .result-header .close-result:focus { outline: 2px solid var(--primary-green); outline-offset: 2px; }
.scope-finder-page .why-box { margin: 0 28px 20px; padding: 16px 18px; border-radius: 14px; border-left: 4px solid; }
.scope-finder-page .result-panel.rs1 .why-box { background: rgba(46, 125, 50, 0.06); border-left-color: var(--primary-green); }
.scope-finder-page .result-panel.rs2 .why-box { background: rgba(2, 119, 189, 0.06); border-left-color: var(--primary-blue); }
.scope-finder-page .result-panel.rs3 .why-box { background: rgba(27, 94, 32, 0.06); border-left-color: var(--dark-green); }
.scope-finder-page .why-box .why-label { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.2px; margin-bottom: 6px; }
.scope-finder-page .result-panel.rs1 .why-box .why-label { color: var(--primary-green); }
.scope-finder-page .result-panel.rs2 .why-box .why-label { color: var(--primary-blue); }
.scope-finder-page .result-panel.rs3 .why-box .why-label { color: var(--dark-green); }
.scope-finder-page .why-box .why-text { font-size: 13px; line-height: 1.6; color: var(--gray-700); }
.scope-finder-page .result-sources { padding: 0 28px 20px; }
.scope-finder-page .result-sources .sources-label { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.2px; color: var(--gray-500); margin-bottom: 10px; }
.scope-finder-page .source-chip-list { display: flex; flex-wrap: wrap; gap: 8px; }
.scope-finder-page .source-chip { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 10px; background: #fff; border: 1px solid var(--gray-200); font-size: 12px; font-weight: 500; color: var(--gray-800); box-shadow: 0 1px 2px rgba(0,0,0,.04); transition: transform .15s, box-shadow .15s; }
.scope-finder-page .source-chip:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,.08); }
.scope-finder-page .source-chip .chip-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.scope-finder-page .rs1 .source-chip .chip-dot { background: var(--primary-green); }
.scope-finder-page .rs2 .source-chip .chip-dot { background: var(--primary-blue); }
.scope-finder-page .rs3 .source-chip .chip-dot { background: var(--dark-green); }
.scope-finder-page .confirm-box { margin: 0 28px 28px; padding: 22px 0 0; border-top: 2px solid var(--gray-100); }
.scope-finder-page .confirm-box .confirm-label { font-size: 16px; font-weight: 700; color: var(--gray-800); margin-bottom: 4px; }
.scope-finder-page .confirm-box .confirm-hint { font-size: 13px; color: var(--gray-600); margin-bottom: 18px; line-height: 1.45; }
.scope-finder-page .confirm-actions { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
.scope-finder-page .confirm-actions .btn-confirm { padding: 14px 26px; border-radius: 14px; font-size: 14px; font-weight: 700; cursor: pointer; border: none; transition: all .25s; display: inline-flex; align-items: center; gap: 10px; letter-spacing: .02em; }
.scope-finder-page .confirm-actions .btn-confirm:focus { outline: 2px solid var(--primary-green); outline-offset: 2px; }
.scope-finder-page .confirm-actions .btn-go { color: #fff; box-shadow: 0 4px 14px rgba(0,0,0,.2); }
.scope-finder-page .confirm-actions .btn-go { background: linear-gradient(180deg, #2e7d32 0%, #1b5e20 100%); }
.scope-finder-page .confirm-actions .btn-go:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(46,125,50,.4); filter: brightness(1.05); }
.scope-finder-page .confirm-actions .btn-go:active { transform: translateY(0); }
.scope-finder-page .confirm-actions .btn-cancel { background: #fff; color: var(--gray-600); border: 2px solid var(--gray-200); }
.scope-finder-page .confirm-actions .btn-cancel:hover { background: var(--gray-50); color: var(--gray-800); border-color: var(--gray-300); }
.scope-finder-page .rs2 .confirm-actions .btn-go { background: linear-gradient(180deg, #0277bd 0%, #01579b 100%); box-shadow: 0 4px 14px rgba(2,119,189,.25); }
.scope-finder-page .rs2 .confirm-actions .btn-go:hover { box-shadow: 0 6px 20px rgba(2,119,189,.4); }
.scope-finder-page .rs3 .confirm-actions .btn-go { background: linear-gradient(180deg, #388e3c 0%, #1b5e20 100%); box-shadow: 0 4px 14px rgba(27,94,32,.25); }
.scope-finder-page .rs3 .confirm-actions .btn-go:hover { box-shadow: 0 6px 20px rgba(27,94,32,.4); }
.scope-finder-page .confirm-actions .btn-go .btn-arrow { font-size: 16px; opacity: .95; transition: transform .2s; }
.scope-finder-page .confirm-actions .btn-go:hover .btn-arrow { transform: translateX(3px); }
.scope-finder-page .no-match { text-align: center; padding: 40px 20px; color: var(--gray-600); font-size: 14px; display: none; }
.scope-finder-page .no-match.show { display: block; }
.scope-finder-page .no-match span { font-size: 32px; display: block; margin-bottom: 8px; color: var(--gray-300); }
.scope-finder-page .cat-tile { opacity: 0; animation: scPop .35s ease forwards; cursor: pointer; }
.scope-finder-page .cat-tile:focus { outline: 2px solid var(--primary-green); outline-offset: 2px; }
@keyframes scPop { from { opacity: 0; transform: scale(.95) translateY(8px); } to { opacity: 1; transform: scale(1) translateY(0); } }
@media (max-width: 600px) {
  .scope-finder-page .cat-grid { grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 10px; }
  .scope-finder-page .cat-tile { padding: 16px 12px 14px; }
  .scope-finder-page .cat-tile .tile-icon { font-size: 30px; margin-bottom: 8px; }
  .scope-finder-page .scope-tabs { flex-direction: column; gap: 6px; }
  .scope-finder-page .sc-container { padding: 20px 14px 60px; }
  .scope-modal-overlay { padding: 16px; }
  .scope-modal-dialog { max-height: calc(100vh - 32px); border-radius: 20px; }
  .scope-finder-page .result-header { padding: 24px 22px 18px; }
  .scope-finder-page .result-header .result-badge-wrap { width: 52px; height: 52px; }
  .scope-finder-page .result-header .modal-cat-icon { width: 26px; height: 26px; font-size: 18px; }
  .scope-finder-page .result-sources { padding: 0 22px 18px; }
  .scope-finder-page .why-box { margin: 0 22px 18px; }
  .scope-finder-page .confirm-box { margin: 0 22px 24px; }
  .scope-finder-page .confirm-actions .btn-confirm { padding: 16px 24px; width: 100%; justify-content: center; }
  .scope-finder-page .confirm-actions .btn-cancel { width: 100%; justify-content: center; }
}
</style>
@endpush

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="scope-finder-page">
        <div class="sc-container">
            <div class="sc-header">
                <h1><i class="fas fa-search" style="color: var(--primary-green); margin-right: 8px;"></i>What activity are you reporting?</h1>
                <p>Not sure which scope (1, 2, or 3) your activity belongs to? Pick a category below and we’ll tell you — easy for everyone.</p>
            </div>
            <div class="scope-tabs" id="scopeTabs">
                <div class="scope-tab" data-scope="1" id="tab1">
                    <span class="tab-num">1</span>Direct Emissions
                    <span class="tab-label">Fuel you burn, gas leaks</span>
                </div>
                <div class="scope-tab" data-scope="2" id="tab2">
                    <span class="tab-num">2</span>Purchased Energy
                    <span class="tab-label">Electricity, heating, cooling</span>
                </div>
                <div class="scope-tab" data-scope="3" id="tab3">
                    <span class="tab-num">3</span>Value Chain
                    <span class="tab-label">Travel, suppliers, waste</span>
                </div>
            </div>
            <div class="search-box">
                <svg class="si" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <input type="text" id="search" placeholder="Type to search... diesel, electricity, travel, waste">
            </div>
            <div class="cat-grid" id="grid"></div>
            <div class="no-match" id="noMatch"><span>?</span>No categories match your search</div>
        </div>

        <!-- Confirm scope popup -->
        <div class="scope-modal-overlay" id="scopeModal" role="dialog" aria-modal="true" aria-labelledby="rTitle" aria-describedby="rDesc">
            <div class="scope-modal-dialog" id="scopeModalDialog" tabindex="-1">
                <div class="result-panel" id="resultPanel">
                    <div class="result-header">
                        <div class="result-badge-wrap">
                            <span class="result-badge" id="rBadge"></span>
                            <span class="modal-cat-icon" id="rModalIcon" aria-hidden="true"></span>
                        </div>
                        <div class="result-info">
                            <div class="result-category" id="rCategory"></div>
                            <h3 id="rTitle"></h3>
                            <p id="rDesc"></p>
                        </div>
                        <button class="close-result" id="closeBtn" type="button" aria-label="Close">×</button>
                    </div>
                    <div class="why-box">
                        <div class="why-label">Why this scope?</div>
                        <div class="why-text" id="rWhy"></div>
                    </div>
                    <div class="result-sources">
                        <div class="sources-label">Emission sources in this category</div>
                        <div class="source-chip-list" id="rSources"></div>
                    </div>
                    <div class="confirm-box">
                        <div class="confirm-label">Ready to add this emission?</div>
                        <div class="confirm-hint">You’ll enter your activity data on the next page.</div>
                        <div class="confirm-actions">
                            <button type="button" class="btn-confirm btn-go" id="btnGoToScope"><span id="btnGoToScopeText">Go to Scope 1 entry</span> <span class="btn-arrow">→</span></button>
                            <button type="button" class="btn-confirm btn-cancel" id="btnCancelConfirm">Choose another category</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  var scopeUrls = {
    1: "{{ route('scope1_entry.index') }}",
    2: "{{ route('scope2_entry.index') }}",
    3: "{{ route('scope3_entry.index') }}"
  };
  var cats = [
    {id:"stationary",icon:"\uD83D\uDD25",name:"Boilers & Furnaces",hint:"On-site fuel combustion",scope:1,why:"You burn fuel directly in equipment you own like boilers, generators, and furnaces. That makes it a direct emission under your control.",sources:["Biomass","Coal","Diesel (Stationary)","Fuel Oil / Heating Oil","Gasoline (Stationary)","Kerosene","LPG / Propane","Natural Gas","Wood / Wood Pellets"]},
    {id:"fleet",icon:"\uD83D\uDE97",name:"Company Vehicles",hint:"Cars, vans, trucks you own",scope:1,why:"Fuel burned in vehicles your company owns or controls, from delivery vans to company cars.",sources:["Fleet - Gasoline","Fleet - Diesel","Fleet - Biodiesel","Fleet - CNG","Fleet - LPG"]},
    {id:"marine",icon:"\uD83D\uDEA2",name:"Ships & Marine",hint:"Company-owned vessels",scope:1,why:"Fuel used in boats or ships your company owns. Direct combustion means Scope 1.",sources:["Marine Vessels - Diesel","Marine Vessels - Heavy Fuel Oil"]},
    {id:"aviation",icon:"\u2708\uFE0F",name:"Company Aircraft",hint:"Owned planes and helicopters",scope:1,why:"Jet fuel burned in aircraft your company owns or operates directly.",sources:["Aviation (Company-owned) - Jet Fuel"]},
    {id:"offroad",icon:"\uD83D\uDE9C",name:"Off-road & Rail",hint:"Machinery, trains you own",scope:1,why:"Diesel in construction equipment, mining machinery, or company-owned rail, all under your operational control.",sources:["Off-road Machinery - Diesel","Rail (Company-owned) - Diesel"]},
    {id:"refrigerants",icon:"\u2744\uFE0F",name:"Refrigerant Leaks",hint:"AC, chillers, fridges",scope:1,why:"Refrigerant gases that leak from your cooling equipment are fugitive emissions. They escape from assets you own.",sources:["Refrigerant (CFCs)","Refrigerant (HCFCs)","Refrigerant (HFCs)","Refrigerant (HFOs / Natural)"]},
    {id:"fire",icon:"\uD83E\uDDEF",name:"Fire Suppression",hint:"Halon and HFC systems",scope:1,why:"Gases released from fire suppression systems you own. These are fugitive emissions under your control.",sources:["Fire Suppression (Halon)","Fire Suppression (HFCs)"]},
    {id:"process",icon:"\u2697\uFE0F",name:"Industrial Processes",hint:"Chemical and gas emissions",scope:1,why:"Process emissions from your industrial operations, like N2O from nitric acid production or PFCs from aluminium smelting.",sources:["N2O from Industrial Processes","PFCs (Aluminium / Semiconductors)","Methane Leakage (Gas Systems)","SF6 (Electrical Equipment)"]},
    {id:"electricity",icon:"\u26A1",name:"Electricity",hint:"Power from the grid",scope:2,why:"You buy electricity and the power plant burns fuel to generate it. The emissions happen there, not at your site, but on your behalf.",sources:["Purchased Electricity (Location-based)","Purchased Electricity (Market-based)","CHP - Electricity"]},
    {id:"heating",icon:"\uD83C\uDF21\uFE0F",name:"Heating & Cooling",hint:"District heat, steam, cooling",scope:2,why:"You purchase heat, steam, or cooling from a third party. They generate it and you consume it.",sources:["District Heating","District Cooling","Purchased Steam","Purchased Hot Water","CHP - Heat"]},
    {id:"goods",icon:"\uD83D\uDCE6",name:"Purchased Goods",hint:"Cat 1 - Raw materials and supplies",scope:3,why:"Emissions are embedded in the products and materials you buy from suppliers. These happen upstream in your supply chain.",sources:["Purchased Goods & Services"]},
    {id:"capital",icon:"\uD83C\uDFD7\uFE0F",name:"Capital Goods",hint:"Cat 2 - Machinery and buildings",scope:3,why:"Emissions from manufacturing the long-lived equipment, buildings, or IT infrastructure your company purchases.",sources:["Capital Goods"]},
    {id:"fuelenergy",icon:"\u26FD",name:"Fuel & Energy",hint:"Cat 3 - Well-to-tank, T&D losses",scope:3,why:"Upstream emissions from producing the fuels you use, plus electricity lost during transmission. Not covered in Scope 1 or 2.",sources:["Fuel & Energy Related Activities"]},
    {id:"upstream_transport",icon:"\uD83D\uDE9A",name:"Inbound Shipping",hint:"Cat 4 - Suppliers to You",scope:3,why:"Emissions from transporting purchased materials to your facilities via third-party carriers you do not control.",sources:["Upstream Transportation & Distribution"]},
    {id:"waste",icon:"\uD83D\uDDD1\uFE0F",name:"Waste Disposal",hint:"Cat 5 - Landfill, recycling",scope:3,why:"Emissions from third-party treatment of your operational waste including landfill, incineration, recycling, and composting.",sources:["Waste Generated in Operations"]},
    {id:"travel",icon:"\uD83E\uDDF3",name:"Business Travel",hint:"Cat 6 - Flights, hotels, taxis",scope:3,why:"Emissions from employee business trips using airlines, hotels, and rental cars. Vehicles and facilities you do not own.",sources:["Business Travel"]},
    {id:"commute",icon:"\uD83D\uDE8C",name:"Employee Commute",hint:"Cat 7 - Home to Office",scope:3,why:"Emissions from your employees traveling to work using their own cars, buses, or trains.",sources:["Employee Commuting"]},
    {id:"leased_up",icon:"\uD83D\uDD11",name:"Leased Assets (Up)",hint:"Cat 8 - Assets you lease in",scope:3,why:"Emissions from assets you lease from others that are not already counted in your Scope 1 or 2.",sources:["Upstream Leased Assets"]},
    {id:"downstream_transport",icon:"\uD83D\uDCE4",name:"Outbound Shipping",hint:"Cat 9 - You to Customers",scope:3,why:"Emissions from delivering your products to customers through third-party logistics providers.",sources:["Downstream Transportation & Distribution"]},
    {id:"processing",icon:"\uD83D\uDD27",name:"Product Processing",hint:"Cat 10 - By third parties",scope:3,why:"Emissions when another company further processes your intermediate products before final sale.",sources:["Processing of Sold Products"]},
    {id:"product_use",icon:"\uD83D\uDCA1",name:"Product Use",hint:"Cat 11 - By your customers",scope:3,why:"Emissions generated when end-customers use the products you sell, like energy consumption during operation.",sources:["Use of Sold Products"]},
    {id:"end_of_life",icon:"\u267B\uFE0F",name:"End-of-Life",hint:"Cat 12 - Product disposal",scope:3,why:"Emissions from the disposal, recycling, or incineration of your products after customers are done using them.",sources:["End-of-Life Treatment of Sold Products"]},
    {id:"leased_down",icon:"\uD83C\uDFE2",name:"Leased Assets (Down)",hint:"Cat 13 - Assets you lease out",scope:3,why:"Emissions from assets your company owns but leases to other organizations.",sources:["Downstream Leased Assets"]},
    {id:"franchise",icon:"\uD83C\uDFEA",name:"Franchises",hint:"Cat 14 - Franchise operations",scope:3,why:"Emissions from franchise operations. Applies if you are the franchisor reporting on your network.",sources:["Franchises"]},
    {id:"invest",icon:"\uD83D\uDCB0",name:"Investments",hint:"Cat 15 - Equity, debt, finance",scope:3,why:"Emissions associated with your financial investments. Especially relevant for banks and financial institutions.",sources:["Investments"]}
  ];
  var scopeNames = { 1: {title:"Scope 1 - Direct Emissions", desc:"Emissions from sources your company owns or directly controls"}, 2: {title:"Scope 2 - Purchased Energy", desc:"Indirect emissions from the energy you buy and consume"}, 3: {title:"Scope 3 - Value Chain", desc:"All other indirect emissions across your upstream and downstream value chain"} };
  var grid = document.getElementById("grid");
  var selectedTile = null;
  var activeScope = null;
  for (var i = 0; i < cats.length; i++) {
    (function(c, idx) {
      var tile = document.createElement("div");
      tile.className = "cat-tile";
      tile.setAttribute("data-scope", c.scope);
      tile.setAttribute("data-id", c.id);
      tile.style.animationDelay = (idx * 30) + "ms";
      tile.innerHTML = '<div class="scope-dot"></div><span class="tile-icon">' + c.icon + '</span><div class="tile-name">' + c.name + '</div><div class="tile-hint">' + c.hint + '</div>';
      tile.addEventListener("click", function() { selectCategory(c, tile); });
      grid.appendChild(tile);
    })(cats[i], i);
  }
  var pendingScopeUrl = null;
  function selectCategory(cat, tile) {
    if (selectedTile === tile) { selectedTile = null; closeResult(); return; }
    if (selectedTile) selectedTile.classList.remove("selected");
    tile.classList.add("selected");
    selectedTile = tile;
    var modal = document.getElementById("scopeModal");
    var panel = document.getElementById("resultPanel");
    var info = scopeNames[cat.scope];
    panel.className = "result-panel rs" + cat.scope;
    document.getElementById("rCategory").textContent = cat.name;
    document.getElementById("rBadge").textContent = cat.scope;
    document.getElementById("rModalIcon").textContent = cat.icon;
    document.getElementById("rTitle").textContent = info.title;
    document.getElementById("rDesc").textContent = info.desc;
    document.getElementById("rWhy").textContent = cat.why;
    document.getElementById("btnGoToScopeText").textContent = "Go to Scope " + cat.scope + " entry";
    var html = "";
    for (var j = 0; j < cat.sources.length; j++) { html += '<div class="source-chip"><span class="chip-dot"></span>' + cat.sources[j] + '</div>'; }
    document.getElementById("rSources").innerHTML = html;
    pendingScopeUrl = scopeUrls[cat.scope] || null;
    var tabs = document.querySelectorAll(".scope-tab");
    for (var k = 0; k < tabs.length; k++) tabs[k].classList.remove("active");
    document.getElementById("tab" + cat.scope).classList.add("active");
    modal.classList.add("show");
    document.body.style.overflow = "hidden";
    setTimeout(function() { document.getElementById("btnGoToScope").focus(); }, 100);
  }
  function closeResult() {
    document.getElementById("scopeModal").classList.remove("show");
    document.getElementById("resultPanel").className = "result-panel";
    document.body.style.overflow = "";
    if (selectedTile) {
      selectedTile.classList.remove("selected");
      selectedTile.focus({ preventScroll: true });
    }
    selectedTile = null;
    var tabs = document.querySelectorAll(".scope-tab");
    for (var k = 0; k < tabs.length; k++) tabs[k].classList.remove("active");
  }
  document.getElementById("closeBtn").addEventListener("click", closeResult);
  document.getElementById("btnGoToScope").addEventListener("click", function() {
    if (pendingScopeUrl) window.location.href = pendingScopeUrl;
  });
  document.getElementById("btnCancelConfirm").addEventListener("click", closeResult);
  document.getElementById("scopeModal").addEventListener("click", function(e) {
    if (e.target === this) closeResult();
  });
  document.addEventListener("keydown", function(e) {
    if (e.key === "Escape" && document.getElementById("scopeModal").classList.contains("show")) closeResult();
  });
  document.getElementById("search").addEventListener("input", function() {
    var q = this.value.trim().toLowerCase();
    var visible = 0;
    var tiles = document.querySelectorAll(".cat-tile");
    for (var i = 0; i < tiles.length; i++) {
      var t = tiles[i];
      var cid = t.getAttribute("data-id");
      var cat = null;
      for (var j = 0; j < cats.length; j++) { if (cats[j].id === cid) { cat = cats[j]; break; } }
      var match = q === "" || cat.name.toLowerCase().indexOf(q) !== -1 || cat.hint.toLowerCase().indexOf(q) !== -1 || cat.sources.join(" ").toLowerCase().indexOf(q) !== -1;
      if (match) { t.classList.remove("hidden-tile"); visible++; } else { t.classList.add("hidden-tile"); }
    }
    var nm = document.getElementById("noMatch");
    if (visible === 0 && q.length > 0) nm.classList.add("show"); else nm.classList.remove("show");
  });
  function handleTabClick(scope, el) {
    var tabs = document.querySelectorAll(".scope-tab");
    for (var k = 0; k < tabs.length; k++) tabs[k].classList.remove("active");
    if (activeScope === scope) { activeScope = null; var tiles = document.querySelectorAll(".cat-tile"); for (var i = 0; i < tiles.length; i++) tiles[i].classList.remove("hidden-tile"); return; }
    activeScope = scope;
    el.classList.add("active");
    var tiles = document.querySelectorAll(".cat-tile");
    for (var i = 0; i < tiles.length; i++) { var ts = parseInt(tiles[i].getAttribute("data-scope")); if (ts === scope) tiles[i].classList.remove("hidden-tile"); else tiles[i].classList.add("hidden-tile"); }
  }
  document.getElementById("tab1").addEventListener("click", function() { handleTabClick(1, this); });
  document.getElementById("tab2").addEventListener("click", function() { handleTabClick(2, this); });
  document.getElementById("tab3").addEventListener("click", function() { handleTabClick(3, this); });
})();
</script>
@endpush

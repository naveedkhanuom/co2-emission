(function() {
'use strict';

const categories = [
  { id:1, name:'Purchased Goods & Services', icon:'🛒', color:'#f06060', description:'Upstream emissions from production of purchased goods and services.', method:'spend', methodLabel:'Spend-Based', info:'Use the spend-based method: multiply your procurement spend by industry-average emission factors (kg CO₂e per $ spent). We show a suggested factor below — change it if you have your own.', fields:[{key:'category_name',label:'Spend Category',type:'text',placeholder:'e.g. Office Supplies, IT Equipment'},{key:'spend',label:'Annual Spend ($)',type:'number',placeholder:'e.g. 500000'},{key:'ef',label:'Emission Factor (kg CO₂e/$)',type:'number',placeholder:'e.g. 0.43',step:'0.001',defaultValue:'0.43',efHelp:'Suggested (EEIO average). Change if you have a different factor.'}], calcRow: r => (parseFloat(r.spend)||0) * (parseFloat(r.ef)||0) },
  { id:2, name:'Capital Goods', icon:'🏗️', color:'#e07840', description:'Emissions from purchased capital goods (machinery, buildings, vehicles, IT).', method:'spend', methodLabel:'Spend-Based', info:'Similar to Category 1 but for capital expenditures. Suggested factor is pre-filled — adjust if needed.', fields:[{key:'item',label:'Capital Good',type:'text',placeholder:'e.g. Server rack, Vehicle fleet'},{key:'spend',label:'Total Cost ($)',type:'number',placeholder:'e.g. 250000'},{key:'ef',label:'Emission Factor (kg CO₂e/$)',type:'number',placeholder:'e.g. 0.55',step:'0.001',defaultValue:'0.55',efHelp:'Suggested for capital goods. Change if you have your own.'}], calcRow: r => (parseFloat(r.spend)||0) * (parseFloat(r.ef)||0) },
  { id:3, name:'Fuel & Energy Related Activities', icon:'⚡', color:'#f5a623', description:'T&D losses, upstream fuel production, well-to-tank emissions.', method:'activity', methodLabel:'Activity-Based', info:'Covers emissions not in Scope 1/2. Suggested factor shown (e.g. electricity T&D). Varies by source.', fields:[{key:'source',label:'Energy Source',type:'select',options:['Electricity (T&D losses)','Natural Gas (WTT)','Diesel (WTT)','Gasoline (WTT)','Other']},{key:'quantity',label:'Annual Consumption',type:'number',placeholder:'kWh, litres, or therms'},{key:'unit',label:'Unit',type:'select',options:['kWh','litres','therms','gallons','tonnes']},{key:'ef',label:'Emission Factor (kg CO₂e/unit)',type:'number',placeholder:'e.g. 0.019',step:'0.0001',defaultValue:'0.019',efHelp:'Suggested for electricity T&D. Adjust for your energy source.'}], calcRow: r => (parseFloat(r.quantity)||0) * (parseFloat(r.ef)||0) },
  { id:4, name:'Upstream Transportation & Distribution', icon:'🚛', color:'#c9a530', description:'Inbound logistics: emissions from transporting purchased goods.', method:'distance', methodLabel:'Distance-Based', info:'Tonne-km emission factors by mode. Suggested value is for road (truck) — change for rail/sea/air.', fields:[{key:'mode',label:'Transport Mode',type:'select',options:['Road (truck)','Rail','Sea (container)','Air freight','Pipeline']},{key:'weight',label:'Weight (tonnes)',type:'number',placeholder:'e.g. 100'},{key:'distance',label:'Distance (km)',type:'number',placeholder:'e.g. 500'},{key:'ef',label:'Emission Factor (kg CO₂e/tonne-km)',type:'number',placeholder:'e.g. 0.107',step:'0.001',defaultValue:'0.107',efHelp:'Suggested for road freight. Rail ~0.03, sea ~0.01, air ~0.6.'}], calcRow: r => (parseFloat(r.weight)||0) * (parseFloat(r.distance)||0) * (parseFloat(r.ef)||0) },
  { id:5, name:'Waste Generated in Operations', icon:'🗑️', color:'#8bc34a', description:'Emissions from disposal of waste generated in your operations.', method:'waste', methodLabel:'Waste-Type Method', info:'Emission factors vary by waste type and disposal. Suggested value is for general waste to landfill.', fields:[{key:'waste_type',label:'Waste Type',type:'select',options:['General waste','Paper/Cardboard','Plastics','Food/Organic','Glass','Metals','E-waste','Construction']},{key:'disposal',label:'Disposal Method',type:'select',options:['Landfill','Recycling','Incineration','Composting','Anaerobic digestion']},{key:'weight',label:'Weight (tonnes/year)',type:'number',placeholder:'e.g. 50'},{key:'ef',label:'Emission Factor (kg CO₂e/tonne)',type:'number',placeholder:'e.g. 467',step:'0.1',defaultValue:'467',efHelp:'Suggested for landfill. Recycling lower; incineration varies.'}], calcRow: r => (parseFloat(r.weight)||0) * (parseFloat(r.ef)||0) },
  { id:6, name:'Business Travel', icon:'✈️', color:'#4ea3f7', description:'Emissions from flights, rail, car rentals, taxis, and hotel stays.', method:'travel', methodLabel:'Distance / Spend-Based', info:'We show a suggested emission factor. Short haul ~0.255, long haul ~0.15, rail ~0.041, hotel ~15 per night.', fields:[{key:'travel_type',label:'Travel Type',type:'select',options:['Flight — Short haul (<1500 km)','Flight — Medium haul','Flight — Long haul (>3700 km)','Rail','Car rental / Taxi','Hotel nights']},{key:'quantity',label:'Quantity',type:'number',placeholder:'km, nights, or trips'},{key:'unit',label:'Unit',type:'select',options:['passenger-km','nights','trips','km']},{key:'ef',label:'Emission Factor (kg CO₂e/unit)',type:'number',placeholder:'e.g. 0.255',step:'0.001',defaultValue:'0.255',efHelp:'Suggested for short-haul flight. Long haul 0.15, rail 0.041, hotel 15/night.'}], calcRow: r => (parseFloat(r.quantity)||0) * (parseFloat(r.ef)||0) },
  { id:7, name:'Employee Commuting', icon:'🚗', color:'#2e7d32', description:'Commuting by car, bus, rail, motorcycle, bicycle, etc.', method:'commute', methodLabel:'Distance-Based (Detailed)', info:'Emission factors are built in per transport mode (DEFRA). You only enter mode, distance, and employee count.', isDetailed:true, transportModes:{ gasoline_car:{label:'Car — Gasoline',factor:0.192}, diesel_car:{label:'Car — Diesel',factor:0.171}, hybrid_car:{label:'Car — Hybrid',factor:0.112}, electric_car:{label:'Car — Electric',factor:0.053}, carpool_2:{label:'Carpool (2 people)',factor:0.096}, carpool_4:{label:'Carpool (4 people)',factor:0.048}, bus:{label:'Bus',factor:0.089}, rail:{label:'Train / Rail',factor:0.041}, metro:{label:'Metro / Subway',factor:0.033}, motorcycle:{label:'Motorcycle',factor:0.113}, ebike:{label:'E-Bike / E-Scooter',factor:0.022}, bicycle:{label:'Bicycle',factor:0.0}, walking:{label:'Walking',factor:0.0} } },
  { id:8, name:'Upstream Leased Assets', icon:'🏢', color:'#a78bfa', description:'Emissions from leased assets not in Scope 1/2 (operating leases).', method:'spend', methodLabel:'Asset-Based', info:'Area or asset-based factors. Suggested value is for office space (kg CO₂e/m² or per unit).', fields:[{key:'asset',label:'Leased Asset',type:'text',placeholder:'e.g. Office space, Fleet vehicles'},{key:'quantity',label:'Quantity (m², units, or hours)',type:'number',placeholder:'e.g. 2000'},{key:'unit',label:'Unit',type:'select',options:['m²','units','hours','kWh']},{key:'ef',label:'Emission Factor (kg CO₂e/unit)',type:'number',placeholder:'e.g. 35',step:'0.01',defaultValue:'35',efHelp:'Suggested for office m². Change for vehicles or equipment.'}], calcRow: r => (parseFloat(r.quantity)||0) * (parseFloat(r.ef)||0) },
  { id:9, name:'Downstream Transportation & Distribution', icon:'📦', color:'#22d3ee', description:'Outbound logistics to customers (if not paid for by you).', method:'distance', methodLabel:'Distance-Based', info:'Same as Category 4: tonne-km factors. Suggested for road — adjust for rail/sea/air.', fields:[{key:'mode',label:'Transport Mode',type:'select',options:['Road (truck)','Rail','Sea (container)','Air freight','Last-mile delivery']},{key:'weight',label:'Weight (tonnes)',type:'number',placeholder:'e.g. 50'},{key:'distance',label:'Distance (km)',type:'number',placeholder:'e.g. 200'},{key:'ef',label:'Emission Factor (kg CO₂e/tonne-km)',type:'number',placeholder:'e.g. 0.107',step:'0.001',defaultValue:'0.107',efHelp:'Suggested for road. Rail ~0.03, sea ~0.01, air ~0.6.'}], calcRow: r => (parseFloat(r.weight)||0) * (parseFloat(r.distance)||0) * (parseFloat(r.ef)||0) },
  { id:10, name:'Processing of Sold Products', icon:'🏭', color:'#f472b6', description:'Emissions from third-party processing of your intermediate products.', method:'spend', methodLabel:'Activity / Average-Data', info:'Factor per unit processed. Suggested value is illustrative — use your sector data if available.', fields:[{key:'product',label:'Product',type:'text',placeholder:'e.g. Steel sheets, Chemical feedstock'},{key:'quantity',label:'Quantity Sold (units or tonnes)',type:'number',placeholder:'e.g. 10000'},{key:'ef',label:'Emission Factor (kg CO₂e/unit processed)',type:'number',placeholder:'e.g. 12.5',step:'0.01',defaultValue:'12.5',efHelp:'Suggested average. Replace with your sector or supplier data.'}], calcRow: r => (parseFloat(r.quantity)||0) * (parseFloat(r.ef)||0) },
  { id:11, name:'Use of Sold Products', icon:'🔌', color:'#fb923c', description:'Emissions from customer use of your products (energy, fuel consumption).', method:'use', methodLabel:'Product Lifetime Method', info:'Grid emission factor (kg CO₂e/kWh). Suggested value is a typical grid average — use your region if known.', fields:[{key:'product',label:'Product',type:'text',placeholder:'e.g. Electric appliance, Vehicle'},{key:'units_sold',label:'Units Sold',type:'number',placeholder:'e.g. 5000'},{key:'energy_per_use',label:'Energy per Year of Use (kWh)',type:'number',placeholder:'e.g. 300'},{key:'lifetime',label:'Product Lifetime (years)',type:'number',placeholder:'e.g. 10'},{key:'ef',label:'Grid EF (kg CO₂e/kWh)',type:'number',placeholder:'e.g. 0.42',step:'0.001',defaultValue:'0.42',efHelp:'Suggested grid average. Use national/regional factor if available.'}], calcRow: r => (parseFloat(r.units_sold)||0) * (parseFloat(r.energy_per_use)||0) * (parseFloat(r.lifetime)||0) * (parseFloat(r.ef)||0) },
  { id:12, name:'End-of-Life Treatment of Sold Products', icon:'♻️', color:'#84cc16', description:'Waste treatment emissions when customers dispose of your products.', method:'waste', methodLabel:'Waste-Type Method', info:'Factor depends on material and disposal. Suggested for mixed materials to landfill.', fields:[{key:'material',label:'Primary Material',type:'select',options:['Plastics','Metals','Paper/Cardboard','Glass','Electronics','Textiles','Mixed']},{key:'disposal',label:'Likely Disposal',type:'select',options:['Landfill','Recycling','Incineration','Composting']},{key:'weight',label:'Total Product Weight Sold (tonnes)',type:'number',placeholder:'e.g. 200'},{key:'ef',label:'Emission Factor (kg CO₂e/tonne)',type:'number',placeholder:'e.g. 300',step:'0.1',defaultValue:'300',efHelp:'Suggested for mixed/landfill. Varies by material and disposal.'}], calcRow: r => (parseFloat(r.weight)||0) * (parseFloat(r.ef)||0) },
  { id:13, name:'Downstream Leased Assets', icon:'🔑', color:'#818cf8', description:'Emissions from assets you own that are leased to others.', method:'spend', methodLabel:'Asset-Based', info:'Same as Category 8. Suggested factor for leased space/equipment.', fields:[{key:'asset',label:'Leased Asset',type:'text',placeholder:'e.g. Commercial property, Equipment'},{key:'quantity',label:'Quantity (m², units)',type:'number',placeholder:'e.g. 5000'},{key:'unit',label:'Unit',type:'select',options:['m²','units','kWh','hours']},{key:'ef',label:'Emission Factor (kg CO₂e/unit)',type:'number',placeholder:'e.g. 35',step:'0.01',defaultValue:'35',efHelp:'Suggested for property/equipment. Change if you have better data.'}], calcRow: r => (parseFloat(r.quantity)||0) * (parseFloat(r.ef)||0) },
  { id:14, name:'Franchises', icon:'🏪', color:'#e879f9', description:'Emissions from franchise operations not in Scope 1/2.', method:'spend', methodLabel:'Franchise-Based', info:'Revenue or energy-based. Suggested factor is illustrative — use franchise data if available.', fields:[{key:'franchise',label:'Franchise / Region',type:'text',placeholder:'e.g. US West Coast stores'},{key:'quantity',label:'Energy Use or Revenue ($)',type:'number',placeholder:'e.g. 1000000'},{key:'unit',label:'Metric',type:'select',options:['kWh','$ revenue','m² floor area']},{key:'ef',label:'Emission Factor (kg CO₂e/unit)',type:'number',placeholder:'e.g. 0.42',step:'0.001',defaultValue:'0.42',efHelp:'Suggested average. Replace with franchise-specific data if available.'}], calcRow: r => (parseFloat(r.quantity)||0) * (parseFloat(r.ef)||0) },
  { id:15, name:'Investments', icon:'💰', color:'#fbbf24', description:'Emissions from equity investments, debt, project finance.', method:'investment', methodLabel:'Investment-Based', info:'For equity: (% ownership) × investee Scope 1+2. No emission factor field — you enter investee emissions directly.', fields:[{key:'investee',label:'Investee / Fund',type:'text',placeholder:'e.g. Company A, Green Bond Fund'},{key:'invested',label:'Amount Invested ($)',type:'number',placeholder:'e.g. 5000000'},{key:'ownership',label:'Ownership % (or 100 for debt)',type:'number',placeholder:'e.g. 15',step:'0.1'},{key:'investee_emissions',label:'Investee Emissions (tCO₂e)',type:'number',placeholder:'e.g. 25000'}], calcRow: r => (parseFloat(r.ownership)||0) / 100 * (parseFloat(r.investee_emissions)||0) * 1000 },
];

const state = { activeCategory: 1, workingDays: 230, data: {}, results: {} };
categories.forEach(c => { state.data[c.id] = []; state.results[c.id] = 0; });

var SCOPE3_SAVE_PREFS_KEY = 'scope3_calc_save_prefs';

// Suggested emission factors that depend on dropdown selection
var SCOPE3_EF_BY_TRAVEL_TYPE = {
  'Flight — Short haul (<1500 km)': '0.255',
  'Flight — Medium haul': '0.195',
  'Flight — Long haul (>3700 km)': '0.150',
  'Rail': '0.041',
  'Train/Rail': '0.041',
  'Car rental / Taxi': '0.192',
  'Hotel nights': '15'
};

var SCOPE3_EF_BY_FREIGHT_MODE = {
  'Road (truck)': '0.107',
  'Rail': '0.030',
  'Sea (container)': '0.010',
  'Air freight': '0.600',
  'Last-mile delivery': '0.180',
  'Pipeline': '0.020'
};

var SCOPE3_EF_BY_ENERGY_SOURCE = {
  'Electricity (T&D losses)': '0.019',
  'Natural Gas (WTT)': '0.050',
  'Diesel (WTT)': '0.070',
  'Gasoline (WTT)': '0.060',
  'Other': ''
};

function setEfIfNotUserEdited(catId, idx, nextEf) {
  var efEl = document.querySelector('input[data-cat="' + catId + '"][data-idx="' + idx + '"][data-key="ef"]');
  if (!efEl) return;
  var edited = efEl.dataset.userEdited === '1';
  if (edited) return;
  efEl.value = nextEf;
  // keep state in sync
  if (state.data[catId] && state.data[catId][idx]) state.data[catId][idx].ef = nextEf;
}

function handleFieldChange(catId, idx, key, value) {
  // keep state in sync (so switching pages keeps values)
  if (state.data[catId] && state.data[catId][idx]) state.data[catId][idx][key] = value;

  // Auto-fill EF based on selections (unless user manually edited EF)
  if (key === 'travel_type') {
    var ef = SCOPE3_EF_BY_TRAVEL_TYPE[value];
    if (ef !== undefined) setEfIfNotUserEdited(catId, idx, ef);
  }
  if (key === 'mode' && (catId === 4 || catId === 9)) {
    var ef2 = SCOPE3_EF_BY_FREIGHT_MODE[value];
    if (ef2 !== undefined) setEfIfNotUserEdited(catId, idx, ef2);
  }
  if (key === 'source' && catId === 3) {
    var ef3 = SCOPE3_EF_BY_ENERGY_SOURCE[value];
    if (ef3 !== undefined && ef3 !== '') setEfIfNotUserEdited(catId, idx, ef3);
  }
}

function loadSavePrefs() {
  try {
    var raw = localStorage.getItem(SCOPE3_SAVE_PREFS_KEY);
    if (!raw) return;
    var prefs = JSON.parse(raw);
    var dateEl = document.getElementById('scope3SaveEntryDate');
    var facilityEl = document.getElementById('scope3SaveFacility');
    var siteEl = document.getElementById('scope3SaveSite');
    if (dateEl && prefs.entryDate) dateEl.value = prefs.entryDate;
    if (facilityEl && prefs.facility) facilityEl.value = prefs.facility;
    if (siteEl && prefs.site) siteEl.value = prefs.site;
  } catch (e) {}
}

function saveSavePrefs() {
  try {
    var dateEl = document.getElementById('scope3SaveEntryDate');
    var facilityEl = document.getElementById('scope3SaveFacility');
    var siteEl = document.getElementById('scope3SaveSite');
    if (!dateEl || !facilityEl) return;
    var prefs = {
      entryDate: dateEl.value || '',
      facility: facilityEl.value || '',
      site: (siteEl && siteEl.value) ? siteEl.value : ''
    };
    localStorage.setItem(SCOPE3_SAVE_PREFS_KEY, JSON.stringify(prefs));
  } catch (e) {}
}

function closeSidebar() {}

function renderSidebar() {
  const nav = document.getElementById('sidebarNav');
  if (!nav) return;
  nav.innerHTML = categories.map(c => `
    <div class="scope3-cat-item ${state.activeCategory===c.id?'active':''} ${state.results[c.id]>0?'completed':''}" onclick="window.scope3GoToCategory(${c.id})" id="nav-${c.id}" role="button" tabindex="0">
      <div class="cat-num">${c.id}</div>
      <span class="cat-name">${c.name}</span>
      <span class="check-mark"><i class="fas fa-check"></i></span>
    </div>
  `).join('') + `
    <div class="scope3-cat-item ${state.activeCategory==='summary'?'active':''}" onclick="scope3ShowSummary()" role="button" tabindex="0" style="margin-top:8px;border-top:1px solid var(--gray-200);padding-top:12px;">
      <div class="cat-num" style="background:var(--warning-orange);color:#fff;">Σ</div>
      <span class="cat-name">Summary & Totals</span>
    </div>
  `;
}

function goToCategory(id) {
  state.activeCategory = id;
  renderSidebar();
  renderCategoryPage(id);
  closeSidebar();
}

function renderCategoryPage(id) {
  const c = categories.find(x => x.id === id);
  const main = document.getElementById('mainContent');
  if (!main || !c) return;
  if (c.isDetailed && c.id === 7) { renderCommutingPage(c); return; }
  var rows = state.data[id];
  if (rows.length === 0) {
    var emptyRow = {};
    c.fields.forEach(function(f) { emptyRow[f.key] = (f.defaultValue !== undefined ? f.defaultValue : ''); });
    state.data[id].push(emptyRow);
    rows = state.data[id];
  }
  let rowsHTML = '';
  rows.forEach((row, idx) => { rowsHTML += renderGenericRow(c, row, idx); });
  main.innerHTML = `
    <div class="category-section">
      <div class="mb-4">
        <span class="badge bg-secondary me-2">Category ${c.id}</span>
        <span class="badge bg-light text-dark">${c.methodLabel}</span>
        <h4 class="mt-2 mb-1">${c.icon} ${c.name}</h4>
        <p class="text-muted mb-0">${c.description}</p>
      </div>
      <div class="alert alert-info d-flex gap-2 mb-3">
        <i class="fas fa-info-circle mt-1"></i>
        <span>${c.info}</span>
      </div>
      <p class="scope3-tip-inline mb-3">Fill in the row(s) below, then click <strong>Save &amp; Calculate</strong> to save this category to the database.</p>
      <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex align-items-center gap-3">
          <div class="rounded-3 bg-light d-flex align-items-center justify-content-center" style="width:48px;height:48px;font-size:1.5rem;">${c.icon}</div>
          <div>
            <h5 class="mb-0">Data Entry</h5>
            <p class="text-muted small mb-0">Add as many rows as you need. Each row is included in the total.</p>
          </div>
        </div>
        <div class="card-body">
          <div class="scope3-dyn-rows" id="dynRows-${c.id}">${rowsHTML}</div>
          <button type="button" class="btn btn-outline-success btn-sm mt-2" onclick="window.scope3AddGenericRow(${c.id})"><i class="fas fa-plus me-1"></i>Add Row</button>
          <div class="d-flex justify-content-end mt-3">
            <button type="button" class="btn btn-success btn-lg" onclick="window.scope3SaveCategory(${c.id})"><i class="fas fa-save me-2"></i>Save &amp; Calculate</button>
          </div>
        </div>
      </div>
    </div>
  `;
}

function renderGenericRow(c, row, idx) {
  let fieldsHTML = c.fields.map(f => {
    var val = (row[f.key] !== undefined && row[f.key] !== '') ? row[f.key] : (f.defaultValue !== undefined ? f.defaultValue : '');
    var valEsc = ('' + val).replace(/"/g, '&quot;');
    if (f.type === 'select') {
      const opts = f.options.map(o => `<option value="${o}" ${row[f.key]===o?'selected':''}>${o}</option>`).join('');
      return `<div><div class="mini-label">${f.label}</div><select class="form-select form-select-sm" data-key="${f.key}" data-cat="${c.id}" data-idx="${idx}" onchange="window.scope3HandleFieldChange(${c.id},${idx},'${f.key}', this.value)"><option value="">Select...</option>${opts}</select></div>`;
    }
    var inputHtml = `<div><div class="mini-label">${f.label}</div><input type="${f.type}" class="form-control form-control-sm" data-key="${f.key}" data-cat="${c.id}" data-idx="${idx}" value="${valEsc}" placeholder="${f.placeholder||''}" ${f.step?'step="'+f.step+'"':''}></div>`;
    if (f.key === 'ef' && (f.efHelp || f.defaultValue)) {
      inputHtml = `<div><div class="mini-label">${f.label}</div><input type="${f.type}" class="form-control form-control-sm" data-key="${f.key}" data-cat="${c.id}" data-idx="${idx}" value="${valEsc}" placeholder="${f.placeholder||''}" ${f.step?'step="'+f.step+'"':''}><div class="scope3-ef-help"><i class="fas fa-info-circle me-1"></i>${f.efHelp || ('Suggested: ' + (f.defaultValue || '') + ' — change if you have your own factor.')}</div></div>`;
    }
    return inputHtml;
  }).join('');
  return `<div class="scope3-dyn-row" style="grid-template-columns:${'1fr '.repeat(c.fields.length)}auto;" id="row-${c.id}-${idx}">${fieldsHTML}<div class="align-self-end"><button type="button" class="btn btn-outline-danger btn-sm" onclick="window.scope3RemoveGenericRow(${c.id},${idx})" title="Remove row"><i class="fas fa-times"></i></button></div></div>`;
}

function addGenericRow(catId) {
  const c = categories.find(x => x.id === catId);
  if (!c) return;
  const newRow = {};
  c.fields.forEach(f => { newRow[f.key] = (f.defaultValue !== undefined ? f.defaultValue : ''); });
  state.data[catId].push(newRow);
  renderCategoryPage(catId);
}

function removeGenericRow(catId, idx) {
  state.data[catId].splice(idx, 1);
  renderCategoryPage(catId);
}

function saveCategoryToDatabase(catId, totalKg, categoryName) {
  const storeUrl = window.scope3StoreUrl;
  const categoryMap = window.scope3CategoryMap || {};
  const token = window.scope3CsrfToken;
  const dateEl = document.getElementById('scope3SaveEntryDate');
  const facilityEl = document.getElementById('scope3SaveFacility');
  const siteEl = document.getElementById('scope3SaveSite');
  const statusEl = document.getElementById('scope3SaveCategoryStatus');
  if (!storeUrl || !token || !dateEl || !facilityEl) return Promise.resolve(false);
  const entryDate = dateEl.value && dateEl.value.trim();
  const facility = facilityEl.value && facilityEl.value.trim();
  if (!entryDate || !facility) {
    if (statusEl) statusEl.innerHTML = '<span class="text-warning">Set date and facility above to save to database.</span>';
    return Promise.resolve(false);
  }
  const map = categoryMap[catId];
  if (!map) {
    if (statusEl) statusEl.innerHTML = '<span class="text-danger">Category not mapped.</span>';
    return Promise.resolve(false);
  }
  const entry = {
    entryDate: entryDate,
    facilitySelect: facility,
    siteSelect: (siteEl && siteEl.value) ? siteEl.value : null,
    scopeSelect: '3',
    emissionSourceSelect: map.emission_source_name,
    co2eValue: totalKg / 1000,
    confidenceLevel: 'medium',
    dataSource: 'manual',
    scope3_category_id: map.scope3_category_id,
    entryNotes: 'Scope 3 Calculator - Category ' + catId + ': ' + categoryName,
  };
  if (statusEl) statusEl.innerHTML = '<span class="text-muted">Saving to database...</span>';
  return fetch(storeUrl, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
    body: JSON.stringify({ _token: token, entries: [entry], status: 'active' })
  })
    .then(r => r.json().then(data => ({ ok: r.ok, data })).catch(() => ({ ok: r.ok, data: null })))
    .then(({ ok, data }) => {
      if (statusEl) {
        if (ok) statusEl.innerHTML = '<span class="text-success">Saved to Emission Records.</span>';
        else statusEl.innerHTML = '<span class="text-danger">' + (data && data.message ? data.message : 'Save failed') + '</span>';
      }
      return ok;
    })
    .catch(() => {
      if (statusEl) statusEl.innerHTML = '<span class="text-danger">Network error.</span>';
      return false;
    });
}

function saveCategory(catId) {
  const c = categories.find(x => x.id === catId);
  const container = document.getElementById('dynRows-' + catId);
  if (!container) return;
  container.querySelectorAll('input, select').forEach(el => {
    const key = el.dataset.key;
    const idx = parseInt(el.dataset.idx, 10);
    if (state.data[catId][idx]) state.data[catId][idx][key] = el.value;
  });
  let total = 0;
  state.data[catId].forEach(row => { total += c.calcRow(row); });
  state.results[catId] = total;
  persistScope3ResultsToSession();
  renderSidebar();
  saveCategoryToDatabase(catId, total, c.name).then(function(saved) {
    saveSavePrefs();
    var statusEl = document.getElementById('scope3SaveCategoryStatus');
    var tonnes = (total / 1000).toFixed(2);
    if (saved && statusEl) statusEl.innerHTML = '<span class="text-success">Saved! Category ' + catId + ': ' + formatNum(tonnes) + ' t CO₂e</span>';
    var msg = 'Category ' + catId + ': ' + formatNum(total.toFixed(0)) + ' kg CO₂e (' + formatNum(tonnes) + ' t)';
    if (saved) alert('Saved to database.\n' + msg);
    else alert('Calculated.\n' + msg + '\n\nSet date and facility in the green box above to save to the database.');
  });
}

function renderCommutingPage(c) {
  const main = document.getElementById('mainContent');
  if (!main) return;
  if (state.data[7].length === 0) state.data[7].push({ mode: '', distance: '', employees: '' });
  const rows = state.data[7];
  let rowsHTML = rows.map((row, idx) => `
    <div class="scope3-dyn-row" style="grid-template-columns:1fr 120px 100px auto;" id="row-7-${idx}">
      <div><div class="mini-label">Transport Mode</div>
        <select class="form-select form-select-sm" data-key="mode" data-cat="7" data-idx="${idx}">
          <option value="">Select...</option>
          ${Object.entries(c.transportModes).map(([k,v]) => `<option value="${k}" ${row.mode===k?'selected':''}>${v.label}</option>`).join('')}
        </select>
      </div>
      <div><div class="mini-label">Distance (km, one-way)</div><input type="number" class="form-control form-control-sm" data-key="distance" data-cat="7" data-idx="${idx}" value="${row.distance||''}" placeholder="km" min="0" step="0.1"></div>
      <div><div class="mini-label">Employees</div><input type="number" class="form-control form-control-sm" data-key="employees" data-cat="7" data-idx="${idx}" value="${row.employees||''}" placeholder="#" min="1"></div>
      <div class="align-self-end"><button type="button" class="btn btn-outline-danger btn-sm" onclick="window.scope3RemoveGenericRow(7,${idx})"><i class="fas fa-times"></i></button></div>
    </div>
  `).join('');
  main.innerHTML = `
    <div class="category-section">
      <div class="mb-4">
        <span class="badge bg-success me-2">Category 7</span>
        <span class="badge bg-light text-dark">${c.methodLabel}</span>
        <h4 class="mt-2 mb-1">${c.icon} ${c.name}</h4>
        <p class="text-muted mb-0">${c.description}</p>
      </div>
      <div class="alert alert-info d-flex gap-2 mb-4"><i class="fas fa-info-circle mt-1"></i><span>${c.info}</span></div>
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
          <label class="form-label">Working Days / Year</label>
          <input type="number" id="workingDaysInput" class="form-control" value="${state.workingDays}" min="1" max="365" style="max-width:180px;">
        </div>
      </div>
      <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex align-items-center gap-3">
          <div class="rounded-3 bg-light d-flex align-items-center justify-content-center" style="width:48px;height:48px;font-size:1.5rem;">🚗</div>
          <div><h5 class="mb-0">Commuting Groups</h5><p class="text-muted small mb-0">Add a row for each group of employees sharing a commuting pattern.</p></div>
        </div>
        <div class="card-body">
          <div class="scope3-dyn-rows" id="dynRows-7">${rowsHTML}</div>
          <button type="button" class="btn btn-outline-success btn-sm mt-2" onclick="window.scope3AddCommuteRow()"><i class="fas fa-plus me-1"></i>Add Commuting Group</button>
          <div class="d-flex justify-content-end mt-3"><button type="button" class="btn btn-success" onclick="window.scope3SaveCommuting()"><i class="fas fa-save me-2"></i>Save & Calculate</button></div>
        </div>
      </div>
    </div>
  `;
}

function addCommuteRow() {
  state.data[7].push({mode:'', distance:'', employees:''});
  renderCommutingPage(categories.find(x=>x.id===7));
}

function saveCommuting() {
  const c = categories.find(x => x.id === 7);
  const wdEl = document.getElementById('workingDaysInput');
  state.workingDays = wdEl ? parseInt(wdEl.value, 10) || 230 : 230;
  const container = document.getElementById('dynRows-7');
  if (container) container.querySelectorAll('input, select').forEach(el => {
    const key = el.dataset.key;
    const idx = parseInt(el.dataset.idx, 10);
    if (state.data[7][idx]) state.data[7][idx][key] = el.value;
  });
  let total = 0;
  state.data[7].forEach(row => {
    if (!row.mode || !row.distance || !row.employees) return;
    const mode = c.transportModes[row.mode];
    if (!mode) return;
    total += parseInt(row.employees, 10) * parseFloat(row.distance) * 2 * state.workingDays * mode.factor;
  });
  state.results[7] = total;
  persistScope3ResultsToSession();
  renderSidebar();
  saveCategoryToDatabase(7, total, c.name).then(function(saved) {
    saveSavePrefs();
    var statusEl = document.getElementById('scope3SaveCategoryStatus');
    var tonnes = (total / 1000).toFixed(2);
    if (saved && statusEl) statusEl.innerHTML = '<span class="text-success">Saved! Category 7: ' + formatNum(tonnes) + ' t CO₂e</span>';
    var msg = 'Category 7: ' + formatNum(total.toFixed(0)) + ' kg CO₂e (' + formatNum(tonnes) + ' t)';
    if (saved) alert('Saved to database.\n' + msg);
    else alert('Calculated.\n' + msg + '\n\nSet date and facility in the green box above to save to the database.');
  });
}

function showSummary() {
  state.activeCategory = 'summary';
  renderSidebar();
  closeSidebar();
  const totalAll = categories.reduce((sum, c) => sum + state.results[c.id], 0);
  if (totalAll > 0) persistScope3ResultsToSession();
  const tonnes = totalAll / 1000;
  const sorted = [...categories].sort((a,b) => state.results[b.id] - state.results[a.id]);
  const trees = (totalAll / 21.77).toFixed(0);
  const flights = (totalAll / 986).toFixed(1);
  const homes = (totalAll / 4600).toFixed(1);
  const cars = (totalAll / 4600).toFixed(1);
  let barHTML = categories.filter(c => state.results[c.id] > 0).map(c => {
    const pct = totalAll > 0 ? (state.results[c.id] / totalAll * 100) : 0;
    return '<div class="scope3-summary-bar-seg" style="width:' + Math.max(pct,1) + '%;background:' + c.color + ';"></div>';
  }).join('');
  let tableHTML = sorted.map(c => {
    const val = state.results[c.id];
    const pct = totalAll > 0 ? (val / totalAll * 100).toFixed(1) : '0.0';
    return '<tr><td><span class="cat-dot" style="background:' + c.color + '"></span>' + c.id + '. ' + c.name + '</td><td class="text-end fw-bold">' + formatNum(val.toFixed(0)) + ' kg</td><td class="text-end text-muted">' + (val/1000).toFixed(2) + ' t</td><td class="text-end">' + pct + '%</td></tr>';
  }).join('');
  const main = document.getElementById('mainContent');
  if (!main) return;
  main.innerHTML = '<div class="category-section"><div class="mb-4"><span class="badge bg-warning text-dark">Summary Report</span><h4 class="mt-2 mb-1">Scope 3 Emission Summary</h4><p class="text-muted mb-0">Each category is saved to the database when you click <strong>Save &amp; Calculate</strong> on that category. This summary is for viewing your totals only — no save at the end.</p></div><div class="scope3-summary-total"><div class="small text-uppercase fw-bold text-muted mb-1">Total Scope 3 Emissions</div><div class="value">' + formatNum(tonnes.toFixed(2)) + '</div><div class="unit">tonnes CO₂e / year' + (totalAll > 0 ? ' (' + formatNum(totalAll.toFixed(0)) + ' kg)' : '') + '</div></div>' + (totalAll > 0 ? '<div class="card shadow-sm border-0 mb-4"><div class="card-body"><h5 class="mb-3">Emission Distribution</h5><div class="scope3-summary-bar">' + barHTML + '</div><table class="table table-hover scope3-summary-table mb-0"><thead><tr><th>Category</th><th class="text-end">Emissions</th><th class="text-end">Tonnes</th><th class="text-end">Share</th></tr></thead><tbody>' + tableHTML + '<tr class="table-success"><td class="fw-bold">Total</td><td class="text-end fw-bold">' + formatNum(totalAll.toFixed(0)) + ' kg</td><td class="text-end fw-bold">' + formatNum(tonnes.toFixed(2)) + ' t</td><td class="text-end">100%</td></tr></tbody></table></div></div><div class="card shadow-sm border-0 mb-4"><div class="card-body"><h5 class="mb-3">Equivalents</h5><div class="row g-3"><div class="col-6 col-md-3"><div class="scope3-equiv-item border rounded p-3"><div class="fs-4 mb-1">Trees</div><div class="fw-bold fs-5">' + formatNum(trees) + '</div><div class="small text-muted">Trees to offset (1 yr)</div></div></div><div class="col-6 col-md-3"><div class="scope3-equiv-item border rounded p-3"><div class="fs-4 mb-1">Flights</div><div class="fw-bold fs-5">' + flights + '</div><div class="small text-muted">NY↔London flights</div></div></div><div class="col-6 col-md-3"><div class="scope3-equiv-item border rounded p-3"><div class="fs-4 mb-1">Homes</div><div class="fw-bold fs-5">' + homes + '</div><div class="small text-muted">US homes (1 yr)</div></div></div><div class="col-6 col-md-3"><div class="scope3-equiv-item border rounded p-3"><div class="fs-4 mb-1">Cars</div><div class="fw-bold fs-5">' + cars + '</div><div class="small text-muted">Cars driven (1 yr)</div></div></div></div></div></div>' : '<div class="card shadow-sm border-0"><div class="card-body text-center py-5"><div class="text-muted mb-3" style="font-size:3rem;opacity:0.5;">No data yet</div><p class="text-muted mb-0">Select a category, enter data, and click <strong>Save &amp; Calculate</strong> to save that category to the database.</p></div></div>') + '</div>';
}

function formatNum(n) { return Number(n).toLocaleString('en-US'); }

function persistScope3ResultsToSession() {
  try {
    const total = categories.reduce((sum, c) => sum + state.results[c.id], 0);
    if (total > 0) {
      sessionStorage.setItem('scope3CalculatorResults', JSON.stringify({ results: state.results }));
    } else {
      sessionStorage.removeItem('scope3CalculatorResults');
    }
  } catch (e) {}
}

function getSaveToRecordsCardHTML() {
  const facilities = window.scope3Facilities || [];
  const sites = window.scope3Sites || [];
  const today = new Date().toISOString().slice(0, 10);
  let facilityOpts = '<option value="">Choose a facility...</option>';
  facilities.forEach(f => { facilityOpts += '<option value="' + (f.name || '').replace(/"/g, '&quot;') + '">' + (f.name || '').replace(/</g, '&lt;') + '</option>'; });
  let siteOpts = '<option value="">Optional: select site...</option>';
  sites.forEach(s => { siteOpts += '<option value="' + (s.id || '') + '">' + (s.name || '').replace(/</g, '&lt;') + '</option>'; });
  return '<div class="card shadow-sm border-0"><div class="card-header bg-white py-3"><h5 class="mb-0"><i class="fas fa-database me-2"></i>Save to Emission Records</h5><p class="text-muted small mb-0 mt-1">Persist this summary as Scope 3 entries in Emission Records. One record per category with emissions.</p></div><div class="card-body"><form id="scope3SaveToRecordsForm" class="row g-3"><div class="col-md-4"><label class="form-label">Entry date</label><input type="date" class="form-control" id="scope3EntryDate" value="' + today + '" required></div><div class="col-md-4"><label class="form-label">Facility <span class="text-danger">*</span></label><select class="form-select" id="scope3FacilitySelect" required>' + facilityOpts + '</select></div><div class="col-md-4"><label class="form-label">Site</label><select class="form-select" id="scope3SiteSelect">' + siteOpts + '</select></div><div class="col-12"><button type="button" class="btn btn-success" onclick="scope3SaveToEmissionRecords()"><i class="fas fa-save me-2"></i>Save to Emission Records</button><span id="scope3SaveStatus" class="ms-3"></span></div></form></div></div>';
}

function saveToEmissionRecords() {
  const storeUrl = window.scope3StoreUrl;
  const categoryMap = window.scope3CategoryMap || {};
  const token = window.scope3CsrfToken;
  const entryDateEl = document.getElementById('scope3EntryDate');
  const facilityEl = document.getElementById('scope3FacilitySelect');
  const siteEl = document.getElementById('scope3SiteSelect');
  const statusEl = document.getElementById('scope3SaveStatus');
  if (!storeUrl || !token) { if (statusEl) statusEl.innerHTML = '<span class="text-danger">Missing configuration.</span>'; return; }
  if (!entryDateEl || !facilityEl || !facilityEl.value) { if (statusEl) statusEl.innerHTML = '<span class="text-danger">Please select a facility and date.</span>'; return; }
  const entries = [];
  categories.forEach(c => {
    const val = state.results[c.id];
    if (!(val > 0)) return;
    const map = categoryMap[c.id];
    if (!map) return;
    entries.push({
      entryDate: entryDateEl.value,
      facilitySelect: facilityEl.value,
      siteSelect: siteEl.value || null,
      scopeSelect: '3',
      emissionSourceSelect: map.emission_source_name,
      co2eValue: val / 1000,
      confidenceLevel: 'medium',
      dataSource: 'manual',
      scope3_category_id: map.scope3_category_id,
      entryNotes: 'Scope 3 Calculator - Category ' + c.id + ': ' + c.name,
    });
  });
  if (entries.length === 0) { if (statusEl) statusEl.innerHTML = '<span class="text-warning">No emissions to save.</span>'; return; }
  if (statusEl) statusEl.innerHTML = '<span class="text-muted">Saving...</span>';
  fetch(storeUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ _token: token, entries: entries, status: 'active' }) })
    .then(r => r.json().then(data => ({ ok: r.ok, status: r.status, data })).catch(() => ({ ok: r.ok, status: r.status, data: null })))
    .then(({ ok, data }) => {
      if (ok) {
        try { sessionStorage.removeItem('scope3CalculatorResults'); } catch (e) {}
        if (statusEl) statusEl.innerHTML = '<span class="text-success">Saved ' + entries.length + ' record(s) successfully.</span>';
      } else {
        if (statusEl) statusEl.innerHTML = '<span class="text-danger">' + (data && data.message ? data.message : (data && data.errors ? Object.values(data.errors).flat().join(' ') : 'Save failed.')) + '</span>';
      }
    })
    .catch(() => { if (statusEl) statusEl.innerHTML = '<span class="text-danger">Network error.</span>'; });
}

window.getScope3SaveToRecordsCardHTML = getSaveToRecordsCardHTML;
window.scope3SaveToEmissionRecords = saveToEmissionRecords;
window.scope3GoToCategory = goToCategory;
window.scope3AddGenericRow = addGenericRow;
window.scope3RemoveGenericRow = removeGenericRow;
window.scope3SaveCategory = saveCategory;
window.scope3AddCommuteRow = addCommuteRow;
window.scope3SaveCommuting = saveCommuting;
window.scope3ShowSummary = showSummary;
window.scope3HandleFieldChange = handleFieldChange;

loadSavePrefs();
var facilityEl = document.getElementById('scope3SaveFacility');
var dateEl = document.getElementById('scope3SaveEntryDate');
var siteEl = document.getElementById('scope3SaveSite');
if (dateEl) dateEl.addEventListener('change', saveSavePrefs);
if (facilityEl) facilityEl.addEventListener('change', saveSavePrefs);
if (siteEl) siteEl.addEventListener('change', saveSavePrefs);

// Mark EF as user-edited so auto-fill won't override it
document.addEventListener('input', function(e) {
  var t = e.target;
  if (!t) return;
  if (t.matches && t.matches('input[data-key="ef"]')) {
    t.dataset.userEdited = '1';
    // keep state in sync immediately
    var catId = parseInt(t.dataset.cat, 10);
    var idx = parseInt(t.dataset.idx, 10);
    if (state.data[catId] && state.data[catId][idx]) state.data[catId][idx].ef = t.value;
  }
});

renderSidebar();
goToCategory(1);
})();

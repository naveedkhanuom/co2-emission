<script>
(function(){
var categories = typeof window.scope3Categories !== 'undefined' ? window.scope3Categories : [];
var entryForms = typeof window.scope3EntryForms !== 'undefined' ? window.scope3EntryForms : {};
var selCat = null, upFiles = [], filterCat = 'all', curStep = 1, curSub = 'upstream';
var scope3EffectiveCo2 = null, scope3ActivityPayload = null;
var cats = { all: 'All', upstream: 'Upstream', downstream: 'Downstream' };

var SCOPE3_EF_TRAVEL = { 'Flight — Short haul (<1500 km)': 0.255, 'Flight — Medium haul': 0.195, 'Flight — Long haul (>3700 km)': 0.15, 'Rail': 0.041, 'Car rental / Taxi': 0.192, 'Hotel nights': 15 };
var SCOPE3_EF_FREIGHT = { 'Road (truck)': 0.107, 'Rail': 0.03, 'Sea (container)': 0.01, 'Air freight': 0.6, 'Last-mile delivery': 0.18, 'Pipeline': 0.02 };
var SCOPE3_EF_ENERGY = { 'Electricity (T&D losses)': 0.019, 'Natural Gas (WTT)': 0.05, 'Diesel (WTT)': 0.07, 'Gasoline (WTT)': 0.06 };
var SCOPE3_EF_COMMUTE = { 'Car — Gasoline': 0.192, 'Car — Diesel': 0.171, 'Car — Hybrid': 0.112, 'Car — Electric': 0.053, 'Bus': 0.089, 'Train / Rail': 0.041, 'Metro': 0.033, 'Motorcycle': 0.113, 'Bicycle': 0, 'Walking': 0 };

function loadStats() {
  if (!window.scope3StatsUrl) return;
  fetch(window.scope3StatsUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
    .then(function(r) { return r.json(); })
    .then(function(d) {
      var el;
      if (el = document.getElementById('scope3StatTotal')) el.textContent = d.total || 0;
      if (el = document.getElementById('scope3StatUpstream')) el.textContent = d.upstream || 0;
      if (el = document.getElementById('scope3StatDownstream')) el.textContent = d.downstream || 0;
      var tabs = document.getElementById('scope3FTabs');
      if (tabs && tabs.querySelectorAll) {
        tabs.querySelectorAll('.ftab').forEach(function(tab) {
          var k = tab.getAttribute('data-f');
          var c = k === 'all' ? (d.total || 0) : (k === 'upstream' ? (d.upstream || 0) : (d.downstream || 0));
          var cn = tab.querySelector('.cn');
          if (cn) cn.textContent = c;
        });
      }
    })
    .catch(function() {});
}

function initScope3() {
  var wrap = document.getElementById('scope3FTabs');
  if (wrap) {
    Object.keys(cats).forEach(function(k) {
      var t = document.createElement('div');
      t.className = 'ftab' + (k === 'all' ? ' on' : '');
      t.setAttribute('data-f', k);
      t.innerHTML = cats[k] + ' <span class="cn">0</span>';
      t.addEventListener('click', function() {
        wrap.querySelectorAll('.ftab').forEach(function(x) { x.classList.remove('on'); });
        t.classList.add('on');
        filterCat = k;
        if (window.scope3Table && typeof $ !== 'undefined' && $.fn.DataTable && $.fn.DataTable.isDataTable('#scope3Table')) {
          window.scope3Table.ajax.reload();
        }
      });
      wrap.appendChild(t);
    });
  }

  function openM() {
    selCat = null;
    upFiles = [];
    resetForm();
    curSub = 'upstream';
    renderSubBtns();
    renderCatCards();
    var suc = document.getElementById('scope3Suc');
    if (suc) { suc.classList.remove('on'); suc.style.display = 'none'; }
    var stp = document.querySelector('#scope3Ov .stp');
    if (stp) stp.style.display = '';
    for (var i = 1; i <= 3; i++) {
      var p = document.getElementById('scope3P' + i);
      if (p) { p.classList.remove('show'); p.style.display = (i === 1) ? 'block' : 'none'; }
    }
    goStep(1);
    var ov = document.getElementById('scope3Ov');
    if (ov) ov.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeM() {
    var ov = document.getElementById('scope3Ov');
    if (ov) ov.classList.remove('open');
    document.body.style.overflow = '';
  }

  function renderSubBtns() {
    var c = document.getElementById('scope3Scb');
    if (!c) return;
    c.innerHTML = '';
    ['upstream', 'downstream'].forEach(function(k) {
      var b = document.createElement('div');
      b.className = 'scbt' + (curSub === k ? ' on' : '');
      b.textContent = cats[k];
      b.addEventListener('click', function() {
        curSub = k;
        c.querySelectorAll('.scbt').forEach(function(x) { x.classList.remove('on'); });
        b.classList.add('on');
        renderCatCards();
      });
      c.appendChild(b);
    });
  }

  function renderCatCards() {
    selCat = null;
    var g = document.getElementById('scope3CatG');
    if (!g) return;
    g.innerHTML = '';
    var list = categories.filter(function(c) { return c.category_type === curSub; });
    list.forEach(function(c) {
      var d = document.createElement('div');
      d.className = 'sc2';
      d.innerHTML = '<div class="sd2"></div><div><div class="sn3">' + (c.emission_source_name || c.name || '') + '</div><div class="sd3">' + (c.description || '').substring(0, 80) + (c.description && c.description.length > 80 ? '…' : '') + '</div></div>';
      d.addEventListener('click', function() {
        g.querySelectorAll('.sc2').forEach(function(x) { x.classList.remove('pk'); });
        d.classList.add('pk');
        selCat = c;
        var fg = document.getElementById('scope3FgCat');
        if (fg) fg.classList.remove('ferr');
      });
      g.appendChild(d);
    });
  }

  function renderScope3Step2() {
    var so = selCat && (selCat.sort_order != null) ? Number(selCat.sort_order) : (selCat && selCat.id ? Number(selCat.id) : 0);
    var cfg = entryForms[so];
    var gDynamic = document.getElementById('scope3DynamicFields');
    var gLabel = document.getElementById('scope3DynamicLabel');
    var gInfo = document.getElementById('scope3FormInfo');
    var gErr = document.getElementById('scope3DynamicErr');
    var co2Result = document.getElementById('scope3Co2Result');
    var co2Manual = document.getElementById('scope3FgCo2Manual');
    var co2Override = document.getElementById('scope3Fco2Override');
    if (co2Override) co2Override.value = '';
    if (gErr) gErr.style.display = 'none';
    document.getElementById('scope3FgDynamic').classList.remove('ferr');

    if (cfg && cfg.fields && cfg.fields.length) {
      if (gLabel) gLabel.textContent = 'Category data';
      if (gInfo) { gInfo.textContent = cfg.info || ''; gInfo.style.display = cfg.info ? 'block' : 'none'; }
      if (co2Result) co2Result.style.display = 'block';
      if (co2Manual) co2Manual.style.display = 'none';
      document.getElementById('scope3Fco2').value = '';

      gDynamic.innerHTML = '';
      cfg.fields.forEach(function(f) {
        var wrap = document.createElement('div');
        wrap.className = 'fg-inline';
        var label = document.createElement('label');
        label.textContent = f.label + (f.key === 'ef' || f.key === 'spend' || f.key === 'quantity' || f.key === 'weight' || f.key === 'distance' || f.key === 'ownership' || f.key === 'investee_emissions' ? '' : '');
        var val = (f.defaultValue !== undefined && f.defaultValue !== '') ? f.defaultValue : '';
        if (f.type === 'select') {
          var sel = document.createElement('select');
          sel.className = 'fsl scope3-dyn-input';
          sel.setAttribute('data-key', f.key);
          sel.innerHTML = '<option value="">Select...</option>' + (f.options || []).map(function(o) { return '<option value="' + o + '">' + o + '</option>'; }).join('');
          if (val && f.options && f.options.indexOf(val) >= 0) sel.value = val;
          sel.addEventListener('change', function() { updateScope3EfFromSelect(so, f.key, this.value); updateScope3Calculated(); });
          wrap.appendChild(label);
          wrap.appendChild(sel);
        } else {
          var inp = document.createElement('input');
          inp.className = 'fi scope3-dyn-input';
          inp.type = f.type || 'text';
          inp.setAttribute('data-key', f.key);
          inp.placeholder = f.placeholder || '';
          if (f.step) inp.setAttribute('step', f.step);
          inp.value = val;
          inp.addEventListener('input', function() { updateScope3Calculated(); });
          inp.addEventListener('change', function() { updateScope3Calculated(); });
          wrap.appendChild(label);
          wrap.appendChild(inp);
        }
        gDynamic.appendChild(wrap);
      });
      updateScope3Calculated();
    } else {
      if (gLabel) gLabel.textContent = 'Category data';
      if (gInfo) gInfo.style.display = 'none';
      if (co2Result) co2Result.style.display = 'none';
      if (co2Manual) co2Manual.style.display = 'block';
      gDynamic.innerHTML = '';
      document.getElementById('scope3Fco2').value = '';
    }
  }

  function updateScope3EfFromSelect(sortOrder, key, value) {
    var cfg = entryForms[sortOrder];
    if (!cfg) return;
    var efVal = null;
    if (key === 'travel_type' && SCOPE3_EF_TRAVEL[value] != null) efVal = SCOPE3_EF_TRAVEL[value];
    if (key === 'mode' && (sortOrder === 4 || sortOrder === 9) && SCOPE3_EF_FREIGHT[value] != null) efVal = SCOPE3_EF_FREIGHT[value];
    if (key === 'source' && sortOrder === 3 && SCOPE3_EF_ENERGY[value] != null) efVal = SCOPE3_EF_ENERGY[value];
    if (key === 'mode' && sortOrder === 7 && SCOPE3_EF_COMMUTE[value] != null) efVal = SCOPE3_EF_COMMUTE[value];
    if (efVal != null) {
      var inp = document.querySelector('#scope3DynamicFields input[data-key="ef"]');
      if (inp) inp.value = efVal;
    }
  }

  function getScope3DynamicValues() {
    var out = {};
    document.querySelectorAll('#scope3DynamicFields .scope3-dyn-input').forEach(function(el) {
      var k = el.getAttribute('data-key');
      if (k) out[k] = el.value;
    });
    return out;
  }

  function calcScope3Co2e(sortOrder, cfg, values) {
    if (!cfg || !cfg.method) return null;
    var m = cfg.method;
    var v = values || getScope3DynamicValues();
    function n(k) { return parseFloat(v[k]) || 0; }
    function s(k) { return (v[k] || '').toString().trim(); }
    var kg = 0;
    if (m === 'spend') kg = n('spend') * n('ef');
    else if (m === 'activity') kg = n('quantity') * n('ef');
    else if (m === 'distance') kg = n('weight') * n('distance') * n('ef');
    else if (m === 'waste') kg = n('weight') * n('ef');
    else if (m === 'travel') kg = n('quantity') * n('ef');
    else if (m === 'commute') kg = n('distance_km') * n('employees') * n('days_per_year') * 2 * n('ef');
    else if (m === 'use') kg = n('units_sold') * n('energy_per_use') * n('lifetime') * n('ef');
    else if (m === 'investment') kg = (n('ownership') / 100) * n('investee_emissions') * 1000;
    else return null;
    return isNaN(kg) ? null : kg;
  }

  function updateScope3Calculated() {
    var so = selCat && (selCat.sort_order != null) ? Number(selCat.sort_order) : (selCat && selCat.id ? Number(selCat.id) : 0);
    var cfg = entryForms[so];
    var resEl = document.getElementById('scope3Co2ResultVal');
    if (!resEl || !cfg) return;
    var kg = calcScope3Co2e(so, cfg, getScope3DynamicValues());
    if (kg != null && !isNaN(kg)) {
      resEl.textContent = (kg / 1000).toFixed(4) + ' tCO2e';
    } else {
      resEl.textContent = '—';
    }
  }

  function goStep(n) {
    curStep = n;
    var suc = document.getElementById('scope3Suc');
    if (suc) { suc.classList.remove('on'); suc.style.display = 'none'; }
    for (var i = 1; i <= 3; i++) {
      var p = document.getElementById('scope3P' + i);
      var m = document.getElementById('scope3Ms' + i);
      if (p) {
        p.classList.toggle('show', i === n);
        p.style.display = (i === n) ? 'block' : 'none';
      }
      if (m) m.className = 'st' + (i < n ? ' dn' : '') + (i === n ? ' ac2' : '');
    }
  }

  function resetForm() {
    ['scope3Fco2', 'scope3Fco2Override', 'scope3Fdt', 'scope3Ffac', 'scope3Fdsc'].forEach(function(id) {
      var el = document.getElementById(id);
      if (el) el.value = '';
    });
    document.getElementById('scope3Fper').value = '';
    document.getElementById('scope3Fmethod').value = 'activity-based';
    document.getElementById('scope3Fquality').value = 'estimated';
    var ffl = document.getElementById('scope3Ffl');
    if (ffl) ffl.innerHTML = '';
    upFiles = [];
    scope3EffectiveCo2 = null;
    scope3ActivityPayload = null;
    document.querySelectorAll('.scope3-app .ferr').forEach(function(e) { e.classList.remove('ferr'); });
  }

  var btnAdd = document.getElementById('scope3BtnAdd');
  if (btnAdd) btnAdd.addEventListener('click', openM);
  var mx = document.getElementById('scope3Mx');
  if (mx) mx.addEventListener('click', closeM);
  var n1c = document.getElementById('scope3N1c');
  if (n1c) n1c.addEventListener('click', closeM);

  document.getElementById('scope3N1n').addEventListener('click', function() {
    if (!selCat) {
      document.getElementById('scope3FgCat').classList.add('ferr');
      return;
    }
    goStep(2);
    renderScope3Step2();
  });

  document.getElementById('scope3N2b').addEventListener('click', function() { goStep(1); });

  document.getElementById('scope3N2n').addEventListener('click', function() {
    document.querySelectorAll('.scope3-app .ferr').forEach(function(e) { e.classList.remove('ferr'); });
    var so = selCat && (selCat.sort_order != null) ? Number(selCat.sort_order) : (selCat && selCat.id ? Number(selCat.id) : 0);
    var cfg = entryForms[so];
    var useDynamic = cfg && cfg.fields && cfg.fields.length;
    var ok = true;
    var co2 = 0;
    var actPayload = null;

    if (useDynamic) {
      var vals = getScope3DynamicValues();
      var kg = calcScope3Co2e(so, cfg, vals);
      var overrideEl = document.getElementById('scope3Fco2Override');
      var overrideVal = overrideEl && overrideEl.value.trim() !== '' ? parseFloat(overrideEl.value) : NaN;
      if (overrideVal >= 0 && !isNaN(overrideVal)) co2 = overrideVal;
      else if (kg != null && !isNaN(kg) && kg >= 0) co2 = kg / 1000;
      else {
        document.getElementById('scope3FgDynamic').classList.add('ferr');
        var errEl = document.getElementById('scope3DynamicErr');
        if (errEl) { errEl.style.display = 'block'; errEl.textContent = 'Fill in the required fields above to get a calculated value, or use Override.'; }
        ok = false;
      }
      actPayload = vals;
    } else {
      var co2Val = parseFloat(document.getElementById('scope3Fco2').value);
      if (document.getElementById('scope3Fco2').value === '' || isNaN(co2Val) || co2Val < 0) {
        document.getElementById('scope3FgCo2Manual').classList.add('ferr');
        ok = false;
      } else co2 = co2Val;
    }

    if (!document.getElementById('scope3Fper').value) {
      document.getElementById('scope3FgPer').classList.add('ferr');
      ok = false;
    }
    if (!document.getElementById('scope3Fdt').value) {
      document.getElementById('scope3FgDt').classList.add('ferr');
      ok = false;
    }
    if (!document.getElementById('scope3Ffac').value.trim()) {
      document.getElementById('scope3FgFac').classList.add('ferr');
      ok = false;
    }
    if (!ok) return;

    scope3EffectiveCo2 = co2;
    scope3ActivityPayload = actPayload;
    var per = document.getElementById('scope3Fper').value;
    var dt = document.getElementById('scope3Fdt').value;
    var fac = document.getElementById('scope3Ffac').value;
    var method = document.getElementById('scope3Fmethod').value;
    var quality = document.getElementById('scope3Fquality').value;
    var actLine = actPayload && typeof actPayload === 'object' ? JSON.stringify(actPayload) : '';
    document.getElementById('scope3Rvw').innerHTML = '<strong>Category:</strong> ' + (selCat.emission_source_name || selCat.name) + '<br><strong>Emissions:</strong> <span style="color:var(--primary-green);font-weight:700">' + co2.toFixed(4) + ' tCO2e</span><br><strong>Method:</strong> ' + method + ' | <strong>Data quality:</strong> ' + quality + (actLine ? '<br><strong>Activity data:</strong> ' + actLine : '') + '<br><strong>Period:</strong> ' + per + ' | ' + dt + '<br><strong>Facility:</strong> ' + fac;
    goStep(3);
  });

  document.getElementById('scope3N3b').addEventListener('click', function() { goStep(2); });

  document.getElementById('scope3N3s').addEventListener('click', function() {
    var co2 = scope3EffectiveCo2 != null ? scope3EffectiveCo2 : (parseFloat(document.getElementById('scope3Fco2').value) || 0);
    var formData = new FormData();
    formData.append('_token', window.scope3Csrf);
    formData.append('entryDate', document.getElementById('scope3Fdt').value);
    formData.append('facilitySelect', document.getElementById('scope3Ffac').value.trim());
    formData.append('scopeSelect', '3');
    formData.append('scope3_category_id', selCat.id);
    formData.append('emissionSourceSelect', selCat.emission_source_name || selCat.name);
    formData.append('co2eValue', co2.toFixed(6));
    var actPayload = scope3ActivityPayload;
    var actNum = '';
    if (actPayload && typeof actPayload === 'object') {
      var singleNum = null;
      ['spend', 'quantity', 'weight', 'co2e', 'investee_emissions'].forEach(function(k) { if (actPayload[k] !== undefined && actPayload[k] !== '') { var x = parseFloat(actPayload[k]); if (!isNaN(x)) singleNum = x; } });
      if (singleNum != null) actNum = singleNum;
    }
    formData.append('activityData', actNum !== '' ? actNum : '');
    formData.append('calculation_method', document.getElementById('scope3Fmethod').value);
    formData.append('data_quality', document.getElementById('scope3Fquality').value);
    formData.append('confidenceLevel', 'medium');
    formData.append('dataSource', 'manual');
    var notes = document.getElementById('scope3Fdsc').value.trim();
    var per = document.getElementById('scope3Fper').value;
    if (per) notes = (notes ? notes + '\n' : '') + 'Period: ' + per;
    if (actPayload && typeof actPayload === 'object') notes = (notes ? notes + '\n' : '') + 'Activity: ' + JSON.stringify(actPayload);
    formData.append('entryNotes', notes);
    for (var i = 0; i < upFiles.length; i++) {
      formData.append('supporting_documents[]', upFiles[i]);
    }

    var btn = document.getElementById('scope3N3s');
    btn.disabled = true;
    btn.textContent = 'Saving...';

    fetch(window.scope3StoreUrl, {
      method: 'POST',
      body: formData,
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); })
    .then(function(res) {
      btn.disabled = false;
      btn.textContent = '✓ Submit';
      if (res.ok && res.data && res.data.status) {
        document.getElementById('scope3SucM').textContent = (selCat.emission_source_name || selCat.name) + ' | ' + co2.toFixed(4) + ' tCO2e';
        for (var i = 1; i <= 3; i++) {
          var p = document.getElementById('scope3P' + i);
          if (p) { p.classList.remove('show'); p.style.display = 'none'; }
        }
        var stp = document.querySelector('#scope3Ov .stp');
        if (stp) stp.style.display = 'none';
        var suc = document.getElementById('scope3Suc');
        if (suc) { suc.classList.add('on'); suc.style.display = 'block'; }
        if (window.scope3Table && typeof $ !== 'undefined' && $.fn.DataTable && $.fn.DataTable.isDataTable('#scope3Table')) window.scope3Table.ajax.reload();
        loadStats();
      } else {
        var msg = (res.data && res.data.message) || (res.data && res.data.errors && JSON.stringify(res.data.errors)) || 'Save failed';
        alert(msg);
      }
    })
    .catch(function() {
      btn.disabled = false;
      btn.textContent = '✓ Submit';
      alert('Network or server error. Please try again.');
    });
  });

  document.getElementById('scope3SucC').addEventListener('click', closeM);
  document.getElementById('scope3SucA').addEventListener('click', function() {
    var suc = document.getElementById('scope3Suc');
    if (suc) { suc.classList.remove('on'); suc.style.display = 'none'; }
    resetForm();
    curSub = 'upstream';
    renderSubBtns();
    renderCatCards();
    goStep(1);
  });

  var fup = document.getElementById('scope3Fup'), fupi = document.getElementById('scope3Fupi');
  if (fup && fupi) {
    fup.addEventListener('click', function() { fupi.click(); });
    fupi.addEventListener('change', function() {
      for (var i = 0; i < this.files.length; i++) upFiles.push(this.files[i]);
      renderFiles();
      this.value = '';
    });
  }

  function renderFiles() {
    var c = document.getElementById('scope3Ffl');
    if (!c) return;
    c.innerHTML = '';
    upFiles.forEach(function(f, i) {
      var d = document.createElement('div');
      d.className = 'ffi';
      d.innerHTML = f.name + ' <span class="ffx">&times;</span>';
      d.querySelector('.ffx').addEventListener('click', function() {
        upFiles.splice(i, 1);
        renderFiles();
      });
      c.appendChild(d);
    });
  }

  if (typeof $ !== 'undefined' && $('#scope3Table').length) {
    window.scope3Table = $('#scope3Table').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: window.scope3DataUrl,
        data: function(d) {
          d.subcat = filterCat === 'all' ? '' : filterCat;
        }
      },
      columns: [
        { data: 'emission_source', name: 'emission_source' },
        { data: 'activity_data', name: 'activity_data', render: function(v) { return v != null && v !== '' ? v : '-'; } },
        { data: 'co2e_value', name: 'co2e_value' },
        { data: 'facility', name: 'facility' },
        { data: 'entry_date', name: 'entry_date' },
        { data: 'actions', name: 'actions', orderable: false, searchable: false, createdCell: function(td, cellData) { $(td).html(cellData || ''); } }
      ],
      order: [[4, 'desc']],
      pageLength: 10,
      responsive: true
    });
  }
  loadStats();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initScope3);
} else {
  initScope3();
}
})();
</script>

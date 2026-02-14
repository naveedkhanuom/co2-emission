<script>
(function(){
var sources = typeof window.scope2Sources !== 'undefined' ? window.scope2Sources : {};
var gridEF = Array.isArray(window.scope2GridEf) ? window.scope2GridEf : [];
var S = {
  electricity: sources.electricity || [],
  heating: sources.heating || [],
  cooling: sources.cooling || []
};
var curSub = 'electricity', selSrc = null, upFiles = [], filterCat = 'all', curStep = 1, selRegionIdx = 0;
var cats = { all: 'All', electricity: 'Electricity', heating: 'Heating / Steam', cooling: 'Cooling' };

function toKwh(qty, label) {
  if (label === 'MWh' || label === 'MWh (thermal)') return qty * 1000;
  if (label === 'GJ') return qty * 277.778;
  if (label === 'MMBtu') return qty * 293.071;
  if (label === 'Ton-hours') return qty * 3.517;
  if (label === 'tonnes steam') return qty * 694.4;
  return qty;
}

function loadStats() {
  if (!window.scope2StatsUrl) return;
  fetch(window.scope2StatsUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
    .then(function(r) { return r.json(); })
    .then(function(d) {
      var el;
      if (el = document.getElementById('scope2StatTotal')) el.textContent = d.total || 0;
      if (el = document.getElementById('scope2StatElectricity')) el.textContent = d.electricity || 0;
      if (el = document.getElementById('scope2StatHeating')) el.textContent = d.heating || 0;
      if (el = document.getElementById('scope2StatCooling')) el.textContent = d.cooling || 0;
      var tabs = document.getElementById('scope2FTabs');
      if (tabs && tabs.querySelectorAll) {
        tabs.querySelectorAll('.ftab').forEach(function(tab) {
          var k = tab.getAttribute('data-f');
          var c = k === 'all' ? (d.total || 0) : (k === 'electricity' ? (d.electricity || 0) : (k === 'heating' ? (d.heating || 0) : (d.cooling || 0)));
          var cn = tab.querySelector('.cn');
          if (cn) cn.textContent = c;
        });
      }
    })
    .catch(function() {});
}

function initScope2() {
  var wrap = document.getElementById('scope2FTabs');
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
        if (window.scope2Table && typeof $ !== 'undefined' && $.fn.DataTable && $.fn.DataTable.isDataTable('#scope2Table')) {
          window.scope2Table.ajax.reload();
        }
      });
      wrap.appendChild(t);
    });
  }

  function openM() {
    selSrc = null;
    upFiles = [];
    resetForm();
    curSub = 'electricity';
    renderSubBtns();
    renderSrcCards();
    var suc = document.getElementById('scope2Suc');
    if (suc) { suc.classList.remove('on'); suc.style.display = 'none'; }
    var stp = document.querySelector('#scope2Ov .stp');
    if (stp) stp.style.display = '';
    for (var i = 1; i <= 3; i++) {
      var p = document.getElementById('scope2P' + i);
      if (p) { p.classList.remove('show'); p.style.display = (i === 1) ? 'block' : 'none'; }
    }
    goStep(1);
    var ov = document.getElementById('scope2Ov');
    if (ov) { ov.classList.add('open'); }
    document.body.style.overflow = 'hidden';
  }

  function closeM() {
    var ov = document.getElementById('scope2Ov');
    if (ov) ov.classList.remove('open');
    document.body.style.overflow = '';
  }

  function renderSubBtns() {
    var c = document.getElementById('scope2Scb');
    if (!c) return;
    c.innerHTML = '';
    ['electricity', 'heating', 'cooling'].forEach(function(k) {
      var b = document.createElement('div');
      b.className = 'scbt' + (curSub === k ? ' on' : '');
      b.textContent = cats[k];
      b.addEventListener('click', function() {
        curSub = k;
        c.querySelectorAll('.scbt').forEach(function(x) { x.classList.remove('on'); });
        b.classList.add('on');
        renderSrcCards();
      });
      c.appendChild(b);
    });
  }

  function renderSrcCards() {
    selSrc = null;
    var g = document.getElementById('scope2SrcG');
    if (!g) return;
    g.innerHTML = '';
    (S[curSub] || []).forEach(function(s) {
      var d = document.createElement('div');
      d.className = 'sc2';
      d.innerHTML = '<div class="sd2"></div><div><div class="sn3">' + (s.name || '') + '</div><div class="sd3">' + (s.desc || '') + '</div></div>';
      d.addEventListener('click', function() {
        g.querySelectorAll('.sc2').forEach(function(x) { x.classList.remove('pk'); });
        d.classList.add('pk');
        selSrc = s;
        var fg = document.getElementById('scope2FgSrc');
        if (fg) fg.classList.remove('ferr');
      });
      g.appendChild(d);
    });
  }

  function populateUnits() {
    var sel = document.getElementById('scope2Funit');
    if (!sel) return;
    sel.innerHTML = '<option value="">Select...</option>';
    if (!selSrc || !selSrc.units) return;
    selSrc.units.forEach(function(u, i) {
      var o = document.createElement('option');
      o.value = i;
      o.textContent = u.label || u.u;
      sel.appendChild(o);
    });
  }

  function showEF() {
    var rb = document.getElementById('scope2RegionBox');
    var eb = document.getElementById('scope2EfBox');
    var ov = document.getElementById('scope2EfOverride');
    var gcef = document.getElementById('scope2FgCef');
    var chk = document.getElementById('scope2ChkEfOvr');
    var fefOvr = document.getElementById('scope2FefOvr');
    if (rb) rb.style.display = 'none';
    if (eb) eb.style.display = 'none';
    if (ov) ov.style.display = 'none';
    if (gcef) gcef.style.display = 'none';
    if (chk) chk.checked = false;
    if (fefOvr) { fefOvr.style.display = 'none'; fefOvr.value = ''; }
    if (!selSrc) return;

    if (selSrc.isGrid && gridEF.length) {
      if (rb) rb.style.display = 'block';
      var sel = document.getElementById('scope2Fregion');
      if (sel) {
        sel.innerHTML = '';
        gridEF.forEach(function(g, i) {
          var o = document.createElement('option');
          o.value = i;
          o.textContent = g.region + ' (' + g.co2 + ' kgCO2/kWh)';
          sel.appendChild(o);
        });
        selRegionIdx = 0;
      }
      if (eb) eb.style.display = 'block';
      updateGridEFDisplay();
    } else if (selSrc.efPerKwh !== undefined) {
      if (eb) eb.style.display = 'block';
      if (ov) ov.style.display = 'block';
      var row = document.getElementById('scope2EfRow');
      if (row) row.innerHTML = '<div class="ef-item"><div class="ef-val">' + (selSrc.efPerKwh || 0) + '</div><div class="ef-lbl">kgCO2e / kWh</div></div><div class="ef-item"><div class="ef-val">IPCC/IEA/DEFRA</div><div class="ef-lbl">Source</div></div>';
    }
  }

  function updateGridEFDisplay() {
    var sel = document.getElementById('scope2Fregion');
    var idx = sel ? (parseInt(sel.value, 10) || 0) : 0;
    selRegionIdx = idx;
    var g = gridEF[idx];
    var gcef = document.getElementById('scope2FgCef');
    var row = document.getElementById('scope2EfRow');
    if (!row) return;
    if (g && g.region && g.region.indexOf('Custom') !== -1) {
      if (gcef) gcef.style.display = 'block';
      var cv = parseFloat(document.getElementById('scope2Fcef').value) || 0;
      row.innerHTML = '<div class="ef-item"><div class="ef-val">' + (cv || '--') + '</div><div class="ef-lbl">kgCO2/kWh</div></div><div class="ef-item"><div class="ef-val">User</div><div class="ef-lbl">Source</div></div>';
    } else {
      if (gcef) gcef.style.display = 'none';
      if (g) row.innerHTML = '<div class="ef-item"><div class="ef-val">' + g.co2 + '</div><div class="ef-lbl">kgCO2/kWh</div></div><div class="ef-item"><div class="ef-val">' + (g.src || '') + '</div><div class="ef-lbl">Source</div></div>';
    }
  }

  function calcCO2e() {
    var box = document.getElementById('scope2Co2box');
    var cf = document.getElementById('scope2Co2f');
    var cv = document.getElementById('scope2Co2v');
    if (!selSrc) { if (box) box.style.display = 'none'; return 0; }
    var qty = parseFloat(document.getElementById('scope2Fqty').value);
    var uIdx = document.getElementById('scope2Funit').value;
    if (!qty || qty <= 0 || uIdx === '') { if (box) box.style.display = 'none'; return 0; }
    uIdx = parseInt(uIdx, 10);
    var uD = selSrc.units[uIdx];
    if (!uD) { if (box) box.style.display = 'none'; return 0; }
    var t = 0;
    var label = uD.label || uD.u;
    var kwhQty = toKwh(qty, label);

    if (selSrc.isGrid && gridEF.length) {
      var gef = gridEF[selRegionIdx] ? gridEF[selRegionIdx].co2 : 0;
      if (gridEF[selRegionIdx] && gridEF[selRegionIdx].region && gridEF[selRegionIdx].region.indexOf('Custom') !== -1) {
        gef = parseFloat(document.getElementById('scope2Fcef').value) || 0;
      }
      t = (kwhQty * gef) / 1000;
      if (cf) cf.textContent = qty + ' ' + label + ' (' + kwhQty.toFixed(1) + ' kWh) x ' + gef + ' kgCO2/kWh = ' + t.toFixed(4) + ' tCO2e';
    } else if (selSrc.efPerKwh !== undefined) {
      var ef = selSrc.efPerKwh;
      var chk = document.getElementById('scope2ChkEfOvr');
      if (chk && chk.checked) ef = parseFloat(document.getElementById('scope2FefOvr').value) || 0;
      t = (kwhQty * ef) / 1000;
      if (cf) cf.textContent = qty + ' ' + label + ' (' + kwhQty.toFixed(1) + ' kWh) x ' + ef + ' kgCO2e/kWh = ' + t.toFixed(4) + ' tCO2e';
    }

    if (cv) cv.textContent = t.toFixed(4);
    if (box) box.style.display = 'block';
    return t;
  }

  function goStep(n) {
    curStep = n;
    var suc = document.getElementById('scope2Suc');
    if (suc) { suc.classList.remove('on'); suc.style.display = 'none'; }
    for (var i = 1; i <= 3; i++) {
      var p = document.getElementById('scope2P' + i);
      var m = document.getElementById('scope2Ms' + i);
      if (p) {
        p.classList.toggle('show', i === n);
        p.style.display = (i === n) ? 'block' : 'none';
      }
      if (m) m.className = 'st' + (i < n ? ' dn' : '') + (i === n ? ' ac2' : '');
    }
  }

  function resetForm() {
    ['scope2Fqty', 'scope2Fdt', 'scope2Ffac', 'scope2Fdsc'].forEach(function(id) {
      var el = document.getElementById(id);
      if (el) el.value = '';
    });
    var funit = document.getElementById('scope2Funit');
    if (funit) funit.innerHTML = '<option value="">Select...</option>';
    var ffl = document.getElementById('scope2Ffl');
    if (ffl) ffl.innerHTML = '';
    var co2box = document.getElementById('scope2Co2box');
    if (co2box) co2box.style.display = 'none';
    var efBox = document.getElementById('scope2EfBox');
    if (efBox) efBox.style.display = 'none';
    var rb = document.getElementById('scope2RegionBox');
    if (rb) rb.style.display = 'none';
    var gcef = document.getElementById('scope2FgCef');
    if (gcef) gcef.style.display = 'none';
    var fcef = document.getElementById('scope2Fcef');
    if (fcef) fcef.value = '';
    var chk = document.getElementById('scope2ChkEfOvr');
    if (chk) chk.checked = false;
    var fefOvr = document.getElementById('scope2FefOvr');
    if (fefOvr) { fefOvr.value = ''; fefOvr.style.display = 'none'; }
    document.getElementById('scope2Fper').value = '';
    upFiles = [];
    document.querySelectorAll('.scope2-app .ferr').forEach(function(e) { e.classList.remove('ferr'); });
  }

  var btnAdd = document.getElementById('scope2BtnAdd');
  if (btnAdd) btnAdd.addEventListener('click', openM);
  var mx = document.getElementById('scope2Mx');
  if (mx) mx.addEventListener('click', closeM);
  var n1c = document.getElementById('scope2N1c');
  if (n1c) n1c.addEventListener('click', closeM);

  document.getElementById('scope2N1n').addEventListener('click', function() {
    if (!selSrc) {
      document.getElementById('scope2FgSrc').classList.add('ferr');
      return;
    }
    populateUnits();
    showEF();
    goStep(2);
  });

  document.getElementById('scope2N2b').addEventListener('click', function() { goStep(1); });

  document.getElementById('scope2N2n').addEventListener('click', function() {
    document.querySelectorAll('.scope2-app .ferr').forEach(function(e) { e.classList.remove('ferr'); });
    var ok = true;
    if (!document.getElementById('scope2Fqty').value || parseFloat(document.getElementById('scope2Fqty').value) <= 0) {
      document.getElementById('scope2FgQty').classList.add('ferr');
      ok = false;
    }
    if (document.getElementById('scope2Funit').value === '') {
      document.getElementById('scope2FgUnit').classList.add('ferr');
      ok = false;
    }
    if (!document.getElementById('scope2Fper').value) {
      document.getElementById('scope2FgPer').classList.add('ferr');
      ok = false;
    }
    if (!document.getElementById('scope2Fdt').value) {
      document.getElementById('scope2FgDt').classList.add('ferr');
      ok = false;
    }
    if (!document.getElementById('scope2Ffac').value.trim()) {
      document.getElementById('scope2FgFac').classList.add('ferr');
      ok = false;
    }
    if (selSrc && selSrc.isGrid && gridEF[selRegionIdx] && gridEF[selRegionIdx].region && gridEF[selRegionIdx].region.indexOf('Custom') !== -1) {
      if (!document.getElementById('scope2Fcef').value || parseFloat(document.getElementById('scope2Fcef').value) < 0) {
        document.getElementById('scope2FgCef').classList.add('ferr');
        ok = false;
      }
    }
    if (!ok) return;
    var t = calcCO2e();
    var uIdx = parseInt(document.getElementById('scope2Funit').value, 10);
    var uLabel = selSrc.units[uIdx] ? selSrc.units[uIdx].label : '';
    var per = document.getElementById('scope2Fper').value;
    var dt = document.getElementById('scope2Fdt').value;
    var fac = document.getElementById('scope2Ffac').value;
    var regionTxt = '';
    if (selSrc.isGrid && gridEF[selRegionIdx]) {
      if (gridEF[selRegionIdx].region.indexOf('Custom') !== -1) {
        regionTxt = ' | Region: Custom (' + document.getElementById('scope2Fcef').value + ' kgCO2/kWh)';
      } else {
        regionTxt = ' | Region: ' + gridEF[selRegionIdx].region;
      }
    }
    document.getElementById('scope2Rvw').innerHTML = '<strong>Source:</strong> ' + selSrc.name + '<br><strong>Quantity:</strong> ' + document.getElementById('scope2Fqty').value + ' ' + uLabel + regionTxt + '<br><strong>Emissions:</strong> <span style="color:var(--primary-green);font-weight:700">' + t.toFixed(4) + ' tCO2e</span><br><strong>EF:</strong> ' + (selSrc.note || '') + '<br><strong>Period:</strong> ' + per + ' | ' + dt + '<br><strong>Facility:</strong> ' + fac;
    goStep(3);
  });

  document.getElementById('scope2N3b').addEventListener('click', function() { goStep(2); });

  document.getElementById('scope2N3s').addEventListener('click', function() {
    var t = calcCO2e();
    var uIdx = parseInt(document.getElementById('scope2Funit').value, 10);
    var formData = new FormData();
    formData.append('_token', window.scope2Csrf);
    formData.append('entryDate', document.getElementById('scope2Fdt').value);
    formData.append('facilitySelect', document.getElementById('scope2Ffac').value.trim());
    formData.append('scopeSelect', '2');
    formData.append('emissionSourceSelect', selSrc.name);
    formData.append('co2eValue', t.toFixed(6));
    formData.append('activityData', document.getElementById('scope2Fqty').value);
    formData.append('confidenceLevel', 'medium');
    formData.append('dataSource', 'manual');
    var notes = document.getElementById('scope2Fdsc').value.trim();
    var per = document.getElementById('scope2Fper').value;
    if (per) notes = (notes ? notes + '\n' : '') + 'Period: ' + per;
    if (selSrc.isGrid && gridEF[selRegionIdx]) {
      var r = gridEF[selRegionIdx];
      if (r.region.indexOf('Custom') !== -1) {
        notes = (notes ? notes + '\n' : '') + 'Region: Custom (' + document.getElementById('scope2Fcef').value + ' kgCO2/kWh)';
      } else {
        notes = (notes ? notes + '\n' : '') + 'Region: ' + r.region;
      }
    }
    formData.append('entryNotes', notes);
    for (var i = 0; i < upFiles.length; i++) {
      formData.append('supporting_documents[]', upFiles[i]);
    }

    var btn = document.getElementById('scope2N3s');
    btn.disabled = true;
    btn.textContent = 'Saving...';

    fetch(window.scope2StoreUrl, {
      method: 'POST',
      body: formData,
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); })
    .then(function(res) {
      btn.disabled = false;
      btn.textContent = '✓ Submit';
      if (res.ok && res.data && res.data.status) {
        document.getElementById('scope2SucM').textContent = selSrc.name + ' | ' + t.toFixed(4) + ' tCO2e';
        for (var i = 1; i <= 3; i++) {
          var p = document.getElementById('scope2P' + i);
          if (p) { p.classList.remove('show'); p.style.display = 'none'; }
        }
        var stp = document.querySelector('#scope2Ov .stp');
        if (stp) stp.style.display = 'none';
        var suc = document.getElementById('scope2Suc');
        if (suc) { suc.classList.add('on'); suc.style.display = 'block'; }
        if (window.scope2Table && typeof $ !== 'undefined' && $.fn.DataTable && $.fn.DataTable.isDataTable('#scope2Table')) window.scope2Table.ajax.reload();
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

  document.getElementById('scope2SucC').addEventListener('click', closeM);
  document.getElementById('scope2SucA').addEventListener('click', function() {
    var suc = document.getElementById('scope2Suc');
    if (suc) { suc.classList.remove('on'); suc.style.display = 'none'; }
    resetForm();
    curSub = 'electricity';
    renderSubBtns();
    renderSrcCards();
    goStep(1);
  });

  document.getElementById('scope2Fqty').addEventListener('input', calcCO2e);
  document.getElementById('scope2Funit').addEventListener('change', function() { showEF(); calcCO2e(); });
  var fregion = document.getElementById('scope2Fregion');
  if (fregion) fregion.addEventListener('change', function() { updateGridEFDisplay(); calcCO2e(); });
  var fcef = document.getElementById('scope2Fcef');
  if (fcef) fcef.addEventListener('input', function() { updateGridEFDisplay(); calcCO2e(); });
  var chkEf = document.getElementById('scope2ChkEfOvr');
  if (chkEf) chkEf.addEventListener('change', function() {
    var inp = document.getElementById('scope2FefOvr');
    if (inp) inp.style.display = this.checked ? 'block' : 'none';
    if (!this.checked && inp) inp.value = '';
    calcCO2e();
  });
  var fefOvr = document.getElementById('scope2FefOvr');
  if (fefOvr) fefOvr.addEventListener('input', calcCO2e);

  var fup = document.getElementById('scope2Fup'), fupi = document.getElementById('scope2Fupi');
  if (fup && fupi) {
    fup.addEventListener('click', function() { fupi.click(); });
    fupi.addEventListener('change', function() {
      for (var i = 0; i < this.files.length; i++) upFiles.push(this.files[i]);
      renderFiles();
      this.value = '';
    });
  }

  function renderFiles() {
    var c = document.getElementById('scope2Ffl');
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

  if (typeof $ !== 'undefined' && $('#scope2Table').length) {
    window.scope2Table = $('#scope2Table').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: window.scope2DataUrl,
        data: function(d) {
          d.subcat = filterCat === 'all' ? '' : filterCat;
        }
      },
      columns: [
        { data: 'emission_source', name: 'emission_source' },
        { data: 'activity_data', name: 'activity_data', render: function(v) { return v != null ? Number(v) : '-'; } },
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
  document.addEventListener('DOMContentLoaded', initScope2);
} else {
  initScope2();
}
})();
</script>

<script>
(function(){
var sources = typeof window.scope1Sources !== 'undefined' ? window.scope1Sources : {};
var GWP_CH4 = sources.GWP_CH4 || 28;
var GWP_N2O = sources.GWP_N2O || 265;
var S = {
  stationary: sources.stationary || [],
  mobile: sources.mobile || [],
  fugitive: sources.fugitive || []
};
var curSub = 'stationary', selSrc = null, upFiles = [], filterCat = 'all', curStep = 1;
var cats = { all: 'All', stationary: 'Stationary', mobile: 'Mobile', fugitive: 'Fugitive' };

function loadStats() {
  fetch(window.scope1StatsUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
    .then(function(r) { return r.json(); })
    .then(function(d) {
      document.getElementById('scope1StatTotal').textContent = d.total || 0;
      document.getElementById('scope1StatStationary').textContent = d.stationary || 0;
      document.getElementById('scope1StatMobile').textContent = d.mobile || 0;
      document.getElementById('scope1StatFugitive').textContent = d.fugitive || 0;
      var tabs = document.getElementById('scope1FTabs');
      if (tabs) {
        tabs.querySelectorAll('.ftab').forEach(function(tab) {
          var k = tab.getAttribute('data-f');
          var c = k === 'all' ? d.total : (k === 'stationary' ? d.stationary : (k === 'mobile' ? d.mobile : d.fugitive));
          var cn = tab.querySelector('.cn');
          if (cn) cn.textContent = c;
        });
      }
    })
    .catch(function() {});
}

// Run DOM-dependent init when ready
function initScope1() {
// Tabs
var wrap = document.getElementById('scope1FTabs');
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
      if (window.scope1Table && $.fn.DataTable.isDataTable('#scope1Table')) {
        window.scope1Table.ajax.reload();
      }
    });
    wrap.appendChild(t);
  });
}

function openM() {
  selSrc = null;
  upFiles = [];
  resetForm();
  curSub = 'stationary';
  renderSubBtns();
  renderSrcCards();
  var suc = document.getElementById('scope1Suc');
  if (suc) { suc.classList.remove('on'); suc.style.display = 'none'; }
  var stp = document.querySelector('#scope1Ov .stp');
  if (stp) stp.style.display = '';
  for (var i = 1; i <= 3; i++) {
    var p = document.getElementById('scope1P' + i);
    if (p) { p.classList.remove('show'); p.style.display = (i === 1) ? 'block' : 'none'; }
  }
  goStep(1);
  document.getElementById('scope1Ov').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeM() {
  document.getElementById('scope1Ov').classList.remove('open');
  document.body.style.overflow = '';
}

function renderSubBtns() {
  var c = document.getElementById('scope1Scb');
  if (!c) return;
  c.innerHTML = '';
  ['stationary', 'mobile', 'fugitive'].forEach(function(k) {
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
  var g = document.getElementById('scope1SrcG');
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
      document.getElementById('scope1FgSrc').classList.remove('ferr');
    });
    g.appendChild(d);
  });
}

function populateUnits() {
  var sel = document.getElementById('scope1Funit');
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
  var b = document.getElementById('scope1EfBox');
  b.style.display = 'none';
  if (!selSrc) return;
  b.style.display = 'block';
  var row = document.getElementById('scope1EfRow');
  if (!row) return;
  if (selSrc.isFug) {
    row.innerHTML = '<div class="ef-item"><div class="ef-val">' + (selSrc.gwp || 0).toLocaleString() + '</div><div class="ef-lbl">GWP (AR5)</div></div>';
  } else {
    var u = selSrc.units[0];
    if (!u) return;
    row.innerHTML = '<div class="ef-item"><div class="ef-val">' + (u.co2 || 0) + '</div><div class="ef-lbl">kgCO2/' + (u.label || u.u).split('(')[0].trim() + '</div></div><div class="ef-item"><div class="ef-val">' + (u.ch4 || 0) + '</div><div class="ef-lbl">kgCH4/TJ</div></div><div class="ef-item"><div class="ef-val">' + (u.n2o || 0) + '</div><div class="ef-lbl">kgN2O/TJ</div></div>';
  }
}

function calcCO2e() {
  var box = document.getElementById('scope1Co2box');
  if (!selSrc) { box.style.display = 'none'; return 0; }
  var qty = parseFloat(document.getElementById('scope1Fqty').value), uIdx = document.getElementById('scope1Funit').value;
  if (!qty || qty <= 0 || uIdx === '') { box.style.display = 'none'; return 0; }
  uIdx = parseInt(uIdx, 10);
  var uD = selSrc.units[uIdx];
  if (!uD) { box.style.display = 'none'; return 0; }
  var t = 0;
  if (selSrc.isFug) {
    var gwp = selSrc.gwp || 0;
    if (uD.u === 'm3' && selSrc.gwpM3) gwp = selSrc.gwpM3;
    t = (qty * gwp) / 1000;
    document.getElementById('scope1Co2f').textContent = qty + ' ' + (uD.label || uD.u).split('(')[0].trim() + ' x GWP ' + gwp + ' = ' + t.toFixed(4) + ' tCO2e';
  } else {
    var co2 = qty * (uD.co2 || 0), tj = qty * (uD.ncv || 0), ch4 = tj * (uD.ch4 || 0), n2o = tj * (uD.n2o || 0);
    t = co2 / 1000 + (ch4 * GWP_CH4) / 1000 + (n2o * GWP_N2O) / 1000;
    document.getElementById('scope1Co2f').textContent = 'CO2:' + (co2 / 1000).toFixed(4) + 't CH4:' + (ch4 * GWP_CH4 / 1000).toFixed(4) + 't N2O:' + (n2o * GWP_N2O / 1000).toFixed(4) + 't';
  }
  document.getElementById('scope1Co2v').textContent = t.toFixed(4);
  box.style.display = 'block';
  return t;
}

function goStep(n) {
  curStep = n;
  var suc = document.getElementById('scope1Suc');
  if (suc) { suc.classList.remove('on'); suc.style.display = 'none'; }
  for (var i = 1; i <= 3; i++) {
    var p = document.getElementById('scope1P' + i);
    var m = document.getElementById('scope1Ms' + i);
    if (p) {
      p.classList.toggle('show', i === n);
      p.style.display = (i === n) ? 'block' : 'none';
    }
    if (m) {
      m.className = 'st' + (i < n ? ' dn' : '') + (i === n ? ' ac2' : '');
    }
  }
}

function resetForm() {
  ['scope1Fqty', 'scope1Fdt', 'scope1Ffac', 'scope1Fdsc'].forEach(function(id) {
    var el = document.getElementById(id);
    if (el) el.value = '';
  });
  var funit = document.getElementById('scope1Funit');
  if (funit) funit.innerHTML = '<option value="">Select...</option>';
  var ffl = document.getElementById('scope1Ffl');
  if (ffl) ffl.innerHTML = '';
  var co2box = document.getElementById('scope1Co2box');
  if (co2box) co2box.style.display = 'none';
  var efBox = document.getElementById('scope1EfBox');
  if (efBox) efBox.style.display = 'none';
  document.getElementById('scope1Fper').value = '';
  upFiles = [];
  document.querySelectorAll('.scope1-app .ferr').forEach(function(e) { e.classList.remove('ferr'); });
}

var btnAdd = document.getElementById('scope1BtnAdd');
if (btnAdd) btnAdd.addEventListener('click', openM);
var mx = document.getElementById('scope1Mx');
if (mx) mx.addEventListener('click', closeM);
var n1c = document.getElementById('scope1N1c');
if (n1c) n1c.addEventListener('click', closeM);
/* Click outside does not close popup — user must use Cancel or X */

document.getElementById('scope1N1n').addEventListener('click', function() {
  if (!selSrc) {
    document.getElementById('scope1FgSrc').classList.add('ferr');
    return;
  }
  populateUnits();
  showEF();
  goStep(2);
});

document.getElementById('scope1N2b').addEventListener('click', function() { goStep(1); });

document.getElementById('scope1N2n').addEventListener('click', function() {
  document.querySelectorAll('.scope1-app .ferr').forEach(function(e) { e.classList.remove('ferr'); });
  var ok = true;
  if (!document.getElementById('scope1Fqty').value || parseFloat(document.getElementById('scope1Fqty').value) <= 0) {
    document.getElementById('scope1FgQty').classList.add('ferr');
    ok = false;
  }
  if (document.getElementById('scope1Funit').value === '') {
    document.getElementById('scope1FgUnit').classList.add('ferr');
    ok = false;
  }
  if (!document.getElementById('scope1Fper').value) {
    document.getElementById('scope1FgPer').classList.add('ferr');
    ok = false;
  }
  if (!document.getElementById('scope1Fdt').value) {
    document.getElementById('scope1FgDt').classList.add('ferr');
    ok = false;
  }
  if (!document.getElementById('scope1Ffac').value.trim()) {
    document.getElementById('scope1FgFac').classList.add('ferr');
    ok = false;
  }
  if (!ok) return;
  var t = calcCO2e();
  var uIdx = parseInt(document.getElementById('scope1Funit').value, 10);
  var uLabel = selSrc.units[uIdx] ? selSrc.units[uIdx].label : '';
  var per = document.getElementById('scope1Fper').value;
  var dt = document.getElementById('scope1Fdt').value;
  var fac = document.getElementById('scope1Ffac').value;
  document.getElementById('scope1Rvw').innerHTML = '<strong>Source:</strong> ' + selSrc.name + '<br><strong>Quantity:</strong> ' + document.getElementById('scope1Fqty').value + ' ' + uLabel + '<br><strong>Emissions:</strong> <span style="color:var(--primary-green);font-weight:700">' + t.toFixed(4) + ' tCO2e</span><br><strong>EF:</strong> ' + (selSrc.note || '') + '<br><strong>Period:</strong> ' + per + ' | ' + dt + '<br><strong>Facility:</strong> ' + fac;
  goStep(3);
});

document.getElementById('scope1N3b').addEventListener('click', function() { goStep(2); });

document.getElementById('scope1N3s').addEventListener('click', function() {
  var t = calcCO2e();
  var uIdx = parseInt(document.getElementById('scope1Funit').value, 10);
  var formData = new FormData();
  formData.append('_token', window.scope1Csrf);
  formData.append('entryDate', document.getElementById('scope1Fdt').value);
  formData.append('facilitySelect', document.getElementById('scope1Ffac').value.trim());
  formData.append('scopeSelect', '1');
  formData.append('emissionSourceSelect', selSrc.name);
  formData.append('co2eValue', t.toFixed(6));
  formData.append('activityData', document.getElementById('scope1Fqty').value);
  formData.append('confidenceLevel', 'medium');
  formData.append('dataSource', 'manual');
  var notes = document.getElementById('scope1Fdsc').value.trim();
  var per = document.getElementById('scope1Fper').value;
  if (per) notes = (notes ? notes + '\n' : '') + 'Period: ' + per;
  formData.append('entryNotes', notes);
  for (var i = 0; i < upFiles.length; i++) {
    formData.append('supporting_documents[]', upFiles[i]);
  }

  var btn = document.getElementById('scope1N3s');
  btn.disabled = true;
  btn.textContent = 'Saving...';

  fetch(window.scope1StoreUrl, {
    method: 'POST',
    body: formData,
    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
  })
  .then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); })
  .then(function(res) {
    btn.disabled = false;
    btn.textContent = '✓ Submit';
    if (res.ok && res.data.status) {
      document.getElementById('scope1SucM').textContent = selSrc.name + ' | ' + t.toFixed(4) + ' tCO2e';
      for (var i = 1; i <= 3; i++) {
        var p = document.getElementById('scope1P' + i);
        if (p) { p.classList.remove('show'); p.style.display = 'none'; }
      }
      var stp = document.querySelector('#scope1Ov .stp');
      if (stp) stp.style.display = 'none';
      var suc = document.getElementById('scope1Suc');
      if (suc) { suc.classList.add('on'); suc.style.display = 'block'; }
      if (window.scope1Table && $.fn.DataTable.isDataTable('#scope1Table')) window.scope1Table.ajax.reload();
      loadStats();
    } else {
      var msg = (res.data && res.data.message) || (res.data && res.data.errors && JSON.stringify(res.data.errors)) || 'Save failed';
      alert(msg);
    }
  })
  .catch(function(err) {
    btn.disabled = false;
    btn.textContent = '✓ Submit';
    alert('Network or server error. Please try again.');
  });
});

document.getElementById('scope1SucC').addEventListener('click', closeM);
document.getElementById('scope1SucA').addEventListener('click', function() {
  var suc = document.getElementById('scope1Suc');
  if (suc) { suc.classList.remove('on'); suc.style.display = 'none'; }
  resetForm();
  curSub = 'stationary';
  renderSubBtns();
  renderSrcCards();
  goStep(1);
});

document.getElementById('scope1Fqty').addEventListener('input', calcCO2e);
document.getElementById('scope1Funit').addEventListener('change', function() { showEF(); calcCO2e(); });

var fup = document.getElementById('scope1Fup'), fupi = document.getElementById('scope1Fupi');
if (fup && fupi) {
  fup.addEventListener('click', function() { fupi.click(); });
  fupi.addEventListener('change', function() {
    for (var i = 0; i < this.files.length; i++) upFiles.push(this.files[i]);
    renderFiles();
    this.value = '';
  });
}
function renderFiles() {
  var c = document.getElementById('scope1Ffl');
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

// Escape for safe HTML
function esc(s) {
  if (s == null) return '';
  var d = document.createElement('div');
  d.textContent = s;
  return d.innerHTML;
}

// Delete record button (SweetAlert2)
$(document).on('click', '.deleteBtn', function() {
  var id = this.getAttribute('data-id');
  if (!id) return;
  var btn = this;
  if (typeof Swal === 'undefined') {
    if (confirm('Are you sure you want to delete this emission record? This cannot be undone.')) doDelete();
    return;
  }
  Swal.fire({
    title: 'Are you sure?',
    text: 'This emission record will be deleted. This action cannot be undone.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#2e7d32',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Yes, delete it!'
  }).then(function(result) {
    if (result.isConfirmed) doDelete();
  });

  function doDelete() {
    btn.disabled = true;
    var csrf = document.querySelector('meta[name="csrf-token"]');
    var token = csrf ? csrf.getAttribute('content') : (typeof window.scope1Csrf !== 'undefined' ? window.scope1Csrf : '');
    var baseUrl = (document.body.getAttribute('data-app-url') || '') || '';
    var deleteUrl = (baseUrl ? baseUrl.replace(/\/$/, '') + '/' : '/') + 'emission-records/' + id;
    fetch(deleteUrl, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': token,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
      .then(function(r) {
        return r.json().then(function(data) { return { ok: r.ok, data: data }; });
      })
      .then(function(res) {
        btn.disabled = false;
        if (res.ok) {
          if (window.scope1Table && $.fn.DataTable.isDataTable('#scope1Table')) {
            window.scope1Table.ajax.reload(null, false);
          }
          if (typeof loadStats === 'function') loadStats();
          if (typeof Swal !== 'undefined') {
            Swal.fire('Deleted!', res.data.message || 'Emission record deleted successfully.', 'success');
          }
        } else {
          if (typeof Swal !== 'undefined') {
            Swal.fire('Error', res.data && res.data.message ? res.data.message : 'Failed to delete record.', 'error');
          } else {
            alert(res.data && res.data.message ? res.data.message : 'Failed to delete record.');
          }
        }
      })
      .catch(function() {
        btn.disabled = false;
        if (typeof Swal !== 'undefined') {
          Swal.fire('Error', 'Failed to delete record. Please try again.', 'error');
        } else {
          alert('Failed to delete record. Please try again.');
        }
      });
  }
});

// View record button: open popup modal with record details
$(document).on('click', '.viewBtn', function() {
  var id = this.getAttribute('data-id');
  if (!id) return;
  var modalEl = document.getElementById('scope1ViewRecordModal');
  var bodyEl = document.getElementById('scope1ViewRecordModalBody');
  if (!modalEl || !bodyEl) return;
  bodyEl.innerHTML = '<div class="text-center py-5 scope1-view-loading"><div class="spinner-border text-success" role="status" style="width:2.5rem;height:2.5rem;"></div><p class="text-muted mt-3 mb-0">Loading record...</p></div>';
  var modal = typeof bootstrap !== 'undefined' && bootstrap.Modal ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
  if (modal) modal.show();
  var baseUrl = (typeof window.scope1RecordViewUrl !== 'undefined')
    ? window.scope1RecordViewUrl.replace(/\/[^/]+$/, '')
    : (document.body.getAttribute('data-app-url') || '') || '';
  if (!baseUrl) baseUrl = '';
  var fetchUrl = (baseUrl ? baseUrl.replace(/\/$/, '') + '/' : '/') + 'emission-records/' + id;
  fetch(fetchUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
    .then(function(r) {
      if (!r.ok) throw new Error('Could not load record');
      return r.json();
    })
    .then(function(data) {
      var docBase = (baseUrl ? baseUrl.replace(/\/$/, '') + '/' : '/') + 'emission-records/' + data.id + '/document/';
      var co2Val = data.co2e_value != null ? Number(data.co2e_value).toFixed(4) : '—';
      var dateStr = data.entry_date_formatted || (data.entry_date ? data.entry_date.split('T')[0] : '—');
      var html = '<div class="scope1-view-co2e"><div class="val">' + esc(co2Val) + '</div><div class="lbl">tCO₂e</div></div>';
      html += '<div class="scope1-view-details-card">';
      html += '<div class="scope1-view-row"><span class="k">Source</span><span class="v">' + esc(data.emission_source || '—') + '</span></div>';
      html += '<div class="scope1-view-row"><span class="k">Scope</span><span class="v">Scope ' + esc(String(data.scope || '—')) + '</span></div>';
      html += '<div class="scope1-view-row"><span class="k">Facility</span><span class="v">' + esc(data.facility || '—') + '</span></div>';
      html += '<div class="scope1-view-row"><span class="k">Date</span><span class="v">' + esc(dateStr) + '</span></div>';
      html += '<div class="scope1-view-row"><span class="k">Activity data</span><span class="v">' + (data.activity_data != null ? Number(data.activity_data) : '—') + '</span></div>';
      html += '<div class="scope1-view-row"><span class="k">Data source</span><span class="v">' + esc(data.data_source || '—') + '</span></div>';
      if (data.notes) html += '<div class="scope1-view-row"><span class="k">Notes</span><span class="v">' + esc(data.notes).replace(/\n/g, '<br>') + '</span></div>';
      html += '</div>';
      html += '<div class="scope1-view-attachments"><div class="title"><i class="fas fa-paperclip me-1"></i>Attachments</div>';
      var docs = data.supporting_documents || [];
      if (docs.length === 0) {
        html += '<p class="scope1-view-no-attach mb-0">No attachments for this record.</p>';
      } else {
        for (var i = 0; i < docs.length; i++) {
          var name = (docs[i] && docs[i].split && docs[i].split('/').pop()) || ('Document ' + (i + 1));
          var url = docBase + i;
          html += '<div class="scope1-view-attachment-item"><a href="' + esc(url) + '" target="_blank" rel="noopener" title="Open in new tab">' + esc(name) + '</a><a href="' + esc(url) + '" target="_blank" rel="noopener" class="open-btn"><i class="fas fa-external-link-alt"></i> Open</a></div>';
        }
      }
      html += '</div>';
      bodyEl.innerHTML = html;
    })
    .catch(function() {
      bodyEl.innerHTML = '<div class="alert alert-danger mb-0 rounded-3"><i class="fas fa-exclamation-circle me-2"></i>Failed to load record. Please try again.</div>';
    });
});

// Attachments modal: delegate click to view-attachments buttons (dynamically added by DataTables)
$(document).ready(function() {
  $(document).on('click', '.view-attachments-btn', function() {
    var btn = this;
    var docs = [];
    try {
      docs = JSON.parse(btn.getAttribute('data-docs') || '[]');
    } catch (e) {}
    var urlTemplate = btn.getAttribute('data-url-template') || '';
    var body = document.getElementById('scope1AttachmentsModalBody');
    if (!body) return;
    if (docs.length === 0) {
      body.innerHTML = '<p class="text-muted mb-0">No attachments.</p>';
    } else {
      var ul = document.createElement('ul');
      ul.className = 'list-group list-group-flush';
      docs.forEach(function(d) {
        var url = urlTemplate.replace(':index', d.idx);
        var name = (d.name && String(d.name)) || ('Document ' + (d.idx + 1));
        var li = document.createElement('li');
        li.className = 'list-group-item d-flex align-items-center';
        var icon = document.createElement('i');
        icon.className = 'fas fa-file me-2 text-muted';
        var link = document.createElement('a');
        link.href = url;
        link.target = '_blank';
        link.rel = 'noopener';
        link.className = 'flex-grow-1';
        link.textContent = name;
        li.appendChild(icon);
        li.appendChild(link);
        ul.appendChild(li);
      });
      body.innerHTML = '';
      body.appendChild(ul);
    }
    var modalEl = document.getElementById('scope1AttachmentsModal');
    if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
      var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
      modal.show();
    } else if (modalEl) {
      modalEl.classList.add('show');
      modalEl.style.display = 'block';
      modalEl.setAttribute('aria-hidden', 'false');
      var backdrop = document.createElement('div');
      backdrop.className = 'modal-backdrop fade show';
      backdrop.id = 'scope1AttachmentsBackdrop';
      document.body.appendChild(backdrop);
    }
  });
});

// DataTable
$(document).ready(function() {
  window.scope1Table = $('#scope1Table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: window.scope1DataUrl,
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
      { data: 'attachments', name: 'attachments', orderable: false, searchable: false, createdCell: function(td, cellData) { $(td).html(cellData || ''); } },
      { data: 'actions', name: 'actions', orderable: false, searchable: false, createdCell: function(td, cellData) { $(td).html(cellData || ''); } }
    ],
    order: [[4, 'desc']],
    pageLength: 10,
    responsive: true
  });
  loadStats();
});
} // end initScope1

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initScope1);
} else {
  initScope1();
}
})();
</script>

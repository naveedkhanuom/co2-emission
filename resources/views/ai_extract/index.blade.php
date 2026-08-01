@extends('layouts.app')

@push('styles')
<style>
    /* ---- AI Document Extraction — scoped styling ---------------------- */
    .ai-extract .card { border-radius: 14px; border: 1px solid var(--gray-200); }

    /* Hero */
    .ai-extract .ai-hero {
        background: linear-gradient(135deg, var(--dark-green) 0%, var(--primary-green) 55%, var(--light-green) 100%);
        color: #fff; border: 0; border-radius: 16px; position: relative; overflow: hidden;
    }
    .ai-extract .ai-hero::after {
        content: ''; position: absolute; right: -50px; top: -60px; width: 220px; height: 220px;
        background: rgba(255, 255, 255, .08); border-radius: 50%;
    }
    .ai-extract .ai-hero::before {
        content: ''; position: absolute; right: 90px; bottom: -70px; width: 150px; height: 150px;
        background: rgba(255, 255, 255, .06); border-radius: 50%;
    }
    .ai-extract .ai-hero-body {
        display: flex; align-items: center; gap: 1.25rem; padding: 1.6rem 1.9rem; position: relative; z-index: 1;
    }
    .ai-extract .ai-hero-icon {
        width: 58px; height: 58px; border-radius: 15px; background: rgba(255, 255, 255, .18);
        display: flex; align-items: center; justify-content: center; font-size: 1.7rem; flex-shrink: 0;
    }
    .ai-extract .ai-hero h4 { font-weight: 700; letter-spacing: -.01em; }
    .ai-extract .ai-hero p { opacity: .9; max-width: 640px; }
    .ai-extract .ai-hero-btn {
        background: rgba(255, 255, 255, .16); color: #fff; border: 1px solid rgba(255, 255, 255, .4);
        white-space: nowrap; font-weight: 500; transition: background .2s;
    }
    .ai-extract .ai-hero-btn:hover { background: rgba(255, 255, 255, .3); color: #fff; }

    /* Section titles */
    .ai-extract .section-title { display: flex; align-items: center; gap: .6rem; font-weight: 600; margin: 0; }
    .ai-extract .icon-chip {
        width: 36px; height: 36px; border-radius: 10px; display: inline-flex; align-items: center;
        justify-content: center; background: rgba(46, 125, 50, .12); color: var(--primary-green); flex-shrink: 0;
    }

    /* Dropzone */
    .ai-extract .ai-dropzone {
        display: block; width: 100%; border: 2px dashed var(--gray-300); border-radius: 14px;
        background: var(--gray-50); padding: 2.4rem 1.5rem; cursor: pointer; text-align: center;
        transition: border-color .2s, background .2s, transform .15s;
    }
    .ai-extract .ai-dropzone:hover { border-color: var(--light-green); background: rgba(76, 175, 80, .06); }
    .ai-extract .ai-dropzone.dragover {
        border-color: var(--primary-green); background: rgba(46, 125, 50, .1); transform: scale(1.01);
    }
    .ai-extract .ai-dropzone.has-file { border-style: solid; border-color: var(--light-green); background: rgba(76, 175, 80, .07); }
    .ai-extract .ai-dropzone-icon {
        width: 66px; height: 66px; margin: 0 auto .85rem; border-radius: 50%; color: #fff; font-size: 1.7rem;
        background: linear-gradient(135deg, var(--primary-green), var(--light-green));
        display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 18px rgba(46, 125, 50, .28);
    }
    .ai-extract .ai-dropzone-title { font-size: 1.06rem; font-weight: 600; color: var(--gray-800); }
    .ai-extract .ai-dropzone-sub { color: var(--gray-600); font-size: .85rem; }
    .ai-extract .ai-dropzone-hint {
        display: inline-flex; gap: .4rem; flex-wrap: wrap; justify-content: center; margin-top: .75rem;
    }
    .ai-extract .ai-dropzone-hint .badge {
        background: #fff; border: 1px solid var(--gray-200); color: var(--gray-600); font-weight: 500;
    }

    /* Recent extractions list */
    .ai-extract .recent-item {
        display: flex; align-items: center; gap: 1rem; padding: .75rem .3rem;
        border-bottom: 1px solid var(--gray-100); transition: background .15s;
    }
    .ai-extract .recent-item:last-child { border-bottom: 0; }
    .ai-extract .recent-item:hover { background: var(--gray-50); }
    .ai-extract .recent-file { display: flex; align-items: center; gap: .65rem; min-width: 0; flex: 1 1 40%; }
    .ai-extract .recent-file .file-ico { color: var(--gray-600); flex-shrink: 0; }
    .ai-extract .recent-file .fname { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 500; }
    .ai-extract .recent-meta { color: var(--gray-600); font-size: .82rem; }

    /* Review table */
    .ai-extract #itemsTable thead th {
        background: var(--gray-50); border-bottom: 2px solid var(--gray-200);
        font-size: .74rem; text-transform: uppercase; letter-spacing: .04em; color: var(--gray-600);
        font-weight: 600; vertical-align: bottom;
    }
    .ai-extract #itemsTable td { vertical-align: middle; }
    .ai-extract #itemsTable tbody tr { transition: background .15s; }
    .ai-extract #itemsTable tbody tr.table-warning { background: rgba(245, 124, 0, .07) !important; }
    .ai-extract #itemsTable tbody tr.table-warning:hover { background: rgba(245, 124, 0, .12) !important; }
    .ai-extract #itemsTable .form-control-sm, .ai-extract #itemsTable .form-select-sm { font-size: .85rem; }
    .ai-extract .review-note {
        font-size: .8rem; color: var(--gray-600); display: flex; flex-wrap: wrap;
        gap: .4rem 1.1rem; align-items: center;
    }
</style>
@endpush

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="container-fluid mt-4 ai-extract">

        {{-- Hero header --}}
        <div class="card shadow-sm mb-4 ai-hero">
            <div class="ai-hero-body">
                <div class="ai-hero-icon"><i class="fas fa-robot"></i></div>
                <div class="flex-grow-1">
                    <h4 class="mb-1">AI Document Extraction</h4>
                    <p class="mb-0">Upload an invoice, bill, spreadsheet, Word file, image or PDF — the AI extracts emission line items for you to review.</p>
                </div>
                <a href="{{ route('ai_extract.history') }}" class="btn ai-hero-btn">
                    <i class="fas fa-clock-rotate-left me-1"></i>History
                </a>
            </div>
        </div>

        @unless($aiEnabled)
            <div class="alert alert-warning d-flex align-items-center">
                <i class="fas fa-triangle-exclamation me-2"></i>
                <div>AI is not configured. An administrator must set <code>ANTHROPIC_API_KEY</code> before this feature works.</div>
            </div>
        @endunless

        {{-- Upload --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <form id="extractForm" enctype="multipart/form-data">
                    <label class="ai-dropzone" id="dropzone" for="document">
                        <input type="file" class="d-none" id="document" name="document"
                               accept=".pdf,.jpg,.jpeg,.png,.webp,.gif,.xlsx,.xls,.csv,.docx,.txt" required @unless($aiEnabled) disabled @endunless>
                        <div class="ai-dropzone-icon"><i class="fas fa-cloud-arrow-up" id="dropzoneIcon"></i></div>
                        <div class="ai-dropzone-title" id="dropzoneTitle">Drag &amp; drop a document here</div>
                        <div class="ai-dropzone-sub" id="dropzoneSub">or click to browse from your computer</div>
                        <div class="ai-dropzone-hint">
                            <span class="badge">PDF</span>
                            <span class="badge">Image</span>
                            <span class="badge">Excel / CSV</span>
                            <span class="badge">Word</span>
                            <span class="badge">Text</span>
                            <span class="badge">Max 10 MB</span>
                        </div>
                    </label>

                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-primary btn-lg px-4" id="extractBtn" @unless($aiEnabled) disabled @endunless>
                            <i class="fas fa-wand-magic-sparkles me-2"></i>Extract with AI
                        </button>
                    </div>
                </form>

                <div id="statusArea" class="mt-3"></div>
            </div>
        </div>

        {{-- Recent extractions --}}
        @if(isset($recent) && $recent->count())
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="section-title">
                        <span class="icon-chip"><i class="fas fa-clock-rotate-left"></i></span>
                        Recent extractions
                    </h6>
                </div>
                <div class="card-body pt-2 pb-1">
                    @foreach($recent as $r)
                        <div class="recent-item">
                            <div class="recent-file">
                                <i class="fas fa-file file-ico"></i>
                                <span class="fname">{{ $r->file_name }}</span>
                            </div>
                            <div class="recent-meta d-none d-md-block" style="flex:0 0 130px">{{ $r->document_type ?? '—' }}</div>
                            <div class="recent-meta" style="flex:0 0 80px">{{ $r->items_count }} item(s)</div>
                            <div style="flex:0 0 150px">
                                @php $sc = ['extracted'=>'bg-info','saved'=>'bg-success','discarded'=>'bg-secondary','failed'=>'bg-danger'][$r->status] ?? 'bg-secondary'; @endphp
                                <span class="badge {{ $sc }}">{{ ucfirst($r->status) }}@if($r->status==='saved') · {{ $r->saved_count }} saved @endif</span>
                            </div>
                            <div class="recent-meta d-none d-sm-block text-end" style="flex:0 0 120px">{{ $r->created_at?->diffForHumans() }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Review + edit --}}
        <div class="card shadow-sm d-none" id="reviewCard">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
                <div>
                    <h5 class="section-title">
                        <span class="icon-chip" style="background:rgba(46,125,50,.14)"><i class="fas fa-clipboard-check"></i></span>
                        Review extracted items
                    </h5>
                    <p class="text-muted mb-0 small mt-1 ms-1" id="reviewMeta"></p>
                </div>
                <div class="alert alert-info py-2 px-3 mb-0 small">
                    <i class="fas fa-circle-info me-1"></i>
                    These save as <strong>drafts</strong>. Nothing counts until validated under Review Data.
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle" id="itemsTable">
                        <thead>
                            <tr>
                                <th style="width:38px"></th>
                                <th>Date</th>
                                <th>Scope</th>
                                <th>Emission source</th>
                                <th class="text-end">Quantity</th>
                                <th>Unit</th>
                                <th class="text-end">Factor<br><small class="text-muted fw-normal">kgCO₂e/unit</small></th>
                                <th class="text-end">CO₂e<br><small class="text-muted fw-normal">tCO₂e</small></th>
                                <th>Facility</th>
                                <th>Confidence</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-3">
                    <span class="review-note">
                        <span><i class="fas fa-lightbulb me-1 text-warning"></i>CO₂e recalculates from quantity × factor.</span>
                        <span><span class="badge bg-success">Library</span> = locked to your factors</span>
                        <span><span class="badge bg-warning text-dark">AI est.</span> = verify</span>
                        <span><span class="badge bg-warning text-dark" style="opacity:.5">▊</span> highlighted rows need review</span>
                    </span>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary" id="discardBtn"><i class="fas fa-xmark me-1"></i>Discard</button>
                        <button class="btn btn-success" id="saveBtn"><i class="fas fa-floppy-disk me-1"></i>Save as drafts</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const token       = document.querySelector('meta[name="csrf-token"]').content;
    const extractUrl  = "{{ route('ai_extract.extract') }}";
    const saveUrl     = "{{ route('ai_extract.store') }}";
    const reviewUrl   = "{{ route('review_data.index') }}";

    const form        = document.getElementById('extractForm');
    const extractBtn  = document.getElementById('extractBtn');
    const statusArea  = document.getElementById('statusArea');
    const reviewCard  = document.getElementById('reviewCard');
    const reviewMeta  = document.getElementById('reviewMeta');
    const tbody       = document.querySelector('#itemsTable tbody');
    const saveBtn     = document.getElementById('saveBtn');
    const discardBtn  = document.getElementById('discardBtn');

    const fileInput     = document.getElementById('document');
    const dropzone      = document.getElementById('dropzone');
    const dropzoneIcon  = document.getElementById('dropzoneIcon');
    const dropzoneTitle = document.getElementById('dropzoneTitle');
    const dropzoneSub   = document.getElementById('dropzoneSub');

    let currentDoc = { path: null, name: null, id: null };

    // The company's facilities — same list the manual entry form uses. Facility
    // is stored as the facility name (a string), chosen from this list.
    const facilities = @json(facilities()->pluck('name')->filter()->values());

    // ---- Dropzone: reflect the chosen file & support drag-and-drop ------
    function showFile(name) {
        dropzone.classList.add('has-file');
        dropzoneIcon.className = 'fas fa-file-circle-check';
        dropzoneTitle.textContent = name;
        dropzoneSub.textContent = 'Ready to extract — or click to choose a different file';
    }
    function resetDropzone() {
        dropzone.classList.remove('has-file', 'dragover');
        dropzoneIcon.className = 'fas fa-cloud-arrow-up';
        dropzoneTitle.textContent = 'Drag & drop a document here';
        dropzoneSub.textContent = 'or click to browse from your computer';
    }

    fileInput.addEventListener('change', () => {
        if (fileInput.files.length) showFile(fileInput.files[0].name);
        else resetDropzone();
    });

    ['dragenter', 'dragover'].forEach(ev =>
        dropzone.addEventListener(ev, e => { e.preventDefault(); dropzone.classList.add('dragover'); }));
    ['dragleave', 'dragend'].forEach(ev =>
        dropzone.addEventListener(ev, e => { e.preventDefault(); dropzone.classList.remove('dragover'); }));
    dropzone.addEventListener('drop', e => {
        e.preventDefault();
        dropzone.classList.remove('dragover');
        if (fileInput.disabled || !e.dataTransfer.files.length) return;
        fileInput.files = e.dataTransfer.files;
        fileInput.dispatchEvent(new Event('change'));
    });

    function alertBox(type, msg) {
        statusArea.innerHTML =
            `<div class="alert alert-${type} alert-dismissible fade show">${msg}
             <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`;
    }

    const esc = s => (s == null ? '' : String(s).replace(/[&<>"']/g,
        c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])));

    function recalcRow(tr) {
        const qty    = parseFloat(tr.querySelector('.f-qty').value);
        const factor = parseFloat(tr.querySelector('.f-factor').value); // kg/unit
        const co2eEl = tr.querySelector('.f-co2e');
        if (!isNaN(qty) && !isNaN(factor)) {
            co2eEl.value = (qty * factor / 1000).toFixed(4); // tonnes
        }
    }

    // Build the Facility cell: a dropdown of the company's facilities (matching
    // the manual entry form). Auto-selects when there's only one facility, and
    // pre-selects the AI's guess when it matches a known name. Falls back to a
    // free-text box for companies that haven't set up any facilities yet.
    function facilityCell(guess) {
        guess = (guess || '').trim();
        if (!facilities.length) {
            return `<input type="text" class="form-control form-control-sm f-facility" maxlength="50" placeholder="—" value="${esc(guess)}">`;
        }
        const selected = facilities.length === 1
            ? facilities[0]
            : (facilities.find(f => f.toLowerCase() === guess.toLowerCase()) || '');
        let opts = '<option value="">Select…</option>';
        facilities.forEach(f => {
            opts += `<option value="${esc(f)}" ${f === selected ? 'selected' : ''}>${esc(f)}</option>`;
        });
        return `<select class="form-select form-select-sm f-facility">${opts}</select>`;
    }

    function factorBadge(basis) {
        return basis === 'library'
            ? '<span class="badge bg-success" title="Matched to your factor library — locked for audit">Library</span>'
            : (basis === 'manual'
                ? '<span class="badge bg-secondary">Manual</span>'
                : '<span class="badge bg-warning text-dark" title="AI-estimated factor — verify before validating">AI est.</span>');
    }

    function addRow(item) {
        const tr = document.createElement('tr');
        tr.dataset.scope3CategoryId  = item.scope3_category_id ?? '';
        tr.dataset.emissionFactorId  = item.emission_factor_id ?? '';
        tr.dataset.supplierId        = item.supplier_id ?? '';
        if (item.needs_review) tr.classList.add('table-warning');
        const snippet = item.source_snippet
            ? `<i class="fas fa-circle-info text-primary" title="${esc(item.source_snippet)}"></i>`
            : '';
        const supplierTag = item.supplier_matched
            ? ` <span class="badge bg-success" title="Linked to an existing supplier">linked</span>`
            : (item.supplier_name ? ` <span class="badge bg-light text-dark border" title="No matching supplier on file">new</span>` : '');
        const supplier = item.supplier_name
            ? `<div class="small text-muted">${esc(item.supplier_name)}${supplierTag}</div>` : '';
        const catName  = item.scope3_category_name ? `<div class="small text-muted">Cat: ${esc(item.scope3_category_name)}</div>` : '';

        tr.innerHTML = `
            <td class="text-center">
                <div class="form-check d-inline-block">
                    <input class="form-check-input row-include" type="checkbox" checked>
                </div>
                <div class="mt-1">${snippet}</div>
            </td>
            <td><input type="date" class="form-control form-control-sm f-date" value="${esc(item.date || '')}"></td>
            <td>
                <select class="form-select form-select-sm f-scope">
                    <option value="1" ${item.scope==1?'selected':''}>1</option>
                    <option value="2" ${item.scope==2?'selected':''}>2</option>
                    <option value="3" ${item.scope==3?'selected':''}>3</option>
                </select>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm f-source" maxlength="100" value="${esc(item.emission_source)}">
                ${supplier}${catName}
            </td>
            <td><input type="number" step="any" min="0" class="form-control form-control-sm f-qty text-end" value="${item.activity_data ?? ''}"></td>
            <td><input type="text" class="form-control form-control-sm f-unit" maxlength="20" style="width:80px" value="${esc(item.unit || '')}"></td>
            <td>
                <input type="number" step="any" min="0" class="form-control form-control-sm f-factor text-end" value="${item.factor_kg_per_unit ?? ''}" title="${esc(item.factor_note || '')}">
                <div class="mt-1 text-center f-factor-badge">${factorBadge(item.factor_basis)}</div>
            </td>
            <td><input type="number" step="any" min="0" class="form-control form-control-sm f-co2e text-end" value="${item.co2e_value ?? ''}"></td>
            <td>${facilityCell(item.facility)}</td>
            <td>
                <select class="form-select form-select-sm f-confidence">
                    <option value="high"   ${item.confidence_level=='high'?'selected':''}>High</option>
                    <option value="medium" ${item.confidence_level=='medium'?'selected':''}>Medium</option>
                    <option value="low"    ${item.confidence_level=='low'?'selected':''}>Low</option>
                </select>
            </td>`;

        tr.querySelector('.f-qty').addEventListener('input', () => recalcRow(tr));
        tr.querySelector('.f-factor').addEventListener('input', () => {
            // Hand-editing the factor breaks the library lock — drop it and relabel.
            tr.dataset.emissionFactorId = '';
            tr.querySelector('.f-factor-badge').innerHTML = factorBadge('manual');
            recalcRow(tr);
        });
        tbody.appendChild(tr);
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!fileInput.files.length) { alertBox('warning', 'Choose a document first.'); return; }

        extractBtn.disabled = true;
        extractBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Reading document…';
        statusArea.innerHTML = '';

        const fd = new FormData();
        fd.append('document', fileInput.files[0]);

        fetch(extractUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }, body: fd })
            .then(async r => {
                const text = await r.text();
                let data;
                try { data = JSON.parse(text); }
                catch (e) {
                    // Non-JSON reply — almost always a PHP timeout or 500 HTML page.
                    throw new Error(r.status === 419
                        ? 'Your session expired. Refresh the page and try again.'
                        : (r.status === 413 ? 'The file is too large for the server to accept.'
                        : 'Server error (' + r.status + '). The extraction may have taken too long — try a smaller or clearer file.'));
                }
                return data;
            })
            .then(data => {
                if (!data.ok) { alertBox('danger', esc(data.message || 'Extraction failed.')); return; }

                currentDoc = { path: data.document_path, name: data.document_name, id: data.extraction_id };
                tbody.innerHTML = '';
                (data.line_items || []).forEach(addRow);
                reviewMeta.textContent =
                    `${data.line_items.length} item(s) · ${data.document_type || 'document'} · ${data.document_name || ''}`;
                reviewCard.classList.remove('d-none');
                alertBox('success', esc(data.message));
                reviewCard.scrollIntoView({ behavior: 'smooth' });
            })
            .catch(err => alertBox('danger', esc(err.message || 'Network error while contacting the server.')))
            .finally(() => {
                extractBtn.disabled = false;
                extractBtn.innerHTML = '<i class="fas fa-wand-magic-sparkles me-2"></i>Extract with AI';
            });
    });

    discardBtn.addEventListener('click', function () {
        reviewCard.classList.add('d-none');
        tbody.innerHTML = '';
        form.reset();
        resetDropzone();
        statusArea.innerHTML = '';
    });

    saveBtn.addEventListener('click', function () {
        const items = [];
        tbody.querySelectorAll('tr').forEach(tr => {
            if (!tr.querySelector('.row-include').checked) return;
            const factorKg = parseFloat(tr.querySelector('.f-factor').value);
            const scope    = tr.querySelector('.f-scope').value;
            items.push({
                entry_date:       tr.querySelector('.f-date').value || null,
                scope:            scope,
                emission_source:  tr.querySelector('.f-source').value.trim(),
                facility:         tr.querySelector('.f-facility').value.trim(),
                activity_data:    tr.querySelector('.f-qty').value || null,
                emission_factor:  isNaN(factorKg) ? null : (factorKg / 1000), // tonnes/unit
                co2e_value:       tr.querySelector('.f-co2e').value || 0,
                confidence_level: tr.querySelector('.f-confidence').value,
                scope3_category_id: scope === '3' ? (tr.dataset.scope3CategoryId || null) : null,
                supplier_id:      scope === '3' ? (tr.dataset.supplierId || null) : null,
                emission_factor_id: tr.dataset.emissionFactorId || null,
                notes:            'Extracted by AI from ' + (currentDoc.name || 'uploaded document'),
            });
        });

        if (!items.length) { alertBox('warning', 'Select at least one row to save.'); return; }

        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving…';

        fetch(saveUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ document_path: currentDoc.path, extraction_id: currentDoc.id, items })
        })
            .then(r => r.json().then(j => ({ status: r.status, body: j })))
            .then(({ status, body }) => {
                if (status === 422) {
                    alertBox('danger', 'Validation failed: ' + esc(JSON.stringify(body.errors || body.message)));
                    return;
                }
                if (!body.ok) { alertBox('danger', esc(body.message || 'Save failed.')); return; }
                reviewCard.classList.add('d-none');
                tbody.innerHTML = '';
                form.reset();
                resetDropzone();
                statusArea.innerHTML =
                    `<div class="alert alert-success">${esc(body.message)}
                        <a href="${reviewUrl}" class="alert-link ms-2">Go to Review Data →</a></div>`;
                statusArea.scrollIntoView({ behavior: 'smooth' });
            })
            .catch(() => alertBox('danger', 'Network error while saving.'))
            .finally(() => {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-floppy-disk me-1"></i>Save as drafts';
            });
    });
})();
</script>
@endpush

{{--
    Quick-start band for the Scope entry pages.

    Leads with the fastest ways to get data in (OCR a bill, import a spreadsheet)
    and demotes manual entry to the third option, so non-expert users aren't faced
    with a blank form first.

    Params (via @include):
      $scope         int    1 | 2 | 3  — tailors copy and whether the bill card shows
      $manualTrigger string  DOM id of the page's existing "Add entry" button; the
                             manual card clicks it to open the guided modal.

    Reuses existing routes only (bill.upload, emissions.import.form) — no new backend.
--}}
@php
    $qsScope = $scope ?? 1;
    // Bill OCR only handles fuel (Scope 1) and electricity (Scope 2).
    $qsBill = match ((int) $qsScope) {
        1 => ['type' => 'fuel',        'label' => 'Snap a fuel bill',        'help' => "Photo or PDF of a diesel/petrol bill — we read the litres for you."],
        2 => ['type' => 'electricity', 'label' => 'Snap an electricity bill', 'help' => "Photo or PDF of a power bill — we read the kWh for you."],
        default => null,
    };
@endphp

<style>
.qstart{margin-bottom:24px;background:linear-gradient(135deg,rgba(46,125,50,.05),rgba(3,169,244,.05));border:1px solid var(--gray-200);border-radius:16px;padding:18px 20px}
.qstart .qs-head{display:flex;align-items:baseline;gap:12px;flex-wrap:wrap;margin-bottom:14px}
.qstart .qs-eyebrow{font-size:.8125rem;font-weight:700;color:var(--primary-green);display:inline-flex;align-items:center;gap:7px;text-transform:uppercase;letter-spacing:.04em}
.qstart .qs-sub{font-size:.8125rem;color:var(--gray-600)}
.qstart .qs-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px}
.qstart .qs-card{display:flex;flex-direction:column;align-items:flex-start;gap:2px;text-align:left;background:#fff;border:1.5px solid var(--gray-200);border-radius:13px;padding:16px 16px 14px;cursor:pointer;text-decoration:none;transition:transform .15s,box-shadow .15s,border-color .15s;font-family:inherit;width:100%}
.qstart .qs-card:hover{transform:translateY(-2px);border-color:var(--light-green);box-shadow:0 6px 18px rgba(46,125,50,.12)}
.qstart .qs-card .qs-ic{font-size:1.5rem;line-height:1;margin-bottom:6px}
.qstart .qs-card b{font-size:.95rem;color:var(--gray-800)}
.qstart .qs-card small{font-size:.78rem;color:var(--gray-600);line-height:1.35;margin-bottom:8px}
.qstart .qs-card .qs-go{margin-top:auto;font-size:.8rem;font-weight:600;color:var(--primary-green);display:inline-flex;align-items:center;gap:6px}
.qstart .qs-card.qs-primary{border-color:rgba(46,125,50,.35);background:linear-gradient(180deg,#fff,rgba(76,175,80,.04))}
.qstart .qs-manual{color:inherit}
</style>

<div class="qstart">
    <div class="qs-head">
        <span class="qs-eyebrow"><i class="fas fa-bolt"></i> Fastest ways to add data</span>
        <span class="qs-sub">Let us do the data entry — pick whichever you already have.</span>
    </div>
    <div class="qs-grid">
        @if($qsBill)
            @canany(['create-utility-bill', 'list-utility-bills'])
                <a class="qs-card qs-primary" href="{{ route('bill.upload', ['type' => $qsBill['type']]) }}">
                    <span class="qs-ic">📸</span>
                    <b>{{ $qsBill['label'] }}</b>
                    <small>{{ $qsBill['help'] }}</small>
                    <span class="qs-go">Upload bill <i class="fas fa-arrow-right"></i></span>
                </a>
            @endcanany
        @endif

        <a class="qs-card" href="{{ route('emissions.import.form') }}">
            <span class="qs-ic">📄</span>
            <b>Import a spreadsheet</b>
            <small>Add many rows at once from an Excel or CSV file.</small>
            <span class="qs-go">Import file <i class="fas fa-arrow-right"></i></span>
        </a>

        <button type="button" class="qs-card qs-manual" onclick="document.getElementById('{{ $manualTrigger }}')?.click()">
            <span class="qs-ic">✍️</span>
            <b>Enter manually</b>
            <small>Type in a single entry using the guided form.</small>
            <span class="qs-go">Add entry <i class="fas fa-arrow-right"></i></span>
        </button>
    </div>
</div>

{{--
    The monitoring plan's narrative and tabular sections — everything in the
    EAD workbook that is not a source or a stream.

    Each section is its OWN form posting to mrv.saveReport with a `section`
    field. The workbook takes weeks to complete and is filled by different
    people: the QA procedures come from the quality manager, the mitigation
    measures from whoever owns the reduction plan. One combined submit would
    mean one person's draft overwriting another's, and a validation failure
    anywhere losing all of it.

    Repeating tables render what is saved plus two blank rows, capped at the
    number of rows the workbook itself has. Blank rows are discarded on save,
    so the table grows by being used and needs no JavaScript.
--}}
@php
    $plan          = $report;
    $contacts      = $plan?->contacts ?? [];
    $products      = $plan?->products ?? [];
    $methane       = $plan?->methane ?? [];
    $gaps          = $plan?->data_gaps ?? [];
    $management    = $plan?->management ?? [];
    $mitigation    = $plan?->mitigation_measures ?? [];
    $measures      = $mitigation['measures'] ?? [];

    $rowsFor = fn (array $existing, int $max) => min(max(count($existing) + 2, 3), $max);

    $productRows   = $rowsFor($products, 10);
    $gapRows       = $rowsFor($gaps, 10);
    $respRows      = $rowsFor($management['responsibilities'] ?? [], 5);
    $measureRows   = $rowsFor($measures, 8);

    // Which sections have anything in them — drives the completeness badges.
    // Does anything here actually use the fall-back approach? Derived rather
    // than asked, because the emission sources already say so — and a second,
    // manually-maintained answer to the same question would eventually
    // contradict the first.
    $usesMeasurement = $sources->contains('methodology', 'measurement');
    $measurementRecorded = filled($plan?->measurement_approach) || filled($plan?->measurement_derivation);

    $usesFallback = $sources->contains('methodology', 'fallback');
    $fallbackRecorded = filled($plan?->fallback_description) || filled($plan?->fallback_justification);

    $done = [
        'contacts'     => filled(data_get($contacts, 'primary.surname')),
        'products'     => count($products) > 0,
        'measurement'  => $usesMeasurement ? $measurementRecorded : true,
        'fallback'     => $usesFallback ? $fallbackRecorded : true,
        'methane'      => $plan?->methane_present ? filled(data_get($methane, 'annual_volume')) : true,
        'verification' => filled($plan?->verification_text),
        'management'   => count($management['responsibilities'] ?? []) > 0,
        'mitigation'   => count($measures) > 0,
    ];
    $doneCount = count(array_filter($done));
    $sectionCount = count($done);
@endphp

<div class="card border-0 shadow-sm mt-3" style="border-radius:16px;">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2" style="border-radius:16px 16px 0 0;">
        <div>
            <h5 class="mb-0 fw-bold">Monitoring Plan Details</h5>
            <div class="text-muted" style="font-size:.78rem;">
                Sheets 2c1, 2c2, 3g, 4h, 4I and 4J of the EAD workbook
            </div>
        </div>
        <span class="badge rounded-pill {{ $doneCount === $sectionCount ? 'bg-success' : 'bg-secondary' }}" style="font-size:.78rem;">
            {{ $doneCount }} of {{ $sectionCount }} sections started
        </span>
    </div>

    <div class="accordion accordion-flush" id="planAccordion">

        {{-- ============ CONTACTS — 2c1 ============ --}}
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secContacts">
                    <i class="fas {{ $done['contacts'] ? 'fa-circle-check text-success' : 'fa-circle text-muted' }} me-2"></i>
                    Contacts <span class="text-muted ms-2" style="font-size:.8rem;">Who EAD contacts about this plan</span>
                </button>
            </h2>
            <div id="secContacts" class="accordion-collapse collapse" data-bs-parent="#planAccordion">
                <div class="accordion-body">
                    <form method="POST" action="{{ route('mrv.saveReport') }}">
                        @csrf
                        <input type="hidden" name="facility_id" value="{{ $facility->id }}">
                        <input type="hidden" name="year" value="{{ $year }}">
                        <input type="hidden" name="section" value="contacts">

                        @foreach(['primary' => 'Primary contact', 'alternate' => 'Alternative contact'] as $which => $heading)
                            <h6 class="fw-bold small text-uppercase text-muted mt-2">{{ $heading }}</h6>
                            <div class="row g-2 mb-3">
                                @foreach(['title' => 'Title', 'first_name' => 'First name', 'surname' => 'Surname', 'job_title' => 'Job title'] as $f => $label)
                                    <div class="col-md-3">
                                        <label class="form-label small">{{ $label }}</label>
                                        <input type="text" name="contacts[{{ $which }}][{{ $f }}]" class="form-control form-control-sm"
                                               value="{{ data_get($contacts, "$which.$f") }}">
                                    </div>
                                @endforeach
                                <div class="col-md-4">
                                    <label class="form-label small">Organisation <span class="text-muted">(if not the operator)</span></label>
                                    <input type="text" name="contacts[{{ $which }}][organisation]" class="form-control form-control-sm" value="{{ data_get($contacts, "$which.organisation") }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Telephone</label>
                                    <input type="text" name="contacts[{{ $which }}][telephone]" class="form-control form-control-sm" value="{{ data_get($contacts, "$which.telephone") }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Email</label>
                                    <input type="email" name="contacts[{{ $which }}][email]" class="form-control form-control-sm" value="{{ data_get($contacts, "$which.email") }}">
                                </div>
                            </div>
                        @endforeach

                        <button class="btn btn-sm btn-success"><i class="fas fa-save me-1"></i>Save contacts</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ============ PRODUCTS — 2c2 (b) ============ --}}
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secProducts">
                    <i class="fas {{ $done['products'] ? 'fa-circle-check text-success' : 'fa-circle text-muted' }} me-2"></i>
                    Products <span class="text-muted ms-2" style="font-size:.8rem;">What you make, and how much of it</span>
                </button>
            </h2>
            <div id="secProducts" class="accordion-collapse collapse" data-bs-parent="#planAccordion">
                <div class="accordion-body">
                    <form method="POST" action="{{ route('mrv.saveReport') }}">
                        @csrf
                        <input type="hidden" name="facility_id" value="{{ $facility->id }}">
                        <input type="hidden" name="year" value="{{ $year }}">
                        <input type="hidden" name="section" value="products">

                        <p class="text-muted small">
                            Identify every product whose production contributes to emissions here. The product IDs
                            (P01…) are what an emission source refers to.
                        </p>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:52px;">ID</th>
                                        <th>Product category</th>
                                        <th>Technology / process</th>
                                        <th style="width:70px;">Energy</th>
                                        <th style="width:70px;">Process</th>
                                        <th>Capacity</th>
                                        <th>Actual production</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @for($i = 0; $i < $productRows; $i++)
                                    @php $p = $products[$i] ?? []; $id = $p['id'] ?? sprintf('P%02d', $i + 1); @endphp
                                    <tr>
                                        <td>
                                            <input type="hidden" name="products[{{ $i }}][id]" value="{{ $id }}">
                                            <span class="badge bg-light text-dark border">{{ $id }}</span>
                                        </td>
                                        <td>
                                            <select name="products[{{ $i }}][category]" class="form-select form-select-sm">
                                                <option value="">—</option>
                                                @foreach(config('mrv.product_benchmarks') as $benchmark)
                                                    <option value="{{ $benchmark }}" @selected(($p['category'] ?? null) === $benchmark)>{{ $benchmark }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="text" name="products[{{ $i }}][technology]" class="form-control form-control-sm" value="{{ $p['technology'] ?? '' }}"></td>
                                        <td class="text-center"><input type="checkbox" class="form-check-input" name="products[{{ $i }}][energy_related]" value="1" @checked(! empty($p['energy_related']))></td>
                                        <td class="text-center"><input type="checkbox" class="form-check-input" name="products[{{ $i }}][process_emissions]" value="1" @checked(! empty($p['process_emissions']))></td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="any" min="0" name="products[{{ $i }}][capacity]" class="form-control" value="{{ $p['capacity'] ?? '' }}">
                                                <input type="text" name="products[{{ $i }}][capacity_unit]" class="form-control" style="max-width:70px;" placeholder="t/yr" value="{{ $p['capacity_unit'] ?? '' }}">
                                            </div>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="any" min="0" name="products[{{ $i }}][actual]" class="form-control" value="{{ $p['actual'] ?? '' }}">
                                                <input type="text" name="products[{{ $i }}][actual_unit]" class="form-control" style="max-width:70px;" placeholder="t" value="{{ $p['actual_unit'] ?? '' }}">
                                            </div>
                                        </td>
                                    </tr>
                                @endfor
                                </tbody>
                            </table>
                        </div>

                        <button class="btn btn-sm btn-success"><i class="fas fa-save me-1"></i>Save products</button>
                        <span class="text-muted small ms-2">Blank rows are discarded. Save to add more.</span>
                    </form>
                </div>
            </div>
        </div>

        {{-- ============ MEASUREMENT / CEMS — 3e2 ============ --}}
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secMeasurement">
                    <i class="fas {{ $done['measurement'] ? 'fa-circle-check text-success' : 'fa-circle text-warning' }} me-2"></i>
                    Measurement &amp; CEMS <span class="text-muted ms-2" style="font-size:.8rem;">Only if you measure the flue gas directly</span>
                </button>
            </h2>
            <div id="secMeasurement" class="accordion-collapse collapse" data-bs-parent="#planAccordion">
                <div class="accordion-body">
                    @if($usesMeasurement && ! $measurementRecorded)
                        <div class="alert alert-warning small">
                            <i class="fas fa-triangle-exclamation me-1"></i>
                            One or more emission sources is <strong>measurement-based</strong>, so EAD expects this sheet
                            completed — including how a year's emissions are derived from concentration and flow.
                        </div>
                    @elseif(! $usesMeasurement)
                        <p class="text-muted small">
                            Nothing here is measurement-based — every emission source is calculated or falls back — so
                            this sheet can stay empty. Complete it if a source is monitored with a continuous emission
                            monitoring system (CEMS).
                        </p>
                    @endif

                    <form method="POST" action="{{ route('mrv.saveReport') }}">
                        @csrf
                        <input type="hidden" name="facility_id" value="{{ $facility->id }}">
                        <input type="hidden" name="year" value="{{ $year }}">
                        <input type="hidden" name="section" value="measurement">

                        <label class="form-label small">Description of the measurement-based approach</label>
                        <textarea name="measurement_approach" rows="4" class="form-control">{{ $plan?->measurement_approach }}</textarea>
                        <div class="form-text" style="font-size:.72rem;">
                            Include the type of instrument(s) used and whether measurements are taken under wet or dry
                            conditions.
                        </div>

                        <label class="form-label small mt-3">
                            How annual emissions are determined from concentration and flue-gas flow
                        </label>
                        <textarea name="measurement_derivation" rows="6" class="form-control">{{ $plan?->measurement_derivation }}</textarea>
                        <div class="form-text" style="font-size:.72rem;">
                            State how often concentration and flow are each determined, and — the part most operators
                            leave out — <strong>what is substituted when no data can be determined</strong>. An analyser
                            offline for a fortnight has to be accounted for, and that is what an assurer checks.
                            One point per line.
                        </div>

                        <label class="form-label small mt-3">Comments <span class="text-muted">(optional)</span></label>
                        <textarea name="measurement_comments" rows="3" class="form-control">{{ $plan?->measurement_comments }}</textarea>
                        <div class="form-text" style="font-size:.72rem;">
                            Biomass estimation method, further QA/QC measures, any deviation from the uncertainty
                            requirements.
                        </div>

                        <button class="btn btn-sm btn-success mt-3"><i class="fas fa-save me-1"></i>Save measurement</button>
                    </form>

                    {{-- 3e2 (b) — measurement points. Their own records, so their own table. --}}
                    <hr class="my-4">

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <div>
                            <h6 class="fw-bold small text-uppercase text-muted mb-0">Measurement points</h6>
                            <div class="text-muted" style="font-size:.75rem;">
                                Where a CEMS sits — a stack, or the pipeline cross-section whose CO₂ flow is measured.
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal"
                                data-bs-target="#instrumentModal" onclick="resetInstrumentForm()">
                            <i class="fas fa-plus me-1"></i>Add point
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:70px;">ID</th>
                                    <th style="width:80px;">Source</th>
                                    <th>Type / location</th>
                                    <th>Range</th>
                                    <th style="width:90px;">Uncertainty</th>
                                    <th style="width:70px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($instruments as $mi)
                                <tr>
                                    <td><span class="badge bg-light text-dark border">{{ $mi->instrument_code }}</span></td>
                                    <td>{{ $mi->emission_source_code ?: '—' }}</td>
                                    <td>
                                        <div>{{ $mi->type ?: '—' }}</div>
                                        @if($mi->location_id)<div class="text-muted" style="font-size:.72rem;">{{ $mi->location_id }}</div>@endif
                                    </td>
                                    <td style="font-size:.8rem;">
                                        @if($mi->range_lower !== null || $mi->range_upper !== null)
                                            {{ rtrim(rtrim(number_format((float) $mi->range_lower, 4, '.', ''), '0'), '.') }}–{{ rtrim(rtrim(number_format((float) $mi->range_upper, 4, '.', ''), '0'), '.') }} {{ $mi->range_unit }}
                                        @else — @endif
                                    </td>
                                    <td>{{ $mi->specified_uncertainty_pct !== null ? rtrim(rtrim(number_format((float) $mi->specified_uncertainty_pct, 3, '.', ''), '0'), '.').'%' : '—' }}</td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-link p-0 me-2 edit-instrument"
                                                data-instrument='@json($mi, JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP)'
                                                data-bs-toggle="modal" data-bs-target="#instrumentModal" title="Edit"><i class="fas fa-pen"></i></button>
                                        <form method="POST" action="{{ route('mrv.deleteInstrument', $mi->id) }}" class="d-inline"
                                              onsubmit="return confirm('Delete measurement point {{ $mi->instrument_code }}?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-link text-danger p-0" title="Delete"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-3" style="font-size:.85rem;">
                                    No measurement points yet.
                                </td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ FALL-BACK — 3f ============ --}}
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secFallback">
                    <i class="fas {{ $done['fallback'] ? 'fa-circle-check text-success' : 'fa-circle text-warning' }} me-2"></i>
                    Fall-back approach <span class="text-muted ms-2" style="font-size:.8rem;">Only if you monitor something without tiers</span>
                </button>
            </h2>
            <div id="secFallback" class="accordion-collapse collapse" data-bs-parent="#planAccordion">
                <div class="accordion-body">
                    {{--
                        The prompt fires off the emission sources, not off a
                        second answer to the same question: if a source declares
                        the fall-back methodology, EAD expects this sheet
                        completed, and an incomplete submission is the failure
                        that costs something.
                    --}}
                    @if($usesFallback && ! $fallbackRecorded)
                        <div class="alert alert-warning small">
                            <i class="fas fa-triangle-exclamation me-1"></i>
                            One or more of your emission sources is monitored with the <strong>fall-back approach</strong>,
                            so EAD expects both boxes below to be completed.
                        </div>
                    @elseif(! $usesFallback && ! $fallbackRecorded)
                        <p class="text-muted small">
                            Nothing here uses the fall-back approach — every emission source is calculation- or
                            measurement-based — so this sheet can stay empty. Complete it only if you monitor a
                            source stream or emission source without using the tier system.
                        </p>
                    @endif

                    <form method="POST" action="{{ route('mrv.saveReport') }}">
                        @csrf
                        <input type="hidden" name="facility_id" value="{{ $facility->id }}">
                        <input type="hidden" name="year" value="{{ $year }}">
                        <input type="hidden" name="section" value="fallback">

                        <label class="form-label small">
                            Description of the monitoring approach, including formulae
                        </label>
                        <textarea name="fallback_description" rows="5" class="form-control">{{ $plan?->fallback_description }}</textarea>
                        <div class="form-text" style="font-size:.72rem;">
                            Cover every source stream or emission source for which no tier approach is used.
                        </div>

                        <label class="form-label small mt-3">Justification for applying it</label>
                        <textarea name="fallback_justification" rows="5" class="form-control">{{ $plan?->fallback_justification }}</textarea>
                        <div class="form-text" style="font-size:.72rem;">
                            You must be able to demonstrate that overall uncertainty for the installation's annual
                            emissions <strong>does not exceed 7.5%</strong>. The competent authority may ask for the
                            full workings behind this.
                        </div>

                        <button class="btn btn-sm btn-success mt-3"><i class="fas fa-save me-1"></i>Save fall-back</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ============ METHANE — 3g ============ --}}
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secMethane">
                    <i class="fas {{ $done['methane'] ? 'fa-circle-check text-success' : 'fa-circle text-muted' }} me-2"></i>
                    Methane <span class="text-muted ms-2" style="font-size:.8rem;">Only if CH₄ occurs at this facility</span>
                </button>
            </h2>
            <div id="secMethane" class="accordion-collapse collapse" data-bs-parent="#planAccordion">
                <div class="accordion-body">
                    <form method="POST" action="{{ route('mrv.saveReport') }}">
                        @csrf
                        <input type="hidden" name="facility_id" value="{{ $facility->id }}">
                        <input type="hidden" name="year" value="{{ $year }}">
                        <input type="hidden" name="section" value="methane">

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="methane_present" value="1" id="methanePresent" @checked($plan?->methane_present)>
                            <label class="form-check-label fw-semibold" for="methanePresent">Methane emissions occur at this facility</label>
                            <div class="text-muted small">Leave this off and sheet 3g is submitted empty, which is the correct answer when there are none.</div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small">Annual volume of methane</label>
                                <input type="text" name="methane[annual_volume]" class="form-control" placeholder="N/A if none known" value="{{ $methane['annual_volume'] ?? '' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Estimated CO₂e from methane</label>
                                <input type="text" name="methane[estimated_co2e]" class="form-control" value="{{ $methane['estimated_co2e'] ?? '' }}">
                                <div class="form-text" style="font-size:.72rem;">
                                    EAD asks for <strong>AR5</strong> 100-year GWP here — not this platform's AR6 default. Enter the
                                    figure you computed, so the basis is one you can defend.
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Source of your estimations (including conversion factors)</label>
                                <textarea name="methane[estimation_source]" rows="2" class="form-control">{{ $methane['estimation_source'] ?? '' }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Key emission points and source stream type</label>
                                <textarea name="methane[key_sources]" rows="2" class="form-control" placeholder="e.g. leak from combustion at…">{{ $methane['key_sources'] ?? '' }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Procedures used to determine or estimate the quantity emitted</label>
                                <textarea name="methane[determination_procedures]" rows="2" class="form-control">{{ $methane['determination_procedures'] ?? '' }}</textarea>
                            </div>
                        </div>

                        <h6 class="fw-bold small text-uppercase text-muted mt-4">Leak detection &amp; repair</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small">Title of procedure</label>
                                <input type="text" name="methane[ldar_title]" class="form-control" value="{{ $methane['ldar_title'] ?? '' }}">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small">Person in charge and contact details</label>
                                <input type="text" name="methane[ldar_person]" class="form-control" value="{{ $methane['ldar_person'] ?? '' }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Brief description, including how often it is carried out</label>
                                <textarea name="methane[ldar_description]" rows="2" class="form-control">{{ $methane['ldar_description'] ?? '' }}</textarea>
                            </div>
                        </div>

                        <button class="btn btn-sm btn-success mt-3"><i class="fas fa-save me-1"></i>Save methane</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ============ VERIFICATION & DATA GAPS — 4h ============ --}}
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secVerification">
                    <i class="fas {{ $done['verification'] ? 'fa-circle-check text-success' : 'fa-circle text-muted' }} me-2"></i>
                    Verification &amp; data gaps <span class="text-muted ms-2" style="font-size:.8rem;">How the data was checked, and where it was missing</span>
                </button>
            </h2>
            <div id="secVerification" class="accordion-collapse collapse" data-bs-parent="#planAccordion">
                <div class="accordion-body">
                    <form method="POST" action="{{ route('mrv.saveReport') }}">
                        @csrf
                        <input type="hidden" name="facility_id" value="{{ $facility->id }}">
                        <input type="hidden" name="year" value="{{ $year }}">
                        <input type="hidden" name="section" value="verification">

                        <label class="form-label small">Verification methodology applied to your source streams and emission sources</label>
                        <textarea name="verification_text" rows="5" class="form-control">{{ $plan?->verification_text }}</textarea>

                        <h6 class="fw-bold small text-uppercase text-muted mt-4">Data gaps</h6>
                        <p class="text-muted small">Where data was missing, say when, why, and what you estimated in its place.</p>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:110px;">Stream / ID</th>
                                        <th style="width:140px;">From</th>
                                        <th style="width:140px;">Until</th>
                                        <th>Description, reasons and method</th>
                                        <th style="width:110px;">Est. tCO₂e</th>
                                        <th style="width:150px;">Source of estimate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @for($i = 0; $i < $gapRows; $i++)
                                    @php $g = $gaps[$i] ?? []; @endphp
                                    <tr>
                                        <td><input type="text" name="data_gaps[{{ $i }}][ref]" class="form-control form-control-sm" value="{{ $g['ref'] ?? '' }}" placeholder="F01"></td>
                                        <td><input type="date" name="data_gaps[{{ $i }}][from]" class="form-control form-control-sm" value="{{ $g['from'] ?? '' }}"></td>
                                        <td><input type="date" name="data_gaps[{{ $i }}][until]" class="form-control form-control-sm" value="{{ $g['until'] ?? '' }}"></td>
                                        <td><input type="text" name="data_gaps[{{ $i }}][description]" class="form-control form-control-sm" value="{{ $g['description'] ?? '' }}"></td>
                                        <td><input type="number" step="any" min="0" name="data_gaps[{{ $i }}][estimated_co2e]" class="form-control form-control-sm" value="{{ $g['estimated_co2e'] ?? '' }}"></td>
                                        <td><input type="text" name="data_gaps[{{ $i }}][source]" class="form-control form-control-sm" value="{{ $g['source'] ?? '' }}"></td>
                                    </tr>
                                @endfor
                                </tbody>
                            </table>
                        </div>

                        <button class="btn btn-sm btn-success"><i class="fas fa-save me-1"></i>Save verification</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ============ MANAGEMENT & QA — 4I ============ --}}
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secManagement">
                    <i class="fas {{ $done['management'] ? 'fa-circle-check text-success' : 'fa-circle text-muted' }} me-2"></i>
                    Management &amp; QA <span class="text-muted ms-2" style="font-size:.8rem;">Who is responsible, and the procedures they follow</span>
                </button>
            </h2>
            <div id="secManagement" class="accordion-collapse collapse" data-bs-parent="#planAccordion">
                <div class="accordion-body">
                    <form method="POST" action="{{ route('mrv.saveReport') }}">
                        @csrf
                        <input type="hidden" name="facility_id" value="{{ $facility->id }}">
                        <input type="hidden" name="year" value="{{ $year }}">
                        <input type="hidden" name="section" value="management">

                        <h6 class="fw-bold small text-uppercase text-muted">Responsibilities</h6>
                        <div class="table-responsive mb-4">
                            <table class="table table-sm align-middle">
                                <thead class="table-light"><tr><th style="width:30%;">Job title / post</th><th>Responsibilities</th></tr></thead>
                                <tbody>
                                @for($i = 0; $i < $respRows; $i++)
                                    @php $r = $management['responsibilities'][$i] ?? []; @endphp
                                    <tr>
                                        <td><input type="text" name="management[responsibilities][{{ $i }}][post]" class="form-control form-control-sm" value="{{ $r['post'] ?? '' }}" placeholder="HSEQ deputy head of unit"></td>
                                        <td><input type="text" name="management[responsibilities][{{ $i }}][duties]" class="form-control form-control-sm" value="{{ $r['duties'] ?? '' }}"></td>
                                    </tr>
                                @endfor
                                </tbody>
                            </table>
                        </div>

                        @foreach([
                            'equipment_qa'    => ['QA of measuring equipment', 'How instruments are calibrated and checked, and what happens when one fails.'],
                            'data_validation' => ['Internal review and data validation', 'How data is checked for completeness and compared against previous years and purchase records.'],
                        ] as $key => [$heading, $hint])
                            @php $proc = $management[$key] ?? []; @endphp
                            <h6 class="fw-bold small text-uppercase text-muted">{{ $heading }}</h6>
                            <p class="text-muted small">{{ $hint }}</p>
                            <div class="row g-2 mb-4">
                                <div class="col-md-4"><label class="form-label small">Title of procedure</label><input type="text" name="management[{{ $key }}][title]" class="form-control form-control-sm" value="{{ $proc['title'] ?? '' }}"></div>
                                <div class="col-md-4"><label class="form-label small">Reference</label><input type="text" name="management[{{ $key }}][reference]" class="form-control form-control-sm" value="{{ $proc['reference'] ?? '' }}"></div>
                                <div class="col-md-4"><label class="form-label small">Diagram reference</label><input type="text" name="management[{{ $key }}][diagram]" class="form-control form-control-sm" value="{{ $proc['diagram'] ?? '' }}"></div>
                                <div class="col-12">
                                    <label class="form-label small">Brief description</label>
                                    <textarea name="management[{{ $key }}][description]" rows="3" class="form-control form-control-sm">{{ $proc['description'] ?? '' }}</textarea>
                                    <div class="form-text" style="font-size:.72rem;">One point per line — the workbook lays these out a line per row.</div>
                                </div>
                                <div class="col-md-6"><label class="form-label small">Responsible post or department</label><input type="text" name="management[{{ $key }}][responsible_post]" class="form-control form-control-sm" value="{{ $proc['responsible_post'] ?? '' }}"></div>
                                <div class="col-md-6"><label class="form-label small">Where records are kept</label><input type="text" name="management[{{ $key }}][records_location]" class="form-control form-control-sm" value="{{ $proc['records_location'] ?? '' }}"></div>
                            </div>
                        @endforeach

                        <label class="form-label small">Any further quality control / assurance detail</label>
                        <textarea name="management[further_details]" rows="3" class="form-control">{{ $management['further_details'] ?? '' }}</textarea>

                        <button class="btn btn-sm btn-success mt-3"><i class="fas fa-save me-1"></i>Save management &amp; QA</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ============ MITIGATION — 4J ============ --}}
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secMitigation">
                    <i class="fas {{ $done['mitigation'] ? 'fa-circle-check text-success' : 'fa-circle text-muted' }} me-2"></i>
                    Mitigation measures <span class="text-muted ms-2" style="font-size:.8rem;">What you are doing to cut emissions — all scopes</span>
                </button>
            </h2>
            <div id="secMitigation" class="accordion-collapse collapse" data-bs-parent="#planAccordion">
                <div class="accordion-body">
                    <form method="POST" action="{{ route('mrv.saveReport') }}">
                        @csrf
                        <input type="hidden" name="facility_id" value="{{ $facility->id }}">
                        <input type="hidden" name="year" value="{{ $year }}">
                        <input type="hidden" name="section" value="mitigation">

                        <p class="text-muted small">
                            One row per measure — do not combine actions. This is the one sheet that covers
                            <strong>Scope 2 and 3</strong> as well as Scope 1.
                        </p>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle" style="min-width:1200px;">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:20%;">Measure</th>
                                        <th>Category</th>
                                        <th style="width:70px;">Scope</th>
                                        <th style="width:80px;">GHG</th>
                                        <th style="width:80px;">Start</th>
                                        <th>Status</th>
                                        <th style="width:110px;">Baseline</th>
                                        <th style="width:100px;">This year</th>
                                        <th style="width:100px;">Expected/yr</th>
                                        <th>Methodology</th>
                                        <th>Verification</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @for($i = 0; $i < $measureRows; $i++)
                                    @php $m = $measures[$i] ?? []; @endphp
                                    <tr>
                                        <td><input type="text" name="mitigation_measures[{{ $i }}][description]" class="form-control form-control-sm" value="{{ $m['description'] ?? '' }}"></td>
                                        <td>
                                            <select name="mitigation_measures[{{ $i }}][category]" class="form-select form-select-sm">
                                                <option value="">—</option>
                                                @foreach(config('mrv.mitigation_categories') as $c)
                                                    <option value="{{ $c }}" @selected(($m['category'] ?? null) === $c)>{{ $c }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="mitigation_measures[{{ $i }}][scope]" class="form-select form-select-sm">
                                                <option value="">—</option>
                                                @foreach([1,2,3] as $sc)
                                                    <option value="{{ $sc }}" @selected((int) ($m['scope'] ?? 0) === $sc)>{{ $sc }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="text" name="mitigation_measures[{{ $i }}][ghg]" class="form-control form-control-sm" value="{{ $m['ghg'] ?? '' }}" placeholder="CO₂"></td>
                                        <td><input type="number" name="mitigation_measures[{{ $i }}][start_year]" class="form-control form-control-sm" min="1990" max="2100" value="{{ $m['start_year'] ?? '' }}"></td>
                                        <td>
                                            <select name="mitigation_measures[{{ $i }}][status]" class="form-select form-select-sm">
                                                <option value="">—</option>
                                                @foreach(config('mrv.mitigation_statuses') as $s)
                                                    <option value="{{ $s }}" @selected(($m['status'] ?? null) === $s)>{{ $s }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="text" name="mitigation_measures[{{ $i }}][baseline]" class="form-control form-control-sm" value="{{ $m['baseline'] ?? '' }}" placeholder="4,200 (2022)"></td>
                                        <td><input type="number" step="any" name="mitigation_measures[{{ $i }}][reporting_year_reduction]" class="form-control form-control-sm" value="{{ $m['reporting_year_reduction'] ?? '' }}"></td>
                                        <td><input type="number" step="any" name="mitigation_measures[{{ $i }}][expected_annual_reduction]" class="form-control form-control-sm" value="{{ $m['expected_annual_reduction'] ?? '' }}"></td>
                                        <td><input type="text" name="mitigation_measures[{{ $i }}][methodology]" class="form-control form-control-sm" value="{{ $m['methodology'] ?? '' }}"></td>
                                        <td>
                                            <select name="mitigation_measures[{{ $i }}][verification]" class="form-select form-select-sm">
                                                <option value="">—</option>
                                                @foreach(config('mrv.mitigation_verification') as $v)
                                                    <option value="{{ $v }}" @selected(($m['verification'] ?? null) === $v)>{{ $v }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                @endfor
                                </tbody>
                            </table>
                        </div>

                        <label class="form-label small mt-2">Anything else relevant <span class="text-muted">(EAD asks for "N/A" if nothing)</span></label>
                        <textarea name="mitigation_additional" rows="2" class="form-control">{{ $mitigation['additional'] ?? '' }}</textarea>

                        <button class="btn btn-sm btn-success mt-3"><i class="fas fa-save me-1"></i>Save mitigation</button>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <div class="card-footer bg-white text-muted small">
        <i class="fas fa-info-circle me-1"></i>
        Every sheet of the EAD workbook is covered here. Sections that do not apply to this facility — measurement,
        fall-back, methane — are submitted empty, which is the correct answer for them.
    </div>
</div>

{{-- Measurement point editor — 3e2 (b) --}}
<div class="modal fade" id="instrumentModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;">
            <form method="POST" action="{{ route('mrv.saveInstrument') }}">
                @csrf
                <input type="hidden" name="facility_id" value="{{ $facility->id }}">
                <input type="hidden" name="year" value="{{ $year }}">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="instrumentModalTitle">Add Measurement Point</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-3"><label class="form-label small">Point ID *</label><input type="text" name="instrument_code" id="mi_code" class="form-control" placeholder="MI1" required></div>
                        <div class="col-md-3"><label class="form-label small">Emission source</label><input type="text" name="emission_source_code" id="mi_source" class="form-control" placeholder="S03" maxlength="20"></div>
                        <div class="col-md-6"><label class="form-label small">Instrument type</label><input type="text" name="type" id="mi_type" class="form-control" placeholder="NDIR CO₂ analyser + ultrasonic flow"></div>

                        <div class="col-12"><label class="form-label small">Location</label><input type="text" name="location_id" id="mi_location" class="form-control" placeholder="Stack 2, sampling plane 18 m"></div>

                        <div class="col-12">
                            <label class="form-label small">Procedures used for this point</label>
                            <textarea name="procedures" id="mi_procedures" rows="3" class="form-control" placeholder="Calculation, data aggregation, validation…"></textarea>
                        </div>
                        <div class="col-md-6"><label class="form-label small">Relevant procedures followed</label><input type="text" name="relevant_procedures" id="mi_relevant_procedures" class="form-control" placeholder="EN 14181"></div>
                        <div class="col-md-6"><label class="form-label small">Relevant source</label><input type="text" name="relevant_source" id="mi_relevant_source" class="form-control"></div>
                    </div>

                    <h6 class="fw-bold small text-uppercase text-muted mt-4">Instrument specification</h6>
                    <div class="row g-3">
                        <div class="col-md-3"><label class="form-label small">Range from</label><input type="number" step="any" name="range_lower" id="mi_range_lower" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label small">Range to</label><input type="number" step="any" name="range_upper" id="mi_range_upper" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label small">Range unit</label><input type="text" name="range_unit" id="mi_range_unit" class="form-control" placeholder="mg/Nm³"></div>
                        <div class="col-md-3"><label class="form-label small">Specified uncertainty %</label><input type="number" step="any" min="0" name="specified_uncertainty_pct" id="mi_uncertainty" class="form-control"></div>

                        <div class="col-md-3"><label class="form-label small">Used range from</label><input type="number" step="any" name="use_range_lower" id="mi_use_lower" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label small">Used range to</label><input type="number" step="any" name="use_range_upper" id="mi_use_upper" class="form-control"></div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-text" style="font-size:.72rem;">
                                The part of the instrument's range actually used in operation — what the achieved
                                uncertainty is assessed against.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save point</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function resetInstrumentForm() {
        document.getElementById('instrumentModalTitle').textContent = 'Add Measurement Point';
        ['mi_code','mi_source','mi_type','mi_location','mi_procedures','mi_relevant_procedures',
         'mi_relevant_source','mi_range_lower','mi_range_upper','mi_range_unit','mi_uncertainty',
         'mi_use_lower','mi_use_upper']
            .forEach(function (id) { var el = document.getElementById(id); if (el) el.value = ''; });
    }

    document.querySelectorAll('.edit-instrument').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var m = JSON.parse(this.dataset.instrument);
            document.getElementById('instrumentModalTitle').textContent = 'Edit Measurement Point ' + (m.instrument_code || '');
            var set = function (id, v) { var el = document.getElementById(id); if (el) el.value = (v === null || v === undefined ? '' : v); };
            set('mi_code', m.instrument_code);
            set('mi_source', m.emission_source_code);
            set('mi_type', m.type);
            set('mi_location', m.location_id);
            set('mi_procedures', m.procedures);
            set('mi_relevant_procedures', m.relevant_procedures);
            set('mi_relevant_source', m.relevant_source);
            set('mi_range_lower', m.range_lower);
            set('mi_range_upper', m.range_upper);
            set('mi_range_unit', m.range_unit);
            set('mi_uncertainty', m.specified_uncertainty_pct);
            set('mi_use_lower', m.use_range_lower);
            set('mi_use_upper', m.use_range_upper);
        });
    });
</script>
@endpush

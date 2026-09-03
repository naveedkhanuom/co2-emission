@extends('layouts.app')

@section('title', 'MRV Workspace')
@section('page-title', 'MRV Workspace')

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="container-fluid mt-4" style="font-family:'Segoe UI',Tahoma,sans-serif;">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-1"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-1"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Intro + facility/year picker --}}
        <div class="card border-0 shadow-sm" style="border-radius:16px;">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span style="width:40px;height:40px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--primary-green),var(--light-green));color:#fff;"><i class="fas fa-file-contract"></i></span>
                    <h4 class="mb-0 fw-bold">Regulated MRV — EAD (UAE) / EU-ETS</h4>
                </div>
                <p class="text-muted mb-3">
                    Facility-level Monitoring, Reporting &amp; Verification for one installation and one calendar year.
                    Uses the EU-ETS calculation approach (Activity × NCV × EF × Oxidation) and is fully optional — it only
                    applies to facilities you enable for MRV.
                </p>

                @if($mrvFacilities->isEmpty())
                    <div class="alert alert-info d-flex justify-content-between align-items-center flex-wrap gap-2 mb-0">
                        <span>
                            <i class="fas fa-info-circle me-1"></i>
                            No facility has MRV mode enabled yet. Enable it on a facility to begin a regulated submission.
                        </span>
                        @if($allFacilities->isEmpty())
                            <a href="{{ route('facilities.index') }}" class="btn btn-sm btn-primary"><i class="fas fa-plus me-1"></i>Create a facility first</a>
                        @else
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#facilitySettingsModal">
                                <i class="fas fa-cog me-1"></i>Enable MRV on a facility
                            </button>
                        @endif
                    </div>
                @else
                    <form method="GET" action="{{ route('mrv.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Facility (MRV-enabled)</label>
                            <select name="facility_id" class="form-select" onchange="this.form.submit()">
                                @foreach($mrvFacilities as $f)
                                    <option value="{{ $f->id }}" @selected($facility && $facility->id === $f->id)>{{ $f->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Reporting Year</label>
                            <select name="year" class="form-select" onchange="this.form.submit()">
                                @foreach($years as $y)
                                    <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#facilitySettingsModal">
                                <i class="fas fa-cog me-1"></i>Facility &amp; MRV settings
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>

        @if($facility)
            {{-- Facility regulatory identifiers --}}
            <div class="row g-3 mt-1">
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm h-100" style="border-radius:14px;">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3"><i class="fas fa-id-card me-1 text-muted"></i>{{ $facility->name }}</h6>
                            <div class="row small">
                                <div class="col-md-6 mb-2"><span class="text-muted">Economic Licence:</span> <strong>{{ $facility->economic_licence_number ?: '—' }}</strong></div>
                                <div class="col-md-6 mb-2"><span class="text-muted">Environmental Permit:</span> <strong>{{ $facility->environmental_permit_no ?: '—' }}</strong></div>
                                <div class="col-md-6 mb-2"><span class="text-muted">Parent Entity:</span> <strong>{{ $facility->parent_entity ?: '—' }}</strong></div>
                                <div class="col-md-6 mb-2"><span class="text-muted">Coordinates:</span> <strong>{{ $facility->coordinates ?: '—' }}</strong></div>
                                <div class="col-md-6 mb-2"><span class="text-muted">Primary Sector:</span> <strong>{{ $facility->primary_sector ?: '—' }}</strong></div>
                                <div class="col-md-6 mb-2"><span class="text-muted">Primary Activity:</span> <strong>{{ $facility->primary_activity ?: '—' }}</strong></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100" style="border-radius:14px;background:#ecfdf5;">
                        <div class="card-body d-flex flex-column justify-content-center">
                            <div class="text-muted small">Total Scope 1 emissions ({{ $year }})</div>
                            <div class="fw-bold fs-3">{{ number_format($total, 2) }}</div>
                            <div class="text-muted" style="font-size:.7rem;">tCO₂e · {{ $streams->count() }} source stream(s)</div>
                            @if(!empty($reconciliationWarnings))
                                <div class="mt-2 text-danger small">
                                    <i class="fas fa-triangle-exclamation me-1"></i>
                                    Recompute mismatch: {{ implode(', ', $reconciliationWarnings) }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{--
                Emission sources — 2c2 table (d). Sat above the streams because
                that is the containment order the workbook uses: a stream
                belongs to a source, and 2c2 column E is that reference.
                Until now these could only be invented by prefill and never
                corrected, so every facility exported as CO₂-only,
                energy-related and calculation-based.
            --}}
            <div class="card border-0 shadow-sm mt-3" style="border-radius:16px;">
                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2" style="border-radius:16px 16px 0 0;">
                    <h5 class="mb-0 fw-bold">Emission Sources</h5>
                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#sourceModal" onclick="resetSourceForm()">
                        <i class="fas fa-plus me-1"></i>Add source
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Code</th>
                                    <th>Name / description</th>
                                    <th>Product</th>
                                    <th>GHGs</th>
                                    <th>Type</th>
                                    <th>Methodology</th>
                                    <th class="text-end">tCO₂e</th>
                                    <th style="width:90px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($sources as $src)
                                <tr>
                                    <td><span class="badge bg-light text-dark border">{{ $src->source_code }}</span></td>
                                    <td>
                                        <div class="fw-semibold">{{ $src->name ?: '—' }}</div>
                                        @if($src->description)<div class="text-muted" style="font-size:.75rem;">{{ Str::limit($src->description, 70) }}</div>@endif
                                    </td>
                                    <td>{{ $src->associated_product ?: '—' }}</td>
                                    <td><span style="font-size:.75rem;">{{ $src->ghg_types ?: '—' }}</span></td>
                                    <td>
                                        @if($src->energy_related)<span class="badge bg-info-subtle text-info-emphasis border" style="font-size:.65rem;">Energy</span>@endif
                                        @if($src->process_emissions)<span class="badge bg-warning-subtle text-warning-emphasis border" style="font-size:.65rem;">Process</span>@endif
                                        @if(! $src->energy_related && ! $src->process_emissions)—@endif
                                    </td>
                                    <td style="font-size:.8rem;">{{ config('mrv.methodologies')[$src->methodology] ?? '—' }}</td>
                                    <td class="text-end fw-semibold">{{ number_format($src->total_co2e, 2) }}</td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-link p-0 me-2 edit-source"
                                            data-source='@json($src, JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP)'
                                            data-bs-toggle="modal" data-bs-target="#sourceModal" title="Edit"><i class="fas fa-pen"></i></button>
                                        <form method="POST" action="{{ route('mrv.deleteSource', $src->id) }}" class="d-inline" onsubmit="return confirm('Delete emission source {{ $src->source_code }}?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-link text-danger p-0" title="Delete"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="8" class="text-center text-muted py-4">
                                    No emission sources yet. <strong>Pre-fill from Scope 1</strong> creates one per distinct source, which you can then correct.
                                </td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white text-muted small">
                    <i class="fas fa-info-circle me-1"></i>
                    Report combustion and process emissions <strong>separately</strong>. Pre-fill assumes every source is
                    CO₂-only, energy-related and calculation-based — correct anything that is not, particularly process
                    emissions such as calcination, which are often the larger half of a facility's inventory.
                </div>
            </div>

            {{-- Source streams --}}
            <div class="card border-0 shadow-sm mt-3" style="border-radius:16px;">
                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2" style="border-radius:16px 16px 0 0;">
                    <h5 class="mb-0 fw-bold">Source Streams &amp; Calculation</h5>
                    <div class="d-flex gap-2">
                        <form method="POST" action="{{ route('mrv.prefill') }}" class="d-inline">
                            @csrf
                            <input type="hidden" name="facility_id" value="{{ $facility->id }}">
                            <input type="hidden" name="year" value="{{ $year }}">
                            <button type="submit" class="btn btn-sm btn-outline-primary" {{ $scope1Available === 0 ? 'disabled' : '' }}>
                                <i class="fas fa-download me-1"></i>Pre-fill from Scope 1 ({{ $scope1Available }})
                            </button>
                        </form>
                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#streamModal" onclick="resetStreamForm()">
                            <i class="fas fa-plus me-1"></i>Add stream
                        </button>
                        <a href="{{ route('mrv.export', ['facility_id' => $facility->id, 'year' => $year]) }}"
                           class="btn btn-sm btn-danger" {{ $streams->isEmpty() ? 'aria-disabled=true' : '' }}>
                            <i class="fas fa-file-excel me-1"></i>Export EAD workbook
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Code</th>
                                    <th>Description / Fuel</th>
                                    <th class="text-end">Activity</th>
                                    <th class="text-end">NCV</th>
                                    <th class="text-end">EF</th>
                                    <th>Tier</th>
                                    <th>Materiality</th>
                                    <th class="text-end">tCO₂e</th>
                                    <th style="width:90px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($streams as $s)
                                <tr>
                                    <td><span class="badge bg-light text-dark border">{{ $s->stream_code }}</span></td>
                                    <td>
                                        <div class="fw-semibold">{{ $s->description ?: '—' }}</div>
                                        <div class="text-muted" style="font-size:.75rem;">{{ $s->fuel_type }}{{ $s->emission_source_code ? ' · '.$s->emission_source_code : '' }}</div>
                                    </td>
                                    <td class="text-end">{{ $s->activity_level ? number_format($s->activity_level, 2).' '.$s->activity_unit : '—' }}</td>
                                    <td class="text-end">{{ $s->net_calorific_value ? rtrim(rtrim(number_format($s->net_calorific_value,4),'0'),'.').' '.$s->ncv_unit : '—' }}</td>
                                    <td class="text-end">{{ $s->emission_factor_value ? rtrim(rtrim(number_format($s->emission_factor_value,4),'0'),'.').' '.$s->ef_unit : '—' }}</td>
                                    <td>{{ $s->tier_level ?: '—' }}</td>
                                    <td>
                                        @php $mlabels=['major'=>'success','minor'=>'warning','de_minimis'=>'secondary'];@endphp
                                        @if($s->materiality)
                                            <span class="badge bg-{{ $mlabels[$s->materiality] ?? 'light' }}">{{ ucfirst(str_replace('_',' ',$s->materiality)) }}</span>
                                        @else — @endif
                                    </td>
                                    <td class="text-end fw-semibold">{{ number_format($s->estimated_co2e, 2) }}</td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-link p-0 me-2 edit-stream"
                                            data-stream='@json($s, JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP)'
                                            data-bs-toggle="modal" data-bs-target="#streamModal" title="Edit"><i class="fas fa-pen"></i></button>
                                        <form method="POST" action="{{ route('mrv.deleteStream', $s->id) }}" class="d-inline" onsubmit="return confirm('Delete stream {{ $s->stream_code }}?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-link text-danger p-0" title="Delete"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="9" class="text-center text-muted py-4">
                                    No source streams yet. Use <strong>Pre-fill from Scope 1</strong> to import your existing data, or add one manually.
                                </td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white text-muted small">
                    <i class="fas fa-info-circle me-1"></i>
                    When you enter both a Net Calorific Value and an Emission Factor, the tCO₂e is recomputed with the
                    EU-ETS formula (Activity × NCV × EF × Oxidation × Conversion). Otherwise the imported Scope 1 value is kept.
                    <strong>Export EAD workbook</strong> fills the official EAD "Deliverable C" template — identifiers,
                    facility description, source streams, tiers and calculation inputs, plus whatever you have completed
                    in <strong>Monitoring Plan Details</strong> below. Measurement/CEMS (3e) and fall-back (3f) are not
                    yet covered and stay blank.
                </div>
            </div>

            @include('reports.mrv._plan')
        @endif
    </div>
</div>

{{-- Facility & MRV settings modal --}}
<div class="modal fade" id="facilitySettingsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;">
            <form method="POST" action="{{ route('mrv.enableFacility') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Facility &amp; MRV Settings</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Facility</label>
                        <select name="facility_id" id="settingsFacilitySelect" class="form-select" required onchange="syncFacilitySettings()">
                            <option value="">— Select a facility —</option>
                            @foreach($allFacilities as $f)
                                <option value="{{ $f->id }}"
                                    data-mrv="{{ $f->mrv_enabled ? 1 : 0 }}"
                                    data-licence="{{ $f->economic_licence_number }}"
                                    data-permit="{{ $f->environmental_permit_no }}"
                                    data-parent="{{ $f->parent_entity }}"
                                    data-coords="{{ $f->coordinates }}"
                                    data-sector="{{ $f->primary_sector }}"
                                    data-activity="{{ $f->primary_activity }}"
                                    @selected($facility && $facility->id === $f->id)>{{ $f->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="mrv_enabled" value="1" id="mrvEnabledSwitch" checked>
                        <label class="form-check-label" for="mrvEnabledSwitch">Enable regulated MRV mode for this facility</label>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label small">Economic Licence Number</label><input type="text" name="economic_licence_number" id="setLicence" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label small">Environmental Permit Number</label><input type="text" name="environmental_permit_no" id="setPermit" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label small">Parent / Group Entity</label><input type="text" name="parent_entity" id="setParent" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label small">Coordinates (lat,lng)</label><input type="text" name="coordinates" id="setCoords" class="form-control" placeholder="24.4539, 54.3773"></div>
                        <div class="col-md-6"><label class="form-label small">Primary Sector</label><input type="text" name="primary_sector" id="setSector" class="form-control" placeholder="Energy"></div>
                        {{--
                            A dropdown, not free text: 2c2's own cell is bound
                            to this list (sheet 4k), so "Iron & steel
                            production" typed by hand fails EAD's validation
                            even though it means the listed option exactly.
                        --}}
                        <div class="col-md-6"><label class="form-label small">Primary Activity</label>
                            <select name="primary_activity" id="setActivity" class="form-select">
                                <option value="">—</option>
                                @foreach(config('mrv.primary_activities') as $activity)
                                    <option value="{{ $activity }}">{{ $activity }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Add / edit source stream modal --}}
@if($facility)
<div class="modal fade" id="streamModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;">
            <form method="POST" action="{{ route('mrv.saveStream') }}">
                @csrf
                <input type="hidden" name="facility_id" value="{{ $facility->id }}">
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="id" id="streamId">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="streamModalTitle">Add Source Stream</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-3"><label class="form-label small">Stream code *</label><input type="text" name="stream_code" id="f_stream_code" class="form-control" placeholder="F01" required></div>
                        <div class="col-md-5"><label class="form-label small">Description</label><input type="text" name="description" id="f_description" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label small">Classification *</label>
                            <select name="classification" id="f_classification" class="form-select">
                                <option value="fuel_combusted">Fuel combusted</option>
                                <option value="other_input">Other input</option>
                                <option value="output">Output</option>
                            </select>
                        </div>
                        <div class="col-md-4"><label class="form-label small">Fuel type</label><input type="text" name="fuel_type" id="f_fuel_type" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label small">Emission source code</label><input type="text" name="emission_source_code" id="f_emission_source_code" class="form-control" placeholder="S01"></div>
                        <div class="col-md-3"><label class="form-label small">Activity level</label><input type="number" step="any" name="activity_level" id="f_activity_level" class="form-control"></div>
                        <div class="col-md-2"><label class="form-label small">Activity unit</label><input type="text" name="activity_unit" id="f_activity_unit" class="form-control" placeholder="t"></div>

                        <div class="col-12"><hr class="my-1"><small class="text-muted fw-semibold">Decomposed calculation (optional — fill both NCV &amp; EF to recompute via EU-ETS formula)</small></div>
                        <div class="col-md-3"><label class="form-label small">NCV value</label><input type="number" step="any" name="net_calorific_value" id="f_ncv" class="form-control" placeholder="42.3"></div>
                        <div class="col-md-3"><label class="form-label small">NCV unit</label><input type="text" name="ncv_unit" id="f_ncv_unit" class="form-control" placeholder="TJ/Gg"></div>
                        <div class="col-md-3"><label class="form-label small">EF value</label><input type="number" step="any" name="emission_factor_value" id="f_ef" class="form-control" placeholder="73.3"></div>
                        <div class="col-md-3"><label class="form-label small">EF unit</label><input type="text" name="ef_unit" id="f_ef_unit" class="form-control" placeholder="tCO2/TJ"></div>
                        <div class="col-md-3"><label class="form-label small">Oxidation factor</label><input type="number" step="any" name="oxidation_factor" id="f_oxidation" class="form-control" placeholder="1"></div>
                        <div class="col-md-3"><label class="form-label small">Conversion factor</label><input type="number" step="any" name="conversion_factor" id="f_conversion" class="form-control" placeholder="1"></div>

                        <div class="col-12"><hr class="my-1"><small class="text-muted fw-semibold">Tier &amp; materiality (EU-ETS / EAD)</small></div>
                        {{-- 2c2 columns J–L: the equipment the stream is burned in. --}}
                        <div class="col-md-5"><label class="form-label small">Combustion device / technology</label><input type="text" name="combustion_device" id="f_combustion_device" class="form-control" placeholder="Gas fired heaters"></div>
                        <div class="col-md-4"><label class="form-label small">Device capacity</label><input type="number" step="any" min="0" name="device_capacity" id="f_device_capacity" class="form-control" placeholder="100"></div>
                        <div class="col-md-3"><label class="form-label small">Capacity unit</label><input type="text" name="device_capacity_unit" id="f_device_capacity_unit" class="form-control" placeholder="MW"></div>

                        <div class="col-md-3"><label class="form-label small">Materiality</label>
                            <select name="materiality" id="f_materiality" class="form-select">
                                <option value="">—</option>
                                <option value="major">Major</option>
                                <option value="minor">Minor</option>
                                <option value="de_minimis">De-minimis</option>
                            </select>
                        </div>
                        <div class="col-md-3"><label class="form-label small">Tier (1–4)</label><input type="number" min="1" max="4" name="tier_level" id="f_tier" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label small">Uncertainty %</label><input type="number" step="any" name="uncertainty_pct" id="f_uncertainty" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label small">Est. tCO₂e (if no NCV/EF)</label><input type="number" step="any" name="estimated_co2e" id="f_estimated_co2e" class="form-control"></div>
                        {{-- 3d1 (b) column G — how the activity figure's accuracy is established. --}}
                        <div class="col-md-3"><label class="form-label small">Source of accuracy</label>
                            <input type="text" name="accuracy_source" id="f_accuracy_source" class="form-control" list="accuracySources" placeholder="Lab. Analysis">
                            <datalist id="accuracySources">
                                <option value="Lab. Analysis"></option>
                                <option value="Supplier invoice"></option>
                                <option value="In-house technical data"></option>
                                <option value="Calibrated meter"></option>
                                <option value="Default value (IPCC)"></option>
                            </datalist>
                        </div>
                        <div class="col-12"><label class="form-label small">Information source</label><input type="text" name="information_source" id="f_information_source" class="form-control" placeholder="IPCC, National Inventory…"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save stream</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Emission source editor — 2c2 table (d) --}}
<div class="modal fade" id="sourceModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;">
            <form method="POST" action="{{ route('mrv.saveSource') }}">
                @csrf
                <input type="hidden" name="facility_id" value="{{ $facility->id }}">
                <input type="hidden" name="year" value="{{ $year }}">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="sourceModalTitle">Add Emission Source</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-3"><label class="form-label small">Source code *</label><input type="text" name="source_code" id="s_source_code" class="form-control" placeholder="S01" required></div>
                        <div class="col-md-9"><label class="form-label small">Name *</label><input type="text" name="name" id="s_name" class="form-control" placeholder="Cement kiln 1" required></div>
                        <div class="col-12"><label class="form-label small">Description</label><textarea name="description" id="s_description" class="form-control" rows="2" maxlength="1000"></textarea></div>

                        <div class="col-md-4"><label class="form-label small">Associated product</label>
                            <input type="text" name="associated_product" id="s_associated_product" class="form-control" placeholder="P01" maxlength="20">
                            <div class="form-text" style="font-size:.7rem;">The product ID whose production causes these emissions. Use a separate source per product.</div>
                        </div>
                        <div class="col-md-4"><label class="form-label small">Greenhouse gases</label>
                            <select name="ghg_types" id="s_ghg_types" class="form-select">
                                <option value="">—</option>
                                @foreach(config('mrv.ghg_types') as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4"><label class="form-label small">Methodology *</label>
                            <select name="methodology" id="s_methodology" class="form-select" required>
                                @foreach(config('mrv.methodologies') as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4"><label class="form-label small">Materiality</label>
                            <select name="materiality" id="s_materiality" class="form-select">
                                <option value="">—</option>
                                <option value="major">Major</option>
                                <option value="minor">Minor</option>
                                <option value="de_minimis">De-minimis</option>
                            </select>
                        </div>
                        <div class="col-md-4"><label class="form-label small">Total tCO₂e</label><input type="number" step="any" min="0" name="total_co2e" id="s_total_co2e" class="form-control"></div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="energy_related" value="1" id="s_energy_related">
                                    <label class="form-check-label small" for="s_energy_related">Energy-related emissions</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="process_emissions" value="1" id="s_process_emissions">
                                    <label class="form-check-label small" for="s_process_emissions">Process emissions</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-light border mt-3 mb-0 small">
                        <i class="fas fa-circle-info me-1 text-muted"></i>
                        EAD asks for combustion and process emissions to be reported separately. A source can be both —
                        a kiln that burns fuel <em>and</em> calcines limestone — in which case tick both boxes.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save source</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
function syncFacilitySettings() {
    const opt = document.getElementById('settingsFacilitySelect').selectedOptions[0];
    if (!opt || !opt.value) return;
    document.getElementById('mrvEnabledSwitch').checked = opt.dataset.mrv === '1';
    document.getElementById('setLicence').value  = opt.dataset.licence || '';
    document.getElementById('setPermit').value   = opt.dataset.permit || '';
    document.getElementById('setParent').value   = opt.dataset.parent || '';
    document.getElementById('setCoords').value   = opt.dataset.coords || '';
    document.getElementById('setSector').value   = opt.dataset.sector || '';
    document.getElementById('setActivity').value = opt.dataset.activity || '';
}
document.addEventListener('DOMContentLoaded', syncFacilitySettings);

function resetStreamForm() {
    document.getElementById('streamModalTitle').textContent = 'Add Source Stream';
    ['streamId','f_stream_code','f_description','f_fuel_type','f_emission_source_code','f_activity_level','f_activity_unit',
     'f_ncv','f_ncv_unit','f_ef','f_ef_unit','f_oxidation','f_conversion','f_tier','f_uncertainty','f_estimated_co2e','f_information_source',
     'f_combustion_device','f_device_capacity','f_device_capacity_unit','f_accuracy_source']
        .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
    document.getElementById('f_classification').value = 'fuel_combusted';
    document.getElementById('f_materiality').value = '';
}

function resetSourceForm() {
    document.getElementById('sourceModalTitle').textContent = 'Add Emission Source';
    ['s_source_code','s_name','s_description','s_associated_product','s_total_co2e']
        .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
    document.getElementById('s_ghg_types').value = 'CO2';
    document.getElementById('s_methodology').value = 'calculation';
    document.getElementById('s_materiality').value = '';
    document.getElementById('s_energy_related').checked = true;
    document.getElementById('s_process_emissions').checked = false;
}

document.querySelectorAll('.edit-source').forEach(btn => {
    btn.addEventListener('click', function () {
        const s = JSON.parse(this.dataset.source);
        document.getElementById('sourceModalTitle').textContent = 'Edit Emission Source ' + (s.source_code || '');
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.value = (v ?? ''); };
        set('s_source_code', s.source_code);
        set('s_name', s.name);
        set('s_description', s.description);
        set('s_associated_product', s.associated_product);
        set('s_total_co2e', s.total_co2e);
        document.getElementById('s_ghg_types').value = s.ghg_types || '';
        document.getElementById('s_methodology').value = s.methodology || 'calculation';
        document.getElementById('s_materiality').value = s.materiality || '';
        // Cast: the model casts these to bool, but JSON from an unsaved edit
        // can still carry 1/0.
        document.getElementById('s_energy_related').checked = !!s.energy_related;
        document.getElementById('s_process_emissions').checked = !!s.process_emissions;
    });
});

document.querySelectorAll('.edit-stream').forEach(btn => {
    btn.addEventListener('click', function () {
        const s = JSON.parse(this.dataset.stream);
        document.getElementById('streamModalTitle').textContent = 'Edit Source Stream ' + (s.stream_code || '');
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.value = (v ?? ''); };
        set('streamId', s.id);
        set('f_stream_code', s.stream_code);
        set('f_description', s.description);
        set('f_fuel_type', s.fuel_type);
        set('f_emission_source_code', s.emission_source_code);
        set('f_activity_level', s.activity_level);
        set('f_activity_unit', s.activity_unit);
        set('f_ncv', s.net_calorific_value);
        set('f_ncv_unit', s.ncv_unit);
        set('f_ef', s.emission_factor_value);
        set('f_ef_unit', s.ef_unit);
        set('f_oxidation', s.oxidation_factor);
        set('f_conversion', s.conversion_factor);
        set('f_tier', s.tier_level);
        set('f_uncertainty', s.uncertainty_pct);
        set('f_estimated_co2e', s.estimated_co2e);
        set('f_information_source', s.information_source);
        set('f_combustion_device', s.combustion_device);
        set('f_device_capacity', s.device_capacity);
        set('f_device_capacity_unit', s.device_capacity_unit);
        set('f_accuracy_source', s.accuracy_source);
        document.getElementById('f_classification').value = s.classification || 'fuel_combusted';
        document.getElementById('f_materiality').value = s.materiality || '';
    });
});
</script>
@endpush
@endsection

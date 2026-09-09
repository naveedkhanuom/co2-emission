<?php

namespace App\Http\Controllers;

use App\Models\EmissionFactor;
use App\Models\EmissionRecord;
use App\Models\Facilities;
use App\Models\MrvEmissionSource;
use App\Models\MrvFacilityReport;
use App\Models\MrvMeasuringInstrument;
use App\Models\MrvSourceStream;
use App\Models\ReportingPeriod;
use App\Services\MRV\EadCapacityExceededException;
use App\Services\MRV\EadTemplateMismatchException;
use App\Services\MRV\EadTemplateMissingException;
use App\Services\MRV\EadWorkbookFiller;
use App\Services\MRV\MrvCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Regulated MRV workspace (EAD / EU-ETS) — facility-level, one reporting year.
 *
 * This is the opt-in layer on top of the global GHG-Protocol engine: it only ever
 * touches MRV-enabled facilities and the dedicated mrv_* tables, so nothing in the
 * normal entry/analytics/disclosure flows is affected. Reuses the reports permission
 * set, like the disclosure reports.
 */
class MrvReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:list-reports|create-report|edit-report|delete-report');
    }

    public function index(Request $request, MrvCalculator $calculator)
    {
        $companyId = (int) (current_company_id() ?? auth()->user()->company_id);

        $mrvFacilities = Facilities::where('mrv_enabled', true)->orderBy('name')->get();
        $allFacilities = Facilities::orderBy('name')->get();

        $year = (int) $request->get('year', date('Y'));
        $years = range((int) date('Y'), (int) date('Y') - 6);

        $facility = null;
        if ($request->filled('facility_id')) {
            $facility = $mrvFacilities->firstWhere('id', (int) $request->get('facility_id'));
        }
        $facility ??= $mrvFacilities->first();

        $report = null;
        $streams = collect();
        $sources = collect();
        $instruments = collect();
        $total = 0.0;
        $reconciliationWarnings = [];
        $capacityWarnings = [];
        $measuredTotal = 0.0;
        $scope1Available = 0;

        if ($facility) {
            $report = MrvFacilityReport::where('facility_id', $facility->id)
                ->where('reporting_year', $year)
                ->first();

            $streams = MrvSourceStream::where('facility_id', $facility->id)
                ->where('reporting_year', $year)
                ->orderBy('stream_code')
                ->get();

            $sources = MrvEmissionSource::where('facility_id', $facility->id)
                ->where('reporting_year', $year)
                ->orderBy('source_code')
                ->get();

            $instruments = MrvMeasuringInstrument::where('facility_id', $facility->id)
                ->where('reporting_year', $year)
                ->orderBy('instrument_code')
                ->get();

            foreach ($streams as $stream) {
                $total += (float) $stream->estimated_co2e;
                if ($stream->hasDecomposedInputs()) {
                    try {
                        $recomputed = $calculator->co2eForStream($stream);
                        if (abs($recomputed - (float) $stream->estimated_co2e) > 0.01) {
                            $reconciliationWarnings[] = $stream->stream_code;
                        }
                    } catch (InvalidArgumentException $e) {
                        $reconciliationWarnings[] = $stream->stream_code.' (unit error)';
                    }
                }
            }

            /*
             * Measured and fall-back sources report a figure the operator
             * typed; nothing recomputes it, because the CEMS numeric path is
             * phase 2 (see MRV_EAD_PLAN.md §7). It still reaches the workbook —
             * 2c2 column G, which 3e1 then reads — so leaving it out of this
             * screen entirely meant a facility monitored by CEMS saw a total of
             * zero beside a submission carrying real numbers.
             *
             * Kept separate from $total rather than added to it. Calculated
             * streams are recomputed here and disagreements surface as
             * reconciliation warnings; these figures have had no such check, and
             * merging the two would present one number as if all of it had been
             * verified. A source using calculation is excluded because its
             * emissions are already counted through its streams.
             */
            $measuredTotal = (float) $sources
                ->where('methodology', '!=', 'calculation')
                ->sum('total_co2e');

            // The export refuses outright when the facility holds more rows
            // than EAD's fixed tables — see EadWorkbookFiller. Saying so here
            // means the operator finds out while they can still act on it,
            // rather than at the moment they need the file.
            $capacityWarnings = EadWorkbookFiller::capacityOverflows(
                $sources->count(),
                $streams->count(),
                EadWorkbookFiller::measurementPoints($instruments)->count(),
                $report,
                EadWorkbookFiller::specifiedInstruments($instruments)->count(),
            );

            $scope1Available = $this->scope1RecordsFor($facility, $year)->count();
        }

        return view('reports.mrv.index', [
            'mrvFacilities' => $mrvFacilities,
            'allFacilities' => $allFacilities,
            'facility' => $facility,
            'year' => $year,
            'years' => $years,
            'report' => $report,
            'streams' => $streams,
            'sources' => $sources,
            'instruments' => $instruments,
            'total' => $total,
            'reconciliationWarnings' => $reconciliationWarnings,
            'capacityWarnings' => $capacityWarnings,
            'measuredTotal' => $measuredTotal,
            'scope1Available' => $scope1Available,
        ]);
    }

    /**
     * Refuse a write that would change a locked year's FIGURES, or null to proceed.
     *
     * Locking a reporting period is this platform's governance action: it
     * declares an inventory final, and seven other write paths — manual entry,
     * the Excel import, OCR, AI extraction, the review queue, the supplier
     * survey converter — all consult it. The MRV layer consulted nothing, so a
     * signed-off year's regulated submission stayed editable indefinitely. That
     * is the same hole as GHG-01 and TEN-13, in the one place where the output
     * goes to a government agency.
     *
     * WHAT THIS DOES NOT BLOCK, AND WHY
     *
     * Only the tables that carry emissions figures: source streams, emission
     * sources, and the prefill that creates both from a locked year's records.
     * The monitoring plan itself — contacts, products, the 3f/3g/4h/4I/4J
     * narratives, measurement points — stays editable after a lock, on purpose.
     * Those sheets describe HOW the facility monitors, not what it emitted, and
     * an operator answering EAD's questions on a submitted plan is doing the
     * thing the workbook exists for. Freezing them would make a regulator's
     * follow-up unanswerable without unlocking the whole inventory.
     *
     * The line is therefore "does this change a number in the submission",
     * not "does this touch MRV".
     */
    private function refuseIfPeriodLocked(int $facilityId, int $year): ?RedirectResponse
    {
        $companyId = (int) (current_company_id() ?? auth()->user()->company_id);

        if (! ReportingPeriod::isYearLocked($year, $companyId)) {
            return null;
        }

        return redirect()
            ->route('mrv.index', ['facility_id' => $facilityId, 'year' => $year])
            ->with('error', "The {$year} reporting period is locked, so its emissions figures cannot be changed. The monitoring plan itself can still be edited. Unlock {$year} first if the figures need to change.");
    }

    /**
     * The facility's active Scope 1 records for a reporting year.
     *
     * Matched on facility_id, because that is the facility's identity — the
     * `facility` column is the name that was typed at the time. Both used to be
     * matched on the name alone, which the rename cascade in the Facilities
     * model keeps working for linked rows, but which cannot tell two facilities
     * apart when a company has given them the same name. Nothing stops it: there
     * is no unique constraint on facilities.name, and "Boiler House" at two
     * sites is an ordinary thing for an operator to have. Both facilities' Scope
     * 1 records would then prefill into one regulated submission.
     *
     * The name is still accepted, but only for records that carry NO facility_id
     * at all — rows written before the linking migration, or by a writer that
     * only ever set the name. Dropping them would quietly shrink an operator's
     * prefill; matching them by name cannot be ambiguous, because a row that
     * names a facility and identifies none belongs to whichever facility bears
     * that name or to nothing.
     *
     * @return \Illuminate\Database\Eloquent\Builder<EmissionRecord>
     */
    private function scope1RecordsFor(Facilities $facility, int $year)
    {
        return EmissionRecord::where('scope', 1)
            ->where('status', 'active')
            ->whereYear('entry_date', $year)
            ->where(function ($query) use ($facility) {
                $query->where('facility_id', $facility->id)
                    ->orWhere(function ($legacy) use ($facility) {
                        $legacy->whereNull('facility_id')
                            ->where('facility', $facility->name);
                    });
            });
    }

    /**
     * Enable MRV mode on a facility and capture its regulatory identifiers.
     */
    public function enableFacility(Request $request)
    {
        $data = $request->validate([
            'facility_id' => 'required|integer',
            'mrv_enabled' => 'nullable|boolean',
            // 2c1(b), which EAD marks mandatory. Long, because it asks for an
            // outline of the site and its activities, not a label.
            'description' => 'nullable|string|max:5000',
            'economic_licence_number' => 'nullable|string|max:255',
            'environmental_permit_no' => 'nullable|string|max:255',
            'parent_entity' => 'nullable|string|max:255',
            'coordinates' => 'nullable|string|max:255',
            /*
             * 2c2 K10 and K12 are both dropdowns bound to fixed lists, so a
             * value typed freehand is one EAD's validation rejects — the same
             * reason saveSource uses Rule::in for its methodology. K10's list
             * had been free text since the MRV layer was written, because it
             * lives inline in the cell rather than on sheet 4k with the others.
             */
            'primary_sector' => ['nullable', Rule::in(config('mrv.primary_sectors'))],
            'primary_activity' => ['nullable', Rule::in(config('mrv.primary_activities'))],

            // C11 asks it in the template's own words, and K11 is where the
            // answer goes. Required only when the sector actually is "Other",
            // which is the only time the workbook asks.
            'primary_sector_other' => 'nullable|string|max:500|required_if:primary_sector,Other',
        ]);

        $facility = Facilities::findOrFail($data['facility_id']);
        $facility->fill([
            'mrv_enabled' => (bool) ($data['mrv_enabled'] ?? true),
            'description' => $data['description'] ?? $facility->description,
            'economic_licence_number' => $data['economic_licence_number'] ?? $facility->economic_licence_number,
            'environmental_permit_no' => $data['environmental_permit_no'] ?? $facility->environmental_permit_no,
            'parent_entity' => $data['parent_entity'] ?? $facility->parent_entity,
            'coordinates' => $data['coordinates'] ?? $facility->coordinates,
            'primary_sector' => $data['primary_sector'] ?? $facility->primary_sector,
            'primary_sector_other' => $data['primary_sector_other'] ?? $facility->primary_sector_other,
            'primary_activity' => $data['primary_activity'] ?? $facility->primary_activity,
        ])->save();

        return redirect()
            ->route('mrv.index', ['facility_id' => $facility->id])
            ->with('success', "MRV mode enabled for {$facility->name}.");
    }

    /**
     * Pre-fill source streams and emission sources from the facility's existing
     * Scope 1 emission records for the year — so users don't re-key data. Each
     * distinct emission source becomes one MRV source + stream, carrying the
     * already-computed co2e_value; the decomposed inputs (NCV/EF/tier) are left
     * blank for the user to add only if they want the full EU-ETS calculation.
     */
    public function prefill(Request $request)
    {
        $data = $request->validate([
            'facility_id' => 'required|integer',
            'year' => 'required|integer',
        ]);

        $companyId = (int) (current_company_id() ?? auth()->user()->company_id);
        $facility = Facilities::findOrFail($data['facility_id']);
        $year = (int) $data['year'];

        // Prefill writes streams and sources, so it is a figures write.
        if ($locked = $this->refuseIfPeriodLocked($facility->id, $year)) {
            return $locked;
        }

        $grouped = $this->scope1RecordsFor($facility, $year)->get()->groupBy('emission_source');

        if ($grouped->isEmpty()) {
            return redirect()
                ->route('mrv.index', ['facility_id' => $facility->id, 'year' => $year])
                ->with('error', "No active Scope 1 records found for {$facility->name} in {$year}.");
        }

        MrvFacilityReport::firstOrCreate(
            ['facility_id' => $facility->id, 'reporting_year' => $year],
            ['company_id' => $companyId, 'status' => 'draft', 'created_by' => auth()->id()]
        );

        // Reuse existing codes where a source/stream for the same name already
        // exists, otherwise allocate the next free code — keeps prefill idempotent.
        $existingSources = MrvEmissionSource::where('facility_id', $facility->id)
            ->where('reporting_year', $year)->get()->keyBy('name');
        $existingStreams = MrvSourceStream::where('facility_id', $facility->id)
            ->where('reporting_year', $year)->get()->keyBy('description');

        $sourceIdx = $existingSources->count();
        $streamIdx = $existingStreams->count();
        $imported = 0;

        foreach ($grouped as $sourceName => $records) {
            $sourceName = (string) $sourceName;
            $sourceCode = $existingSources[$sourceName]->source_code ?? sprintf('S%02d', ++$sourceIdx);

            // The emission source is the physical thing, so it stays whole and
            // carries the source's entire footprint.
            //
            // Split into derived and guessed. Everything below the first block
            // is a GUESS — "CO2 only, energy-related, calculation-based"
            // describes a boiler and misdescribes a cement kiln, where the
            // calcination of limestone is process CO2 and usually the larger
            // half of the inventory. Now that those fields are editable, an
            // updateOrCreate that reasserted them would silently undo the
            // operator's correction on the next prefill — and prefill is
            // idempotent by design, so re-running it is encouraged.
            //
            // So: refresh what is derived from the records, and set the
            // guesses only when creating the row.
            $source = MrvEmissionSource::firstOrNew([
                'facility_id' => $facility->id,
                'reporting_year' => $year,
                'source_code' => $sourceCode,
            ]);

            if (! $source->exists) {
                $source->fill([
                    'ghg_types' => 'CO2',
                    'energy_related' => true,
                    'process_emissions' => false,
                    'methodology' => 'calculation',
                ]);
            }

            $source->fill([
                'company_id' => $companyId,
                'name' => $sourceName,
                'total_co2e' => (float) $records->sum('co2e_value'),
            ])->save();

            // A source stream, though, is one material measured in ONE unit —
            // that is what its activity level and tier mean. This used to sum
            // activity_data across every record under the source name, so a
            // generator logged partly in litres and partly in kWh produced a
            // single meaningless total, exported as the stream's activity with
            // no unit beside it. Splitting by unit is both the correct EAD
            // shape and the only way the figure means anything.
            $byUnit = $records->groupBy(fn ($record) => (string) ($record->activity_unit ?? ''));

            foreach ($byUnit as $unit => $unitRecords) {
                $unit = $unit === '' ? null : (string) $unit;

                // Qualify the description only when the source genuinely spans
                // units, so the ordinary single-unit case reads unchanged and
                // stays matchable against streams created before this.
                $description = $byUnit->count() > 1 && $unit !== null
                    ? "{$sourceName} ({$unit})"
                    : $sourceName;

                $streamCode = $existingStreams[$description]->stream_code ?? sprintf('F%02d', ++$streamIdx);

                // Only when every record agrees. Records under one source can
                // legitimately carry different factors — a fuel switch, a
                // factor revision mid-year — and picking whichever happened to
                // be first would state one of them as the stream's factor.
                // Left blank, the operator supplies it; guessed, nobody knows
                // it was guessed.
                $factors = $unitRecords->pluck('emission_factor')->filter()->unique();

                // Same split as the source above. Tier, uncertainty, NCV, EF
                // unit, combustion device and accuracy source are never named
                // here, so they already survive a re-run; classification,
                // fuel type and the import note are guesses, and now do too.
                $stream = MrvSourceStream::firstOrNew([
                    'facility_id' => $facility->id,
                    'reporting_year' => $year,
                    'stream_code' => $streamCode,
                ]);

                if (! $stream->exists) {
                    $stream->fill([
                        'classification' => 'fuel_combusted',
                        'fuel_type' => $sourceName,
                        'information_source' => 'Imported from Scope 1 records',
                    ]);
                }

                $stream->fill([
                    'company_id' => $companyId,
                    'description' => $description,
                    'emission_source_code' => $sourceCode,
                    'activity_level' => (float) $unitRecords->sum('activity_data') ?: null,
                    'activity_unit' => $unit,
                    'emission_factor_value' => $factors->count() === 1 ? $factors->first() : null,
                    'estimated_co2e' => (float) $unitRecords->sum('co2e_value'),
                ]);

                $this->applyDecomposition($stream, $unitRecords);

                $stream->save();

                $imported++;
            }
        }

        return redirect()
            ->route('mrv.index', ['facility_id' => $facility->id, 'year' => $year])
            ->with('success', "{$imported} source stream(s) pre-filled from Scope 1 records.");
    }

    /**
     * Create or update a single source stream. When the decomposed inputs
     * (NCV + EF) are present, the EU-ETS figure is recomputed server-side and
     * stored as authoritative; otherwise the entered estimate is kept.
     */
    public function saveStream(Request $request, MrvCalculator $calculator)
    {
        $data = $request->validate([
            'facility_id' => 'required|integer',
            'year' => 'required|integer',
            'id' => 'nullable|integer',
            'stream_code' => 'required|string|max:20',
            'description' => 'nullable|string|max:255',
            'emission_source_code' => 'nullable|string|max:20',
            // Rule::in for the same reason as saveSource: the list is config,
            // and a key that ever contains a comma must not silently split.
            'classification' => ['required', Rule::in(array_keys(config('mrv.stream_types')))],
            'fuel_type' => 'nullable|string|max:255',
            'activity_level' => 'nullable|numeric',
            'activity_unit' => 'nullable|string|max:50',

            // 2c2 columns J–L. These have had database columns and an export
            // path since the MRV layer was written, and no form field — so the
            // combustion device and its capacity could not be recorded at all,
            // and the workbook's equipment columns were always blank.
            'combustion_device' => 'nullable|string|max:255',
            'device_capacity' => 'nullable|numeric|min:0',
            'device_capacity_unit' => 'nullable|string|max:50',

            'materiality' => 'nullable|in:major,minor,de_minimis',
            'tier_level' => 'nullable|integer|min:1|max:4',
            'uncertainty_pct' => 'nullable|numeric|min:0',

            // 3d1 (b) column G. Same story: stored, exported, unenterable.
            'accuracy_source' => 'nullable|string|max:255',
            'net_calorific_value' => 'nullable|numeric',
            'ncv_unit' => 'nullable|string|max:50',
            'emission_factor_value' => 'nullable|numeric',
            'ef_unit' => 'nullable|string|max:50',
            'oxidation_factor' => 'nullable|numeric',
            'conversion_factor' => 'nullable|numeric',
            'information_source' => 'nullable|string|max:255',
            'estimated_co2e' => 'nullable|numeric',
        ]);

        $companyId = (int) (current_company_id() ?? auth()->user()->company_id);
        $facility = Facilities::findOrFail($data['facility_id']);
        $year = (int) $data['year'];

        if ($locked = $this->refuseIfPeriodLocked($facility->id, $year)) {
            return $locked;
        }

        $stream = MrvSourceStream::updateOrCreate(
            ['facility_id' => $facility->id, 'reporting_year' => $year, 'stream_code' => $data['stream_code']],
            array_merge($data, ['company_id' => $companyId])
        );

        // Recompute from the EU-ETS formula when the decomposed inputs are present.
        if ($stream->hasDecomposedInputs()) {
            try {
                $stream->estimated_co2e = $calculator->co2eForStream($stream);
                $stream->save();
            } catch (InvalidArgumentException $e) {
                return redirect()
                    ->route('mrv.index', ['facility_id' => $facility->id, 'year' => $year])
                    ->with('error', "Stream {$stream->stream_code}: {$e->getMessage()}");
            }
        }

        return redirect()
            ->route('mrv.index', ['facility_id' => $facility->id, 'year' => $year])
            ->with('success', "Source stream {$stream->stream_code} saved.");
    }

    /**
     * The narrative and tabular sections of the monitoring plan that are not
     * sources or streams: contacts, products, methane, verification and data
     * gaps, management and QA, and mitigation measures.
     *
     * All of these had a column on MrvFacilityReport from the day the MRV layer
     * was written, and no way to put anything in it — so five sheets of the
     * workbook exported blank no matter how complete the operator believed
     * their plan to be.
     *
     * Saved one SECTION at a time rather than as a single form. The workbook
     * takes weeks to complete and is filled by different people — the QA
     * procedures come from the quality manager, the mitigation measures from
     * whoever owns the reduction plan — so a single submit would mean one
     * person's draft overwriting another's, and a validation failure anywhere
     * losing everything.
     */
    public function saveReport(Request $request)
    {
        $base = $request->validate([
            'facility_id' => 'required|integer',
            'year' => 'required|integer',
            'section' => ['required', Rule::in([
                'contacts', 'products', 'measurement', 'fallback', 'methane',
                'verification', 'management', 'mitigation',
            ])],
        ]);

        $facility = Facilities::findOrFail($base['facility_id']);
        $year = (int) $base['year'];

        $report = MrvFacilityReport::firstOrNew([
            'facility_id' => $facility->id,
            'reporting_year' => $year,
        ]);

        if (! $report->exists) {
            $report->fill([
                'company_id' => (int) (current_company_id() ?? auth()->user()->company_id),
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);
        }

        $report->fill($this->{'validate'.ucfirst($base['section']).'Section'}($request))->save();

        return redirect()
            ->route('mrv.index', ['facility_id' => $facility->id, 'year' => $year])
            ->with('success', ucfirst($base['section']).' details saved.');
    }

    /** 2c1 — the two named people EAD contacts about the monitoring plan. */
    private function validateContactsSection(Request $request): array
    {
        $fields = ['title', 'first_name', 'surname', 'job_title', 'organisation', 'telephone', 'email'];

        $rules = [];
        foreach (['primary', 'alternate'] as $which) {
            foreach ($fields as $field) {
                $rules["contacts.{$which}.{$field}"] = $field === 'email'
                    ? 'nullable|email|max:255'
                    : 'nullable|string|max:255';
            }
        }

        return ['contacts' => $request->validate($rules)['contacts'] ?? []];
    }

    /** 2c2 (b) — products whose production causes emissions, P01…P10. */
    private function validateProductsSection(Request $request): array
    {
        $data = $request->validate([
            'products' => 'nullable|array|max:10',
            'products.*.id' => 'nullable|string|max:10',
            'products.*.category' => ['nullable', Rule::in(config('mrv.product_benchmarks'))],
            'products.*.technology' => 'nullable|string|max:500',
            'products.*.energy_related' => 'nullable|boolean',
            'products.*.process_emissions' => 'nullable|boolean',
            'products.*.capacity' => 'nullable|numeric|min:0',
            'products.*.capacity_unit' => 'nullable|string|max:50',
            'products.*.actual' => 'nullable|numeric|min:0',
            'products.*.actual_unit' => 'nullable|string|max:50',
        ]);

        // Rows the operator left entirely blank are not products. Keeping them
        // would export empty rows over the template's P01…P10 scaffolding.
        $products = collect($data['products'] ?? [])
            ->filter(fn ($p) => filled($p['category'] ?? null) || filled($p['technology'] ?? null))
            ->map(fn ($p) => [
                'id' => $p['id'] ?? null,
                'category' => $p['category'] ?? null,
                'technology' => $p['technology'] ?? null,
                'energy_related' => (bool) ($p['energy_related'] ?? false),
                'process_emissions' => (bool) ($p['process_emissions'] ?? false),
                'capacity' => $p['capacity'] ?? null,
                'capacity_unit' => $p['capacity_unit'] ?? null,
                'actual' => $p['actual'] ?? null,
                'actual_unit' => $p['actual_unit'] ?? null,
            ])
            ->values()
            ->all();

        return ['products' => $products];
    }

    /**
     * 3e2 — how the flue gas is measured, and how a year's emissions come out
     * of it.
     *
     * `measurement_derivation` is the field that carries the weight. EAD asks
     * for how annual emissions are determined from concentration AND flue-gas
     * flow, at what frequency each is determined, and — the part operators
     * forget — what is substituted when no data can be determined. A CEMS that
     * was offline for a fortnight has to be accounted for somehow, and the
     * substitution method is what an assurer checks.
     */
    private function validateMeasurementSection(Request $request): array
    {
        $data = $request->validate([
            'measurement_approach' => 'nullable|string|max:5000',
            'measurement_derivation' => 'nullable|string|max:5000',
            'measurement_comments' => 'nullable|string|max:5000',
        ]);

        return [
            'measurement_approach' => $data['measurement_approach'] ?? null,
            'measurement_derivation' => $data['measurement_derivation'] ?? null,
            'measurement_comments' => $data['measurement_comments'] ?? null,
        ];
    }

    /**
     * 3f — monitoring without tiers, and the case for departing from them.
     *
     * The justification carries a hard threshold: the operator must be able to
     * demonstrate that overall uncertainty for the installation's annual
     * emissions stays below 7.5%. That is a substantive claim the competent
     * authority may ask for the workings behind, which is why it is a long free
     * text and not a checkbox.
     */
    private function validateFallbackSection(Request $request): array
    {
        $data = $request->validate([
            'fallback_description' => 'nullable|string|max:5000',
            'fallback_justification' => 'nullable|string|max:5000',
        ]);

        // Spelled out rather than returned as-is, so submitting the form with
        // the boxes emptied CLEARS them. validate() omits absent keys, and
        // fill() on a partial array would silently keep the old text — leaving
        // a justification in the workbook for an approach no longer in use.
        return [
            'fallback_description' => $data['fallback_description'] ?? null,
            'fallback_justification' => $data['fallback_justification'] ?? null,
        ];
    }

    /**
     * 3g — methane, and the 2c2 flag that decides whether the sheet applies.
     *
     * EAD asks for the CO2e conversion on AR5 100-year GWP specifically. That
     * differs from this platform's AR6 default, which is why the figure is
     * captured rather than derived: silently converting on a different basis
     * would put a number in a regulatory return that the operator did not
     * compute and cannot defend.
     */
    private function validateMethaneSection(Request $request): array
    {
        $data = $request->validate([
            'methane_present' => 'nullable|boolean',
            'methane.annual_volume' => 'nullable|string|max:255',
            'methane.estimated_co2e' => 'nullable|string|max:255',
            'methane.estimation_source' => 'nullable|string|max:2000',
            'methane.key_sources' => 'nullable|string|max:2000',
            'methane.determination_procedures' => 'nullable|string|max:2000',
            'methane.ldar_title' => 'nullable|string|max:255',
            'methane.ldar_description' => 'nullable|string|max:2000',
            'methane.ldar_person' => 'nullable|string|max:500',
        ]);

        return [
            'methane_present' => (bool) ($data['methane_present'] ?? false),
            'methane' => $data['methane'] ?? [],
        ];
    }

    /** 4h — verification methodology, and the data gaps table. */
    private function validateVerificationSection(Request $request): array
    {
        $data = $request->validate([
            'verification_text' => 'nullable|string|max:5000',
            'data_gaps' => 'nullable|array|max:10',
            'data_gaps.*.ref' => 'nullable|string|max:50',
            'data_gaps.*.from' => 'nullable|date',
            'data_gaps.*.until' => 'nullable|date|after_or_equal:data_gaps.*.from',
            'data_gaps.*.description' => 'nullable|string|max:2000',
            'data_gaps.*.estimated_co2e' => 'nullable|numeric|min:0',
            'data_gaps.*.source' => 'nullable|string|max:500',
        ]);

        $gaps = collect($data['data_gaps'] ?? [])
            ->filter(fn ($g) => filled($g['ref'] ?? null) || filled($g['description'] ?? null))
            ->values()
            ->all();

        return [
            'verification_text' => $data['verification_text'] ?? null,
            'data_gaps' => $gaps,
        ];
    }

    /** 4I — who is responsible, and the two procedures EAD asks to see. */
    private function validateManagementSection(Request $request): array
    {
        $procedure = fn (string $key) => [
            "management.{$key}.title" => 'nullable|string|max:255',
            "management.{$key}.reference" => 'nullable|string|max:255',
            "management.{$key}.diagram" => 'nullable|string|max:255',
            "management.{$key}.description" => 'nullable|string|max:3000',
            "management.{$key}.responsible_post" => 'nullable|string|max:255',
            "management.{$key}.records_location" => 'nullable|string|max:255',
        ];

        $data = $request->validate(array_merge(
            [
                'management.responsibilities' => 'nullable|array|max:5',
                'management.responsibilities.*.post' => 'nullable|string|max:255',
                'management.responsibilities.*.duties' => 'nullable|string|max:2000',
                'management.further_details' => 'nullable|string|max:3000',
            ],
            $procedure('equipment_qa'),
            $procedure('data_validation'),
        ));

        $management = $data['management'] ?? [];

        $management['responsibilities'] = collect($management['responsibilities'] ?? [])
            ->filter(fn ($r) => filled($r['post'] ?? null) || filled($r['duties'] ?? null))
            ->values()
            ->all();

        return ['management' => $management];
    }

    /** 4J — one row per measure, across all three scopes. */
    private function validateMitigationSection(Request $request): array
    {
        $data = $request->validate([
            'mitigation_measures' => 'nullable|array|max:8',
            'mitigation_measures.*.description' => 'nullable|string|max:1000',
            'mitigation_measures.*.category' => ['nullable', Rule::in(config('mrv.mitigation_categories'))],
            'mitigation_measures.*.scope' => 'nullable|in:1,2,3',
            'mitigation_measures.*.ghg' => 'nullable|string|max:50',
            'mitigation_measures.*.start_year' => 'nullable|integer|min:1990|max:2100',
            'mitigation_measures.*.status' => ['nullable', Rule::in(config('mrv.mitigation_statuses'))],
            'mitigation_measures.*.baseline' => 'nullable|string|max:255',
            'mitigation_measures.*.reporting_year_reduction' => 'nullable|numeric',
            'mitigation_measures.*.expected_annual_reduction' => 'nullable|numeric',
            'mitigation_measures.*.methodology' => 'nullable|string|max:1000',
            'mitigation_measures.*.verification' => ['nullable', Rule::in(config('mrv.mitigation_verification'))],
            'mitigation_additional' => 'nullable|string|max:3000',
        ]);

        // Keyed rather than a bare list, so 4J's free-text addendum shares the
        // column without pretending to be a measure — the same shape
        // `management` uses. A column of its own would be a migration for one
        // textarea.
        return ['mitigation_measures' => [
            'measures' => collect($data['mitigation_measures'] ?? [])
                ->filter(fn ($m) => filled($m['description'] ?? null))
                ->values()
                ->all(),
            'additional' => $data['mitigation_additional'] ?? null,
        ]];
    }

    /**
     * Carry the EU-ETS decomposition from the factor the records were priced
     * with onto the stream — NCV, the energy-basis emission factor, oxidation
     * and conversion.
     *
     * Without this the operator picks a fuel the platform already knows
     * everything about and then types four numbers back in by hand, which is
     * both the slowest part of the workbook and the easiest place to put a
     * digit wrong in a regulatory return.
     *
     * NEVER OVERWRITES. A field already holding a value is left exactly as it
     * is, because under EU-ETS the values worth having are the ones the
     * operator measured: Tier 3 and 4 are defined by using a site-specific
     * calorific value from laboratory analysis rather than a published default.
     * Re-running prefill must not quietly replace a lab result with a
     * catalogue average — that would silently demote the tier the submission
     * claims.
     *
     * Only when every record in the group was priced with the SAME factor. Two
     * different factors under one stream means the stream is not one material,
     * and picking either one would state a decomposition that priced only part
     * of the activity.
     *
     * @param  \Illuminate\Support\Collection<int, EmissionRecord>  $records
     */
    private function applyDecomposition(MrvSourceStream $stream, $records): void
    {
        $factorIds = $records->pluck('emission_factor_id')->filter()->unique();

        if ($factorIds->count() !== 1) {
            return;
        }

        $factor = EmissionFactor::find($factorIds->first());

        // A factor with no calorific value is not decomposed — a distance-based
        // or spend-based row, or a publisher that only ships combined figures.
        // There is nothing here to carry.
        if (! $factor || $factor->net_calorific_value === null || $factor->ef_per_energy === null) {
            return;
        }

        $decomposition = [
            'emission_factor_id' => $factor->id,
            'net_calorific_value' => $factor->net_calorific_value,
            'ncv_unit' => $factor->ncv_unit,
            'oxidation_factor' => $factor->oxidation_factor,
            'conversion_factor' => $factor->conversion_factor,
            'information_source' => $factor->ipcc_reference ?: $stream->information_source,
        ];

        foreach ($decomposition as $column => $value) {
            if ($value !== null && $stream->{$column} === null) {
                $stream->{$column} = $value;
            }
        }

        // The emission factor and its unit are ONE value, and the pair has to be
        // replaced together or not at all.
        //
        // The step above has already put the records' combined factor —
        // tCO2e per litre — into emission_factor_value. Under the EU-ETS
        // formula the EF acts on ENERGY, so what belongs there once an NCV is
        // present is tCO2/TJ. Left as it was, MrvCalculator would multiply an
        // energy quantity by a per-litre factor and return a number that is
        // wrong by the calorific value itself.
        //
        // A missing ef_unit is what marks the existing value as not-an-EU-ETS-EF:
        // MrvCalculator refuses a null unit outright, so nothing that reads
        // these columns was relying on it. An operator who HAS set the unit has
        // established the basis themselves, and is left alone.
        if ($stream->ef_unit === null) {
            $stream->emission_factor_value = $factor->ef_per_energy;
            $stream->ef_unit = $factor->ef_per_energy_unit;
        }
    }

    /**
     * Create or update one emission source — 2c2 table (d).
     *
     * There was no way to edit an emission source at all: prefill invented them
     * from Scope 1 records and nothing could correct what it guessed. Its
     * guesses are `ghg_types = CO2`, `energy_related = true`,
     * `process_emissions = false`, `methodology = calculation`, which describe
     * a boiler and misdescribe a cement kiln — where the calcination of
     * limestone is process CO2 and is typically the larger half of the
     * facility's inventory.
     *
     * The associated product is here too, because 2c2 asks operators to file
     * each source against the product whose production causes it, and to split
     * a source across rows when it serves more than one.
     */
    public function saveSource(Request $request)
    {
        $data = $request->validate([
            'facility_id' => 'required|integer',
            'year' => 'required|integer',
            'source_code' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'associated_product' => 'nullable|string|max:20',

            // Rule::in, not "in:a,b,c" — several of these keys are themselves
            // comma-separated gas lists ("CO2, CH4"), and the string form of
            // the rule splits on commas, so that option silently became two
            // invalid ones and every multi-gas source failed validation.
            'ghg_types' => ['nullable', Rule::in(array_keys(config('mrv.ghg_types')))],

            'energy_related' => 'nullable|boolean',
            'process_emissions' => 'nullable|boolean',
            'methodology' => ['required', Rule::in(array_keys(config('mrv.methodologies')))],
            'materiality' => 'nullable|in:major,minor,de_minimis',
            'total_co2e' => 'nullable|numeric|min:0',

            // 3e1(b). Only meaningful for a measured source, but accepted
            // regardless: an operator who fills them then switches the
            // methodology should not silently lose the values, and the export
            // writes them only where measurement applies.
            'tier_level' => 'nullable|integer|min:1|max:4',
            'uncertainty_pct' => 'nullable|numeric|min:0',
            'emission_stream_type' => 'nullable|string|max:255',
            'accuracy_source' => 'nullable|string|max:255',
        ]);

        $facility = Facilities::findOrFail($data['facility_id']);
        $year = (int) $data['year'];

        if ($locked = $this->refuseIfPeriodLocked($facility->id, $year)) {
            return $locked;
        }

        $source = MrvEmissionSource::updateOrCreate(
            ['facility_id' => $facility->id, 'reporting_year' => $year, 'source_code' => $data['source_code']],
            array_merge($data, [
                'company_id' => (int) (current_company_id() ?? auth()->user()->company_id),
                // Unchecked boxes are absent from the payload, not false, so
                // both flags are resolved explicitly. Without this, clearing
                // "energy related" on an existing source would leave it set.
                'energy_related' => (bool) ($data['energy_related'] ?? false),
                'process_emissions' => (bool) ($data['process_emissions'] ?? false),
            ])
        );

        return redirect()
            ->route('mrv.index', ['facility_id' => $facility->id, 'year' => $year])
            ->with('success', "Emission source {$source->source_code} saved.");
    }

    public function deleteSource(Request $request, $id)
    {
        $source = MrvEmissionSource::findOrFail($id);
        $facilityId = $source->facility_id;
        $year = $source->reporting_year;

        // Removing a source removes its emissions from the submission, so a
        // delete is as much a figures write as a save.
        if ($locked = $this->refuseIfPeriodLocked((int) $facilityId, (int) $year)) {
            return $locked;
        }

        // Streams reference their source by code, so removing a source that
        // still has streams would leave them pointing at nothing — and 2c2
        // column E is exactly that reference.
        $attached = MrvSourceStream::where('facility_id', $facilityId)
            ->where('reporting_year', $year)
            ->where('emission_source_code', $source->source_code)
            ->count();

        if ($attached > 0) {
            return redirect()
                ->route('mrv.index', ['facility_id' => $facilityId, 'year' => $year])
                ->with('error', "Emission source {$source->source_code} still has {$attached} source ".str('stream')->plural($attached).' attached. Reassign or delete those first.');
        }

        $source->delete();

        return redirect()
            ->route('mrv.index', ['facility_id' => $facilityId, 'year' => $year])
            ->with('success', "Emission source {$source->source_code} deleted.");
    }

    /**
     * Create or update a measurement point — 3e2 table (b).
     *
     * A measurement point is where a continuous emission monitoring system
     * (CEMS) sits: the stack or the pipeline cross-section whose CO2 flow is
     * measured directly, rather than calculated from fuel consumed. EAD asks
     * which emission source each one serves and which procedures govern it.
     *
     * The instrument's own specification — range, specified uncertainty, the
     * part of the range actually used — was already modelled here; this is the
     * descriptive half the workbook also wants.
     */
    public function saveInstrument(Request $request)
    {
        $data = $request->validate([
            'facility_id' => 'required|integer',
            'year' => 'required|integer',
            'instrument_code' => 'required|string|max:20',
            'emission_source_code' => 'nullable|string|max:20',
            'source_stream_code' => 'nullable|string|max:20',
            'type' => 'nullable|string|max:255',
            'location_id' => 'nullable|string|max:255',
            'procedures' => 'nullable|string|max:3000',
            'relevant_procedures' => 'nullable|string|max:255',
            'relevant_source' => 'nullable|string|max:255',
            'range_unit' => 'nullable|string|max:50',
            'range_lower' => 'nullable|numeric',
            'range_upper' => 'nullable|numeric|gte:range_lower',
            'use_range_lower' => 'nullable|numeric',
            'use_range_upper' => 'nullable|numeric|gte:use_range_lower',
            'specified_uncertainty_pct' => 'nullable|numeric|min:0',
        ], [
            'range_upper.gte' => 'The top of the instrument range cannot be below the bottom of it.',
            'use_range_upper.gte' => 'The top of the used range cannot be below the bottom of it.',
        ]);

        $facility = Facilities::findOrFail($data['facility_id']);
        $year = (int) $data['year'];

        $instrument = MrvMeasuringInstrument::updateOrCreate(
            [
                'facility_id' => $facility->id,
                'reporting_year' => $year,
                'instrument_code' => $data['instrument_code'],
            ],
            array_merge($data, [
                'company_id' => (int) (current_company_id() ?? auth()->user()->company_id),
            ]),
        );

        return redirect()
            ->route('mrv.index', ['facility_id' => $facility->id, 'year' => $year])
            ->with('success', "Measurement point {$instrument->instrument_code} saved.");
    }

    public function deleteInstrument(Request $request, $id)
    {
        $instrument = MrvMeasuringInstrument::findOrFail($id);
        $facilityId = $instrument->facility_id;
        $year = $instrument->reporting_year;
        $code = $instrument->instrument_code;

        $instrument->delete();

        return redirect()
            ->route('mrv.index', ['facility_id' => $facilityId, 'year' => $year])
            ->with('success', "Measurement point {$code} deleted.");
    }

    /**
     * Export the facility/year as a filled EAD "Deliverable C" workbook (.xlsx),
     * preserving the official template's formatting and formulas.
     */
    public function export(Request $request, EadWorkbookFiller $filler)
    {
        $data = $request->validate([
            'facility_id' => 'required|integer',
            'year' => 'required|integer',
        ]);

        $facility = Facilities::findOrFail($data['facility_id']);
        $year = (int) $data['year'];

        $report = MrvFacilityReport::where('facility_id', $facility->id)
            ->where('reporting_year', $year)
            ->first();

        try {
            $book = $filler->fill($facility, $year, $report);
        } catch (EadTemplateMissingException $e) {
            // A server installation problem, not something the client did. Log
            // the detail — it names a filesystem path, which is for whoever
            // administers the deployment, not for a client's sustainability
            // officer — and say what is actionable.
            Log::error('EAD workbook export failed: '.$e->getMessage());

            return redirect()
                ->route('mrv.index', ['facility_id' => $facility->id, 'year' => $year])
                ->with('error', 'The EAD workbook template is not installed on this server, so the submission could not be generated. Please contact your administrator.');
        } catch (EadTemplateMismatchException $e) {
            // Caught separately because the remedy is the opposite one. This
            // used to share the branch above, so an operator whose server had a
            // NEWER EAD template was told it was not installed — and sent their
            // administrator looking for a file that was sitting right there.
            Log::error('EAD workbook export failed: '.$e->getMessage());

            return redirect()
                ->route('mrv.index', ['facility_id' => $facility->id, 'year' => $year])
                ->with('error', 'The EAD template installed on this server is not the version this export was built for, so filling it could put figures under the wrong headings. The export has stopped. Please ask your administrator to check which template version is installed.');
        } catch (EadCapacityExceededException $e) {
            // The operator's own data, and the operator is who resolves it, so
            // the exception's own message is shown: it names each table, what
            // the facility holds and what the workbook has room for.
            return redirect()
                ->route('mrv.index', ['facility_id' => $facility->id, 'year' => $year])
                ->with('error', $e->getMessage());
        }

        $safeName = preg_replace('/[^A-Za-z0-9_-]+/', '_', $facility->name);
        $filename = "EAD_MRV_{$safeName}_{$year}.xlsx";

        return new StreamedResponse(function () use ($book) {
            (new XlsxWriter($book))->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function deleteStream(Request $request, $id)
    {
        $stream = MrvSourceStream::findOrFail($id);
        $facilityId = $stream->facility_id;
        $year = $stream->reporting_year;

        if ($locked = $this->refuseIfPeriodLocked((int) $facilityId, (int) $year)) {
            return $locked;
        }

        $stream->delete();

        return redirect()
            ->route('mrv.index', ['facility_id' => $facilityId, 'year' => $year])
            ->with('success', 'Source stream deleted.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\EmissionRecord;
use App\Models\Facilities;
use App\Models\MrvEmissionSource;
use App\Models\MrvFacilityReport;
use App\Models\MrvSourceStream;
use App\Services\MRV\EadWorkbookFiller;
use App\Services\MRV\MrvCalculator;
use Illuminate\Http\Request;
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
        $total = 0.0;
        $reconciliationWarnings = [];
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

            foreach ($streams as $stream) {
                $total += (float) $stream->estimated_co2e;
                if ($stream->hasDecomposedInputs()) {
                    try {
                        $recomputed = $calculator->co2eForStream($stream);
                        if (abs($recomputed - (float) $stream->estimated_co2e) > 0.01) {
                            $reconciliationWarnings[] = $stream->stream_code;
                        }
                    } catch (InvalidArgumentException $e) {
                        $reconciliationWarnings[] = $stream->stream_code . ' (unit error)';
                    }
                }
            }

            $scope1Available = EmissionRecord::where('scope', 1)
                ->where('facility', $facility->name)
                ->whereYear('entry_date', $year)
                ->where('status', 'active')
                ->count();
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
            'total' => $total,
            'reconciliationWarnings' => $reconciliationWarnings,
            'scope1Available' => $scope1Available,
        ]);
    }

    /**
     * Enable MRV mode on a facility and capture its regulatory identifiers.
     */
    public function enableFacility(Request $request)
    {
        $data = $request->validate([
            'facility_id' => 'required|integer',
            'mrv_enabled' => 'nullable|boolean',
            'economic_licence_number' => 'nullable|string|max:255',
            'environmental_permit_no' => 'nullable|string|max:255',
            'parent_entity' => 'nullable|string|max:255',
            'coordinates' => 'nullable|string|max:255',
            'primary_sector' => 'nullable|string|max:255',
            'primary_activity' => 'nullable|string|max:255',
        ]);

        $facility = Facilities::findOrFail($data['facility_id']);
        $facility->fill([
            'mrv_enabled' => (bool) ($data['mrv_enabled'] ?? true),
            'economic_licence_number' => $data['economic_licence_number'] ?? $facility->economic_licence_number,
            'environmental_permit_no' => $data['environmental_permit_no'] ?? $facility->environmental_permit_no,
            'parent_entity' => $data['parent_entity'] ?? $facility->parent_entity,
            'coordinates' => $data['coordinates'] ?? $facility->coordinates,
            'primary_sector' => $data['primary_sector'] ?? $facility->primary_sector,
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

        $grouped = EmissionRecord::where('scope', 1)
            ->where('facility', $facility->name)
            ->whereYear('entry_date', $year)
            ->where('status', 'active')
            ->get()
            ->groupBy('emission_source');

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
            $streamCode = $existingStreams[$sourceName]->stream_code ?? sprintf('F%02d', ++$streamIdx);

            $co2e = (float) $records->sum('co2e_value');
            $activity = (float) $records->sum('activity_data');
            $factor = $records->first()->emission_factor;

            MrvEmissionSource::updateOrCreate(
                ['facility_id' => $facility->id, 'reporting_year' => $year, 'source_code' => $sourceCode],
                [
                    'company_id' => $companyId,
                    'name' => $sourceName,
                    'ghg_types' => 'CO2',
                    'energy_related' => true,
                    'process_emissions' => false,
                    'methodology' => 'calculation',
                    'total_co2e' => $co2e,
                ]
            );

            MrvSourceStream::updateOrCreate(
                ['facility_id' => $facility->id, 'reporting_year' => $year, 'stream_code' => $streamCode],
                [
                    'company_id' => $companyId,
                    'description' => $sourceName,
                    'emission_source_code' => $sourceCode,
                    'classification' => 'fuel_combusted',
                    'fuel_type' => $sourceName,
                    'activity_level' => $activity ?: null,
                    'emission_factor_value' => $factor,
                    'estimated_co2e' => $co2e,
                    'information_source' => 'Imported from Scope 1 records',
                ]
            );

            $imported++;
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
            'classification' => 'required|in:fuel_combusted,other_input,output',
            'fuel_type' => 'nullable|string|max:255',
            'activity_level' => 'nullable|numeric',
            'activity_unit' => 'nullable|string|max:50',
            'materiality' => 'nullable|in:major,minor,de_minimis',
            'tier_level' => 'nullable|integer|min:1|max:4',
            'uncertainty_pct' => 'nullable|numeric',
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

        $book = $filler->fill($facility, $year, $report);

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
        $stream->delete();

        return redirect()
            ->route('mrv.index', ['facility_id' => $facilityId, 'year' => $year])
            ->with('success', 'Source stream deleted.');
    }
}

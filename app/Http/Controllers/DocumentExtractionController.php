<?php

namespace App\Http\Controllers;

use App\Models\AiExtraction;
use App\Models\EmissionRecord;
use App\Models\EmissionSource;
use App\Models\Supplier;
use App\Services\AI\DocumentEmissionExtractor;
use App\Services\EmissionEnrichmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Phase 1 "AI Document Extraction".
 *
 * Users upload a document (invoice, bill, spreadsheet, Word, image, PDF); the
 * AI extracts emission-relevant line items which are shown for review. On save,
 * each confirmed line becomes a DRAFT EmissionRecord (status=draft) that flows
 * into the existing Review Data queue for human validation — the AI never
 * auto-approves anything.
 */
class DocumentExtractionController extends Controller
{
    public function __construct(private DocumentEmissionExtractor $extractor)
    {
        $this->middleware('auth');
        // Reuse the emission-record permission — no new permission to seed.
        $this->middleware('permission:create-emission-record', ['only' => ['index', 'extract', 'store']]);
    }

    protected function currentCompanyId()
    {
        if (app()->bound('current_company_id')) {
            return app('current_company_id');
        }
        return auth()->user()?->company_id;
    }

    public function index()
    {
        return view('ai_extract.index', [
            'aiEnabled'  => $this->extractor->enabled(),
            'recent'     => AiExtraction::with('user:id,name')
                ->orderByDesc('id')->limit(10)->get(),
        ]);
    }

    /**
     * Full extraction audit history for the current company.
     */
    public function history()
    {
        return view('ai_extract.history', [
            'extractions' => AiExtraction::with('user:id,name')
                ->orderByDesc('id')->paginate(25),
        ]);
    }

    /**
     * Upload + extract. Stores the file (so it can be attached to the saved
     * drafts) and returns the extracted line items as JSON. Nothing is saved to
     * emission_records here.
     */
    public function extract(Request $request)
    {
        // The AI call (especially for image/PDF documents) can take well over the
        // default 30s PHP limit. Without this, PHP kills the request mid-call —
        // Anthropic still bills for the completed call, but the browser sees a
        // broken response ("Network error"). Give it room to finish reading it.
        @set_time_limit(150);

        $request->validate([
            'document' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,webp,gif,xlsx,xls,csv,docx,txt',
        ]);

        if (!$this->extractor->enabled()) {
            return response()->json([
                'ok'      => false,
                'message' => 'AI is not configured. Ask an administrator to set ANTHROPIC_API_KEY.',
            ], 200);
        }

        $companyId = $this->currentCompanyId();
        $file = $request->file('document');

        // Persist to the public disk under a company-scoped folder so we can
        // attach it as a supporting document when the drafts are saved.
        $folder = 'supporting-documents/' . ($companyId ?: 'unknown') . '/' . now()->format('Y/m');
        $storedPath = $file->storePublicly($folder, 'public');

        try {
            $result = $this->extractor->extract($file);
        } catch (\Throwable $e) {
            Log::error('AI document extraction failed', ['error' => $e->getMessage()]);
            return response()->json([
                'ok'      => false,
                'message' => 'Something went wrong reading that document. Please try again or enter the data manually.',
            ], 200);
        }

        Log::info('AI document extraction', [
            'company_id'     => $companyId,
            'user_id'        => auth()->id(),
            'prompt_version' => DocumentEmissionExtractor::PROMPT_VERSION,
            'ok'             => $result['ok'],
            'items'          => count($result['line_items']),
        ]);

        // Persist an audit record of this run (proposal captured for compliance).
        $extraction = AiExtraction::create([
            'company_id'     => $companyId,
            'created_by'     => auth()->id(),
            'file_name'      => $file->getClientOriginalName(),
            'file_path'      => $result['ok'] ? $storedPath : null,
            'document_type'  => $result['document_type'] ?? null,
            'status'         => $result['ok'] ? 'extracted' : 'failed',
            'items_count'    => count($result['line_items']),
            'currency'       => $result['currency'] ?? null,
            'prompt_version' => DocumentEmissionExtractor::PROMPT_VERSION,
            'result'         => ['line_items' => $result['line_items'], 'message' => $result['message']],
        ]);

        $result['extraction_id'] = $extraction->id;
        $result['document_path'] = $result['ok'] ? $storedPath : null;
        $result['document_name'] = $file->getClientOriginalName();

        return response()->json($result);
    }

    /**
     * Persist the reviewed line items as DRAFT emission records.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'document_path'            => 'nullable|string',
            'extraction_id'            => 'nullable|integer|exists:ai_extractions,id',
            'items'                    => 'required|array|min:1',
            'items.*.entry_date'       => 'nullable|date',
            'items.*.scope'            => 'required|in:1,2,3',
            'items.*.emission_source'  => 'required|string|max:100',
            'items.*.facility'         => 'nullable|string|max:50',
            'items.*.department'       => 'nullable|string|max:100',
            'items.*.activity_data'    => 'nullable|numeric|min:0',
            'items.*.emission_factor'  => 'nullable|numeric|min:0',
            'items.*.co2e_value'       => 'required|numeric|min:0',
            'items.*.confidence_level' => 'nullable|in:low,medium,high',
            'items.*.scope3_category_id' => 'nullable|exists:scope3_categories,id',
            'items.*.emission_factor_id' => 'nullable|integer|exists:emission_factors,id',
            'items.*.supplier_id'      => 'nullable|integer|exists:suppliers,id',
            'items.*.notes'            => 'nullable|string|max:1000',
        ]);

        $companyId = $this->currentCompanyId();

        // Pre-resolve the valid supplier ids for this company once, so a spoofed
        // cross-tenant supplier_id in the payload is simply ignored.
        $validSupplierIds = Supplier::pluck('id')->all();

        // Only attach a document that lives under THIS company's folder.
        $docs = null;
        $path = $validated['document_path'] ?? null;
        if ($path
            && str_starts_with($path, 'supporting-documents/' . $companyId . '/')
            && Storage::disk('public')->exists($path)) {
            $docs = [$path];
        }

        $enricher = app(EmissionEnrichmentService::class);
        $created = 0;

        foreach ($validated['items'] as $item) {
            $scope = (int) $item['scope'];
            $sourceName = trim($item['emission_source']);

            if ($sourceName !== '') {
                EmissionSource::firstOrCreate(
                    ['name' => $sourceName],
                    ['scope' => $scope, 'description' => 'Added from AI document extraction']
                );
            }

            $data = [
                'company_id'       => $companyId,
                'entry_date'       => $item['entry_date'] ?? now()->toDateString(),
                'facility'         => $item['facility'] ?? '',           // empty => Review flags "Missing Facility"
                'scope'            => $scope,
                'emission_source'  => $sourceName,
                'activity_data'    => $item['activity_data'] ?? null,
                'emission_factor'  => $item['emission_factor'] ?? null,
                'co2e_value'       => $item['co2e_value'],
                'confidence_level' => $item['confidence_level'] ?? 'low',
                'department'       => $item['department'] ?? null,
                'data_source'      => 'import',
                'notes'            => $item['notes'] ?? null,
                'created_by'       => auth()->id(),
                'status'           => 'draft',                            // -> Review Data "Pending"
                'data_quality'     => $scope === 3 ? 'estimated' : 'primary',
            ];

            if ($scope === 3) {
                $data['scope3_category_id']  = $item['scope3_category_id'] ?? null;
                $data['calculation_method']  = 'activity-based';
                $data['spend_currency']      = 'USD';

                // Link supplier only if it belongs to this company (tenant guard).
                $supplierId = $item['supplier_id'] ?? null;
                if ($supplierId && in_array((int) $supplierId, $validSupplierIds, true)) {
                    $data['supplier_id'] = (int) $supplierId;
                }
            }

            if ($docs) {
                $data['supporting_documents'] = $docs;
            }

            // Lock the exact library factor when the extraction matched one, so
            // the draft records its provenance (dataset, per-gas split) up front.
            $data = $enricher->enrich($data, [
                'emission_factor_id' => $item['emission_factor_id'] ?? null,
            ]);

            EmissionRecord::create($data);
            $created++;
        }

        // Close out the audit record: mark saved and note how many of the
        // proposed items were actually kept.
        if (!empty($validated['extraction_id'])) {
            $extraction = AiExtraction::find($validated['extraction_id']);
            if ($extraction && $extraction->company_id == $companyId) {
                $extraction->update(['status' => 'saved', 'saved_count' => $created]);
            }
        }

        return response()->json([
            'ok'      => true,
            'message' => $created . ' draft record(s) saved. Find them under Review Data → Pending.',
            'count'   => $created,
        ]);
    }
}

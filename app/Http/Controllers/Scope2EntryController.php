<?php

namespace App\Http\Controllers;

use App\Models\EmissionRecord;
use Illuminate\Http\Request;
use DataTables;

class Scope2EntryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:list-emission-records', ['only' => ['index', 'getData', 'getStats']]);
        $this->middleware('permission:create-emission-record', ['only' => ['index']]);
    }

    /** Source names per subcat for filtering (from config). */
    private function sourceNamesBySubcat(): array
    {
        $cfg = config('scope2_sources', []);
        $electricity = array_column($cfg['electricity'] ?? [], 'name');
        $heating = array_column($cfg['heating'] ?? [], 'name');
        $cooling = array_column($cfg['cooling'] ?? [], 'name');
        return [
            'electricity' => $electricity,
            'heating' => $heating,
            'cooling' => $cooling,
        ];
    }

    /**
     * Scope 2 entry counts by sub-category (for stats cards).
     */
    public function getStats()
    {
        $names = $this->sourceNamesBySubcat();
        $base = EmissionRecord::where('scope', 2);
        $total = (clone $base)->count();
        $electricity = (clone $base)->whereIn('emission_source', $names['electricity'])->count();
        $heating = (clone $base)->whereIn('emission_source', $names['heating'])->count();
        $cooling = (clone $base)->whereIn('emission_source', $names['cooling'])->count();

        return response()->json([
            'total' => $total,
            'electricity' => $electricity,
            'heating' => $heating,
            'cooling' => $cooling,
        ]);
    }

    /**
     * Scope 2 Purchased Energy entry page (separate from Manual Entry).
     */
    public function index()
    {
        $cfg = config('scope2_sources', []);
        $sources = [
            'electricity' => $cfg['electricity'] ?? [],
            'heating' => $cfg['heating'] ?? [],
            'cooling' => $cfg['cooling'] ?? [],
        ];
        $storeUrl = route('emission-records.store');

        return view('scope2_entry.index', [
            'sourcesJson' => json_encode($sources),
            'gridEfJson' => json_encode($cfg['grid_ef'] ?? []),
            'storeUrl' => $storeUrl,
        ]);
    }

    /**
     * DataTables data for Scope 2 entries only.
     */
    public function getData(Request $request)
    {
        $names = $this->sourceNamesBySubcat();
        $records = EmissionRecord::where('scope', 2)
            ->with(['company', 'site', 'user'])
            ->orderBy('entry_date', 'desc')
            ->orderBy('created_at', 'desc');

        $subcat = $request->get('subcat');
        if (in_array($subcat, ['electricity', 'heating', 'cooling'], true)) {
            $records->whereIn('emission_source', $names[$subcat]);
        }

        return DataTables::of($records)
            ->addColumn('subcat', function ($row) use ($names) {
                if (in_array($row->emission_source, $names['electricity'], true)) {
                    return 'electricity';
                }
                if (in_array($row->emission_source, $names['heating'], true)) {
                    return 'heating';
                }
                if (in_array($row->emission_source, $names['cooling'], true)) {
                    return 'cooling';
                }
                return 'other';
            })
            ->addColumn('attachments', function ($row) {
                $docs = $row->supporting_documents ?? [];
                if (!is_array($docs)) {
                    $docs = [];
                }
                if (count($docs) === 0) {
                    return '<span class="text-muted">—</span>';
                }
                $list = [];
                foreach (array_values($docs) as $i => $path) {
                    $list[] = ['idx' => $i, 'name' => basename($path)];
                }
                $urlTemplate = route('emission_records.document', ['emissionRecord' => $row->id, 'index' => ':index']);
                return '<button type="button" class="btn btn-sm btn-outline-primary view-attachments-btn" data-docs="' . e(json_encode($list)) . '" data-url-template="' . e($urlTemplate) . '" title="View attachments"><i class="fas fa-paperclip me-1"></i>View (' . count($list) . ')</button>';
            })
            ->addColumn('actions', function ($row) {
                return '
                    <button type="button" class="btn btn-sm btn-info viewBtn" data-id="' . $row->id . '">View</button>
                    <button type="button" class="btn btn-sm btn-danger deleteBtn" data-id="' . $row->id . '" title="Delete this record">Delete</button>
                ';
            })
            ->editColumn('co2e_value', fn ($row) => number_format($row->co2e_value, 4))
            ->editColumn('entry_date', fn ($row) => $row->entry_date?->format('Y-m-d'))
            ->rawColumns(['attachments', 'actions'])
            ->make(true);
    }
}

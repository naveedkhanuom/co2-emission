<?php

namespace App\Http\Controllers;

use App\Models\EmissionRecord;
use App\Models\Scope3Category;
use Illuminate\Http\Request;
use DataTables;

class Scope3EntryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:list-emission-records', ['only' => ['index', 'getData', 'getStats']]);
        $this->middleware('permission:create-emission-record', ['only' => ['index']]);
    }

    /** Emission source name for each category (matches Scope3Controller convention). */
    private static function emissionSourceName(int $sortOrder, string $name): string
    {
        $names = [
            1 => 'Scope 3 - 1. Purchased Goods & Services',
            2 => 'Scope 3 - 2. Capital Goods',
            3 => 'Scope 3 - 3. Fuel & Energy Related Activities',
            4 => 'Scope 3 - 4. Upstream Transportation & Distribution',
            5 => 'Scope 3 - 5. Waste Generated in Operations',
            6 => 'Scope 3 - 6. Business Travel',
            7 => 'Scope 3 - 7. Employee Commuting',
            8 => 'Scope 3 - 8. Upstream Leased Assets',
            9 => 'Scope 3 - 9. Downstream Transportation & Distribution',
            10 => 'Scope 3 - 10. Processing of Sold Products',
            11 => 'Scope 3 - 11. Use of Sold Products',
            12 => 'Scope 3 - 12. End-of-Life Treatment of Sold Products',
            13 => 'Scope 3 - 13. Downstream Leased Assets',
            14 => 'Scope 3 - 14. Franchises',
            15 => 'Scope 3 - 15. Investments',
        ];
        return $names[$sortOrder] ?? ('Scope 3 - ' . $sortOrder . '. ' . $name);
    }

    /**
     * Scope 3 entry counts: total, upstream (categories 1–8), downstream (9–15).
     */
    public function getStats()
    {
        $upstreamIds = Scope3Category::active()->upstream()->pluck('id')->toArray();
        $downstreamIds = Scope3Category::active()->downstream()->pluck('id')->toArray();

        $base = EmissionRecord::where('scope', 3);
        $total = (clone $base)->count();
        $upstream = (clone $base)->whereIn('scope3_category_id', $upstreamIds)->count();
        $downstream = (clone $base)->whereIn('scope3_category_id', $downstreamIds)->count();

        return response()->json([
            'total' => $total,
            'upstream' => $upstream,
            'downstream' => $downstream,
        ]);
    }

    /**
     * Scope 3 Entry page — same flow as Scope 1/2: category pick, data, review, save.
     */
    public function index()
    {
        $categories = Scope3Category::active()
            ->orderBy('sort_order')
            ->get()
            ->map(function ($cat) {
                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'description' => $cat->description ?? '',
                    'category_type' => $cat->category_type,
                    'sort_order' => $cat->sort_order,
                    'emission_source_name' => self::emissionSourceName($cat->sort_order, $cat->name),
                ];
            });

        $entryForms = config('scope3_entry_forms', []);

        return view('scope3_entry.index', [
            'categoriesJson' => $categories->toJson(),
            'entryFormsJson' => json_encode($entryForms),
            'storeUrl' => route('emission-records.store'),
        ]);
    }

    /**
     * DataTables for Scope 3 entries only.
     */
    public function getData(Request $request)
    {
        $records = EmissionRecord::where('scope', 3)
            ->with(['company', 'site', 'user', 'scope3Category'])
            ->orderBy('entry_date', 'desc')
            ->orderBy('created_at', 'desc');

        $subcat = $request->get('subcat');
        if ($subcat === 'upstream') {
            $ids = Scope3Category::active()->upstream()->pluck('id');
            $records->whereIn('scope3_category_id', $ids);
        } elseif ($subcat === 'downstream') {
            $ids = Scope3Category::active()->downstream()->pluck('id');
            $records->whereIn('scope3_category_id', $ids);
        }

        return DataTables::of($records)
            ->addColumn('subcat', function ($row) {
                return $row->scope3Category->category_type ?? 'other';
            })
            ->addColumn('actions', function ($row) {
                return '
                    <button class="btn btn-sm btn-info viewBtn" data-id="' . $row->id . '">View</button>
                    <button class="btn btn-sm btn-primary editBtn" data-id="' . $row->id . '">Edit</button>
                    <button class="btn btn-sm btn-danger deleteBtn" data-id="' . $row->id . '">Delete</button>
                ';
            })
            ->editColumn('co2e_value', fn ($row) => number_format($row->co2e_value, 4))
            ->editColumn('entry_date', fn ($row) => $row->entry_date?->format('Y-m-d'))
            ->rawColumns(['actions'])
            ->make(true);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\EmissionRecord;
use Illuminate\Http\Request;
use DataTables;

class Scope1EntryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:list-emission-records', ['only' => ['index', 'getData', 'getStats']]);
        $this->middleware('permission:create-emission-record', ['only' => ['index']]);
    }

    /**
     * Scope 1 entry counts by sub-category (for stats cards).
     */
    public function getStats()
    {
        $base = EmissionRecord::where('scope', 1);
        $total = (clone $base)->count();
        $stationary = (clone $base)->where(function ($q) {
            $q->where('emission_source', 'like', '%Biomass%')
                ->orWhere('emission_source', 'like', '%Coal%')
                ->orWhere('emission_source', 'like', '%Diesel (Stationary)%')
                ->orWhere('emission_source', 'like', '%Fuel Oil%')
                ->orWhere('emission_source', 'like', '%Gasoline (Stationary)%')
                ->orWhere('emission_source', 'like', '%Kerosene%')
                ->orWhere('emission_source', 'like', '%LPG / Propane%')
                ->orWhere('emission_source', 'like', '%Natural Gas%')
                ->orWhere('emission_source', 'like', '%Wood%');
        })->count();
        $mobile = (clone $base)->where(function ($q) {
            $q->where('emission_source', 'like', '%Aviation%')
                ->orWhere('emission_source', 'like', '%Fleet%')
                ->orWhere('emission_source', 'like', '%Marine%')
                ->orWhere('emission_source', 'like', '%Off-road%')
                ->orWhere('emission_source', 'like', '%Rail%');
        })->count();
        $fugitive = (clone $base)->where(function ($q) {
            $q->where('emission_source', 'like', '%Refrigerant%')
                ->orWhere('emission_source', 'like', '%Methane%')
                ->orWhere('emission_source', 'like', '%SF6%')
                ->orWhere('emission_source', 'like', '%PFC%')
                ->orWhere('emission_source', 'like', '%N2O%')
                ->orWhere('emission_source', 'like', '%Fire Suppression%');
        })->count();

        return response()->json([
            'total'      => $total,
            'stationary' => $stationary,
            'mobile'     => $mobile,
            'fugitive'   => $fugitive,
        ]);
    }

    /**
     * Scope 1 Direct Emissions entry page (separate from Manual Entry).
     */
    public function index()
    {
        $sources = config('scope1_sources', []);
        $storeUrl = route('emission-records.store');

        return view('scope1_entry.index', [
            'sourcesJson' => json_encode($sources),
            'storeUrl'    => $storeUrl,
        ]);
    }

    /**
     * DataTables data for Scope 1 entries only.
     */
    public function getData(Request $request)
    {
        $records = EmissionRecord::where('scope', 1)
            ->with(['company', 'site', 'user'])
            ->orderBy('entry_date', 'desc')
            ->orderBy('created_at', 'desc');

        $subcat = $request->get('subcat');
        if (in_array($subcat, ['stationary', 'mobile', 'fugitive'], true)) {
            if ($subcat === 'stationary') {
                $records->where(function ($q) {
                    $q->where('emission_source', 'like', '%Biomass%')->orWhere('emission_source', 'like', '%Coal%')
                        ->orWhere('emission_source', 'like', '%Diesel (Stationary)%')->orWhere('emission_source', 'like', '%Fuel Oil%')
                        ->orWhere('emission_source', 'like', '%Gasoline (Stationary)%')->orWhere('emission_source', 'like', '%Kerosene%')
                        ->orWhere('emission_source', 'like', '%LPG / Propane%')->orWhere('emission_source', 'like', '%Natural Gas%')
                        ->orWhere('emission_source', 'like', '%Wood%');
                });
            } elseif ($subcat === 'mobile') {
                $records->where(function ($q) {
                    $q->where('emission_source', 'like', '%Aviation%')->orWhere('emission_source', 'like', '%Fleet%')
                        ->orWhere('emission_source', 'like', '%Marine%')->orWhere('emission_source', 'like', '%Off-road%')
                        ->orWhere('emission_source', 'like', '%Rail%');
                });
            } else {
                $records->where(function ($q) {
                    $q->where('emission_source', 'like', '%Refrigerant%')->orWhere('emission_source', 'like', '%Methane%')
                        ->orWhere('emission_source', 'like', '%SF6%')->orWhere('emission_source', 'like', '%PFC%')
                        ->orWhere('emission_source', 'like', '%N2O%')->orWhere('emission_source', 'like', '%Fire Suppression%');
                });
            }
        }

        return DataTables::of($records)
            ->addColumn('subcat', function ($row) {
                $src = (string) $row->emission_source;
                if (str_contains($src, 'Fleet') || str_contains($src, 'Aviation') || str_contains($src, 'Marine') || str_contains($src, 'Off-road') || str_contains($src, 'Rail')) {
                    return 'mobile';
                }
                if (str_contains($src, 'Refrigerant') || str_contains($src, 'Methane') || str_contains($src, 'SF6') || str_contains($src, 'PFC') || str_contains($src, 'N2O') || str_contains($src, 'Fire Suppression')) {
                    return 'fugitive';
                }
                return 'stationary';
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

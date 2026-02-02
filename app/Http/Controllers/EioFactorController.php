<?php

namespace App\Http\Controllers;

use App\Models\EioFactor;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class EioFactorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:list-eio-factors|create-eio-factor|edit-eio-factor|delete-eio-factor', ['only' => ['index', 'getData', 'getFactor', 'calculate']]);
        $this->middleware('permission:create-eio-factor', ['only' => ['store']]);
        $this->middleware('permission:edit-eio-factor', ['only' => ['update']]);
        $this->middleware('permission:delete-eio-factor', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of EIO factors.
     */
    public function index()
    {
        return view('eio_factors.index');
    }

    /**
     * Get EIO factors data for DataTables.
     */
    public function getData(Request $request)
    {
        $factors = EioFactor::query();

        if ($request->has('country')) {
            $factors->where('country', $request->country);
        }

        if ($request->has('active_only')) {
            $factors->where('is_active', true);
        }

        return DataTables::of($factors)
            ->addColumn('factor_formatted', function ($factor) {
                return number_format($factor->emission_factor, 6) . ' ' . $factor->factor_unit;
            })
            ->addColumn('status_badge', function ($factor) {
                return $factor->is_active 
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-secondary">Inactive</span>';
            })
            ->addColumn('actions', function ($factor) {
                return '
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-info viewBtn" data-id="' . $factor->id . '" title="View">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-primary editBtn" data-id="' . $factor->id . '" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger deleteBtn" data-id="' . $factor->id . '" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    /**
     * Get factor for calculation.
     */
    public function getFactor(Request $request)
    {
        $request->validate([
            'sector_code' => 'required|string',
            'country' => 'nullable|string|size:3',
            'year' => 'nullable|integer',
        ]);

        $country = $request->country ?? 'USA';
        $factor = EioFactor::getFactor($request->sector_code, $country, $request->year);

        if (!$factor) {
            return response()->json([
                'success' => false,
                'message' => 'Factor not found for the specified criteria.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'factor' => $factor
        ]);
    }

    /**
     * Calculate emissions from spend.
     */
    public function calculate(Request $request)
    {
        $request->validate([
            'spend_amount' => 'required|numeric|min:0',
            'sector_code' => 'required|string',
            'country' => 'nullable|string|size:3',
            'currency' => 'nullable|string|size:3',
        ]);

        $country = $request->country ?? 'USA';
        $emissions = EioFactor::calculateFromSpend(
            $request->spend_amount,
            $request->sector_code,
            $country,
            $request->currency ?? 'USD'
        );

        if ($emissions === null) {
            return response()->json([
                'success' => false,
                'message' => 'Could not calculate emissions. Factor not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'spend_amount' => $request->spend_amount,
            'currency' => $request->currency ?? 'USD',
            'emissions_kg_co2e' => $emissions,
            'emissions_t_co2e' => $emissions / 1000,
        ]);
    }

    /**
     * Store a newly created factor.
     */
    public function store(Request $request)
    {
        $request->validate([
            'sector_code' => 'required|string|max:50',
            'sector_name' => 'required|string|max:255',
            'country' => 'required|string|size:3',
            'currency' => 'required|string|size:3',
            'emission_factor' => 'required|numeric|min:0',
            'data_source' => 'nullable|string|max:255',
            'year' => 'nullable|integer|min:2000|max:2100',
        ]);

        $factor = EioFactor::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'EIO factor created successfully',
            'data' => $factor
        ]);
    }

    /**
     * Update factor.
     */
    public function update(Request $request, $id)
    {
        $factor = EioFactor::findOrFail($id);

        $request->validate([
            'sector_code' => 'required|string|max:50',
            'sector_name' => 'required|string|max:255',
            'country' => 'required|string|size:3',
            'currency' => 'required|string|size:3',
            'emission_factor' => 'required|numeric|min:0',
            'data_source' => 'nullable|string|max:255',
            'year' => 'nullable|integer|min:2000|max:2100',
        ]);

        $factor->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'EIO factor updated successfully',
            'data' => $factor
        ]);
    }

    /**
     * Delete factor.
     */
    public function destroy($id)
    {
        $factor = EioFactor::findOrFail($id);
        $factor->delete();

        return response()->json([
            'success' => true,
            'message' => 'EIO factor deleted successfully'
        ]);
    }
}

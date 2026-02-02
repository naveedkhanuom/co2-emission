<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\EmissionRecord;
use Illuminate\Http\Request;
use App\Helpers\CompanyHelper;
use Yajra\DataTables\Facades\DataTables;

class SupplierController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:list-suppliers|create-supplier|edit-supplier|delete-supplier', ['only' => ['index', 'getData', 'list', 'show', 'getEmissionsSummary']]);
        $this->middleware('permission:create-supplier', ['only' => ['store']]);
        $this->middleware('permission:edit-supplier', ['only' => ['update']]);
        $this->middleware('permission:delete-supplier', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of suppliers.
     */
    public function index()
    {
        return view('suppliers.index');
    }

    /**
     * Get suppliers data for DataTables.
     */
    public function getData(Request $request)
    {
        $companyId = CompanyHelper::currentCompanyId();
        
        $suppliers = Supplier::where('company_id', $companyId)
            ->withCount('emissionRecords')
            ->latest();

        return DataTables::of($suppliers)
            ->addColumn('total_emissions', function ($supplier) {
                $currentYear = now()->year;
                $total = $supplier->getTotalEmissions($currentYear);
                return number_format($total, 2) . ' tCO2e';
            })
            ->addColumn('data_quality_badge', function ($supplier) {
                $badges = [
                    'primary' => '<span class="badge bg-success">Primary</span>',
                    'secondary' => '<span class="badge bg-warning">Secondary</span>',
                    'estimated' => '<span class="badge bg-secondary">Estimated</span>',
                ];
                return $badges[$supplier->data_quality] ?? '<span class="badge bg-secondary">Unknown</span>';
            })
            ->addColumn('status_badge', function ($supplier) {
                $badges = [
                    'active' => '<span class="badge bg-success">Active</span>',
                    'inactive' => '<span class="badge bg-danger">Inactive</span>',
                    'pending' => '<span class="badge bg-warning">Pending</span>',
                ];
                return $badges[$supplier->status] ?? '<span class="badge bg-secondary">Unknown</span>';
            })
            ->addColumn('actions', function ($supplier) {
                return '
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-info viewBtn" data-id="' . $supplier->id . '" title="View">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-primary editBtn" data-id="' . $supplier->id . '" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger deleteBtn" data-id="' . $supplier->id . '" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['data_quality_badge', 'status_badge', 'actions'])
            ->make(true);
    }

    /**
     * Simple supplier list endpoint for dropdowns (not DataTables).
     */
    public function list(Request $request)
    {
        $companyId = CompanyHelper::currentCompanyId();

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'No company selected.'
            ], 400);
        }

        $suppliers = Supplier::where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'status']);

        return response()->json([
            'success' => true,
            'data' => $suppliers,
        ]);
    }

    /**
     * Store a newly created supplier.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:100',
            'industry' => 'nullable|string|max:100',
        ]);

        $companyId = CompanyHelper::currentCompanyId();
        
        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'No company selected. Please select a company first.'
            ], 400);
        }

        $supplier = Supplier::create([
            'company_id' => $companyId,
            'name' => $request->name,
            'email' => $request->email,
            'contact_person' => $request->contact_person,
            'phone' => $request->phone,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'country' => $request->country,
            'industry' => $request->industry,
            'status' => $request->status ?? 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Supplier created successfully',
            'data' => $supplier
        ]);
    }

    /**
     * Display the specified supplier.
     */
    public function show($id)
    {
        $supplier = Supplier::with(['emissionRecords.scope3Category', 'emissionRecords.site'])
            ->findOrFail($id);

        // Verify supplier belongs to current company
        $companyId = CompanyHelper::currentCompanyId();
        if ($supplier->company_id != $companyId) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this supplier.'
            ], 403);
        }

        $currentYear = now()->year;
        $totalEmissions = $supplier->getTotalEmissions($currentYear);
        $emissionsByCategory = $supplier->getEmissionsByCategory($currentYear);

        return response()->json([
            'success' => true,
            'supplier' => $supplier,
            'total_emissions' => $totalEmissions,
            'emissions_by_category' => $emissionsByCategory,
        ]);
    }

    /**
     * Update the specified supplier.
     */
    public function update(Request $request, $id)
    {
        $supplier = Supplier::findOrFail($id);

        // Verify supplier belongs to current company
        $companyId = CompanyHelper::currentCompanyId();
        if ($supplier->company_id != $companyId) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this supplier.'
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:100',
            'industry' => 'nullable|string|max:100',
        ]);

        $supplier->update($request->only([
            'name', 'email', 'contact_person', 'phone',
            'address', 'city', 'state', 'country',
            'industry', 'status', 'notes'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Supplier updated successfully',
            'data' => $supplier
        ]);
    }

    /**
     * Remove the specified supplier.
     */
    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);

        // Verify supplier belongs to current company
        $companyId = CompanyHelper::currentCompanyId();
        if ($supplier->company_id != $companyId) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this supplier.'
            ], 403);
        }

        // Check if supplier has emission records
        if ($supplier->emissionRecords()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete supplier with existing emission records. Please remove or reassign emissions first.'
            ], 400);
        }

        $supplier->delete();

        return response()->json([
            'success' => true,
            'message' => 'Supplier deleted successfully'
        ]);
    }

    /**
     * Get supplier emissions summary.
     */
    public function getEmissionsSummary($id)
    {
        $supplier = Supplier::findOrFail($id);

        // Verify supplier belongs to current company
        $companyId = CompanyHelper::currentCompanyId();
        if ($supplier->company_id != $companyId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $currentYear = now()->year;
        $total = $supplier->getTotalEmissions($currentYear);
        $byCategory = $supplier->getEmissionsByCategory($currentYear);

        return response()->json([
            'total' => $total,
            'by_category' => $byCategory,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:list-companies|create-company|edit-company|delete-company', ['only' => ['index', 'show']]);
        $this->middleware('permission:create-company', ['only' => ['store']]);
        $this->middleware('permission:edit-company', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete-company', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        // If JSON request, return companies as JSON
        if ($request->wantsJson() || $request->expectsJson()) {
            $companies = Company::select('id', 'name', 'code', 'industry_type', 'size', 'country', 'is_active')
                ->orderBy('id', 'desc')
                ->get();
            return response()->json($companies);
        }
        
        // Otherwise return view
        $companies = Company::all();
        return view('companies.index', compact('companies'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:companies,code',
            'industry_type' => 'nullable|in:manufacturing,energy,transportation,agriculture,construction,retail,healthcare,education,technology,finance,hospitality,mining,chemical,textile,food_beverage,other',
            'country' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:100',
            'registration_number' => 'nullable|string|max:100',
            'website' => 'nullable|url|max:255',
            'size' => 'nullable|in:small,medium,large,enterprise',
            'employee_count' => 'nullable|integer|min:0',
            'annual_revenue' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'timezone' => 'nullable|string|max:50',
            'fiscal_year_start' => 'nullable|string|max:10',
            'reporting_standards' => 'nullable|array',
            'scopes_enabled' => 'nullable|array',
            'is_active' => 'nullable',
        ]);

        // Handle empty code - set to null if empty string
        if (isset($validated['code']) && (empty($validated['code']) || trim($validated['code']) === '')) {
            $validated['code'] = null;
        }
        
        // Convert is_active to boolean (handle string "true"/"false", 1/0, etc.)
        if (isset($validated['is_active'])) {
            $isActive = $validated['is_active'];
            if (is_string($isActive)) {
                $isActive = strtolower($isActive);
                $validated['is_active'] = in_array($isActive, ['true', '1', 'on', 'yes'], true);
            } elseif (is_numeric($isActive)) {
                $validated['is_active'] = (bool) $isActive;
            } else {
                $validated['is_active'] = (bool) $isActive;
            }
        } else {
            $validated['is_active'] = true;
        }
        
        // Set defaults
        $validated['currency'] = $validated['currency'] ?? 'USD';
        $validated['timezone'] = $validated['timezone'] ?? 'UTC';
        $validated['scopes_enabled'] = $validated['scopes_enabled'] ?? [1, 2, 3];

        $company = Company::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Company created successfully',
            'data' => $company
        ]);
    }

    public function update(Request $request, $id)
    {
        $company = Company::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|nullable|string|max:50|unique:companies,code,' . $id,
            'industry_type' => 'sometimes|nullable|in:manufacturing,energy,transportation,agriculture,construction,retail,healthcare,education,technology,finance,hospitality,mining,chemical,textile,food_beverage,other',
            'country' => 'sometimes|nullable|string|max:255',
            'address' => 'sometimes|nullable|string',
            'contact_person' => 'sometimes|nullable|string|max:255',
            'email' => 'sometimes|nullable|email',
            'phone' => 'sometimes|nullable|string|max:255',
            'tax_id' => 'sometimes|nullable|string|max:100',
            'registration_number' => 'sometimes|nullable|string|max:100',
            'website' => 'sometimes|nullable|url|max:255',
            'size' => 'sometimes|nullable|in:small,medium,large,enterprise',
            'employee_count' => 'sometimes|nullable|integer|min:0',
            'annual_revenue' => 'sometimes|nullable|numeric|min:0',
            'currency' => 'sometimes|nullable|string|size:3',
            'timezone' => 'sometimes|nullable|string|max:50',
            'fiscal_year_start' => 'sometimes|nullable|string|max:10',
            'reporting_standards' => 'sometimes|nullable|array',
            'scopes_enabled' => 'sometimes|nullable|array',
            'is_active' => 'sometimes|nullable',
        ]);

        // Handle empty code - set to null if empty string
        if (isset($validated['code']) && (empty($validated['code']) || trim($validated['code']) === '')) {
            $validated['code'] = null;
        }
        
        // Convert is_active to boolean (handle string "true"/"false", 1/0, etc.)
        if (isset($validated['is_active'])) {
            $isActive = $validated['is_active'];
            if (is_string($isActive)) {
                $isActive = strtolower($isActive);
                $validated['is_active'] = in_array($isActive, ['true', '1', 'on', 'yes'], true);
            } elseif (is_numeric($isActive)) {
                $validated['is_active'] = (bool) $isActive;
            } else {
                $validated['is_active'] = (bool) $isActive;
            }
        }

        $company->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Company updated successfully',
            'data' => $company->fresh()
        ]);
    }

    public function show($id)
    {
        $company = Company::findOrFail($id);
        return response()->json($company);
    }

    public function edit($id)
    {
        $company = Company::findOrFail($id);
        return response()->json($company);
    }

    public function destroy($id)
    {
        $company = Company::findOrFail($id);
        $company->delete();

        return response()->json([
            'success' => true,
            'message' => 'Company deleted successfully'
        ]);
    }
}

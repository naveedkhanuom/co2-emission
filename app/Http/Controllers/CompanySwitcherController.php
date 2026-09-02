<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanySwitcherController extends Controller
{
    /**
     * Switch to a different company.
     */
    public function switch(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $companyId = $request->input('company_id');

        // Check if user can access this company
        if (! Auth::user()->canAccessCompany($companyId)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this company',
            ], 403);
        }

        $company = Company::findOrFail($companyId);

        if (! $company->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Company is inactive',
            ], 403);
        }

        // Set company in session
        $request->session()->put('current_company_id', $companyId);

        // ...and on the user, so it outlives the session. logout() flushes the
        // session, so without this the choice was gone at the next sign-in —
        // and for an account owner, who has no company_id to fall back to,
        // nothing at all was selected.
        Auth::user()->rememberCompany($companyId);

        // Set company context
        app()->instance('current_company', $company);
        app()->instance('current_company_id', $companyId);

        return response()->json([
            'success' => true,
            'message' => 'Company switched successfully',
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'code' => $company->code,
            ],
        ]);
    }

    /**
     * Get list of companies user can access.
     */
    public function getAccessibleCompanies()
    {
        try {
            $user = Auth::user();

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }

            // Get accessible companies query builder
            $companiesQuery = $user->accessibleCompanies();

            // Filter active companies first
            $companiesQuery->where('is_active', true);

            // Check if user has any accessible companies
            $companyCount = (clone $companiesQuery)->count();

            // Debug logging (remove in production if needed)
            \Log::debug('Company Access Debug', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'company_access' => $user->company_access,
                'is_account_owner' => $user->is_account_owner,
                'accessible_count' => $companyCount,
            ]);

            // If user has no companies assigned and is not super admin, try to assign first active company
            if ($companyCount === 0 && ! $user->is_account_owner) {
                $firstCompany = Company::where('is_active', true)->first();
                if ($firstCompany) {
                    // Auto-assign user to first company if they have none
                    $user->company_id = $firstCompany->id;
                    $user->save();

                    // Refresh user to get updated company_id
                    $user->refresh();

                    // Rebuild query with new company_id
                    $companiesQuery = $user->accessibleCompanies()->where('is_active', true);
                }
            }

            // Select only needed fields and get companies
            $companies = $companiesQuery
                ->select('id', 'name', 'code', 'industry_type')
                ->get()
                ->map(function ($company) {
                    return [
                        'id' => $company->id,
                        'name' => $company->name,
                        'code' => $company->code,
                        'industry_type' => $company->industry_type,
                    ];
                })
                ->values();

            $currentCompanyId = session('current_company_id') ?? $user->company_id ?? ($companies->isNotEmpty() ? $companies->first()['id'] : null);

            // If no current company is set but user has companies, set the first one
            if (! $currentCompanyId && $companies->isNotEmpty()) {
                $currentCompanyId = $companies->first()['id'];
                session(['current_company_id' => $currentCompanyId]);

                // Set in application context
                $company = Company::find($currentCompanyId);
                if ($company) {
                    app()->instance('current_company', $company);
                    app()->instance('current_company_id', $currentCompanyId);
                }
            }

            return response()->json([
                'success' => true,
                'companies' => $companies,
                'current_company_id' => $currentCompanyId,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error loading accessible companies: '.$e->getMessage());
            \Log::error('Stack trace: '.$e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Error loading companies: '.$e->getMessage(),
            ], 500);
        }
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Company;

class EnsureCompanyAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Get company ID from route parameter or request
        $companyId = $request->route('company') 
            ?? $request->input('company_id') 
            ?? $request->query('company_id')
            ?? session('current_company_id');

        if ($companyId) {
            $company = Company::find($companyId);
            
            if (!$company) {
                abort(404, 'Company not found');
            }

            if (!$company->is_active) {
                abort(403, 'Company is inactive');
            }

            // Check if user can access this company
            if (!Auth::user()->canAccessCompany($companyId)) {
                abort(403, 'You do not have access to this company');
            }

            // Set company context
            app()->instance('current_company', $company);
            app()->instance('current_company_id', $companyId);
            $request->session()->put('current_company_id', $companyId);
        }

        return $next($request);
    }
}

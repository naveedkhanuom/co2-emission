<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Models\Company;

class SetCompanyConnection
{
    public function handle($request, Closure $next)
    {
        // Skip for public routes
        if ($request->is('login', 'register', 'password/*')) {
            return $next($request);
        }

        // Get company from session, query parameter, or user's default company
        $companyId = $request->session()->get('current_company_id');
        
        if (!$companyId && Auth::check()) {
            $companyId = Auth::user()->company_id;
        }

        // If company ID is provided in query, validate and set it
        if ($request->has('company_id')) {
            $requestedCompanyId = $request->query('company_id');
            
            if (Auth::check() && Auth::user()->canAccessCompany($requestedCompanyId)) {
                $companyId = $requestedCompanyId;
                $request->session()->put('current_company_id', $companyId);
            }
        }

        // Set company context for the application
        if ($companyId) {
            $company = Company::find($companyId);
            if ($company && $company->is_active) {
                app()->instance('current_company', $company);
                app()->instance('current_company_id', $companyId);
            }
        }

        return $next($request);
    }
}

<?php

namespace App\Helpers;

use App\Models\Company;
use Illuminate\Support\Facades\Auth;

class CompanyHelper
{
    /**
     * Get the current company from context.
     */
    public static function currentCompany()
    {
        if (app()->bound('current_company')) {
            return app('current_company');
        }
        
        $companyId = self::currentCompanyId();
        if ($companyId) {
            return Company::find($companyId);
        }
        
        return null;
    }

    /**
     * Get the current company ID from context.
     */
    public static function currentCompanyId()
    {
        if (app()->bound('current_company_id')) {
            return app('current_company_id');
        }
        
        if (session()->has('current_company_id')) {
            return session('current_company_id');
        }
        
        if (Auth::check() && Auth::user()->company_id) {
            return Auth::user()->company_id;
        }
        
        return null;
    }

    /**
     * Check if user can access a company.
     */
    public static function canAccessCompany($companyId)
    {
        if (!Auth::check()) {
            return false;
        }
        
        return Auth::user()->canAccessCompany($companyId);
    }

    /**
     * Get accessible companies for current user.
     */
    public static function accessibleCompanies()
    {
        if (!Auth::check()) {
            return collect([]);
        }
        
        return Auth::user()->accessibleCompanies();
    }
}


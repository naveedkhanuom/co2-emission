<?php

use App\Models\Facilities as Facility;
use App\Models\Department;

if (!function_exists('facilities')) {
    function facilities($all = true)
    {
        $query = Facility::query();
        
        // Automatically scope to current company if available
        $companyId = current_company_id();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        
        if ($all) {
            return $query->get();
        }
        return $query; // allows further chaining
    }
}


if (!function_exists('departments')) {
    function departments($facilityId = null)
    {
        $query = Department::query();
        
        // Automatically scope to current company if available
        $companyId = current_company_id();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($facilityId) {
            $query->where('facility_id', $facilityId);
        }

        return $query->get();
    }
}

if (!function_exists('current_company')) {
    /**
     * Get the current company from context.
     */
    function current_company()
    {
        return \App\Helpers\CompanyHelper::currentCompany();
    }
}

if (!function_exists('current_company_id')) {
    /**
     * Get the current company ID from context.
     */
    function current_company_id()
    {
        return \App\Helpers\CompanyHelper::currentCompanyId();
    }
}

if (!function_exists('sites')) {
    /**
     * Get sites for the current company.
     */
    function sites($all = true)
    {
        $query = \App\Models\Site::query();
        
        // Automatically scope to current company if available
        $companyId = current_company_id();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        
        if ($all) {
            return $query->orderBy('name')->get();
        }
        return $query->orderBy('name'); // allows further chaining
    }
}

if (!function_exists('scope3_categories')) {
    /**
     * Get active Scope 3 categories.
     */
    function scope3_categories()
    {
        return \App\Models\Scope3Category::active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}

if (!function_exists('suppliers')) {
    /**
     * Get suppliers for the current company.
     */
    function suppliers($all = true)
    {
        $query = \App\Models\Supplier::query();
        
        // Automatically scope to current company if available
        $companyId = current_company_id();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        
        if ($all) {
            return $query->orderBy('name')->get();
        }
        return $query->orderBy('name'); // allows further chaining
    }
}

if (!function_exists('demo_route_restricted')) {
    /**
     * Check if a route is restricted in demo mode (for sidebar / UI).
     */
    function demo_route_restricted(string $routeName): bool
    {
        if (! config('demo.enabled', false)) {
            return false;
        }
        $restricted = config('demo.restricted_routes', []);
        foreach ($restricted as $pattern) {
            if (str_ends_with($pattern, '.')) {
                if (str_starts_with($routeName, $pattern)) {
                    return true;
                }
            } else {
                if ($routeName === $pattern) {
                    return true;
                }
            }
        }
        return false;
    }
}
<?php

use App\Models\Department;
use App\Models\Facilities as Facility;

if (! function_exists('facilities')) {
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

if (! function_exists('departments')) {
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

if (! function_exists('current_company')) {
    /**
     * Get the current company from context.
     */
    function current_company()
    {
        return \App\Helpers\CompanyHelper::currentCompany();
    }
}

if (! function_exists('stored_file_url')) {
    /**
     * URL for a file on the public disk, correct inside a tenant.
     *
     * Storage::url() returns /storage/..., which the public/storage symlink
     * resolves to the CENTRAL storage directory. A client's uploads live in
     * storage/tenant{id}/app/public, where that symlink cannot reach — so
     * inside a tenant the URL has to go through stancl's asset route instead.
     *
     * That route reads storage_path("app/public/{$path}"), which is the
     * public disk's own root, so the stored path is passed through unchanged.
     *
     * A value that is already a full URL is returned as-is, for logos held
     * on a CDN rather than uploaded.
     */
    function stored_file_url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        if (function_exists('tenancy') && tenancy()->initialized) {
            return tenant_asset($path);
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($path);
    }
}

if (! function_exists('app_logo_url')) {
    /**
     * Resolve the global app logo (General Settings) to a URL. An uploaded logo
     * is a public-disk path; a full URL is used as-is. Returns $default when no
     * app logo has been set. Used on the login screen and as the sidebar fallback.
     */
    function app_logo_url($default = null)
    {
        try {
            $logo = \App\Models\Setting::get('app_logo');
        } catch (\Throwable $e) {
            return $default; // settings table not migrated yet, etc.
        }

        return stored_file_url($logo) ?? $default;
    }
}

if (! function_exists('current_company_id')) {
    /**
     * Get the current company ID from context.
     */
    function current_company_id()
    {
        return \App\Helpers\CompanyHelper::currentCompanyId();
    }
}

if (! function_exists('sites')) {
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

if (! function_exists('scope3_categories')) {
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

if (! function_exists('suppliers')) {
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

if (! function_exists('get_demo_restricted_routes')) {
    /**
     * Get the list of restricted route patterns for a demo user.
     * When user has restricted_sidebar_routes set (array), use that; when null, use config.
     * Empty array means no routes restricted for this user.
     *
     * @param  \App\Models\User  $user
     * @return array<string>
     */
    function get_demo_restricted_routes($user): array
    {
        if ($user->restricted_sidebar_routes !== null && is_array($user->restricted_sidebar_routes)) {
            return $user->restricted_sidebar_routes;
        }

        return config('demo.restricted_routes', []);
    }
}

if (! function_exists('route_matches_restricted_pattern')) {
    /**
     * Check if a route name matches a restricted pattern (exact or prefix).
     */
    function route_matches_restricted_pattern(string $routeName, string $pattern): bool
    {
        if (str_ends_with($pattern, '.')) {
            return str_starts_with($routeName, $pattern);
        }

        return $routeName === $pattern;
    }
}

if (! function_exists('demo_route_restricted')) {
    /**
     * Check if a route is restricted for the current user (demo user = restricted access).
     * Uses per-user restricted_sidebar_routes when set; otherwise config. Used for sidebar (lock links).
     */
    function demo_route_restricted(string $routeName): bool
    {
        $user = auth()->user();
        if (! $user || ! $user->is_demo_user) {
            return false;
        }
        if ($user->hasRole('Super Admin') || $user->hasRole('Admin')) {
            return false;
        }
        if (! config('demo.enabled', true)) {
            return false;
        }
        $restricted = get_demo_restricted_routes($user);
        foreach ($restricted as $pattern) {
            if (route_matches_restricted_pattern($routeName, $pattern)) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('user_can_see_sidebar_route')) {
    /**
     * Check if the current user should see this sidebar link.
     * Demo users see all sidebar links (restricted ones show with lock; click shows no-permission).
     * When user has custom allowed_sidebar_routes (array), only those routes are shown.
     * When null (default), show link (role/permission may still apply for non-demo).
     */
    function user_can_see_sidebar_route(string $routeName): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        $allowed = $user->allowed_sidebar_routes;
        // Demo users see all sidebar links; restricted ones get lock and no-permission on click
        if ($user->is_demo_user && ($allowed === null || ! is_array($allowed))) {
            return true;
        }
        if ($allowed === null || ! is_array($allowed)) {
            return true;
        }

        return in_array($routeName, $allowed, true);
    }
}

if (! function_exists('demo_restricted_tooltip')) {
    /**
     * Tooltip text shown on sidebar links that demo users cannot access.
     */
    function demo_restricted_tooltip(): string
    {
        return 'Demo user have no permission. This feature is not available for demo accounts. You can see it in the menu but cannot access it. Contact your administrator for full access.';
    }
}
if (! function_exists('format_factor')) {
    /**
     * Render an emission factor readably without rounding it away.
     *
     * Factors in this system span roughly 1.96e-7 (biogas per kWh) to 12.2
     * (PFC-116 per kg), so any fixed number of decimals is wrong at one end:
     * number_format($factor, 2) printed diesel's 0.0026847 as "0.00", on a
     * report that shows activity, factor and CO2e side by side — so the
     * arithmetic visibly failed to reconcile.
     *
     * Shows a fixed number of SIGNIFICANT figures instead, so both ends stay
     * legible, with trailing zeros trimmed and at least two decimals kept so a
     * column of factors still lines up.
     */
    function format_factor($value, int $significant = 4): string
    {
        $value = (float) $value;

        if ($value == 0.0) {
            return '0.00';
        }

        // Decimals needed to show $significant figures for this magnitude.
        $magnitude = (int) floor(log10(abs($value)));
        $decimals = max(2, min(12, $significant - 1 - $magnitude));

        $formatted = number_format($value, $decimals, '.', '');

        // Trim trailing zeros but never below two decimal places.
        if (str_contains($formatted, '.')) {
            $formatted = rtrim($formatted, '0');
            [$whole, $fraction] = array_pad(explode('.', $formatted, 2), 2, '');
            $formatted = $whole.'.'.str_pad($fraction, 2, '0');
        }

        return $formatted;
    }
}

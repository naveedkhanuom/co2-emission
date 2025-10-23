<?php

namespace App\Http\Middleware;

use Closure;

class SetCompanyConnection
{
    public function handle($request, Closure $next)
    {
        if ($request->is('reports*')) {

            return $next($request); // Skip logic
        }

        // 1️⃣ Detect company from query string
        $company = $request->query('company');

        // 2️⃣ If not provided, optionally detect from subdomain
        if (!$company) {
            $host = $request->getHost(); // e.g., qrs.example.com
            $subdomain = explode('.', $host)[0];

            if (in_array($subdomain, ['qrs', 'tqs'])) {
                $company = $subdomain;
            }
        }

        // 3️⃣ Default to main DB if no match
        if (!in_array($company, ['qrs', 'tqs'])) {
            $company = 'mysql';
        }

        // Share connection with the app for all models
        app()->instance('company_connection', $company);

        return $next($request);
    }
}

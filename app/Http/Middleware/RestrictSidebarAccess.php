<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictSidebarAccess
{
    /**
     * When user has custom allowed_sidebar_routes (array), only allow access to those routes.
     * Super Admin and Admin bypass this restriction.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        if ($user->hasRole('Super Admin') || $user->hasRole('Admin')) {
            return $next($request);
        }

        $allowed = $user->allowed_sidebar_routes;
        if ($allowed === null || ! is_array($allowed)) {
            return $next($request);
        }

        $route = $request->route();
        if (! $route) {
            return $next($request);
        }

        $routeName = $route->getName();
        if (! $routeName) {
            return $next($request);
        }

        // Allow if this route is in the list, or belongs to same section (e.g. reports.index allows reports.data)
        $allowedMatch = in_array($routeName, $allowed, true);
        if (! $allowedMatch) {
            foreach ($allowed as $allowedRoute) {
                $prefix = str_contains($allowedRoute, '.') ? substr($allowedRoute, 0, strrpos($allowedRoute, '.') + 1) : $allowedRoute . '.';
                if (str_starts_with($routeName, $prefix)) {
                    $allowedMatch = true;
                    break;
                }
            }
        }
        if (! $allowedMatch) {
            abort(403, 'You do not have access to this page.');
        }

        return $next($request);
    }
}

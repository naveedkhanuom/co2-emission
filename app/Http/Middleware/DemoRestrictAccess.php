<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DemoRestrictAccess
{
    /**
     * For users marked as "demo user" (is_demo_user = true), block access to
     * restricted routes and show a friendly message. Other users are not affected.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->is_demo_user) {
            return $next($request);
        }
        // Super Admin and Admin always have full access (no demo restriction)
        if ($user->hasRole('Super Admin') || $user->hasRole('Admin')) {
            return $next($request);
        }

        if (! config('demo.enabled', true)) {
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

        $restricted = get_demo_restricted_routes($user);
        foreach ($restricted as $pattern) {
            if (route_matches_restricted_pattern($routeName, $pattern)) {
                return $this->noPermissionResponse();
            }
        }

        return $next($request);
    }

    protected function noPermissionResponse(): Response
    {
        return response()->view('demo.no_permission', [], 403);
    }
}

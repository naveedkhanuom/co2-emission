<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DemoRestrictAccess
{
    /**
     * When demo mode is on, block access to restricted routes and show
     * a friendly "You don't have permission" message.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('demo.enabled', false)) {
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

        $restricted = config('demo.restricted_routes', []);
        foreach ($restricted as $pattern) {
            if (str_ends_with($pattern, '.')) {
                if (str_starts_with($routeName, $pattern)) {
                    return $this->noPermissionResponse();
                }
            } else {
                if ($routeName === $pattern) {
                    return $this->noPermissionResponse();
                }
            }
        }

        return $next($request);
    }

    protected function noPermissionResponse(): Response
    {
        return response()->view('demo.no_permission', [], 403);
    }
}

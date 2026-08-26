<?php

namespace App\Http\Middleware;

use Closure;
use Stancl\Tenancy\Exceptions\NotASubdomainException;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;

/**
 * Resolves a tenant from the subdomain when there is one, and continues in
 * central mode when there is not.
 *
 * This is TRANSITIONAL. The end state is the strict pair — InitializeTenancy
 * BySubdomain plus PreventAccessFromCentralDomains — where the application is
 * unreachable except on a client's subdomain. We cannot switch to that yet
 * because the existing inventory still lives in the central database and has
 * not been split into tenants, so cutting central off would take the running
 * app down until that migration completes.
 *
 * The important distinction, and the reason this is safe rather than a hole:
 * an UNKNOWN subdomain does not fall through to central. Only a host that is
 * not a subdomain at all does. A request to nosuchclient.example.com raises
 * TenantCouldNotBeIdentifiedOnDomainException and fails, instead of quietly
 * serving another tenant's data from the central connection.
 *
 * Remove this class once the data migration lands. It has one job and a
 * deliberately short life.
 */
class InitializeTenancyIfSubdomain extends InitializeTenancyBySubdomain
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $subdomain = $this->makeSubdomain($request->getHost());

        // Not a subdomain at all: a central domain, bare localhost, an IP, or
        // a third-party host. Serve from the central database as before.
        if ($subdomain instanceof NotASubdomainException) {
            return $next($request);
        }

        // It IS a subdomain, so it must resolve to a real tenant. Anything
        // else is an error, never a fallback.
        return parent::handle($request, $next);
    }
}

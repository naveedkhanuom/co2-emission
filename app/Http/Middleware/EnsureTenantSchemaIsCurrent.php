<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\TenantSchema;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * TEN-06 — refuses to serve a tenant whose database is behind the code.
 *
 * A tenant migration added after a client was provisioned never reaches them on
 * its own. Without this the first symptom is a 500 from a query naming a column
 * that does not exist, somewhere deep in a page — which reads as a bug in the
 * feature rather than as a deploy step that was missed.
 *
 * A 503 saying "being updated" is both truthful and actionable: it names the
 * cause, it is the right status for something transient, and it fails at the
 * front door instead of halfway through a request that may already have written
 * something.
 *
 * COST
 *
 * Nothing on the hot path. The comparison is two strings: the stamp already
 * loaded on the tenant row when the subdomain resolved, against a
 * process-cached directory listing. The one exception is a tenant with no stamp
 * yet — every tenant that existed before this shipped — which costs one query
 * once, and is then stamped for good.
 *
 * ORDER
 *
 * After EnsureTenantIsActive. A suspended account should be told it is
 * suspended, not that it is being updated — its status is the more specific
 * fact, and it is true for longer.
 */
class EnsureTenantSchemaIsCurrent
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        // No tenant bound means this is not a tenant route; nothing to police.
        if (! $tenant instanceof Tenant) {
            return $next($request);
        }

        // Never stamped: this tenant predates the stamp, or was provisioned by
        // a path that did not write one. Compute it once from the tenant's real
        // migrations table rather than assuming either answer — assuming
        // "behind" would take every existing client offline on deploy, and
        // assuming "current" would hide the very drift this exists to catch.
        if ($tenant->schema_version === null) {
            try {
                TenantSchema::stampCurrentTenant($tenant);
            } catch (\Throwable $e) {
                // A tenant whose migrations table cannot be read is a problem,
                // but not one to answer by taking the workspace down: let the
                // request through and let the real failure speak for itself.
                Log::warning('Could not stamp tenant schema version', [
                    'tenant' => $tenant->getTenantKey(),
                    'error' => $e->getMessage(),
                ]);

                return $next($request);
            }
        }

        if (! TenantSchema::isBehind($tenant)) {
            return $next($request);
        }

        // The stamp says behind — now CHECK, before taking a workspace down.
        //
        // The stamp is a cache, and it goes stale in an entirely ordinary way:
        // `tenants:migrate` brings a database fully up to date and does not
        // touch the stamp, so between it and `schema:stamp` every tenant is
        // correctly migrated and incorrectly marked behind. Refusing on the
        // stamp alone turned that gap into an outage — which is how this was
        // found, with two tenants at 0 pending migrations being served 503s.
        //
        // Verifying costs one query, and only on the path that was about to fail
        // anyway. The happy path still compares two strings and queries nothing.
        try {
            if (TenantSchema::pendingForCurrentTenant() === []) {
                TenantSchema::stampCurrentTenant($tenant);

                return $next($request);
            }
        } catch (\Throwable $e) {
            Log::warning('Could not verify tenant schema state', [
                'tenant' => $tenant->getTenantKey(),
                'error' => $e->getMessage(),
            ]);

            return $next($request);
        }

        Log::warning('Refusing to serve a tenant whose schema is behind', [
            'tenant' => $tenant->getTenantKey(),
            'schema_version' => $tenant->schema_version,
            'expected' => TenantSchema::latestAvailable(),
            'pending' => TenantSchema::pendingForCurrentTenant(),
        ]);

        return response()->view('tenant.unavailable', [
            'heading' => 'Being updated',
            'message' => 'This workspace is part-way through an update and is briefly unavailable. '
                .'Nothing has been lost. Please try again in a few minutes.',
            'account' => $tenant->name,
        ], 503);
    }
}

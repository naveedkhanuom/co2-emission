<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stops a tenant that is not active from being served.
 *
 * Tenants are resolved by subdomain, and the resolver matches on the domain
 * alone — it has no opinion about status. Without this, suspending an account
 * changed nothing a user could notice: they could still sign in and work
 * normally, and only the nightly scheduled run skipped them. "Suspended"
 * has to actually mean something before anything offers a button for it.
 *
 * Runs after tenancy initialises, so tenant() is bound by the time it reads.
 */
class EnsureTenantIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        // No tenant bound means this is not a tenant route; nothing to police.
        if (! $tenant instanceof Tenant || $tenant->isActive()) {
            return $next($request);
        }

        [$status, $heading, $message] = $this->responseFor($tenant->status);

        return response()->view('tenant.unavailable', [
            'heading' => $heading,
            'message' => $message,
            'account' => $tenant->name,
        ], $status);
    }

    /**
     * @return array{0: int, 1: string, 2: string}
     */
    protected function responseFor(?string $tenantStatus): array
    {
        return match ($tenantStatus) {
            Tenant::STATUS_PROVISIONING => [
                503,
                'Almost ready',
                'This workspace is still being set up. It will be available shortly.',
            ],
            Tenant::STATUS_PAST_DUE => [
                403,
                'Payment needed',
                'Access is paused while an outstanding invoice is settled. Your data is untouched and returns as soon as the account is brought up to date.',
            ],
            Tenant::STATUS_SUSPENDED => [
                403,
                'Workspace suspended',
                'This workspace has been suspended. Your data is retained. Please contact your account manager.',
            ],
            // Archived or a provisioning run that failed and rolled back:
            // as far as anyone visiting is concerned, it does not exist.
            default => [
                404,
                'Not found',
                'There is no workspace at this address.',
            ],
        };
    }
}

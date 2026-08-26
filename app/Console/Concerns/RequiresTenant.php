<?php

namespace App\Console\Concerns;

/**
 * For commands that operate on a client's data.
 *
 * Client data lives in per-tenant databases. A command run without a tenant
 * bound queries the CENTRAL connection, where none of it exists — and because
 * the central database still carries the old tables from before the split,
 * such a command does not error. It finds nothing, reports success, and moves
 * on. Worse, where stale rows remain it acts on them: an anomaly scan would
 * email people about companies that no longer exist anywhere real.
 *
 * Silent wrong behaviour is the failure mode this guard exists to remove.
 */
trait RequiresTenant
{
    /**
     * @return bool True if a tenant is bound and the command may proceed.
     */
    protected function ensureTenantContext(): bool
    {
        if (tenancy()->initialized) {
            return true;
        }

        $name = $this->getName();

        $this->components->error(
            "{$name} operates on a client's data and needs a tenant. Run it for every active "
            ."client with:\n\n    php artisan tenants:each {$name}\n\n"
            ."Or for one client with:\n\n    php artisan tenants:each {$name} --tenant=acme"
        );

        return false;
    }
}

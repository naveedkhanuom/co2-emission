<?php

namespace App\Console\Commands;

use App\Console\Concerns\RequiresTenant;
use App\Models\Tenant;
use App\Support\TenantSchema;
use Illuminate\Console\Command;

/**
 * TEN-06 — records what a tenant's database has actually migrated.
 *
 * Run immediately after `tenants:migrate`, as part of deploying:
 *
 *     php artisan tenants:migrate --force
 *     php artisan tenants:each schema:stamp
 *
 * Without the stamp, EnsureTenantSchemaIsCurrent has nothing to compare and the
 * back-office cannot show drift across the fleet without opening a connection
 * per client.
 *
 * Also reports anything still pending, which is the case worth seeing: a stamp
 * written while migrations are outstanding means `tenants:migrate` did not
 * finish, and the deploy should stop rather than continue quietly.
 */
class StampTenantSchemaVersion extends Command
{
    use RequiresTenant;

    protected $signature = 'schema:stamp
        {--check : Report drift without writing the stamp}';

    protected $description = "Record the migration state of a tenant's database on its tenant row";

    public function handle(): int
    {
        if (! $this->ensureTenantContext()) {
            return self::FAILURE;
        }

        $tenant = tenant();
        $id = $tenant instanceof Tenant ? $tenant->getTenantKey() : 'unknown';

        $pending = TenantSchema::pendingForCurrentTenant();
        $applied = TenantSchema::appliedForCurrentTenant();

        if ($this->option('check')) {
            if ($pending === []) {
                $this->components->info("[{$id}] up to date at {$applied}.");

                return self::SUCCESS;
            }

            $this->components->error("[{$id}] is behind by ".count($pending).' migration(s):');
            foreach ($pending as $migration) {
                $this->line("    {$migration}");
            }

            return self::FAILURE;
        }

        // Stamping a tenant that still has pending migrations would record it as
        // current when it is not — precisely the silent-drift failure this
        // exists to prevent. Refuse, loudly.
        if ($pending !== []) {
            $this->components->error(
                "[{$id}] has ".count($pending).' pending migration(s) and was NOT stamped. '
                .'Run `php artisan tenants:migrate --force` first.'
            );

            foreach ($pending as $migration) {
                $this->line("    {$migration}");
            }

            return self::FAILURE;
        }

        TenantSchema::stampCurrentTenant($tenant);

        $this->components->info("[{$id}] stamped at {$applied}.");

        return self::SUCCESS;
    }
}

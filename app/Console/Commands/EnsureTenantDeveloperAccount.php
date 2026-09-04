<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Support\DeveloperAccount;
use Illuminate\Console\Command;
use Throwable;

/**
 * Adds the developer account to clients that already exist.
 *
 * New clients get it during provisioning. This is for the ones created before
 * the account existed, and for repairing it after the configured password
 * changes — DeveloperAccount::ensure() rewrites the password, so running this
 * again is how a rotation reaches every client.
 */
class EnsureTenantDeveloperAccount extends Command
{
    protected $signature = 'tenant:dev-account
        {--tenant=* : Limit to specific client ids. Default: every active client}
        {--remove : Delete the account from each client instead of creating it}
        {--force : With --remove, delete even where it is the only account owner}';

    protected $description = 'Create, refresh, or remove the developer account inside each client workspace';

    public function handle(): int
    {
        $config = config('tenant_defaults.developer_account');

        if ($this->option('remove')) {
            return $this->removeEverywhere($config);
        }

        if (! ($config['enabled'] ?? false)) {
            $this->components->warn(
                'The developer account is disabled. Set TENANT_DEV_ACCOUNT_ENABLED=true to use it, '
                .'or run with --remove to clear it from clients that already have one.'
            );

            return self::SUCCESS;
        }

        if (empty($config['email']) || empty($config['password'])) {
            $this->components->error(
                'TENANT_DEV_ACCOUNT_EMAIL and TENANT_DEV_ACCOUNT_PASSWORD must both be set. '
                .'Refusing to create a half-configured account nobody knows the credentials for.'
            );

            return self::FAILURE;
        }

        $tenants = Tenant::query()
            ->where('status', Tenant::STATUS_ACTIVE)
            ->when($this->option('tenant'), fn ($q, $ids) => $q->whereIn('id', $ids))
            ->orderBy('id')
            ->get();

        if ($tenants->isEmpty()) {
            $this->components->info('No active clients.');

            return self::SUCCESS;
        }

        $failures = [];

        foreach ($tenants as $tenant) {
            try {
                $tenant->run(fn () => DeveloperAccount::ensure());
                $this->components->twoColumnDetail("<fg=cyan>{$tenant->id}</>", '<fg=green>ok</>');
            } catch (Throwable $e) {
                $failures[$tenant->id] = $e->getMessage();
                $this->components->twoColumnDetail("<fg=cyan>{$tenant->id}</>", '<fg=red>'.$e->getMessage().'</>');
            }
        }

        $this->newLine();
        $this->components->info(
            ($tenants->count() - count($failures))." of {$tenants->count()} client(s) now have "
            .$config['email'].' as a sign-in.'
        );

        $this->components->warn(
            'The same credentials now open every one of those workspaces. Handing a client their '
            .'database hands over this account\'s hash too.'
        );

        return $failures === [] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Delete the account from every client that has one.
     *
     * Runs against ALL clients, not only active ones: a suspended workspace
     * still has a database, and an account that opens it is exposure whether
     * or not the client is currently being served.
     *
     * @param  array<string, mixed>  $config
     */
    private function removeEverywhere(array $config): int
    {
        $email = (string) ($config['email'] ?? '');

        if ($email === '') {
            $this->components->error(
                'TENANT_DEV_ACCOUNT_EMAIL is not set, so there is no account to identify. '
                .'Set it to the address that was provisioned, then run this again.'
            );

            return self::FAILURE;
        }

        $tenants = Tenant::query()
            ->when($this->option('tenant'), fn ($q, $ids) => $q->whereIn('id', $ids))
            ->orderBy('id')
            ->get();

        $force = (bool) $this->option('force');
        $removed = 0;
        $orphans = [];
        $failures = [];

        foreach ($tenants as $tenant) {
            try {
                $result = $tenant->run(fn () => DeveloperAccount::remove($force));

                $removed += $result === DeveloperAccount::REMOVE_REMOVED ? 1 : 0;

                if ($result === DeveloperAccount::REMOVE_WOULD_ORPHAN) {
                    $orphans[] = $tenant->id;
                }

                $this->components->twoColumnDetail("<fg=cyan>{$tenant->id}</>", match ($result) {
                    DeveloperAccount::REMOVE_REMOVED => '<fg=green>removed</>',
                    DeveloperAccount::REMOVE_WOULD_ORPHAN => '<fg=yellow>skipped — only account owner</>',
                    default => '<fg=gray>none</>',
                });
            } catch (Throwable $e) {
                $failures[$tenant->id] = $e->getMessage();
                $this->components->twoColumnDetail("<fg=cyan>{$tenant->id}</>", '<fg=red>'.$e->getMessage().'</>');
            }
        }

        $this->newLine();
        $this->components->info("Removed {$email} from {$removed} of {$tenants->count()} client(s).");

        if ($orphans !== []) {
            $this->components->warn(
                'Skipped '.implode(', ', $orphans).': the developer account is the only account owner there, '
                .'so removing it would leave the workspace with nobody who can administer it. Add a real owner '
                .'first, then re-run. Use --force only for a tenant that is genuinely disposable.'
            );
        }

        if ($removed > 0) {
            $this->components->warn(
                'That password opened every one of those workspaces. Treat it as compromised and '
                .'rotate it anywhere else it was used.'
            );
        }

        return $failures === [] ? self::SUCCESS : self::FAILURE;
    }
}

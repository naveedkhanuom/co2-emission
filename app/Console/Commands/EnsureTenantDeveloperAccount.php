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
        {--tenant=* : Limit to specific client ids. Default: every active client}';

    protected $description = 'Create or refresh the developer account inside each client workspace';

    public function handle(): int
    {
        $config = config('tenant_defaults.developer_account');

        if (! ($config['enabled'] ?? false)) {
            $this->components->warn('The developer account is disabled. Set TENANT_DEV_ACCOUNT_ENABLED=true to use it.');

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
}

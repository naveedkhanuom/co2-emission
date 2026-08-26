<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

/**
 * Creates a client account: its database, its subdomain, its first company
 * and its owner.
 *
 * Provisioning creates a tenant with ONE company in it. Every company after
 * that is an ordinary in-app action against a database that already exists —
 * no new database, no new subdomain. Keeping those two paths separate is what
 * stops a holding group accidentally getting a database per subsidiary.
 */
class ProvisionTenant extends Command
{
    /**
     * Subdomains that must never resolve to a client, because they are ours
     * or because a mail/TLS convention already claims them.
     *
     * @var array<int, string>
     */
    protected const RESERVED_SUBDOMAINS = [
        'www', 'admin', 'api', 'app', 'mail', 'smtp', 'imap', 'ftp', 'ns1', 'ns2',
        'status', 'docs', 'blog', 'cdn', 'static', 'assets', 'support', 'help',
        'billing', 'dashboard', 'account', 'accounts', 'login', 'auth', 'test',
        'staging', 'dev', 'demo', 'internal', 'central',
    ];

    protected $signature = 'tenant:provision
        {subdomain : The subdomain and database suffix, e.g. "acme" for acme.example.com and tenant_acme}
        {--name= : The client account name. Defaults to the subdomain, title-cased}
        {--company= : The first company inside the account. Defaults to the account name}
        {--owner-name= : Full name of the account owner}
        {--owner-email= : Email the account owner signs in with}
        {--owner-password= : The owner password. Generated and shown once if omitted}
        {--plan= : Plan identifier, recorded on the tenant for billing}';

    protected $description = 'Provision a new client account: database, subdomain, first company and owner';

    public function handle(): int
    {
        $subdomain = Str::lower(trim((string) $this->argument('subdomain')));

        if (($failure = $this->validateSubdomain($subdomain)) !== null) {
            $this->components->error($failure);

            return self::FAILURE;
        }

        $accountName = (string) ($this->option('name') ?: Str::headline($subdomain));
        $companyName = (string) ($this->option('company') ?: $accountName);
        $ownerName = (string) ($this->option('owner-name') ?: 'Account Owner');
        $ownerEmail = Str::lower(trim((string) $this->option('owner-email')));

        if ($ownerEmail === '' || ! filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
            $this->components->error('--owner-email is required and must be a valid email address.');

            return self::FAILURE;
        }

        // The caller may supply the password so it knows what it is. The
        // back-office needs that: it invokes this command in-process, and a
        // password generated in here would only reach the command's output
        // buffer — which the caller discards, leaving an account nobody can
        // sign into.
        $password = (string) ($this->option('owner-password') ?: Str::password(16));
        $tenant = null;

        try {
            $this->components->task("Creating tenant, database tenant_{$subdomain}, and seeding it", function () use (
                $subdomain, $accountName, &$tenant
            ) {
                // Creating the tenant fires TenantCreated, whose pipeline
                // creates the database, migrates it and seeds it. See
                // App\Providers\TenancyServiceProvider.
                $tenant = Tenant::create([
                    'id' => $subdomain,
                    'name' => $accountName,
                    'status' => Tenant::STATUS_PROVISIONING,
                    'plan' => $this->option('plan'),
                ]);
            });

            $this->components->task("Pointing {$subdomain} at the tenant", function () use ($tenant, $subdomain) {
                $tenant->domains()->create(['domain' => $subdomain]);
            });

            $this->components->task("Creating company \"{$companyName}\" and its owner", function () use (
                $tenant, $companyName, $ownerName, $ownerEmail, $password
            ) {
                $tenant->run(function () use ($companyName, $ownerName, $ownerEmail, $password) {
                    // Roles were just seeded into this database; Spatie caches
                    // them per process, so clear it before assigning.
                    app(PermissionRegistrar::class)->forgetCachedPermissions();

                    $company = Company::create([
                        'name' => $companyName,
                        'is_active' => true,
                    ]);

                    $owner = User::create([
                        'name' => $ownerName,
                        'email' => $ownerEmail,
                        'password' => Hash::make($password),
                        'company_id' => $company->id,
                        // Account owner: sees every company in THIS tenant,
                        // and nothing outside it. The flag is still named
                        // is_account_owner from the single-database era.
                        'is_account_owner' => true,
                    ]);

                    $owner->assignRole('Super Admin');
                });
            });

            $this->components->task('Activating the account', function () use ($tenant) {
                $tenant->update(['status' => Tenant::STATUS_ACTIVE]);
            });
        } catch (Throwable $e) {
            $this->rollBack($tenant, $e);

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info("Account \"{$accountName}\" is ready.");
        $this->table(['', ''], [
            ['Sign in at', $this->signInUrl($subdomain)],
            ['Database', "tenant_{$subdomain}"],
            ['First company', $companyName],
            ['Owner', "{$ownerName} <{$ownerEmail}>"],
            ['Password', $password],
        ]);
        $this->components->warn('This password is shown once and is not stored anywhere. Send it over a channel you trust, and have them change it.');

        return self::SUCCESS;
    }

    /**
     * @return string|null The reason it is unusable, or null if it is fine.
     */
    protected function validateSubdomain(string $subdomain): ?string
    {
        $validator = Validator::make(['subdomain' => $subdomain], [
            'subdomain' => [
                'required',
                'string',
                'min:2',
                'max:63',
                // A DNS label: lowercase alphanumerics and hyphens, not
                // starting or ending with a hyphen.
                'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/',
                Rule::notIn(self::RESERVED_SUBDOMAINS),
            ],
        ], [
            'subdomain.regex' => 'A subdomain may contain only lowercase letters, numbers and hyphens, and may not start or end with a hyphen.',
            'subdomain.not_in' => 'That subdomain is reserved for the platform.',
        ]);

        if ($validator->fails()) {
            return (string) $validator->errors()->first('subdomain');
        }

        if (Tenant::whereKey($subdomain)->exists()) {
            return "A tenant with the id \"{$subdomain}\" already exists.";
        }

        return null;
    }

    /**
     * Leave nothing half-built. Deleting the tenant fires TenantDeleted, which
     * drops the database, so a failed run is fully reversed rather than
     * leaving a database nobody knows about.
     */
    protected function rollBack(?Tenant $tenant, Throwable $e): void
    {
        $this->newLine();
        $this->components->error('Provisioning failed: '.$e->getMessage());

        if ($tenant === null) {
            return;
        }

        try {
            $tenant->delete();
            $this->components->info('Rolled back: the tenant and its database were removed.');
        } catch (Throwable $rollbackFailure) {
            $tenant->update(['status' => Tenant::STATUS_FAILED]);
            $this->components->error(
                'Rollback also failed, so tenant "'.$tenant->id.'" is marked failed and its database may still exist. '
                .'Clean it up by hand: '.$rollbackFailure->getMessage()
            );
        }
    }

    protected function signInUrl(string $subdomain): string
    {
        $central = config('tenancy.central_domains')[0] ?? 'localhost';
        $scheme = str_contains((string) config('app.url'), 'https://') ? 'https' : 'http';

        return "{$scheme}://{$subdomain}.{$central}/login";
    }
}

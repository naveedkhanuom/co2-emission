<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Sets a new password for someone inside a client account.
 *
 * Provisioning shows the owner password once and stores it nowhere, which is
 * the right default and also means it can be lost. This is the way back in —
 * for that, and for the ordinary support case of a client locked out.
 *
 * Deliberately not a back-office button yet: resetting a named user's
 * password is a support action that should be logged and attributable, and
 * that deserves designing rather than bolting on.
 */
class ResetTenantOwnerPassword extends Command
{
    protected $signature = 'tenant:reset-password
        {tenant : The tenant id, e.g. acme}
        {--email= : Which user. Defaults to the account owner}
        {--password= : The new password. Generated and shown once if omitted}';

    protected $description = 'Set a new password for a user inside a client account';

    public function handle(): int
    {
        $tenant = Tenant::find($this->argument('tenant'));

        if (! $tenant) {
            $this->components->error("No tenant with id \"{$this->argument('tenant')}\".");

            return self::FAILURE;
        }

        $email = $this->option('email');
        $password = (string) ($this->option('password') ?: Str::password(16));
        $generated = ! $this->option('password');

        $result = $tenant->run(function () use ($email, $password) {
            $user = $email
                ? User::where('email', Str::lower(trim($email)))->first()
                : User::where('is_account_owner', true)->orderBy('id')->first();

            if (! $user) {
                return null;
            }

            $user->forceFill(['password' => Hash::make($password)])->save();

            return ['name' => $user->name, 'email' => $user->email];
        });

        if ($result === null) {
            $this->components->error(
                $email
                    ? "No user with email \"{$email}\" in \"{$tenant->id}\"."
                    : "\"{$tenant->id}\" has no account owner to reset."
            );

            return self::FAILURE;
        }

        $subdomain = $tenant->subdomain() ?? $tenant->id;
        $central = config('tenancy.central_domains')[0] ?? 'localhost';

        $this->components->info("Password reset for {$result['name']}.");
        $this->table(['', ''], array_filter([
            ['Sign in at', "http://{$subdomain}.{$central}/login"],
            ['Email', $result['email']],
            $generated ? ['Password', $password] : null,
        ]));

        if ($generated) {
            $this->components->warn('Shown once and stored nowhere. Send it over a channel you trust.');
        }

        return self::SUCCESS;
    }
}

<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * The standing developer account inside a client workspace.
 *
 * One implementation, used from two places: the seeder that runs while a
 * client is being provisioned, and the command that adds it to clients
 * provisioned before this existed. Two copies of "create the support account"
 * would eventually disagree about what it is allowed to do.
 *
 * Must be called with a tenant already bound — it writes into whichever
 * database is current.
 */
class DeveloperAccount
{
    /**
     * Create or update the account in the current tenant's database.
     *
     * Idempotent, so re-running is safe and also repairs a changed password.
     *
     * @return User|null Null when the account is disabled or unconfigured.
     */
    public static function ensure(): ?User
    {
        $config = config('tenant_defaults.developer_account');

        if (! ($config['enabled'] ?? false)) {
            return null;
        }

        $email = Str::lower(trim((string) ($config['email'] ?? '')));
        $password = (string) ($config['password'] ?? '');

        // Refuse to create a passwordless or nameless account rather than
        // inventing something. A half-configured support account is worse
        // than none: nobody knows it is there, and it still opens the door.
        if ($email === '' || $password === '') {
            return null;
        }

        $user = User::withoutGlobalScope('company')
            ->where('email', $email)
            ->first();

        $attributes = [
            'name' => (string) ($config['name'] ?? 'Developer'),
            'email' => $email,
            'password' => Hash::make($password),
            // Sees every company in this client's account. Still bounded by
            // the client's own database — this is not cross-client reach.
            'is_account_owner' => true,
        ];

        if ($user) {
            $user->forceFill($attributes)->save();
        } else {
            $user = User::create($attributes);
        }

        $role = $config['role'] ?? null;

        if ($role) {
            // Roles were seeded into this database moments ago during
            // provisioning; Spatie caches them per process.
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }
        }

        return $user;
    }
}

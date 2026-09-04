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
    public const REMOVE_REMOVED = 'removed';

    public const REMOVE_ABSENT = 'absent';

    public const REMOVE_WOULD_ORPHAN = 'would-orphan';

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

    /**
     * Delete the account from the current tenant's database.
     *
     * Turning the feature off stops NEW clients getting the account; it does
     * nothing about the ones that already have it. Those rows are the actual
     * exposure — a live account owner with a password shared across every
     * workspace on the platform — and they outlive the config flag that made
     * them.
     *
     * Deliberately reads the email from config rather than deleting anything
     * that looks like a support account: the only account this may remove is
     * the one this class created.
     *
     * Works with the feature disabled, which is the state it is normally run
     * in — you turn the account off, then clear what it left behind.
     *
     * REFUSES TO ORPHAN A WORKSPACE
     *
     * In some clients the developer account is the only account owner — it was
     * created during provisioning and nobody else was ever added. Deleting it
     * there does not reduce exposure, it takes the workspace away from whoever
     * owns it, with no way back in short of a database edit. Those cases are
     * reported rather than actioned, so a fleet-wide clear-out cannot quietly
     * lock a client out of their own data.
     *
     * $force is for the deliberate exception — a scratch or fixture tenant that
     * is genuinely disposable.
     *
     * @return string One of: removed, absent, would-orphan.
     */
    public static function remove(bool $force = false): string
    {
        $email = Str::lower(trim((string) config('tenant_defaults.developer_account.email')));

        if ($email === '') {
            return self::REMOVE_ABSENT;
        }

        $user = User::withoutGlobalScope('company')
            ->where('email', $email)
            ->first();

        if (! $user) {
            return self::REMOVE_ABSENT;
        }

        if (! $force && ! self::hasAnotherOwner($email)) {
            return self::REMOVE_WOULD_ORPHAN;
        }

        $user->delete();

        return self::REMOVE_REMOVED;
    }

    /** Is there an account owner in this database other than the developer? */
    private static function hasAnotherOwner(string $email): bool
    {
        return User::withoutGlobalScope('company')
            ->where('is_account_owner', true)
            ->whereRaw('LOWER(email) <> ?', [$email])
            ->exists();
    }
}

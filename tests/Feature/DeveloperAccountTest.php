<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\DeveloperAccount;
use Illuminate\Support\Facades\Hash;
use Tests\TenantTestCase;

/**
 * The standing developer account inside every client workspace.
 *
 * Convenient, and a real exposure: the same credentials open every client, so
 * handing one client their database hands over the hash of an account that
 * opens all the others. These tests pin down the two things that keep it from
 * being worse — it can be switched off entirely, and it refuses to exist
 * half-configured, which would leave an account nobody knows the password for
 * but which still opens the door.
 */
class DeveloperAccountTest extends TenantTestCase
{
    protected function configure(array $overrides = []): void
    {
        config(['tenant_defaults.developer_account' => array_merge([
            'enabled' => true,
            'name' => 'Developer',
            'email' => 'dev-account@gmail.com',
            'password' => 'a-test-password',
            'role' => 'Super Admin',
        ], $overrides)]);
    }

    protected function tearDown(): void
    {
        User::withoutGlobalScope('company')
            ->whereIn('email', ['dev-account@gmail.com', 'other@gmail.com'])
            ->delete();

        parent::tearDown();
    }

    /**
     * A security control's default decides what a forgotten deployment gets.
     *
     * This read `env('TENANT_DEV_ACCOUNT_ENABLED', true)`, so a deployment that
     * never set the key was provisioned with a standing account owner in every
     * client database. Off-by-default fails by locking someone out; on-by-
     * default fails by silently granting access to every client on the
     * platform, and nothing about the deployment looks wrong either way.
     */
    public function test_the_account_is_off_unless_a_deployment_asks_for_it(): void
    {
        $this->assertFalse(
            (bool) (require base_path('config/tenant_defaults.php'))['developer_account']['enabled'],
            'The developer account defaults to enabled — a deployment that forgets the key gets it.'
        );
    }

    public function test_removing_it_deletes_the_account(): void
    {
        $this->configure();
        DeveloperAccount::ensure();

        // Someone else owns this workspace, so the developer is not load-bearing.
        User::create([
            'name' => 'Real Owner',
            'email' => 'other@gmail.com',
            'password' => Hash::make('x'),
            'is_account_owner' => true,
        ]);

        $this->assertSame(DeveloperAccount::REMOVE_REMOVED, DeveloperAccount::remove());

        $this->assertNull(
            User::withoutGlobalScope('company')->where('email', 'dev-account@gmail.com')->first()
        );
    }

    public function test_removing_works_once_the_feature_is_switched_off(): void
    {
        // The order this is actually run in: turn the account off, then clear
        // what it left behind. A removal that needed the feature enabled would
        // be useless at exactly the moment it is wanted.
        $this->configure();
        DeveloperAccount::ensure();

        User::create([
            'name' => 'Real Owner',
            'email' => 'other@gmail.com',
            'password' => Hash::make('x'),
            'is_account_owner' => true,
        ]);

        $this->configure(['enabled' => false]);

        $this->assertSame(DeveloperAccount::REMOVE_REMOVED, DeveloperAccount::remove());
    }

    public function test_it_refuses_to_leave_a_workspace_with_no_owner(): void
    {
        // Real case, found across the live fleet: in some clients the developer
        // account is the ONLY account owner, because it was created at
        // provisioning and nobody else was ever added. Deleting it there does
        // not reduce exposure — it takes the workspace away from whoever owns
        // it, with no way back in short of a database edit.
        User::withoutGlobalScope('company')
            ->where('is_account_owner', true)
            ->update(['is_account_owner' => false]);

        $this->configure();
        DeveloperAccount::ensure();

        $this->assertSame(DeveloperAccount::REMOVE_WOULD_ORPHAN, DeveloperAccount::remove());

        $this->assertNotNull(
            User::withoutGlobalScope('company')->where('email', 'dev-account@gmail.com')->first(),
            'The only account owner was deleted, locking the workspace out.'
        );
    }

    public function test_force_removes_it_even_when_that_orphans_the_workspace(): void
    {
        User::withoutGlobalScope('company')
            ->where('is_account_owner', true)
            ->update(['is_account_owner' => false]);

        $this->configure();
        DeveloperAccount::ensure();

        // The deliberate exception — a scratch or fixture tenant.
        $this->assertSame(DeveloperAccount::REMOVE_REMOVED, DeveloperAccount::remove(force: true));
    }

    public function test_removing_an_account_that_was_never_there_is_not_an_error(): void
    {
        $this->configure();

        $this->assertSame(DeveloperAccount::REMOVE_ABSENT, DeveloperAccount::remove());
    }

    public function test_it_only_ever_removes_the_configured_address(): void
    {
        $this->configure();
        DeveloperAccount::ensure();

        $other = User::create([
            'name' => 'Real Owner',
            'email' => 'other@gmail.com',
            'password' => Hash::make('x'),
            'is_account_owner' => true,
        ]);

        DeveloperAccount::remove();

        // Nothing that merely looks like a support account is touched.
        $this->assertNotNull(User::withoutGlobalScope('company')->find($other->id));
    }

    public function test_it_creates_the_account_with_access_to_every_company(): void
    {
        $this->configure();

        $user = DeveloperAccount::ensure();

        $this->assertNotNull($user);
        $this->assertSame('dev-account@gmail.com', $user->email);
        $this->assertTrue((bool) $user->is_account_owner, 'It must see every company in the account.');
        $this->assertTrue($user->hasRole('Super Admin'));
        $this->assertTrue(Hash::check('a-test-password', $user->password));
    }

    /**
     * Running it again must not duplicate the account, and must bring the
     * password back in line — that is how rotating it reaches every client.
     */
    public function test_it_is_idempotent_and_refreshes_the_password(): void
    {
        $this->configure();
        DeveloperAccount::ensure();

        $this->configure(['password' => 'a-rotated-password']);
        $user = DeveloperAccount::ensure();

        $this->assertSame(
            1,
            User::withoutGlobalScope('company')->where('email', 'dev-account@gmail.com')->count(),
            'Re-running must not create a second account.'
        );
        $this->assertTrue(Hash::check('a-rotated-password', $user->fresh()->password));
    }

    public function test_it_creates_nothing_when_disabled(): void
    {
        $this->configure(['enabled' => false]);

        $this->assertNull(DeveloperAccount::ensure());
        $this->assertNull(User::withoutGlobalScope('company')->where('email', 'dev-account@gmail.com')->first());
    }

    /**
     * A half-configured support account is worse than none: nobody knows it
     * is there, nobody knows its password, and it still opens the door.
     *
     * @dataProvider incompleteConfigurations
     */
    public function test_it_refuses_to_exist_half_configured(array $overrides): void
    {
        $this->configure($overrides);

        $this->assertNull(DeveloperAccount::ensure());
        $this->assertSame(
            0,
            User::withoutGlobalScope('company')->whereIn('email', ['dev-account@gmail.com', ''])->count()
        );
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function incompleteConfigurations(): array
    {
        return [
            'no email' => [['email' => '']],
            'no password' => [['password' => '']],
            'neither' => [['email' => '', 'password' => '']],
        ];
    }

    public function test_a_newly_provisioned_client_gets_the_account(): void
    {
        // The fixture tenant was seeded through TenantDatabaseSeeder, which
        // ends with DeveloperAccountSeeder, so the configured account is
        // already present from the real provisioning path.
        $configured = config('tenant_defaults.developer_account');

        if (! ($configured['enabled'] ?? false) || empty($configured['email'])) {
            $this->markTestSkipped('The developer account is not enabled in this environment.');
        }

        $this->assertNotNull(
            User::withoutGlobalScope('company')->where('email', $configured['email'])->first(),
            'Provisioning must leave the developer account in the workspace.'
        );
    }
}

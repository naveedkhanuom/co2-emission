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

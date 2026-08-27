<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Country;
use App\Models\EmissionFactor;
use App\Models\EmissionSource;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * tenant:provision creates a whole client account — database, subdomain,
 * first company, owner.
 *
 * Deliberately NOT using DatabaseTransactions: provisioning issues CREATE
 * DATABASE, which MySQL treats as an implicit commit, so a surrounding
 * transaction would not roll back cleanly. tearDown deletes the tenant
 * instead, which drops its database through the TenantDeleted pipeline.
 */
class TenantProvisioningTest extends TestCase
{
    /** Set by any test that actually provisions, so tearDown can clean up. */
    protected ?string $provisionedTenantId = null;

    protected function tearDown(): void
    {
        if ($this->provisionedTenantId !== null) {
            Tenant::find($this->provisionedTenantId)?->delete();
            $this->provisionedTenantId = null;
        }

        parent::tearDown();
    }

    public function test_it_provisions_a_complete_client_account(): void
    {
        $this->provisionedTenantId = 'phpunitacme';

        $this->artisan('tenant:provision', [
            'subdomain' => 'phpunitacme',
            '--name' => 'PHPUnit Holding Group',
            '--company' => 'PHPUnit Industries Ltd',
            '--owner-name' => 'Sara Malik',
            '--owner-email' => 'sara@phpunit.test',
        ])->assertSuccessful();

        $tenant = Tenant::find('phpunitacme');

        $this->assertNotNull($tenant, 'The tenant row was not created.');
        $this->assertSame('PHPUnit Holding Group', $tenant->name);
        $this->assertSame(Tenant::STATUS_ACTIVE, $tenant->status);
        $this->assertSame('phpunitacme', $tenant->subdomain());

        $tenant->run(function () {
            $this->assertSame(1, Company::count(), 'Provisioning creates exactly one company.');
            $this->assertSame('PHPUnit Industries Ltd', Company::first()->name);

            // Asserted by identity rather than by count: a client workspace
            // may also carry the standing developer account, which is
            // configuration rather than something provisioning decides.
            $owner = User::where('email', 'sara@phpunit.test')->first();

            $this->assertNotNull($owner, 'The account owner must be created.');
            $this->assertSame('sara@phpunit.test', $owner->email);
            $this->assertSame(Company::first()->id, $owner->company_id);
            $this->assertTrue((bool) $owner->is_account_owner, 'The owner sees every company in their account.');
            $this->assertTrue($owner->hasRole('Super Admin'));

            // Access control and the shared catalogues are seeded, so the
            // account is usable the moment the owner signs in.
            $this->assertGreaterThan(0, Role::count());
            $this->assertGreaterThan(0, Permission::count());
            $this->assertGreaterThan(0, Country::count());
            $this->assertGreaterThan(0, EmissionSource::count());
            $this->assertGreaterThan(0, EmissionFactor::count());
        });
    }

    public function test_the_owner_password_is_hashed_and_not_the_email(): void
    {
        $this->provisionedTenantId = 'phpunitpw';

        $this->artisan('tenant:provision', [
            'subdomain' => 'phpunitpw',
            '--owner-email' => 'owner@phpunit.test',
        ])->assertSuccessful();

        Tenant::find('phpunitpw')->run(function () {
            $owner = User::first();

            $this->assertNotSame('owner@phpunit.test', $owner->password);
            $this->assertTrue(str_starts_with($owner->password, '$'), 'The password should be a hash.');
            $this->assertFalse(Hash::check('password', $owner->password));
        });
    }

    public function test_it_refuses_a_reserved_subdomain(): void
    {
        $this->artisan('tenant:provision', [
            'subdomain' => 'admin',
            '--owner-email' => 'owner@phpunit.test',
        ])->assertFailed();

        $this->assertNull(Tenant::find('admin'));
    }

    public function test_it_refuses_a_subdomain_that_is_not_a_valid_dns_label(): void
    {
        foreach (['Acme Corp', 'acme_corp', '-acme', 'acme-', 'a'] as $invalid) {
            $this->artisan('tenant:provision', [
                'subdomain' => $invalid,
                '--owner-email' => 'owner@phpunit.test',
            ])->assertFailed();
        }

        $this->assertSame(0, Tenant::whereIn('id', ['acme corp', 'acme_corp', '-acme', 'acme-', 'a'])->count());
    }

    public function test_it_refuses_a_subdomain_that_is_already_taken(): void
    {
        $this->provisionedTenantId = 'phpunitdupe';

        $this->artisan('tenant:provision', [
            'subdomain' => 'phpunitdupe',
            '--owner-email' => 'first@phpunit.test',
        ])->assertSuccessful();

        $this->artisan('tenant:provision', [
            'subdomain' => 'phpunitdupe',
            '--owner-email' => 'second@phpunit.test',
        ])->assertFailed();

        // The failed second run must not have touched the first tenant.
        Tenant::find('phpunitdupe')->run(function () {
            $this->assertNotNull(
                User::where('email', 'first@phpunit.test')->first(),
                'The original owner must survive the rejected second run.'
            );
            $this->assertNull(
                User::where('email', 'second@phpunit.test')->first(),
                'The rejected run must not have created its owner.'
            );
        });
    }

    public function test_it_requires_a_valid_owner_email(): void
    {
        foreach (['', 'not-an-email'] as $invalid) {
            $this->artisan('tenant:provision', [
                'subdomain' => 'phpunitnoemail',
                '--owner-email' => $invalid,
            ])->assertFailed();
        }

        $this->assertNull(
            Tenant::find('phpunitnoemail'),
            'A bad email must be caught before any database is created.'
        );
    }
}

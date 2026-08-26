<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;
use Tests\TenantTestCase;

/**
 * is_account_owner must never be settable through a form.
 *
 * The flag lets its holder skip the company filter in HasCompanyScope and see
 * every company in the account. UserController strips it from request input on
 * both create and update — three separate call sites — but nothing tested that,
 * so a refactor that tidied one of them away would have removed a privilege
 * boundary silently.
 *
 * Every test here asserts the request SUCCEEDED as well as that the flag did
 * not stick. Without that, a request rejected by validation would leave the
 * flag false and the test would pass for entirely the wrong reason.
 *
 * Emails use gmail.com because StoreUserRequest and UpdateUserRequest validate
 * with email:rfc,dns, which requires a domain carrying a real MX record —
 * example.com and .test both fail it, having none. The lookup is live, so
 * these tests need working DNS.
 */
class AccountOwnerEscalationTest extends TenantTestCase
{
    private function makeCompany(string $name = 'Escalation Test Co'): Company
    {
        return Company::create([
            'name' => $name,
            'industry_type' => 'manufacturing',
            'is_active' => true,
        ]);
    }

    private function makeActor(Company $company): User
    {
        $actor = User::create([
            'name' => 'Actor',
            'email' => 'actor-'.uniqid().'@gmail.com',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
            'is_account_owner' => true,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $actor->givePermissionTo(['list-users', 'create-user', 'edit-user']);

        return $actor;
    }

    private function makeTarget(Company $company): User
    {
        return User::create([
            'name' => 'Ordinary User',
            'email' => 'ordinary-'.uniqid().'@gmail.com',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
            'is_account_owner' => false,
        ]);
    }

    public function test_creating_a_user_cannot_grant_account_ownership(): void
    {
        $company = $this->makeCompany();
        $actor = $this->makeActor($company);
        $email = 'escalate-'.uniqid().'@gmail.com';

        $this->actingAs($actor)
            ->post(route('users.store'), [
                'name' => 'Aspiring Owner',
                'email' => $email,
                'password' => 'password1234',
                'password_confirmation' => 'password1234',
                'roles' => ['User'],
                'company_id' => $company->id,
                'is_account_owner' => 1,
            ])
            ->assertSessionHasNoErrors();

        $created = User::where('email', $email)->first();

        $this->assertNotNull($created, 'The user should be created — the flag is stripped, not rejected.');
        $this->assertFalse(
            (bool) $created->is_account_owner,
            'Posting is_account_owner must not grant it.'
        );
    }

    public function test_updating_a_user_cannot_grant_account_ownership(): void
    {
        $company = $this->makeCompany();
        $actor = $this->makeActor($company);
        $target = $this->makeTarget($company);

        $this->actingAs($actor)
            ->put(route('users.update', $target->id), [
                'name' => 'Renamed Without Password',
                'email' => $target->email,
                'roles' => ['User'],
                'company_id' => $company->id,
                'is_account_owner' => 1,
            ])
            ->assertSessionHasNoErrors();

        $target->refresh();

        $this->assertSame(
            'Renamed Without Password',
            $target->name,
            'The update must actually have applied, or this test proves nothing.'
        );
        $this->assertFalse(
            (bool) $target->is_account_owner,
            'Updating a user must not be able to grant account ownership.'
        );
    }

    /**
     * The update path strips the flag twice — once on the with-password branch
     * and once on the without-password branch. Only one runs per request, so
     * both need covering.
     */
    public function test_updating_a_user_with_a_new_password_cannot_grant_account_ownership(): void
    {
        $company = $this->makeCompany();
        $actor = $this->makeActor($company);
        $target = $this->makeTarget($company);
        $originalPassword = $target->password;

        $this->actingAs($actor)
            ->put(route('users.update', $target->id), [
                'name' => 'Renamed With Password',
                'email' => $target->email,
                'password' => 'newpassword1234',
                'password_confirmation' => 'newpassword1234',
                'roles' => ['User'],
                'company_id' => $company->id,
                'is_account_owner' => 1,
            ])
            ->assertSessionHasNoErrors();

        $target->refresh();

        $this->assertNotSame(
            $originalPassword,
            $target->password,
            'The with-password branch must actually have run.'
        );
        $this->assertFalse(
            (bool) $target->is_account_owner,
            'The with-password update branch must strip the flag too.'
        );
    }
}

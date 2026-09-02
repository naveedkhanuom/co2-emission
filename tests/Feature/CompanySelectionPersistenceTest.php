<?php

namespace Tests\Feature;

use App\Http\Middleware\SetCompanyConnection;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Tests\TenantTestCase;

/**
 * TEN-09 — an account owner's company selection was silently cleared by
 * ordinary saves.
 *
 * SetCompanyConnection tested $request->has('company_id') — which consults
 * $request->all(), query string AND request body — but read the value with
 * $request->query('company_id'), which reads the query string alone. A POST
 * carrying company_id in its body therefore entered the branch with a NULL
 * value, and User::canAccessCompany(null) returns true unconditionally for an
 * account owner, so the branch wrote current_company_id = null into the session.
 *
 * HasCompanyScope then took its is_account_owner early return and left the
 * query unscoped. The owner's chosen company filter was gone and every company
 * in the account was pooled together, with nothing on screen saying so — so the
 * figures read after saving a user were for a different scope than the one
 * selected, and looked entirely normal.
 *
 * Reachable from users/create.blade.php, users/edit.blade.php and
 * sites/index.blade.php, all of which post company_id in the body.
 *
 * No cross-tenant exposure — the tenant database is still the boundary — but
 * wrong numbers presented as right ones, which is the failure mode this
 * platform can least afford.
 *
 * The middleware is exercised directly rather than through a route: the bug is
 * entirely in how it reads the request, and a route would add validation and
 * permissions that could mask which half failed.
 */
class CompanySelectionPersistenceTest extends TenantTestCase
{
    private function makeCompany(string $name): Company
    {
        return Company::create([
            'name' => $name.' '.uniqid(),
            'industry_type' => 'manufacturing',
            'is_active' => true,
        ]);
    }

    private function makeUser(Company $company, bool $owner = false): User
    {
        return User::create([
            'name' => 'Selection Test User',
            'email' => 'selection-'.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
            'is_account_owner' => $owner,
        ]);
    }

    private function sessionWith(?int $selected): Store
    {
        $session = new Store('test-session', new ArraySessionHandler(120));

        if ($selected !== null) {
            $session->put('current_company_id', $selected);
        }

        return $session;
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function runMiddleware(Store $session, string $method, string $uri, array $body = []): void
    {
        $request = Request::create($uri, $method, $body);
        $request->setLaravelSession($session);

        (new SetCompanyConnection)->handle($request, fn () => response('ok'));
    }

    /**
     * The bug, exactly: an owner viewing company B saves a user, the form posts
     * company_id in the body, and B is lost.
     */
    public function test_a_post_body_company_id_does_not_clear_an_owners_selection(): void
    {
        $home = $this->makeCompany('Home Co');
        $selected = $this->makeCompany('Selected Co');

        $this->actingAs($this->makeUser($home, owner: true));

        $session = $this->sessionWith($selected->id);
        $this->runMiddleware($session, 'POST', '/users', ['company_id' => $selected->id]);

        $this->assertSame(
            $selected->id,
            $session->get('current_company_id'),
            "The owner's selected company was cleared by a save that posted company_id in its body."
        );
    }

    /**
     * The same shape with an empty value, which is what a form renders when no
     * company is picked in its dropdown. This is the variant that survives a
     * naive fix — swapping query() for input() without also checking emptiness
     * still writes '' into the session.
     */
    public function test_an_empty_body_company_id_does_not_clear_an_owners_selection(): void
    {
        $home = $this->makeCompany('Home Co');
        $selected = $this->makeCompany('Selected Co');

        $this->actingAs($this->makeUser($home, owner: true));

        $session = $this->sessionWith($selected->id);
        $this->runMiddleware($session, 'POST', '/users', ['company_id' => '']);

        $this->assertSame(
            $selected->id,
            $session->get('current_company_id'),
            'An empty company_id in the request body cleared the selection.'
        );
    }

    /**
     * The branch exists so that switching companies works. Fixing the leak must
     * not break the feature.
     */
    public function test_a_query_string_company_id_still_switches_the_selection(): void
    {
        $home = $this->makeCompany('Home Co');
        $target = $this->makeCompany('Target Co');

        $this->actingAs($this->makeUser($home, owner: true));

        $session = $this->sessionWith($home->id);
        $this->runMiddleware($session, 'GET', '/sites?company_id='.$target->id);

        $this->assertEquals(
            $target->id,
            $session->get('current_company_id'),
            'An owner can no longer switch company through the query string.'
        );
    }

    /**
     * Switching by body is legitimate for a company the user may access — the
     * fix is about not switching to NOTHING, not about refusing the body.
     */
    public function test_an_owner_can_switch_by_post_body_to_a_real_company(): void
    {
        $home = $this->makeCompany('Home Co');
        $target = $this->makeCompany('Target Co');

        $this->actingAs($this->makeUser($home, owner: true));

        $session = $this->sessionWith($home->id);
        $this->runMiddleware($session, 'POST', '/sites', ['company_id' => $target->id]);

        $this->assertEquals($target->id, $session->get('current_company_id'));
    }

    /**
     * A user who is not an account owner must not be able to switch into a
     * company they have no access to, by any route into the middleware.
     */
    public function test_a_non_owner_cannot_switch_into_a_company_they_cannot_access(): void
    {
        $mine = $this->makeCompany('My Co');
        $other = $this->makeCompany('Other Co');

        $this->actingAs($this->makeUser($mine, owner: false));

        $session = $this->sessionWith($mine->id);
        $this->runMiddleware($session, 'POST', '/sites', ['company_id' => $other->id]);

        $this->assertSame(
            $mine->id,
            $session->get('current_company_id'),
            'A non-owner switched into a company they cannot access.'
        );
    }
}

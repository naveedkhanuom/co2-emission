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
 * An account owner's company selection did not survive signing out.
 *
 * The company switcher wrote its choice to `current_company_id` in the session
 * and nowhere else. LoginController::logout() calls session()->flush(), which
 * is what a logout should do — so on the next sign-in step 1 of the lookup was
 * always empty.
 *
 * For an ordinary member that was invisible: SetCompanyConnection falls back to
 * `users.company_id` and their own company reappeared. An ACCOUNT OWNER has no
 * `company_id` — having none is precisely what makes them an owner rather than
 * a member of one company — so there was nothing to fall back to, no company
 * was bound, and the sidebar showed "Select Company" after every login no
 * matter what they had been working in.
 *
 * Confirmed against the live databases: the standing developer account is an
 * account owner with company_id = NULL in acme, green and zaheer.
 *
 * The middleware is exercised directly, matching CompanySelectionPersistence
 * Test: the behaviour under test is entirely in how the middleware resolves a
 * company, and going through a route would add permissions and validation that
 * could mask which part failed.
 */
class CompanySelectionSurvivesLogoutTest extends TenantTestCase
{
    private function makeCompany(string $name): Company
    {
        return Company::create([
            'name' => $name.' '.uniqid(),
            'industry_type' => 'manufacturing',
            'is_active' => true,
        ]);
    }

    /**
     * An account owner as the application actually creates one: no company_id.
     */
    private function makeOwner(): User
    {
        return User::create([
            'name' => 'Owner '.uniqid(),
            'email' => 'owner-'.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'company_id' => null,
            'is_account_owner' => true,
        ]);
    }

    private function makeMember(Company $company): User
    {
        return User::create([
            'name' => 'Member '.uniqid(),
            'email' => 'member-'.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
            'is_account_owner' => false,
        ]);
    }

    /** A session with nothing in it — what a fresh login gets after flush(). */
    private function emptySession(): Store
    {
        return new Store('test-session', new ArraySessionHandler(120));
    }

    private function runMiddleware(Store $session, string $uri = '/home'): void
    {
        $request = Request::create($uri, 'GET');
        $request->setLaravelSession($session);

        (new SetCompanyConnection)->handle($request, fn () => response('ok'));
    }

    public function test_an_owners_company_is_restored_after_the_session_is_flushed(): void
    {
        $selected = $this->makeCompany('Selected Co');
        $owner = $this->makeOwner();
        $owner->rememberCompany($selected->id);

        $this->actingAs($owner->fresh());

        // The state right after logout()->flush() and a fresh sign-in.
        $session = $this->emptySession();
        $this->runMiddleware($session);

        $this->assertTrue(app()->bound('current_company'), 'No company was bound for the owner.');
        $this->assertSame($selected->id, app('current_company')->id);
        $this->assertSame($selected->id, $session->get('current_company_id'));
    }

    public function test_switching_company_records_it_against_the_user(): void
    {
        $first = $this->makeCompany('First Co');
        $second = $this->makeCompany('Second Co');
        $owner = $this->makeOwner();

        $owner->rememberCompany($first->id);
        $this->assertSame($first->id, $owner->fresh()->last_company_id);

        $owner->rememberCompany($second->id);
        $this->assertSame($second->id, $owner->fresh()->last_company_id);
    }

    public function test_a_remembered_company_the_user_cannot_reach_is_not_restored(): void
    {
        // The row is written on one request and read on a later one, so access
        // can be revoked in between. Restoring it regardless would reinstate a
        // company the user is no longer entitled to see.
        $ownCompany = $this->makeCompany('Own Co');
        $otherCompany = $this->makeCompany('Other Co');

        $member = $this->makeMember($ownCompany);

        // Written directly, bypassing rememberCompany()'s own check, to model a
        // membership that has since changed rather than a bad write.
        $member->last_company_id = $otherCompany->id;
        $member->saveQuietly();

        $this->actingAs($member->fresh());

        $session = $this->emptySession();
        $this->runMiddleware($session);

        $this->assertSame(
            $ownCompany->id,
            app('current_company')->id,
            'A member fell back to a company they can no longer access.'
        );
    }

    public function test_remember_refuses_a_company_the_user_cannot_access(): void
    {
        $ownCompany = $this->makeCompany('Own Co');
        $otherCompany = $this->makeCompany('Other Co');

        $member = $this->makeMember($ownCompany);
        $member->rememberCompany($otherCompany->id);

        $this->assertNull(
            $member->fresh()->last_company_id,
            'A member remembered a company they cannot access.'
        );
    }

    public function test_a_members_own_company_still_wins_when_nothing_is_remembered(): void
    {
        // The pre-existing fallback must keep working — this is the path that
        // hid the bug for every user except account owners.
        $company = $this->makeCompany('Member Co');
        $member = $this->makeMember($company);

        $this->actingAs($member);

        $session = $this->emptySession();
        $this->runMiddleware($session);

        $this->assertSame($company->id, app('current_company')->id);
    }

    public function test_a_session_company_the_user_cannot_access_is_discarded(): void
    {
        // The session id was previously read straight through with no check.
        // That held only because the two paths that write it both call
        // canAccessCompany() first, and logout() flushes — so a stale id could
        // not normally outlive the user who set it. Neither is a guarantee, and
        // binding a company the user cannot reach would scope every query in
        // the request to it.
        //
        // Found by SupportingDocumentPrivacyTest: once the middleware itself
        // began writing the key, one user's company survived in the session
        // into another user's request and served them a document they did not
        // own.
        $ownCompany = $this->makeCompany('Own Co');
        $foreign = $this->makeCompany('Foreign Co');

        $member = $this->makeMember($ownCompany);
        $this->actingAs($member);

        $session = $this->emptySession();
        $session->put('current_company_id', $foreign->id);
        $this->runMiddleware($session);

        $this->assertSame(
            $ownCompany->id,
            app('current_company')->id,
            'A company the user cannot access was bound from the session.'
        );
        $this->assertSame(
            $ownCompany->id,
            $session->get('current_company_id'),
            'The unreachable company was left in the session to be read again.'
        );
    }

    public function test_the_session_still_takes_priority_over_the_remembered_company(): void
    {
        // Remembering must not override a choice made during this session.
        $remembered = $this->makeCompany('Remembered Co');
        $active = $this->makeCompany('Active Co');

        $owner = $this->makeOwner();
        $owner->rememberCompany($remembered->id);

        $this->actingAs($owner->fresh());

        $session = $this->emptySession();
        $session->put('current_company_id', $active->id);
        $this->runMiddleware($session);

        $this->assertSame($active->id, app('current_company')->id);
    }
}

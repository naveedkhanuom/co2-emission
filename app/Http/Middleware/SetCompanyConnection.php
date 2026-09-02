<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Support\Facades\Auth;

class SetCompanyConnection
{
    public function handle($request, Closure $next)
    {
        // Skip for public routes
        if ($request->is('login', 'register', 'password/*')) {
            return $next($request);
        }

        // Where the current company comes from, in order:
        //
        //   1. the session      — what they picked during THIS session
        //   2. last_company_id  — what they picked during a previous one
        //   3. company_id       — the company they belong to
        //
        // Step 2 is what makes the choice survive a logout. logout() calls
        // session()->flush(), correctly, so step 1 is empty on every fresh
        // login; for an ACCOUNT OWNER step 3 is null as well, because having no
        // company_id is what distinguishes an owner from a member. Both those
        // things are right on their own, and together they meant an owner's
        // company selection was lost at every sign-out and the sidebar fell
        // back to "Select Company".
        $companyId = $request->session()->get('current_company_id');

        // The session value is checked against the signed-in user, not trusted
        // for being in the session.
        //
        // It was previously read straight through. That was survivable only
        // because nothing wrote to the session except the two paths that had
        // already called canAccessCompany(), and logout() flushes — so a stale
        // id could not normally outlive the user who put it there. Neither of
        // those is a guarantee: a session that changes hands without being
        // flushed, or any future code that writes the key, would bind a company
        // the current user cannot access, and every scoped query in the request
        // would then run against it.
        //
        // Cheap to check and it removes the assumption entirely.
        if ($companyId && Auth::check() && ! Auth::user()->canAccessCompany($companyId)) {
            $companyId = null;
            $request->session()->forget('current_company_id');
        }

        if (! $companyId && Auth::check()) {
            $user = Auth::user();

            // Re-checked for the same reason. The row was written on an earlier
            // request and the user's access may have been revoked since.
            if ($user->last_company_id && $user->canAccessCompany($user->last_company_id)) {
                $companyId = $user->last_company_id;
            }

            $companyId ??= $user->company_id;

            // Put it back in the session so the rest of the request, and every
            // request after it, reads from one place.
            if ($companyId) {
                $request->session()->put('current_company_id', $companyId);
            }
        }

        // If a company id is supplied, validate and switch to it.
        //
        // Read with input(), not query(). has() consults $request->all() —
        // query string AND request body — while query() reads the query string
        // alone. A POST carrying company_id in its body therefore made the
        // condition true with a NULL value, and canAccessCompany(null) returns
        // true unconditionally for an account owner, so the branch wrote
        // current_company_id = null into the session.
        //
        // HasCompanyScope then took its is_account_owner early return and left
        // the query unscoped: the owner's chosen company filter was gone and
        // every company in the account was pooled together, with nothing on
        // screen saying so. Reachable from users/create, users/edit and
        // sites/index, all of which post company_id in the body.
        //
        // The empty check matters as much as input(): without it, a form that
        // posts an empty company_id clears the selection the same way.
        $requestedCompanyId = $request->input('company_id');

        if ($requestedCompanyId !== null && $requestedCompanyId !== '') {
            if (Auth::check() && Auth::user()->canAccessCompany($requestedCompanyId)) {
                $companyId = $requestedCompanyId;
                $request->session()->put('current_company_id', $companyId);

                // Remembered as well as sessioned, so a company reached by URL
                // is still the one they land in after signing back in.
                Auth::user()->rememberCompany($companyId);
            }
        }

        // Set company context for the application
        if ($companyId) {
            $company = Company::find($companyId);
            if ($company && $company->is_active) {
                app()->instance('current_company', $company);
                app()->instance('current_company_id', $companyId);
            }
        }

        return $next($request);
    }
}

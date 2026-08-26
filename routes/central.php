<?php

use App\Http\Controllers\Platform\SessionController;
use App\Http\Controllers\Platform\TenantController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Central Routes
|--------------------------------------------------------------------------
|
| Served on the central domains listed in TENANCY_CENTRAL_DOMAINS — the
| marketing page and the back-office.
|
| No client data is reachable from here. The application itself lives in
| routes/web.php, which routes/tenant.php loads behind subdomain
| identification, so signing in to a workspace happens on its own address.
|
| Every route here MUST carry a domain constraint. Laravel keys routes by
| method + domain + URI, so an unconstrained '/' here and the unconstrained
| '/' that web.php registers for tenants are the same key — and the tenant
| one, registered later from TenancyServiceProvider's booted() callback,
| silently replaces this one. The constraint keeps the keys distinct and lets
| each host match its own route. It is also what stops /admin answering on a
| client's subdomain.
|
*/

$centralDomains = (array) config('tenancy.central_domains');

/*
 * The landing page is registered for every central domain, and is therefore
 * unnamed: the group runs once per domain and duplicate route names would
 * collide across the iterations.
 */
foreach ($centralDomains as $centralDomain) {
    Route::domain($centralDomain)->group(function () {
        Route::get('/', function () {
            return view('central.welcome');
        });
    });
}

/*
 * The back-office is registered for the FIRST central domain only, because
 * its routes are named and names must be unique. The first entry is the
 * canonical domain — the same one sign-in URLs are built from — so this is
 * where /admin lives. On the other central hosts (localhost, an IP) it simply
 * does not exist.
 */
if ($centralDomains !== []) {
    Route::domain($centralDomains[0])->prefix('admin')->name('platform.')->group(function () {
        // Not behind `guest:platform`: that middleware redirects an
        // already-signed-in user to the app's home, which on the central
        // domain is the marketing page. SessionController::create sends them
        // to the tenant list instead, which is where they meant to go.
        Route::get('login', [SessionController::class, 'create'])->name('login');
        Route::post('login', [SessionController::class, 'store'])->name('login.attempt');

        Route::middleware('auth:platform')->group(function () {
            Route::get('/', [TenantController::class, 'index'])->name('tenants.index');
            Route::post('tenants', [TenantController::class, 'store'])->name('tenants.store');
            Route::post('tenants/{tenant}/suspend', [TenantController::class, 'suspend'])->name('tenants.suspend');
            Route::post('tenants/{tenant}/activate', [TenantController::class, 'activate'])->name('tenants.activate');
            Route::post('logout', [SessionController::class, 'destroy'])->name('logout');
        });
    });
}

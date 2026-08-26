<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Central Routes
|--------------------------------------------------------------------------
|
| Served on the central domains listed in TENANCY_CENTRAL_DOMAINS — the
| marketing site and, later, the admin back-office.
|
| No client data is reachable from here. The application itself lives in
| routes/web.php, which routes/tenant.php loads behind subdomain
| identification, so signing in happens on a client's own address.
|
| Every route here MUST carry a domain constraint. Laravel keys routes by
| method + domain + URI, so an unconstrained '/' here and the unconstrained
| '/' that web.php registers for tenants are the same key — and the tenant
| one, registered later from TenancyServiceProvider's booted() callback,
| silently replaces this one. The constraint keeps the keys distinct and lets
| each host match its own route.
|
| The routes are deliberately unnamed: the group is registered once per
| central domain, and duplicate route names across those iterations would
| collide with each other.
|
*/

foreach ((array) config('tenancy.central_domains') as $centralDomain) {
    Route::domain($centralDomain)->group(function () {
        Route::get('/', function () {
            return view('central.welcome');
        });
    });
}

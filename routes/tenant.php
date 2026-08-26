<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| The application, served on a client's subdomain — acme.example.com.
|
| InitializeTenancyBySubdomain resolves the subdomain to a tenant and swaps
| the database connection before anything else runs. PreventAccessFromCentral
| Domains makes these routes unreachable from the central domain, where no
| tenant is bound and every query would otherwise hit the central database.
|
| routes/web.php is loaded here rather than by bootstrap/app.php, so the whole
| application inherits both. There is no path by which an app route is served
| without a tenant.
|
| Subdomain identification, not domain: clients are provisioned onto
| {slug}.{TENANCY_CENTRAL_DOMAINS}, which needs no DNS change per client
| because the wildcard record already covers it.
|
*/

Route::middleware([
    'web',
    InitializeTenancyBySubdomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    /**
     * Confirms tenant resolution end to end: reachable only on a tenant
     * subdomain, and reports which database the connection actually landed
     * on. Keep it — it is the fastest way to tell a routing problem from a
     * connection problem.
     */
    Route::get('/tenant-health', function () {
        return response()->json([
            'tenant' => tenant('id'),
            'name' => tenant('name'),
            'status' => tenant('status'),
            'database' => DB::connection()->getDatabaseName(),
        ]);
    })->name('tenant.health');

    require __DIR__.'/web.php';
});

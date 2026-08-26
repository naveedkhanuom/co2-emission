<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Routes served on a client's subdomain — acme.example.com. The two
| middleware below do the work: the first resolves the subdomain to a tenant
| and swaps the database connection, the second makes sure these routes are
| unreachable from the central domain, where no tenant is bound.
|
| Subdomain identification, not domain: clients are provisioned onto
| {slug}.{TENANCY_CENTRAL_DOMAINS}, which needs no DNS change per client
| because the wildcard record already covers it.
|
| NOTE: the application's own routes still live in routes/web.php. Moving
| them under this group is the next step, and depends on the tenant/central
| migration split landing first — until a tenant database exists there is
| nothing for these routes to talk to.
|
*/

Route::middleware([
    // The `web` group already resolves the tenant, via
    // InitializeTenancyIfSubdomain prepended in bootstrap/app.php. Repeating
    // an initializer here would boot tenancy twice for one request.
    'web',
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
});

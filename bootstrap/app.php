<?php

use App\Http\Middleware\DemoRestrictAccess;
use App\Http\Middleware\RestrictSidebarAccess;
use App\Http\Middleware\SetCompanyConnection;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        // The CENTRAL domain gets only routes/central.php. The application
        // itself is in routes/web.php, which routes/tenant.php loads behind
        // subdomain identification — so the app is reachable only on a
        // client's own address, never on the central domain.
        web: __DIR__.'/../routes/central.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // 🔒 Spatie Permission Middleware
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        // 🌐 Global Web Middleware
        //
        // Tenancy is NOT here — it is applied in routes/tenant.php, so that
        // central routes stay genuinely tenant-free rather than relying on a
        // middleware deciding to no-op.
        //
        // These three run after it on tenant routes, which is required:
        // SetCompanyConnection reads the companies table, and must read the
        // tenant's rather than the central one. On central routes there is no
        // authenticated user, so each of them returns early.
        $middleware->web([
            SetCompanyConnection::class,
            DemoRestrictAccess::class,
            RestrictSidebarAccess::class,
        ]);

        // ✅ Exclude Zoho webhook from CSRF
        $middleware->validateCsrfTokens(except: [
            'zoho/mail/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();

// use Illuminate\Foundation\Application;
// use Illuminate\Foundation\Configuration\Exceptions;
// use Illuminate\Foundation\Configuration\Middleware;
// use App\Http\Middleware\SetCompanyConnection;

// return Application::configure(basePath: dirname(__DIR__))
//     ->withRouting(
//         web: __DIR__.'/../routes/web.php',
//         commands: __DIR__.'/../routes/console.php',
//         channels: __DIR__.'/../routes/channels.php',
//         health: '/up',
//     )
//     ->withMiddleware(function (Middleware $middleware) {
//         $middleware->alias([
//             'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
//             'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
//             'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
//         ]);
//         $middleware->web([
//             SetCompanyConnection::class,
//         ]);
//     })
//     ->withExceptions(function (Exceptions $exceptions) {
//         //
//     })->create();

// use Illuminate\Foundation\Application;
// use Illuminate\Foundation\Configuration\Exceptions;
// use Illuminate\Foundation\Configuration\Middleware;

// return Application::configure(basePath: dirname(__DIR__))
//     ->withRouting(
//         web: __DIR__ . '/../routes/web.php',
//         // api: __DIR__ . '/../routes/api.php',
//         commands: __DIR__ . '/../routes/console.php',
//         health: '/up',
//     )
//     ->withMiddleware(function (Middleware $middleware) {
//         // ✅ Exclude Zoho webhook from CSRF
//         $middleware->validateCsrfTokens(except: [
//             'zoho/mail/webhook',
//         ]);
//     })
//     ->withExceptions(function (Exceptions $exceptions) {
//         //
//     })
//     ->create();

<?php

use App\Http\Middleware\DemoRestrictAccess;
use App\Http\Middleware\RestrictSidebarAccess;
use App\Http\Middleware\SetCompanyConnection;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

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
        // Deliberately empty of tenant concerns. SetCompanyConnection,
        // DemoRestrictAccess and RestrictSidebarAccess are applied in
        // routes/tenant.php instead, because all three are about a client's
        // users — companies, demo limits, sidebar rules — and none of it
        // means anything on the central domain.
        //
        // They were global until the back-office arrived, and it broke
        // immediately: RestrictSidebarAccess calls hasRole() on the signed-in
        // user, and platform staff authenticate on a different guard against
        // a table with no roles at all. Scoping them to tenant routes fixes
        // that at the cause rather than teaching each one to recognise a user
        // it should never have been handed.

        // ✅ Exclude Zoho webhook from CSRF
        $middleware->validateCsrfTokens(except: [
            'zoho/mail/webhook',
        ]);

        // Unauthenticated users are sent to the right sign-in screen for where
        // they are. Without this, a guest hitting the back-office would be
        // redirected to route('login') — the TENANT login, which is registered
        // inside the tenant route group and does not answer on the central
        // domain at all.
        $middleware->redirectGuestsTo(function (Request $request) {
            return $request->is('admin', 'admin/*')
                ? route('platform.login')
                : route('login');
        });
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

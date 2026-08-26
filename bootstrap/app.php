<?php

use App\Http\Middleware\DemoRestrictAccess;
use App\Http\Middleware\InitializeTenancyIfSubdomain;
use App\Http\Middleware\RestrictSidebarAccess;
use App\Http\Middleware\SetCompanyConnection;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
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
        // Tenancy resolves FIRST. It swaps the database connection, and
        // everything after it — company scoping, demo restrictions, sidebar
        // rules — must read from the tenant's database rather than the
        // central one. On a non-subdomain host it is a no-op, which is what
        // keeps the central app serving while the data migration is pending.
        $middleware->web(
            prepend: [
                InitializeTenancyIfSubdomain::class,
            ],
            append: [
                SetCompanyConnection::class,
                DemoRestrictAccess::class,
                RestrictSidebarAccess::class,
            ],
        );

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

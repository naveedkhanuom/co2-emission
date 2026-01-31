<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\SetCompanyConnection;
use App\Http\Middleware\DemoRestrictAccess;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
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
        $middleware->web([
            SetCompanyConnection::class,
            DemoRestrictAccess::class,
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

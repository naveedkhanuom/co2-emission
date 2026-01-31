<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Demo Mode
    |--------------------------------------------------------------------------
    |
    | When enabled (DEMO_MODE=true in .env), users will see "You don't have
    | permission" when they click on any route listed in restricted_routes.
    | Use this when giving the system to someone for a demo.
    |
    */

    'enabled' => env('DEMO_MODE', false),

    /*
    |--------------------------------------------------------------------------
    | Restricted Routes (Demo Mode)
    |--------------------------------------------------------------------------
    |
    | Route names that are blocked in demo mode. When a user visits these
    | routes, they will see a friendly "no permission" message instead.
    |
    | - Use exact route name: 'users.index' blocks only that route.
    | - Use prefix (ending with .): 'users.' blocks users.index, users.create,
    |   users.edit, etc.
    |
    | Add or remove route names below to control what is hidden during demo.
    |
    */

    'restricted_routes' => [
        // Settings / Admin (often restricted in demo)
        'users.index',
        'users.create',
        'users.edit',
        'users.data',
        'roles.index',
        'roles.create',
        'roles.edit',
        'companies.index',
        'companies.show',
        'companies.edit',
        'companies.store',
        'companies.update',
        'companies.destroy',

        // Sensitive data (customize as needed)
        'import_history.index',
        'import_history.data',
        'import_history.show',
        'import_history.destroy',
        'import_history.bulk_action',

        // Add more route names or prefixes (e.g. 'reports.ghg_protocol', 'targets.')
        // 'reports.ghg_protocol',
        // 'targets.',
    ],

];

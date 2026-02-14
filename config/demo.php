<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Demo Mode (per-user)
    |--------------------------------------------------------------------------
    |
    | Restriction applies only to users marked as "Demo user" (is_demo_user = true).
    | When enabled (DEMO_MODE=true), those users cannot access routes listed in
    | restricted_routes and see a friendly "no permission" message. Other users
    | have full access. Set to false to disable all demo restrictions globally.
    |
    */

    'enabled' => env('DEMO_MODE', true),

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
        'import_history.download',
        'import_history.retry',
        'import_history.cancel',
        'import_history.export',
        'import_history.report',
        'import_history.export_logs',

        // Add more route names or prefixes (e.g. 'reports.ghg_protocol', 'targets.')
        // 'reports.ghg_protocol',
        // 'targets.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Restrictable Sidebar Options (per demo user)
    |--------------------------------------------------------------------------
    |
    | When editing a demo user, admin can check which of these sidebar sections
    | are restricted (link shows with lock; click shows no permission). Key is
    | route prefix (with .) or exact route name; value is label in the form.
    |
    */

    'restrictable_sidebar_options' => [
        // Main menu
        'home' => 'Dashboard',
        'emission_records.' => 'Manual Entry & Scope-Based Entry',
        'emissions.import.' => 'Import Data',
        'review_data.' => 'Review Data',
        'utility.' => 'Upload Bills',
        'import_history.' => 'Import History',
        'data_source.' => 'Data Source',
        'reports.' => 'Reports & GHG Protocol Report',
        'targets.' => 'Targets & Goals',
        'scope3.' => 'Scope 3 Emissions',
        'suppliers.' => 'Suppliers',
        'supplier_surveys.' => 'Supplier Surveys',
        'data_quality.' => 'Data Quality',
        // Settings submenu
        'facilities.' => 'Facility / Location',
        'departments.' => 'Department',
        'emission_sources.' => 'Emission Sources',
        'emission_factors.' => 'Emission Factors',
        'countries.' => 'Countries',
        'companies.' => 'Companies',
        'users.' => 'Users',
        'roles.' => 'Roles & Permissions',
    ],

];

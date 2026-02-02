<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sidebar Menu Items (for per-user visibility)
    |--------------------------------------------------------------------------
    |
    | Route name => Label. Used when editing a user to select which sidebar
    | links they can see. When a user has custom allowed_sidebar_routes set,
    | only these checked routes are shown in the sidebar for that user.
    |
    */

    'menu_items' => [
        'main' => [
            'home' => 'Dashboard',
            'emission_records.index' => 'Manual Entry',
            'emission_records.scope_entry' => 'Scope-Based Entry',
            'emissions.import.form' => 'Import Data',
            'review_data.index' => 'Review Data',
            'utility.create' => 'Upload Bills',
            'import_history.index' => 'Import History',
            'data_source.index' => 'Data Source',
            'reports.index' => 'Reports',
            'reports.ghg_protocol' => 'GHG Protocol Report',
            'targets.index' => 'Targets & Goals',
            'scope3.index' => 'Scope 3 Emissions',
            'suppliers.index' => 'Suppliers',
            'supplier_surveys.index' => 'Supplier Surveys',
            'data_quality.index' => 'Data Quality',
        ],
        'settings' => [
            'facilities.index' => 'Facility / Location',
            'departments.index' => 'Department',
            'emission_sources.index' => 'Emission Sources',
            'emission_factors.index' => 'Emission Factors',
            'countries.index' => 'Countries',
            'companies.index' => 'Companies',
            'users.index' => 'Users',
            'roles.index' => 'Roles & Permissions',
        ],
    ],

];

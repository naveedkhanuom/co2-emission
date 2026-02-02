<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Permissions per module: list, add (create), edit, delete.
     */
    public function run(): void
    {
        $permissions = [
            // Dashboard
            'list-dashboard',

            // Roles
            'list-roles',
            'create-role',
            'edit-role',
            'delete-role',

            // Users
            'list-users',
            'create-user',
            'edit-user',
            'delete-user',

            // Companies
            'list-companies',
            'create-company',
            'edit-company',
            'delete-company',

            // Departments
            'list-departments',
            'create-department',
            'edit-department',
            'delete-department',

            // Facilities
            'list-facilities',
            'create-facility',
            'edit-facility',
            'delete-facility',

            // Sites
            'list-sites',
            'create-site',
            'edit-site',
            'delete-site',

            // Emission Sources
            'list-emission-sources',
            'create-emission-source',
            'edit-emission-source',
            'delete-emission-source',

            // Emission Factors
            'list-emission-factors',
            'create-emission-factor',
            'edit-emission-factor',
            'delete-emission-factor',

            // Countries
            'list-countries',
            'create-country',
            'edit-country',
            'delete-country',

            // Emission Records
            'list-emission-records',
            'create-emission-record',
            'edit-emission-record',
            'delete-emission-record',

            // Emissions Import
            'list-emissions-import',
            'create-emissions-import',
            'edit-emissions-import',
            'delete-emissions-import',

            // Import History
            'list-import-history',
            'create-import-history',
            'edit-import-history',
            'delete-import-history',

            // Review Data
            'list-review-data',
            'create-review-data',
            'edit-review-data',
            'delete-review-data',

            // Targets
            'list-targets',
            'create-target',
            'edit-target',
            'delete-target',

            // Scope 3
            'list-scope3',
            'create-scope3',
            'edit-scope3',
            'delete-scope3',

            // Suppliers
            'list-suppliers',
            'create-supplier',
            'edit-supplier',
            'delete-supplier',

            // Supplier Surveys
            'list-supplier-surveys',
            'create-supplier-survey',
            'edit-supplier-survey',
            'delete-supplier-survey',

            // EIO Factors
            'list-eio-factors',
            'create-eio-factor',
            'edit-eio-factor',
            'delete-eio-factor',

            // Data Quality
            'list-data-quality',
            'create-data-quality',
            'edit-data-quality',
            'delete-data-quality',

            // Reports
            'list-reports',
            'create-report',
            'edit-report',
            'delete-report',

            // Utility Bills
            'list-utility-bills',
            'create-utility-bill',
            'edit-utility-bill',
            'delete-utility-bill',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['name' => $name, 'guard_name' => 'web']
            );
        }
    }
}

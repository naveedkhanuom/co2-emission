<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * All permissions (list, create, edit, delete) for every module.
     */
    protected function allPermissions(): array
    {
        return [
            'list-dashboard',
            'list-roles', 'create-role', 'edit-role', 'delete-role',
            'list-users', 'create-user', 'edit-user', 'delete-user',
            'list-companies', 'create-company', 'edit-company', 'delete-company',
            'list-departments', 'create-department', 'edit-department', 'delete-department',
            'list-facilities', 'create-facility', 'edit-facility', 'delete-facility',
            'list-sites', 'create-site', 'edit-site', 'delete-site',
            'list-emission-sources', 'create-emission-source', 'edit-emission-source', 'delete-emission-source',
            'list-emission-factors', 'create-emission-factor', 'edit-emission-factor', 'delete-emission-factor',
            'list-countries', 'create-country', 'edit-country', 'delete-country',
            'list-emission-records', 'create-emission-record', 'edit-emission-record', 'delete-emission-record',
            'list-emissions-import', 'create-emissions-import', 'edit-emissions-import', 'delete-emissions-import',
            'list-import-history', 'create-import-history', 'edit-import-history', 'delete-import-history',
            'list-review-data', 'create-review-data', 'edit-review-data', 'delete-review-data',
            'list-targets', 'create-target', 'edit-target', 'delete-target',
            'list-scope3', 'create-scope3', 'edit-scope3', 'delete-scope3',
            'list-suppliers', 'create-supplier', 'edit-supplier', 'delete-supplier',
            'list-supplier-surveys', 'create-supplier-survey', 'edit-supplier-survey', 'delete-supplier-survey',
            'list-eio-factors', 'create-eio-factor', 'edit-eio-factor', 'delete-eio-factor',
            'list-data-quality', 'create-data-quality', 'edit-data-quality', 'delete-data-quality',
            'list-reports', 'create-report', 'edit-report', 'delete-report',
            'list-utility-bills', 'create-utility-bill', 'edit-utility-bill', 'delete-utility-bill',
        ];
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Super Admin: no explicit permissions (Gate::before in AppServiceProvider grants all)
        Role::firstOrCreate(
            ['name' => 'Super Admin', 'guard_name' => 'web'],
            ['name' => 'Super Admin', 'guard_name' => 'web']
        );

        // Admin: full access to all modules
        $admin = Role::firstOrCreate(
            ['name' => 'Admin', 'guard_name' => 'web'],
            ['name' => 'Admin', 'guard_name' => 'web']
        );
        $admin->syncPermissions($this->allPermissions());

        // Product Manager: all except roles & users management
        $manager = Role::firstOrCreate(
            ['name' => 'Product Manager', 'guard_name' => 'web'],
            ['name' => 'Product Manager', 'guard_name' => 'web']
        );
        $manager->syncPermissions(array_filter($this->allPermissions(), function ($p) {
            return !preg_match('/^(list-roles|create-role|edit-role|delete-role|list-users|create-user|edit-user|delete-user)$/', $p);
        }));

        // User: list + data entry (emissions, import, review, targets, scope3, reports view, utility bills)
        $user = Role::firstOrCreate(
            ['name' => 'User', 'guard_name' => 'web'],
            ['name' => 'User', 'guard_name' => 'web']
        );
        $user->syncPermissions([
            'list-dashboard',
            'list-emission-records', 'create-emission-record', 'edit-emission-record', 'delete-emission-record',
            'list-emissions-import', 'create-emissions-import',
            'list-import-history',
            'list-review-data', 'edit-review-data',
            'list-targets', 'create-target', 'edit-target', 'delete-target',
            'list-scope3',
            'list-suppliers', 'create-supplier', 'edit-supplier', 'delete-supplier',
            'list-supplier-surveys', 'create-supplier-survey', 'edit-supplier-survey', 'delete-supplier-survey',
            'list-eio-factors', 'create-eio-factor', 'edit-eio-factor', 'delete-eio-factor',
            'list-data-quality', 'edit-data-quality',
            'list-reports', 'create-report', 'edit-report', 'delete-report',
            'list-utility-bills', 'create-utility-bill', 'edit-utility-bill', 'delete-utility-bill',
            'list-departments', 'list-facilities', 'list-sites',
            'list-emission-sources', 'list-emission-factors', 'list-countries',
        ]);
    }
}

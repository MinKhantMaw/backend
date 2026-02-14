<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'users.assignRoles',
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
            'roles.assignPermissions',
            'permissions.view',
            'permissions.create',
            'permissions.update',
            'permissions.delete',
            'auth.sidebar.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'api',
            ]);
        }

        $superAdmin = Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'api',
        ]);
        $superAdmin->syncPermissions(Permission::all());

        $manager = Role::firstOrCreate([
            'name' => 'Manager',
            'guard_name' => 'api',
        ]);
        $manager->syncPermissions([
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'auth.sidebar.view',
        ]);
    }
}

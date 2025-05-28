<?php


namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Define all permissions
        $permissions = [
            'create', 'read', 'update', 'delete',
            'manage_users', 'system_config', 'reports'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Define roles with their respective permissions
        $roles = [
            'Super Admin' => ['create', 'read', 'update', 'delete', 'manage_users', 'system_config'],
            'Admin' => ['create', 'read', 'update', 'delete', 'manage_users'],
            'Manager' => ['create', 'read', 'update', 'reports'],
            'Editor' => ['create', 'read', 'update'],
            'Viewer' => ['read'],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($rolePermissions);
        }
    }
}

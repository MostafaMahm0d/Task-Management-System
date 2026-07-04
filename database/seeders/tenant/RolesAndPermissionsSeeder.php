<?php

namespace Database\Seeders\tenant;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $rolePermissions = [
            'tenant_admin' => [
                'project.create', 'task.assign', 'task.move', 'report.view',
                'ViewAny:Project', 'View:Project', 'Create:Project', 'Update:Project', 'Delete:Project', 'DeleteAny:Project',
                'ViewAny:User', 'View:User', 'Create:User', 'Update:User', 'Delete:User', 'DeleteAny:User',
                'ViewAny:Task', 'View:Task', 'Create:Task', 'Update:Task', 'Delete:Task', 'DeleteAny:Task',
                'ViewAny:Label', 'View:Label', 'Create:Label', 'Update:Label', 'Delete:Label', 'DeleteAny:Label',
            ],
            'project_manager' => [
                'project.create', 'task.assign', 'task.move', 'report.view',
                'ViewAny:Project', 'View:Project', 'Create:Project', 'Update:Project',
                'ViewAny:Task', 'View:Task', 'Create:Task', 'Update:Task',
                'ViewAny:Label', 'View:Label', 'Create:Label', 'Update:Label',
            ],
            'employee' => [
                'task.move', 'report.view',
                'ViewAny:Project', 'View:Project',
                'ViewAny:Task', 'View:Task', 'Update:Task',
                'ViewAny:Label', 'View:Label',
            ],
        ];

        collect($rolePermissions)
            ->flatten()
            ->unique()
            ->each(fn (string $name) => Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));

        foreach ($rolePermissions as $role => $permissionNames) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'])
                ->syncPermissions($permissionNames);
        }
    }
}

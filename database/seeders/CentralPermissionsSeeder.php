<?php

namespace Database\Seeders;

use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class CentralPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $crudAffixes = [
            'ViewAny', 'View', 'Create', 'Update', 'Delete', 'DeleteAny',
            'Restore', 'ForceDelete', 'ForceDeleteAny', 'RestoreAny', 'Replicate', 'Reorder',
        ];

        collect(['Tenant', 'User'])
            ->flatMap(fn (string $subject) => collect($crudAffixes)->map(fn (string $affix) => "{$affix}:{$subject}"))
            ->merge(['CreateSuperAdmin:Tenant', 'ManageUsers:Tenant', 'Suspend:Tenant'])
            ->each(fn (string $name) => Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));

        $superAdminRole = Utils::getRoleModel()::whereName(Utils::getSuperAdminName())->first();
        $superAdminRole?->syncPermissions(Utils::getPermissionModel()::all());
    }
}

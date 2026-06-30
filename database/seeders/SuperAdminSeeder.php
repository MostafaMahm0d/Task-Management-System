<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::firstOrCreate(['name' => env('SUPER_ADMIN_ROLE', 'super_admin')]);
        $role->syncPermissions(Permission::all());

        User::updateOrCreate(
            ['email' => env('SUPER_ADMIN_EMAIL', 'admin@example.com')],
            [
                'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
                'password' => bcrypt(env('SUPER_ADMIN_PASSWORD', 'password')),
                'email_verified_at' => now(),
            ]
        )->assignRole($role);
    }
}

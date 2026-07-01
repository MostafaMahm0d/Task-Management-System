<?php

namespace Database\Seeders;

use Database\Seeders\tenant\RolesAndPermissionsSeeder;
use Database\Seeders\tenant\ShieldSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TenantsDatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ShieldSeeder::class,
            RolesAndPermissionsSeeder::class,
        ]);
    }
}

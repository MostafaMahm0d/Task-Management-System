<?php

namespace App\Jobs\Tenant;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class CreateSuperAdminForTenant implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Tenant $tenant) {}

    public function handle(): void
    {
        $password = $this->tenant->run(function () {
            if (User::role('super_admin')->exists()) {
                return null;
            }

            $password = Str::password(20, symbols: false);

            $admin = User::create([
                'name' => ucfirst($this->tenant->id).' Super Admin',
                'email' => "super-admin@{$this->tenant->id}.local",
                'password' => bcrypt($password),
                'email_verified_at' => now(),
            ]);
            $admin->is_protected = true;
            $admin->save();
            $admin->assignRole('super_admin');

            return $password;
        });

        if ($password) {
            $this->tenant->initial_super_admin_password = encrypt($password);
            $this->tenant->save();
        }
    }
}

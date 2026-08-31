<?php

namespace App\Console\Commands\Tenant;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;

class CreateTenantCommand extends Command
{
    protected $signature = 'tenant:create
                            {id : Unique tenant ID (e.g. tms)}
                            {domain : Full domain for this tenant (e.g. tms.localhost)}
                            {--name= : Super Admin name (defaults to "<Id> Super Admin")}
                            {--email= : Super Admin email (defaults to super-admin@<id>.local, distinct from the central super admin)}
                            {--password= : Super Admin password (defaults to a randomly generated password)}';

    protected $description = 'Create a tenant with its domain, database, migrations, and admin user';

    public function handle(): int
    {
        $id = $this->argument('id');
        $domain = trim($this->argument('domain'));

        if (Tenant::find($id)) {
            $this->error("Tenant [{$id}] already exists.");

            return self::FAILURE;
        }

        $this->info("Creating tenant [{$id}]...");

        // Creating the tenant fires the database provisioning pipeline (create
        // database, migrate, seed roles, create a default super admin) synchronously.
        $tenant = Tenant::create(['id' => $id]);
        $this->line('  ✓ Tenant created (database provisioned, roles seeded, super admin created)');

        $tenant->domains()->create(['domain' => $domain]);
        $this->line("  ✓ Domain [{$domain}] registered");

        $email = "super-admin@{$id}.local";
        $encryptedPassword = $tenant->fresh()->initial_super_admin_password;
        $password = $encryptedPassword ? decrypt($encryptedPassword) : null;

        if ($this->option('name') || $this->option('email') || $this->option('password')) {
            $name = $this->option('name') ?? ucfirst($id).' Super Admin';
            $email = $this->option('email') ?? $email;
            $password = $this->option('password') ?? env('SUPER_ADMIN_PASSWORD', 'password');

            $tenant->run(function () use ($name, $email, $password) {
                User::query()->role('super_admin')->delete();

                User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => bcrypt($password),
                    'email_verified_at' => now(),
                ])->assignRole('super_admin');
            });

            $this->line("  ✓ Super Admin user [{$email}] created");
        }

        $appUrl = config('app.url');
        $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?? 'http';
        $port = parse_url($appUrl, PHP_URL_PORT);
        $portSuffix = ($port && ! in_array($port, [80, 443], true)) ? ":{$port}" : '';

        $this->newLine();
        $this->info("Tenant [{$id}] is ready.");
        $this->table(
            ['Key', 'Value'],
            [
                ['Tenant ID', $id],
                ['Domain', $domain],
                ['Super Admin email', $email],
                ['Super Admin password', $password],
                ['Panel URL', "{$scheme}://{$domain}{$portSuffix}/app"],
            ]
        );

        return self::SUCCESS;
    }
}

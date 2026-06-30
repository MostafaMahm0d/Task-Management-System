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
                            {--name= : Admin name (defaults to SUPER_ADMIN_NAME)}
                            {--email= : Admin email (defaults to SUPER_ADMIN_EMAIL)}
                            {--password= : Admin password (defaults to SUPER_ADMIN_PASSWORD)}';

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
        $tenant = Tenant::create(['id' => $id]);
        $this->line('  ✓ Tenant created');

        $tenant->domains()->create(['domain' => $domain]);
        $this->line("  ✓ Domain [{$domain}] registered");

        $name = $this->option('name') ?? env('SUPER_ADMIN_NAME', 'Admin');
        $email = $this->option('email') ?? env('SUPER_ADMIN_EMAIL', 'admin@example.com');
        $password = $this->option('password') ?? env('SUPER_ADMIN_PASSWORD', 'password');

        $this->info('Provisioning database and running migrations...');
        $tenant->run(function () use ($name, $email, $password) {
            $this->line('  ✓ Database ready');

            User::create([
                'name' => $name,
                'email' => $email,
                'password' => bcrypt($password),
                'email_verified_at' => now(),
            ]);

            $this->line("  ✓ Admin user [{$email}] created");
        });

        $this->newLine();
        $this->info("Tenant [{$id}] is ready.");
        $this->table(
            ['Key', 'Value'],
            [
                ['Tenant ID', $id],
                ['Domain', $domain],
                ['Admin email', $email],
                ['Panel URL', "http://{$domain}/app"],
            ]
        );

        return self::SUCCESS;
    }
}

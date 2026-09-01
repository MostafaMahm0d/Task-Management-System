<?php

namespace App\Filament\Central\Resources\Tenants\Pages;

use App\Filament\Central\Resources\Tenants\TenantResource;
use App\Models\Tenant;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $domain = $data['domain'] ?? null;
        unset($data['domain']);

        /** @var Tenant $tenant */
        $tenant = static::getModel()::create($data);

        if ($domain) {
            $tenant->domains()->create(['domain' => static::normalizeDomain($domain)]);
        }

        return $tenant;
    }

    protected static function normalizeDomain(string $domain): string
    {
        $baseDomain = parse_url(config('app.url'), PHP_URL_HOST);

        if (! $baseDomain || $domain === $baseDomain || str_ends_with($domain, ".{$baseDomain}")) {
            return $domain;
        }

        return "{$domain}.{$baseDomain}";
    }

    protected function afterCreate(): void
    {
        /** @var Tenant $tenant */
        $tenant = $this->getRecord()->fresh();

        if (! $tenant->initial_super_admin_password) {
            return;
        }

        try {
            $password = decrypt($tenant->initial_super_admin_password);
        } catch (\Throwable) {
            $tenant->forceFill(['initial_super_admin_password' => null])->save();

            return;
        }

        Notification::make()
            ->title('Tenant super admin email')
            ->body("super-admin@{$tenant->id}.tm")
            ->success()
            ->persistent()
            ->send();

        Notification::make()
            ->title('Tenant super admin password')
            ->body("{$password}\n\nAlso visible on this tenant's page until the password is changed.")
            ->success()
            ->persistent()
            ->send();
    }
}

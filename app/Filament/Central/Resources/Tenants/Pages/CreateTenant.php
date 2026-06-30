<?php

namespace App\Filament\Central\Resources\Tenants\Pages;

use App\Filament\Central\Resources\Tenants\TenantResource;
use App\Models\Tenant;
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
            $tenant->domains()->create(['domain' => $domain]);
        }

        return $tenant;
    }
}

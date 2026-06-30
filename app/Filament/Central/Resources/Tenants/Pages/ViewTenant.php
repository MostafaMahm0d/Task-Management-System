<?php

namespace App\Filament\Central\Resources\Tenants\Pages;

use App\Filament\Central\Resources\Tenants\Actions\CreateTenantSuperAdminAction;
use App\Filament\Central\Resources\Tenants\TenantResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTenant extends ViewRecord
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateTenantSuperAdminAction::make(),
            EditAction::make(),
        ];
    }
}

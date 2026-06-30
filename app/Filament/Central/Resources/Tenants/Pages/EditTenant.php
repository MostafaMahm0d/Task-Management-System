<?php

namespace App\Filament\Central\Resources\Tenants\Pages;

use App\Filament\Central\Resources\Tenants\Actions\CreateTenantSuperAdminAction;
use App\Filament\Central\Resources\Tenants\TenantResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateTenantSuperAdminAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['domain'] = $this->record->domains()->first()?->domain;

        return $data;
    }

    protected function afterSave(): void
    {
        $domain = $this->form->getState()['domain'] ?? null;

        if ($domain) {
            $this->record->domains()->updateOrCreate([], ['domain' => $domain]);
        }
    }
}

<?php

namespace App\Filament\Central\Resources\Tenants\Pages;

use App\Filament\Central\Resources\Tenants\Actions\CreateTenantSuperAdminAction;
use App\Filament\Central\Resources\Tenants\TenantResource;
use App\Models\Tenant;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    protected ?string $pendingDomain = null;

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

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $this->pendingDomain = $data['domain'] ?? null;
        unset($data['domain']);

        $record->update($data);

        return $record;
    }

    protected function afterSave(): void
    {
        /** @var Tenant $tenant */
        $tenant = $this->record;

        if ($this->pendingDomain) {
            $tenant->domains()->updateOrCreate([], ['domain' => $this->pendingDomain]);
        }
    }
}

<?php

namespace App\Filament\Tenant\Resources\Users\Pages;

use App\Filament\Tenant\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->authorize('delete'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var User $user */
        $user = $this->record;

        if ($user->is_protected && ! User::isViaCentralImpersonation()) {
            $data['email'] = $user->email;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var User $user */
        $user = $this->record;

        if ($user->is_protected && ! User::isViaCentralImpersonation() && ! $user->hasRole('super_admin')) {
            $user->syncRoles(['super_admin']);
        }
    }
}

<?php

namespace App\Filament\Central\Resources\Tenants\Actions;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

class CreateTenantSuperAdminAction
{
    public static function make(): Action
    {
        return Action::make('createSuperAdmin')
            ->label('Create Super Admin')
            ->icon(Heroicon::OutlinedUserPlus)
            ->color('success')
            ->authorize('createSuperAdmin')
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),

                TextInput::make('password')
                    ->password()
                    ->required()
                    ->minLength(8),
            ])
            ->action(function (array $data, $record): void {
                tenancy()->initialize($record);

                User::updateOrCreate(
                    ['email' => $data['email']],
                    [
                        'name' => $data['name'],
                        'password' => $data['password'],
                        'email_verified_at' => now(),
                    ]
                )->assignRole(config('filament-shield.super_admin.name'));

                tenancy()->end();
            });
    }
}

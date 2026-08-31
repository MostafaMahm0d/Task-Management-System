<?php

namespace App\Filament\Tenant\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->disabled(fn (?User $record): bool => (bool) $record?->is_protected && ! User::isViaCentralImpersonation())
                    ->helperText(fn (?User $record): ?string => ($record?->is_protected && ! User::isViaCentralImpersonation())
                        ? 'This is the tenant\'s initial super admin so can not change it.'
                        : null),

                TextInput::make('password')
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->minLength(8)
                    ->dehydrated(fn (?string $state): bool => filled($state)),

                Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->disabled(fn (?User $record): bool => (bool) $record?->is_protected && ! User::isViaCentralImpersonation())
                    ->helperText(fn (?User $record): ?string => ($record?->is_protected && ! User::isViaCentralImpersonation())
                        ? 'This is the tenant\'s initial super admin so can not change it.'
                        : null),
            ]);
    }
}

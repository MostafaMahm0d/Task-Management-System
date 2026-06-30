<?php

namespace App\Filament\Central\Resources\Tenants\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('id')
                    ->label('Identifier')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->alphaDash()
                    ->maxLength(100)
                    ->disabledOn('edit'),

                TextInput::make('domain')
                    ->label('Domain')
                    ->required()
                    ->maxLength(255)
                    ->dehydrated(false),
            ]);
    }
}

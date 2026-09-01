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
                    ->helperText('Enter a subdomain (e.g. "ts.ad") to have "'.parse_url(config('app.url'), PHP_URL_HOST).'" appended automatically, or a full custom domain.')
                    ->required()
                    ->maxLength(255),
            ]);
    }
}

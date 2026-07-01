<?php

namespace App\Filament\Tenant\Resources\Projects\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Textarea::make('description')
                    ->columnSpanFull(),

                Select::make('owner_id')
                    ->label('Owner')
                    ->relationship('owner', 'name')
                    ->default(fn () => auth()->id())
                    ->searchable()
                    ->preload()
                    ->required(),
            ]);
    }
}

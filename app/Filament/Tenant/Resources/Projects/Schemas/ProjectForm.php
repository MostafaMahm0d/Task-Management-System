<?php

namespace App\Filament\Tenant\Resources\Projects\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Project details')
                    ->description('The basics that identify this project.')
                    ->icon(Heroicon::OutlinedRectangleStack)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Select::make('owner_id')
                            ->label('Owner')
                            ->relationship('owner', 'name')
                            ->default(fn () => auth()->id())
                            ->searchable()
                            ->preload()
                            ->required(),

                        Textarea::make('description')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}

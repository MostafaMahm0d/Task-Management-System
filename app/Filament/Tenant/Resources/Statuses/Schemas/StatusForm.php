<?php

namespace App\Filament\Tenant\Resources\Statuses\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class StatusForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                ColorPicker::make('color')
                    ->required(),

                Toggle::make('is_default')
                    ->label('Default status for new tasks')
                    ->helperText('Only one status can be the default; setting this will unset it from any other status.'),

                Toggle::make('is_completed')
                    ->label('Counts as completed')
                    ->helperText('Tasks in this status satisfy dependencies of other tasks.'),
            ]);
    }
}

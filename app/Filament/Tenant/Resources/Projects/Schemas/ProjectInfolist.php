<?php

namespace App\Filament\Tenant\Resources\Projects\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ProjectInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),

                TextEntry::make('owner.name')
                    ->label('Owner'),

                TextEntry::make('description')
                    ->columnSpanFull(),

                TextEntry::make('created_at')
                    ->dateTime(),
            ]);
    }
}

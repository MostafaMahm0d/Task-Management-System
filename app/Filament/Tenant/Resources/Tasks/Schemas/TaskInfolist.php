<?php

namespace App\Filament\Tenant\Resources\Tasks\Schemas;

use App\Models\Task;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TaskInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('title'),

                TextEntry::make('project.name')
                    ->label('Project'),

                TextEntry::make('status')
                    ->badge(),

                TextEntry::make('priority')
                    ->badge(),

                TextEntry::make('assignee.name')
                    ->label('Assignee')
                    ->placeholder('Unassigned'),

                TextEntry::make('reporter.name')
                    ->label('Reporter'),

                TextEntry::make('due_date')
                    ->date(),

                TextEntry::make('estimated_hours')
                    ->suffix(' hrs'),

                IconEntry::make('is_blocked')
                    ->label('Blocked')
                    ->state(fn (Task $record): bool => $record->isBlocked())
                    ->boolean(),

                TextEntry::make('dependsOn.title')
                    ->label('Depends on'),

                TextEntry::make('labels.name')
                    ->label('Labels')
                    ->badge(),

                TextEntry::make('description')
                    ->columnSpanFull(),
            ]);
    }
}

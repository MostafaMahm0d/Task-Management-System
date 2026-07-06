<?php

namespace App\Filament\Tenant\Resources\Tasks\Schemas;

use App\Models\Task;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class TaskInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(fn (Task $record): string => $record->title)
                    ->description(fn (Task $record): ?string => $record->project?->name)
                    ->icon(Heroicon::OutlinedRectangleStack)
                    ->schema([
                        TextEntry::make('status.name')
                            ->label('Status')
                            ->badge()
                            ->color(fn (Task $record): string => $record->status->color),

                        TextEntry::make('priority')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                Task::PRIORITY_URGENT => 'danger',
                                Task::PRIORITY_HIGH => 'warning',
                                Task::PRIORITY_LOW => 'gray',
                                default => 'info',
                            }),

                        IconEntry::make('is_blocked')
                            ->label('Blocked')
                            ->state(fn (Task $record): bool => $record->isBlocked())
                            ->boolean(),

                        TextEntry::make('due_date')
                            ->date()
                            ->icon(Heroicon::OutlinedCalendarDays)
                            ->placeholder('No due date'),

                        TextEntry::make('description')
                            ->placeholder('No description provided.')
                            ->columnSpanFull(),
                    ])
                    ->columns(4),

                Section::make('People')
                    ->icon(Heroicon::OutlinedUsers)
                    ->schema([
                        TextEntry::make('assignee.name')
                            ->label('Assignee')
                            ->icon(Heroicon::OutlinedUserCircle)
                            ->placeholder('Unassigned'),

                        TextEntry::make('reporter.name')
                            ->label('Reporter')
                            ->icon(Heroicon::OutlinedUserCircle),

                        TextEntry::make('estimated_hours')
                            ->label('Estimate')
                            ->suffix(' hrs')
                            ->placeholder('Not estimated'),
                    ])
                    ->columns(3),

                Section::make('Labels & dependencies')
                    ->icon(Heroicon::OutlinedTag)
                    ->schema([
                        TextEntry::make('labels.name')
                            ->label('Labels')
                            ->badge()
                            ->placeholder('No labels'),

                        TextEntry::make('dependsOn.title')
                            ->label('Depends on')
                            ->badge()
                            ->color('gray')
                            ->placeholder('No dependencies'),
                    ])
                    ->columns(2)
                    ->collapsed(fn (Task $record): bool => $record->labels->isEmpty() && $record->dependsOn->isEmpty()),
            ]);
    }
}

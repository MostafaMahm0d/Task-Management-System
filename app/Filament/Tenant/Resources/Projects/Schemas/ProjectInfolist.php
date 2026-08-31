<?php

namespace App\Filament\Tenant\Resources\Projects\Schemas;

use App\Models\Project;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ProjectInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(fn (Project $record): string => $record->name)
                    ->description(fn (Project $record): ?string => $record->description)
                    ->icon(Heroicon::OutlinedRectangleStack)
                    ->schema([
                        TextEntry::make('owner.name')
                            ->label('Owner')
                            ->icon(Heroicon::OutlinedUserCircle),

                        TextEntry::make('members_count')
                            ->label('Members')
                            ->state(fn (Project $record): int => $record->members()->count())
                            ->icon(Heroicon::OutlinedUsers),

                        TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime(),
                    ])
                    ->columns(3),

                Section::make('Task progress')
                    ->icon(Heroicon::OutlinedChartBar)
                    ->schema([
                        TextEntry::make('tasks_total')
                            ->label('Total tasks')
                            ->state(fn (Project $record): int => $record->tasks()->count()),

                        TextEntry::make('tasks_completed')
                            ->label('Completed')
                            ->state(fn (Project $record): int => $record->tasks()->whereHas('status', fn ($query) => $query->where('is_completed', true))->count())
                            ->color('success'),

                        TextEntry::make('tasks_overdue')
                            ->label('Overdue')
                            ->state(fn (Project $record): int => $record->tasks()
                                ->whereDate('due_date', '<', now())
                                ->whereHas('status', fn ($query) => $query->where('is_completed', false))
                                ->count())
                            ->color(fn (int $state): string => $state > 0 ? 'danger' : 'gray'),

                        TextEntry::make('tasks_progress')
                            ->label('Progress')
                            ->state(function (Project $record): string {
                                $total = $record->tasks()->count();

                                if ($total === 0) {
                                    return 'No tasks yet';
                                }

                                $completed = $record->tasks()->whereHas('status', fn ($query) => $query->where('is_completed', true))->count();

                                return round(($completed / $total) * 100).'% complete';
                            })
                            ->badge()
                            ->color('primary')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ]);
    }
}

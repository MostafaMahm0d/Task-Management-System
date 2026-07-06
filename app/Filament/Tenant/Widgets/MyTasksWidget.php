<?php

namespace App\Filament\Tenant\Widgets;

use App\Filament\Tenant\Resources\Tasks\TaskResource;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class MyTasksWidget extends TableWidget
{
    protected static ?string $heading = 'My Tasks';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Task::query()
                ->where('assignee_id', auth()->id())
                ->whereHas('status', fn ($query) => $query->where('is_completed', false)))
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->url(fn (Task $record): string => TaskResource::getUrl('view', ['record' => $record])),

                TextColumn::make('project.name')
                    ->label('Project'),

                TextColumn::make('status.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn (Task $record): string => $record->status->color),

                TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Task::PRIORITY_URGENT => 'danger',
                        Task::PRIORITY_HIGH => 'warning',
                        Task::PRIORITY_LOW => 'gray',
                        default => 'info',
                    }),

                TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->color(fn (Task $record): ?string => $record->due_date?->isPast() ? 'danger' : null),
            ])
            ->defaultSort('due_date')
            ->paginated([5, 10])
            ->headerActions([
                Action::make('viewAll')
                    ->label('View all')
                    ->url(fn (): string => TaskResource::getUrl('my-tasks')),
            ]);
    }
}

<?php

namespace App\Filament\Tenant\Widgets;

use App\Filament\Tenant\Resources\Projects\Resources\Tasks\TaskResource;
use App\Models\Task;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class MyTasksWidget extends TableWidget
{
    use HasWidgetShield;

    protected static ?int $sort = 6;

    public function table(Table $table): Table
    {
        $isAdmin = auth()->user()->can('task.manageAll');

        return $table
            ->heading($isAdmin ? 'All Open Tasks' : 'My Tasks')
            ->query(fn (): Builder => Task::query()
                ->when(! $isAdmin, fn (Builder $query) => $query->where('assignee_id', auth()->id()))
                ->whereHas('status', fn ($query) => $query->where('is_completed', false)->where('is_cancelled', false)))
            ->recordUrl(fn (Task $record): string => TaskResource::getUrl('view', ['project' => $record->project, 'record' => $record]))
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->url(fn (Task $record): string => TaskResource::getUrl('view', ['project' => $record->project, 'record' => $record])),

                TextColumn::make('project.name')
                    ->label('Project'),

                TextColumn::make('assignee.name')
                    ->label('Assignee')
                    ->placeholder('Unassigned')
                    ->visible($isAdmin),

                TextColumn::make('status.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn (Task $record): string => $record->status->color),

                TextColumn::make('priority')
                    ->badge(),

                TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->color(fn (Task $record): ?string => $record->due_date?->isPast() ? 'danger' : null),
            ])
            ->defaultSort('due_date')
            ->paginated([5, 10]);
    }
}

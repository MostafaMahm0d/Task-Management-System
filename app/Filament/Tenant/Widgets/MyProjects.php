<?php

namespace App\Filament\Tenant\Widgets;

use App\Filament\Tenant\Resources\Projects\ProjectResource;
use App\Models\Project;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class MyProjects extends TableWidget
{
    use HasWidgetShield;

    protected static ?int $sort = 5;

    public function table(Table $table): Table
    {
        $isAdmin = auth()->user()->can('project.manageAll');

        return $table
            ->heading($isAdmin ? 'All Projects' : 'My Projects')
            ->query(fn (): Builder => Project::query()
                ->withCount([
                    'tasks',
                    'tasks as completed_tasks_count' => fn (Builder $query) => $query->whereHas('status', fn ($query) => $query->where('is_completed', true)),
                ])
                ->when(
                    ! $isAdmin,
                    fn (Builder $query) => $query->where(
                        fn (Builder $query) => $query
                            ->where('owner_id', auth()->id())
                            ->orWhereHas('members', fn (Builder $query) => $query->whereKey(auth()->id()))
                    )
                ))
            ->recordUrl(fn (Project $record): string => ProjectResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('name')
                    ->searchable(),

                TextColumn::make('role')
                    ->label($isAdmin ? 'Owner' : 'Role')
                    ->state(function (Project $record) use ($isAdmin): string {
                        if ($isAdmin) {
                            return $record->owner?->name ?? 'Unknown';
                        }

                        return $record->owner_id === auth()->id() ? 'Owner' : 'Member';
                    })
                    ->badge(! $isAdmin)
                    ->color(fn (string $state): string => $state === 'Owner' ? 'primary' : 'gray'),

                TextColumn::make('tasks_progress')
                    ->label('Progress')
                    ->state(function (Project $record): string {
                        if ($record->tasks_count === 0) {
                            return 'No tasks';
                        }

                        return "{$record->completed_tasks_count}/{$record->tasks_count} (".round(($record->completed_tasks_count / $record->tasks_count) * 100).'%)';
                    }),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([5, 10]);
    }
}

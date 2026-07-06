<?php

namespace App\Filament\Tenant\Widgets;

use App\Filament\Tenant\Resources\Projects\ProjectResource;
use App\Models\Project;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class MyProjects extends TableWidget
{
    protected static ?string $heading = 'My Projects';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Project::query()
                ->where(
                    fn (Builder $query) => $query
                        ->where('owner_id', auth()->id())
                        ->orWhereHas('members', fn (Builder $query) => $query->whereKey(auth()->id()))
                ))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->url(fn (Project $record): string => ProjectResource::getUrl('view', ['record' => $record])),

                TextColumn::make('role')
                    ->state(fn (Project $record): string => $record->owner_id === auth()->id() ? 'Owner' : 'Member')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Owner' ? 'primary' : 'gray'),

                TextColumn::make('tasks_progress')
                    ->label('Progress')
                    ->state(function (Project $record): string {
                        $total = $record->tasks()->count();

                        if ($total === 0) {
                            return 'No tasks';
                        }

                        $completed = $record->tasks()->whereHas('status', fn ($query) => $query->where('is_completed', true))->count();

                        return "{$completed}/{$total} (".round(($completed / $total) * 100).'%)';
                    }),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([5, 10]);
    }
}

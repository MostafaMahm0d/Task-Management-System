<?php

namespace App\Filament\Tenant\Widgets;

use App\Filament\Tenant\Resources\Tasks\TaskResource;
use App\Models\Task;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class UpcomingDeadlines extends TableWidget
{
    use HasWidgetShield;

    protected static ?string $heading = 'Upcoming Deadlines';

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Task::query()
                ->visibleTo(auth()->user())
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [now()->startOfDay(), now()->addDays(7)->endOfDay()])
                ->whereHas('status', fn ($query) => $query->where('is_completed', false)->where('is_cancelled', false)))
            ->recordUrl(fn (Task $record): string => TaskResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('title')
                    ->searchable(),

                TextColumn::make('project.name')
                    ->label('Project'),

                TextColumn::make('assignee.name')
                    ->label('Assignee')
                    ->placeholder('Unassigned'),

                TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->badge()
                    ->color(fn (Task $record): string => match (true) {
                        $record->due_date->isToday() => 'danger',
                        $record->due_date->diffInDays(now()) <= 2 => 'warning',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('due_date')
            ->paginated([5, 10])
            ->emptyStateHeading('Nothing due soon')
            ->emptyStateDescription('No tasks are due in the next 7 days across your projects.')
            ->emptyStateIcon(Heroicon::OutlinedCalendarDays);
    }
}

<?php

namespace App\Filament\Tenant\Widgets;

use App\Filament\Tenant\Resources\Projects\ProjectResource;
use App\Filament\Tenant\Resources\Tasks\TaskResource;
use App\Models\Activity;
use App\Models\Project;
use App\Models\Task;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentActivity extends TableWidget
{
    use HasWidgetShield;

    protected static ?string $heading = 'Recent Activity';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = auth()->user();

        $projectIds = Project::query()
            ->when(
                ! $user->can('project.manageAll'),
                fn (Builder $query) => $query->where(
                    fn (Builder $query) => $query
                        ->where('owner_id', $user->id)
                        ->orWhereHas('members', fn (Builder $query) => $query->whereKey($user->id))
                )
            )
            ->pluck('id');

        $taskIds = Task::query()->visibleTo($user)->pluck('id');

        return $table
            ->query(fn (): Builder => Activity::query()
                ->with(['causer', 'subject'])
                ->where(
                    fn (Builder $query) => $query
                        ->where(fn (Builder $query) => $query->where('subject_type', Project::class)->whereIn('subject_id', $projectIds))
                        ->orWhere(fn (Builder $query) => $query->where('subject_type', Task::class)->whereIn('subject_id', $taskIds))
                )
                ->latest('created_at'))
            ->recordUrl(function (Activity $record): ?string {
                if ($record->subject === null) {
                    return null;
                }

                return match ($record->subject_type) {
                    Project::class => ProjectResource::getUrl('view', ['record' => $record->subject]),
                    Task::class => TaskResource::getUrl('view', ['record' => $record->subject]),
                    default => null,
                };
            })
            ->columns([
                IconColumn::make('event')
                    ->label('')
                    ->icon(fn (Activity $record): string => $record->eventIcon())
                    ->color(fn (Activity $record): string => $record->eventColor()),

                TextColumn::make('feed')
                    ->label('Activity')
                    ->state(fn (Activity $record): string => $record->feedSentence())
                    ->wrap(),

                TextColumn::make('subject')
                    ->label('On')
                    ->state(fn (Activity $record): string => $record->subjectLabel()),

                TextColumn::make('created_at')
                    ->label('When')
                    ->since()
                    ->tooltip(fn (Activity $record): string => $record->created_at->format('M j, Y \a\t g:ia')),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([5, 10])
            ->emptyStateHeading('No activity yet')
            ->emptyStateDescription('Actions on your projects and tasks will show up here.')
            ->emptyStateIcon(Heroicon::OutlinedBolt);
    }
}

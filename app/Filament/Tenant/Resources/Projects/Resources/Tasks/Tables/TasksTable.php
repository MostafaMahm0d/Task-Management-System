<?php

namespace App\Filament\Tenant\Resources\Projects\Resources\Tasks\Tables;

use App\Enums\TaskPriority;
use App\Filament\Tenant\Resources\Projects\Resources\Tasks\RelationManagers\SubtasksRelationManager;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Relaticle\Comments\Filament\Actions\CommentsTableAction;

class TasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['parent', 'subtasks.status']))
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn (Task $record): string => $record->status->color)
                    ->sortable(),

                TextColumn::make('priority')
                    ->badge()
                    ->sortable(),

                TextColumn::make('assignee.name')
                    ->label('Assignee')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('labels.name')
                    ->label('Labels')
                    ->badge(),

                TextColumn::make('due_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('parent.title')
                    ->label('Parent task')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('subtasks_progress')
                    ->label('Subtasks')
                    ->state(fn (Task $record): ?string => $record->subtasks->isEmpty()
                        ? null
                        : "{$record->subtasks->where('status.is_completed', true)->count()}/{$record->subtasks->count()}")
                    ->placeholder('—'),
            ])
            ->groups([
                Group::make('parent.title')
                    ->label('Parent task')
                    ->getDescriptionFromRecordUsing(function (Task $record, Table $table): ?Htmlable {
                        if (! $parent = $record->parent) {
                            return null;
                        }

                        return $table->getAction('addSubtask')?->getClone()->record($parent);
                    }),
            ])
            ->filters([
                SelectFilter::make('status_id')
                    ->label('Status')
                    ->relationship('status', 'name'),

                SelectFilter::make('priority')
                    ->options(TaskPriority::class),

                SelectFilter::make('assignee')
                    ->relationship('assignee', 'name')
                    ->searchable(),

                SelectFilter::make('reporter')
                    ->relationship('reporter', 'name')
                    ->searchable(),

                SelectFilter::make('labels')
                    ->relationship('labels', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),

                Filter::make('unassigned')
                    ->label('Unassigned')
                    ->query(fn (Builder $query): Builder => $query->whereNull('assignee_id')),

                Filter::make('due_date')
                    ->schema([
                        DatePicker::make('due_from')
                            ->native(false),
                        DatePicker::make('due_until')
                            ->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['due_from'], fn (Builder $query, $date): Builder => $query->whereDate('due_date', '>=', $date))
                        ->when($data['due_until'], fn (Builder $query, $date): Builder => $query->whereDate('due_date', '<=', $date)))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['due_from'] ?? null) {
                            $indicators[] = 'Due from '.$data['due_from'];
                        }

                        if ($data['due_until'] ?? null) {
                            $indicators[] = 'Due until '.$data['due_until'];
                        }

                        return $indicators;
                    }),

                Filter::make('overdue')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('due_date', '<', today())
                        ->whereHas('status', fn (Builder $query) => $query->where('is_completed', false)->where('is_cancelled', false))),

                Filter::make('blocked')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereHas('dependsOn', fn (Builder $query) => $query
                            ->whereHas('status', fn (Builder $query) => $query->where('is_completed', false)->where('is_cancelled', false)))),

                TernaryFilter::make('parent_task_id')
                    ->label('Subtask')
                    ->placeholder('All tasks')
                    ->trueLabel('Subtasks only')
                    ->falseLabel('Top-level tasks only')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('parent_task_id'),
                        false: fn (Builder $query): Builder => $query->whereNull('parent_task_id'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('addSubtask')
                        ->label('Add subtask')
                        ->icon(Heroicon::OutlinedPlusCircle)
                        ->visible(fn (Task $record): bool => ! $record->isSubtask())
                        ->modalHeading(fn (Task $record): string => "Add subtask to \"{$record->title}\"")
                        ->schema(fn (Task $record): array => SubtasksRelationManager::subtaskFields($record))
                        ->action(function (array $data, Task $record): void {
                            $record->subtasks()->create([
                                ...$data,
                                'project_id' => $record->project_id,
                                'reporter_id' => auth()->id(),
                            ]);
                        })
                        ->successNotificationTitle('Subtask created'),
                    CommentsTableAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

<?php

namespace App\Filament\Tenant\Resources\Projects\Resources\Tasks\Pages;

use App\Events\TaskStatusUpdated;
use App\Filament\Tenant\Resources\Projects\Resources\Tasks\Actions\QuickViewAction;
use App\Filament\Tenant\Resources\Projects\Resources\Tasks\TaskResource;
use App\Models\Status;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;
use Wezlo\FilamentKanban\Concerns\HasKanbanBoard;
use Wezlo\FilamentKanban\KanbanBoard;

class Board extends ListRecords
{
    use HasKanbanBoard;

    protected static string $resource = TaskResource::class;
    protected Width | string | null $maxContentWidth = Width::Full;
    public function kanban(KanbanBoard $kanban): KanbanBoard
    {
        return $kanban
            ->relationshipColumn('status', 'name', Status::class, orderAttribute: 'position', colorAttribute: 'color')
            ->modifyQueryUsing(fn(Builder $query): Builder => TaskResource::scopeEloquentQueryToParent($query, $this->getParentRecord()))
            ->cardTitle(fn(Task $record): string => $record->title)
            ->cardDescription(fn(Task $record): ?string => $record->assignee?->name)
            ->cardBadges(fn(Task $record): array => [
                [
                    'label' => $record->priority->getLabel(),
                    'color' => $record->priority->getColor(),
                ],
            ])
            ->cardAction(QuickViewAction::make())
            ->canMove(function (Task $record, string $fromStatusId, string $toStatusId): bool {
                if (! auth()->user()?->can('task.move')) {
                    return false;
                }

                return Status::find((int) $fromStatusId)?->allowsTransitionTo((int) $toStatusId) ?? true;
            })
            ->onRecordMoved(function (Task $record, string $fromStatusId, string $toStatusId) {
                event(new TaskStatusUpdated($record, (int) $fromStatusId));
            });
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('list')
                ->label('List view')
                ->url(fn(): string => TaskResource::getUrl('index', ['project' => $this->getParentRecord()])),
        ];
    }
}

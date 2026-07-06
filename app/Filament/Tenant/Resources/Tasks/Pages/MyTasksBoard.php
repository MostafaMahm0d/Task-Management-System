<?php

namespace App\Filament\Tenant\Resources\Tasks\Pages;

use App\Events\TaskStatusUpdated;
use App\Filament\Tenant\Resources\Tasks\TaskResource;
use App\Models\Status;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Wezlo\FilamentKanban\Concerns\HasKanbanBoard;
use Wezlo\FilamentKanban\KanbanBoard;

class MyTasksBoard extends ListRecords
{
    use HasKanbanBoard;

    protected static string $resource = TaskResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'My Tasks';
    }

    public function getBreadcrumb(): string
    {
        return 'My Tasks';
    }

    public function kanban(KanbanBoard $kanban): KanbanBoard
    {
        return $kanban
            ->relationshipColumn('status', 'name', Status::class, orderAttribute: 'position', colorAttribute: 'color')
            ->modifyQueryUsing(fn ($query) => $query->where('assignee_id', auth()->id()))
            ->cardTitle(fn (Task $record): string => $record->title)
            ->cardDescription(fn (Task $record): ?string => $record->project?->name)
            ->cardBadges(fn (Task $record): array => [
                [
                    'label' => ucfirst($record->priority),
                    'color' => match ($record->priority) {
                        Task::PRIORITY_URGENT => 'danger',
                        Task::PRIORITY_HIGH => 'warning',
                        Task::PRIORITY_LOW => 'gray',
                        default => 'info',
                    },
                ],
            ])
            ->canMove(fn (): bool => (bool) auth()->user()?->can('task.move'))
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
                ->url(fn (): string => TaskResource::getUrl('my-tasks')),
        ];
    }
}

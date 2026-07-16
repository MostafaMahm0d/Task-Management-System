<?php

namespace App\Filament\Tenant\Resources\Tasks\Pages;

use App\Events\TaskStatusUpdated;
use App\Filament\Tenant\Resources\Tasks\Schemas\TaskInfolist;
use App\Filament\Tenant\Resources\Tasks\TaskResource;
use App\Models\Status;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Wezlo\FilamentKanban\Concerns\HasKanbanBoard;
use Wezlo\FilamentKanban\KanbanBoard;

class Board extends ListRecords
{
    use HasKanbanBoard;

    protected static string $resource = TaskResource::class;

    public function kanban(KanbanBoard $kanban): KanbanBoard
    {
        return $kanban
            ->relationshipColumn('status', 'name', Status::class, orderAttribute: 'position', colorAttribute: 'color')
            ->cardTitle(fn (Task $record): string => $record->title)
            ->cardDescription(fn (Task $record): ?string => $record->assignee?->name)
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
            ->cardAction(
                Action::make('viewTask')
                    ->modalHeading(fn (Task $record): string => $record->title)
                    ->schema(fn (Schema $schema): Schema => TaskInfolist::configure($schema))
                    ->modalWidth(Width::SixExtraLarge)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
            )
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
                ->url(fn (): string => TaskResource::getUrl('index')),
        ];
    }
}

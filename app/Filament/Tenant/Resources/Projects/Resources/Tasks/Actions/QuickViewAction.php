<?php

namespace App\Filament\Tenant\Resources\Projects\Resources\Tasks\Actions;

use App\Filament\Tenant\Resources\Projects\Resources\Tasks\Schemas\TaskInfolist;
use App\Filament\Tenant\Resources\Projects\Resources\Tasks\TaskResource;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Livewire\Component;
use Relaticle\Comments\Filament\Actions\CommentsAction;

class QuickViewAction 
{
    public static function make(): Action
    {
        return Action::make('quickView')
            ->label('Quick view')
            ->icon(Heroicon::OutlinedEye)
            ->color('gray')
            ->authorize('view')
            ->modalHeading(fn (Task $record): string => $record->title)
            ->modalWidth(Width::Full)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->schema(fn (Task $record, Component $livewire): array => [
                Actions::make([
                    CommentsAction::make()
                        ->overlayParentActions(),
                ])->alignEnd(),
                ...TaskInfolist::components(),
                ...self::getRelationManagerComponents($record, $livewire::class),
            ]);
    }

    /**
     * @param  class-string  $pageClass
     * @return array<int, Tabs>
     */
    protected static function getRelationManagerComponents(Task $record, string $pageClass): array
    {
        /** @var array<int, class-string<RelationManager>> $relationManagers */
        $relationManagers = TaskResource::getRelations();

        $tabs = collect($relationManagers)
            ->filter(fn (string $relationManager): bool => $relationManager::canViewForRecord($record, $pageClass))
            ->map(fn (string $relationManager): Tab => $relationManager::getTabComponent($record, $pageClass)
                ->schema(fn (): array => [
                    Livewire::make($relationManager, [
                        'ownerRecord' => $record,
                        'pageClass' => $pageClass,
                    ])->key($relationManager.'-'.$record->getKey()),
                ]))
            ->values();

        if ($tabs->isEmpty()) {
            return [];
        }

        return [
            Tabs::make()
                ->key('taskRelationManagers-'.$record->getKey())
                ->contained(false)
                ->tabs($tabs->all()),
        ];
    }
}

<?php

namespace App\Filament\Tenant\Resources\Projects\Resources\Tasks\Pages;

use App\Filament\Tenant\Resources\Projects\Resources\Tasks\TaskResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    protected function makeTable(): Table
    {
        return parent::makeTable()
            ->recordAction('quickView');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('board')
                ->label('Board view')
                ->url(fn (): string => TaskResource::getUrl('board', ['project' => $this->getParentRecord()])),
            CreateAction::make(),
        ];
    }
}

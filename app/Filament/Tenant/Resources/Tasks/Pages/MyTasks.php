<?php

namespace App\Filament\Tenant\Resources\Tasks\Pages;

use App\Filament\Tenant\Resources\Tasks\TaskResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class MyTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'My Tasks';
    }

    public function getBreadcrumb(): ?string
    {
        return 'My Tasks';
    }

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->modifyQueryUsing(fn (Builder $query) => $query->where('assignee_id', auth()->id()));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('board')
                ->label('Board view')
                ->url(fn (): string => TaskResource::getUrl('my-tasks-board')),
            CreateAction::make(),
        ];
    }
}

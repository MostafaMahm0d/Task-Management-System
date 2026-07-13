<?php

namespace App\Filament\Tenant\Resources\Activities\Pages;

use App\Filament\Tenant\Resources\Activities\ActivityResource;
use App\Filament\Tenant\Resources\Activities\Tables\TaskActivitiesTable;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;

class ListTaskActivities extends ListRecords
{
    protected static string $resource = ActivityResource::class;

    public function getTitle(): string
    {
        return 'Activity Log — Tasks';
    }

    public function table(Table $table): Table
    {
        return TaskActivitiesTable::configure($table->query(Task::query()->visibleTo(auth()->user())));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewByProject')
                ->label('View by Project')
                ->icon('heroicon-o-rectangle-stack')
                ->color('gray')
                ->url(fn (): string => ActivityResource::getUrl('index')),
        ];
    }
}

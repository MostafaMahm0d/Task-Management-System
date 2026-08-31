<?php

namespace App\Filament\Tenant\Resources\Activities\Pages;

use App\Filament\Tenant\Resources\Activities\ActivityResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListActivities extends ListRecords
{
    protected static string $resource = ActivityResource::class;

    public function getTitle(): string
    {
        return 'Activity Log — Projects';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewByTask')
                ->label('View by Task')
                ->icon('heroicon-o-check-circle')
                ->color('gray')
                ->url(fn (): string => ActivityResource::getUrl('tasks')),
        ];
    }
}

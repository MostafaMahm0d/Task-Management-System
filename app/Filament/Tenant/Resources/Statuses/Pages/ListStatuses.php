<?php

namespace App\Filament\Tenant\Resources\Statuses\Pages;

use App\Filament\Tenant\Resources\Statuses\StatusResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStatuses extends ListRecords
{
    protected static string $resource = StatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Tenant\Resources\Labels\Pages;

use App\Filament\Tenant\Resources\Labels\LabelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLabels extends ListRecords
{
    protected static string $resource = LabelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

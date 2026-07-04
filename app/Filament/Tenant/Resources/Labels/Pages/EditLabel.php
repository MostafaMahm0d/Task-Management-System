<?php

namespace App\Filament\Tenant\Resources\Labels\Pages;

use App\Filament\Tenant\Resources\Labels\LabelResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLabel extends EditRecord
{
    protected static string $resource = LabelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Tenant\Resources\Tasks\Pages;

use App\Filament\Tenant\Actions\ViewActivityAction;
use App\Filament\Tenant\Resources\Tasks\TaskResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Relaticle\Comments\Filament\Actions\CommentsAction;

class ViewTask extends ViewRecord
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CommentsAction::make(),
            ViewActivityAction::make(),
            EditAction::make(),
        ];
    }
}

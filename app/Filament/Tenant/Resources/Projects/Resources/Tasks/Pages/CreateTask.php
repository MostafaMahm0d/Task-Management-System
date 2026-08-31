<?php

namespace App\Filament\Tenant\Resources\Projects\Resources\Tasks\Pages;

use App\Filament\Tenant\Resources\Projects\Resources\Tasks\TaskResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;
}

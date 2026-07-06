<?php

namespace App\Filament\Tenant\Resources\Statuses\Pages;

use App\Filament\Tenant\Resources\Statuses\StatusResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStatus extends CreateRecord
{
    protected static string $resource = StatusResource::class;
}

<?php

namespace App\Filament\Tenant\Resources\Labels\Pages;

use App\Filament\Tenant\Resources\Labels\LabelResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLabel extends CreateRecord
{
    protected static string $resource = LabelResource::class;
}

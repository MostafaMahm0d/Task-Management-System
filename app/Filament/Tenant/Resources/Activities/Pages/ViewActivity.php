<?php

namespace App\Filament\Tenant\Resources\Activities\Pages;

use App\Filament\Tenant\Resources\Activities\ActivityResource;
use Filament\Resources\Pages\ViewRecord;

class ViewActivity extends ViewRecord
{
    protected static string $resource = ActivityResource::class;

    protected string $view = 'filament.tenant.pages.view-project-activity';

    public function getTitle(): string
    {
        return "Activity — {$this->getRecord()->name}";
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}

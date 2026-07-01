<?php

namespace App\Filament\Tenant\Widgets;

use App\Filament\Tenant\Resources\Projects\ProjectResource;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProjectsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $projects = ProjectResource::getEloquentQuery();

        return [
            Stat::make('My Projects', $projects->count()),

            Stat::make('Owned Projects', (clone $projects)->where('owner_id', auth()->id())->count()),

            Stat::make('Total Members', (clone $projects)->withCount('members')->get()->sum('members_count')),
        ];
    }
}

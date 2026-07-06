<?php

namespace App\Filament\Tenant\Widgets;

use App\Filament\Tenant\Resources\Projects\ProjectResource;
use App\Filament\Tenant\Resources\Tasks\TaskResource;
use App\Models\Task;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $projects = ProjectResource::getEloquentQuery();
        $myTasks = TaskResource::getEloquentQuery()->where('assignee_id', auth()->id());

        $overdueCount = (clone $myTasks)
            ->whereDate('due_date', '<', now())
            ->whereHas('status', fn ($query) => $query->where('is_completed', false))
            ->count();

        return [
            Stat::make('My Projects', $projects->count())
                ->icon(Heroicon::OutlinedRectangleStack)
                ->color('primary'),

            Stat::make('Owned Projects', (clone $projects)->where('owner_id', auth()->id())->count())
                ->icon(Heroicon::OutlinedStar)
                ->color('gray'),

            Stat::make('My Open Tasks', (clone $myTasks)->whereHas('status', fn ($query) => $query->where('is_completed', false))->count())
                ->icon(Heroicon::OutlinedClipboardDocumentList)
                ->color('info'),

            Stat::make('Overdue Tasks', $overdueCount)
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color($overdueCount > 0 ? 'danger' : 'success'),

            Stat::make('Urgent / High Priority', (clone $myTasks)
                ->whereIn('priority', [Task::PRIORITY_URGENT, Task::PRIORITY_HIGH])
                ->whereHas('status', fn ($query) => $query->where('is_completed', false))
                ->count())
                ->icon(Heroicon::OutlinedFlag)
                ->color('warning'),
        ];
    }
}

<?php

namespace App\Filament\Tenant\Widgets;

use App\Filament\Tenant\Resources\Projects\ProjectResource;
use App\Filament\Tenant\Resources\Tasks\TaskResource;
use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardOverview extends StatsOverviewWidget
{
    use HasWidgetShield;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();

        if ($user->can('task.manageAll')) {
            return $this->getTenantWideStats();
        }

        return $this->getPersonalStats();
    }

    /**
     * @return array<Stat>
     */
    private function getTenantWideStats(): array
    {
        $openTasks = Task::query()->whereHas('status', fn ($query) => $query->where('is_completed', false)->where('is_cancelled', false));

        $overdueCount = (clone $openTasks)->whereDate('due_date', '<', now())->count();

        return [
            Stat::make('Total Projects', Project::query()->count())
                ->description('Across the whole tenant')
                ->icon(Heroicon::OutlinedRectangleStack)
                ->color('primary')
                ->url(ProjectResource::getUrl('index')),

            Stat::make('Total Tasks', Task::query()->count())
                ->description('Across the whole tenant')
                ->icon(Heroicon::OutlinedClipboardDocumentList)
                ->color('info')
                ->url(TaskResource::getUrl('index')),

            Stat::make('Overdue Tasks', $overdueCount)
                ->description('Across all projects')
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color($overdueCount > 0 ? 'danger' : 'success')
                ->url(TaskResource::getUrl('index', [
                    'filters' => ['overdue' => ['isActive' => true]],
                ])),

            Stat::make('Urgent / High Priority', (clone $openTasks)
                ->whereIn('priority', [Task::PRIORITY_URGENT, Task::PRIORITY_HIGH])
                ->count())
                ->description('Across all projects')
                ->icon(Heroicon::OutlinedFlag)
                ->color('warning')
                ->url(TaskResource::getUrl('index', [
                    'filters' => [
                        'priority' => ['values' => [Task::PRIORITY_URGENT, Task::PRIORITY_HIGH]],
                        'status_id' => ['values' => $this->openStatusIds()],
                    ],
                ])),
        ];
    }

    /**
     * @return array<Stat>
     */
    private function getPersonalStats(): array
    {
        $projects = ProjectResource::getEloquentQuery();
        $myTasks = TaskResource::getEloquentQuery()->where('assignee_id', auth()->id());
        $myOpenTasks = (clone $myTasks)->whereHas('status', fn ($query) => $query->where('is_completed', false)->where('is_cancelled', false));

        $overdueCount = (clone $myOpenTasks)->whereDate('due_date', '<', now())->count();

        return [
            Stat::make('My Projects', $projects->count())
                ->icon(Heroicon::OutlinedRectangleStack)
                ->color('primary')
                ->url(ProjectResource::getUrl('index')),

            Stat::make('Owned Projects', (clone $projects)->where('owner_id', auth()->id())->count())
                ->icon(Heroicon::OutlinedStar)
                ->color('gray')
                ->url(ProjectResource::getUrl('index', [
                    'filters' => ['owner' => ['value' => auth()->id()]],
                ])),

            Stat::make('My Open Tasks', (clone $myOpenTasks)->count())
                ->icon(Heroicon::OutlinedClipboardDocumentList)
                ->color('info')
                ->url(TaskResource::getUrl('my-tasks', [
                    'filters' => ['status_id' => ['values' => $this->openStatusIds()]],
                ])),

            Stat::make('Overdue Tasks', $overdueCount)
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color($overdueCount > 0 ? 'danger' : 'success')
                ->url(TaskResource::getUrl('my-tasks', [
                    'filters' => ['overdue' => ['isActive' => true]],
                ])),

            Stat::make('Urgent / High Priority', (clone $myOpenTasks)
                ->whereIn('priority', [Task::PRIORITY_URGENT, Task::PRIORITY_HIGH])
                ->count())
                ->icon(Heroicon::OutlinedFlag)
                ->color('warning')
                ->url(TaskResource::getUrl('my-tasks', [
                    'filters' => [
                        'priority' => ['values' => [Task::PRIORITY_URGENT, Task::PRIORITY_HIGH]],
                        'status_id' => ['values' => $this->openStatusIds()],
                    ],
                ])),
        ];
    }

    /**
     * @return array<int>
     */
    private function openStatusIds(): array
    {
        return Status::query()->where('is_completed', false)->where('is_cancelled', false)->pluck('id')->all();
    }
}

<?php

namespace App\Filament\Tenant\Widgets;

use App\Filament\Tenant\Pages\Concerns\InteractsWithReportCache;
use App\Models\Task;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OverdueTasksOverview extends StatsOverviewWidget
{
    use HasWidgetShield;
    use InteractsWithReportCache;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();

        $summary = $this->rememberReport(
            'overdue-tasks-overview:'.$user->id,
            fn () => Task::overdueBreakdown(Task::query()->visibleTo($user)),
        );

        return [
            Stat::make('Total Overdue', $summary['total'])
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color($summary['total'] > 0 ? 'danger' : 'success'),

            Stat::make('Avg. Days Overdue', $summary['avg_days_overdue'] ?? '—')
                ->icon(Heroicon::OutlinedClock)
                ->color('warning'),

            Stat::make('Urgent / High Priority', ($summary['by_priority'][Task::PRIORITY_URGENT] ?? 0) + ($summary['by_priority'][Task::PRIORITY_HIGH] ?? 0))
                ->icon(Heroicon::OutlinedFlag)
                ->color('danger'),
        ];
    }
}

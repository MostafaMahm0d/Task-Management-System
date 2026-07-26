<?php

namespace App\Filament\Tenant\Widgets;

use App\Filament\Tenant\Pages\Concerns\InteractsWithReportCache;
use App\Models\Project;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProjectPerformanceOverview extends StatsOverviewWidget
{
    use HasWidgetShield;
    use InteractsWithReportCache;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();

        $summary = $this->rememberReport(
            'project-performance-overview:'.$user->id,
            fn () => Project::performanceSummary(Project::query()->visibleTo($user)),
        );

        return [
            Stat::make('Active Projects', $summary['active_projects'])
                ->icon(Heroicon::OutlinedRectangleStack)
                ->color('primary'),

            Stat::make('Avg. Completion Rate', $summary['avg_completion_rate'].'%')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color($summary['avg_completion_rate'] >= 70 ? 'success' : ($summary['avg_completion_rate'] >= 40 ? 'warning' : 'danger')),

            Stat::make('Avg. On-Time Rate', $summary['avg_on_time_rate'].'%')
                ->icon(Heroicon::OutlinedClock)
                ->color($summary['avg_on_time_rate'] >= 70 ? 'success' : ($summary['avg_on_time_rate'] >= 40 ? 'warning' : 'danger')),
        ];
    }
}

<?php

namespace App\Filament\Tenant\Widgets;

use App\Filament\Tenant\Pages\Concerns\InteractsWithReportCache;
use App\Models\Task;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TaskCompletionOverview extends StatsOverviewWidget
{
    use HasWidgetShield;
    use InteractsWithReportCache;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();

        $summary = $this->rememberReport(
            'task-completion-overview:'.$user->id,
            fn () => Task::completionSummary(Task::query()->visibleTo($user)),
        );

        return [
            Stat::make('Total Tasks', $summary['total'])
                ->icon(Heroicon::OutlinedClipboardDocumentList)
                ->color('info'),

            Stat::make('Completed', $summary['completed'])
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success'),

            Stat::make('Completion Rate', $summary['rate'].'%')
                ->icon(Heroicon::OutlinedChartBar)
                ->color($summary['rate'] >= 70 ? 'success' : ($summary['rate'] >= 40 ? 'warning' : 'danger')),

            Stat::make('Avg. Days to Close', $summary['avg_days_to_close'] ?? '—')
                ->description('Based on last update')
                ->icon(Heroicon::OutlinedClock)
                ->color('gray'),
        ];
    }
}

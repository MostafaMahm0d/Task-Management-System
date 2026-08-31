<?php

namespace App\Filament\Tenant\Widgets;

use App\Filament\Tenant\Pages\Concerns\InteractsWithReportCache;
use App\Models\User;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TeamWorkloadOverview extends StatsOverviewWidget
{
    use HasWidgetShield;
    use InteractsWithReportCache;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $summary = $this->rememberReport(
            'team-workload-overview',
            fn () => User::workloadSummary(User::query()),
        );

        return [
            Stat::make('Total Open Tasks', $summary['total_open_tasks'])
                ->icon(Heroicon::OutlinedClipboardDocumentList)
                ->color('info'),

            Stat::make('Avg. Tasks / Person', $summary['avg_tasks_per_person'])
                ->icon(Heroicon::OutlinedUserGroup)
                ->color('primary'),

            Stat::make('Most Loaded', $summary['most_loaded'] ?? '—')
                ->icon(Heroicon::OutlinedFlag)
                ->color('warning'),
        ];
    }
}

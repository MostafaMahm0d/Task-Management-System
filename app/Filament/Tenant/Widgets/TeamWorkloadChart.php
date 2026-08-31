<?php

namespace App\Filament\Tenant\Widgets;

use App\Filament\Tenant\Pages\Concerns\InteractsWithReportCache;
use App\Models\User;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;

class TeamWorkloadChart extends ChartWidget
{
    use HasWidgetShield;
    use InteractsWithReportCache;

    protected static ?int $sort = 2;

    public function getHeading(): string
    {
        return 'Open Tasks per Team Member';
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        // Cached as a plain array, not the Eloquent Collection itself — serializing
        // Eloquent models through Redis (predis) can corrupt their protected properties
        // and come back as __PHP_Incomplete_Class on the next request.
        $rows = $this->rememberReport(
            'team-workload-chart',
            fn () => User::query()
                ->withWorkloadAggregates()
                ->having('open_tasks_count', '>', 0)
                ->orderByDesc('open_tasks_count')
                ->limit(10)
                ->get(['id', 'name'])
                ->map(fn (User $user): array => [
                    'name' => $user->name,
                    'open_tasks_count' => $user->open_tasks_count,
                ])
                ->all(),
        );

        return [
            'datasets' => [[
                'label' => 'Open Tasks',
                'data' => array_column($rows, 'open_tasks_count'),
                'backgroundColor' => '#6366f1',
            ]],
            'labels' => array_column($rows, 'name'),
        ];
    }
}

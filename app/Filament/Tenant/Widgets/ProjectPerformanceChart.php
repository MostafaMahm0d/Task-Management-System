<?php

namespace App\Filament\Tenant\Widgets;

use App\Filament\Tenant\Pages\Concerns\InteractsWithReportCache;
use App\Models\Project;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;

class ProjectPerformanceChart extends ChartWidget
{
    use HasWidgetShield;
    use InteractsWithReportCache;

    protected static ?int $sort = 2;

    public function getHeading(): string
    {
        return 'Completion Rate by Project';
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $user = auth()->user();

        // Cached as a plain array, not the Eloquent Collection itself — serializing
        // Eloquent models through Redis (predis) can corrupt their protected properties
        // and come back as __PHP_Incomplete_Class on the next request.
        $rows = $this->rememberReport(
            'project-performance-chart:'.$user->id,
            fn () => Project::query()
                ->visibleTo($user)
                ->withTaskAggregates()
                ->having('tasks_count', '>', 0)
                ->orderByDesc('tasks_count')
                ->limit(10)
                ->get(['id', 'name'])
                ->map(fn (Project $project): array => [
                    'name' => $project->name,
                    'tasks_count' => $project->tasks_count,
                    'completed_tasks_count' => $project->completed_tasks_count,
                ])
                ->all(),
        );

        return [
            'datasets' => [[
                'label' => 'Completion Rate %',
                'data' => array_map(fn (array $row): float => $row['tasks_count'] === 0
                    ? 0.0
                    : round($row['completed_tasks_count'] / $row['tasks_count'] * 100, 2), $rows),
                'backgroundColor' => '#22c55e',
            ]],
            'labels' => array_column($rows, 'name'),
        ];
    }
}

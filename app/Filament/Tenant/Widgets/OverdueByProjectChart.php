<?php

namespace App\Filament\Tenant\Widgets;

use App\Filament\Tenant\Pages\Concerns\InteractsWithReportCache;
use App\Models\Task;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;

class OverdueByProjectChart extends ChartWidget
{
    use HasWidgetShield;
    use InteractsWithReportCache;

    protected static ?int $sort = 2;

    public function getHeading(): string
    {
        return 'Overdue Tasks by Project';
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
            'overdue-by-project-chart:'.$user->id,
            fn () => Task::query()
                ->visibleTo($user)
                ->overdue()
                ->join('projects', 'projects.id', '=', 'tasks.project_id')
                ->selectRaw('projects.name as project_name, COUNT(*) as aggregate')
                ->groupBy('projects.name')
                ->orderByDesc('aggregate')
                ->limit(10)
                ->get()
                ->map(fn (Task $task): array => [
                    'project_name' => $task->project_name,
                    'aggregate' => $task->aggregate,
                ])
                ->all(),
        );

        return [
            'datasets' => [[
                'label' => 'Overdue Tasks',
                'data' => array_column($rows, 'aggregate'),
                'backgroundColor' => '#ef4444',
            ]],
            'labels' => array_column($rows, 'project_name'),
        ];
    }
}

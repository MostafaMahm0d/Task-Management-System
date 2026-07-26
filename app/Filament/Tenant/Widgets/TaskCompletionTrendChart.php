<?php

namespace App\Filament\Tenant\Widgets;

use App\Filament\Tenant\Pages\Concerns\InteractsWithReportCache;
use App\Models\Task;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class TaskCompletionTrendChart extends ChartWidget
{
    use HasWidgetShield;
    use InteractsWithReportCache;

    protected static ?int $sort = 2;

    public function getHeading(): string
    {
        return 'Completion Rate Trend';
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $months = collect(range(5, 0))->map(fn (int $i): string => now()->subMonths($i)->format('Y-m'));

        $user = auth()->user();

        $rates = $this->rememberReport(
            'task-completion-trend:'.$user->id,
            fn () => Task::completionTrend(Task::query()->visibleTo($user)),
        );

        return [
            'datasets' => [[
                'label' => 'Completion Rate %',
                'data' => $months->map(fn (string $month): float => $rates[$month] ?? 0.0)->all(),
                'borderColor' => '#22c55e',
                'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                'fill' => true,
                'tension' => 0.3,
            ]],
            'labels' => $months->map(fn (string $month): string => Carbon::createFromFormat('Y-m', $month)->format('M Y'))->all(),
        ];
    }
}

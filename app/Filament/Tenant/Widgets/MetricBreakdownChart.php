<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\Rating;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;

class MetricBreakdownChart extends ChartWidget
{
    use HasWidgetShield;

    protected static ?int $sort = 3;

    public function getHeading(): string
    {
        return $this->isManager() ? 'Tenant Metric Breakdown' : 'My Metric Breakdown';
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $metrics = Rating::metrics();

        $query = Rating::query();

        if (! $this->isManager()) {
            $query->where('employee_id', auth()->id());
        }

        $data = [];

        foreach ($metrics as $metricKey => $label) {
            $data[] = round((float) (clone $query)->avg($metricKey), 2);
        }

        return [
            'datasets' => [[
                'label' => 'Average Score',
                'data' => $data,
                'backgroundColor' => ['#6366f1', '#8b5cf6', '#ec4899', '#f59e0b'],
            ]],
            'labels' => array_values($metrics),
        ];
    }

    private function isManager(): bool
    {
        return auth()->user()->can('rating.viewAll');
    }
}

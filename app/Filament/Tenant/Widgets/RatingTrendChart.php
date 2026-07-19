<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\Rating;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class RatingTrendChart extends ChartWidget
{
    use HasWidgetShield;

    protected static ?int $sort = 2;

    public function getHeading(): string
    {
        return $this->isManager() ? 'Tenant Rating Trend' : 'My Rating Trend';
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $months = collect(range(5, 0))->map(fn (int $i): string => now()->subMonths($i)->format('Y-m'));

        $query = Rating::query();

        if (! $this->isManager()) {
            $query->where('employee_id', auth()->id());
        }

        // Group by the period being evaluated (falling back to created_at for ad-hoc
        // ratings with no period set), not by created_at alone — historical ratings
        // are often entered well after the period they cover.
        $averagesByMonth = $query
            ->whereRaw('COALESCE(period_start, created_at) >= ?', [now()->subMonths(5)->startOfMonth()])
            ->selectRaw("DATE_FORMAT(COALESCE(period_start, created_at), '%Y-%m') as month, AVG(overall_score) as average")
            ->groupBy('month')
            ->pluck('average', 'month');

        return [
            'datasets' => [[
                'label' => 'Average Overall Score',
                'data' => $months->map(fn (string $month): ?float => isset($averagesByMonth[$month]) ? round((float) $averagesByMonth[$month], 2) : null)->all(),
                'borderColor' => '#6366f1',
                'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                'fill' => true,
                'tension' => 0.3,
            ]],
            'labels' => $months->map(fn (string $month): string => Carbon::createFromFormat('Y-m', $month)->format('M Y'))->all(),
        ];
    }

    private function isManager(): bool
    {
        return auth()->user()->can('rating.viewAll');
    }
}

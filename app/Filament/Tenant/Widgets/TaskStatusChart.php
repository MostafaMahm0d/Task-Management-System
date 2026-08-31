<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\Status;
use App\Models\Task;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;

class TaskStatusChart extends ChartWidget
{
    use HasWidgetShield;

    protected static ?int $sort = 2;

    /**
     * @var array<int, array{id: int, name: string, color: string, count: int}>|null
     */
    private ?array $statusCounts = null;

    public function getHeading(): string
    {
        return $this->isAdmin() ? 'Tenant Open Tasks by Status' : 'My Open Tasks by Status';
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $rows = $this->getStatusCounts();

        if ($rows === []) {
            return [
                'datasets' => [[
                    'data' => [1],
                    'backgroundColor' => ['#9ca3af'],
                ]],
                'labels' => ['No open tasks'],
            ];
        }

        return [
            'datasets' => [[
                'data' => array_column($rows, 'count'),
                'backgroundColor' => array_column($rows, 'color'),
            ]],
            'labels' => array_column($rows, 'name'),
        ];
    }


    private function isAdmin(): bool
    {
        return auth()->user()->can('task.manageAll');
    }

    /**
     * @return array<int, array{id: int, name: string, color: string, count: int}>
     */
    private function getStatusCounts(): array
    {
        if ($this->statusCounts !== null) {
            return $this->statusCounts;
        }

        $statuses = Status::query()->orderBy('position')->get();

        $counts = Task::query()
            ->when(! $this->isAdmin(), fn ($query) => $query->where('assignee_id', auth()->id()))
            ->whereHas('status', fn ($query) => $query->where('is_completed', false)->where('is_cancelled', false))
            ->selectRaw('status_id, count(*) as aggregate')
            ->groupBy('status_id')
            ->pluck('aggregate', 'status_id');

        return $this->statusCounts = $statuses
            ->map(fn (Status $status): array => [
                'id' => $status->id,
                'name' => $status->name,
                'color' => $status->color,
                'count' => $counts[$status->id] ?? 0,
            ])
            ->reject(fn (array $row): bool => $row['count'] === 0)
            ->values()
            ->all();
    }
}

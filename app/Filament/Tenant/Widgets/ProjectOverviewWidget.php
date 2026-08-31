<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\Project;
use App\Models\Task;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProjectOverviewWidget extends StatsOverviewWidget
{
    use HasWidgetShield;

    public ?Project $record = null;

    protected function getStats(): array
    {
        $summary = Task::completionSummary(Task::query()->where('project_id', $this->record->id));
        $overdue = Task::overdueBreakdown(Task::query()->where('project_id', $this->record->id));

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

            Stat::make('Overdue', $overdue['total'])
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color($overdue['total'] > 0 ? 'danger' : 'success'),
        ];
    }
}

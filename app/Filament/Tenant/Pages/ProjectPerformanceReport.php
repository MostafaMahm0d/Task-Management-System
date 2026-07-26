<?php

namespace App\Filament\Tenant\Pages;

use App\Exports\ProjectPerformanceExport;
use App\Filament\Tenant\Pages\Concerns\InteractsWithReportCache;
use App\Filament\Tenant\Widgets\ProjectPerformanceChart;
use App\Filament\Tenant\Widgets\ProjectPerformanceOverview;
use App\Models\Project;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use UnitEnum;

class ProjectPerformanceReport extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithReportCache;
    use InteractsWithTable;

    protected string $view = 'filament.tenant.pages.project-performance-report';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Project Performance';

    protected static ?string $title = 'Project Performance Report';

    protected function getHeaderWidgets(): array
    {
        return [
            ProjectPerformanceOverview::class,
            ProjectPerformanceChart::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon(Heroicon::OutlinedArrowPath)
                ->action(function () {
                    $userId = auth()->id();
                    $this->forgetReportCache('project-performance-overview:'.$userId);
                    $this->forgetReportCache('project-performance-chart:'.$userId);
                }),

            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->visible(fn (): bool => auth()->user()->can('report.export'))
                ->action(function () {
                    $pdf = Pdf::loadView('reports.pdf.project-performance', ['rows' => $this->exportRows()]);
                    $filename = 'project-performance-report-'.now()->format('Y-m-d').'.pdf';

                    // Livewire only intercepts StreamedResponse/BinaryFileResponse to hand a
                    // download to the browser — Pdf::download() returns a plain
                    // Illuminate\Http\Response, whose raw PDF bytes would otherwise fall
                    // through to Livewire's normal JSON-encoded return path and crash on
                    // the non-UTF8 binary content.
                    return response()->streamDownload(
                        fn () => print ($pdf->output()),
                        $filename,
                        ['Content-Type' => 'application/pdf'],
                    );
                }),

            Action::make('exportExcel')
                ->label('Export Excel')
                ->icon(Heroicon::OutlinedTableCells)
                ->visible(fn (): bool => auth()->user()->can('report.export'))
                ->action(fn () => Excel::download(
                    new ProjectPerformanceExport($this->exportRows()),
                    'project-performance-report-'.now()->format('Y-m-d').'.xlsx',
                )),
        ];
    }

    /**
     * @return array<int, array{name: string, owner: string, tasks_count: int, completed_tasks_count: int, overdue_tasks_count: int}>
     */
    private function exportRows(): array
    {
        return $this->getFilteredTableQuery()
            ->with('owner')
            ->get()
            ->map(fn (Project $project): array => [
                'name' => $project->name,
                'owner' => $project->owner?->name ?? '—',
                'tasks_count' => $project->tasks_count,
                'completed_tasks_count' => $project->completed_tasks_count,
                'overdue_tasks_count' => $project->overdue_tasks_count,
            ])
            ->all();
    }

    public function table(Table $table): Table
    {
        $user = auth()->user();

        $query = Project::query()->visibleTo($user)->withTaskAggregates()->having('tasks_count', '>', 0);

        return $table
            ->heading('Project Performance')
            ->query($query)
            ->defaultSort('tasks_count', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Project')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('owner.name')
                    ->label('Owner'),

                TextColumn::make('tasks_count')
                    ->label('Total Tasks')
                    ->sortable(),

                TextColumn::make('completion_rate')
                    ->label('Completion Rate')
                    ->state(fn (Project $record): string => $record->tasks_count === 0
                        ? '0%'
                        : round($record->completed_tasks_count / $record->tasks_count * 100, 2).'%')
                    ->badge()
                    ->color(fn (Project $record): string => match (true) {
                        $record->tasks_count === 0 => 'gray',
                        $record->completed_tasks_count / $record->tasks_count >= 0.7 => 'success',
                        $record->completed_tasks_count / $record->tasks_count >= 0.4 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),

                TextColumn::make('on_time_rate')
                    ->label('On-Time Rate')
                    ->state(fn (Project $record): string => $record->tasks_count === 0
                        ? '0%'
                        : round(($record->tasks_count - $record->overdue_tasks_count) / $record->tasks_count * 100, 2).'%'),

                TextColumn::make('overdue_tasks_count')
                    ->label('Overdue')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'success')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('due_between')
                    ->label('Due date range')
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['from'] || $data['until'],
                        fn (Builder $query) => $query->whereHas('tasks', fn (Builder $query) => $query
                            ->when($data['from'], fn (Builder $query, $date) => $query->whereDate('due_date', '>=', $date))
                            ->when($data['until'], fn (Builder $query, $date) => $query->whereDate('due_date', '<=', $date)))
                    )),
            ]);
    }
}

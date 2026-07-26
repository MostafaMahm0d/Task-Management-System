<?php

namespace App\Filament\Tenant\Pages;

use App\Exports\OverdueTasksExport;
use App\Filament\Tenant\Pages\Concerns\InteractsWithReportCache;
use App\Filament\Tenant\Widgets\OverdueByProjectChart;
use App\Filament\Tenant\Widgets\OverdueTasksOverview;
use App\Models\Task;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use UnitEnum;

class OverdueTasksReport extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithReportCache;
    use InteractsWithTable;

    protected string $view = 'filament.tenant.pages.overdue-tasks-report';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Overdue Tasks';

    protected static ?string $title = 'Overdue Tasks Report';

    protected function getHeaderWidgets(): array
    {
        return [
            OverdueTasksOverview::class,
            OverdueByProjectChart::class,
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
                    $this->forgetReportCache('overdue-tasks-overview:'.$userId);
                    $this->forgetReportCache('overdue-by-project-chart:'.$userId);
                }),

            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->visible(fn (): bool => auth()->user()->can('report.export'))
                ->action(function () {
                    $pdf = Pdf::loadView('reports.pdf.overdue-tasks', ['rows' => $this->exportRows()]);
                    $filename = 'overdue-tasks-report-'.now()->format('Y-m-d').'.pdf';

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
                    new OverdueTasksExport($this->exportRows()),
                    'overdue-tasks-report-'.now()->format('Y-m-d').'.xlsx',
                )),
        ];
    }

    /**
     * @return array<int, array{title: string, project: string, assignee: string, due_date: string, days_overdue: int, priority: string, status: string}>
     */
    private function exportRows(): array
    {
        return $this->getFilteredTableQuery()
            ->with(['project', 'assignee', 'status'])
            ->get()
            ->map(fn (Task $task): array => [
                'title' => $task->title,
                'project' => $task->project?->name ?? '—',
                'assignee' => $task->assignee?->name ?? 'Unassigned',
                'due_date' => $task->due_date->toDateString(),
                'days_overdue' => (int) now()->diffInDays($task->due_date),
                'priority' => $task->priority,
                'status' => $task->status?->name ?? '—',
            ])
            ->all();
    }

    public function table(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->heading('Overdue Tasks')
            ->query(Task::query()->visibleTo($user)->overdue())
            ->defaultSort('due_date', 'asc')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('project.name')
                    ->label('Project'),

                TextColumn::make('assignee.name')
                    ->label('Assignee')
                    ->placeholder('Unassigned'),

                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('days_overdue')
                    ->label('Days Overdue')
                    ->state(fn (Task $record): int => (int) now()->diffInDays($record->due_date))
                    ->badge()
                    ->color('danger'),

                TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Task::PRIORITY_URGENT => 'danger',
                        Task::PRIORITY_HIGH => 'warning',
                        Task::PRIORITY_MEDIUM => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('status.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn (Task $record): string => $record->status->color),
            ])
            ->filters([
                SelectFilter::make('project')
                    ->relationship('project', 'name', modifyQueryUsing: fn (Builder $query) => $query->visibleTo(auth()->user()))
                    ->searchable(),

                SelectFilter::make('assignee')
                    ->relationship('assignee', 'name')
                    ->searchable(),

                SelectFilter::make('priority')
                    ->options([
                        Task::PRIORITY_URGENT => 'Urgent',
                        Task::PRIORITY_HIGH => 'High',
                        Task::PRIORITY_MEDIUM => 'Medium',
                        Task::PRIORITY_LOW => 'Low',
                    ]),
            ]);
    }
}

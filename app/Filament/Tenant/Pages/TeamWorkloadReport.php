<?php

namespace App\Filament\Tenant\Pages;

use App\Exports\TeamWorkloadExport;
use App\Filament\Tenant\Pages\Concerns\InteractsWithReportCache;
use App\Filament\Tenant\Widgets\TeamWorkloadChart;
use App\Filament\Tenant\Widgets\TeamWorkloadOverview;
use App\Models\Project;
use App\Models\User;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
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

class TeamWorkloadReport extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithReportCache;
    use InteractsWithTable;

    protected string $view = 'filament.tenant.pages.team-workload-report';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Team Workload';

    protected static ?string $title = 'Team Workload Report';

    protected function getHeaderWidgets(): array
    {
        return [
            TeamWorkloadOverview::class,
            TeamWorkloadChart::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon(Heroicon::OutlinedArrowPath)
                ->action(function () {
                    $this->forgetReportCache('team-workload-overview');
                    $this->forgetReportCache('team-workload-chart');
                }),

            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->visible(fn (): bool => auth()->user()->can('report.export'))
                ->action(function () {
                    $pdf = Pdf::loadView('reports.pdf.team-workload', ['rows' => $this->exportRows()]);
                    $filename = 'team-workload-report-'.now()->format('Y-m-d').'.pdf';

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
                    new TeamWorkloadExport($this->exportRows()),
                    'team-workload-report-'.now()->format('Y-m-d').'.xlsx',
                )),
        ];
    }

    /**
     * @return array<int, array{name: string, open_tasks_count: int, overdue_tasks_count: int, urgent_tasks_count: int}>
     */
    private function exportRows(): array
    {
        return $this->getFilteredTableQuery()
            ->get()
            ->map(fn (User $user): array => [
                'name' => $user->name,
                'open_tasks_count' => $user->open_tasks_count,
                'overdue_tasks_count' => $user->overdue_tasks_count,
                'urgent_tasks_count' => $user->urgent_tasks_count,
            ])
            ->all();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Workload by Team Member')
            ->query(User::query()->withWorkloadAggregates())
            ->defaultSort('open_tasks_count', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Team Member')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('open_tasks_count')
                    ->label('Open Tasks')
                    ->sortable(),

                TextColumn::make('overdue_tasks_count')
                    ->label('Overdue')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'success')
                    ->sortable(),

                TextColumn::make('urgent_tasks_count')
                    ->label('Urgent / High Priority')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('project')
                    ->schema([
                        Select::make('project_id')
                            ->options(fn () => Project::query()->pluck('name', 'id'))
                            ->searchable(),
                    ])
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['project_id'],
                        fn (Builder $query, $projectId) => $query->whereHas('assignedTasks', fn (Builder $query) => $query->where('project_id', $projectId))
                    )),

                Filter::make('due_between')
                    ->label('Due date range')
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['from'] || $data['until'],
                        fn (Builder $query) => $query->whereHas('assignedTasks', fn (Builder $query) => $query
                            ->when($data['from'], fn (Builder $query, $date) => $query->whereDate('due_date', '>=', $date))
                            ->when($data['until'], fn (Builder $query, $date) => $query->whereDate('due_date', '<=', $date)))
                    )),
            ]);
    }
}

<?php

namespace App\Filament\Tenant\Pages;

use App\Filament\Tenant\Resources\Ratings\RatingResource;
use App\Filament\Tenant\Widgets\MetricBreakdownChart;
use App\Filament\Tenant\Widgets\PerformanceOverview;
use App\Filament\Tenant\Widgets\RatingTrendChart;
use App\Models\Rating;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PerformanceDashboard extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected string $view = 'filament.tenant.pages.performance-dashboard';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Performance';

    protected static ?string $navigationLabel = 'My Dashboard';

    protected static ?string $title = 'My Performance Dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            PerformanceOverview::class,
            RatingTrendChart::class,
            MetricBreakdownChart::class,
        ];
    }

    public function table(Table $table): Table
    {
        $user = auth()->user();

        // This page is the personal view: always scope to ratings the viewer
        // themselves created, never the tenant-wide bypass (that lives on
        // TenantPerformanceDashboard) and never ratings received instead.
        $query = Rating::query()->where('employee_id', $user->id);

        return $table
            ->heading('My Rating History')
            ->query($query)
            ->recordUrl(fn (Rating $record): string => RatingResource::getUrl('view', ['record' => $record]))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('period_start')
                    ->label('Period')
                    ->formatStateUsing(fn (Rating $record): string => $record->period_start && $record->period_end
                        ? "{$record->period_start->format('M j, Y')} – {$record->period_end->format('M j, Y')}"
                        : 'Not set'),

                TextColumn::make('employee.name')
                    ->label('Employee'),

                TextColumn::make('project.name')
                    ->label('Project')
                    ->placeholder('—'),

                TextColumn::make('overall_score')
                    ->label('Overall')
                    ->numeric(2)
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        (float) $state >= 4 => 'success',
                        (float) $state >= 3 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Evaluated on')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('project')
                    ->relationship('project', 'name', modifyQueryUsing: fn (Builder $query) => $query->visibleTo(auth()->user()))
                    ->searchable(),
            ]);
    }
}

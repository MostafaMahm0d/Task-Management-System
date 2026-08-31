<?php

namespace App\Filament\Tenant\Pages;

use App\Filament\Tenant\Resources\Ratings\RatingResource;
use App\Filament\Tenant\Widgets\MetricBreakdownChart;
use App\Filament\Tenant\Widgets\PerformanceOverview;
use App\Filament\Tenant\Widgets\RatingTrendChart;
use App\Filament\Tenant\Widgets\TenantRatingOverview;
use App\Models\User;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use UnitEnum;

class TenantPerformanceDashboard extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected string $view = 'filament.tenant.pages.tenant-performance-dashboard';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Performance';

    protected static ?string $navigationLabel = 'Tenant Dashboard';

    protected static ?string $title = 'Tenant Performance Dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            PerformanceOverview::class,
            TenantRatingOverview::class,
            RatingTrendChart::class,
            MetricBreakdownChart::class,
        ];
    }

    public function table(Table $table): Table
    {
        $query = User::query()
            ->withCount('ratingsReceived')
            ->withAvg('ratingsReceived', 'overall_score')
            ->withAvg('ratingsReceived', 'work_quality')
            ->withAvg('ratingsReceived', 'communication')
            ->withAvg('ratingsReceived', 'teamwork')
            ->withAvg('ratingsReceived', 'punctuality')
            ->having('ratings_received_count', '>', 0);

        return $table
            ->heading('Team Ranking')
            ->query($query)
            ->recordUrl(fn (User $record): string => RatingResource::getUrl('index', [
                'filters' => ['employee' => ['value' => $record->id]],
            ]))
            ->defaultSort('ratings_received_avg_overall_score', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Employee')
                    ->searchable(),

                TextColumn::make('ratings_received_avg_overall_score')
                    ->label('Overall')
                    ->numeric(2)
                    ->badge()
                    ->color(fn (?string $state): string => match (true) {
                        $state === null => 'gray',
                        (float) $state >= 4 => 'success',
                        (float) $state >= 3 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),

                TextColumn::make('ratings_received_avg_work_quality')
                    ->label('Work Quality')
                    ->numeric(2)
                    ->sortable(),

                TextColumn::make('ratings_received_avg_communication')
                    ->label('Communication')
                    ->numeric(2)
                    ->sortable(),

                TextColumn::make('ratings_received_avg_teamwork')
                    ->label('Teamwork')
                    ->numeric(2)
                    ->sortable(),

                TextColumn::make('ratings_received_avg_punctuality')
                    ->label('Punctuality')
                    ->numeric(2)
                    ->sortable(),

                TextColumn::make('ratings_received_count')
                    ->label('Ratings')
                    ->sortable(),
            ]);
    }
}

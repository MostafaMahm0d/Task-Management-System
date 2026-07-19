<?php

namespace App\Filament\Tenant\Widgets;

use App\Filament\Tenant\Resources\Ratings\RatingResource;
use App\Models\Rating;
use App\Models\User;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PerformanceOverview extends StatsOverviewWidget
{
    use HasWidgetShield;

    protected function getStats(): array
    {
        $user = auth()->user();
        $isManager = $user->can('rating.viewAll');

        $query = Rating::query();

        if (! $isManager) {
            $query->where('employee_id', $user->id);
        }

        $totalRatings = (clone $query)->count();
        $averageOverall = round((float) (clone $query)->avg('overall_score'), 2);
        $ratedEmployees = $isManager ? User::query()->whereHas('ratingsReceived')->count() : 1;

        $ratingsUrl = RatingResource::getUrl('index');

        return [
            Stat::make($isManager ? 'Total Ratings' : 'My Ratings', $totalRatings)
                ->icon(Heroicon::OutlinedStar)
                ->color('primary')
                ->url($ratingsUrl),

            Stat::make('Average Overall Score', $averageOverall)
                ->icon(Heroicon::OutlinedChartBar)
                ->color(match (true) {
                    $averageOverall >= 4 => 'success',
                    $averageOverall >= 3 => 'warning',
                    default => 'danger',
                })
                ->url($ratingsUrl),

            Stat::make($isManager ? 'Employees Rated' : 'Employee', $ratedEmployees)
                ->icon(Heroicon::OutlinedUsers)
                ->color('gray')
                ->url($ratingsUrl),
        ];
    }
}

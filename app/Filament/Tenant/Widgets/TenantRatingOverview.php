<?php

namespace App\Filament\Tenant\Widgets;

use App\Filament\Tenant\Resources\Ratings\RatingResource;
use App\Models\Rating;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TenantRatingOverview extends StatsOverviewWidget
{
    use HasWidgetShield;

    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $user = auth()->user();

        if ($user->can('task.manageAll')) {
            return $this->getTenantWideStats();
        }

        return $this->getPersonalStats();
    }

    /**
     * @return array<Stat>
     */
    private function getTenantWideStats(): array
    {
        $totalRatings = Rating::query()->count();
        $averageOverall = $totalRatings > 0 ? round((float) Rating::query()->avg('overall_score'), 2) : null;

        return [
            Stat::make('Total Ratings', $totalRatings)
                ->description('Across the whole tenant')
                ->icon(Heroicon::OutlinedStar)
                ->color('primary')
                ->url(RatingResource::getUrl('index')),

            Stat::make('Average Overall Score', $averageOverall !== null ? number_format($averageOverall, 2).' / 5' : '—')
                ->description('Employees rated tenant-wide')
                ->icon(Heroicon::OutlinedChartBar)
                ->color(match (true) {
                    $averageOverall === null => 'gray',
                    $averageOverall >= 4 => 'success',
                    $averageOverall >= 3 => 'warning',
                    default => 'danger',
                })
                ->url(RatingResource::getUrl('index')),
        ];
    }

    /**
     * @return array<Stat>
     */
    private function getPersonalStats(): array
    {
        $latestRating = Rating::query()->where('employee_id', auth()->id())->latest('created_at')->first();

        return [
            Stat::make('My Latest Rating', $latestRating ? number_format((float) $latestRating->overall_score, 1).' / 5' : '—')
                ->description($latestRating ? 'Rated '.$latestRating->created_at->diffForHumans() : 'No ratings yet')
                ->icon(Heroicon::OutlinedStar)
                ->color(match (true) {
                    $latestRating === null => 'gray',
                    (float) $latestRating->overall_score >= 4 => 'success',
                    (float) $latestRating->overall_score >= 3 => 'warning',
                    default => 'danger',
                })
                ->url(RatingResource::getUrl('index')),
        ];
    }
}

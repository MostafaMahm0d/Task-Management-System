<?php

namespace App\Filament\Tenant\Pages;

use App\Filament\Tenant\Widgets\ClockWidget;
use App\Filament\Tenant\Widgets\DashboardOverview;
use App\Filament\Tenant\Widgets\MyProjects;
use App\Filament\Tenant\Widgets\MyTasksWidget;
use App\Filament\Tenant\Widgets\RecentActivity;
use App\Filament\Tenant\Widgets\TaskStatusChart;
use App\Filament\Tenant\Widgets\TenantRatingOverview;
use App\Filament\Tenant\Widgets\UpcomingDeadlines;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\AccountWidget;

class Dashboard extends BaseDashboard
{
    use HasPageShield;

    public function getWidgets(): array
    {
        return [
            AccountWidget::class,
            ClockWidget::class,
            DashboardOverview::class,
            TenantRatingOverview::class,
            TaskStatusChart::class,
            UpcomingDeadlines::class,
            RecentActivity::class,
            MyProjects::class,
            MyTasksWidget::class,
        ];
    }
}

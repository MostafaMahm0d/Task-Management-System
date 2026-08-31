<?php

namespace App\Filament\Tenant\Widgets;

use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\Widget;

class ClockWidget extends Widget
{
    use HasWidgetShield;

    protected static ?int $sort = -2;

    protected static bool $isLazy = false;

    protected string $view = 'filament.tenant.widgets.clock-widget';
}

<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\CentralPanelProvider;
use App\Providers\Filament\TenantPanelProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\MailSettingsServiceProvider;
use App\Providers\TenancyServiceProvider;

return [
    AppServiceProvider::class,
    CentralPanelProvider::class,
    TenantPanelProvider::class,
    HorizonServiceProvider::class,
    MailSettingsServiceProvider::class,
    TenancyServiceProvider::class,
];

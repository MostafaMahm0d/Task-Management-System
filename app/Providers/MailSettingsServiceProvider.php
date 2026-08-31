<?php

namespace App\Providers;

use App\Models\MailSetting;
use Illuminate\Support\ServiceProvider;
use Throwable;

class MailSettingsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        try {
            MailSetting::query()->first()?->applyToConfig();
        } catch (Throwable) {
            // mail_settings table not migrated yet (e.g. during initial install)
        }
    }
}

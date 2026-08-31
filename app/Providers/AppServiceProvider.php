<?php

namespace App\Providers;

use App\Filament\Tenant\Livewire\ActivityTimeline;
use App\Models\Task;
use App\Observers\RoleObserver;
use App\Observers\TaskObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Spatie\Permission\Events\PermissionAttachedEvent;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Task::observe(TaskObserver::class);
        Event::listen(PermissionAttachedEvent::class, RoleObserver::class);

        Livewire::component('activity-timeline', ActivityTimeline::class);
    }
}

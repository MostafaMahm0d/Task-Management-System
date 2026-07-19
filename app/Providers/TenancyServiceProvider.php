<?php

declare(strict_types=1);

namespace App\Providers;

use App\Jobs\Tenant\CreateSuperAdminForTenant;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Stancl\JobPipeline\JobPipeline;
use Stancl\Tenancy\Events;
use Stancl\Tenancy\Jobs;
use Stancl\Tenancy\Listeners;
use Stancl\Tenancy\Middleware;

class TenancyServiceProvider extends ServiceProvider
{
    // By default, no namespace is used to support the callable array syntax.
    public static string $controllerNamespace = '';

    public function events()
    {
        return [
            // Tenant events
            Events\CreatingTenant::class => [],
            Events\TenantCreated::class => [
                JobPipeline::make([
                    Jobs\CreateDatabase::class,
                    Jobs\MigrateDatabase::class,
                    Jobs\SeedDatabase::class,
                    CreateSuperAdminForTenant::class,

                    // Your own jobs to prepare the tenant.
                    // Provision API keys, create S3 buckets, anything you want!

                ])->send(function (Events\TenantCreated $event) {
                    return $event->tenant;
                })->shouldBeQueued(false), // `false` by default, but you probably want to make this `true` for production.
            ],
            Events\SavingTenant::class => [],
            Events\TenantSaved::class => [],
            Events\UpdatingTenant::class => [],
            Events\TenantUpdated::class => [],
            Events\DeletingTenant::class => [],
            Events\TenantDeleted::class => [
                JobPipeline::make([
                    Jobs\DeleteDatabase::class,
                ])->send(function (Events\TenantDeleted $event) {
                    return $event->tenant;
                })->shouldBeQueued(false), // `false` by default, but you probably want to make this `true` for production.
            ],

            // Domain events
            Events\CreatingDomain::class => [],
            Events\DomainCreated::class => [],
            Events\SavingDomain::class => [],
            Events\DomainSaved::class => [],
            Events\UpdatingDomain::class => [],
            Events\DomainUpdated::class => [],
            Events\DeletingDomain::class => [],
            Events\DomainDeleted::class => [],

            // Database events
            Events\DatabaseCreated::class => [],
            Events\DatabaseMigrated::class => [],
            Events\DatabaseSeeded::class => [],
            Events\DatabaseRolledBack::class => [],
            Events\DatabaseDeleted::class => [],

            // Tenancy events
            Events\InitializingTenancy::class => [],
            Events\TenancyInitialized::class => [
                Listeners\BootstrapTenancy::class,
            ],

            Events\EndingTenancy::class => [],
            Events\TenancyEnded::class => [
                Listeners\RevertToCentralContext::class,
            ],

            Events\BootstrappingTenancy::class => [],
            Events\TenancyBootstrapped::class => [
                function () {
                    $this->retargetPublicDiskUrlAtCurrentDomain();
                },
            ],
            Events\RevertingToCentralContext::class => [],
            Events\RevertedToCentralContext::class => [],

            // Resource syncing
            Events\SyncedResourceSaved::class => [
                Listeners\UpdateSyncedResource::class,
            ],

            // Fired only when a synced resource is changed in a different DB than the origin DB (to avoid infinite loops)
            Events\SyncedResourceChangedInForeignDatabase::class => [],
        ];
    }

    public function register()
    {
        //
    }

    public function boot()
    {
        $this->bootEvents();
        $this->mapRoutes();
        $this->mapLivewireUpdateRoute();
        $this->mapLivewireUploadRoute();
        $this->mapStorageServeRoute();
        $this->makeTenancyMiddlewareHighestPriority();
    }

    /**
     * Livewire's update endpoint is registered globally and isn't covered by
     * a Filament panel's own middleware, so it never initializes tenancy by
     * default. That breaks Livewire-driven forms (e.g. the panel login) on
     * tenant domains: the page loads with tenancy initialized, but the form
     * submission is handled in the central context, causing a CSRF/session
     * mismatch. Re-registering the route here, tagged with the `universal`
     * middleware group, lets it initialize tenancy on tenant domains while
     * still working unchanged on central domains (see UniversalRoutes
     * feature in config/tenancy.php).
     */
    protected function mapLivewireUpdateRoute()
    {
        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/livewire/update', $handle)
                ->middleware(['web', 'universal', Middleware\InitializeTenancyByDomain::class]);
        });
    }

    /**
     * Livewire's file upload endpoint is also registered globally (see
     * mapLivewireUpdateRoute above) and has no config hook to re-register the
     * route, but it does read `livewire.temporary_file_upload.middleware`
     * when resolving its controller middleware. Add tenancy initialization
     * there so uploads on tenant domains use the tenant's session/DB
     * connection instead of the central one, which otherwise causes a
     * CSRF/session mismatch (419) on every upload.
     */
    protected function mapLivewireUploadRoute()
    {
        config([
            'livewire.temporary_file_upload.middleware' => [
                'web', 'universal', Middleware\InitializeTenancyByDomain::class, 'throttle:60,1',
            ],
        ]);
    }

    /**
     * Laravel's built-in `public` disk serve route (`storage/{path}`, enabled
     * via `serve` in config/filesystems.php) is also registered globally with
     * no tenancy awareness. Since FilesystemTenancyBootstrapper suffixes
     * storage_path() per tenant, files like comment attachments live under
     * storage/tenant{id}/app/public/... — without tenancy initialized first,
     * the route resolves the `public` disk against the central root and
     * 404s/403s. The route is registered lazily in a `booted()` callback by
     * FilesystemServiceProvider, and callback firing order between providers
     * isn't reliable, so we attach the middleware on `RouteMatched` instead:
     * it always fires after the full route table (including lazily added
     * routes) exists, but before middleware is gathered for dispatch.
     */
    protected function mapStorageServeRoute()
    {
        Event::listen(RouteMatched::class, function ($event) {
            if ($event->route->getName() === 'storage.public') {
                $event->route->middleware(['universal', Middleware\InitializeTenancyByDomain::class]);
            }
        });
    }

    /**
     * FilesystemTenancyBootstrapper suffixes the `public` disk's root per
     * tenant but leaves its `url` untouched, so every generated public URL
     * (e.g. comment attachments) still points at the central APP_URL
     * regardless of which tenant domain is being browsed. Retarget it to the
     * current request's own domain so links resolve on the correct tenant.
     */
    protected function retargetPublicDiskUrlAtCurrentDomain()
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        config([
            'filesystems.disks.public.url' => rtrim($this->app['request']->getSchemeAndHttpHost(), '/').'/storage',
        ]);
    }

    protected function bootEvents()
    {
        foreach ($this->events() as $event => $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof JobPipeline) {
                    $listener = $listener->toListener();
                }

                Event::listen($event, $listener);
            }
        }
    }

    protected function mapRoutes()
    {
        $this->app->booted(function () {
            if (file_exists(base_path('routes/tenant.php'))) {
                Route::namespace(static::$controllerNamespace)
                    ->group(base_path('routes/tenant.php'));
            }
        });
    }

    protected function makeTenancyMiddlewareHighestPriority()
    {
        $tenancyMiddleware = [
            // Even higher priority than the initialization middleware
            Middleware\PreventAccessFromCentralDomains::class,

            Middleware\InitializeTenancyByDomain::class,
            Middleware\InitializeTenancyBySubdomain::class,
            Middleware\InitializeTenancyByDomainOrSubdomain::class,
            Middleware\InitializeTenancyByPath::class,
            Middleware\InitializeTenancyByRequestData::class,
        ];

        foreach (array_reverse($tenancyMiddleware) as $middleware) {
            $this->app[Kernel::class]->prependToMiddlewarePriority($middleware);
        }
    }
}

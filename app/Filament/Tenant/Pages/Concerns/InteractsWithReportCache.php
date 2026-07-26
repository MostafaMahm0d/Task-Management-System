<?php

namespace App\Filament\Tenant\Pages\Concerns;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Report stat/chart widgets cache their aggregation results in the 'redis' store explicitly
 * (the app's default cache store is 'database', which doesn't support the tags stancl/tenancy's
 * CacheManager wraps facade calls in). Redis isn't tenant-prefixed here — this app's
 * RedisTenancyBootstrapper is disabled (it needs phpredis; the app uses predis) — so every
 * key below embeds the tenant id manually to avoid cross-tenant cache leakage.
 *
 * Table queries themselves aren't cached through this trait: Filament's table pagination,
 * sorting and searching need a live query Builder to work, so only the widgets above each
 * report table (which return a small, already-computed array/collection) are cached here.
 */
trait InteractsWithReportCache
{
    protected function reportCacheKey(string $slug): string
    {
        return 'reports:'.tenant('id').':'.$slug;
    }

    protected function rememberReport(string $slug, Closure $callback): mixed
    {
        return Cache::store('redis')->remember($this->reportCacheKey($slug), now()->addMinutes(5), $callback);
    }

    protected function forgetReportCache(string $slug): void
    {
        Cache::store('redis')->forget($this->reportCacheKey($slug));
    }
}

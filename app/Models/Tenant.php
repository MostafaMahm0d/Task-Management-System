<?php

namespace App\Models;

use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    public function url(string $path = ''): string
    {
        $appUrl = config('app.url');
        $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?? 'http';
        $port = parse_url($appUrl, PHP_URL_PORT);
        $portSuffix = ($port === null || in_array($port, [80, 443], true)) ? '' : ":{$port}";
        $domain = $this->domains()->first()?->domain;

        return "{$scheme}://{$domain}{$portSuffix}{$path}";
    }
}

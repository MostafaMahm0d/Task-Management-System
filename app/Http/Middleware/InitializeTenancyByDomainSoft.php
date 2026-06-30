<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenancyByDomainSoft
{
    public function __construct(protected InitializeTenancyByDomain $tenancyMiddleware) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->getHost(), config('tenancy.central_domains', []))) {
            return $next($request);
        }

        return $this->tenancyMiddleware->handle($request, $next);
    }
}

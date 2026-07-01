<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventAccessIfTenantSuspended
{
    public function handle(Request $request, Closure $next): Response
    {
        if (tenant('is_active') === false) {
            abort(503, 'This tenant has been suspended.');
        }

        return $next($request);
    }
}

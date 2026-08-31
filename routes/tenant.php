<?php

declare(strict_types=1);

use App\Http\Middleware\PreventAccessIfTenantSuspended;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    PreventAccessIfTenantSuspended::class,
])->group(function () {
    Route::get('/', function () {
        return 'This is your multi-tenant application. The id of the current tenant is '.tenant('id');
    });

    Route::get('/app/impersonate/{token}', function (string $token) {
        $central = DB::connection(config('tenancy.database.central_connection'));

        $central->table('impersonation_tokens')->where('expires_at', '<=', now())->delete();

        $record = $central->table('impersonation_tokens')
            ->where('token', $token)
            ->where('tenant_id', tenant('id'))
            ->where('expires_at', '>', now())
            ->first();

        abort_unless($record, 404);

        Auth::guard('web')->login(User::findOrFail($record->tenant_user_id));
        session(['via_central_impersonation_expires_at' => now()->addMinutes(30)]);

        return redirect('/app');
    })->name('tenant.impersonate');
});

<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Middleware\PreventAccessIfTenantSuspended;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::prefix('v1')
    ->middleware([
        InitializeTenancyByDomain::class,
        PreventAccessFromCentralDomains::class,
        PreventAccessIfTenantSuspended::class,
    ])
    ->group(function () {
        Route::post('/login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
            Route::apiResource('tasks', TaskController::class);
        });
    });

<?php

namespace App\Filament\Central\Resources\Tenants\Actions;

use App\Models\Tenant;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ManageTenantUsersAction
{
    public static function make(): Action
    {
        return Action::make('manageUsers')
            ->label('Manage Users')
            ->icon(Heroicon::OutlinedUsers)
            ->color('gray')
            ->authorize('manageUsers')
            ->url(function (Tenant $record): ?string {
                $domain = $record->domains()->first()?->domain;

                if (blank($domain)) {
                    return null;
                }

                $tenantUserId = $record->run(function () {
                    $user = User::role('super_admin')->first()
                        ?? User::role('tenant_admin')->first()
                        ?? User::first();

                    return $user?->id;
                });

                if (! $tenantUserId) {
                    return null;
                }

                $token = Str::random(40);

                DB::table('impersonation_tokens')->insert([
                    'token' => $token,
                    'tenant_id' => $record->id,
                    'tenant_user_id' => $tenantUserId,
                    'expires_at' => now()->addMinutes(5),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $scheme = request()->getScheme();
                $port = request()->getPort();
                $portSuffix = in_array($port, [80, 443], true) ? '' : ":{$port}";

                return "{$scheme}://{$domain}{$portSuffix}/app/impersonate/{$token}";
            })
            ->disabled(fn (Tenant $record): bool => blank($record->domains()->first()?->domain))
            ->openUrlInNewTab();
    }
}

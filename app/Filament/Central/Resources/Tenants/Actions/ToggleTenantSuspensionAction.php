<?php

namespace App\Filament\Central\Resources\Tenants\Actions;

use App\Models\Tenant;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class ToggleTenantSuspensionAction
{
    public static function make(): Action
    {
        return Action::make('toggleSuspension')
            ->label(fn (Tenant $record): string => $record->is_active === false ? 'Resume Tenant' : 'Pause Tenant')
            ->icon(fn (Tenant $record): string|Heroicon => $record->is_active === false ? Heroicon::OutlinedPlay : Heroicon::OutlinedPause)
            ->color(fn (Tenant $record): string => $record->is_active === false ? 'success' : 'danger')
            ->authorize('suspend')
            ->requiresConfirmation()
            ->action(fn (Tenant $record) => $record->update(['is_active' => $record->is_active === false]));
    }
}

<?php

namespace App\Filament\Tenant\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;

class ViewActivityAction
{
    public static function make(): Action
    {
        return Action::make('viewActivity')
            ->label('Activity')
            ->icon(Heroicon::OutlinedClock)
            ->color('gray')
            ->authorize('viewActivity')
            ->slideOver()
            ->modalHeading('Activity Log')
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->modalContent(fn (Model $record): View => view('filament.tenant.components.activity-modal', [
                'record' => $record,
            ]));
    }
}

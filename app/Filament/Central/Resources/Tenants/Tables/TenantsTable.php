<?php

namespace App\Filament\Central\Resources\Tenants\Tables;

use App\Filament\Central\Resources\Tenants\Actions\ToggleTenantSuspensionAction;
use App\Models\Tenant;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TenantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Identifier')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('domains.domain')
                    ->label('Domain')
                    ->searchable(),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->getStateUsing(fn (Tenant $record): bool => $record->is_active !== false)
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-pause-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                ToggleTenantSuspensionAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

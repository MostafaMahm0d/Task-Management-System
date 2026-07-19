<?php

namespace App\Filament\Tenant\Resources\Ratings\Tables;

use App\Models\Rating;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RatingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('rater.name')
                    ->label('Rated by')
                    ->formatStateUsing(fn (?string $state): string => auth()->user()?->can('rating.manageAll') ? $state : '****')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('project.name')
                    ->label('Project')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('overall_score')
                    ->label('Overall')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        (float) $state >= 4 => 'success',
                        (float) $state >= 3 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),

                ...collect(Rating::metrics())
                    ->map(fn (string $label, string $metric) => TextColumn::make($metric)
                        ->label($label)
                        ->toggleable(isToggledHiddenByDefault: true)
                        ->sortable())
                    ->all(),

                TextColumn::make('period_start')
                    ->label('Period')
                    ->date()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Evaluated on')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('employee')
                    ->relationship('employee', 'name')
                    ->searchable(),

                SelectFilter::make('project')
                    ->relationship('project', 'name', modifyQueryUsing: fn (Builder $query) => $query->visibleTo(auth()->user()))
                    ->searchable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

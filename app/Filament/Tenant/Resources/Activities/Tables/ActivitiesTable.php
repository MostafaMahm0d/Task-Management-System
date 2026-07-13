<?php

namespace App\Filament\Tenant\Resources\Activities\Tables;

use App\Filament\Tenant\Resources\Activities\ActivityResource;
use App\Models\Project;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Project $record): string => ActivityResource::getUrl('view', ['record' => $record]))
            ->defaultSort('created_at', 'desc')
            ->contentGrid([
                'default' => 1,
                'md' => 2,
                'xl' => 3,
            ])
            ->columns([
                Stack::make([
                    TextColumn::make('name')
                        ->label('Project')
                        ->weight(FontWeight::Bold)
                        ->size('lg')
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('owner.name')
                        ->label('Owner')
                        ->icon('heroicon-o-user')
                        ->color('gray')
                        ->searchable(),
                    TextColumn::make('stats')
                        ->label('')
                        ->state(fn (Project $record): string => "{$record->tasks()->count()} tasks · {$record->members()->count()} members")
                        ->color('gray')
                        ->size('sm'),
                    TextColumn::make('activity_count')
                        ->label('')
                        ->state(fn (Project $record): string => $record->activityLogQuery()->count().' activity events')
                        ->badge()
                        ->color('primary'),
                    TextColumn::make('last_activity_at')
                        ->label('')
                        ->state(function (Project $record): string {
                            $latest = $record->activityLogQuery()->latest('created_at')->value('created_at');

                            return $latest ? "Last activity {$latest->diffForHumans()}" : 'No activity yet';
                        })
                        ->color('gray')
                        ->size('sm'),
                ])->space(2),
            ])
            ->filters([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}

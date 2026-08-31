<?php

namespace App\Filament\Tenant\Resources\Activities\Tables;

use App\Filament\Tenant\Resources\Activities\ActivityResource;
use App\Models\Activity;
use App\Models\Task;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TaskActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Task $record): string => ActivityResource::getUrl('view-task', ['record' => $record]))
            ->defaultSort('created_at', 'desc')
            ->contentGrid([
                'default' => 1,
                'md' => 2,
                'xl' => 3,
            ])
            ->columns([
                Stack::make([
                    TextColumn::make('title')
                        ->label('Task')
                        ->weight(FontWeight::Bold)
                        ->size('lg')
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('project.name')
                        ->label('Project')
                        ->icon('heroicon-o-folder')
                        ->color('gray')
                        ->searchable(),
                    TextColumn::make('status.name')
                        ->label('Status')
                        ->badge()
                        ->color(fn (Task $record): string => $record->status?->color ?? 'gray'),
                    TextColumn::make('assignee.name')
                        ->label('Assignee')
                        ->icon('heroicon-o-user')
                        ->color('gray')
                        ->default('Unassigned'),
                    TextColumn::make('activity_count')
                        ->label('')
                        ->state(fn (Task $record): string => Activity::query()->forSubject($record)->count().' activity events')
                        ->badge()
                        ->color('primary'),
                    TextColumn::make('last_activity_at')
                        ->label('')
                        ->state(function (Task $record): string {
                            $latest = Activity::query()->forSubject($record)->latest('created_at')->value('created_at');

                            return $latest ? "Last activity {$latest->diffForHumans()}" : 'No activity yet';
                        })
                        ->color('gray')
                        ->size('sm'),
                ])->space(2),
            ])
            ->filters([
                SelectFilter::make('project')
                    ->relationship('project', 'name')
                    ->searchable(),

                SelectFilter::make('assignee')
                    ->relationship('assignee', 'name')
                    ->searchable(),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}

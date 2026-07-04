<?php

namespace App\Filament\Tenant\Resources\Tasks\Tables;

use App\Models\Task;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('project.name')
                    ->label('Project')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Task::STATUS_DONE => 'success',
                        Task::STATUS_IN_PROGRESS, Task::STATUS_IN_REVIEW => 'info',
                        Task::STATUS_CANCELLED => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Task::PRIORITY_URGENT => 'danger',
                        Task::PRIORITY_HIGH => 'warning',
                        Task::PRIORITY_LOW => 'gray',
                        default => 'info',
                    })
                    ->sortable(),

                TextColumn::make('assignee.name')
                    ->label('Assignee')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('labels.name')
                    ->label('Labels')
                    ->badge(),

                TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        Task::STATUS_TODO => 'To Do',
                        Task::STATUS_IN_PROGRESS => 'In Progress',
                        Task::STATUS_IN_REVIEW => 'In Review',
                        Task::STATUS_DONE => 'Done',
                        Task::STATUS_CANCELLED => 'Cancelled',
                    ]),

                SelectFilter::make('priority')
                    ->options([
                        Task::PRIORITY_LOW => 'Low',
                        Task::PRIORITY_MEDIUM => 'Medium',
                        Task::PRIORITY_HIGH => 'High',
                        Task::PRIORITY_URGENT => 'Urgent',
                    ]),

                SelectFilter::make('assignee')
                    ->relationship('assignee', 'name')
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

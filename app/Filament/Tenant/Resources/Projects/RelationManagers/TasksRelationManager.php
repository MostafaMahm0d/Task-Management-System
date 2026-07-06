<?php

namespace App\Filament\Tenant\Resources\Projects\RelationManagers;

use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),

                Textarea::make('description')
                    ->columnSpanFull(),

                Select::make('status_id')
                    ->label('Status')
                    ->relationship('status', 'name', modifyQueryUsing: fn ($query) => $query->orderBy('position'))
                    ->default(fn () => Status::where('is_default', true)->value('id'))
                    ->required(),

                Select::make('priority')
                    ->options([
                        Task::PRIORITY_LOW => 'Low',
                        Task::PRIORITY_MEDIUM => 'Medium',
                        Task::PRIORITY_HIGH => 'High',
                        Task::PRIORITY_URGENT => 'Urgent',
                    ])
                    ->default(Task::PRIORITY_MEDIUM)
                    ->required(),

                Select::make('assignee_id')
                    ->label('Assignee')
                    ->options(fn (): array => User::whereIn('id', $this->getOwnerRecord()->assignableUserIds())->pluck('name', 'id')->all())
                    ->searchable()
                    ->helperText('Only members of this project can be assigned.'),

                Select::make('reporter_id')
                    ->label('Reporter')
                    ->relationship('reporter', 'name')
                    ->default(fn () => auth()->id())
                    ->searchable()
                    ->preload()
                    ->required(),

                DatePicker::make('due_date'),

                TextInput::make('estimated_hours')
                    ->numeric()
                    ->suffix('hrs'),

                Select::make('labels')
                    ->relationship('labels', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')
                    ->searchable(),

                TextColumn::make('status.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn (Task $record): string => $record->status->color),

                TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Task::PRIORITY_URGENT => 'danger',
                        Task::PRIORITY_HIGH => 'warning',
                        Task::PRIORITY_LOW => 'gray',
                        default => 'info',
                    }),

                TextColumn::make('assignee.name')
                    ->label('Assignee'),

                TextColumn::make('due_date')
                    ->date(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

<?php

namespace App\Filament\Tenant\Resources\Projects\Resources\Tasks\Schemas;

use App\Enums\TaskPriority;
use App\Models\Status;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Task details')
                    ->description('What needs to be done.')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        RichEditor::make('description')
                            ->columnSpanFull(),
                    ]),

                Section::make('Status & priority')
                    ->description('Track progress and urgency.')
                    ->icon(Heroicon::OutlinedFlag)
                    ->schema([
                        Select::make('status_id')
                            ->label('Status')
                            ->relationship('status', 'name', modifyQueryUsing: fn ($query) => $query->orderBy('position'))
                            ->default(fn () => Status::where('is_default', true)->value('id'))
                            ->preload()
                            ->required(),

                        Select::make('priority')
                            ->options(TaskPriority::class)
                            ->default(TaskPriority::Medium)
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Assignment')
                    ->description('Who is doing the work and who asked for it.')
                    ->icon(Heroicon::OutlinedUsers)
                    ->schema([
                        Select::make('assignee_id')
                            ->label('Assignee')
                            ->options(fn (Page $livewire): array => User::whereIn('id', $livewire->getParentRecord()->assignableUserIds())->pluck('name', 'id')->all())
                            ->searchable()
                            ->helperText('Only members of this project can be assigned.'),

                        Select::make('reporter_id')
                            ->label('Reporter')
                            ->relationship('reporter', 'name')
                            ->default(fn () => auth()->id())
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Scheduling & labels')
                    ->icon(Heroicon::OutlinedCalendarDays)
                    ->schema([
                        DatePicker::make('due_date')
                            ->native(false),

                        TextInput::make('estimated_hours')
                            ->numeric()
                            ->suffix('hrs'),

                        Select::make('labels')
                            ->relationship('labels', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
            ->columns(1);
    }
}

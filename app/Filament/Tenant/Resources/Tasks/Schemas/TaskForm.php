<?php

namespace App\Filament\Tenant\Resources\Tasks\Schemas;

use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Task details')
                    ->description('What needs to be done and where it belongs.')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->schema([
                        Select::make('project_id')
                            ->label('Project')
                            ->relationship('project', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->columnSpanFull(),

                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->rows(4)
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
                            ->options([
                                Task::PRIORITY_LOW => 'Low',
                                Task::PRIORITY_MEDIUM => 'Medium',
                                Task::PRIORITY_HIGH => 'High',
                                Task::PRIORITY_URGENT => 'Urgent',
                            ])
                            ->default(Task::PRIORITY_MEDIUM)
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Assignment')
                    ->description('Who is doing the work and who asked for it.')
                    ->icon(Heroicon::OutlinedUsers)
                    ->schema([
                        Select::make('assignee_id')
                            ->label('Assignee')
                            ->options(function (Get $get): array {
                                $project = Project::find($get('project_id'));

                                return $project
                                    ? User::whereIn('id', $project->assignableUserIds())->pluck('name', 'id')->all()
                                    : [];
                            })
                            ->searchable()
                            ->helperText('Only members of the selected project can be assigned.'),

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

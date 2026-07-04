<?php

namespace App\Filament\Tenant\Resources\Tasks\Schemas;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('project_id')
                    ->label('Project')
                    ->relationship('project', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live(),

                TextInput::make('title')
                    ->required()
                    ->maxLength(255),

                Textarea::make('description')
                    ->columnSpanFull(),

                Select::make('status')
                    ->options([
                        Task::STATUS_TODO => 'To Do',
                        Task::STATUS_IN_PROGRESS => 'In Progress',
                        Task::STATUS_IN_REVIEW => 'In Review',
                        Task::STATUS_DONE => 'Done',
                        Task::STATUS_CANCELLED => 'Cancelled',
                    ])
                    ->default(Task::STATUS_TODO)
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
}

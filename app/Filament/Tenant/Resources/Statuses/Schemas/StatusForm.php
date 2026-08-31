<?php

namespace App\Filament\Tenant\Resources\Statuses\Schemas;

use App\Models\ProjectMember;
use App\Models\Status;
use App\Models\StatusAssignmentRule;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;

class StatusForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                ColorPicker::make('color')
                    ->required(),

                Toggle::make('is_default')
                    ->label('Default status for new tasks')
                    ->helperText('Only one status can be the default; setting this will unset it from any other status.'),

                Toggle::make('is_completed')
                    ->label('Counts as completed')
                    ->helperText('Tasks in this status satisfy dependencies of other tasks.'),

                Select::make('transitionsTo')
                    ->label('Allowed next statuses')
                    ->relationship(
                        'transitionsTo',
                        'name',
                        modifyQueryUsing: fn ($query, ?Status $record) => $record ? $query->whereKeyNot($record->id) : $query,
                    )
                    ->multiple()
                    ->preload()
                    ->helperText('Statuses a task can move to from here. Leave empty to allow moving to any status.'),

                Fieldset::make('Auto-assignment')
                    ->relationship('assignmentRule')
                    ->schema([
                        Select::make('strategy')
                            ->label('When a task enters this status')
                            ->options([
                                StatusAssignmentRule::STRATEGY_CREATOR => "Assign back to the task's creator",
                                StatusAssignmentRule::STRATEGY_OWNER => 'Assign to the project owner',
                                StatusAssignmentRule::STRATEGY_ROLE => 'Assign to a project role',
                            ])
                            ->live()
                            ->helperText('Leave blank to leave the assignee unchanged.'),

                        Select::make('role')
                            ->options(Arr::except(ProjectMember::roleOptions(), [ProjectMember::ROLE_OWNER, ProjectMember::ROLE_MEMBER]))
                            ->visible(fn (Get $get): bool => $get('strategy') === StatusAssignmentRule::STRATEGY_ROLE)
                            ->required(fn (Get $get): bool => $get('strategy') === StatusAssignmentRule::STRATEGY_ROLE),
                    ]),
            ]);
    }
}

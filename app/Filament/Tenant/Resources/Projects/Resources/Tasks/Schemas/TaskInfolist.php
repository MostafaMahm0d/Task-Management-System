<?php

namespace App\Filament\Tenant\Resources\Projects\Resources\Tasks\Schemas;

use App\Enums\TaskPriority;
use App\Models\Label;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class TaskInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(self::components());
    }

    /**
     * @return array<int, Component>
     */
    public static function components(): array
    {
        return [
            Section::make(fn (Task $record): string => $record->title)
                ->description(fn (Task $record): ?string => $record->project?->name)
                ->icon(Heroicon::OutlinedRectangleStack)
                ->afterHeader([
                    self::quickEditAction('title', [
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                    ]),
                ])
                ->schema([
                    TextEntry::make('status.name')
                        ->label('Status')
                        ->badge()
                        ->color(fn (Task $record): string => $record->status->color)
                        ->hintAction(self::quickEditAction('status_id', [
                            Select::make('status_id')
                                ->label('Status')
                                ->options(fn (Task $record): array => Status::orderBy('position')
                                    ->get()
                                    ->filter(fn (Status $status): bool => $record->status->allowsTransitionTo($status->id))
                                    ->pluck('name', 'id')
                                    ->all())
                                ->required(),
                        ], heading: 'Edit status')),

                    TextEntry::make('priority')
                        ->badge()
                        ->hintAction(self::quickEditAction('priority', [
                            Select::make('priority')
                                ->options(TaskPriority::class)
                                ->required(),
                        ])),

                    IconEntry::make('is_blocked')
                        ->label('Blocked')
                        ->state(fn (Task $record): bool => $record->isBlocked())
                        ->boolean(),

                    TextEntry::make('due_date')
                        ->date()
                        ->icon(Heroicon::OutlinedCalendarDays)
                        ->placeholder('No due date')
                        ->hintAction(self::quickEditAction('due_date', [
                            DatePicker::make('due_date')
                                ->native(false),
                        ])),

                    TextEntry::make('description')
                        ->placeholder('No description provided.')
                        ->columnSpanFull()
                        ->hintAction(self::quickEditAction('description', [
                            Textarea::make('description')
                                ->rows(4),
                        ], width: Width::Large)),
                ])
                ->columns(4),

            Section::make('People')
                ->icon(Heroicon::OutlinedUsers)
                ->schema([
                    TextEntry::make('assignee.name')
                        ->label('Assignee')
                        ->icon(Heroicon::OutlinedUserCircle)
                        ->placeholder('Unassigned')
                        ->hintAction(self::quickEditAction('assignee_id', [
                            Select::make('assignee_id')
                                ->label('Assignee')
                                ->options(fn (Task $record): array => User::whereIn('id', $record->project->assignableUserIds())->pluck('name', 'id')->all())
                                ->searchable(),
                        ], heading: 'Edit assignee')),

                    TextEntry::make('reporter.name')
                        ->label('Reporter')
                        ->icon(Heroicon::OutlinedUserCircle)
                        ->hintAction(self::quickEditAction('reporter_id', [
                            Select::make('reporter_id')
                                ->label('Reporter')
                                ->options(fn (): array => User::orderBy('name')->pluck('name', 'id')->all())
                                ->searchable()
                                ->required(),
                        ], heading: 'Edit reporter')),

                    TextEntry::make('estimated_hours')
                        ->label('Estimate')
                        ->suffix(' hrs')
                        ->placeholder('Not estimated')
                        ->hintAction(self::quickEditAction('estimated_hours', [
                            TextInput::make('estimated_hours')
                                ->label('Estimate')
                                ->numeric()
                                ->suffix('hrs'),
                        ], heading: 'Edit estimate')),
                ])
                ->columns(3),

            Section::make('Labels & dependencies')
                ->icon(Heroicon::OutlinedTag)
                ->schema([
                    TextEntry::make('labels.name')
                        ->label('Labels')
                        ->badge()
                        ->placeholder('No labels')
                        ->hintAction(self::quickEditLabelsAction()),

                    TextEntry::make('dependsOn.title')
                        ->label('Depends on')
                        ->badge()
                        ->color('gray')
                        ->placeholder('No dependencies'),

                    TextEntry::make('parent.title')
                        ->label('Parent task')
                        ->placeholder('Top-level task'),

                    TextEntry::make('subtasks.title')
                        ->label('Subtasks')
                        ->badge()
                        ->color('gray')
                        ->placeholder('No subtasks'),
                ])
                ->columns(2)
                ->collapsed(fn (Task $record): bool => $record->labels->isEmpty() && $record->dependsOn->isEmpty() && $record->subtasks->isEmpty()),
        ];
    }

    /**
     * @param  array<int, Component>  $fields
     */
    protected static function quickEditAction(string $attribute, array $fields, ?string $heading = null, Width $width = Width::Medium): Action
    {
        return Action::make("edit_{$attribute}")
            ->iconButton()
            ->icon(Heroicon::OutlinedPencilSquare)
            ->tooltip('Edit')
            ->authorize('update')
            ->overlayParentActions()
            ->modalHeading($heading ?? 'Edit '.str_replace('_', ' ', $attribute))
            ->modalWidth($width)
            ->schema($fields)
            ->fillForm(function (Task $record) use ($attribute): array {
                $value = $record->getAttribute($attribute);

                return [$attribute => $value instanceof BackedEnum ? $value->value : $value];
            })
            ->action(function (array $data, Task $record, Action $action): void {
                $record->update($data);
                $record->refresh();

                $action->success();
            })
            ->successNotificationTitle('Task updated');
    }

    protected static function quickEditLabelsAction(): Action
    {
        return Action::make('edit_labels')
            ->iconButton()
            ->icon(Heroicon::OutlinedPencilSquare)
            ->tooltip('Edit')
            ->authorize('update')
            ->overlayParentActions()
            ->modalHeading('Edit labels')
            ->modalWidth(Width::Medium)
            ->schema([
                Select::make('labels')
                    ->label('Labels')
                    ->options(fn (): array => Label::orderBy('name')->pluck('name', 'id')->all())
                    ->multiple()
                    ->searchable(),
            ])
            ->fillForm(fn (Task $record): array => ['labels' => $record->labels->pluck('id')->all()])
            ->action(function (array $data, Task $record, Action $action): void {
                $record->labels()->sync($data['labels'] ?? []);
                $record->refresh();

                $action->success();
            })
            ->successNotificationTitle('Task updated');
    }
}

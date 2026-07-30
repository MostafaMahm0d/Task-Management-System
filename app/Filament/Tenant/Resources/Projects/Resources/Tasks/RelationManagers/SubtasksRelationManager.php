<?php

namespace App\Filament\Tenant\Resources\Projects\Resources\Tasks\RelationManagers;

use App\Enums\TaskPriority;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SubtasksRelationManager extends RelationManager
{
    protected static string $relationship = 'subtasks';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->parent_task_id === null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components(self::subtaskFields($this->getOwnerRecord()));
    }

    /**
     * @return array<int, Component>
     */
    public static function subtaskFields(Task $ownerRecord): array
    {
        return [
            TextInput::make('title')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Select::make('status_id')
                ->label('Status')
                ->options(fn (): array => Status::orderBy('position')->pluck('name', 'id')->all())
                ->default(fn (): ?int => Status::where('is_default', true)->value('id'))
                ->required(),

            Select::make('priority')
                ->options(TaskPriority::class)
                ->default(TaskPriority::Medium)
                ->required(),

            Select::make('assignee_id')
                ->label('Assignee')
                ->options(fn (): array => User::whereIn('id', $ownerRecord->project->assignableUserIds())->pluck('name', 'id')->all())
                ->searchable(),

            DatePicker::make('due_date')
                ->native(false),
        ];
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
                    ->badge(),

                TextColumn::make('assignee.name')
                    ->label('Assignee'),

                TextColumn::make('due_date')
                    ->date(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(fn (array $data): array => [
                        ...$data,
                        'project_id' => $this->getOwnerRecord()->project_id,
                        'reporter_id' => auth()->id(),
                    ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

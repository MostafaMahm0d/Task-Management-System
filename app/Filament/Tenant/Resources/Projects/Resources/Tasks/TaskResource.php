<?php

namespace App\Filament\Tenant\Resources\Projects\Resources\Tasks;

use App\Filament\Tenant\Resources\Projects\ProjectResource;
use App\Filament\Tenant\Resources\Projects\Resources\Tasks\Pages\Board;
use App\Filament\Tenant\Resources\Projects\Resources\Tasks\Pages\CreateTask;
use App\Filament\Tenant\Resources\Projects\Resources\Tasks\Pages\EditTask;
use App\Filament\Tenant\Resources\Projects\Resources\Tasks\Pages\ListTasks;
use App\Filament\Tenant\Resources\Projects\Resources\Tasks\Pages\ViewTask;
use App\Filament\Tenant\Resources\Projects\Resources\Tasks\RelationManagers\DependsOnRelationManager;
use App\Filament\Tenant\Resources\Projects\Resources\Tasks\RelationManagers\SubtasksRelationManager;
use App\Filament\Tenant\Resources\Projects\Resources\Tasks\Schemas\TaskForm;
use App\Filament\Tenant\Resources\Projects\Resources\Tasks\Schemas\TaskInfolist;
use App\Filament\Tenant\Resources\Projects\Resources\Tasks\Tables\TasksTable;
use App\Models\Task;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $parentResource = ProjectResource::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (! $user = auth()->user()) {
            return $query;
        }

        return $query->visibleTo($user);
    }

    public static function form(Schema $schema): Schema
    {
        return TaskForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TaskInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TasksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            DependsOnRelationManager::class,
            SubtasksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTasks::route('/'),
            'board' => Board::route('/board'),
            'create' => CreateTask::route('/create'),
            'view' => ViewTask::route('/{record}'),
            'edit' => EditTask::route('/{record}/edit'),
        ];
    }
}

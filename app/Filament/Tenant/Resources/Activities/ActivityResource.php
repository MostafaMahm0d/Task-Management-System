<?php

namespace App\Filament\Tenant\Resources\Activities;

use App\Filament\Tenant\Resources\Activities\Pages\ListActivities;
use App\Filament\Tenant\Resources\Activities\Pages\ListTaskActivities;
use App\Filament\Tenant\Resources\Activities\Pages\ViewActivity;
use App\Filament\Tenant\Resources\Activities\Pages\ViewTaskActivity;
use App\Filament\Tenant\Resources\Activities\Tables\ActivitiesTable;
use App\Models\Project;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ActivityResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Activity Log';

    protected static ?string $modelLabel = 'Activity';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();

        if ($user?->can('activity.viewAll')) {
            return $query;
        }

        return $query->where(
            fn (Builder $query) => $query
                ->where('owner_id', $user?->id)
                ->orWhereHas('members', fn (Builder $query) => $query->whereKey($user?->id))
        );
    }

    public static function table(Table $table): Table
    {
        return ActivitiesTable::configure($table);
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('ViewAny:Activity');
    }

    public static function canView(Model $record): bool
    {
        return (bool) auth()->user()?->can('viewActivity', $record);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(mixed $record): bool
    {
        return false;
    }

    public static function canDelete(mixed $record): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivities::route('/'),
            'tasks' => ListTaskActivities::route('/tasks'),
            'view-task' => ViewTaskActivity::route('/tasks/{record}'),
            'view' => ViewActivity::route('/{record}'),
        ];
    }
}

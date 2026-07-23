<?php

namespace App\Filament\Tenant\Resources\Projects;

use App\Filament\Tenant\Resources\Projects\Pages\CreateProject;
use App\Filament\Tenant\Resources\Projects\Pages\EditProject;
use App\Filament\Tenant\Resources\Projects\Pages\ListProjects;
use App\Filament\Tenant\Resources\Projects\Pages\ViewProject;
use App\Filament\Tenant\Resources\Projects\RelationManagers\MembersRelationManager;
use App\Filament\Tenant\Resources\Projects\RelationManagers\TasksRelationManager;
use App\Filament\Tenant\Resources\Projects\Resources\Tasks\TaskResource;
use App\Filament\Tenant\Resources\Projects\Schemas\ProjectForm;
use App\Filament\Tenant\Resources\Projects\Schemas\ProjectInfolist;
use App\Filament\Tenant\Resources\Projects\Tables\ProjectsTable;
use App\Models\Project;
use BackedEnum;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();

        if ($user?->hasRole('tenant_admin')) {
            return $query;
        }

        return $query->where(
            fn (Builder $query) => $query
                ->where('owner_id', $user?->id)
                ->orWhereHas('members', fn (Builder $query) => $query->whereKey($user?->id))
        );
    }

    public static function form(Schema $schema): Schema
    {
        return ProjectForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProjectInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProjectsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            MembersRelationManager::class,
            'tasks' => TasksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
            'view' => ViewProject::route('/{record}'),
            'edit' => EditProject::route('/{record}/edit'),
        ];
    }

    public static function getNavigationItems(): array
    {
        $items = parent::getNavigationItems();

        if (! $user = auth()->user()) {
            return $items;
        }

        $projects = static::getEloquentQuery()->orderBy('name')->get();

        foreach ($projects as $project) {
            $items[] = NavigationItem::make($project->name)
                ->group('My Work')
                ->icon(Heroicon::OutlinedFolder)
                ->sort(static::getNavigationSort())
                ->isActiveWhen(fn (): bool => (string) request()->route('project') === (string) $project->getKey())
                ->url(fn (): string => TaskResource::getUrl('index', ['project' => $project]));
        }

        return $items;
    }
}

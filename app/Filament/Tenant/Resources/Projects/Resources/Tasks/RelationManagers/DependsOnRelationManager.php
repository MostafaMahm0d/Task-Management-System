<?php

namespace App\Filament\Tenant\Resources\Projects\Resources\Tasks\RelationManagers;

use App\Models\Task;
use Filament\Actions\ActionGroup;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DependsOnRelationManager extends RelationManager
{
    protected static string $relationship = 'dependsOn';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->inverseRelationship('blocks')
            ->columns([
                TextColumn::make('title')
                    ->searchable(),

                TextColumn::make('status.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn (Task $record): string => $record->status->color),
            ])
            ->headerActions([
                AttachAction::make()
                    ->recordSelectOptionsQuery(fn (Builder $query): Builder => $query
                        ->where('project_id', $this->getOwnerRecord()->project_id)
                        ->whereKeyNot($this->getOwnerRecord()->getKey())),
            ])
            ->recordActions([
                ActionGroup::make([
                    DetachAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}

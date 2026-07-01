<?php

namespace App\Filament\Tenant\Widgets;

use App\Filament\Tenant\Resources\Projects\ProjectResource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentProjects extends TableWidget
{
    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => ProjectResource::getEloquentQuery())
            ->columns([
                TextColumn::make('name')
                    ->searchable(),

                TextColumn::make('owner.name')
                    ->label('Owner'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(false);
    }
}

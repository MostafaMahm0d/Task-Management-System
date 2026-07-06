<?php

namespace App\Filament\Tenant\Resources\Statuses;

use App\Filament\Tenant\Resources\Statuses\Pages\CreateStatus;
use App\Filament\Tenant\Resources\Statuses\Pages\EditStatus;
use App\Filament\Tenant\Resources\Statuses\Pages\ListStatuses;
use App\Filament\Tenant\Resources\Statuses\Schemas\StatusForm;
use App\Filament\Tenant\Resources\Statuses\Tables\StatusesTable;
use App\Models\Status;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StatusResource extends Resource
{
    protected static ?string $model = Status::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedViewColumns;

    public static function form(Schema $schema): Schema
    {
        return StatusForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StatusesTable::configure($table);
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
            'index' => ListStatuses::route('/'),
            'create' => CreateStatus::route('/create'),
            'edit' => EditStatus::route('/{record}/edit'),
        ];
    }
}

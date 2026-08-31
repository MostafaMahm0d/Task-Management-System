<?php

namespace App\Filament\Tenant\Resources\Labels;

use App\Filament\Tenant\Resources\Labels\Pages\CreateLabel;
use App\Filament\Tenant\Resources\Labels\Pages\EditLabel;
use App\Filament\Tenant\Resources\Labels\Pages\ListLabels;
use App\Filament\Tenant\Resources\Labels\Schemas\LabelForm;
use App\Filament\Tenant\Resources\Labels\Tables\LabelsTable;
use App\Models\Label;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LabelResource extends Resource
{
    protected static ?string $model = Label::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return LabelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LabelsTable::configure($table);
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
            'index' => ListLabels::route('/'),
            'create' => CreateLabel::route('/create'),
            'edit' => EditLabel::route('/{record}/edit'),
        ];
    }
}

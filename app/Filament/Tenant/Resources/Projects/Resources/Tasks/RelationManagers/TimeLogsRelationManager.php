<?php

namespace App\Filament\Tenant\Resources\Projects\Resources\Tasks\RelationManagers;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TimeLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'timeLogs';

    protected static ?string $title = 'Time logs';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $totalHours = $ownerRecord->totalLoggedHours();

        return $totalHours > 0 ? number_format($totalHours, 2).' hrs' : null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('hours')
                ->numeric()
                ->minValue(0.1)
                ->maxValue(999)
                ->suffix('hrs')
                ->required(),

            DatePicker::make('logged_on')
                ->label('Date')
                ->native(false)
                ->default(today())
                ->maxDate(today())
                ->required(),

            TextInput::make('note')
                ->maxLength(255)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Logged by'),

                TextColumn::make('hours')
                    ->suffix(' hrs')
                    ->sortable()
                    ->summarize(Sum::make()->label('Total')->suffix(' hrs')),

                TextColumn::make('note')
                    ->placeholder('—')
                    ->limit(50),

                TextColumn::make('logged_on')
                    ->label('Date')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('logged_on', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Log time')
                    ->modalHeading('Log time')
                    ->mutateDataUsing(fn (array $data): array => [
                        ...$data,
                        'user_id' => auth()->id(),
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

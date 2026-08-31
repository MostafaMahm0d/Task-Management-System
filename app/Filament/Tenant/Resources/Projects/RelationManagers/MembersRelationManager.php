<?php

namespace App\Filament\Tenant\Resources\Projects\RelationManagers;

use App\Models\ProjectMember;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),

                TextColumn::make('email')
                    ->searchable(),

                TextColumn::make('pivot.role')
                    ->label('Role')
                    ->badge(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->recordSelectSearchColumns(['name', 'email'])
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Select::make('role')
                            ->options(ProjectMember::roleOptions())
                            ->default(ProjectMember::ROLE_MEMBER)
                            ->required(),
                    ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('editRole')
                        ->label('Edit role')
                        ->schema([
                            Select::make('role')
                                ->options(ProjectMember::roleOptions())
                                ->required(),
                        ])
                        ->fillForm(fn (User $record): array => ['role' => $record->pivot->role])
                        ->action(fn (User $record, array $data) => $record->pivot->update(['role' => $data['role']])),
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

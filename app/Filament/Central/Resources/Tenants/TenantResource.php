<?php

namespace App\Filament\Central\Resources\Tenants;

use App\Filament\Central\Resources\Tenants\Pages\CreateTenant;
use App\Filament\Central\Resources\Tenants\Pages\EditTenant;
use App\Filament\Central\Resources\Tenants\Pages\ListTenants;
use App\Filament\Central\Resources\Tenants\Pages\ViewTenant;
use App\Filament\Central\Resources\Tenants\Schemas\TenantForm;
use App\Filament\Central\Resources\Tenants\Tables\TenantsTable;
use App\Models\Tenant;
use App\Models\User;
use BackedEnum;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    public static function form(Schema $schema): Schema
    {
        return TenantForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')
                    ->label('Identifier'),

                TextEntry::make('domains.domain')
                    ->label('Domain'),

                TextEntry::make('created_at')
                    ->dateTime(),

                TextEntry::make('updated_at')
                    ->dateTime(),

                Section::make('Users')
                    ->schema([
                        RepeatableEntry::make('tenantUsers')
                            ->label('')
                            ->state(function (Tenant $record): array {
                                $initialPassword = null;

                                if ($record->initial_super_admin_password) {
                                    try {
                                        $initialPassword = decrypt($record->initial_super_admin_password);
                                    } catch (\Throwable) {
                                        $record->forceFill(['initial_super_admin_password' => null])->save();
                                    }
                                }

                                return $record->run(function () use ($record, $initialPassword) {
                                    $users = User::with('roles')->get();

                                    if ($initialPassword) {
                                        $stillCurrent = $users->contains(
                                            fn (User $user): bool => Hash::check($initialPassword, $user->password)
                                        );

                                        if (! $stillCurrent) {
                                            $record->forceFill(['initial_super_admin_password' => null])->save();
                                            $initialPassword = null;
                                        }
                                    }

                                    return $users->map(fn (User $user): array => [
                                        'name' => $user->name,
                                        'email' => $user->email,
                                        'roles' => $user->roles->pluck('name')->implode(', ') ?: '—',
                                        'initial_password' => ($initialPassword && Hash::check($initialPassword, $user->password))
                                            ? $initialPassword
                                            : null,
                                    ])->all();
                                });
                            })
                            ->schema([
                                TextEntry::make('name'),
                                TextEntry::make('email'),
                                TextEntry::make('roles')
                                    ->label('Roles')
                                    ->badge(),
                                TextEntry::make('initial_password')
                                    ->label('Initial Password')
                                    ->placeholder('— changed —')
                                    ->copyable(),
                            ])
                            ->columns(4),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return TenantsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTenants::route('/'),
            'create' => CreateTenant::route('/create'),
            'view' => ViewTenant::route('/{record}'),
            'edit' => EditTenant::route('/{record}/edit'),
        ];
    }
}

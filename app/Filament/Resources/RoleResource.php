<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Models\Permission;
use App\Models\Role;
use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|UnitEnum|null $navigationGroup = 'Identité & accès';

    protected static ?string $navigationLabel = 'Rôles';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('slug')->label('Slug')->required()->maxLength(50)->unique(ignoreRecord: true),
            TextInput::make('name')->label('Nom')->required()->maxLength(100),
            Select::make('scope_level')->label('Niveau de périmètre')
                ->options([
                    'national' => 'National',
                    'regional' => 'Régional',
                    'departmental' => 'Départemental',
                    'establishment' => 'Établissement',
                    'self' => 'Individuel (candidat)',
                ])
                ->helperText("Doit correspondre à la colonne de périmètre renseignée sur les comptes utilisateurs (section 31.1).")
                ->required(),
            Textarea::make('description')->columnSpanFull(),
            CheckboxList::make('permissions')->label('Permissions')
                ->relationship('permissions', 'name')
                ->options(fn () => Permission::query()->pluck('name', 'id'))
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('scope_level')->label('Périmètre')->badge(),
                TextColumn::make('permissions_count')->label('Permissions')->counts('permissions'),
                TextColumn::make('users_count')->label('Utilisateurs')->counts('users'),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}

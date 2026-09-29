<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\PermissionResource\Pages;
use App\Models\Permission;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|UnitEnum|null $navigationGroup = 'Identité & accès';

    protected static ?string $navigationLabel = 'Permissions';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('slug')->label('Slug')->required()->maxLength(100)->unique(ignoreRecord: true)
                ->helperText('Ex: applications.validate'),
            TextInput::make('name')->label('Nom')->required()->maxLength(150),
            Textarea::make('description')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('slug')->label('Slug')->searchable()->sortable(),
                TextColumn::make('name')->label('Nom')->searchable(),
                TextColumn::make('roles_count')->label('Rôles')->counts('roles'),
            ])
            ->defaultSort('slug');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPermissions::route('/'),
            'create' => Pages\CreatePermission::route('/create'),
            'edit' => Pages\EditPermission::route('/{record}/edit'),
        ];
    }
}

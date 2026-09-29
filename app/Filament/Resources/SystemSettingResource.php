<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\SystemSettingResource\Pages;
use App\Models\SystemSetting;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class SystemSettingResource extends Resource
{
    protected static ?string $model = SystemSetting::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|UnitEnum|null $navigationGroup = 'Système';

    protected static ?string $navigationLabel = 'Paramètres';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('key')->label('Clé')->required()->maxLength(100)->unique(ignoreRecord: true)->disabled(fn (string $operation) => $operation === 'edit'),
            TextInput::make('value')->label('Valeur')->required()
                ->helperText('Saisie brute (texte, nombre, true/false) — convertie en JSON en base.'),
            Textarea::make('description')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')->label('Clé')->searchable()->sortable(),
                TextColumn::make('value')->label('Valeur')->formatStateUsing(fn ($state) => json_encode($state)),
                TextColumn::make('description')->label('Description')->limit(60),
                TextColumn::make('updatedBy.name')->label('Modifié par')->placeholder('—'),
            ])
            ->defaultSort('key');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSystemSettings::route('/'),
            'create' => Pages\CreateSystemSetting::route('/create'),
            'edit' => Pages\EditSystemSetting::route('/{record}/edit'),
        ];
    }
}

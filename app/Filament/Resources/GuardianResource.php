<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\GuardianResource\Pages;
use App\Models\Guardian;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class GuardianResource extends Resource
{
    protected static ?string $model = Guardian::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|UnitEnum|null $navigationGroup = 'Scolarité';

    protected static ?string $navigationLabel = 'Parents / Tuteurs';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('last_name')->label('Nom')->required()->maxLength(100),
            TextInput::make('first_name')->label('Prénom')->required()->maxLength(100),
            TextInput::make('phone')->label('Téléphone')->tel()->maxLength(30),
            TextInput::make('email')->label('E-mail')->email()->maxLength(150),
            TextInput::make('profession')->label('Profession')->maxLength(150),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('last_name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('first_name')->label('Prénom')->searchable()->sortable(),
                TextColumn::make('phone')->label('Téléphone'),
                TextColumn::make('email')->label('E-mail'),
                TextColumn::make('students_count')->label('Enfant(s)')->counts('students'),
            ])
            ->defaultSort('last_name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGuardians::route('/'),
            'create' => Pages\CreateGuardian::route('/create'),
            'edit' => Pages\EditGuardian::route('/{record}/edit'),
        ];
    }
}

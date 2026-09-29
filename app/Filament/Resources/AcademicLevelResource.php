<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\AcademicLevelResource\Pages;
use App\Models\AcademicLevel;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class AcademicLevelResource extends Resource
{
    protected static ?string $model = AcademicLevel::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bars-3-bottom-left';

    protected static string|UnitEnum|null $navigationGroup = 'Scolarité';

    protected static ?string $navigationLabel = 'Niveaux';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nom')->required()->maxLength(100),
            Select::make('cycle')->label('Cycle')
                ->options(['primaire' => 'Primaire', 'secondaire' => 'Secondaire'])
                ->required()->default('secondaire'),
            TextInput::make('order')->label('Ordre')->numeric()->required()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order')->label('#')->sortable(),
                TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('cycle')->label('Cycle')->badge(),
            ])
            ->defaultSort('order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAcademicLevels::route('/'),
            'create' => Pages\CreateAcademicLevel::route('/create'),
            'edit' => Pages\EditAcademicLevel::route('/{record}/edit'),
        ];
    }
}

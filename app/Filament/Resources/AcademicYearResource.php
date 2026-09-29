<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\AcademicYearResource\Pages;
use App\Models\AcademicYear;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class AcademicYearResource extends Resource
{
    protected static ?string $model = AcademicYear::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar';

    protected static string|UnitEnum|null $navigationGroup = 'Scolarité';

    protected static ?string $navigationLabel = 'Années scolaires';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('label')->label('Libellé')->required()->maxLength(20)
                ->helperText("Format : 2026-2027")->unique(ignoreRecord: true),
            DatePicker::make('start_date')->label('Date de début')->required(),
            DatePicker::make('end_date')->label('Date de fin')->required(),
            Toggle::make('is_current')->label('Année courante'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')->label('Libellé')->sortable(),
                TextColumn::make('start_date')->label('Début')->date('d/m/Y'),
                TextColumn::make('end_date')->label('Fin')->date('d/m/Y'),
                IconColumn::make('is_current')->label('Courante')->boolean(),
            ])
            ->defaultSort('start_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAcademicYears::route('/'),
            'create' => Pages\CreateAcademicYear::route('/create'),
            'edit' => Pages\EditAcademicYear::route('/{record}/edit'),
        ];
    }
}

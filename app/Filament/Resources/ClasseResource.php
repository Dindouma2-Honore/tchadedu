<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ClasseResource\Pages;
use App\Models\AcademicLevel;
use App\Models\AcademicYear;
use App\Models\Classe;
use App\Models\Establishment;
use App\Support\PerimeterScope;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ClasseResource extends Resource
{
    protected static ?string $model = Classe::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|UnitEnum|null $navigationGroup = 'Scolarité';

    protected static ?string $navigationLabel = 'Classes';

    public static function getEloquentQuery(): Builder
    {
        return PerimeterScope::forEstablishmentOwnedModel(parent::getEloquentQuery());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('establishment_id')->label('Établissement')
                ->options(fn () => Establishment::query()->orderBy('name')->pluck('name', 'id'))
                ->searchable()->required(),
            Select::make('academic_level_id')->label('Niveau')
                ->options(fn () => AcademicLevel::query()->orderBy('order')->pluck('name', 'id'))
                ->required(),
            Select::make('academic_year_id')->label('Année scolaire')
                ->options(fn () => AcademicYear::query()->orderBy('start_date', 'desc')->pluck('label', 'id'))
                ->required(),
            TextInput::make('name')->label('Nom')->required()->maxLength(50)->helperText('Ex: 6ème A'),
            TextInput::make('capacity')->label('Capacité')->numeric()->required()->default(50),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Classe')->searchable()->sortable(),
                TextColumn::make('establishment.name')->label('Établissement')->searchable()->sortable(),
                TextColumn::make('academicLevel.name')->label('Niveau'),
                TextColumn::make('academicYear.label')->label('Année scolaire'),
                TextColumn::make('capacity')->label('Capacité'),
                TextColumn::make('students_count')->label('Effectif')->counts('students'),
            ])
            ->filters([
                SelectFilter::make('establishment_id')->label('Établissement')
                    ->options(fn () => Establishment::query()->pluck('name', 'id')),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClasses::route('/'),
            'create' => Pages\CreateClasse::route('/create'),
            'edit' => Pages\EditClasse::route('/{record}/edit'),
        ];
    }
}

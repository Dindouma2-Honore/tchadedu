<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Models\Classe;
use App\Models\Establishment;
use App\Models\Student;
use App\Support\PerimeterScope;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user';

    protected static string|UnitEnum|null $navigationGroup = 'Scolarité';

    protected static ?string $navigationLabel = 'Élèves';

    public static function getEloquentQuery(): Builder
    {
        return PerimeterScope::forStudent(parent::getEloquentQuery());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('last_name')->label('Nom')->required()->maxLength(100),
            TextInput::make('first_name')->label('Prénom')->required()->maxLength(100),
            DatePicker::make('birth_date')->label('Date de naissance')->required(),
            TextInput::make('birth_place')->label('Lieu de naissance')->maxLength(150),
            Select::make('sex')->label('Sexe')->options(['M' => 'Masculin', 'F' => 'Féminin'])->required(),
            TextInput::make('nationality')->label('Nationalité')->required()->default('Tchadienne'),

            Select::make('establishment_id')->label('Établissement')->live()
                ->options(fn () => Establishment::query()->orderBy('name')->pluck('name', 'id'))
                ->searchable(),

            Select::make('class_id')->label('Classe')
                ->options(fn (Get $get) => Classe::query()
                    ->when($get('establishment_id'), fn ($q, $id) => $q->where('establishment_id', $id))
                    ->pluck('name', 'id'))
                ->searchable(),

            TextInput::make('matricule')->label('Matricule')
                ->helperText('Laissez vide — attribué automatiquement à la validation de la candidature.')
                ->disabled()->dehydrated(false),

            Select::make('status')->label('Statut')
                ->options([
                    'actif' => 'Actif',
                    'transfere' => 'Transféré',
                    'abandon' => 'Abandon',
                    'diplome' => 'Diplômé',
                ])->required()->default('actif'),

            FileUpload::make('photo_path')->label('Photo')->image()->disk('public')->directory('students/photos'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo_path')->label('Photo')->circular()->disk('public'),
                TextColumn::make('last_name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('first_name')->label('Prénom')->searchable()->sortable(),
                TextColumn::make('matricule')->label('Matricule')->placeholder('Non attribué'),
                TextColumn::make('establishment.name')->label('Établissement')->sortable(),
                TextColumn::make('classe.name')->label('Classe'),
                TextColumn::make('status')->label('Statut')->badge(),
            ])
            ->filters([
                SelectFilter::make('establishment_id')->label('Établissement')
                    ->options(fn () => Establishment::query()->pluck('name', 'id')),
                SelectFilter::make('status')->label('Statut')
                    ->options([
                        'actif' => 'Actif',
                        'transfere' => 'Transféré',
                        'abandon' => 'Abandon',
                        'diplome' => 'Diplômé',
                    ]),
            ])
            ->defaultSort('last_name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}

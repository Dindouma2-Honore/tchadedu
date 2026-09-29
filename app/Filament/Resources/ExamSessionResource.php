<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ExamSessionResource\Pages;
use App\Models\AcademicYear;
use App\Models\ExamSession;
use App\Models\ExamType;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ExamSessionResource extends Resource
{
    protected static ?string $model = ExamSession::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|UnitEnum|null $navigationGroup = 'Examens';

    protected static ?string $navigationLabel = "Sessions d'examen";

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('exam_type_id')->label("Type d'examen")
                ->options(fn () => ExamType::query()->pluck('name', 'id'))
                ->required(),
            Select::make('academic_year_id')->label('Année scolaire')
                ->options(fn () => AcademicYear::query()->orderBy('start_date', 'desc')->pluck('label', 'id'))
                ->required(),
            TextInput::make('label')->label('Libellé')->required()->maxLength(100),
            DatePicker::make('registration_start')->label('Début des inscriptions')->required(),
            DatePicker::make('registration_end')->label('Fin des inscriptions')->required(),
            DatePicker::make('exam_date_start')->label('Début des épreuves'),
            DatePicker::make('exam_date_end')->label('Fin des épreuves'),
            Select::make('status')->label('Statut')
                ->options([
                    'brouillon' => 'Brouillon',
                    'ouverte' => 'Ouverte',
                    'fermee' => 'Fermée',
                    'archivee' => 'Archivée',
                ])->required()->default('brouillon'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')->label('Session')->searchable()->sortable(),
                TextColumn::make('examType.code')->label('Type')->badge(),
                TextColumn::make('academicYear.label')->label('Année scolaire'),
                TextColumn::make('registration_start')->label('Ouverture')->date('d/m/Y'),
                TextColumn::make('registration_end')->label('Clôture')->date('d/m/Y'),
                TextColumn::make('status')->label('Statut')->badge()
                    ->color(fn (string $state) => match ($state) {
                        'ouverte' => 'success',
                        'brouillon' => 'gray',
                        'fermee' => 'warning',
                        'archivee' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('applications_count')->label('Candidatures')->counts('applications'),
            ])
            ->filters([
                SelectFilter::make('exam_type_id')->label('Type')
                    ->options(fn () => ExamType::query()->pluck('name', 'id')),
                SelectFilter::make('status')->label('Statut')
                    ->options([
                        'brouillon' => 'Brouillon',
                        'ouverte' => 'Ouverte',
                        'fermee' => 'Fermée',
                        'archivee' => 'Archivée',
                    ]),
            ])
            ->defaultSort('registration_start', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExamSessions::route('/'),
            'create' => Pages\CreateExamSession::route('/create'),
            'edit' => Pages\EditExamSession::route('/{record}/edit'),
        ];
    }
}

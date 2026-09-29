<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ExamTypeResource\Pages;
use App\Models\ExamType;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ExamTypeResource extends Resource
{
    protected static ?string $model = ExamType::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static string|UnitEnum|null $navigationGroup = 'Examens';

    protected static ?string $navigationLabel = "Types d'examen";

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->label('Code')->required()->maxLength(20)->unique(ignoreRecord: true),
            TextInput::make('name')->label('Nom')->required()->maxLength(100),
            Select::make('cycle')->label('Cycle')
                ->options(['primaire' => 'Primaire', 'secondaire' => 'Secondaire'])
                ->required()->default('secondaire'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Code')->badge()->sortable(),
                TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('cycle')->label('Cycle'),
                TextColumn::make('exam_sessions_count')->label('Sessions')->counts('examSessions'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExamTypes::route('/'),
            'create' => Pages\CreateExamType::route('/create'),
            'edit' => Pages\EditExamType::route('/{record}/edit'),
        ];
    }
}

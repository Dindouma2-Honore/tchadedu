<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentTypeResource\Pages;
use App\Models\DocumentType;
use App\Models\ExamType;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class DocumentTypeResource extends Resource
{
    protected static ?string $model = DocumentType::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|UnitEnum|null $navigationGroup = 'Examens';

    protected static ?string $navigationLabel = 'Types de documents';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->label('Code')->required()->maxLength(40)->unique(ignoreRecord: true),
            TextInput::make('name')->label('Nom')->required()->maxLength(150),
            Select::make('exam_type_id')->label("Spécifique à un type d'examen (optionnel)")
                ->options(fn () => ExamType::query()->pluck('name', 'id'))
                ->helperText('Laissez vide si le document est commun à tous les examens.'),
            Toggle::make('is_required')->label('Obligatoire')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Code')->searchable()->sortable(),
                TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('examType.name')->label("Type d'examen")->placeholder('Commun'),
                IconColumn::make('is_required')->label('Obligatoire')->boolean(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocumentTypes::route('/'),
            'create' => Pages\CreateDocumentType::route('/create'),
            'edit' => Pages\EditDocumentType::route('/{record}/edit'),
        ];
    }
}

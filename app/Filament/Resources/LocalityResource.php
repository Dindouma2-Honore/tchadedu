<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\LocalityResource\Pages;
use App\Models\Department;
use App\Models\Locality;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class LocalityResource extends Resource
{
    protected static ?string $model = Locality::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|UnitEnum|null $navigationGroup = 'Référentiel territorial';

    protected static ?string $navigationLabel = 'Localités';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('department_id')->label('Département')
                ->options(fn () => Department::query()->orderBy('name')->pluck('name', 'id'))
                ->searchable()->required(),
            TextInput::make('code')->label('Code')->required()->maxLength(20)->unique(ignoreRecord: true),
            TextInput::make('name')->label('Nom')->required()->maxLength(150),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Code')->searchable()->sortable(),
                TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('department.name')->label('Département')->sortable(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLocalities::route('/'),
            'create' => Pages\CreateLocality::route('/create'),
            'edit' => Pages\EditLocality::route('/{record}/edit'),
        ];
    }
}

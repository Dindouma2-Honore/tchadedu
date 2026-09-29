<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\DepartmentResource\Pages;
use App\Models\Department;
use App\Models\Region;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    protected static string|UnitEnum|null $navigationGroup = 'Référentiel territorial';

    protected static ?string $navigationLabel = 'Départements';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('region_id')->label('Région')
                ->options(fn () => Region::query()->orderBy('name')->pluck('name', 'id'))
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
                TextColumn::make('region.name')->label('Région')->sortable(),
                TextColumn::make('establishments_count')->label('Établissements')->counts('establishments'),
            ])
            ->filters([
                SelectFilter::make('region_id')->label('Région')
                    ->options(fn () => Region::query()->pluck('name', 'id')),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepartments::route('/'),
            'create' => Pages\CreateDepartment::route('/create'),
            'edit' => Pages\EditDepartment::route('/{record}/edit'),
        ];
    }
}

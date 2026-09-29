<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\EstablishmentResource\Pages;
use App\Models\Department;
use App\Models\Establishment;
use App\Models\Locality;
use App\Models\Region;
use App\Support\PerimeterScope;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class EstablishmentResource extends Resource
{
    protected static ?string $model = Establishment::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-library';

    protected static string|UnitEnum|null $navigationGroup = 'Référentiel territorial';

    protected static ?string $navigationLabel = 'Établissements';

    public static function getEloquentQuery(): Builder
    {
        return PerimeterScope::forEstablishment(parent::getEloquentQuery());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->label('Code')->required()->maxLength(30)->unique(ignoreRecord: true),
            TextInput::make('name')->label('Nom')->required()->maxLength(200),

            Select::make('region_id')->label('Région')->live()
                ->options(fn () => Region::query()->orderBy('name')->pluck('name', 'id'))
                ->searchable()->required(),

            Select::make('department_id')->label('Département')->live()
                ->options(fn (Get $get) => Department::query()
                    ->when($get('region_id'), fn ($q, $regionId) => $q->where('region_id', $regionId))
                    ->orderBy('name')->pluck('name', 'id'))
                ->searchable()->required(),

            Select::make('locality_id')->label('Localité (optionnel)')
                ->options(fn (Get $get) => Locality::query()
                    ->when($get('department_id'), fn ($q, $deptId) => $q->where('department_id', $deptId))
                    ->orderBy('name')->pluck('name', 'id'))
                ->searchable(),

            Select::make('type')->label('Type')
                ->options(['public' => 'Public', 'prive' => 'Privé'])
                ->required()->default('public'),

            Select::make('status')->label('Statut')
                ->options(['actif' => 'Actif', 'suspendu' => 'Suspendu', 'ferme' => 'Fermé'])
                ->required()->default('actif'),

            TextInput::make('phone')->label('Téléphone')->tel()->maxLength(30),
            TextInput::make('email')->label('E-mail')->email()->maxLength(150),
            Textarea::make('address')->label('Adresse')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Code')->searchable()->sortable(),
                TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('region.name')->label('Région')->sortable(),
                TextColumn::make('department.name')->label('Département')->sortable(),
                TextColumn::make('type')->label('Type')->badge(),
                TextColumn::make('status')->label('Statut')->badge()
                    ->color(fn (string $state) => match ($state) {
                        'actif' => 'success',
                        'suspendu' => 'warning',
                        'ferme' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('region_id')->label('Région')
                    ->options(fn () => Region::query()->pluck('name', 'id')),
                SelectFilter::make('status')->label('Statut')
                    ->options(['actif' => 'Actif', 'suspendu' => 'Suspendu', 'ferme' => 'Fermé']),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEstablishments::route('/'),
            'create' => Pages\CreateEstablishment::route('/create'),
            'edit' => Pages\EditEstablishment::route('/{record}/edit'),
        ];
    }
}

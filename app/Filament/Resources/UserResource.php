<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Department;
use App\Models\Establishment;
use App\Models\Region;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Support\PerimeterScope;
use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|UnitEnum|null $navigationGroup = 'Identité & accès';

    protected static ?string $navigationLabel = 'Utilisateurs';

    public static function getEloquentQuery(): Builder
    {
        return PerimeterScope::forUser(parent::getEloquentQuery());
    }

    /**
     * Un seul des 4 champs de périmètre doit être renseigné — l'Observer
     * UserScopeObserver le fait respecter côté serveur (section 31.1) ;
     * ce formulaire se contente d'un avertissement pour guider la saisie.
     */
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nom complet')->required()->maxLength(150),
            TextInput::make('email')->label('E-mail')->email()->required()->maxLength(150)->unique(ignoreRecord: true),
            TextInput::make('password')->label('Mot de passe')->password()->revealable()
                ->dehydrateStateUsing(fn (string $state) => Hash::make($state))
                ->dehydrated(fn (?string $state) => filled($state))
                ->required(fn (string $operation) => $operation === 'create'),
            TextInput::make('phone')->label('Téléphone')->tel()->maxLength(30),
            Toggle::make('is_active')->label('Compte actif')->default(true),

            Placeholder::make('scope_warning')
                ->hiddenLabel()
                ->content("⚠️ Renseignez UN SEUL des 4 champs de périmètre ci-dessous, selon le niveau hiérarchique du compte (aucun = national)."),

            Select::make('region_id')->label('Région (compte régional)')
                ->options(fn () => Region::query()->pluck('name', 'id'))->searchable(),
            Select::make('department_id')->label('Département (compte départemental)')
                ->options(fn () => Department::query()->pluck('name', 'id'))->searchable(),
            Select::make('establishment_id')->label('Établissement (compte établissement)')
                ->options(fn () => Establishment::query()->pluck('name', 'id'))->searchable(),
            Select::make('student_id')->label('Élève lié (compte candidat)')
                ->options(fn () => Student::query()->orderBy('last_name')
                    ->get()->mapWithKeys(fn (Student $s) => [$s->id => "{$s->last_name} {$s->first_name}"]))
                ->searchable(),

            CheckboxList::make('roles')->label('Rôles')
                ->relationship('roles', 'name')
                ->options(fn () => Role::query()->pluck('name', 'id'))
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('email')->label('E-mail')->searchable(),
                TextColumn::make('roles.name')->label('Rôles')->badge(),
                TextColumn::make('region.name')->label('Région')->placeholder('—'),
                TextColumn::make('department.name')->label('Département')->placeholder('—'),
                TextColumn::make('establishment.name')->label('Établissement')->placeholder('—'),
                IconColumn::make('is_active')->label('Actif')->boolean(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}

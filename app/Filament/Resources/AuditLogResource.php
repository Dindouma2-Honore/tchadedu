<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Journal immuable : ni création, ni modification, ni suppression depuis
 * l'interface (section 15 du document d'architecture — l'audit ne doit
 * jamais pouvoir être altéré, même par un administrateur).
 */
class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|UnitEnum|null $navigationGroup = 'Système';

    protected static ?string $navigationLabel = "Journal d'audit";

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i:s')->sortable(),
                TextColumn::make('user.name')->label('Utilisateur')->placeholder('Système'),
                TextColumn::make('action')->label('Action')->badge()->searchable(),
                TextColumn::make('auditable_type')->label('Type')->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '—'),
                TextColumn::make('auditable_id')->label('ID'),
                TextColumn::make('ip_address')->label('IP'),
                TextColumn::make('result')->label('Résultat')->badge()
                    ->color(fn (string $state) => $state === 'success' ? 'success' : 'danger'),
            ])
            ->filters([
                SelectFilter::make('result')->label('Résultat')
                    ->options(['success' => 'Succès', 'failure' => 'Échec']),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
        ];
    }
}

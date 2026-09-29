<?php

declare(strict_types=1);

namespace App\Filament\Resources\PaymentResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Journal en lecture seule des événements d'un paiement — écrit exclusivement par PaymentService. */
class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $title = "Journal d'audit du paiement";

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('event')
            ->columns([
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('event')->label('Événement')->badge(),
                TextColumn::make('status_before')->label('Avant')->default('—'),
                TextColumn::make('status_after')->label('Après')->default('—'),
                TextColumn::make('actor.name')->label('Par')->default('Système / prestataire'),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        return true;
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Resources\ApplicationResource\RelationManagers;

use App\Models\Application;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Journal en lecture seule des transitions de statut d'une candidature
 * (section 15 : traçabilité). Aucune action de création/édition/suppression
 * n'est exposée ici : ces lignes ne sont écrites que par
 * ApplicationWorkflowService, jamais manuellement.
 */
class StatusHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'statusHistories';

    protected static ?string $title = 'Historique du dossier';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('to_status')
            ->columns([
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('from_status')->label('De')
                    ->formatStateUsing(fn (?string $state) => $state ? (Application::LABELS[$state] ?? $state) : '—'),
                TextColumn::make('to_status')->label('Vers')
                    ->formatStateUsing(fn (string $state) => Application::LABELS[$state] ?? $state)
                    ->badge(),
                TextColumn::make('changedBy.name')->label('Par')->default('—'),
                TextColumn::make('comment')->label('Commentaire')->wrap()->default('—'),
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

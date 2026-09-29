<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Application;
use App\Services\ApplicationStatisticsService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Suivi des motifs de blocage (section 16.2 : "dossiers rejetés, motifs de
 * rejet") — utile aux niveaux qui contrôlent des dossiers, pas à
 * l'établissement (qui voit déjà ses propres motifs directement sur ses
 * candidatures) ni au candidat.
 */
class RecentRejectionsTable extends TableWidget
{
    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user !== null
            && in_array($user->scopeLevel(), ['national', 'regional', 'departmental'], true)
            && ($user->hasPermissionTo('applications.validate') || $user->hasPermissionTo('applications.reject'));
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Derniers rejets et corrections demandées')
            ->query(fn () => app(ApplicationStatisticsService::class)->recentRejectionsAndCorrectionsQuery(8))
            ->columns([
                TextColumn::make('reference')->label('Référence')->copyable(),
                TextColumn::make('establishment.name')->label('Établissement'),
                TextColumn::make('status')->label('Statut')->badge()
                    ->formatStateUsing(fn (Application $record) => $record->statusLabel())
                    ->color(fn (string $state) => $state === Application::STATUS_REJETEE ? 'danger' : 'warning'),
                TextColumn::make('rejection_reason')->label('Motif')->wrap()->limit(80),
                TextColumn::make('updated_at')->label('Le')->dateTime('d/m/Y H:i'),
            ])
            ->defaultSort('updated_at', 'desc')
            ->paginated(false);
    }
}

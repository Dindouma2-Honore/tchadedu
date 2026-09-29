<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Application;
use App\Services\ApplicationStatisticsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Vue d'ensemble des candidatures dans le périmètre de l'utilisateur
 * (section 16 : "dossiers brouillons/soumis/à corriger/validés" côté
 * établissement, déclinée automatiquement au niveau régional/national
 * puisque ApplicationStatisticsService applique déjà PerimeterScope).
 */
class ApplicationStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Application::class) ?? false;
    }

    protected function getStats(): array
    {
        $counts = app(ApplicationStatisticsService::class)->statusCounts();

        $stats = [
            Stat::make('Total candidatures', (string) $counts['total'])
                ->icon('heroicon-o-document-text')
                ->color('gray'),
        ];

        if (auth()->user()?->scopeLevel() === 'establishment') {
            $stats[] = Stat::make('Brouillons', (string) $counts['brouillon'])
                ->icon('heroicon-o-pencil')
                ->color('gray');
        }

        $stats[] = Stat::make('En cours de contrôle', (string) $counts['en_cours'])
            ->icon('heroicon-o-clock')
            ->color('info');

        $stats[] = Stat::make('À corriger', (string) $counts['a_corriger'])
            ->icon('heroicon-o-exclamation-triangle')
            ->color('warning');

        $stats[] = Stat::make('Inscrits', (string) $counts['inscrit'])
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->description($counts['total'] > 0
                ? round($counts['inscrit'] / $counts['total'] * 100, 1).'% du total'
                : null);

        $stats[] = Stat::make('Rejetés', (string) $counts['rejetee'])
            ->icon('heroicon-o-x-circle')
            ->color('danger');

        return $stats;
    }
}

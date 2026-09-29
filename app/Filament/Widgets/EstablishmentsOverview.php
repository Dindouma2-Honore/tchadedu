<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\ApplicationStatisticsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * N'a de sens qu'au-dessus du niveau établissement (section 16.2/16.3 :
 * "nombre d'établissements", "répartition public/privé") — un directeur
 * d'établissement ou un candidat n'ont qu'un seul établissement en vue,
 * ce chiffre ne leur apporterait rien.
 */
class EstablishmentsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        $level = auth()->user()?->scopeLevel();

        return in_array($level, ['national', 'regional', 'departmental'], true);
    }

    protected function getStats(): array
    {
        $summary = app(ApplicationStatisticsService::class)->establishmentsSummary();
        $averageDays = app(ApplicationStatisticsService::class)->averageProcessingDays();

        return [
            Stat::make('Établissements', (string) $summary['total'])
                ->icon('heroicon-o-building-office-2')
                ->color('gray')
                ->description("{$summary['public']} publics · {$summary['prive']} privés"),

            Stat::make('Délai moyen de traitement', $averageDays !== null ? "{$averageDays} j" : '—')
                ->icon('heroicon-o-clock')
                ->color('info')
                ->description('Du dépôt à la décision finale'),
        ];
    }
}

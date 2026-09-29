<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\ApplicationStatisticsService;
use Filament\Widgets\ChartWidget;

/**
 * Répartition filles/garçons parmi les candidats (section 16.3). Réservé
 * au niveau national : le document conditionne explicitement cette
 * statistique à sa pertinence "légalement et statistiquement" — on
 * l'affiche donc seulement agrégée à l'échelle du pays, jamais au niveau
 * d'un établissement où l'effectif est trop réduit pour rester anonyme.
 */
class GenderDistributionChart extends ChartWidget
{
    protected static ?int $sort = 5;

    protected ?string $heading = 'Répartition filles / garçons';

    public static function canView(): bool
    {
        return auth()->user()?->scopeLevel() === 'national';
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $distribution = app(ApplicationStatisticsService::class)->genderDistribution();

        return [
            'datasets' => [
                [
                    'data' => [$distribution['F'], $distribution['M']],
                    'backgroundColor' => ['#ec4899', '#3b82f6'],
                ],
            ],
            'labels' => ['Filles', 'Garçons'],
        ];
    }
}

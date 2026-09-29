<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Application;
use App\Services\ApplicationStatisticsService;
use Filament\Widgets\ChartWidget;

/** Répartition des candidatures par type d'examen (BEPC, BAC...), dans le périmètre de l'utilisateur. */
class ApplicationsByExamTypeChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Candidatures par examen';

    protected string $color = 'info';

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Application::class) ?? false;
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $counts = app(ApplicationStatisticsService::class)->countByExamType();

        return [
            'datasets' => [
                [
                    'label' => 'Candidatures',
                    'data' => $counts->values()->all(),
                    'backgroundColor' => '#f59e0b',
                ],
            ],
            'labels' => $counts->keys()->all(),
        ];
    }
}

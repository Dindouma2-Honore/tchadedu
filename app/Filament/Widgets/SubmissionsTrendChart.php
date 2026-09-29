<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Application;
use App\Services\ApplicationStatisticsService;
use Filament\Widgets\ChartWidget;

/** Évolution mensuelle des dépôts (section 16.2 : "évolution par année"). */
class SubmissionsTrendChart extends ChartWidget
{
    protected static ?int $sort = 4;

    protected ?string $heading = 'Évolution des dépôts (6 derniers mois)';

    protected string $color = 'success';

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Application::class) ?? false;
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $trend = app(ApplicationStatisticsService::class)->submissionsTrend(6);

        return [
            'datasets' => [
                [
                    'label' => 'Dossiers soumis',
                    'data' => $trend->values()->all(),
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.15)',
                    'fill' => true,
                ],
            ],
            'labels' => $trend->keys()->map(fn (string $ym) => \Illuminate\Support\Carbon::createFromFormat('Y-m', $ym)->translatedFormat('M Y'))->all(),
        ];
    }
}

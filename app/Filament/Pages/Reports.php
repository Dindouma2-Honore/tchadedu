<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Application;
use App\Models\ExamSession;
use App\Services\ApplicationStatisticsService;
use App\Services\PdfRenderer;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Rapports & Statistiques (module dédié du document d'architecture,
 * section 16 + diagramme "Rapports & Statistiques" / "Export Excel/PDF").
 *
 * Contrairement aux widgets du tableau de bord (aperçu permanent), cette
 * page permet de filtrer par session d'examen et d'exporter le détail en
 * CSV. La dimension de regroupement du tableau (région, département,
 * établissement...) s'adapte automatiquement au périmètre de la personne
 * connectée via ApplicationStatisticsService::breakdown().
 */
class Reports extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'Examens';

    protected static ?int $navigationSort = 90;

    protected static ?string $navigationLabel = 'Rapports & Statistiques';

    protected static ?string $title = 'Rapports & Statistiques';

    protected string $view = 'filament.pages.reports';

    #[Url]
    public ?int $examSessionId = null;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user !== null
            && $user->scopeLevel() !== 'self'
            && ($user->hasPermissionTo('applications.view') || $user->hasPermissionTo('applications.validate'));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPdf')
                ->label('Exporter en PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(fn () => $this->exportPdf()),

            Action::make('export')
                ->label('Exporter en CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => $this->exportCsv()),
        ];
    }

    /** Options du filtre "session d'examen", limitées à celles ayant au moins une candidature dans le périmètre de l'utilisateur. */
    public function getExamSessionOptions(): array
    {
        return ExamSession::query()
            ->whereHas('applications')
            ->orderByDesc('id')
            ->pluck('label', 'id')
            ->all();
    }

    public function getStatusCounts(): array
    {
        return app(ApplicationStatisticsService::class)->statusCounts($this->examSessionId);
    }

    public function getAverageProcessingDays(): ?float
    {
        return app(ApplicationStatisticsService::class)->averageProcessingDays($this->examSessionId);
    }

    /** @return array<int, array{label: string, total: int, inscrits: int, a_corriger: int, rejetees: int, taux_validation: float, delai_moyen_jours: ?float}> */
    public function getBreakdownRows(): array
    {
        return app(ApplicationStatisticsService::class)->breakdown($this->examSessionId)->all();
    }

    /** Libellé de la colonne de regroupement, cohérent avec ApplicationStatisticsService::breakdown(). */
    public function getBreakdownLabel(): string
    {
        return match (Auth::user()?->scopeLevel()) {
            'national' => 'Région',
            'regional' => 'Département',
            'departmental' => 'Établissement',
            default => 'Session d\'examen',
        };
    }

    private function exportPdf(): StreamedResponse
    {
        $sessionLabel = $this->examSessionId
            ? (ExamSession::find($this->examSessionId)?->label ?? 'Session inconnue')
            : 'Toutes les sessions';

        $html = view('pdf.report', [
            'sessionLabel' => $sessionLabel,
            'generatedBy' => Auth::user()?->name ?? 'Système',
            'counts' => $this->getStatusCounts(),
            'averageDays' => $this->getAverageProcessingDays(),
            'breakdownLabel' => $this->getBreakdownLabel(),
            'rows' => $this->getBreakdownRows(),
        ])->render();

        $pdf = app(PdfRenderer::class)->render($html, 'a4', 'landscape');

        return response()->streamDownload(
            fn () => print ($pdf),
            'rapport-tchadedu-'.now()->format('Ymd-His').'.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    private function exportCsv(): StreamedResponse
    {
        $rows = $this->getBreakdownRows();
        $groupLabel = $this->getBreakdownLabel();

        $filename = 'rapport-tchadedu-'.now()->format('Ymd-His').'.csv';

        $callback = function () use ($rows, $groupLabel) {
            $handle = fopen('php://output', 'w');
            // BOM UTF-8 pour un ouverture correcte des accents dans Excel.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [$groupLabel, 'Total', 'Inscrits', 'À corriger', 'Rejetés', 'Taux de validation (%)', 'Délai moyen (j)']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['label'],
                    $row['total'],
                    $row['inscrits'],
                    $row['a_corriger'],
                    $row['rejetees'],
                    $row['taux_validation'],
                    $row['delai_moyen_jours'] ?? '',
                ]);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}

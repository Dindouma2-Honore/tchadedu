<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Application;
use App\Models\Establishment;
use App\Support\PerimeterScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Source unique de vérité pour tous les indicateurs des tableaux de bord
 * (section 16 du document d'architecture) et de la page Rapports. Chaque
 * méthode applique PerimeterScope avant tout calcul : un compte
 * départemental ne voit jamais de chiffres agrégeant des données hors de
 * son département, même dans un total ou une moyenne (section 31.8 :
 * "les recherches, filtres et statistiques doivent respecter le périmètre
 * de l'utilisateur").
 *
 * Les widgets Filament et la page Rapports appellent ce service plutôt que
 * d'interroger applications/establishments directement, pour ne jamais
 * dupliquer la logique de périmètre ni les règles de regroupement par
 * statut.
 */
class ApplicationStatisticsService
{
    /** Dossiers encore actifs dans le circuit de validation, à un niveau de contrôle quelconque. */
    public const EN_COURS_STATUSES = [
        Application::STATUS_SOUMISE,
        Application::STATUS_VALIDEE_DEPARTEMENT,
        Application::STATUS_VALIDEE_REGION,
    ];

    /** Dossiers renvoyés à l'établissement, à un niveau de correction quelconque. */
    public const A_CORRIGER_STATUSES = [
        Application::STATUS_A_CORRIGER_DEPARTEMENT,
        Application::STATUS_A_CORRIGER_REGION,
        Application::STATUS_A_CORRIGER_NATIONAL,
    ];

    /** Requête de base : candidatures dans le périmètre de l'utilisateur connecté, avec les relations utiles préchargées. */
    public function scopedApplications(?int $examSessionId = null): Builder
    {
        $query = PerimeterScope::forApplication(Application::query())
            ->with([
                'student:id,sex',
                'examSession:id,exam_type_id,label',
                'examSession.examType:id,name',
                'establishment:id,name,type,department_id,region_id',
                'establishment.department:id,name,region_id',
                'establishment.region:id,name',
            ]);

        if ($examSessionId !== null) {
            $query->where('exam_session_id', $examSessionId);
        }

        return $query;
    }

    /**
     * Compteurs par grande famille de statut (section 16.1 : brouillons,
     * soumis, à corriger, validés...), regroupés pour rester lisibles quel
     * que soit le niveau de granularité des 10 statuts internes.
     *
     * @return array{brouillon: int, en_cours: int, a_corriger: int, inscrit: int, rejetee: int, annulee: int, total: int}
     */
    public function statusCounts(?int $examSessionId = null): array
    {
        $counts = $this->scopedApplications($examSessionId)->get(['status'])->countBy('status');

        $sumOf = fn (array $statuses) => collect($statuses)->sum(fn (string $s) => $counts->get($s, 0));

        return [
            'brouillon' => $counts->get(Application::STATUS_BROUILLON, 0),
            'en_cours' => $sumOf(self::EN_COURS_STATUSES),
            'a_corriger' => $sumOf(self::A_CORRIGER_STATUSES),
            'inscrit' => $counts->get(Application::STATUS_INSCRIT, 0),
            'rejetee' => $counts->get(Application::STATUS_REJETEE, 0),
            'annulee' => $counts->get(Application::STATUS_ANNULEE, 0),
            'total' => $counts->sum(),
        ];
    }

    /** Répartition des candidatures par type d'examen (BEPC, BAC...), pour le graphique correspondant. */
    public function countByExamType(?int $examSessionId = null): Collection
    {
        return $this->scopedApplications($examSessionId)->get()
            ->groupBy(fn (Application $a) => $a->examSession?->examType?->name ?? 'Non renseigné')
            ->map->count()
            ->sortDesc();
    }

    /** Nombre de dépôts par mois sur les $months derniers mois (section 16.2 : "évolution par année"). */
    public function submissionsTrend(int $months = 6): Collection
    {
        $from = now()->subMonths($months - 1)->startOfMonth();

        $counts = $this->scopedApplications()
            ->whereNotNull('submitted_at')
            ->where('submitted_at', '>=', $from)
            ->get(['submitted_at'])
            ->groupBy(fn (Application $a) => $a->submitted_at->format('Y-m'))
            ->map->count();

        // On force la présence de tous les mois de la période, même à 0,
        // pour un graphique continu plutôt que des trous silencieux.
        $labels = collect(range(0, $months - 1))
            ->map(fn (int $i) => now()->subMonths($months - 1 - $i)->format('Y-m'));

        return $labels->mapWithKeys(fn (string $month) => [$month => $counts->get($month, 0)]);
    }

    /**
     * Délai moyen, en jours, entre le dépôt et la décision finale
     * (inscription ou rejet) — indicateur de réussite section 27
     * ("temps moyen de traitement d'un dossier").
     */
    public function averageProcessingDays(?int $examSessionId = null): ?float
    {
        $termines = $this->scopedApplications($examSessionId)
            ->whereIn('status', [Application::STATUS_INSCRIT, Application::STATUS_REJETEE])
            ->whereNotNull('submitted_at')
            ->whereNotNull('validated_at')
            ->get(['submitted_at', 'validated_at']);

        if ($termines->isEmpty()) {
            return null;
        }

        return round($termines->avg(fn (Application $a) => $a->submitted_at->diffInDays($a->validated_at)), 1);
    }

    /** Requête (non exécutée) des derniers dossiers renvoyés en correction ou rejetés — pour un widget Filament (qui a besoin d'un Builder, pas d'une Collection, y compris quand le résultat est vide). */
    public function recentRejectionsAndCorrectionsQuery(int $limit = 8): Builder
    {
        return $this->scopedApplications()
            ->whereIn('status', [Application::STATUS_REJETEE, ...self::A_CORRIGER_STATUSES])
            ->whereNotNull('rejection_reason')
            ->latest('updated_at')
            ->limit($limit);
    }

    /** Derniers dossiers renvoyés en correction ou rejetés, avec leur motif — pour le suivi des causes de blocage (section 16.2 : "motifs de rejet"). */
    public function recentRejectionsAndCorrections(int $limit = 8): Collection
    {
        return $this->recentRejectionsAndCorrectionsQuery($limit)->get();
    }

    /**
     * Nombre d'établissements dans le périmètre, avec répartition
     * public/privé (section 16.3 : "répartition public/privé").
     *
     * @return array{total: int, public: int, prive: int}
     */
    public function establishmentsSummary(): array
    {
        $base = PerimeterScope::forEstablishment(Establishment::query());

        return [
            'total' => (clone $base)->count(),
            'public' => (clone $base)->where('type', 'public')->count(),
            'prive' => (clone $base)->where('type', 'prive')->count(),
        ];
    }

    /**
     * Répartition filles/garçons parmi les candidats (section 16.3,
     * explicitement conditionnée par le document à sa pertinence légale et
     * statistique — affichée uniquement au niveau national dans les
     * widgets, jamais individualisée en dessous d'un effectif agrégé).
     *
     * @return array{M: int, F: int}
     */
    public function genderDistribution(?int $examSessionId = null): array
    {
        $students = $this->scopedApplications($examSessionId)->get()->pluck('student')->filter();

        return [
            'M' => $students->where('sex', 'M')->count(),
            'F' => $students->where('sex', 'F')->count(),
        ];
    }

    /**
     * Ligne agrégée par entité administrative, la dimension de
     * regroupement s'adaptant au niveau de l'utilisateur connecté :
     * national -> par région, régional -> par département,
     * départemental -> par établissement, établissement -> par session
     * d'examen (section 16 : tableaux "statistiques par établissement et
     * région"). Alimente à la fois les widgets tableau et l'export CSV.
     *
     * @return Collection<int, array{label: string, total: int, inscrits: int, a_corriger: int, rejetees: int, taux_validation: float, delai_moyen_jours: ?float}>
     */
    public function breakdown(?int $examSessionId = null): Collection
    {
        $level = Auth::user()?->scopeLevel();

        $groupResolver = match ($level) {
            'national' => fn (Application $a) => $a->establishment?->region?->name ?? 'Non renseigné',
            'regional' => fn (Application $a) => $a->establishment?->department?->name ?? 'Non renseigné',
            'departmental' => fn (Application $a) => $a->establishment?->name ?? 'Non renseigné',
            default => fn (Application $a) => $a->examSession?->label ?? 'Non renseigné',
        };

        return $this->scopedApplications($examSessionId)->get()
            ->groupBy($groupResolver)
            ->map(function (Collection $group, string $label) {
                $total = $group->count();
                $inscrits = $group->where('status', Application::STATUS_INSCRIT)->count();
                $rejetees = $group->where('status', Application::STATUS_REJETEE)->count();
                $aCorriger = $group->whereIn('status', self::A_CORRIGER_STATUSES)->count();

                $termines = $group->whereIn('status', [Application::STATUS_INSCRIT, Application::STATUS_REJETEE])
                    ->filter(fn (Application $a) => $a->submitted_at && $a->validated_at);

                return [
                    'label' => $label,
                    'total' => $total,
                    'inscrits' => $inscrits,
                    'a_corriger' => $aCorriger,
                    'rejetees' => $rejetees,
                    'taux_validation' => $total > 0 ? round($inscrits / $total * 100, 1) : 0.0,
                    'delai_moyen_jours' => $termines->isEmpty()
                        ? null
                        : round($termines->avg(fn (Application $a) => $a->submitted_at->diffInDays($a->validated_at)), 1),
                ];
            })
            ->sortByDesc('total')
            ->values();
    }
}

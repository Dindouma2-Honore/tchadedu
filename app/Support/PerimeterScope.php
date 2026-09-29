<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Applique le filtrage par périmètre (section 31 du document
 * d'architecture) directement sur les requêtes Eloquent des Resources
 * Filament, en plus des Policies (qui, elles, bloquent les actions et
 * l'accès direct par URL à un enregistrement précis).
 *
 * Sans ce filtrage, un compte "établissement" verrait quand même TOUTES
 * les lignes dans les listes — les Policies seules ne cachent rien dans
 * un tableau, elles bloquent seulement les actions.
 */
class PerimeterScope
{
    /**
     * Pour les modèles qui référencent un établissement via une colonne
     * establishment_id ET une relation establishment() (Classe, Student,
     * Application). Remonte jusqu'à la région/département de
     * l'établissement pour les comptes régionaux/départementaux.
     */
    public static function forEstablishmentOwnedModel(Builder $query): Builder
    {
        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        return match ($user->scopeLevel()) {
            'national' => $query,
            'regional' => $query->whereHas(
                'establishment',
                fn (Builder $q) => $q->where('region_id', $user->region_id)
            ),
            'departmental' => $query->whereHas(
                'establishment',
                fn (Builder $q) => $q->where('department_id', $user->department_id)
            ),
            'establishment' => $query->where('establishment_id', $user->establishment_id),
            default => $query->whereRaw('1 = 0'), // 'self' et cas non prévus : aucun accès à ces listes
        };
    }

    /**
     * Spécifique à Establishment : la portée s'applique directement sur
     * ses propres colonnes (region_id, department_id, id), pas via une
     * relation vers lui-même.
     */
    public static function forEstablishment(Builder $query): Builder
    {
        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        return match ($user->scopeLevel()) {
            'national' => $query,
            'regional' => $query->where('region_id', $user->region_id),
            'departmental' => $query->where('department_id', $user->department_id),
            'establishment' => $query->where('id', $user->establishment_id),
            default => $query->whereRaw('1 = 0'),
        };
    }

    /** Student : un candidat ('self') ne voit que sa propre fiche. */
    public static function forStudent(Builder $query): Builder
    {
        $user = Auth::user();

        if ($user && $user->scopeLevel() === 'self') {
            return $query->where('id', $user->student_id);
        }

        return self::forEstablishmentOwnedModel($query);
    }

    /** Application : un candidat ne voit que ses propres candidatures. */
    public static function forApplication(Builder $query): Builder
    {
        $user = Auth::user();

        if ($user && $user->scopeLevel() === 'self') {
            return $query->where('student_id', $user->student_id);
        }

        return self::forEstablishmentOwnedModel($query);
    }

    /** Payment : passe par la relation application -> establishment, à un niveau de plus que forApplication(). */
    public static function forPayment(Builder $query): Builder
    {
        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->scopeLevel() === 'self') {
            return $query->whereHas('application', fn (Builder $q) => $q->where('student_id', $user->student_id));
        }

        return match ($user->scopeLevel()) {
            'national' => $query,
            'regional' => $query->whereHas('application.establishment', fn (Builder $q) => $q->where('region_id', $user->region_id)),
            'departmental' => $query->whereHas('application.establishment', fn (Builder $q) => $q->where('department_id', $user->department_id)),
            'establishment' => $query->whereHas('application', fn (Builder $q) => $q->where('establishment_id', $user->establishment_id)),
            default => $query->whereRaw('1 = 0'),
        };
    }

    /**
     * User (gestion des comptes) : chacun ne voit et ne gère que les
     * comptes de son propre périmètre ou plus restreint — un compte
     * régional ne doit pas pouvoir gérer un autre compte régional d'une
     * autre région, ni un compte national.
     */
    public static function forUser(Builder $query): Builder
    {
        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        return match ($user->scopeLevel()) {
            'national' => $query,
            'regional' => $query->where('region_id', $user->region_id),
            'departmental' => $query->where('department_id', $user->department_id),
            'establishment' => $query->where('establishment_id', $user->establishment_id),
            default => $query->where('id', $user->id),
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Fait respecter la règle de la section 31.1 du document d'architecture :
 * un compte n'est rattaché qu'à UN SEUL niveau de périmètre à la fois
 * (region_id XOR department_id XOR establishment_id XOR student_id, ou
 * aucun des quatre pour un compte national).
 *
 * Volontairement implémenté en Observer plutôt qu'en contrainte SQL CHECK :
 * plus lisible, plus simple à faire évoluer, et le message d'erreur reste
 * exploitable côté formulaire Filament.
 */
class UserScopeObserver
{
    public function saving(User $user): void
    {
        $scopesRenseignes = collect([
            'region_id' => $user->region_id,
            'department_id' => $user->department_id,
            'establishment_id' => $user->establishment_id,
            'student_id' => $user->student_id,
        ])->filter(fn ($value) => $value !== null);

        if ($scopesRenseignes->count() > 1) {
            throw ValidationException::withMessages([
                'scope' => sprintf(
                    "Un compte ne peut être rattaché qu'à un seul périmètre à la fois (région, département, établissement OU élève). Colonnes renseignées simultanément : %s.",
                    $scopesRenseignes->keys()->implode(', ')
                ),
            ]);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Policies\Concerns;

use App\Models\User;

/**
 * Pour les tables de référence transverses (Region, ExamType, Role...) :
 * lecture ouverte à tout compte authentifié (nécessaire pour les listes
 * déroulantes des autres formulaires), écriture réservée au national.
 */
trait RestrictsWritesToNational
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, $model): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->scopeLevel() === 'national';
    }

    public function update(User $user, $model): bool
    {
        return $user->scopeLevel() === 'national';
    }

    public function delete(User $user, $model): bool
    {
        return $user->scopeLevel() === 'national';
    }
}

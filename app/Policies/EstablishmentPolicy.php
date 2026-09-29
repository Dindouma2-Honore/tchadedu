<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Establishment;
use App\Models\User;

class EstablishmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('establishments.view') || $user->hasPermissionTo('establishments.manage');
    }

    public function view(User $user, Establishment $establishment): bool
    {
        return $this->inScope($user, $establishment) && $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('establishments.manage');
    }

    public function update(User $user, Establishment $establishment): bool
    {
        return $this->inScope($user, $establishment) && $user->hasPermissionTo('establishments.manage');
    }

    public function delete(User $user, Establishment $establishment): bool
    {
        return $user->scopeLevel() === 'national' && $user->hasPermissionTo('establishments.manage');
    }

    private function inScope(User $user, Establishment $establishment): bool
    {
        return match ($user->scopeLevel()) {
            'national' => true,
            'regional' => $establishment->region_id === $user->region_id,
            'departmental' => $establishment->department_id === $user->department_id,
            'establishment' => $establishment->id === $user->establishment_id,
            default => false,
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Classe;
use App\Models\User;

class ClassePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->scopeLevel() !== 'self';
    }

    public function view(User $user, Classe $classe): bool
    {
        return $this->inScope($user, $classe);
    }

    public function create(User $user): bool
    {
        return in_array($user->scopeLevel(), ['national', 'establishment']) && $user->hasPermissionTo('students.manage');
    }

    public function update(User $user, Classe $classe): bool
    {
        return $this->inScope($user, $classe) && $user->hasPermissionTo('students.manage');
    }

    public function delete(User $user, Classe $classe): bool
    {
        return $user->scopeLevel() === 'national';
    }

    private function inScope(User $user, Classe $classe): bool
    {
        $etablissement = $classe->establishment;

        return match ($user->scopeLevel()) {
            'national' => true,
            'regional' => $etablissement?->region_id === $user->region_id,
            'departmental' => $etablissement?->department_id === $user->department_id,
            'establishment' => $classe->establishment_id === $user->establishment_id,
            default => false,
        };
    }
}

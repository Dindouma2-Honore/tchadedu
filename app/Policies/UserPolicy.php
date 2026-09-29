<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('users.manage');
    }

    public function view(User $user, User $model): bool
    {
        return $this->inScope($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('users.manage');
    }

    public function update(User $user, User $model): bool
    {
        return $this->inScope($user, $model) && $user->hasPermissionTo('users.manage');
    }

    /** Personne, même national, ne peut se supprimer soi-même via cette interface. */
    public function delete(User $user, User $model): bool
    {
        return $user->scopeLevel() === 'national'
            && $user->hasPermissionTo('users.manage')
            && $user->id !== $model->id;
    }

    private function inScope(User $user, User $model): bool
    {
        return match ($user->scopeLevel()) {
            'national' => true,
            'regional' => $model->region_id === $user->region_id,
            'departmental' => $model->department_id === $user->department_id,
            'establishment' => $model->establishment_id === $user->establishment_id,
            default => $model->id === $user->id,
        };
    }
}

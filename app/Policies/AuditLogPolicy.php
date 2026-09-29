<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('audit.view');
    }

    public function view(User $user, $model): bool
    {
        return $user->hasPermissionTo('audit.view');
    }

    // create/update/delete : volontairement absents. Sans méthode définie,
    // Laravel refuse par défaut (deny by default) — cohérent avec le
    // caractère immuable du journal (section 15 du document).
}

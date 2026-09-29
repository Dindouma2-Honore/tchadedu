<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Mapping volontairement simple pour le MVP — à affiner une fois les
     * Policies Filament écrites (section 31.9 du document d'architecture).
     */
    public function run(): void
    {
        $map = [
            'admin-national' => ['*'],
            'direction-examens' => ['establishments.view', 'students.view', 'applications.view', 'applications.validate', 'applications.reject', 'applications.cancel', 'documents.view', 'documents.verify', 'audit.view', 'payments.view'],
            'agent-financier' => ['applications.view', 'audit.view', 'payments.view', 'payments.manual_confirm', 'payments.refund'],
            'auditeur' => ['audit.view', 'applications.view', 'students.view', 'establishments.view', 'payments.view'],
            'direction-regionale' => ['establishments.view', 'students.view', 'applications.view', 'applications.validate', 'applications.reject', 'documents.view', 'payments.view'],
            'direction-departementale' => ['establishments.view', 'students.view', 'applications.view', 'applications.validate', 'applications.reject', 'documents.view', 'payments.view'],
            'directeur-etablissement' => ['students.manage', 'applications.view', 'applications.create', 'applications.submit', 'applications.cancel', 'documents.upload', 'documents.view', 'payments.view', 'payments.initiate'],
            'agent-etablissement' => ['students.manage', 'applications.create', 'documents.upload', 'documents.view', 'payments.view', 'payments.initiate'],
            'candidat' => ['applications.view', 'documents.view', 'payments.view'],
        ];

        $allPermissionSlugs = Permission::query()->pluck('id', 'slug');

        foreach ($map as $roleSlug => $permissionSlugs) {
            $role = Role::query()->where('slug', $roleSlug)->first();

            if (! $role) {
                continue;
            }

            if ($permissionSlugs === ['*']) {
                $role->permissions()->sync($allPermissionSlugs->values());

                continue;
            }

            $ids = collect($permissionSlugs)
                ->map(fn (string $slug) => $allPermissionSlugs[$slug] ?? null)
                ->filter()
                ->values();

            $role->permissions()->sync($ids);
        }
    }
}

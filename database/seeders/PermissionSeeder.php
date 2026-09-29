<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['slug' => 'establishments.view', 'name' => 'Consulter les établissements'],
            ['slug' => 'establishments.manage', 'name' => 'Gérer les établissements'],
            ['slug' => 'students.view', 'name' => 'Consulter les élèves'],
            ['slug' => 'students.manage', 'name' => 'Gérer les élèves'],
            ['slug' => 'applications.view', 'name' => 'Consulter les candidatures'],
            ['slug' => 'applications.create', 'name' => 'Créer une candidature'],
            ['slug' => 'applications.submit', 'name' => 'Soumettre une candidature'],
            ['slug' => 'applications.validate', 'name' => 'Valider une candidature'],
            ['slug' => 'applications.reject', 'name' => 'Rejeter une candidature'],
            ['slug' => 'applications.cancel', 'name' => 'Annuler une candidature'],
            ['slug' => 'payments.view', 'name' => 'Consulter les paiements'],
            ['slug' => 'payments.initiate', 'name' => 'Initier un paiement'],
            ['slug' => 'payments.manual_confirm', 'name' => 'Confirmer manuellement un paiement'],
            ['slug' => 'payments.refund', 'name' => 'Rembourser un paiement'],
            ['slug' => 'documents.view', 'name' => 'Consulter les documents'],
            ['slug' => 'documents.upload', 'name' => 'Déposer un document'],
            ['slug' => 'documents.verify', 'name' => 'Vérifier un document'],
            ['slug' => 'users.manage', 'name' => 'Gérer les comptes utilisateurs'],
            ['slug' => 'settings.manage', 'name' => 'Gérer les paramètres système'],
            ['slug' => 'audit.view', 'name' => "Consulter le journal d'audit"],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate(['slug' => $permission['slug']], $permission);
        }
    }
}

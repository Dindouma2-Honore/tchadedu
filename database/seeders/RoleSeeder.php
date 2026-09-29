<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['slug' => 'admin-national', 'name' => 'Administrateur national', 'scope_level' => 'national', 'description' => "Administration technique globale : utilisateurs, paramètres, sécurité."],
            ['slug' => 'direction-examens', 'name' => 'Direction des examens', 'scope_level' => 'national', 'description' => 'Gestion des sessions, types d\'examen, contrôles et validations finales.'],
            ['slug' => 'agent-financier', 'name' => 'Agent financier', 'scope_level' => 'national', 'description' => 'Contrôle des transactions et rapprochement des paiements.'],
            ['slug' => 'auditeur', 'name' => 'Auditeur', 'scope_level' => 'national', 'description' => 'Lecture seule des journaux d\'audit sur le périmètre attribué.'],
            ['slug' => 'direction-regionale', 'name' => 'Direction régionale', 'scope_level' => 'regional', 'description' => 'Contrôle et validation des dossiers de sa région.'],
            ['slug' => 'direction-departementale', 'name' => 'Direction départementale', 'scope_level' => 'departmental', 'description' => 'Contrôle et validation des dossiers de son département.'],
            ['slug' => 'directeur-etablissement', 'name' => "Directeur d'établissement", 'scope_level' => 'establishment', 'description' => 'Gestion des candidats et soumission des dossiers de son établissement.'],
            ['slug' => 'agent-etablissement', 'name' => "Agent d'établissement", 'scope_level' => 'establishment', 'description' => 'Saisie et préparation des élèves, documents, candidatures.'],
            ['slug' => 'candidat', 'name' => 'Candidat / Élève', 'scope_level' => 'self', 'description' => 'Consultation de son propre dossier et suivi.'],
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrCreate(['slug' => $role['slug']], $role);
        }
    }
}

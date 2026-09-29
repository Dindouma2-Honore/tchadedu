<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Establishment;
use App\Models\Region;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Un compte par niveau hiérarchique (section 31.1), pour pouvoir
     * tester l'isolation par périmètre immédiatement après le seed.
     * Mot de passe identique pour tous en environnement de démo : "password".
     */
    public function run(): void
    {
        $motDePasse = Hash::make('admin');

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@tchadedu.com'],
            ['name' => 'Admin National', 'password' => $motDePasse, 'is_active' => true]
        );
        $this->attachRole($admin, 'admin-national');

        $regionLogone = Region::query()->where('code', 'LO')->first();
        if ($regionLogone) {
            $directeurRegional = User::query()->updateOrCreate(
                ['email' => 'occidental@tchadedu.com'],
                ['name' => 'Directeur Régional Logone Occidental', 'password' => $motDePasse, 'is_active' => true, 'region_id' => $regionLogone->id]
            );
            $this->attachRole($directeurRegional, 'direction-regionale');
        }

        $departementNdjamena = Department::query()->where('code', 'NDJ-01')->first();
        if ($departementNdjamena) {
            $directeurDepartemental = User::query()->updateOrCreate(
                ['email' => 'ndjamena@tchadedu.com'],
                ['name' => "Directeur Départemental N'Djamena", 'password' => $motDePasse, 'is_active' => true, 'department_id' => $departementNdjamena->id]
            );
            $this->attachRole($directeurDepartemental, 'direction-departementale');
        }

        $lyceeEboue = Establishment::query()->where('code', 'NDJ-LYC-001')->first();
        if ($lyceeEboue) {
            $directeurEtablissement = User::query()->updateOrCreate(
                ['email' => 'eboue@tchadedu.com'],
                ['name' => 'Directeur Lycée Félix Éboué', 'password' => $motDePasse, 'is_active' => true, 'establishment_id' => $lyceeEboue->id]
            );
            $this->attachRole($directeurEtablissement, 'directeur-etablissement');

            $agentEtablissement = User::query()->updateOrCreate(
                ['email' => 'agenteboue@tchadedu.com'],
                ['name' => 'Agent Lycée Félix Éboué', 'password' => $motDePasse, 'is_active' => true, 'establishment_id' => $lyceeEboue->id]
            );
            $this->attachRole($agentEtablissement, 'agent-etablissement');
        }

        $premierEleve = Student::query()->first();
        if ($premierEleve) {
            $candidat = User::query()->updateOrCreate(
                ['email' => 'eleve@tchadedu.com'],
                ['name' => $premierEleve->first_name . ' ' . $premierEleve->last_name, 'password' => $motDePasse, 'is_active' => true, 'student_id' => $premierEleve->id]
            );
            $this->attachRole($candidat, 'candidat');
        }
    }

    private function attachRole(User $user, string $roleSlug): void
    {
        $role = Role::query()->where('slug', $roleSlug)->first();

        if ($role) {
            $user->roles()->syncWithoutDetaching([$role->id]);
        }
    }
}

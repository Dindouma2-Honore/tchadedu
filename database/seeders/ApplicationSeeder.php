<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Classe;
use App\Models\ExamSession;
use App\Models\ExamType;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;

class ApplicationSeeder extends Seeder
{
    /**
     * Crée quelques candidatures réelles via Application::create(), ce qui
     * déclenche ApplicationObserver::creating() et donc
     * ApplicationReferenceService::generate() — sert de test de bout en
     * bout du générateur de référence dès le seed.
     */
    public function run(): void
    {
        $bepc = ExamType::query()->where('code', 'BEPC')->first();
        $bac = ExamType::query()->where('code', 'BAC')->first();
        $admin = User::query()->where('email', 'admin@tchadedu.com')->first();

        if (! $bepc || ! $bac) {
            return;
        }

        $sessionBepc = ExamSession::query()->where('exam_type_id', $bepc->id)->first();
        $sessionBac = ExamSession::query()->where('exam_type_id', $bac->id)->first();

        $elevesTroisieme = Classe::query()->where('name', '3ème A')->with('students')->get()
            ->flatMap(fn (Classe $classe) => $classe->students);

        $elevesTerminale = Classe::query()->where('name', 'Terminale D')->with('students')->get()
            ->flatMap(fn (Classe $classe) => $classe->students);

        foreach ($elevesTroisieme->take(2) as $eleve) {
            $this->creerCandidature($eleve, $sessionBepc, $admin);
        }

        foreach ($elevesTerminale->take(2) as $eleve) {
            $this->creerCandidature($eleve, $sessionBac, $admin);
        }
    }

    private function creerCandidature(Student $eleve, ?ExamSession $session, ?User $admin): void
    {
        if (! $session) {
            return;
        }

        Application::query()->firstOrCreate(
            [
                'student_id' => $eleve->id,
                'exam_session_id' => $session->id,
            ],
            [
                'establishment_id' => $eleve->establishment_id,
                'status' => 'soumise',
                'submitted_at' => now(),
            ]
        );
    }
}

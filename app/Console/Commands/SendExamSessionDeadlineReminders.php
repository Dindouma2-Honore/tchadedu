<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Application;
use App\Models\ExamSession;
use App\Models\User;
use App\Notifications\ExamSessionDeadlineApproaching;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * "Alerte système" (module Notifications du diagramme) : prévient les
 * établissements ayant encore des dossiers en brouillon à l'approche de la
 * clôture d'une session. Volontairement limitée aux dossiers déjà
 * commencés (statut brouillon) plutôt qu'à "tout élève potentiellement
 * éligible n'ayant pas encore de dossier" : cette dernière règle
 * demanderait de savoir quels élèves sont éligibles à quel examen, une
 * correspondance niveau scolaire <-> type d'examen qui n'est pas modélisée
 * dans le schéma actuel — mieux vaut une alerte fiable à 100% qu'une
 * alerte approximative basée sur une règle inventée.
 */
class SendExamSessionDeadlineReminders extends Command
{
    protected $signature = 'applications:remind-deadlines {--days=7 : Nombre de jours avant la clôture à partir duquel alerter}';

    protected $description = "Alerte les établissements ayant des dossiers en brouillon avant la clôture d'une session d'examen";

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $sessions = ExamSession::query()
            ->where('status', 'ouverte')
            ->whereBetween('registration_end', [now()->startOfDay(), now()->addDays($days)->endOfDay()])
            ->get();

        $totalNotified = 0;

        foreach ($sessions as $session) {
            $totalNotified += $this->remindForSession($session);
        }

        $this->info("{$sessions->count()} session(s) concernée(s), {$totalNotified} destinataire(s) notifié(s).");

        return self::SUCCESS;
    }

    private function remindForSession(ExamSession $session): int
    {
        $draftsByEstablishment = Application::query()
            ->where('exam_session_id', $session->id)
            ->where('status', Application::STATUS_BROUILLON)
            ->selectRaw('establishment_id, count(*) as total')
            ->groupBy('establishment_id')
            ->pluck('total', 'establishment_id');

        if ($draftsByEstablishment->isEmpty()) {
            return 0;
        }

        $notified = 0;

        foreach ($draftsByEstablishment as $establishmentId => $count) {
            $recipients = User::query()
                ->where('establishment_id', $establishmentId)
                ->where('is_active', true)
                ->get();

            if ($recipients->isEmpty()) {
                continue;
            }

            Notification::send($recipients, new ExamSessionDeadlineApproaching($session, (int) $count));
            $notified += $recipients->count();
        }

        return $notified;
    }
}

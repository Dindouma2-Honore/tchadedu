<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\User;
use App\Notifications\ApplicationStatusChanged;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification as NotificationFacade;

/**
 * Détermine QUI doit être notifié à chaque changement de statut (module
 * "Notifications" du diagramme) et déclenche l'envoi. Branché sur la
 * création d'une ApplicationStatusHistory plutôt que sur
 * ApplicationWorkflowService directement : ainsi, toute transition —
 * même une future ajoutée sans passer par le service — reste notifiée
 * tant qu'elle écrit dans cette table, qui est le seul moyen de changer un
 * statut (voir le commentaire de ApplicationWorkflowService::applyTransition).
 */
class ApplicationStatusHistoryObserver
{
    public function created(ApplicationStatusHistory $history): void
    {
        $application = $history->application()->with('establishment')->first();

        if ($application === null) {
            return;
        }

        $recipients = $this->resolveRecipients($application);

        if ($recipients->isNotEmpty()) {
            NotificationFacade::send($recipients, new ApplicationStatusChanged($history));
        }
    }

    /**
     * Trois catégories de destinataires, jamais exclusives :
     * (1) l'établissement propriétaire, toujours informé de l'avancement de son propre dossier ;
     * (2) le niveau administratif qui doit maintenant agir, pour que le circuit ne stagne pas ;
     * (3) le candidat lui-même, s'il dispose d'un compte.
     */
    private function resolveRecipients(Application $application): Collection
    {
        $recipients = User::query()
            ->where('establishment_id', $application->establishment_id)
            ->where('is_active', true)
            ->get();

        $etablissement = $application->establishment;
        $level = $application->requiredLevelForDecision();

        $waitingLevelUsers = match ($level) {
            'departmental' => $etablissement !== null
                ? User::query()->where('department_id', $etablissement->department_id)->where('is_active', true)->get()
                : collect(),
            'regional' => $etablissement !== null
                ? User::query()->where('region_id', $etablissement->region_id)->where('is_active', true)->get()
                : collect(),
            'national' => User::query()
                ->whereNull('region_id')->whereNull('department_id')
                ->whereNull('establishment_id')->whereNull('student_id')
                ->where('is_active', true)->get(),
            default => collect(),
        };

        $candidat = User::query()
            ->where('student_id', $application->student_id)
            ->where('is_active', true)
            ->get();

        return $recipients->merge($waitingLevelUsers)->merge($candidat)->unique('id');
    }
}

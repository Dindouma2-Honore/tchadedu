<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InvalidApplicationTransitionException;
use App\Models\Application;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Fait respecter le workflow de candidature à 3 niveaux de contrôle
 * (section 6 + diagramme "Workflow de candidature et validation") :
 *
 *   brouillon -> soumise -> [contrôle départemental]
 *             -> validee_departement -> [contrôle régional]
 *             -> validee_region -> [contrôle national]
 *             -> inscrit
 *
 * Avec, à chaque étape de contrôle, deux issues possibles : approbation
 * (passage à l'étape suivante) ou demande de correction (retour à
 * l'établissement, motif obligatoire), plus un rejet définitif possible.
 *
 * C'est le SEUL point d'entrée qui doit modifier `applications.status` :
 * les Filament Resources/Actions appellent ce service plutôt que de faire
 * `$application->update(['status' => ...])` directement, pour garantir
 * que chaque changement est autorisé ET tracé dans
 * application_status_histories (section 31.7 : contrôle serveur
 * obligatoire, jamais seulement côté interface).
 */
class ApplicationWorkflowService
{
    /** L'établissement dépose son dossier : brouillon -> soumise. */
    public function submit(Application $application, User $actor, ?string $comment = null): Application
    {
        if ($application->status !== Application::STATUS_BROUILLON) {
            throw new InvalidApplicationTransitionException(
                'Seul un dossier en brouillon peut être soumis.'
            );
        }

        $this->assertActorOwnsEstablishment($actor, $application);

        // Contrôle automatique section 7 (ligne 72) : "Contrôle du statut
        // de paiement lorsque le paiement est obligatoire." Ne s'applique
        // que si la session exige un paiement (Application::requiresPayment()) ;
        // la majorité des sessions, sans frais, ne sont pas concernées.
        if ($application->requiresPayment() && ! $application->hasSuccessfulPayment()) {
            throw new InvalidApplicationTransitionException(
                "Le paiement des frais d'examen doit être confirmé avant de soumettre ce dossier."
            );
        }

        return $this->applyTransition(
            $application,
            $actor,
            Application::STATUS_SOUMISE,
            $comment ?? "Dépôt de candidature par l'établissement.",
            fn () => $application->forceFill(['submitted_at' => now()])
        );
    }

    /** Le niveau responsable de l'étape courante approuve : passage à l'étape suivante (ou inscription si c'était la dernière). */
    public function approve(Application $application, User $actor, ?string $comment = null): Application
    {
        $step = Application::CONTROL_STEPS[$application->status] ?? null;

        if ($step === null) {
            throw new InvalidApplicationTransitionException(
                "Ce dossier n'est pas à une étape de contrôle : aucune approbation possible dans son statut actuel."
            );
        }

        $this->assertActorAtRequiredLevel($actor, $application, $step['level']);

        return $this->applyTransition(
            $application,
            $actor,
            $step['approve_to'],
            $comment,
            fn () => $application->forceFill([
                'validated_at' => now(),
                'validated_by' => $actor->id,
                'rejection_reason' => null,
            ])
        );
    }

    /** Le niveau responsable renvoie le dossier à l'établissement pour correction (motif obligatoire). */
    public function requestCorrection(Application $application, User $actor, string $comment): Application
    {
        $comment = trim($comment);

        if ($comment === '') {
            throw new InvalidApplicationTransitionException(
                'Un motif est obligatoire pour renvoyer un dossier en correction.'
            );
        }

        $step = Application::CONTROL_STEPS[$application->status] ?? null;

        if ($step === null) {
            throw new InvalidApplicationTransitionException(
                "Ce dossier n'est pas à une étape de contrôle : aucune correction ne peut être demandée dans son statut actuel."
            );
        }

        $this->assertActorAtRequiredLevel($actor, $application, $step['level']);

        return $this->applyTransition(
            $application,
            $actor,
            $step['correction_to'],
            $comment,
            fn () => $application->forceFill([
                'validated_at' => null,
                'validated_by' => $actor->id,
                'rejection_reason' => $comment,
            ])
        );
    }

    /** L'établissement corrige puis redépose : le dossier repart vers le niveau qui avait demandé la correction. */
    public function resubmit(Application $application, User $actor, ?string $comment = null): Application
    {
        $target = Application::RESUBMIT_TARGETS[$application->status] ?? null;

        if ($target === null) {
            throw new InvalidApplicationTransitionException(
                "Ce dossier n'est pas en attente de correction : aucune resoumission possible."
            );
        }

        $this->assertActorOwnsEstablishment($actor, $application);

        return $this->applyTransition(
            $application,
            $actor,
            $target,
            $comment ?? 'Dossier corrigé et resoumis par l\'établissement.',
            fn () => $application->forceFill([
                'submitted_at' => now(),
                'rejection_reason' => null,
            ])
        );
    }

    /** Rejet définitif, possible à n'importe quelle étape de contrôle, par le niveau responsable de cette étape (motif obligatoire). */
    public function reject(Application $application, User $actor, string $reason): Application
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new InvalidApplicationTransitionException(
                'Un motif est obligatoire pour rejeter un dossier.'
            );
        }

        $step = Application::CONTROL_STEPS[$application->status] ?? null;

        if ($step === null) {
            throw new InvalidApplicationTransitionException(
                'Ce dossier ne peut pas être rejeté dans son statut actuel.'
            );
        }

        $this->assertActorAtRequiredLevel($actor, $application, $step['level']);

        return $this->applyTransition(
            $application,
            $actor,
            Application::STATUS_REJETEE,
            $reason,
            fn () => $application->forceFill([
                'validated_at' => now(),
                'validated_by' => $actor->id,
                'rejection_reason' => $reason,
            ])
        );
    }

    /** Annulation par l'établissement (dossier pas encore engagé dans le circuit de validation) ou par le national. */
    public function cancel(Application $application, User $actor, ?string $comment = null): Application
    {
        if ($application->isTerminal()) {
            throw new InvalidApplicationTransitionException(
                'Un dossier déjà inscrit, rejeté ou annulé ne peut plus être annulé.'
            );
        }

        if ($actor->scopeLevel() !== 'national') {
            $this->assertActorOwnsEstablishment($actor, $application);
        }

        return $this->applyTransition(
            $application,
            $actor,
            Application::STATUS_ANNULEE,
            $comment ?? 'Candidature annulée.',
            fn () => $application->forceFill(['rejection_reason' => $comment])
        );
    }

    /**
     * Un compte national conserve une vision et une capacité d'action
     * nationale (section 31.5) : il peut agir à n'importe quel niveau de
     * contrôle. Un compte de niveau intermédiaire doit correspondre
     * exactement au niveau requis par l'étape ET être dans le bon
     * périmètre géographique (département/région de l'établissement).
     */
    private function assertActorAtRequiredLevel(User $actor, Application $application, string $requiredLevel): void
    {
        if ($actor->scopeLevel() === 'national') {
            return;
        }

        if ($actor->scopeLevel() !== $requiredLevel) {
            throw new InvalidApplicationTransitionException(
                "Cette action requiert un compte de niveau {$this->levelLabel($requiredLevel)}."
            );
        }

        $etablissement = $application->establishment;

        $inPerimeter = match ($requiredLevel) {
            'departmental' => $etablissement?->department_id === $actor->department_id,
            'regional' => $etablissement?->region_id === $actor->region_id,
            default => false,
        };

        if (! $inPerimeter) {
            throw new InvalidApplicationTransitionException(
                "Ce dossier ne relève pas de votre périmètre {$this->levelLabel($requiredLevel)}."
            );
        }
    }

    /** Réservé à l'établissement propriétaire du dossier (ou au national). */
    private function assertActorOwnsEstablishment(User $actor, Application $application): void
    {
        if ($actor->scopeLevel() === 'national') {
            return;
        }

        if ($actor->scopeLevel() !== 'establishment' || $actor->establishment_id !== $application->establishment_id) {
            throw new InvalidApplicationTransitionException(
                "Cette action est réservée à l'établissement propriétaire du dossier."
            );
        }
    }

    private function levelLabel(string $level): string
    {
        return match ($level) {
            'departmental' => 'départemental',
            'regional' => 'régional',
            'national' => 'national',
            default => $level,
        };
    }

    /**
     * Applique effectivement la transition dans une transaction : met à
     * jour le statut (+ champs additionnels propres à l'action via
     * $mutate), puis journalise l'entrée d'historique correspondante.
     * Un seul chemin de code pour toutes les transitions => impossible de
     * changer le statut sans que l'historique soit écrit en même temps.
     */
    private function applyTransition(
        Application $application,
        User $actor,
        string $toStatus,
        ?string $comment,
        \Closure $mutate
    ): Application {
        return DB::transaction(function () use ($application, $actor, $toStatus, $comment, $mutate) {
            $fromStatus = $application->status;

            $mutate();
            $application->status = $toStatus;
            $application->save();

            $application->statusHistories()->create([
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'changed_by' => $actor->id,
                'comment' => $comment,
            ]);

            return $application->fresh(['statusHistories']);
        });
    }
}

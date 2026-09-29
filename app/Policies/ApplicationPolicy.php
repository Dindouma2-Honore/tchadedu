<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Application;
use App\Models\User;

class ApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('applications.view') || $user->scopeLevel() === 'self';
    }

    public function view(User $user, Application $application): bool
    {
        return $this->inScope($user, $application);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('applications.create');
    }

    public function update(User $user, Application $application): bool
    {
        return $this->inScope($user, $application)
            && ($user->hasPermissionTo('applications.submit') || $user->hasPermissionTo('applications.validate'));
    }

    /** Dépôt initial du dossier par l'établissement propriétaire (brouillon -> soumise). */
    public function submit(User $user, Application $application): bool
    {
        return $application->status === Application::STATUS_BROUILLON
            && $user->hasPermissionTo('applications.submit')
            && $this->ownsEstablishment($user, $application);
    }

    /** Resoumission après correction : réservée à l'établissement propriétaire du dossier. */
    public function resubmit(User $user, Application $application): bool
    {
        return $application->isAwaitingCorrection()
            && $user->hasPermissionTo('applications.submit')
            && $this->ownsEstablishment($user, $application);
    }

    /** Approbation à l'étape de contrôle courante (départemental, régional ou national selon le statut). */
    public function approve(User $user, Application $application): bool
    {
        return $user->hasPermissionTo('applications.validate') && $this->atRequiredLevel($user, $application);
    }

    /** Renvoi en correction à l'étape de contrôle courante. */
    public function requestCorrection(User $user, Application $application): bool
    {
        return $user->hasPermissionTo('applications.validate') && $this->atRequiredLevel($user, $application);
    }

    /** Rejet définitif à l'étape de contrôle courante. */
    public function reject(User $user, Application $application): bool
    {
        return $user->hasPermissionTo('applications.reject') && $this->atRequiredLevel($user, $application);
    }

    /** Annulation : établissement propriétaire (tant que le dossier n'est pas clos) ou national. */
    public function cancel(User $user, Application $application): bool
    {
        if ($application->isTerminal() || ! $user->hasPermissionTo('applications.cancel')) {
            return false;
        }

        return $user->scopeLevel() === 'national' || $this->ownsEstablishment($user, $application);
    }

    public function delete(User $user, Application $application): bool
    {
        return $user->scopeLevel() === 'national';
    }

    /**
     * Vrai si le dossier est à une étape de contrôle et que $user a
     * exactement le niveau administratif requis pour cette étape (ou est
     * national), dans le bon périmètre géographique. Reflète
     * ApplicationWorkflowService::assertActorAtRequiredLevel : cette
     * duplication volontaire permet à Filament de masquer les actions non
     * autorisées dans l'interface, le Service restant le garde-fou final
     * côté serveur.
     */
    private function atRequiredLevel(User $user, Application $application): bool
    {
        $requiredLevel = $application->requiredLevelForDecision();

        if ($requiredLevel === null) {
            return false;
        }

        if ($user->scopeLevel() === 'national') {
            return true;
        }

        if ($user->scopeLevel() !== $requiredLevel) {
            return false;
        }

        $etablissement = $application->establishment;

        return match ($requiredLevel) {
            'departmental' => $etablissement?->department_id === $user->department_id,
            'regional' => $etablissement?->region_id === $user->region_id,
            default => false,
        };
    }

    private function ownsEstablishment(User $user, Application $application): bool
    {
        if ($user->scopeLevel() === 'national') {
            return true;
        }

        return $user->scopeLevel() === 'establishment' && $application->establishment_id === $user->establishment_id;
    }

    private function inScope(User $user, Application $application): bool
    {
        if ($user->scopeLevel() === 'self') {
            return $application->student_id === $user->student_id;
        }

        $etablissement = $application->establishment;

        return match ($user->scopeLevel()) {
            'national' => true,
            'regional' => $etablissement?->region_id === $user->region_id,
            'departmental' => $etablissement?->department_id === $user->department_id,
            'establishment' => $application->establishment_id === $user->establishment_id,
            default => false,
        };
    }
}

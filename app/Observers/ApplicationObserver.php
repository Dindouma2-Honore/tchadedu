<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Application;
use App\Services\ApplicationReferenceService;

/**
 * Attribue automatiquement la référence publique non-devinable
 * (ex: BEPC-2027-TCD-00001234) à la création d'une candidature, sans que
 * l'appelant (Filament, API, import de masse) ait à s'en soucier.
 */
class ApplicationObserver
{
    public function __construct(
        private readonly ApplicationReferenceService $referenceService,
    ) {}

    public function creating(Application $application): void
    {
        if (blank($application->reference)) {
            $application->reference = $this->referenceService->generate(
                $application->examSession
            );
        }
    }
}

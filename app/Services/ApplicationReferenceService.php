<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ExamSession;
use Illuminate\Support\Facades\DB;

/**
 * Génère la référence publique d'une candidature (section 9.2 du document
 * d'architecture) : {CODE_EXAMEN}-{ANNEE}-TCD-{SEQUENCE sur 8 chiffres}
 * ex: BEPC-2027-TCD-00001234
 *
 * La séquence est propre à chaque session d'examen (exam_sessions.
 * last_reference_sequence) et incrémentée sous verrou de ligne
 * (lockForUpdate) à l'intérieur d'une transaction, pour rester correcte
 * même en cas de soumissions concurrentes lors d'un pic de clôture.
 */
class ApplicationReferenceService
{
    public function generate(ExamSession $examSession): string
    {
        return DB::transaction(function () use ($examSession) {
            /** @var ExamSession $session */
            $session = ExamSession::query()
                ->whereKey($examSession->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $sequence = $session->last_reference_sequence + 1;

            $session->forceFill([
                'last_reference_sequence' => $sequence,
            ])->save();

            $session->loadMissing(['examType', 'academicYear']);

            $annee = $session->academicYear->end_date->format('Y');
            $codeExamen = strtoupper($session->examType->code);

            return sprintf(
                '%s-%s-TCD-%08d',
                $codeExamen,
                $annee,
                $sequence
            );
        });
    }
}

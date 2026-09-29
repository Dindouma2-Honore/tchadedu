<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Levée par ApplicationWorkflowService quand une action (soumission,
 * approbation, correction, rejet, annulation) est demandée alors que le
 * dossier n'est pas dans un statut compatible, ou que l'utilisateur n'a
 * pas le niveau administratif requis pour agir à cette étape.
 *
 * Le message est rédigé pour être affiché tel quel dans une notification
 * Filament (aucune donnée technique sensible dedans).
 */
class InvalidApplicationTransitionException extends RuntimeException
{
}

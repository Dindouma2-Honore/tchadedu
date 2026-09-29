<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Levée par PaymentService quand une action est demandée sur un paiement
 * qui n'est pas dans un état compatible (ex: rembourser un paiement non
 * payé), ou par ApplicationWorkflowService quand une soumission est
 * bloquée faute de paiement confirmé (contrôle automatique section 7,
 * ligne 72 : "Contrôle du statut de paiement lorsque le paiement est
 * obligatoire").
 */
class InvalidPaymentTransitionException extends RuntimeException
{
}

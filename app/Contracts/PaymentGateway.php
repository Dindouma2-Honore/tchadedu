<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Payment;

/**
 * Abstraction du prestataire de paiement (section 18 : "le paiement doit
 * être traité comme une intégration indépendante"). Aucun fournisseur
 * n'est codé en dur : pour brancher un vrai opérateur (Orange Money,
 * Airtel Money, carte bancaire...), implémenter cette interface et changer
 * la liaison dans AppServiceProvider::register() — voir
 * App\Services\Payment\SimulatedPaymentGateway pour l'implémentation de
 * démonstration actuellement branchée.
 *
 * IMPORTANT : `initiate()` ne doit JAMAIS être considéré comme une preuve
 * de paiement, quel que soit ce qu'il retourne. Seul un callback
 * serveur-à-serveur vérifié (voir PaymentWebhookController +
 * PaymentWebhookVerifier) peut faire passer un paiement à "success".
 */
interface PaymentGateway
{
    /**
     * Démarre une intention de paiement chez le prestataire.
     *
     * @return array{redirect_url: string, provider_reference: string}
     */
    public function initiate(Payment $payment): array;

    /**
     * Interroge le prestataire pour connaître le statut RÉEL et VÉRIFIÉ
     * d'un paiement (jamais une simple lecture du corps d'une
     * notification webhook, qui peut être falsifiée ou rejouée). C'est
     * cette méthode, pas le contenu d'un webhook, qui fait autorité.
     *
     * @return string Une valeur parmi Payment::STATUS_SUCCESS, ::STATUS_FAILED, ::STATUS_PENDING.
     */
    public function verify(Payment $payment): string;
}

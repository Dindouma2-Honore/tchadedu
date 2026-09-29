<?php

declare(strict_types=1);

namespace App\Services\Payment;

/**
 * Signature HMAC-SHA256 des callbacks de paiement (section 18 :
 * "idéalement par callback/webhook signé et vérification serveur à
 * serveur"). Utilisée à la fois pour vérifier les callbacks entrants
 * (PaymentWebhookController) et, côté démonstration, pour signer les
 * appels simulés (DemoPaymentController) exactement comme le ferait un
 * vrai prestataire — même code de vérification des deux côtés.
 */
class PaymentWebhookVerifier
{
    public function __construct(private readonly string $secret) {}

    public function sign(string $rawPayload): string
    {
        return hash_hmac('sha256', $rawPayload, $this->secret);
    }

    public function verify(string $rawPayload, ?string $signature): bool
    {
        if (blank($signature)) {
            return false;
        }

        return hash_equals($this->sign($rawPayload), $signature);
    }
}

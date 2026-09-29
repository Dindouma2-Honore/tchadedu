<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Models\Payment;
use Illuminate\Support\Str;
/**
 * Implémentation par défaut de PaymentGateway : n'appelle AUCUN prestataire
 * réel. C'est un choix explicite et documenté (même logique que
 * App\Services\Sms\LogSmsGateway) : le projet n'a pas de contrat
 * opérateur de paiement confirmé au moment de l'écriture de ce module.
 *
 * `redirect_url` pointe vers une page de démonstration hébergée par
 * l'application elle-même (DemoPaymentController), qui simule la page de
 * paiement d'un vrai prestataire : elle notifie le résultat via un
 * callback signé, exactement comme le ferait un vrai prestataire — ce qui
 * permet de tester honnêtement tout le circuit de vérification serveur
 * décrit section 18, sans jamais faire semblant qu'un simple clic suffit.
 *
 * À remplacer avant mise en production par une implémentation réelle de
 * PaymentGateway, liée dans AppServiceProvider::register() à la place de
 * celle-ci.
 */
class SimulatedPaymentGateway implements PaymentGateway
{
    public function initiate(Payment $payment): array
    {
        return [
            'redirect_url' => route('payments.demo.show', $payment->reference),
            'provider_reference' => 'SIM-'.Str::upper(Str::random(12)),
        ];
    }

    /**
     * N'a rien à vérifier auprès d'un tiers : dans le flux de
     * démonstration, c'est le clic sur la page simulée elle-même qui joue
     * le rôle du prestataire (voir DemoPaymentController). Cette méthode
     * n'est donc jamais appelée par le flux normal de /webhooks/payments —
     * elle n'existe que pour satisfaire l'interface, et se contente de
     * refléter le statut déjà enregistré en base.
     */
    public function verify(Payment $payment): string
    {
        return $payment->status;
    }
}

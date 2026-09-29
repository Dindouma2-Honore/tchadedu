<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Exceptions\InvalidPaymentTransitionException;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Intégration réelle de CinetPay (https://cinetpay.com), agrégateur
 * couvrant le Mobile Money en Afrique centrale et francophone — dont le
 * Tchad, en XAF — via Orange Money, Airtel Money, Moov Money, etc.
 *
 * Documentation officielle utilisée pour cette implémentation :
 *   - Initialisation : https://docs.cinetpay.com/api/1.0-fr/checkout/initialisation
 *   - Vérification    : https://docs.cinetpay.com/api/1.0-fr/checkout/verification
 *   - Notification     : https://docs.cinetpay.com/api/1.0-fr/checkout/notification
 *   - X-TOKEN HMAC     : https://docs.cinetpay.com/api/1.0-fr/checkout/hmac
 *
 * Comme documenté par CinetPay lui-même : la notification (webhook) ne
 * doit JAMAIS être considérée comme la source de vérité, y compris après
 * vérification du X-TOKEN — un appel serveur-à-serveur à l'API de
 * vérification de transaction est systématiquement nécessaire pour
 * obtenir le statut réel. C'est exactement ce que fait verify() ci-dessous,
 * et c'est la méthode que App\Http\Controllers\CinetPayWebhookController
 * appelle avant d'accepter un changement de statut — jamais le contenu
 * brut de la notification.
 */
class CinetPayGateway implements PaymentGateway
{
    private const INIT_URL = 'https://api-checkout.cinetpay.com/v2/payment';

    private const CHECK_URL = 'https://api-checkout.cinetpay.com/v2/payment/check';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $siteId,
    ) {}

    public function initiate(Payment $payment): array
    {
        $application = $payment->application()->with('student')->first();
        $student = $application->student;

        $response = Http::asJson()->timeout(15)->post(self::INIT_URL, [
            'apikey' => $this->apiKey,
            'site_id' => $this->siteId,
            'transaction_id' => $payment->reference,
            // CinetPay attend un montant entier (XAF n'a pas de sous-unité usuelle).
            'amount' => (int) round((float) $payment->amount),
            'currency' => $payment->currency,
            'description' => 'Frais examen — dossier '.$application->reference,
            'notify_url' => route('payments.cinetpay.webhook'),
            'return_url' => route('payments.cinetpay.return', $payment->reference),
            'channels' => 'MOBILE_MONEY',
            'customer_name' => $student->first_name ?? 'Candidat',
            'customer_surname' => $student->last_name ?? 'TchadEdu',
            'metadata' => $payment->reference,
            'lang' => 'FR',
        ]);

        $data = $response->json() ?? [];

        if (($data['code'] ?? null) !== '201') {
            Log::error('Échec initialisation paiement CinetPay', ['response' => $data, 'payment' => $payment->reference]);

            throw new InvalidPaymentTransitionException(
                'Le prestataire de paiement a refusé la demande : '.($data['description'] ?? $data['message'] ?? 'erreur inconnue').'.'
            );
        }

        return [
            'redirect_url' => $data['data']['payment_url'],
            'provider_reference' => $data['data']['payment_token'],
        ];
    }

    public function verify(Payment $payment): string
    {
        $response = Http::asJson()->timeout(15)->post(self::CHECK_URL, [
            'apikey' => $this->apiKey,
            'site_id' => $this->siteId,
            'transaction_id' => $payment->reference,
        ]);

        $data = $response->json() ?? [];
        $status = $data['data']['status'] ?? null;

        Log::info('Vérification paiement CinetPay', ['payment' => $payment->reference, 'response' => $data]);

        return match ($status) {
            'ACCEPTED' => Payment::STATUS_SUCCESS,
            'REFUSED', 'CANCELLED' => Payment::STATUS_FAILED,
            // WAITING_FOR_CUSTOMER : certains opérateurs mobile money confirment
            // par notification "push" sur le téléphone du client, en attente de
            // sa validation. La documentation CinetPay est explicite : NE PAS
            // traiter ce statut comme un échec, sous peine de rejeter à tort des
            // paiements simplement encore en cours de validation par le client.
            'WAITING_FOR_CUSTOMER' => Payment::STATUS_PENDING,
            default => Payment::STATUS_PENDING,
        };
    }
}

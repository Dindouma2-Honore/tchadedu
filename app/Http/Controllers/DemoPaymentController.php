<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\Payment\PaymentWebhookVerifier;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Simule la page de paiement hébergée par un prestataire externe. À
 * REMPLACER PAR LE VRAI PRESTATAIRE avant mise en production — cette page
 * n'existe que parce qu'aucun prestataire réel n'est encore branché (voir
 * App\Services\Payment\SimulatedPaymentGateway).
 *
 * Le point important, conservé même en simulation : le résultat n'est
 * JAMAIS appliqué directement depuis cette page. Le clic déclenche un
 * appel HTTP signé vers PaymentWebhookController, exactement comme le
 * ferait le serveur d'un vrai prestataire — la page elle-même n'a aucun
 * pouvoir de changer le statut du paiement.
 */
class DemoPaymentController extends Controller
{
    public function __construct(private readonly PaymentWebhookVerifier $verifier) {}

    public function show(Payment $payment): View
    {
        return view('payments.demo', ['payment' => $payment]);
    }

    public function confirm(Request $request, Payment $payment): RedirectResponse
    {
        $status = $request->input('outcome') === 'failed' ? 'failed' : 'success';

        $payload = [
            'payment_reference' => $payment->reference,
            'provider_reference' => $payment->provider_reference,
            'status' => $status,
            'simulated' => true,
        ];

        $rawBody = json_encode($payload);
        $signature = $this->verifier->sign($rawBody);

        try {
            Http::withBody($rawBody, 'application/json')
                ->withHeaders(['X-Payment-Signature' => $signature])
                ->post(route('payments.webhook'));
        } catch (ConnectionException $exception) {
            Log::error('Échec de l\'appel webhook de paiement simulé', ['message' => $exception->getMessage()]);

            return redirect()
                ->route('payments.demo.show', $payment->reference)
                ->with('error', "La confirmation n'a pas pu être transmise. Réessayez.");
        }

        return redirect()
            ->route('payments.demo.show', $payment->reference)
            ->with('status', $status);
    }
}

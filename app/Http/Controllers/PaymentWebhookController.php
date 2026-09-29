<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\Payment\PaymentWebhookVerifier;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Point d'entrée UNIQUE par lequel un paiement peut passer à "success"
 * (avec confirmManually(), réservé aux agents habilités). Reçoit un
 * callback serveur-à-serveur du prestataire (section 18) — jamais une
 * requête initiée par le navigateur du candidat.
 *
 * Exclu de la vérification CSRF (voir bootstrap/app.php) : un prestataire
 * externe n'a pas de session ni de jeton CSRF Laravel.
 */
class PaymentWebhookController extends Controller
{
    public function __construct(
        private readonly PaymentWebhookVerifier $verifier,
        private readonly PaymentService $paymentService,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        $signature = $request->header('X-Payment-Signature');

        if (! $this->verifier->verify($rawBody, $signature)) {
            return response()->json(['error' => 'invalid_signature'], 401);
        }

        $payload = json_decode($rawBody, true) ?? [];
        $reference = $payload['payment_reference'] ?? null;
        $status = $payload['status'] ?? null;

        if (blank($reference) || blank($status)) {
            return response()->json(['error' => 'invalid_payload'], 422);
        }

        $payment = Payment::query()->where('reference', $reference)->first();

        if ($payment === null) {
            // 200 volontaire : ne pas donner à un tiers un moyen de sonder
            // l'existence de références de paiement par essai/erreur.
            return response()->json(['status' => 'ignored']);
        }

        $this->paymentService->handleCallback($payment, $status, $payload);

        return response()->json(['status' => 'ok']);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Réception de la notification CinetPay (section 18 du document +
 * documentation CinetPay officielle : https://docs.cinetpay.com/api/1.0-fr/checkout/notification).
 *
 * Deux couches de protection, aucune des deux ne suffisant seule :
 *   1. Le X-TOKEN (HMAC-SHA256, filtre les requêtes non authentiques) —
 *      seulement si CINETPAY_SECRET_KEY est configuré.
 *   2. Un appel serveur-à-serveur à l'API "Vérification de transaction"
 *      (PaymentGateway::verify()) — la SEULE source de vérité sur le
 *      statut. Le corps de la notification n'est utilisé que pour
 *      identifier QUEL paiement est concerné (cpm_trans_id), jamais pour
 *      décider de son statut : c'est exactement ce que recommande
 *      CinetPay elle-même dans sa documentation.
 */
class CinetPayWebhookController extends Controller
{
    /** Champs du corps de la notification, dans l'ordre exact attendu par la formule de concaténation CinetPay (section X-TOKEN HMAC). */
    private const HMAC_FIELDS = [
        'cpm_site_id', 'cpm_trans_id', 'cpm_trans_date', 'cpm_amount', 'cpm_currency',
        'signature', 'payment_method', 'cel_phone_num', 'cpm_phone_prefixe',
        'cpm_language', 'cpm_version', 'cpm_payment_config', 'cpm_page_action',
        'cpm_custom', 'cpm_designation', 'cpm_error_message',
    ];

    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly PaymentService $paymentService,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $secretKey = config('services.cinetpay.secret_key');

        if (filled($secretKey) && ! $this->hasValidToken($request, $secretKey)) {
            return response()->json(['error' => 'invalid_signature'], 401);
        }

        $reference = $request->input('cpm_trans_id');

        if (blank($reference)) {
            return response()->json(['error' => 'invalid_payload'], 422);
        }

        $payment = Payment::query()->where('reference', $reference)->first();

        if ($payment === null) {
            // 200 volontaire : ne pas révéler à un tiers si une référence existe.
            return response()->json(['status' => 'ignored']);
        }

        // Le statut appliqué n'est JAMAIS celui du corps de la requête —
        // toujours celui renvoyé par un appel serveur-à-serveur dédié.
        $verifiedStatus = $this->gateway->verify($payment);

        $this->paymentService->handleCallback($payment, $verifiedStatus, $request->all());

        return response()->json(['status' => 'ok']);
    }

    private function hasValidToken(Request $request, string $secretKey): bool
    {
        $received = $request->header('x-token');

        if (blank($received)) {
            return false;
        }

        $concatenated = collect(self::HMAC_FIELDS)
            ->map(fn (string $field) => (string) $request->input($field, ''))
            ->implode('');

        return hash_equals(hash_hmac('sha256', $concatenated, $secretKey), (string) $received);
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Exceptions\InvalidPaymentTransitionException;
use App\Models\Application;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\User;
use App\Notifications\PaymentStatusChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Unique point d'entrée autorisé à créer un paiement ou à changer son
 * statut (section 18). Comme ApplicationWorkflowService pour les
 * candidatures : aucun code ailleurs dans l'application ne doit faire
 * `$payment->update(['status' => ...])` directement.
 *
 * Invariant central, rappelé ici car il structure toute la classe : SEULS
 * handleCallback() (déclenché par un webhook signé et vérifié) et
 * confirmManually() (réconciliation humaine explicitement tracée) peuvent
 * faire passer un paiement à "success". initiate() ne le peut jamais.
 */
class PaymentService
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    /**
     * Démarre un paiement pour une candidature dont la session l'exige.
     * Idempotent : si un paiement non terminal existe déjà pour ce
     * dossier, il est réutilisé plutôt que dupliqué (évite les doublons en
     * cas de double clic ou de nouvel essai après un rechargement).
     *
     * @return array{payment: Payment, redirect_url: string}
     */
    public function initiate(Application $application, User $actor): array
    {
        if (! $application->requiresPayment()) {
            throw new InvalidPaymentTransitionException(
                "La session d'examen de ce dossier ne requiert aucun paiement."
            );
        }

        if ($application->hasSuccessfulPayment()) {
            throw new InvalidPaymentTransitionException(
                'Les frais de ce dossier ont déjà été réglés.'
            );
        }

        $existing = $application->payments()
            ->whereIn('status', [Payment::STATUS_INITIATED, Payment::STATUS_PENDING])
            ->latest('id')
            ->first();

        $payment = $existing ?? $this->createPayment($application, $actor);

        $result = $this->gateway->initiate($payment);

        $payment->forceFill(['provider_reference' => $result['provider_reference']])->save();

        $this->logTransaction($payment, 'initiated', null, $payment->status, $actor, [
            'provider_reference' => $result['provider_reference'],
        ]);

        return ['payment' => $payment->fresh(), 'redirect_url' => $result['redirect_url']];
    }

    /**
     * Traite un callback prestataire déjà vérifié (signature + décodage
     * effectués par PaymentWebhookController AVANT l'appel à cette
     * méthode : cette méthode ne revérifie rien, elle applique la
     * transition). Idempotent : un callback dupliqué sur un paiement déjà
     * dans un état terminal est journalisé mais n'a aucun effet, pour
     * encaisser sans risque les retries que font la plupart des
     * prestataires réels.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleCallback(Payment $payment, string $providerStatus, array $payload): Payment
    {
        $newStatus = match ($providerStatus) {
            'success' => Payment::STATUS_SUCCESS,
            'failed' => Payment::STATUS_FAILED,
            'expired' => Payment::STATUS_EXPIRED,
            'pending' => Payment::STATUS_PENDING,
            default => throw new InvalidPaymentTransitionException("Statut prestataire inconnu : {$providerStatus}."),
        };

        if ($payment->isTerminal()) {
            // Callback dupliqué (retry prestataire) ou tardif sur un
            // paiement déjà clos : on journalise pour la traçabilité mais
            // on n'applique aucun changement d'état.
            $this->logTransaction($payment, 'callback_received_ignored', $payment->status, $payment->status, null, $payload);

            return $payment;
        }

        return DB::transaction(function () use ($payment, $newStatus, $payload) {
            $before = $payment->status;

            $payment->status = $newStatus;
            if ($newStatus === Payment::STATUS_SUCCESS) {
                $payment->paid_at = now();
            }
            $payment->save();

            $this->logTransaction($payment, 'callback_received', $before, $newStatus, null, $payload);

            if ($newStatus === Payment::STATUS_SUCCESS) {
                $this->issueReceipt($payment);
            }

            $this->notify($payment);

            return $payment->fresh(['receipt']);
        });
    }

    /**
     * Réconciliation manuelle d'un paiement reçu hors ligne (espèces au
     * trésor, virement bancaire...) par un agent habilité — réaliste dans
     * un contexte où le paiement numérique n'est pas encore universel.
     * Toujours tracée nominativement, jamais silencieuse.
     */
    public function confirmManually(Payment $payment, User $actor, string $note): Payment
    {
        if ($payment->isTerminal()) {
            throw new InvalidPaymentTransitionException(
                'Ce paiement est déjà dans un état définitif ('.$payment->statusLabel().').'
            );
        }

        return DB::transaction(function () use ($payment, $actor, $note) {
            $before = $payment->status;

            $payment->status = Payment::STATUS_SUCCESS;
            $payment->paid_at = now();
            $payment->save();

            $this->logTransaction($payment, 'manual_confirmation', $before, Payment::STATUS_SUCCESS, $actor, [
                'note' => $note,
            ]);

            $this->issueReceipt($payment);
            $this->notify($payment);

            return $payment->fresh(['receipt']);
        });
    }

    /** Remboursement d'un paiement déjà confirmé. */
    public function refund(Payment $payment, User $actor, string $reason): Payment
    {
        if ($payment->status !== Payment::STATUS_SUCCESS) {
            throw new InvalidPaymentTransitionException(
                'Seul un paiement confirmé (payé) peut être remboursé.'
            );
        }

        return DB::transaction(function () use ($payment, $actor, $reason) {
            $payment->status = Payment::STATUS_REFUNDED;
            $payment->save();

            $this->logTransaction($payment, 'refund', Payment::STATUS_SUCCESS, Payment::STATUS_REFUNDED, $actor, [
                'reason' => $reason,
            ]);

            $this->notify($payment);

            return $payment->fresh();
        });
    }

    /** Maintenance : marque comme expirés les paiements non finalisés au-delà du délai (par défaut 24h). Destiné à une commande planifiée. */
    public function expireStalePayments(int $hoursThreshold = 24): int
    {
        $stale = Payment::query()
            ->whereIn('status', [Payment::STATUS_INITIATED, Payment::STATUS_PENDING])
            ->where('created_at', '<=', now()->subHours($hoursThreshold))
            ->get();

        foreach ($stale as $payment) {
            $before = $payment->status;
            $payment->status = Payment::STATUS_EXPIRED;
            $payment->save();

            $this->logTransaction($payment, 'expired', $before, Payment::STATUS_EXPIRED, null, [
                'hours_threshold' => $hoursThreshold,
            ]);
        }

        return $stale->count();
    }

    private function createPayment(Application $application, User $actor): Payment
    {
        // Boucle de tentatives pour l'improbable collision de référence
        // aléatoire, plutôt qu'une transaction verrouillante coûteuse pour
        // un volume de paiements simultanés qui reste modeste en pratique.
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return Payment::create([
                    'reference' => $this->generateReference(),
                    'application_id' => $application->id,
                    'initiated_by' => $actor->id,
                    'amount' => $application->examSession->fee_amount,
                    'currency' => 'XAF',
                    'status' => Payment::STATUS_INITIATED,
                    'provider' => 'simulated',
                ]);
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                if ($attempt === 2) {
                    throw $e;
                }
            }
        }

        throw new InvalidPaymentTransitionException('Impossible de générer une référence de paiement unique.');
    }

    private function generateReference(): string
    {
        return 'PAY-'.now()->format('Y').'-'.Str::upper(Str::random(10));
    }

    private function issueReceipt(Payment $payment): void
    {
        if ($payment->receipt()->exists()) {
            return;
        }

        Receipt::create([
            'payment_id' => $payment->id,
            'number' => 'REC-'.now()->format('Y').'-'.Str::upper(Str::random(10)),
            'issued_at' => now(),
        ]);
    }

    /** @param array<string, mixed>|null $payload */
    private function logTransaction(Payment $payment, string $event, ?string $before, ?string $after, ?User $actor, ?array $payload): void
    {
        $payment->transactions()->create([
            'event' => $event,
            'status_before' => $before,
            'status_after' => $after,
            'actor_id' => $actor?->id,
            'payload' => $payload,
        ]);
    }

    /** Établissement propriétaire du dossier + candidat, s'il a un compte — même logique que ApplicationStatusHistoryObserver, sans l'escalade hiérarchique qui n'a pas de sens pour un paiement. */
    private function notify(Payment $payment): void
    {
        $application = $payment->application()->with('establishment')->first();

        $recipients = User::query()
            ->where('establishment_id', $application->establishment_id)
            ->where('is_active', true)
            ->get()
            ->merge(
                User::query()->where('student_id', $application->student_id)->where('is_active', true)->get()
            )
            ->unique('id');

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new PaymentStatusChanged($payment));
        }
    }
}

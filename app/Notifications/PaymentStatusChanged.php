<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Payment;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Notifie un changement de statut de paiement (module "Notifications" du diagramme). */
class PaymentStatusChanged extends Notification
{
    private const SMS_WORTHY_STATUSES = [
        Payment::STATUS_SUCCESS,
        Payment::STATUS_FAILED,
        Payment::STATUS_REFUNDED,
    ];

    public function __construct(private readonly Payment $payment) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (filled($notifiable->email ?? null)) {
            $channels[] = 'mail';
        }

        if (filled($notifiable->phone ?? null) && in_array($this->payment->status, self::SMS_WORTHY_STATUSES, true)) {
            $channels[] = SmsChannel::class;
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Paiement '.$this->payment->reference.' : '.$this->payment->statusLabel())
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line($this->summarySentence())
            ->line('Montant : '.number_format((float) $this->payment->amount, 0, ',', ' ').' '.$this->payment->currency)
            ->line('Référence du dossier : '.$this->payment->application->reference)
            ->salutation('Tchad Éducation Digital');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'payment_id' => $this->payment->id,
            'payment_reference' => $this->payment->reference,
            'application_id' => $this->payment->application_id,
            'application_reference' => $this->payment->application->reference,
            'status' => $this->payment->status,
            'status_label' => $this->payment->statusLabel(),
            'message' => $this->summarySentence(),
        ];
    }

    public function toSms(object $notifiable): string
    {
        return sprintf(
            'TchadEdu : paiement %s -> %s (%s %s).',
            $this->payment->reference,
            $this->payment->statusLabel(),
            number_format((float) $this->payment->amount, 0, ',', ' '),
            $this->payment->currency
        );
    }

    public function logSubject(): string
    {
        return 'Paiement '.$this->payment->reference.' : '.$this->payment->statusLabel();
    }

    public function logBody(): string
    {
        return $this->summarySentence();
    }

    public function relatedApplicationId(): int
    {
        return $this->payment->application_id;
    }

    private function summarySentence(): string
    {
        return sprintf(
            'Le paiement %s (dossier %s) est passé au statut « %s ».',
            $this->payment->reference,
            $this->payment->application->reference,
            $this->payment->statusLabel()
        );
    }
}

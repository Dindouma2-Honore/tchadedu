<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Contracts\SmsGateway;
use App\Models\NotificationLog;
use Illuminate\Notifications\Notification;
use Throwable;

/**
 * Canal "sms" pour le système de notifications Laravel. Contrairement aux
 * canaux mail/database, celui-ci journalise lui-même chaque tentative
 * (succès ET échec) dans NotificationLog : un échec de passerelle SMS est
 * un scénario normal et attendu (réseau, opérateur, numéro invalide), pas
 * une erreur de configuration — il ne doit donc jamais interrompre l'envoi
 * aux autres destinataires ou sur les autres canaux (voir
 * Illuminate\Notifications\NotificationSender::sendToNotifiable : une
 * exception non rattrapée y stoppe l'envoi à TOUS les destinataires
 * restants, pas seulement au destinataire courant).
 */
class SmsChannel
{
    public function __construct(private readonly SmsGateway $gateway) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSms')) {
            return;
        }

        $phone = method_exists($notifiable, 'routeNotificationForSms')
            ? $notifiable->routeNotificationForSms($notification)
            : ($notifiable->phone ?? null);

        if (blank($phone)) {
            return;
        }

        $message = $notification->toSms($notifiable);

        try {
            $this->gateway->send($phone, $message);

            $this->log($notifiable, $notification, $message, 'sent');
        } catch (Throwable $exception) {
            $this->log($notifiable, $notification, $message, 'failed', $exception->getMessage());
        }
    }

    private function log(object $notifiable, Notification $notification, string $message, string $status, ?string $error = null): void
    {
        NotificationLog::create([
            'notifiable_type' => $notifiable::class,
            'notifiable_id' => $notifiable->getKey(),
            'application_id' => method_exists($notification, 'relatedApplicationId') ? $notification->relatedApplicationId() : null,
            'channel' => 'sms',
            'subject' => null,
            'body' => $message,
            'status' => $status,
            'error_message' => $error,
        ]);
    }
}

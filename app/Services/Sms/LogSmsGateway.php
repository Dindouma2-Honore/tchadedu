<?php

declare(strict_types=1);

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use Illuminate\Support\Facades\Log;

/**
 * Implémentation par défaut de SmsGateway : n'appelle aucun opérateur réel,
 * se contente d'écrire dans les logs applicatifs. C'est un CHOIX EXPLICITE
 * et documenté, pas un oubli : le projet n'a pas de contrat opérateur SMS
 * au moment de l'écriture de ce module, et il est hors de question de
 * coder en dur un fournisseur précis sans confirmation commerciale.
 *
 * À remplacer avant mise en production par une implémentation réelle de
 * SmsGateway (ex: AirtelSmsGateway, TigoSmsGateway...), liée dans
 * AppServiceProvider::register() à la place de celle-ci. Toute la
 * mécanique de notification (SmsChannel, NotificationLog, workflow) reste
 * inchangée : seul ce fichier change.
 */
class LogSmsGateway implements SmsGateway
{
    public function send(string $to, string $message): void
    {
        Log::info('[SIMULATION SMS — aucun opérateur réel connecté]', [
            'to' => $to,
            'message' => $message,
        ]);
    }
}

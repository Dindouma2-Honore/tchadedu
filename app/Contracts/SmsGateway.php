<?php

declare(strict_types=1);

namespace App\Contracts;

use RuntimeException;

/**
 * Abstraction du "SMS Gateway" du schéma d'architecture (section 4,
 * "Services Externes"). Le projet ne dépend d'aucun fournisseur SMS
 * particulier : pour brancher un vrai opérateur (Airtel, Tigo, Twilio,
 * Africa's Talking...), il suffit d'implémenter cette interface et de
 * changer la liaison dans AppServiceProvider::register() — aucun autre
 * fichier de l'application n'a besoin de changer.
 */
interface SmsGateway
{
    /**
     * @throws RuntimeException si l'envoi échoue (réseau, quota, numéro invalide...).
     */
    public function send(string $to, string $message): void;
}

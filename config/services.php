<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'payment_webhook' => [
        // Secret partagé pour signer/vérifier les callbacks de paiement
        // (HMAC-SHA256, voir App\Services\Payment\PaymentWebhookVerifier).
        // À remplacer par une vraie valeur secrète en production — celle
        // fournie par défaut ne sert qu'au développement local.
        'secret' => env('PAYMENT_WEBHOOK_SECRET', 'dev-only-insecure-secret-change-me'),
    ],

    'cinetpay' => [
        // Laissez ces 3 valeurs vides pour rester sur le prestataire de
        // démonstration (SimulatedPaymentGateway). Dès que apikey ET
        // site_id sont renseignés, AppServiceProvider bascule
        // automatiquement sur CinetPayGateway (vrai prestataire) sans
        // aucune autre modification de code. Récupérables sur
        // https://app-new.cinetpay.com (menu "Intégrations").
        'apikey' => env('CINETPAY_API_KEY'),
        'site_id' => env('CINETPAY_SITE_ID'),
        // Distincte de l'apikey : nécessaire pour vérifier le X-TOKEN des
        // notifications (voir CinetPayWebhookController). Optionnelle :
        // en son absence, la vérification serveur-à-serveur via
        // /v2/payment/check reste appliquée (voir CinetPayGateway::verify),
        // mais sans le premier filtre X-TOKEN.
        'secret_key' => env('CINETPAY_SECRET_KEY'),
    ],

];

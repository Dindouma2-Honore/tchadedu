<?php

use App\Http\Controllers\CinetPayReturnController;
use App\Http\Controllers\CinetPayWebhookController;
use App\Http\Controllers\DemoPaymentController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\ReceiptPdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Callback serveur-à-serveur du prestataire de paiement (section 18) —
// jamais appelé par le navigateur du candidat. Exclu du CSRF, voir bootstrap/app.php.
Route::post('/webhooks/payments', [PaymentWebhookController::class, 'handle'])->name('payments.webhook');

// Page de démonstration simulant un prestataire de paiement externe — à
// retirer une fois un vrai prestataire branché (voir SimulatedPaymentGateway).
Route::get('/demo-payments/{payment:reference}', [DemoPaymentController::class, 'show'])->name('payments.demo.show');
Route::post('/demo-payments/{payment:reference}/confirm', [DemoPaymentController::class, 'confirm'])->name('payments.demo.confirm');

// Intégration CinetPay réelle (voir App\Services\Payment\CinetPayGateway).
Route::post('/webhooks/payments/cinetpay', [CinetPayWebhookController::class, 'handle'])->name('payments.cinetpay.webhook');
Route::get('/payments/cinetpay/{payment:reference}/return', [CinetPayReturnController::class, 'show'])->name('payments.cinetpay.return');

// Téléchargement du reçu PDF — authentification + autorisation vérifiées dans le contrôleur (voir ReceiptPdfController).
Route::get('/receipts/{receipt}/download', [ReceiptPdfController::class, 'download'])->name('receipts.download');

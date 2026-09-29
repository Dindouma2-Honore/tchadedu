<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\View\View;

/**
 * Page affichée au navigateur du candidat après son passage sur la page
 * de paiement CinetPay. PUREMENT INFORMATIVE : conformément à la section
 * 18 ("le système ne doit jamais considérer une simple redirection ou un
 * clic comme preuve de paiement"), cette page ne modifie jamais le statut
 * du paiement — elle affiche simplement l'état actuel en base (déjà, ou
 * pas encore, mis à jour par le webhook serveur-à-serveur qui peut très
 * bien arriver après que le client ait été redirigé ici).
 */
class CinetPayReturnController extends Controller
{
    public function show(Payment $payment): View
    {
        return view('payments.cinetpay-return', ['payment' => $payment->fresh()]);
    }
}

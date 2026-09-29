<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Services\PdfRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Génère le PDF du reçu à la volée (jamais stocké sur disque : le reçu
 * n'est qu'un enregistrement — numéro + date — voir Receipt ; le PDF est
 * une simple représentation imprimable reconstruite à chaque téléchargement).
 */
class ReceiptPdfController extends Controller
{
    public function download(Receipt $receipt, PdfRenderer $renderer): Response|RedirectResponse
    {
        // Pas de route "login" nommée en dehors du panel Filament (dont le
        // nom réel est "filament.admin.auth.login") : on redirige donc
        // manuellement plutôt que de laisser le middleware "auth" générique
        // échouer en essayant de résoudre route('login').
        if (! Auth::check()) {
            return redirect()->route('filament.admin.auth.login');
        }

        Gate::authorize('view', $receipt->payment);

        $html = view('pdf.receipt', [
            'receipt' => $receipt,
            'payment' => $receipt->payment()->with('application.student', 'application.establishment', 'application.examSession')->first(),
        ])->render();

        $pdf = $renderer->render($html);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="recu-'.$receipt->number.'.pdf"',
        ]);
    }
}

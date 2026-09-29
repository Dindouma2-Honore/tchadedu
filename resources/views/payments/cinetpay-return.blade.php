<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Paiement en cours de vérification — Tchad Éducation Digital</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="5">
    <style>
        body { font-family: system-ui, sans-serif; background: #f3f4f6; margin: 0; padding: 2rem; }
        .card { max-width: 28rem; margin: 3rem auto; background: white; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,.1); padding: 2rem; text-align: center; }
        .status { padding: 0.75rem 1rem; border-radius: 0.5rem; margin: 1rem 0; font-weight: 600; }
        .status.success { background: #d1fae5; color: #065f46; }
        .status.pending { background: #dbeafe; color: #1e3a8a; }
        .status.failed { background: #fee2e2; color: #991b1b; }
        p.hint { color: #6b7280; font-size: 0.875rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Dossier {{ $payment->application->reference }}</h1>

        @php
            $cssClass = match ($payment->status) {
                \App\Models\Payment::STATUS_SUCCESS => 'success',
                \App\Models\Payment::STATUS_FAILED, \App\Models\Payment::STATUS_EXPIRED => 'failed',
                default => 'pending',
            };
        @endphp

        <div class="status {{ $cssClass }}">{{ $payment->statusLabel() }}</div>

        @if (! $payment->isTerminal())
            <p class="hint">Votre paiement est en cours de confirmation par l'opérateur. Cette page se rafraîchit automatiquement — vous pouvez aussi fermer cette fenêtre, le statut sera à jour dans votre espace dès confirmation.</p>
        @else
            <p class="hint">Vous pouvez fermer cette fenêtre et retourner sur la plateforme.</p>
        @endif
    </div>
</body>
</html>

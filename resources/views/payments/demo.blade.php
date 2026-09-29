<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Paiement (démonstration) — Tchad Éducation Digital</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: system-ui, sans-serif; background: #f3f4f6; margin: 0; padding: 2rem; }
        .card { max-width: 28rem; margin: 3rem auto; background: white; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,.1); padding: 2rem; }
        .banner { background: #fef3c7; color: #92400e; padding: 0.75rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; margin-bottom: 1.5rem; }
        h1 { font-size: 1.25rem; margin: 0 0 0.25rem; }
        .ref { color: #6b7280; font-size: 0.875rem; margin-bottom: 1.5rem; }
        .amount { font-size: 2rem; font-weight: 700; margin-bottom: 1.5rem; }
        .status { padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-weight: 600; }
        .status.success { background: #d1fae5; color: #065f46; }
        .status.failed { background: #fee2e2; color: #991b1b; }
        form { display: inline; }
        button { width: 100%; padding: 0.75rem; border: none; border-radius: 0.5rem; font-size: 1rem; font-weight: 600; cursor: pointer; margin-top: 0.5rem; }
        .btn-success { background: #16a34a; color: white; }
        .btn-fail { background: #e5e7eb; color: #374151; }
    </style>
</head>
<body>
    <div class="card">
        <div class="banner">
            🧪 Page de démonstration — aucun véritable prestataire de paiement n'est connecté. Un vrai déploiement redirigerait ici vers Orange Money, Airtel Money ou un autre opérateur.
        </div>

        <h1>Paiement des frais d'examen</h1>
        <p class="ref">Référence : {{ $payment->reference }} — Dossier {{ $payment->application->reference }}</p>
        <p class="amount">{{ number_format((float) $payment->amount, 0, ',', ' ') }} {{ $payment->currency }}</p>

        @if (session('status'))
            <div class="status {{ session('status') }}">
                @if (session('status') === 'success')
                    ✅ Paiement confirmé. Le dossier peut maintenant être soumis.
                @else
                    ❌ Paiement échoué. Vous pouvez réessayer.
                @endif
            </div>
        @endif

        @if (session('error'))
            <div class="status failed">{{ session('error') }}</div>
        @endif

        @if (! $payment->isTerminal())
            <form method="POST" action="{{ route('payments.demo.confirm', $payment->reference) }}">
                @csrf
                <input type="hidden" name="outcome" value="success">
                <button type="submit" class="btn-success">Simuler un paiement réussi</button>
            </form>
            <form method="POST" action="{{ route('payments.demo.confirm', $payment->reference) }}">
                @csrf
                <input type="hidden" name="outcome" value="failed">
                <button type="submit" class="btn-fail">Simuler un échec</button>
            </form>
        @else
            <p>Statut actuel : <strong>{{ $payment->statusLabel() }}</strong></p>
        @endif
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #1f2937; margin: 0; }
        .header { display: table; width: 100%; margin-bottom: 24px; border-bottom: 3px solid #1d4ed8; padding-bottom: 12px; }
        .header .left { display: table-cell; width: 60%; vertical-align: top; }
        .header .right { display: table-cell; width: 40%; vertical-align: top; text-align: right; }
        .title { font-size: 20px; font-weight: bold; color: #1d4ed8; margin: 0 0 4px; }
        .subtitle { color: #6b7280; margin: 0; }
        .receipt-number { font-size: 14px; font-weight: bold; }
        table.info { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        table.info td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; }
        table.info td.label { color: #6b7280; width: 40%; }
        table.info td.value { font-weight: bold; }
        .amount-box { background: #ecfdf5; border: 1px solid #6ee7b7; border-radius: 6px; padding: 16px; text-align: center; margin: 24px 0; }
        .amount-box .amount { font-size: 28px; font-weight: bold; color: #065f46; }
        .amount-box .label { color: #065f46; }
        .footer { margin-top: 40px; padding-top: 12px; border-top: 1px solid #e5e7eb; color: #9ca3af; font-size: 10px; text-align: center; }
        .stamp { display: inline-block; border: 2px solid #16a34a; color: #16a34a; padding: 6px 16px; border-radius: 4px; font-weight: bold; transform: rotate(-4deg); }
    </style>
</head>
<body>
    <div class="header">
        <div class="left">
            <p class="title">TCHAD ÉDUCATION DIGITAL</p>
            <p class="subtitle">Plateforme nationale de gestion des examens scolaires</p>
        </div>
        <div class="right">
            <p class="receipt-number">Reçu n° {{ $receipt->number }}</p>
            <p class="subtitle">Émis le {{ $receipt->issued_at->translatedFormat('d F Y à H:i') }}</p>
        </div>
    </div>

    <table class="info">
        <tr>
            <td class="label">Dossier de candidature</td>
            <td class="value">{{ $payment->application->reference }}</td>
        </tr>
        <tr>
            <td class="label">Candidat</td>
            <td class="value">{{ $payment->application->student->first_name }} {{ $payment->application->student->last_name }}</td>
        </tr>
        <tr>
            <td class="label">Établissement</td>
            <td class="value">{{ $payment->application->establishment->name }}</td>
        </tr>
        <tr>
            <td class="label">Session d'examen</td>
            <td class="value">{{ $payment->application->examSession->label }}</td>
        </tr>
        <tr>
            <td class="label">Référence du paiement</td>
            <td class="value">{{ $payment->reference }}</td>
        </tr>
        <tr>
            <td class="label">Moyen de paiement</td>
            <td class="value">{{ $payment->provider === 'simulated' ? 'Démonstration' : $payment->provider }}</td>
        </tr>
        <tr>
            <td class="label">Date de paiement</td>
            <td class="value">{{ $payment->paid_at?->translatedFormat('d F Y à H:i') }}</td>
        </tr>
    </table>

    <div class="amount-box">
        <p class="label">Montant réglé</p>
        <p class="amount">{{ number_format((float) $payment->amount, 0, ',', ' ') }} {{ $payment->currency }}</p>
        <span class="stamp">PAYÉ</span>
    </div>

    <div class="footer">
        Ce reçu atteste du règlement des frais d'examen pour le dossier référencé ci-dessus.
        Document généré automatiquement par Tchad Éducation Digital — reçu n° {{ $receipt->number }}.
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; }
        .header { border-bottom: 3px solid #1d4ed8; padding-bottom: 12px; margin-bottom: 20px; }
        .title { font-size: 18px; font-weight: bold; color: #1d4ed8; margin: 0 0 4px; }
        .subtitle { color: #6b7280; margin: 0; }
        .meta { color: #6b7280; font-size: 10px; margin-top: 4px; }
        table.summary { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        table.summary td { padding: 10px; text-align: center; border: 1px solid #e5e7eb; }
        table.summary td .value { display: block; font-size: 18px; font-weight: bold; }
        table.summary td .label { display: block; color: #6b7280; font-size: 9px; text-transform: uppercase; }
        table.breakdown { width: 100%; border-collapse: collapse; }
        table.breakdown th { background: #1d4ed8; color: white; padding: 6px 8px; text-align: left; font-size: 10px; text-transform: uppercase; }
        table.breakdown th.num, table.breakdown td.num { text-align: right; }
        table.breakdown td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; }
        table.breakdown tr:nth-child(even) { background: #f9fafb; }
        .footer { margin-top: 24px; color: #9ca3af; font-size: 9px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <p class="title">TCHAD ÉDUCATION DIGITAL — Rapport statistique</p>
        <p class="subtitle">{{ $sessionLabel }}</p>
        <p class="meta">Généré le {{ now()->translatedFormat('d F Y à H:i') }} par {{ $generatedBy }}</p>
    </div>

    <table class="summary">
        <tr>
            <td><span class="value">{{ $counts['total'] }}</span><span class="label">Total</span></td>
            <td><span class="value">{{ $counts['en_cours'] }}</span><span class="label">En cours</span></td>
            <td><span class="value">{{ $counts['a_corriger'] }}</span><span class="label">À corriger</span></td>
            <td><span class="value">{{ $counts['inscrit'] }}</span><span class="label">Inscrits</span></td>
            <td><span class="value">{{ $counts['rejetee'] }}</span><span class="label">Rejetés</span></td>
            <td><span class="value">{{ $averageDays !== null ? $averageDays.' j' : '—' }}</span><span class="label">Délai moyen</span></td>
        </tr>
    </table>

    <table class="breakdown">
        <thead>
            <tr>
                <th>{{ $breakdownLabel }}</th>
                <th class="num">Total</th>
                <th class="num">Inscrits</th>
                <th class="num">À corriger</th>
                <th class="num">Rejetés</th>
                <th class="num">Taux validation</th>
                <th class="num">Délai moyen</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td class="num">{{ $row['total'] }}</td>
                    <td class="num">{{ $row['inscrits'] }}</td>
                    <td class="num">{{ $row['a_corriger'] }}</td>
                    <td class="num">{{ $row['rejetees'] }}</td>
                    <td class="num">{{ $row['taux_validation'] }}%</td>
                    <td class="num">{{ $row['delai_moyen_jours'] !== null ? $row['delai_moyen_jours'].' j' : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Aucune donnée pour ce filtre.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Document généré automatiquement — Tchad Éducation Digital.</div>
</body>
</html>

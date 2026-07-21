<!-- resources/views/admin/reports/pdf.blade.php -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport d'Activité de Ventes</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            line-height: 1.5;
            font-size: 14px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        .title {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
            color: #1a202c;
        }
        .subtitle {
            font-size: 14px;
            color: #718096;
            margin-top: 5px;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #2d3748;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 5px;
            margin-top: 30px;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #edf2f7;
        }
        th {
            background-color: #f7fafc;
            font-weight: bold;
            color: #4a5568;
        }
        .total-box {
            background-color: #f7fafc;
            border: 1px solid #e2e8f0;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .total-row {
            display: table;
            width: 100%;
            margin-bottom: 5px;
        }
        .total-label {
            display: table-cell;
            font-weight: bold;
            color: #4a5568;
        }
        .total-value {
            display: table-cell;
            text-align: right;
            font-weight: bold;
            font-size: 16px;
            color: #2b6cb0;
        }
        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header">
        <h1 class="title">🍔 BURGER PALACE</h1>
        <div class="subtitle">Rapport d'Activité de Ventes & Clôture TVA</div>
        <div style="font-size: 12px; margin-top: 10px; color: #4a5568;">
            Période du <strong>{{ $startDate }}</strong> au <strong>{{ $endDate }}</strong>
        </div>
    </div>

    <!-- 1. FINANCIAL SUMMARY -->
    <div class="section-title">📊 Synthèse Financière</div>
    <table>
        <thead>
            <tr>
                <th>Indicateur</th>
                <th class="text-right">Montant (Hors-Taxe)</th>
                <th class="text-right">Taxe (TVA)</th>
                <th class="text-right">Total (TTC)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Chiffre d'Affaires Global</strong></td>
                <td class="text-right">{{ number_format($totals->total_ht, 2, ',', ' ') }} €</td>
                <td class="text-right" style="color: #dd6b20;">{{ number_format($totals->total_tva, 2, ',', ' ') }} €</td>
                <td class="text-right" style="font-weight: bold; color: #2b6cb0;">{{ number_format($totals->total_ttc, 2, ',', ' ') }} €</td>
            </tr>
        </tbody>
    </table>

    <!-- 2. VAT BREAKDOWN -->
    <div class="section-title">⚖️ Détail de la TVA Collectée par Taux (French Brackets)</div>
    <table>
        <thead>
            <tr>
                <th>Taux de TVA</th>
                <th class="text-right">Base TTC</th>
                <th class="text-right">Montant TVA Collecté</th>
            </tr>
        </thead>
        <tbody>
            @foreach($vatBreakdown as $bracket)
                <tr>
                    <td><strong>TVA {{ number_format($bracket->vat_rate, 1, ',', ' ') }}%</strong></td>
                    <td class="text-right">{{ number_format($bracket->total_ttc, 2, ',', ' ') }} €</td>
                    <td class="text-right" style="font-weight: bold; color: #38a169;">+{{ number_format($bracket->collected_vat, 2, ',', ' ') }} €</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- 3. PAYMENT METHODS -->
    <div class="section-title">💳 Récapitulatif des Règlements</div>
    <table>
        <thead>
            <tr>
                <th>Mode de Paiement</th>
                <th class="text-right">Montant Total Réglé</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $payment)
                <tr>
                    <td style="text-transform: capitalize;">
                        @if($payment->method === 'cash') 💵 Espèces @elseif($payment->method === 'card') 💳 Carte @else 🎟️ Ticket Resto @endif
                    </td>
                    <td class="text-right" style="font-weight: bold;">{{ number_format($payment->total, 2, ',', ' ') }} €</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Total Footer Box -->
    <div class="total-box">
        <div class="total-row">
            <span class="total-label">Total Recettes Réglées (TTC) :</span>
            <span class="total-value">{{ number_format($totals->total_ttc, 2, ',', ' ') }} €</span>
        </div>
    </div>

    <!-- Legal Footer -->
    <div style="margin-top: 50px; text-align: center; font-size: 11px; color: #a0aec0; border-top: 1px solid #edf2f7; padding-top: 15px;">
        Document généré automatiquement par Burger Palace POS SaaS le {{ now()->format('d/m/Y H:i') }}. <br>
        Certifié conforme en application de l'article 286 du CGI.
    </div>

</body>
</html>
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
            font-size: 13px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 12px;
            margin-bottom: 25px;
        }
        .title {
            font-size: 22px;
            font-weight: bold;
            margin: 0;
            color: #1a202c;
        }
        .subtitle {
            font-size: 12px;
            color: #718096;
            margin-top: 5px;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #2d3748;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 5px;
            margin-top: 25px;
            margin-bottom: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 8px;
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
            padding: 12px;
            border-radius: 6px;
            margin-top: 20px;
        }
        .total-row {
            display: table;
            width: 100%;
            margin-bottom: 3px;
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
            font-size: 15px;
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
        
        <!-- 🚀 Dynamic Document Title based on Report Type -->
        <div class="subtitle">
            @if($reportType === 'sales')
                Rapport de Ventes & Clôture TVA (Sales)
            @elseif($reportType === 'purchases')
                Rapport des Approvisionnements & COGS (Purchases)
            @elseif($reportType === 'expenses')
                Rapport des Dépenses Opérationnelles (Expenses)
            @else
                Compte de Résultat Simplifié (P&L global)
            @endif
        </div>
        
        <div style="font-size: 11px; margin-top: 8px; color: #4a5568;">
            Période du <strong>{{ $startDate }}</strong> au <strong>{{ $endDate }}</strong>
        </div>
    </div>

    <!-- ==========================================
         🚀 REPORT TYPE: SALES REPORT
         ========================================== -->
    @if($reportType === 'sales')
        <div class="section-title">📊 Synthèse de Ventes</div>
        <table>
            <thead>
                <tr>
                    <th>Indicateur</th>
                    <th class="text-right">Valeur</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Tickets de caisse émis</td>
                    <td class="text-right font-bold">{{ $totals->total_orders }}</td>
                </tr>
                <tr>
                    <td>Chiffre d'Affaires Brut (TTC)</td>
                    <td class="text-right font-bold">{{ number_format($totals->total_ttc, 2, ',', ' ') }} €</td>
                </tr>
                <tr>
                    <td>TVA Collectée (Total)</td>
                    <td class="text-right font-bold">{{ number_format($totals->total_tva, 2, ',', ' ') }} €</td>
                </tr>
            </tbody>
        </table>

        <div class="section-title">⚖️ Clôture de la TVA (Tax Brackets)</div>
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

        <div class="section-title">📈 Top-Ventes Produits (Volume d'activité)</div>
        <table>
            <thead>
                <tr>
                    <th>Produit</th>
                    <th class="text-right">Quantité Vendue</th>
                    <th class="text-right">Volume TTC</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topProducts as $item)
                    <tr>
                        <td><strong>{{ $item->product_name }}</strong></td>
                        <td class="text-right">{{ number_format($item->qty_sold, 0, ',', ' ') }}</td>
                        <td class="text-right">{{ number_format($item->total_ttc, 2, ',', ' ') }} €</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="total-box">
            <div class="total-row">
                <span class="total-label">Chiffre d'Affaires Global (TTC) :</span>
                <span class="total-value">{{ number_format($totals->total_ttc, 2, ',', ' ') }} €</span>
            </div>
        </div>

    <!-- ==========================================
         🚀 REPORT TYPE: PURCHASES REPORT
         ========================================== -->
    @elseif($reportType === 'purchases')
        <div class="section-title">📦 Journal des Livraisons Reçues (Audits COGS)</div>
        @if($purchasesList->isEmpty())
            <p style="color: #718096; text-align: center; padding: 20px;">Aucune livraison enregistrée sur cette période.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Date Réception</th>
                        <th>Bon (PO #)</th>
                        <th>Fournisseur</th>
                        <th>N° Facture</th>
                        <th class="text-right">Coût TTC</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchasesList as $po)
                        <tr>
                            <td>{{ $po->received_at->format('d/m/Y') }}</td>
                            <td><strong>PO #{{ $po->po_number }}</strong></td>
                            <td>{{ $po->supplier->name ?? 'N/A' }}</td>
                            <td><code>{{ $po->invoice_number }}</code></td>
                            <td class="text-right font-bold">{{ number_format($po->total_cost, 2, ',', ' ') }} €</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="total-box">
            <div class="total-row">
                <span class="total-label">Total Coût des Matières Premières (COGS) :</span>
                <span class="total-value" style="color: #e53e3e;">{{ number_format($totalPurchasesCost, 2, ',', ' ') }} €</span>
            </div>
        </div>

    <!-- ==========================================
         🚀 REPORT TYPE: EXPENSES REPORT
         ========================================== -->
    @elseif($reportType === 'expenses')
        <div class="section-title">💸 Journal des Dépenses de Fonctionnement (OPEX)</div>
        @if($expensesList->isEmpty())
            <p style="color: #718096; text-align: center; padding: 20px;">Aucune dépense de fonctionnement enregistrée sur cette période.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Catégorie</th>
                        <th>Description</th>
                        <th>Mode Règlement</th>
                        <th class="text-right">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($expensesList as $exp)
                        <tr>
                            <td>{{ $exp->created_at->format('d/m/Y') }}</td>
                            <td><span style="text-transform: uppercase; font-size: 11px; font-weight: bold;">{{ $exp->category }}</span></td>
                            <td>{{ $exp->description }}</td>
                            <td style="text-transform: capitalize;">{{ str_replace('_', ' ', $exp->payment_method) }}</td>
                            <td class="text-right font-bold" style="color: #718096;">-{{ number_format($exp->amount, 2, ',', ' ') }} €</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="total-box">
            <div class="total-row">
                <span class="total-label">Total des Charges de Fonctionnement (OPEX) :</span>
                <span class="total-value" style="color: #718096;">{{ number_format($totalExpensesCost, 2, ',', ' ') }} €</span>
            </div>
        </div>

    <!-- ==========================================
         🚀 REPORT TYPE: GENERAL P&L STATEMENT (DEFAULT)
         ========================================== -->
    @else
        <div class="section-title">📊 Synthèse Financière Global</div>
        <table>
            <thead>
                <tr>
                    <th>Indicateur</th>
                    <th class="text-right">Base HT</th>
                    <th class="text-right">Taxes (TVA)</th>
                    <th class="text-right">Chiffre TTC</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Chiffre d'Affaires Global (A)</strong></td>
                    <td class="text-right">{{ number_format($totals->total_ht, 2, ',', ' ') }} €</td>
                    <td class="text-right">{{ number_format($totals->total_tva, 2, ',', ' ') }} €</td>
                    <td class="text-right" style="font-weight: bold; color: #2b6cb0;">{{ number_format($totals->total_ttc, 2, ',', ' ') }} €</td>
                </tr>
            </tbody>
        </table>

        <div class="section-title">⚖️ Compte de Résultat Simplifié (HT)</div>
        <table>
            <tbody>
                <tr>
                    <td><strong>Ventes Nettes (HT) (A) :</strong></td>
                    <td class="text-right" style="font-weight: bold;">{{ number_format($totals->total_ht, 2, ',', ' ') }} €</td>
                </tr>
                <tr style="color: #e53e3e;">
                    <td><strong>Coût des Matières (COGS - Food Cost) (B) :</strong></td>
                    <td class="text-right" style="font-weight: bold;">-{{ number_format($totalPurchasesCost, 2, ',', ' ') }} €</td>
                </tr>
                <tr style="color: #718096;">
                    <td><strong>Frais de Fonctionnement (OPEX) (C) :</strong></td>
                    <td class="text-right" style="font-weight: bold;">-{{ number_format($totalExpensesCost, 2, ',', ' ') }} €</td>
                </tr>
            </tbody>
        </table>

        <div class="total-box">
            <div class="total-row">
                <span class="total-label">Bénéfice Net Estimé (A - B - C) :</span>
                <span class="total-value" style="color: {{ ($totals->total_ht - ($totalPurchasesCost + $totalExpensesCost)) >= 0 ? '#38a169' : '#e53e3e' }};">
                    {{ number_format($totals->total_ht - ($totalPurchasesCost + $totalExpensesCost), 2, ',', ' ') }} €
                </span>
            </div>
        </div>
    @endif

    <!-- Legal Footer -->
    <div style="margin-top: 50px; text-align: center; font-size: 10px; color: #a0aec0; border-top: 1px solid #edf2f7; padding-top: 15px;">
        Document généré automatiquement par Burger Palace POS le {{ now()->format('d/m/Y H:i') }}. <br>
        Certifié conforme en application de l'article 286 du CGI.
    </div>

</body>
</html>
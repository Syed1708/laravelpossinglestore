<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Sales &amp; Financial Activity Report — Burger Palace</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #2d3748;
            line-height: 1.5;
            font-size: 12px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #2d3748;
            padding-bottom: 12px;
            margin-bottom: 25px;
        }
        .title {
            font-size: 22px;
            font-weight: bold;
            margin: 0;
            color: #1a202c;
            letter-spacing: 0.5px;
        }
        .subtitle {
            font-size: 11px;
            color: #718096;
            margin-top: 5px;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .period {
            font-size: 11px;
            margin-top: 6px;
            color: #4a5568;
        }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #1a202c;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 5px;
            margin-top: 22px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        th, td {
            padding: 7px 10px;
            text-align: left;
            border-bottom: 1px solid #edf2f7;
        }
        th {
            background-color: #f7fafc;
            font-weight: bold;
            font-size: 11px;
            color: #4a5568;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .total-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 12px 16px;
            border-radius: 6px;
            margin-top: 15px;
        }
        .total-row {
            display: table;
            width: 100%;
        }
        .total-label {
            display: table-cell;
            font-weight: bold;
            color: #4a5568;
            font-size: 13px;
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
        .font-bold {
            font-weight: bold;
        }
        .empty-text {
            color: #a0aec0;
            text-align: center;
            padding: 20px;
            font-style: italic;
        }
        .badge {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 2px 6px;
            border-radius: 4px;
            background: #edf2f7;
            color: #4a5568;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #a0aec0;
            border-top: 1px solid #edf2f7;
            padding-top: 15px;
            line-height: 1.6;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header">
        <h1 class="title">🍔 BURGER PALACE</h1>
        
        <!-- Dynamic Document Title based on Report Type -->
        <div class="subtitle">
            @if($reportType === 'sales')
                Sales &amp; VAT Tax Ledger
            @elseif($reportType === 'purchases')
                Supplier Purchases &amp; Inventory Deliveries (COGS)
            @elseif($reportType === 'expenses')
                Operating Expenses Ledger (OPEX)
            @else
                Executive Profit &amp; Loss Statement (P&amp;L)
            @endif
        </div>
        
        <div class="period">
            Reporting Period: <strong>{{ $startDate }}</strong> to <strong>{{ $endDate }}</strong>
        </div>
    </div>

    <!-- ==========================================
         1. REPORT TYPE: SALES & VAT REPORT
         ========================================== -->
    @if($reportType === 'sales')
        <div class="section-title">📊 Sales Activity Summary</div>
        <table>
            <thead>
                <tr>
                    <th>Metric</th>
                    <th class="text-right">Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Completed Orders</td>
                    <td class="text-right font-bold">{{ number_format($totals->total_orders ?? 0) }}</td>
                </tr>
                <tr>
                    <td>Gross Sales Revenue (Incl. VAT)</td>
                    <td class="text-right font-bold">{{ $currencySymbol }}{{ number_format($totals->total_ttc ?? 0, 2, '.', ',') }}</td>
                </tr>
                <tr>
                    <td>Net Sales Revenue (Excl. VAT)</td>
                    <td class="text-right font-bold">{{ $currencySymbol }}{{ number_format($totals->total_ht ?? 0, 2, '.', ',') }}</td>
                </tr>
                <tr>
                    <td>Total VAT Collected</td>
                    <td class="text-right font-bold" style="color: #38a169;">{{ $currencySymbol }}{{ number_format($totals->total_tva ?? 0, 2, '.', ',') }}</td>
                </tr>
            </tbody>
        </table>

        <div class="section-title">⚖️ VAT Tax Breakdown (French Brackets)</div>
        <table>
            <thead>
                <tr>
                    <th>VAT Bracket</th>
                    <th class="text-right">Gross Total (TTC)</th>
                    <th class="text-right">VAT Amount Collected</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vatBreakdown as $bracket)
                    <tr>
                        <td><strong>VAT {{ number_format($bracket->vat_rate, 1) }}%</strong></td>
                        <td class="text-right">{{ $currencySymbol }}{{ number_format($bracket->total_ttc, 2, '.', ',') }}</td>
                        <td class="text-right font-bold" style="color: #38a169;">+{{ $currencySymbol }}{{ number_format($bracket->collected_vat, 2, '.', ',') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="empty-text">No product sales recorded for this period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="section-title">💳 Payment Methods Breakdown</div>
        <table>
            <thead>
                <tr>
                    <th>Payment Method</th>
                    <th class="text-right">Total Collected</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    @php
                        $methodLabel = match($payment->method) {
                            'cash'            => 'Cash (Till)',
                            'card'            => 'Card (POS Terminal)',
                            'stripe_checkout' => 'Stripe (Web / Online)',
                            'card_terminal'   => 'Card (Kiosk Terminal)',
                            'split'           => 'Split Payment',
                            'bank_transfer'   => 'Bank Transfer',
                            default           => ucfirst($payment->method),
                        };
                    @endphp
                    <tr>
                        <td><strong>{{ $methodLabel }}</strong></td>
                        <td class="text-right font-bold">{{ $currencySymbol }}{{ number_format($payment->total, 2, '.', ',') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="empty-text">No payment transactions recorded for this period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="section-title">🏆 Top Selling Products (By Volume)</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product Name</th>
                    <th class="text-right">Quantity Sold</th>
                    <th class="text-right">Gross Total (TTC)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($topProducts as $idx => $item)
                    <tr>
                        <td style="width: 30px; color: #718096;">#{{ $idx + 1 }}</td>
                        <td><strong>{{ $item->product_name }}</strong></td>
                        <td class="text-right">{{ number_format($item->qty_sold) }}</td>
                        <td class="text-right font-bold">{{ $currencySymbol }}{{ number_format($item->total_ttc, 2, '.', ',') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty-text">No product sales recorded.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="total-box">
            <div class="total-row">
                <span class="total-label">Total Gross Sales (Incl. VAT):</span>
                <span class="total-value">{{ $currencySymbol }}{{ number_format($totals->total_ttc ?? 0, 2, '.', ',') }}</span>
            </div>
        </div>

    <!-- ==========================================
         2. REPORT TYPE: PURCHASES & DELIVERIES (COGS)
         ========================================== -->
    @elseif($reportType === 'purchases')
        <div class="section-title">📦 Received Supplier Deliveries (COGS Audit)</div>
        @if($purchasesList->isEmpty())
            <p class="empty-text">No supplier deliveries recorded for this period.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Delivery Date</th>
                        <th>PO #</th>
                        <th>Supplier</th>
                        <th>Invoice #</th>
                        <th class="text-right">Total Cost</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchasesList as $po)
                        <tr>
                            <td>{{ $po->received_at ? $po->received_at->format('d/m/Y') : '—' }}</td>
                            <td><strong>PO #{{ $po->po_number }}</strong></td>
                            <td>{{ $po->supplier->name ?? 'N/A' }}</td>
                            <td><code>{{ $po->invoice_number }}</code></td>
                            <td class="text-right font-bold">{{ $currencySymbol }}{{ number_format($po->total_cost, 2, '.', ',') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="total-box">
            <div class="total-row">
                <span class="total-label">Total Raw Materials &amp; Inventory Cost (COGS):</span>
                <span class="total-value" style="color: #e53e3e;">-{{ $currencySymbol }}{{ number_format($totalPurchasesCost, 2, '.', ',') }}</span>
            </div>
        </div>

    <!-- ==========================================
         3. REPORT TYPE: EXPENSES (OPEX)
         ========================================== -->
    @elseif($reportType === 'expenses')
        <div class="section-title">💸 Operating Expenses Ledger (OPEX)</div>
        @if($expensesList->isEmpty())
            <p class="empty-text">No operating expenses recorded for this period.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Payment Date</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Payment Method</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($expensesList as $exp)
                        <tr>
                            <td>{{ ($exp->paid_at ?? $exp->created_at)->format('d/m/Y') }}</td>
                            <td><span class="badge">{{ $exp->expenseCategory->name ?? ucfirst($exp->category) }}</span></td>
                            <td>{{ $exp->description }}</td>
                            <td style="text-transform: capitalize;">{{ str_replace('_', ' ', $exp->payment_method ?? 'N/A') }}</td>
                            <td class="text-right font-bold" style="color: #718096;">-{{ $currencySymbol }}{{ number_format($exp->amount, 2, '.', ',') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="total-box">
            <div class="total-row">
                <span class="total-label">Total Operating Expenses (OPEX):</span>
                <span class="total-value" style="color: #e53e3e;">-{{ $currencySymbol }}{{ number_format($totalExpensesCost, 2, '.', ',') }}</span>
            </div>
        </div>

    <!-- ==========================================
         4. REPORT TYPE: P&L STATEMENT (DEFAULT)
         ========================================== -->
    @else
        <div class="section-title">📊 Executive Financial Overview</div>
        <table>
            <thead>
                <tr>
                    <th>Financial Component</th>
                    <th class="text-right">Net (Excl. VAT)</th>
                    <th class="text-right">Taxes (VAT)</th>
                    <th class="text-right">Gross (Incl. VAT)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Gross Operating Revenue (A)</strong></td>
                    <td class="text-right">{{ $currencySymbol }}{{ number_format($totals->total_ht ?? 0, 2, '.', ',') }}</td>
                    <td class="text-right">{{ $currencySymbol }}{{ number_format($totals->total_tva ?? 0, 2, '.', ',') }}</td>
                    <td class="text-right font-bold" style="color: #2b6cb0;">{{ $currencySymbol }}{{ number_format($totals->total_ttc ?? 0, 2, '.', ',') }}</td>
                </tr>
            </tbody>
        </table>

        <div class="section-title">⚖️ Income Statement Summary (Net Basis)</div>
        <table>
            <tbody>
                <tr>
                    <td><strong>Net Sales Revenue (Excl. VAT) (A):</strong></td>
                    <td class="text-right font-bold">{{ $currencySymbol }}{{ number_format($totals->total_ht ?? 0, 2, '.', ',') }}</td>
                </tr>
                <tr style="color: #e53e3e;">
                    <td><strong>Cost of Goods Sold (COGS - Deliveries) (B):</strong></td>
                    <td class="text-right font-bold">-{{ $currencySymbol }}{{ number_format($totalPurchasesCost, 2, '.', ',') }}</td>
                </tr>
                <tr style="color: #718096;">
                    <td><strong>Operating &amp; Staff Expenses (OPEX) (C):</strong></td>
                    <td class="text-right font-bold">-{{ $currencySymbol }}{{ number_format($totalExpensesCost, 2, '.', ',') }}</td>
                </tr>
            </tbody>
        </table>

        @php
            $netProfit = ($totals->total_ht ?? 0) - ($totalPurchasesCost + $totalExpensesCost);
            $marginPct = ($totals->total_ht ?? 0) > 0 ? round(($netProfit / $totals->total_ht) * 100, 1) : 0;
        @endphp

        <div class="total-box">
            <div class="total-row">
                <span class="total-label">Estimated Net Operating Profit (A - B - C):</span>
                <span class="total-value" style="color: {{ $netProfit >= 0 ? '#38a169' : '#e53e3e' }};">
                    {{ $netProfit < 0 ? '-' : '' }}{{ $currencySymbol }}{{ number_format(abs($netProfit), 2, '.', ',') }}
                    <span style="font-size: 11px; color: #718096; font-weight: normal;">({{ $marginPct }}% margin)</span>
                </span>
            </div>
        </div>
    @endif

    <!-- Legal & Fiscal Compliance Footer -->
    <div class="footer">
        Document automatically generated by Burger Palace POS on {{ now('Europe/Paris')->format('Y-m-d H:i') }} (Europe/Paris).<br>
        Certified compliant in accordance with French fiscal integrity standards (Art. 286-I-3° bis of the CGI / NF525).
    </div>

</body>
</html>